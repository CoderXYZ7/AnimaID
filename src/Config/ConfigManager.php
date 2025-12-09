<?php

namespace AnimaID\Config;

use Dotenv\Dotenv;

/**
 * Configuration Manager
 * Loads configuration from environment variables and provides access to config values
 */
class ConfigManager
{
    private static ?ConfigManager $instance = null;
    private array $config = [];

    private function __construct()
    {
        $this->loadEnvironment();
        $this->loadConfiguration();
    }

    public static function getInstance(): ConfigManager
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function loadEnvironment(): void
    {
        // Try to load .env with Dotenv if available
        if (class_exists('Dotenv\Dotenv')) {
            try {
                $dotenv = Dotenv::createImmutable(__DIR__ . '/../../');
                
                // Load .env file if it exists
                if (file_exists(__DIR__ . '/../../.env')) {
                    $dotenv->load();
                }
            } catch (\Exception $e) {
                // Ignore if Dotenv fails or creates issues, fallback to server env vars
            }
        }
    }

    private function loadConfiguration(): void
    {
        $this->config = [
            // Database Configuration
            'database' => [
                'type' => $this->env('DB_TYPE', 'sqlite'),
                'file' => __DIR__ . '/../../' . $this->env('DB_FILE', 'database/animaid.db'),
                'charset' => 'utf8mb4',
            ],

            // JWT Configuration
            'jwt' => [
                'secret' => $this->env('JWT_SECRET', $this->generateDefaultSecret()),
                'algorithm' => 'HS256',
                'expiration_hours' => (int) $this->env('SESSION_LIFETIME_HOURS', 2),
            ],

            // System Settings
            'system' => [
                'name' => $this->env('APP_NAME', 'AnimaID'),
                'version' => '0.9',
                'environment' => $this->env('APP_ENV', 'development'),
                'debug' => $this->env('APP_DEBUG', 'true') === 'true',
                'timezone' => $this->env('APP_TIMEZONE', 'Europe/Rome'),
                'locale' => $this->env('APP_LOCALE', 'it_IT'),
            ],

            // Security Settings
            'security' => [
                'bcrypt_cost' => (int) $this->env('BCRYPT_COST', 12),
                'password_min_length' => (int) $this->env('PASSWORD_MIN_LENGTH', 8),
                'password_require_uppercase' => $this->env('PASSWORD_REQUIRE_UPPERCASE', 'true') === 'true',
                'password_require_lowercase' => $this->env('PASSWORD_REQUIRE_LOWERCASE', 'true') === 'true',
                'password_require_numbers' => $this->env('PASSWORD_REQUIRE_NUMBERS', 'true') === 'true',
                'password_require_symbols' => $this->env('PASSWORD_REQUIRE_SYMBOLS', 'false') === 'true',
                'max_login_attempts' => 5,
                'lockout_duration_minutes' => 15,
                'session_lifetime_hours' => (int) $this->env('SESSION_LIFETIME_HOURS', 8),
            ],

            // API Settings
            'api' => [
                'rate_limit_authenticated' => (int) $this->env('API_RATE_LIMIT_AUTHENTICATED', 1000),
                'rate_limit_unauthenticated' => (int) $this->env('API_RATE_LIMIT_UNAUTHENTICATED', 100),
                'cors_origins' => explode(',', $this->env('CORS_ORIGINS', 'http://localhost:3000,http://localhost:8080')),
                'cors_headers' => explode(',', $this->env('CORS_HEADERS', 'Content-Type,Authorization,X-Requested-With')),
                'cors_methods' => explode(',', $this->env('CORS_METHODS', 'GET,POST,PUT,DELETE,OPTIONS')),
                'port' => 443,
            ],

            // File Upload Settings
            'uploads' => [
                'max_file_size' => (int) $this->env('UPLOAD_MAX_FILE_SIZE', 10 * 1024 * 1024),
                'allowed_extensions' => explode(',', $this->env('UPLOAD_ALLOWED_EXTENSIONS', 'jpg,jpeg,png,gif,pdf,doc,docx')),
                'upload_path' => __DIR__ . '/../../' . $this->env('UPLOAD_PATH', 'uploads/'),
            ],

            // Email Configuration
            'email' => [
                'enabled' => $this->env('EMAIL_ENABLED', 'false') === 'true',
                'smtp_host' => $this->env('SMTP_HOST', 'localhost'),
                'smtp_port' => (int) $this->env('SMTP_PORT', 587),
                'smtp_username' => $this->env('SMTP_USERNAME', ''),
                'smtp_password' => $this->env('SMTP_PASSWORD', ''),
                'smtp_encryption' => $this->env('SMTP_ENCRYPTION', 'tls'),
                'from_address' => $this->env('EMAIL_FROM_ADDRESS', 'noreply@animaid.local'),
                'from_name' => $this->env('EMAIL_FROM_NAME', 'AnimaID System'),
            ],

            // Logging Configuration
            'logging' => [
                'enabled' => $this->env('LOG_ENABLED', 'true') === 'true',
                'level' => $this->env('LOG_LEVEL', 'debug'),
                'file' => __DIR__ . '/../../' . $this->env('LOG_FILE', 'logs/animaid.log'),
                'max_files' => (int) $this->env('LOG_MAX_FILES', 30),
                'max_file_size' => 10 * 1024 * 1024,
            ],

            // Backup Configuration
            'backup' => [
                'enabled' => $this->env('BACKUP_ENABLED', 'true') === 'true',
                'path' => __DIR__ . '/../../' . $this->env('BACKUP_PATH', 'backups/'),
                'schedule' => $this->env('BACKUP_SCHEDULE', 'daily'),
                'retention_days' => (int) $this->env('BACKUP_RETENTION_DAYS', 30),
                'compress' => true,
            ],

            // Feature Flags
            'features' => [
                'user_registration' => $this->env('FEATURE_USER_REGISTRATION', 'false') === 'true',
                'password_reset' => $this->env('FEATURE_PASSWORD_RESET', 'true') === 'true',
                'email_notifications' => $this->env('FEATURE_EMAIL_NOTIFICATIONS', 'false') === 'true',
                'two_factor_auth' => $this->env('FEATURE_TWO_FACTOR_AUTH', 'false') === 'true',
                'audit_logging' => $this->env('FEATURE_AUDIT_LOGGING', 'true') === 'true',
                'api_documentation' => $this->env('FEATURE_API_DOCUMENTATION', 'true') === 'true',
                'show_medical_data' => $this->env('FEATURE_SHOW_MEDICAL_DATA', 'false') === 'true',
                'show_demo_credentials' => $this->env('FEATURE_SHOW_DEMO_CREDENTIALS', 'false') === 'true',
            ],
        ];
    }

    private function env(string $key, $default = null)
    {
        return $_ENV[$key] ?? getenv($key) ?: $default;
    }

    private function generateDefaultSecret(): string
    {
        // Generate a random secret if none is provided
        // This should only be used in development
        if ($this->env('APP_ENV') === 'production') {
            throw new \Exception('JWT_SECRET must be set in production environment');
        }
        
        return bin2hex(random_bytes(32));
    }

    public function get(string $key, $default = null)
    {
        $keys = explode('.', $key);
        $value = $this->config;

        foreach ($keys as $k) {
            if (!isset($value[$k])) {
                return $default;
            }
            $value = $value[$k];
        }

        return $value;
    }

    public function all(): array
    {
        return $this->config;
    }
}
