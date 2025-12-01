<?php
/**
 * Diagnostic file to check server configuration
 * Access this at: https://animaidsgn.mywire.org/diagnostic.php
 */

header('Content-Type: text/plain');

echo "=== AnimaID Server Diagnostic ===\n\n";

echo "1. Current File Location:\n";
echo "   " . __FILE__ . "\n\n";

echo "2. Document Root:\n";
echo "   " . $_SERVER['DOCUMENT_ROOT'] . "\n\n";

echo "3. Request URI:\n";
echo "   " . $_SERVER['REQUEST_URI'] . "\n\n";

echo "4. Script Filename:\n";
echo "   " . $_SERVER['SCRIPT_FILENAME'] . "\n\n";

echo "5. PHP Version:\n";
echo "   " . PHP_VERSION . "\n\n";

echo "6. File Checks:\n";
$files = [
    'config.js.php' => __DIR__ . '/config.js.php',
    'index.php' => __DIR__ . '/index.php',
    '.htaccess' => __DIR__ . '/.htaccess',
    '../api/index.php' => __DIR__ . '/../api/index.php',
    '../index.php' => __DIR__ . '/../index.php',
];

foreach ($files as $name => $path) {
    $exists = file_exists($path) ? 'EXISTS' : 'MISSING';
    $readable = is_readable($path) ? 'READABLE' : 'NOT READABLE';
    echo "   $name: $exists, $readable\n";
    echo "      Path: $path\n";
}

echo "\n7. Apache Modules (if available):\n";
if (function_exists('apache_get_modules')) {
    $modules = apache_get_modules();
    echo "   mod_rewrite: " . (in_array('mod_rewrite', $modules) ? 'ENABLED' : 'DISABLED') . "\n";
} else {
    echo "   apache_get_modules() not available\n";
}

echo "\n8. .htaccess Content:\n";
$htaccess = __DIR__ . '/.htaccess';
if (file_exists($htaccess)) {
    echo "   " . str_replace("\n", "\n   ", file_get_contents($htaccess));
} else {
    echo "   .htaccess not found\n";
}

echo "\n\n9. All SERVER variables:\n";
foreach ($_SERVER as $key => $value) {
    if (!is_array($value)) {
        echo "   $key = $value\n";
    }
}
