<?php

/**
 * AnimaID Public Entry Point
 * This file should be in the public/ directory if your web server's document root points here
 */

// Get the requested path
$requestUri = $_SERVER['REQUEST_URI'];
$path = parse_url($requestUri, PHP_URL_PATH);

// Log for debugging
$logFile = __DIR__ . '/../debug_log.txt';
file_put_contents($logFile, date('Y-m-d H:i:s') . " - URI: $requestUri - Path: $path\n", FILE_APPEND);

// Special handling for config.js.php
if ($path === '/config.js.php') {
    $configFile = __DIR__ . '/config.js.php';
    if (file_exists($configFile)) {
        require $configFile;
        exit;
    }
}

// Serve static files directly if they exist in the public directory
$staticFilePath = __DIR__ . $path;
if (file_exists($staticFilePath) && is_file($staticFilePath)) {
    $extension = pathinfo($staticFilePath, PATHINFO_EXTENSION);
    $contentTypes = [
        'html' => 'text/html',
        'css' => 'text/css',
        'js' => 'application/javascript',
        'json' => 'application/json',
        'md' => 'text/markdown',
        'ico' => 'image/x-icon',
        'png' => 'image/png',
        'jpg' => 'image/jpeg',
        'jpeg' => 'image/jpeg',
        'svg' => 'image/svg+xml',
        'php' => 'text/html', // PHP files should be executed, not served
    ];
    
    // For PHP files, execute them
    if ($extension === 'php') {
        require $staticFilePath;
        exit;
    }
    
    $contentType = $contentTypes[$extension] ?? 'text/plain';
    header("Content-Type: {$contentType}");
    readfile($staticFilePath);
    exit;
}

$path = ltrim($path, '/');

// Define public files that can be served
$publicFiles = [
    '' => 'index.html', // Root path
    'index.html' => 'index.html',
    'login.html' => 'login.html',
    'dashboard.html' => 'dashboard.html',
    'calendar.html' => 'pages/calendar.html',
    'attendance.html' => 'pages/attendance.html',
    'children.html' => 'pages/children.html',
    'animators.html' => 'pages/animators.html',
    'communications.html' => 'pages/communications.html',
    'media.html' => 'pages/media.html',
    'shared.html' => 'pages/shared.html',
    'pages/wiki.html' => 'pages/wiki.html',
    'pages/wiki-categories.html' => 'pages/wiki-categories.html',
    'public.html' => 'public.html',
    'admin/users.html' => 'admin/users.html',
    'admin/roles.html' => 'admin/roles.html',
    'admin/status.html' => 'admin/status.html',
    'admin/reports.html' => 'admin/reports.html',
    'config.js' => 'config.js.php', // Add config.js route
];

// Check if requesting a public file
if (isset($publicFiles[$path])) {
    $file = __DIR__ . '/' . $publicFiles[$path];

    if (file_exists($file)) {
        // Set appropriate content type
        $extension = pathinfo($file, PATHINFO_EXTENSION);
        $contentTypes = [
            'html' => 'text/html',
            'css' => 'text/css',
            'js' => 'application/javascript',
            'json' => 'application/json',
            'md' => 'text/markdown',
            'php' => 'text/html',
        ];

        $contentType = $contentTypes[$extension] ?? 'text/plain';
        header("Content-Type: {$contentType}");

        // For PHP files, execute them
        if ($extension === 'php') {
            require $file;
        } else {
            readfile($file);
        }
        exit;
    }
}

// Check if requesting API
if (strpos($path, 'api/') === 0) {
    // Route to API handler (one level up from public/)
    require_once __DIR__ . '/../api/index.php';
    exit;
}

// Default: redirect to landing page
header('Location: /index.html');
exit;
