<?php

/**
 * AnimaID Main Entry Point
 * Serves static files and handles basic routing
 */

// Get the requested path
$requestUri = $_SERVER['REQUEST_URI'];
$path = parse_url($requestUri, PHP_URL_PATH);
$path = ltrim($path, '/');

// Define public files that can be served
$publicFiles = [
    '' => 'index.html', // Root path
    'index.html' => 'index.html',
    'login.html' => 'login.html',
    'dashboard.html' => 'dashboard.html',
    'calendar.html' => 'calendar.html',
    'attendance.html' => 'attendance.html',
    'children.html' => 'children.html',
    'admin/users.html' => 'admin/users.html',
    'admin/roles.html' => 'admin/roles.html',
    'admin/status.html' => 'admin/status.html',
    'styleguide.html' => 'StyleGuide.md', // For demo purposes
];

// Check if requesting a public file
if (isset($publicFiles[$path])) {
    $file = $publicFiles[$path];

    if (file_exists($file)) {
        // Set appropriate content type
        $extension = pathinfo($file, PATHINFO_EXTENSION);
        $contentTypes = [
            'html' => 'text/html',
            'css' => 'text/css',
            'js' => 'application/javascript',
            'json' => 'application/json',
            'md' => 'text/markdown',
        ];

        $contentType = $contentTypes[$extension] ?? 'text/plain';
        header("Content-Type: {$contentType}");

        // Serve the file
        readfile($file);
        exit;
    }
}

// Check if requesting API
if (strpos($path, 'api/') === 0) {
    // Route to API handler
    require_once 'api/index.php';
    exit;
}

// Default: redirect to landing page
header('Location: /index.html');
exit;
