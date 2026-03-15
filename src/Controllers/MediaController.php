<?php

namespace AnimaID\Controllers;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use AnimaID\Services\MediaService;
use AnimaID\Exceptions\NotFoundException;
use AnimaID\Exceptions\ForbiddenException;
use AnimaID\Exceptions\ValidationException;

/**
 * Media Controller
 * Handles media file/folder listing, uploads, downloads, sharing, and deletion
 */
class MediaController
{
    private MediaService $mediaService;

    public function __construct(MediaService $mediaService)
    {
        $this->mediaService = $mediaService;
    }

    /**
     * List media (folders + files for a given folder context)
     * GET /api/media
     *
     * Query parameters: page, limit, folder_id, search
     */
    public function index(Request $request, Response $response): Response
    {
        try {
            $params = $request->getQueryParams();
            $page = (int) ($params['page'] ?? 1);
            $limit = (int) ($params['limit'] ?? 20);
            $folderId = isset($params['folder_id']) ? (int) $params['folder_id'] : null;
            $search = $params['search'] ?? '';

            $result = $this->mediaService->listMedia($page, $limit, $folderId, $search);

            return $this->jsonResponse($response, [
                'success' => true,
                'data' => $result,
            ]);
        } catch (\Exception $e) {
            return $this->jsonResponse($response, [
                'success' => false,
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Upload a file or create a folder
     * POST /api/media
     *
     * File upload: multipart/form-data with field "file" (+ optional "folder_id")
     * Folder creation: JSON body with {"name": "...", "type": "folder", "parent_id": ...}
     */
    public function upload(Request $request, Response $response): Response
    {
        try {
            $currentUser = $request->getAttribute('user');

            // Determine whether this is a file upload or a folder creation
            $uploadedFiles = $request->getUploadedFiles();

            if (!empty($uploadedFiles['file'])) {
                // --- Multipart file upload ---
                $uploadedFile = $uploadedFiles['file'];

                // Convert PSR-7 UploadedFile to the $_FILES-style array expected by MediaService
                $phpFileArray = [
                    'name'     => $uploadedFile->getClientFilename(),
                    'type'     => $uploadedFile->getClientMediaType(),
                    'size'     => $uploadedFile->getSize(),
                    'error'    => $uploadedFile->getError(),
                    'tmp_name' => $this->moveTempFile($uploadedFile),
                ];

                $parsedBody = $request->getParsedBody();
                $folderId = $parsedBody['folder_id'] ?? null;

                $fileId = $this->mediaService->uploadFile($phpFileArray, $currentUser['id'], $folderId);

                return $this->jsonResponse($response, [
                    'success' => true,
                    'data' => ['file_id' => $fileId],
                    'message' => 'File uploaded successfully',
                ], 201);
            }

            // --- Folder creation ---
            $body = json_decode($request->getBody()->getContents(), true) ?? [];

            if (isset($body['name']) && isset($body['type']) && $body['type'] === 'folder') {
                $folderId = $this->mediaService->createFolder($body, $currentUser['id']);

                return $this->jsonResponse($response, [
                    'success' => true,
                    'data' => ['folder_id' => $folderId],
                    'message' => 'Folder created successfully',
                ], 201);
            }

            return $this->jsonResponse($response, [
                'success' => false,
                'error' => 'Invalid request data',
            ], 400);
        } catch (ValidationException $e) {
            return $this->jsonResponse($response, [
                'success' => false,
                'error' => $e->getMessage(),
            ], 422);
        } catch (\Exception $e) {
            return $this->jsonResponse($response, [
                'success' => false,
                'error' => $e->getMessage(),
            ], 400);
        }
    }

    /**
     * Download or preview a file
     * GET /api/media/files/{id}/download
     *
     * When the client sends Accept: application/json a text preview is returned as JSON.
     * Otherwise the file is streamed directly to the client.
     */
    public function download(Request $request, Response $response, array $args): Response
    {
        try {
            $fileId = (int) $args['id'];

            // Detect whether the client wants a JSON preview
            $acceptHeader = $request->getHeaderLine('Accept');
            $jsonPreviewRequested = str_contains($acceptHeader, 'application/json');

            // This may exit() for binary streams; for JSON previews it returns data
            $previewData = $this->mediaService->downloadFile($fileId, $jsonPreviewRequested);

            // Only reached for JSON text previews
            return $this->jsonResponse($response, $previewData);
        } catch (NotFoundException $e) {
            return $this->jsonResponse($response, [
                'success' => false,
                'error' => $e->getMessage(),
            ], 404);
        } catch (\Exception $e) {
            return $this->jsonResponse($response, [
                'success' => false,
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Resolve a share token and return the shared resource
     * GET /api/media/shared/{token}
     *
     * No authentication required.
     */
    public function getShared(Request $request, Response $response, array $args): Response
    {
        try {
            $token = $args['token'];

            $resource = $this->mediaService->getSharedResource($token);

            return $this->jsonResponse($response, [
                'success' => true,
                'data' => $resource,
            ]);
        } catch (NotFoundException $e) {
            return $this->jsonResponse($response, [
                'success' => false,
                'error' => $e->getMessage(),
            ], 404);
        } catch (\Exception $e) {
            return $this->jsonResponse($response, [
                'success' => false,
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Create a share link
     * POST /api/media/share
     */
    public function share(Request $request, Response $response): Response
    {
        try {
            $body = json_decode($request->getBody()->getContents(), true) ?? [];
            $currentUser = $request->getAttribute('user');

            $resourceType = $body['resource_type'] ?? '';
            $resourceId = (int) ($body['resource_id'] ?? 0);
            $permission = $body['permission'] ?? 'view';
            $expiresHours = (int) ($body['expires_hours'] ?? 24);

            if (!$resourceType || !$resourceId) {
                throw new ValidationException('Resource type and ID are required');
            }

            $shareToken = $this->mediaService->createShareLink(
                $resourceType,
                $resourceId,
                $currentUser['id'],
                $permission,
                $expiresHours
            );

            return $this->jsonResponse($response, [
                'success' => true,
                'data' => [
                    'share_token' => $shareToken,
                    'share_url' => "/shared.html?token={$shareToken}",
                ],
                'message' => 'Share link created successfully',
            ], 201);
        } catch (ValidationException $e) {
            return $this->jsonResponse($response, [
                'success' => false,
                'error' => $e->getMessage(),
            ], 422);
        } catch (\Exception $e) {
            return $this->jsonResponse($response, [
                'success' => false,
                'error' => $e->getMessage(),
            ], 400);
        }
    }

    /**
     * Get file metadata
     * GET /api/media/files/{id}
     */
    public function showFile(Request $request, Response $response, array $args): Response
    {
        try {
            $fileId = (int) $args['id'];
            $file = $this->mediaService->getFile($fileId);

            return $this->jsonResponse($response, [
                'success' => true,
                'data' => $file,
            ]);
        } catch (NotFoundException $e) {
            return $this->jsonResponse($response, [
                'success' => false,
                'error' => $e->getMessage(),
            ], 404);
        } catch (\Exception $e) {
            return $this->jsonResponse($response, [
                'success' => false,
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Move a file to a different folder
     * PUT /api/media/files/{id}
     */
    public function moveFile(Request $request, Response $response, array $args): Response
    {
        try {
            $fileId = (int) $args['id'];
            $body = json_decode($request->getBody()->getContents(), true) ?? [];
            $newFolderId = isset($body['folder_id']) ? (int) $body['folder_id'] : null;

            $this->mediaService->moveFile($fileId, $newFolderId);

            return $this->jsonResponse($response, [
                'success' => true,
                'message' => 'File moved successfully',
            ]);
        } catch (NotFoundException $e) {
            return $this->jsonResponse($response, [
                'success' => false,
                'error' => $e->getMessage(),
            ], 404);
        } catch (\Exception $e) {
            return $this->jsonResponse($response, [
                'success' => false,
                'error' => $e->getMessage(),
            ], 400);
        }
    }

    /**
     * Delete a file
     * DELETE /api/media/files/{id}
     */
    public function delete(Request $request, Response $response, array $args): Response
    {
        try {
            $fileId = (int) $args['id'];

            $this->mediaService->deleteFile($fileId);

            return $this->jsonResponse($response, [
                'success' => true,
                'message' => 'File deleted successfully',
            ]);
        } catch (NotFoundException $e) {
            return $this->jsonResponse($response, [
                'success' => false,
                'error' => $e->getMessage(),
            ], 404);
        } catch (\Exception $e) {
            return $this->jsonResponse($response, [
                'success' => false,
                'error' => $e->getMessage(),
            ], 400);
        }
    }

    /**
     * Get folder details and contents
     * GET /api/media/folders/{id}
     */
    public function showFolder(Request $request, Response $response, array $args): Response
    {
        try {
            $folderId = (int) $args['id'];
            $folder = $this->mediaService->getFolder($folderId);

            return $this->jsonResponse($response, [
                'success' => true,
                'data' => $folder,
            ]);
        } catch (NotFoundException $e) {
            return $this->jsonResponse($response, [
                'success' => false,
                'error' => $e->getMessage(),
            ], 404);
        } catch (\Exception $e) {
            return $this->jsonResponse($response, [
                'success' => false,
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Move a folder to a new parent
     * PUT /api/media/folders/{id}
     */
    public function moveFolder(Request $request, Response $response, array $args): Response
    {
        try {
            $folderId = (int) $args['id'];
            $body = json_decode($request->getBody()->getContents(), true) ?? [];
            $newParentId = isset($body['parent_id']) ? (int) $body['parent_id'] : null;

            $this->mediaService->moveFolder($folderId, $newParentId);

            return $this->jsonResponse($response, [
                'success' => true,
                'message' => 'Folder moved successfully',
            ]);
        } catch (NotFoundException $e) {
            return $this->jsonResponse($response, [
                'success' => false,
                'error' => $e->getMessage(),
            ], 404);
        } catch (\Exception $e) {
            return $this->jsonResponse($response, [
                'success' => false,
                'error' => $e->getMessage(),
            ], 400);
        }
    }

    /**
     * Delete a folder and all its contents
     * DELETE /api/media/folders/{id}
     */
    public function deleteFolder(Request $request, Response $response, array $args): Response
    {
        try {
            $folderId = (int) $args['id'];

            $this->mediaService->deleteFolder($folderId);

            return $this->jsonResponse($response, [
                'success' => true,
                'message' => 'Folder deleted successfully',
            ]);
        } catch (NotFoundException $e) {
            return $this->jsonResponse($response, [
                'success' => false,
                'error' => $e->getMessage(),
            ], 404);
        } catch (\Exception $e) {
            return $this->jsonResponse($response, [
                'success' => false,
                'error' => $e->getMessage(),
            ], 400);
        }
    }

    // -------------------------------------------------------------------------
    // Private helpers
    // -------------------------------------------------------------------------

    /**
     * Move a PSR-7 UploadedFile to a real temp path and return that path.
     * This is necessary because MediaService calls move_uploaded_file() which
     * requires a real filesystem path, not a PHP stream wrapper.
     */
    private function moveTempFile(\Psr\Http\Message\UploadedFileInterface $uploadedFile): string
    {
        $tmpPath = sys_get_temp_dir() . '/' . uniqid('upload_', true);
        $uploadedFile->moveTo($tmpPath);
        return $tmpPath;
    }

    /**
     * Helper method to create a JSON response
     */
    private function jsonResponse(Response $response, array $data, int $status = 200): Response
    {
        $response->getBody()->write(json_encode($data));

        return $response
            ->withHeader('Content-Type', 'application/json')
            ->withStatus($status);
    }
}
