<?php

require_once 'src/Database.php';

try {
    echo "Testing database connection...\n";
    $db = Database::getInstance();
    echo "Database connection successful\n";

    echo "Testing query...\n";
    $result = $db->fetchOne("SELECT COUNT(*) as count FROM users");
    echo "Query result: " . json_encode($result) . "\n";

    echo "Database test completed successfully!\n";

} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
}
