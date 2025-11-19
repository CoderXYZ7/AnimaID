<?php

echo "Current directory: " . __DIR__ . "\n";

try {
    echo "Loading config from: " . __DIR__ . '/../config/config.php' . "\n";
    $config = require __DIR__ . '/../config/config.php';
    echo "Config loaded successfully\n";
    echo "Version: " . ($config['system']['version'] ?? 'unknown') . "\n";
} catch (Exception $e) {
    echo "Error loading config: " . $e->getMessage() . "\n";
}
