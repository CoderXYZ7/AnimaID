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

// For the server, we need to use the same port as the main site
// Don't include port in URL for standard ports
if (($protocol === 'https' && $apiPort === 443) || ($protocol === 'http' && $apiPort === 80)) {
    $apiBaseUrl = $protocol . '://' . $host . '/api';
} else {
    // Use the configured port for non-standard ports
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
echo "    }\n";
echo "};\n";

// Also provide a helper function for backward compatibility
echo "// Backward compatibility\n";
echo "const API_BASE_URL = window.ANIMAID_CONFIG.api.baseUrl;\n";
?>
