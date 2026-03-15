<?php

namespace AnimaID\Repositories;

use PDO;

/**
 * Media Repository
 * Handles media files, folders, and sharing operations
 */
class MediaRepository extends BaseRepository
{
    protected string $table = 'media_files';

    // -------------------------------------------------------------------------
    // File operations
    // -------------------------------------------------------------------------

    /**
     * Find all files with optional folder and search filters
     */
    public function findFiles(int $limit = 20, int $offset = 0, ?int $folderId = null, string $search = ''): array
    {
        $query = "SELECT mf.*, u.username as uploaded_by_name, f.name as folder_name
                  FROM media_files mf
                  LEFT JOIN users u ON mf.uploaded_by = u.id
                  LEFT JOIN media_folders f ON mf.folder_id = f.id
                  WHERE 1=1";
        $params = [];

        if ($folderId !== null) {
            $query .= ' AND mf.folder_id = ?';
            $params[] = $folderId;
        } else {
            // Root-level files when no folder is specified
            $query .= ' AND mf.folder_id IS NULL';
        }

        if (!empty($search)) {
            $query .= ' AND (mf.original_name LIKE ? OR mf.filename LIKE ?)';
            $searchTerm = '%' . $search . '%';
            $params[] = $searchTerm;
            $params[] = $searchTerm;
        }

        $query .= ' ORDER BY mf.created_at DESC LIMIT ? OFFSET ?';
        $params[] = $limit;
        $params[] = $offset;

        return $this->query($query, $params);
    }

    /**
     * Count files with optional folder and search filters
     */
    public function countFiles(?int $folderId = null, string $search = ''): int
    {
        $query = 'SELECT COUNT(*) as count FROM media_files mf WHERE 1=1';
        $params = [];

        if ($folderId !== null) {
            $query .= ' AND mf.folder_id = ?';
            $params[] = $folderId;
        } else {
            $query .= ' AND mf.folder_id IS NULL';
        }

        if (!empty($search)) {
            $query .= ' AND (mf.original_name LIKE ? OR mf.filename LIKE ?)';
            $searchTerm = '%' . $search . '%';
            $params[] = $searchTerm;
            $params[] = $searchTerm;
        }

        $result = $this->queryOne($query, $params);
        return (int) $result['count'];
    }

    /**
     * Find a single file by ID with uploader and folder info
     */
    public function findFileById(int $fileId): ?array
    {
        return $this->queryOne(
            "SELECT mf.*, u.username as uploaded_by_name, f.name as folder_name
             FROM media_files mf
             LEFT JOIN users u ON mf.uploaded_by = u.id
             LEFT JOIN media_folders f ON mf.folder_id = f.id
             WHERE mf.id = ?",
            [$fileId]
        );
    }

    /**
     * Insert a new file record
     */
    public function createFile(array $data): int
    {
        return $this->insert($data);
    }

    /**
     * Move a file to a different folder (or to root when $newFolderId is null)
     */
    public function moveFile(int $fileId, ?int $newFolderId): bool
    {
        return $this->update($fileId, ['folder_id' => $newFolderId]);
    }

    /**
     * Increment the download count for a file
     */
    public function incrementDownloadCount(int $fileId): void
    {
        $this->db->prepare(
            'UPDATE media_files SET download_count = download_count + 1 WHERE id = ?'
        )->execute([$fileId]);
    }

    /**
     * Delete a file record by ID
     */
    public function deleteFile(int $fileId): bool
    {
        return $this->delete($fileId);
    }

    /**
     * Get file paths for all files in a folder (used during folder deletion)
     */
    public function findFilePathsByFolder(int $folderId): array
    {
        return $this->query(
            'SELECT file_path FROM media_files WHERE folder_id = ?',
            [$folderId]
        );
    }

    /**
     * Delete all file records in a folder
     */
    public function deleteFilesByFolder(int $folderId): bool
    {
        $stmt = $this->db->prepare('DELETE FROM media_files WHERE folder_id = ?');
        return $stmt->execute([$folderId]);
    }

    // -------------------------------------------------------------------------
    // Folder operations
    // -------------------------------------------------------------------------

    /**
     * Find folders at the given level (root when $parentId is null)
     */
    public function findFolders(int $limit = 20, int $offset = 0, ?int $parentId = null): array
    {
        if ($parentId !== null) {
            $whereClause = 'WHERE f.parent_id = ?';
            $params = [$parentId];
        } else {
            $whereClause = 'WHERE f.parent_id IS NULL';
            $params = [];
        }

        $query = "SELECT f.*, u.username as created_by_name,
                         (SELECT COUNT(*) FROM media_folders WHERE parent_id = f.id) as subfolder_count,
                         (SELECT COUNT(*) FROM media_files WHERE folder_id = f.id) as file_count
                  FROM media_folders f
                  LEFT JOIN users u ON f.created_by = u.id
                  {$whereClause}
                  ORDER BY f.name
                  LIMIT ? OFFSET ?";

        $params[] = $limit;
        $params[] = $offset;

        return $this->query($query, $params);
    }

    /**
     * Count folders at the given level
     */
    public function countFolders(?int $parentId = null): int
    {
        if ($parentId !== null) {
            $whereClause = 'WHERE parent_id = ?';
            $params = [$parentId];
        } else {
            $whereClause = 'WHERE parent_id IS NULL';
            $params = [];
        }

        $result = $this->queryOne(
            "SELECT COUNT(*) as count FROM media_folders {$whereClause}",
            $params
        );
        return (int) $result['count'];
    }

    /**
     * Find a single folder by ID
     */
    public function findFolderById(int $folderId): ?array
    {
        return $this->queryOne(
            'SELECT * FROM media_folders WHERE id = ?',
            [$folderId]
        );
    }

    /**
     * Find a folder with creator info by ID
     */
    public function findFolderByIdWithMeta(int $folderId): ?array
    {
        return $this->queryOne(
            "SELECT f.*, u.username as created_by_name
             FROM media_folders f
             LEFT JOIN users u ON f.created_by = u.id
             WHERE f.id = ?",
            [$folderId]
        );
    }

    /**
     * Create a new folder record
     */
    public function createFolder(array $data): int
    {
        $stmt = $this->db->prepare(
            "INSERT INTO media_folders (" . implode(', ', array_keys($data)) . ")
             VALUES (" . implode(', ', array_fill(0, count($data), '?')) . ")"
        );
        $stmt->execute(array_values($data));
        return (int) $this->db->lastInsertId();
    }

    /**
     * Update a folder record
     */
    public function updateFolder(int $folderId, array $data): bool
    {
        $fields = [];
        $params = [];
        foreach ($data as $field => $value) {
            $fields[] = "{$field} = ?";
            $params[] = $value;
        }
        $params[] = $folderId;

        $stmt = $this->db->prepare(
            'UPDATE media_folders SET ' . implode(', ', $fields) . ' WHERE id = ?'
        );
        return $stmt->execute($params);
    }

    /**
     * Delete a folder record
     */
    public function deleteFolderById(int $folderId): bool
    {
        $stmt = $this->db->prepare('DELETE FROM media_folders WHERE id = ?');
        return $stmt->execute([$folderId]);
    }

    /**
     * Get direct subfolder IDs for a parent folder
     */
    public function findSubfolderIds(int $parentId): array
    {
        return $this->query(
            'SELECT id FROM media_folders WHERE parent_id = ?',
            [$parentId]
        );
    }

    /**
     * Get subfolders with name (used for path rebuilding)
     */
    public function findSubfoldersWithName(int $parentId): array
    {
        return $this->query(
            'SELECT id, name FROM media_folders WHERE parent_id = ?',
            [$parentId]
        );
    }

    // -------------------------------------------------------------------------
    // Sharing operations
    // -------------------------------------------------------------------------

    /**
     * Insert a sharing record and return its ID
     */
    public function createSharing(array $data): int
    {
        $stmt = $this->db->prepare(
            "INSERT INTO media_sharing (" . implode(', ', array_keys($data)) . ")
             VALUES (" . implode(', ', array_fill(0, count($data), '?')) . ")"
        );
        $stmt->execute(array_values($data));
        return (int) $this->db->lastInsertId();
    }

    /**
     * Find a non-expired sharing record by token
     */
    public function findSharingByToken(string $token): ?array
    {
        return $this->queryOne(
            "SELECT * FROM media_sharing
             WHERE share_token = ? AND (expires_at IS NULL OR expires_at > datetime('now'))",
            [$token]
        );
    }

    /**
     * Find a non-expired sharing record by token and folder
     */
    public function findFolderSharingByToken(string $token, int $folderId): ?array
    {
        return $this->queryOne(
            "SELECT * FROM media_sharing
             WHERE share_token = ?
               AND resource_type = 'folder'
               AND resource_id = ?
               AND (expires_at IS NULL OR expires_at > datetime('now'))",
            [$token, $folderId]
        );
    }

    /**
     * Mark a file as shared
     */
    public function markFileShared(int $fileId): void
    {
        $this->update($fileId, ['is_shared' => 1]);
    }

    /**
     * Mark a folder as shared
     */
    public function markFolderShared(int $folderId): void
    {
        $this->updateFolder($folderId, ['is_shared' => 1]);
    }
}
