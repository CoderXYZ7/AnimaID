<?php
// Simple test script to check server configuration
echo "Server Test Script\n";
echo "==================\n";
echo "PHP Version: " . PHP_VERSION . "\n";
echo "Current Directory: " . __DIR__ . "\n";
echo "Request URI: " . ($_SERVER['REQUEST_URI'] ?? 'N/A') . "\n";
echo "Document Root: " . ($_SERVER['DOCUMENT_ROOT'] ?? 'N/A') . "\n";
echo "Script Name: " . ($_SERVER['SCRIPT_NAME'] ?? 'N/A') . "\n";

// Test if we can access public files
$test_files = [
    'public/index.html',
    'public/login.html', 
    'public/js/themeLanguageSwitcher.js',
    'public/config.js.php'
];

echo "\nFile Access Test:\n";
foreach ($test_files as $file) {
    if (file_exists($file)) {
        echo "✓ $file exists\n";
    } else {
        echo "✗ $file NOT FOUND\n";
    }
}

// Test .htaccess by checking if mod_rewrite is working
echo "\nMod_Rewrite Test:\n";
if (function_exists('apache_get_modules')) {
    $modules = apache_get_modules();
    if (in_array('mod_rewrite', $modules)) {
        echo "✓ mod_rewrite is enabled\n";
    } else {
        echo "✗ mod_rewrite is NOT enabled\n";
    }
} else {
    echo "? Cannot check mod_rewrite status\n";
}

// Test if we can read config
echo "\nConfig Test:\n";
if (file_exists('config/config.php')) {
    $config = require 'config/config.php';
    echo "✓ Config loaded successfully\n";
    echo "  API Port: " . ($config['api']['port'] ?? 'Not set') . "\n";
} else {
    echo "✗ Config file not found\n";
}
?>
