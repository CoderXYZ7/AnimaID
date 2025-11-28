<?php
// Configuration JavaScript file for AnimaID
// This file provides configuration values to the frontend JavaScript

// Load Composer autoloader
require_once __DIR__ . '/../vendor/autoload.php';

// Load environment variables
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/..');
$dotenv->safeLoad();

// Set the content type to JavaScript
header('Content-Type: application/javascript');

// Get configuration values from environment
$appName = $_ENV['APP_NAME'] ?? 'AnimaID';
$appEnv = $_ENV['APP_ENV'] ?? 'production';
$showDemoCredentials = filter_var($_ENV['FEATURE_SHOW_DEMO_CREDENTIALS'] ?? 'false', FILTER_VALIDATE_BOOLEAN);

// Get the current protocol and host
$protocol = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http");
$host = $_SERVER['HTTP_HOST'];

// Remove any existing port from the host
$host = preg_replace('/:\d+$/', '', $host);

// Build API URL
$apiBaseUrl = $protocol . '://' . $host . '/api';

// Output the JavaScript configuration
echo "// AnimaID Frontend Configuration\n";
echo "window.ANIMAID_CONFIG = {\n";
echo "    api: {\n";
echo "        baseUrl: '" . $apiBaseUrl . "',\n";
echo "        port: " . ($protocol === 'https' ? 443 : 80) . "\n";
echo "    },\n";
echo "    system: {\n";
echo "        name: '" . addslashes($appName) . "',\n";
echo "        version: '0.9',\n";
echo "        environment: '" . $appEnv . "',\n";
echo "        locale: '" . ($_ENV['APP_LOCALE'] ?? 'it_IT') . "'\n";
echo "    },\n";
echo "    features: {\n";
echo "        show_demo_credentials: " . ($showDemoCredentials ? 'true' : 'false') . "\n";
echo "    }\n";
echo "};\n";
echo "\n";
echo "// Backward compatibility\n";
echo "window.API_BASE_URL = window.ANIMAID_CONFIG.api.baseUrl;\n";
?>
