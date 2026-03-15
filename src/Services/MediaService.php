<?php

namespace AnimaID\Services;

use AnimaID\Repositories\MediaRepository;
use AnimaID\Config\ConfigManager;
use AnimaID\Exceptions\NotFoundException;
use AnimaID\Exceptions\ForbiddenException;
use AnimaID\Exceptions\ValidationException;

/**
 * Media Service
 * Handles file uploads, downloads, folder management, and share-link generation
 */
class MediaService
{
    private MediaRepository $mediaRepository;
    private ConfigManager $config;

    // Text MIME types that support in-browser preview
    private const TEXT_MIME_TYPES = [
        'text/plain', 'text/csv', 'text/markdown', 'text/html', 'text/css',
        'text/javascript', 'application/json', 'application/xml', 'application/javascript',
    ];

    // Maximum size for text file preview (100 KB)
    private const PREVIEW_MAX_FILE_SIZE = 100 * 1024;

    // Maximum characters returned in a preview snippet
    private const PREVIEW_MAX_CHARS = 512;

    public function __construct(
        MediaRepository $mediaRepository,
        ConfigManager $config
    ) {
        $this->mediaRepository = $mediaRepository;
        $this->config = $config;
    }

    // -------------------------------------------------------------------------
    // Listing
    // -------------------------------------------------------------------------

    /**
     * List folders and root-level files for a given folder context
     */
    public function listMedia(int $page = 1, int $limit = 20, ?int $folderId = null, string $search = ''): array
    {
        $offset = ($page - 1) * $limit;

        $folders = $this->mediaRepository->findFolders($limit, $offset, $folderId);
        $files = $this->mediaRepository->findFiles($limit, $offset, $folderId, $search);

        return [
            'folders' => $folders,
            'files' => $files,
            'total_items' => count($folders) + count($files),
        ];
    }

    /**
     * Get details of a single file
     *
     * @throws NotFoundException
     */
    public function getFile(int $fileId): array
    {
        $file = $this->mediaRepository->findFileById($fileId);
        if (!$file) {
            throw new NotFoundException('File not found');
        }
        return $file;
    }

    // -------------------------------------------------------------------------
    // Upload
    // -------------------------------------------------------------------------

    /**
     * Handle a multipart file upload from $_FILES['file']
     *
     * @param array $uploadedFile  Entry from $_FILES (e.g. $_FILES['file'])
     * @param int   $userId        ID of the authenticated uploader
     * @param mixed $folderId      Target folder ID (null / empty string = root)
     * @throws ValidationException
     */
    public function uploadFile(array $uploadedFile, int $userId, mixed $folderId = null): int
    {
        // Validate PHP upload error
        if ($uploadedFile['error'] !== UPLOAD_ERR_OK) {
            throw new ValidationException('File upload failed: error code ' . $uploadedFile['error']);
        }

        // Validate file size
        $maxSize = $this->config->get('uploads.max_file_size', 10 * 1024 * 1024);
        if ($uploadedFile['size'] > $maxSize) {
            $maxMb = round($maxSize / (1024 * 1024));
            throw new ValidationException("File size exceeds {$maxMb}MB limit");
        }

        // Validate extension
        $extension = strtolower(pathinfo($uploadedFile['name'], PATHINFO_EXTENSION));
        $allowedExtensions = $this->config->get('uploads.allowed_extensions', []);
        if (!empty($allowedExtensions) && !in_array($extension, $allowedExtensions, true)) {
            throw new ValidationException("File extension '{$extension}' is not allowed");
        }

        // Resolve upload directory
        $uploadBase = rtrim($this->config->get('uploads.upload_path', __DIR__ . '/../../uploads/'), '/');
        $uploadDir = $uploadBase . '/media/';

        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }

        // Generate a unique filename on disk
        $filename = uniqid('media_', true) . '.' . $extension;
        $filePath = $uploadDir . $filename;

        if (!move_uploaded_file($uploadedFile['tmp_name'], $filePath)) {
            throw new ValidationException('Failed to save uploaded file');
        }

        // Determine file type category
        $fileType = $this->resolveFileType($uploadedFile['type']);

        // Build the record
        $fileData = [
            'original_name' => $uploadedFile['name'],
            'filename'      => $filename,
            'file_path'     => $filePath,
            'file_size'     => $uploadedFile['size'],
            'mime_type'     => $uploadedFile['type'],
            'file_type'     => $fileType,
            'uploaded_by'   => $userId,
        ];

        // Only attach folder_id when it is a meaningful value
        if (!empty($folderId) && $folderId !== 'null') {
            $fileData['folder_id'] = (int) $folderId;
        }

        return $this->mediaRepository->createFile($fileData);
    }

    // -------------------------------------------------------------------------
    // Download / preview
    // -------------------------------------------------------------------------

    /**
     * Serve a file for download or preview.
     *
     * Returns an array when the file content should be delivered as JSON
     * (text preview requested via Accept: application/json), or exits after
     * streaming binary content directly to the client.
     *
     * @throws NotFoundException
     */
    public function downloadFile(int $fileId, bool $jsonPreviewRequested): array
    {
        $file = $this->mediaRepository->findFileById($fileId);
        if (!$file) {
            throw new NotFoundException('File not found');
        }

        if (!file_exists($file['file_path'])) {
            throw new NotFoundException('File not found on disk');
        }

        // Increment download counter
        $this->mediaRepository->incrementDownloadCount($fileId);

        $isTextFile = $this->isTextMimeType($file['mime_type']);

        if ($isTextFile && $jsonPreviewRequested) {
            return $this->buildTextPreviewResponse($file);
        }

        // Stream the file directly to the client
        $this->streamFile($file);

        // streamFile() exits; this return is never reached but satisfies the type system
        return [];
    }

    /**
     * Build a JSON-compatible preview payload for text files
     */
    private function buildTextPreviewResponse(array $file): array
    {
        $fileSize = filesize($file['file_path']);

        if ($fileSize > self::PREVIEW_MAX_FILE_SIZE) {
            return [
                'success'    => true,
                'content'    => "File too large to preview ({$fileSize} bytes). Use download instead.",
                'mime_type'  => $file['mime_type'],
                'file_name'  => $file['original_name'],
                'truncated'  => false,
                'file_size'  => $fileSize,
            ];
        }

        $content = @file_get_contents($file['file_path']);
        if ($content === false) {
            throw new \RuntimeException('Failed to read file content');
        }

        // Ensure UTF-8 encoding
        if (!mb_check_encoding($content, 'UTF-8')) {
            $content = mb_convert_encoding($content, 'UTF-8', 'auto');
        }

        $truncated = false;
        if (strlen($content) > self::PREVIEW_MAX_CHARS) {
            $content = mb_substr($content, 0, self::PREVIEW_MAX_CHARS, 'UTF-8') . "\n\n[Content truncated for preview]";
            $truncated = true;
        }

        $content = mb_convert_encoding($content, 'UTF-8', 'UTF-8');

        return [
            'success'   => true,
            'content'   => base64_encode($content),
            'encoding'  => 'base64',
            'mime_type' => $file['mime_type'],
            'file_name' => $file['original_name'],
            'truncated' => $truncated,
            'file_size' => $fileSize,
        ];
    }

    /**
     * Stream a binary file directly to the HTTP client and exit
     */
    private function streamFile(array $file): never
    {
        ob_clean();

        header_remove('Content-Type');
        header('Content-Type: ' . $file['mime_type']);
        header('Content-Length: ' . filesize($file['file_path']));
        header('Content-Disposition: inline; filename="' . $file['original_name'] . '"');
        header('Cache-Control: private, max-age=0, must-revalidate');
        header('Pragma: public');

        readfile($file['file_path']);
        exit;
    }

    // -------------------------------------------------------------------------
    // File management
    // -------------------------------------------------------------------------

    /**
     * Move a file to a different folder
     *
     * @throws NotFoundException
     */
    public function moveFile(int $fileId, ?int $newFolderId): void
    {
        $file = $this->mediaRepository->findFileById($fileId);
        if (!$file) {
            throw new NotFoundException('File not found');
        }

        $this->mediaRepository->moveFile($fileId, $newFolderId);
    }

    /**
     * Delete a file record and its on-disk data
     *
     * @throws NotFoundException
     */
    public function deleteFile(int $fileId): void
    {
        $file = $this->mediaRepository->findFileById($fileId);
        if (!$file) {
            throw new NotFoundException('File not found');
        }

        if ($file['file_path'] && file_exists($file['file_path'])) {
            unlink($file['file_path']);
        }

        $this->mediaRepository->deleteFile($fileId);
    }

    // -------------------------------------------------------------------------
    // Folder management
    // -------------------------------------------------------------------------

    /**
     * Get folder details and its direct contents
     *
     * @throws NotFoundException
     */
    public function getFolder(int $folderId): array
    {
        $folder = $this->mediaRepository->findFolderById($folderId);
        if (!$folder) {
            throw new NotFoundException('Folder not found');
        }

        $contents = $this->getFolderContents($folderId);
        return array_merge($folder, ['contents' => $contents]);
    }

    /**
     * Get the direct contents (sub-folders + files) of a folder
     */
    public function getFolderContents(int $folderId): array
    {
        $folders = $this->mediaRepository->findFolders(100, 0, $folderId);
        $files   = $this->mediaRepository->findFiles(100, 0, $folderId);

        return [
            'folders'     => $folders,
            'files'       => $files,
            'total_items' => count($folders) + count($files),
        ];
    }

    /**
     * Create a new media folder
     *
     * @throws ValidationException
     */
    public function createFolder(array $data, int $userId): int
    {
        if (empty($data['name'])) {
            throw new ValidationException('Folder name is required');
        }

        // Build hierarchical path
        $path = $data['name'];
        if (!empty($data['parent_id'])) {
            $parent = $this->mediaRepository->findFolderById((int) $data['parent_id']);
            if ($parent) {
                $path = $parent['path'] . '/' . $data['name'];
            }
        }

        $folderData = [
            'name'        => $data['name'],
            'description' => $data['description'] ?? '',
            'path'        => $path,
            'created_by'  => $userId,
        ];

        if (isset($data['parent_id']) && $data['parent_id'] !== null) {
            $folderData['parent_id'] = (int) $data['parent_id'];
        }

        return $this->mediaRepository->createFolder($folderData);
    }

    /**
     * Move a folder to a new parent (or to root when $newParentId is null)
     *
     * @throws NotFoundException
     */
    public function moveFolder(int $folderId, ?int $newParentId): void
    {
        $folder = $this->mediaRepository->findFolderById($folderId);
        if (!$folder) {
            throw new NotFoundException('Folder not found');
        }

        $newPath = $folder['name'];
        if ($newParentId) {
            $parent = $this->mediaRepository->findFolderById($newParentId);
            if ($parent) {
                $newPath = $parent['path'] . '/' . $folder['name'];
            }
        }

        $this->mediaRepository->updateFolder($folderId, [
            'parent_id' => $newParentId,
            'path'      => $newPath,
        ]);

        // Cascade path update to all sub-folders
        $this->updateSubfolderPaths($folderId, $newPath);
    }

    /**
     * Delete a folder and all of its contents recursively
     *
     * @throws NotFoundException
     */
    public function deleteFolder(int $folderId): void
    {
        if (!$this->mediaRepository->findFolderById($folderId)) {
            throw new NotFoundException('Folder not found');
        }

        $allFolderIds = $this->getAllSubfolderIds($folderId);
        $allFolderIds[] = $folderId;

        // Delete on-disk files and their database records for each folder
        foreach ($allFolderIds as $fid) {
            $filePaths = $this->mediaRepository->findFilePathsByFolder($fid);
            foreach ($filePaths as $row) {
                if ($row['file_path'] && file_exists($row['file_path'])) {
                    unlink($row['file_path']);
                }
            }
            $this->mediaRepository->deleteFilesByFolder($fid);
        }

        // Delete folders from deepest level up to avoid FK violations
        foreach (array_reverse($allFolderIds) as $fid) {
            $this->mediaRepository->deleteFolderById($fid);
        }
    }

    // -------------------------------------------------------------------------
    // Sharing
    // -------------------------------------------------------------------------

    /**
     * Create a share link for a file or folder
     *
     * @throws ValidationException
     */
    public function createShareLink(
        string $resourceType,
        int $resourceId,
        int $sharedBy,
        string $permission = 'view',
        int $expiresHours = 24
    ): string {
        if (!in_array($resourceType, ['file', 'folder'], true)) {
            throw new ValidationException('Resource type must be "file" or "folder"');
        }

        $shareToken = bin2hex(random_bytes(16));

        $this->mediaRepository->createSharing([
            'resource_type'      => $resourceType,
            'resource_id'        => $resourceId,
            'shared_by_user_id'  => $sharedBy,
            'permission'         => $permission,
            'share_token'        => $shareToken,
            'expires_at'         => date('Y-m-d H:i:s', strtotime("+{$expiresHours} hours")),
        ]);

        if ($resourceType === 'file') {
            $this->mediaRepository->markFileShared($resourceId);
        } else {
            $this->mediaRepository->markFolderShared($resourceId);
        }

        return $shareToken;
    }

    /**
     * Resolve a share token and return the associated resource
     * (accessible without authentication)
     *
     * @throws NotFoundException
     */
    public function getSharedResource(string $token): array
    {
        $sharing = $this->mediaRepository->findSharingByToken($token);
        if (!$sharing) {
            throw new NotFoundException('Shared resource not found or expired');
        }

        if ($sharing['resource_type'] === 'file') {
            $resource = $this->mediaRepository->findFileById($sharing['resource_id']);
        } else {
            $resource = $this->mediaRepository->findFolderByIdWithMeta($sharing['resource_id']);
        }

        if (!$resource) {
            throw new NotFoundException('Shared resource not found or expired');
        }

        $resource['sharing'] = $sharing;
        return $resource;
    }

    /**
     * Verify that a share token grants access to a specific folder
     */
    public function verifyFolderShareToken(string $token, int $folderId): bool
    {
        return $this->mediaRepository->findFolderSharingByToken($token, $folderId) !== null;
    }

    // -------------------------------------------------------------------------
    // Private helpers
    // -------------------------------------------------------------------------

    /**
     * Recursively collect all subfolder IDs beneath a parent
     */
    private function getAllSubfolderIds(int $parentId): array
    {
        $ids = [];
        $rows = $this->mediaRepository->findSubfolderIds($parentId);

        foreach ($rows as $row) {
            $ids[] = $row['id'];
            $ids = array_merge($ids, $this->getAllSubfolderIds($row['id']));
        }

        return $ids;
    }

    /**
     * Cascade a path update to all sub-folders of a moved folder
     */
    private function updateSubfolderPaths(int $parentId, string $parentPath): void
    {
        $subfolders = $this->mediaRepository->findSubfoldersWithName($parentId);

        foreach ($subfolders as $subfolder) {
            $newPath = $parentPath . '/' . $subfolder['name'];
            $this->mediaRepository->updateFolder($subfolder['id'], ['path' => $newPath]);
            $this->updateSubfolderPaths($subfolder['id'], $newPath);
        }
    }

    /**
     * Determine the logical file-type category from a MIME type
     */
    private function resolveFileType(string $mimeType): string
    {
        if (str_starts_with($mimeType, 'image/')) return 'image';
        if (str_starts_with($mimeType, 'video/')) return 'video';
        if (str_starts_with($mimeType, 'audio/')) return 'audio';
        if (in_array($mimeType, [
            'application/pdf',
            'application/msword',
            'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
        ], true)) {
            return 'document';
        }
        return 'other';
    }

    /**
     * Return true when the MIME type is a text type eligible for preview
     */
    private function isTextMimeType(string $mimeType): bool
    {
        return in_array($mimeType, self::TEXT_MIME_TYPES, true)
            || str_starts_with($mimeType, 'text/');
    }
}
