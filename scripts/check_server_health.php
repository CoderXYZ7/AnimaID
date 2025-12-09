#!/usr/bin/env php
<?php

/**
 * AnimaID Server Health Check
 * Diagnoses common server configuration issues
 */

echo "=========================================\n";
echo "AnimaID Server Health Check\n";
echo "=========================================\n\n";

$issues = [];
$warnings = [];

// Check 1: Database file exists
echo "[1/6] Checking database file...\n";
$dbPath = __DIR__ . '/../database/animaid.db';
if (!file_exists($dbPath)) {
    $issues[] = "Database file not found: {$dbPath}";
    echo "❌ Database file not found\n";
} else {
    echo "✓ Database file exists\n";
    
    // Check database permissions
    $perms = substr(sprintf('%o', fileperms($dbPath)), -4);
    $owner = posix_getpwuid(fileowner($dbPath))['name'];
    $group = posix_getgrgid(filegroup($dbPath))['name'];
    
    echo "  Path: {$dbPath}\n";
    echo "  Permissions: {$perms}\n";
    echo "  Owner: {$owner}:{$group}\n";
    
    if (!is_readable($dbPath)) {
        $issues[] = "Database file is not readable";
        echo "❌ Database is not readable\n";
    } else {
        echo "✓ Database is readable\n";
    }
    
    if (!is_writable($dbPath)) {
        $issues[] = "Database file is not writable (owner: {$owner}:{$group}, perms: {$perms})";
        echo "❌ Database is not writable\n";
    } else {
        echo "✓ Database is writable\n";
    }
}
echo "\n";

// Check 2: Database directory permissions
echo "[2/6] Checking database directory...\n";
$dbDir = dirname($dbPath);
if (!is_writable($dbDir)) {
    $issues[] = "Database directory is not writable: {$dbDir}";
    echo "❌ Database directory is not writable\n";
} else {
    echo "✓ Database directory is writable\n";
}
echo "\n";

// Check 3: Composer dependencies
echo "[3/6] Checking Composer dependencies...\n";
$vendorPath = __DIR__ . '/../vendor/autoload.php';
if (!file_exists($vendorPath)) {
    $issues[] = "Composer dependencies not installed (vendor/autoload.php missing)";
    echo "❌ Composer dependencies not installed\n";
} else {
    echo "✓ Composer dependencies installed\n";
}
echo "\n";

// Check 4: .env file
echo "[4/6] Checking .env file...\n";
$envPath = __DIR__ . '/../.env';
if (!file_exists($envPath)) {
    $warnings[] = ".env file not found (using defaults)";
    echo "⚠ .env file not found\n";
} else {
    echo "✓ .env file exists\n";
    
    // Try to load it
    try {
        require_once $vendorPath;
        $dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/..');
        $dotenv->safeLoad();
        
        if (empty($_ENV['JWT_SECRET'])) {
            $warnings[] = "JWT_SECRET not set in .env";
            echo "⚠ JWT_SECRET not set\n";
        } else {
            echo "✓ JWT_SECRET is set\n";
        }
    } catch (Exception $e) {
        $issues[] = ".env file has errors: " . $e->getMessage();
        echo "❌ .env file has errors\n";
    }
}
echo "\n";

// Check 5: Test database connection
echo "[5/6] Testing database connection...\n";
try {
    $db = new PDO("sqlite:{$dbPath}");
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Try a simple query
    $stmt = $db->query("SELECT COUNT(*) as count FROM users");
    $count = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
    
    echo "✓ Database connection successful\n";
    echo "  Users in database: {$count}\n";
    
    // Try to write
    try {
        $db->exec("CREATE TABLE IF NOT EXISTS _test_write (id INTEGER)");
        $db->exec("DROP TABLE _test_write");
        echo "✓ Database write test successful\n";
    } catch (Exception $e) {
        $issues[] = "Cannot write to database: " . $e->getMessage();
        echo "❌ Cannot write to database\n";
    }
    
} catch (Exception $e) {
    $issues[] = "Database connection failed: " . $e->getMessage();
    echo "❌ Database connection failed\n";
}
echo "\n";

// Check 6: Check recent error logs
echo "[6/6] Checking error logs...\n";
$logFile = __DIR__ . '/../logs/animaid.log';
if (file_exists($logFile)) {
    $lines = array_slice(file($logFile), -5);
    if (!empty($lines)) {
        echo "Recent log entries:\n";
        foreach ($lines as $line) {
            echo "  " . trim($line) . "\n";
        }
    } else {
        echo "✓ No recent errors\n";
    }
} else {
    echo "⚠ Log file not found\n";
}
echo "\n";

// Summary
echo "=========================================\n";
echo "Summary\n";
echo "=========================================\n\n";

if (empty($issues) && empty($warnings)) {
    echo "✅ All checks passed! Server should be healthy.\n\n";
} else {
    if (!empty($issues)) {
        echo "❌ Issues found (" . count($issues) . "):\n";
        foreach ($issues as $i => $issue) {
            echo "  " . ($i + 1) . ". {$issue}\n";
        }
        echo "\n";
    }
    
    if (!empty($warnings)) {
        echo "⚠ Warnings (" . count($warnings) . "):\n";
        foreach ($warnings as $i => $warning) {
            echo "  " . ($i + 1) . ". {$warning}\n";
        }
        echo "\n";
    }
    
    echo "Recommended actions:\n";
    if (!empty($issues)) {
        echo "1. Run: bash scripts/maintenance/fix-permissions.sh\n";
        echo "2. Run: composer install --no-dev\n";
        echo "3. Check .env file configuration\n";
    }
}
