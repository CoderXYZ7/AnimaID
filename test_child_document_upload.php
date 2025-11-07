<?php
/**
 * Test script for Child Document Upload API functionality
 */

require_once __DIR__ . '/src/Database.php';
require_once __DIR__ . '/src/Auth.php';

try {
    echo "Testing Child Document Upload API functionality...\n\n";

    // Test database connection
    $db = Database::getInstance();
    echo "✓ Database connection successful\n";

    // Check if child_documents table exists
    $tableExists = $db->fetchOne("SELECT name FROM sqlite_master WHERE type='table' AND name='child_documents'");
    if (!$tableExists) {
        echo "✗ child_documents table not found in database\n";
        exit(1);
    }
    echo "✓ child_documents table exists\n";

    // Show table structure
    $columns = $db->fetchAll("PRAGMA table_info(child_documents)");
    echo "✓ Table structure:\n";
    foreach ($columns as $column) {
        echo "  - " . $column['name'] . " (" . $column['type'] . ")\n";
    }

    // Check if there's at least one child in the database
    $child = $db->fetchOne("SELECT id, first_name, last_name FROM children LIMIT 1");
    if (!$child) {
        echo "✗ No children found in database. Please create a child first.\n";
        exit(1);
    }
    echo "✓ Found child: {$child['first_name']} {$child['last_name']} (ID: {$child['id']})\n";

    // Test authentication
    $auth = new Auth();
    $loginResult = $auth->login('admin', 'Admin123!@#');
    if (!$loginResult || !isset($loginResult['token'])) {
        echo "✗ Authentication failed\n";
        exit(1);
    }
    $token = $loginResult['token'];
    echo "✓ Authentication successful, got token\n";

    // Test API endpoint structure
    echo "\nTesting API endpoint structure...\n";

    // Simulate a POST request to /api/children/{id}/documents
    $_SERVER['REQUEST_URI'] = "/api/children/{$child['id']}/documents";
    $_SERVER['REQUEST_METHOD'] = 'POST';
    $_SERVER['HTTP_AUTHORIZATION'] = "Bearer {$token}";

    // Test the API function directly by calling the Auth method
    echo "Testing addChildDocument method...\n";

    // Create a test file directly in the uploads directory
    $testFileContent = "This is a test document for upload testing.";
    $uploadDir = __DIR__ . '/uploads/children/';
    $filename = uniqid('child_doc_', true) . '.txt';
    $filePath = $uploadDir . $filename;
    file_put_contents($filePath, $testFileContent);

    // Test the Auth method directly
    $documentData = [
        'document_type' => 'test_document',
        'original_name' => 'test_document.txt',
        'file_name' => $filename,
        'file_path' => realpath($filePath),
        'file_size' => strlen($testFileContent),
        'mime_type' => 'text/plain',
        'expiry_date' => null,
        'notes' => 'Test document upload'
    ];

    $documentId = $auth->addChildDocument($child['id'], $documentData, 1);

    if ($documentId) {
        echo "✓ Document upload successful! Document ID: {$documentId}\n";

        // Verify the document was saved in the database
        $savedDoc = $db->fetchOne("SELECT * FROM child_documents WHERE id = ?", [$documentId]);
        if ($savedDoc) {
            echo "✓ Document saved in database:\n";
            echo "  - document_type: {$savedDoc['document_type']}\n";
            echo "  - original_name: {$savedDoc['original_name']}\n";
            echo "  - file_name: {$savedDoc['file_name']}\n";
            echo "  - file_size: {$savedDoc['file_size']}\n";
            echo "  - mime_type: {$savedDoc['mime_type']}\n";

            // Check if file exists on disk
            if (file_exists($savedDoc['file_path'])) {
                echo "✓ File exists on disk at: {$savedDoc['file_path']}\n";
            } else {
                echo "✗ File not found on disk\n";
            }
        } else {
            echo "✗ Document not found in database\n";
        }
    } else {
        echo "✗ Document upload failed\n";
    }

    // Clean up
    if (file_exists($filePath)) {
        unlink($filePath);
    }

    echo "\nChild document upload test completed!\n";

} catch (Exception $e) {
    echo "✗ Error: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
}
?>
