<?php
//RENAME THIS TO config.php TO USE
// AnimaID Configuration File
// This file contains all system configuration including database settings and default admin account

return [
    // Database Configuration
    'database' => [
        'type' => 'sqlite',
        'file' => __DIR__ . '/../database/animaid.db',
        'charset' => 'utf8mb4',
    ],

    // JWT Configuration
    'jwt' => [
        'secret' => 'animaid-production-secret-key-2025-11-19-change-in-production',
        'algorithm' => 'HS256',
        'expiration_hours' => 2, // Token expires in 2 hours
        'refresh_expiration_days' => 7, // Refresh token expires in 7 days
    ],

    // Default Admin Account
    // This account will be created automatically on first run
    'default_admin' => [
        'username' => 'admin',
        'email' => 'admin@animaid.local',
        'password' => 'Admin123!@#', // Change this immediately after first login
        'auto_create' => true, // Set to false after initial setup
    ],

    // System Settings
    'system' => [
        'name' => 'AnimaID',
        'version' => '0.9',
        'environment' => 'development', // development, staging, production
        'debug' => true,
        'timezone' => 'Europe/Rome',
        'locale' => 'it_IT',
    ],

    // Security Settings
    'security' => [
        'bcrypt_cost' => 12,
        'password_min_length' => 8,
        'password_require_uppercase' => true,
        'password_require_lowercase' => true,
        'password_require_numbers' => true,
        'password_require_symbols' => false,
        'max_login_attempts' => 5,
        'lockout_duration_minutes' => 15,
        'session_lifetime_hours' => 8,
    ],

    // API Settings
    'api' => [
        'rate_limit_authenticated' => 1000, // requests per hour
        'rate_limit_unauthenticated' => 100, // requests per hour
        'cors_origins' => ['http://localhost:3000', 'http://localhost:8080', 'https://animaidsgn.mywire.org'],
        'cors_headers' => ['Content-Type', 'Authorization', 'X-Requested-With'],
        'cors_methods' => ['GET', 'POST', 'PUT', 'DELETE', 'OPTIONS'],
        'port' => 443, // Port used for API calls (443 for HTTPS)
    ],

    // File Upload Settings
    'uploads' => [
        'max_file_size' => 10 * 1024 * 1024, // 10MB
        'allowed_extensions' => ['jpg', 'jpeg', 'png', 'gif', 'pdf', 'doc', 'docx'],
        'upload_path' => __DIR__ . '/../uploads/',
    ],

    // Email Configuration (for future use)
    'email' => [
        'enabled' => false,
        'smtp_host' => 'localhost',
        'smtp_port' => 587,
        'smtp_username' => '',
        'smtp_password' => '',
        'smtp_encryption' => 'tls',
        'from_address' => 'noreply@animaid.local',
        'from_name' => 'AnimaID System',
    ],

    // Logging Configuration
    'logging' => [
        'enabled' => true,
        'level' => 'debug', // emergency, alert, critical, error, warning, notice, info, debug
        'file' => __DIR__ . '/../logs/animaid.log',
        'max_files' => 30,
        'max_file_size' => 10 * 1024 * 1024, // 10MB
    ],

    // Backup Configuration
    'backup' => [
        'enabled' => true,
        'path' => __DIR__ . '/../backups/',
        'schedule' => 'daily', // daily, weekly, monthly
        'retention_days' => 30,
        'compress' => true,
    ],

    // Feature Flags
    'features' => [
        'user_registration' => false, // Public user registration
        'password_reset' => true,
        'email_notifications' => false,
        'two_factor_auth' => false,
        'audit_logging' => true,
        'api_documentation' => true,
        'show_medical_data' => false,
        'show_demo_credentials' => false, // Show demo credentials on login page
    ],

    // External Services (for future integrations)
    'services' => [
        'google_maps_api_key' => '',
        'stripe_publishable_key' => '',
        'stripe_secret_key' => '',
        'slack_webhook_url' => '',
    ],
];
