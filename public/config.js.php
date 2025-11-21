<?php
// Configuration JavaScript file for AnimaID
// This file provides configuration values to the frontend JavaScript

// Load the main configuration
$config = require __DIR__ . '/../config/config.php';

// Set the content type to JavaScript
header('Content-Type: application/javascript');

// Get the current protocol and host
$protocol = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http");
$host = $_SERVER['HTTP_HOST'];

// Remove any existing port from the host
$host = preg_replace('/:\d+$/', '', $host);

// Get the configured API port
$apiPort = $config['api']['port'] ?? 8000;

// For HTTPS, don't include port in URL (standard HTTPS port 443)
// For HTTP with non-standard ports, include the port
if ($protocol === 'https') {
    $apiBaseUrl = $protocol . '://' . $host . '/api';
} else {
    $apiBaseUrl = $protocol . '://' . $host . ':' . $apiPort . '/api';
}

// Output the JavaScript configuration
echo "// AnimaID Frontend Configuration\n";
echo "window.ANIMAID_CONFIG = {\n";
echo "    api: {\n";
echo "        baseUrl: '" . $apiBaseUrl . "',\n";
echo "        port: " . $apiPort . "\n";
echo "    },\n";
echo "    system: {\n";
echo "        name: '" . ($config['system']['name'] ?? 'AnimaID') . "',\n";
echo "        version: '" . ($config['system']['version'] ?? '0.9') . "',\n";
echo "        locale: '" . ($config['system']['locale'] ?? 'it_IT') . "'\n";
echo "    },\n";
echo "    features: {\n";
echo "        show_demo_credentials: " . (isset($config['features']['show_demo_credentials']) ? ($config['features']['show_demo_credentials'] ? 'true' : 'false') : 'true') . "\n";
echo "    }\n";
echo "};\n";

// Also provide a helper function for backward compatibility
echo "// Backward compatibility\n";
echo "const API_BASE_URL = window.ANIMAID_CONFIG.api.baseUrl;\n";
?>
