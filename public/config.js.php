<?php
// Configuration JavaScript file for AnimaID
// This file provides configuration values to the frontend JavaScript

// Load the main configuration
$configFile = __DIR__ . '/../config/config.php';
if (file_exists($configFile)) {
    $config = require $configFile;
} else {
    // Fallback to defaults if config doesn't exist
    $config = [
        'system' => [
            'name' => 'AnimaID',
            'version' => '0.9',
            'locale' => 'it_IT'
        ],
        'features' => [
            'show_demo_credentials' => false
        ]
    ];
}

// Set the content type to JavaScript
header('Content-Type: application/javascript');

// Get the current protocol and host
$protocol = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http");
$host = $_SERVER['HTTP_HOST'];

// Remove any existing port from the host
$host = preg_replace('/:\d+$/', '', $host);

// Build API URL
$apiBaseUrl = $protocol . '://' . $host . '/api';

// Determine environment
$environment = ($config['system']['environment'] ?? 'production');

// Output the JavaScript configuration
echo "// AnimaID Frontend Configuration\n";
echo "window.ANIMAID_CONFIG = {\n";
echo "    api: {\n";
echo "        baseUrl: '" . $apiBaseUrl . "',\n";
echo "        port: " . ($protocol === 'https' ? 443 : 80) . "\n";
echo "    },\n";
echo "    system: {\n";
echo "        name: '" . ($config['system']['name'] ?? 'AnimaID') . "',\n";
echo "        version: '" . ($config['system']['version'] ?? '0.9') . "',\n";
echo "        environment: '" . $environment . "',\n";
echo "        locale: '" . ($config['system']['locale'] ?? 'it_IT') . "'\n";
echo "    },\n";
echo "    features: {\n";
echo "        show_demo_credentials: " . (isset($config['features']['show_demo_credentials']) && $config['features']['show_demo_credentials'] ? 'true' : 'false') . "\n";
echo "    }\n";
echo "};\n";
echo "\n";
echo "// Backward compatibility\n";
echo "window.API_BASE_URL = window.ANIMAID_CONFIG.api.baseUrl;\n";
?>
