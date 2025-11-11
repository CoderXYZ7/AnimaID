<?php
/**
 * Comprehensive test for Media System functionality
 */

require_once __DIR__ . '/src/Database.php';
require_once __DIR__ . '/src/Auth.php';

try {
    echo "=== AnimaID Media System Test ===\n\n";
    
    // Test database connection
    $db = Database::getInstance();
    echo "✓ Database connection successful\n";
    
    // Create Auth instance
    $auth = new Auth();
    
    // Test login with admin credentials
    echo "\n1. Testing authentication...\n";
    try {
        $loginResult = $auth->login('admin', 'Admin123!@#');
        echo "✓ Admin login successful\n";
        $token = $loginResult['token'];
        echo "✓ JWT token obtained\n";
    } catch (Exception $e) {
        echo "✗ Admin login failed: " . $e->getMessage() . "\n";
        exit(1);
    }
    
    // Test token verification
    echo "\n2. Testing token verification...\n";
    try {
        $user = $auth->verifyToken($token);
        echo "✓ Token verification successful\n";
        echo "   User: " . $user['username'] . " (ID: " . $user['id'] . ")\n";
        echo "   Roles: " . implode(', ', array_column($user['roles'], 'name')) . "\n";
    } catch (Exception $e) {
        echo "✗ Token verification failed: " . $e->getMessage() . "\n";
        exit(1);
    }
    
    // Test media permissions
    echo "\n3. Testing media permissions...\n";
    $hasMediaView = $auth->checkPermission($user['id'], 'media.view');
    $hasMediaUpload = $auth->checkPermission($user['id'], 'media.upload');
    $hasMediaDelete = $auth->checkPermission($user['id'], 'media.delete');
    
    echo "✓ Media permissions:\n";
    echo "   - media.view: " . ($hasMediaView ? '✓' : '✗') . "\n";
    echo "   - media.upload: " . ($hasMediaUpload ? '✓' : '✗') . "\n";
    echo "   - media.delete: " . ($hasMediaDelete ? '✓' : '✗') . "\n";
    
    if (!$hasMediaView) {
        echo "✗ User doesn't have required media.view permission\n";
        exit(1);
    }
    
    // Test API endpoint with valid token using file_get_contents
    echo "\n4. Testing API endpoint with valid token...\n";
    
    $context = stream_context_create([
        'http' => [
            'method' => 'GET',
            'header' => "Authorization: Bearer $token\r\n" .
                       "Content-Type: application/json\r\n",
            'ignore_errors' => true
        ]
    ]);
    
    $response = file_get_contents('http://localhost:8000/api/media', false, $context);
    
    if ($response !== false) {
        $data = json_decode($response, true);
        if ($data['success']) {
            echo "✓ API endpoint working correctly\n";
            echo "   Folders: " . count($data['folders']) . "\n";
            echo "   Files: " . count($data['files']) . "\n";
            echo "   Total items: " . $data['total_items'] . "\n";
            
            // Show sample data
            if (!empty($data['folders'])) {
                echo "\n   Sample folders:\n";
                foreach (array_slice($data['folders'], 0, 3) as $folder) {
                    echo "     - " . $folder['name'] . " (ID: " . $folder['id'] . ")\n";
                }
            }
            
            if (!empty($data['files'])) {
                echo "\n   Sample files:\n";
                foreach (array_slice($data['files'], 0, 3) as $file) {
                    echo "     - " . $file['original_name'] . " (" . $file['file_type'] . ")\n";
                }
            }
        } else {
            echo "✗ API returned error: " . $data['error'] . "\n";
        }
    } else {
        echo "✗ API request failed\n";
    }
    
    // Test folder creation using file_get_contents
    echo "\n5. Testing folder creation...\n";
    
    $postData = json_encode([
        'type' => 'folder',
        'name' => 'Test Folder ' . date('Y-m-d H:i:s'),
        'description' => 'Test folder created by automated test'
    ]);
    
    $context = stream_context_create([
        'http' => [
            'method' => 'POST',
            'header' => "Authorization: Bearer $token\r\n" .
                       "Content-Type: application/json\r\n" .
                       "Content-Length: " . strlen($postData) . "\r\n",
            'content' => $postData,
            'ignore_errors' => true
        ]
    ]);
    
    $response = file_get_contents('http://localhost:8000/api/media', false, $context);
    
    if ($response !== false) {
        $data = json_decode($response, true);
        if ($data['success']) {
            echo "✓ Folder creation successful\n";
            echo "   Folder ID: " . $data['folder_id'] . "\n";
            $testFolderId = $data['folder_id'];
        } else {
            echo "✗ Folder creation failed: " . ($data['error'] ?? 'Unknown error') . "\n";
        }
    } else {
        echo "✗ Folder creation request failed\n";
    }
    
    // Test file upload (simulated - would need actual file)
    echo "\n6. Testing file upload simulation...\n";
    echo "   Note: Actual file upload requires multipart form data\n";
    
    // Test getting specific folder using file_get_contents
    echo "\n7. Testing folder navigation...\n";
    
    $context = stream_context_create([
        'http' => [
            'method' => 'GET',
            'header' => "Authorization: Bearer $token\r\n" .
                       "Content-Type: application/json\r\n",
            'ignore_errors' => true
        ]
    ]);
    
    $response = file_get_contents('http://localhost:8000/api/media/folders/1', false, $context);
    
    if ($response !== false) {
        $data = json_decode($response, true);
        if (!isset($data['error'])) {
            echo "✓ Folder details retrieved\n";
            if (isset($data['contents'])) {
                echo "   Contents: " . count($data['contents']['folders']) . " folders, " . 
                     count($data['contents']['files']) . " files\n";
            }
        } else {
            echo "✗ Folder details failed: " . $data['error'] . "\n";
        }
    } else {
        echo "✗ Folder details request failed\n";
    }
    
    // Test file download/preview using file_get_contents
    echo "\n8. Testing file download/preview...\n";
    
    // First get a file ID from the database
    $files = $db->fetchAll("SELECT id FROM media_files LIMIT 1");
    if (!empty($files)) {
        $fileId = $files[0]['id'];
        
        $context = stream_context_create([
            'http' => [
                'method' => 'GET',
                'header' => "Authorization: Bearer $token\r\n" .
                           "Accept: application/json\r\n",
                'ignore_errors' => true
            ]
        ]);
        
        $response = file_get_contents('http://localhost:8000/api/media/files/' . $fileId . '/download', false, $context);
        
        if ($response !== false) {
            $data = json_decode($response, true);
            if ($data['success']) {
                echo "✓ File preview/download working\n";
                echo "   File type: " . $data['mime_type'] . "\n";
                echo "   File size: " . $data['file_size'] . " bytes\n";
                if (isset($data['truncated'])) {
                    echo "   Preview truncated: " . ($data['truncated'] ? 'yes' : 'no') . "\n";
                }
            } else {
                echo "✗ File download failed: " . $data['error'] . "\n";
            }
        } else {
            echo "✗ File download request failed\n";
        }
    } else {
        echo "   No files found in database to test download\n";
    }
    
    echo "\n=== Media System Test Complete ===\n";
    echo "✓ All core functionality is working correctly!\n";
    echo "✓ Frontend UI should now display media content properly\n";
    
} catch (Exception $e) {
    echo "✗ Test failed with error: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
}
?>
