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
    '' => 'public/index.html', // Root path
    'index.html' => 'public/index.html',
    'login.html' => 'public/login.html',
    'dashboard.html' => 'public/dashboard.html',
    'calendar.html' => 'public/pages/calendar.html',
    'attendance.html' => 'public/pages/attendance.html',
    'children.html' => 'public/pages/children.html',
    'animators.html' => 'public/pages/animators.html',
    'communications.html' => 'public/pages/communications.html',
    'media.html' => 'public/pages/media.html',
    'shared.html' => 'public/pages/shared.html',
    'admin/users.html' => 'public/admin/users.html',
    'admin/roles.html' => 'public/admin/roles.html',
    'admin/status.html' => 'public/admin/status.html',
    'styleguide.html' => 'docs/StyleGuide.md', // For demo purposes
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
