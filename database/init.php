<?php

/**
 * AnimaID Database Initialization Script
 * This script creates the SQLite database and populates it with initial data
 */

require_once __DIR__ . '/../config.php';

$config = require __DIR__ . '/../config.php';

// Create database directory if it doesn't exist
$dbDir = dirname($config['database']['file']);
if (!is_dir($dbDir)) {
    mkdir($dbDir, 0755, true);
}

// Connect to SQLite database (creates file if it doesn't exist)
try {
    $pdo = new PDO('sqlite:' . $config['database']['file']);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);

    echo "Connected to database successfully.\n";

    // Create tables
    createTables($pdo);
    echo "Database tables created.\n";

    // Insert initial data
    insertInitialData($pdo, $config);
    echo "Initial data inserted.\n";

    // Create default admin account
    if ($config['default_admin']['auto_create']) {
        createDefaultAdmin($pdo, $config);
        echo "Default admin account created.\n";
    }

    echo "Database initialization completed successfully!\n";
    echo "Default admin credentials:\n";
    echo "Username: " . $config['default_admin']['username'] . "\n";
    echo "Password: " . $config['default_admin']['password'] . "\n";
    echo "Please change the password immediately after first login.\n";

} catch (PDOException $e) {
    die("Database initialization failed: " . $e->getMessage() . "\n");
}

function createTables(PDO $pdo) {
    // Users table
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS users (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            username VARCHAR(50) UNIQUE NOT NULL,
            email VARCHAR(255) UNIQUE NOT NULL,
            password_hash VARCHAR(255) NOT NULL,
            is_active BOOLEAN DEFAULT 1,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            last_login DATETIME NULL
        )
    ");

    // Roles table
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS roles (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            name VARCHAR(50) UNIQUE NOT NULL,
            display_name VARCHAR(100) NOT NULL,
            description TEXT,
            is_system_role BOOLEAN DEFAULT 0,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP
        )
    ");

    // User roles junction table
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS user_roles (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            user_id INTEGER NOT NULL,
            role_id INTEGER NOT NULL,
            assigned_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            assigned_by INTEGER,
            is_primary BOOLEAN DEFAULT 0,
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
            FOREIGN KEY (role_id) REFERENCES roles(id) ON DELETE CASCADE,
            FOREIGN KEY (assigned_by) REFERENCES users(id),
            UNIQUE(user_id, role_id)
        )
    ");

    // Permissions table
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS permissions (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            name VARCHAR(100) UNIQUE NOT NULL,
            display_name VARCHAR(150) NOT NULL,
            description TEXT,
            module VARCHAR(50) NOT NULL,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP
        )
    ");

    // Role permissions junction table
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS role_permissions (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            role_id INTEGER NOT NULL,
            permission_id INTEGER NOT NULL,
            granted_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            granted_by INTEGER,
            FOREIGN KEY (role_id) REFERENCES roles(id) ON DELETE CASCADE,
            FOREIGN KEY (permission_id) REFERENCES permissions(id) ON DELETE CASCADE,
            FOREIGN KEY (granted_by) REFERENCES users(id),
            UNIQUE(role_id, permission_id)
        )
    ");

    // User sessions table
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS user_sessions (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            user_id INTEGER NOT NULL,
            session_token VARCHAR(255) UNIQUE NOT NULL,
            ip_address VARCHAR(45),
            user_agent TEXT,
            expires_at DATETIME NOT NULL,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
        )
    ");

    // Password resets table
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS password_resets (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            user_id INTEGER NOT NULL,
            reset_token VARCHAR(255) UNIQUE NOT NULL,
            expires_at DATETIME NOT NULL,
            used BOOLEAN DEFAULT 0,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
        )
    ");
}

function insertInitialData(PDO $pdo, array $config) {
    // Insert default roles
    $roles = [
        ['technical_admin', 'Technical Admin', 'Full system access', 1],
        ['organizzatore', 'Organizzatore', 'Center organization', 1],
        ['responsabile', 'Responsabile', 'Department responsibility', 1],
        ['animatore', 'Animatore', 'Activity animator', 1],
        ['aiutoanimatore', 'Aiutoanimatore', 'Assistant animator', 1],
    ];

    $stmt = $pdo->prepare("
        INSERT OR IGNORE INTO roles (name, display_name, description, is_system_role)
        VALUES (?, ?, ?, ?)
    ");

    foreach ($roles as $role) {
        $stmt->execute($role);
    }

    // Insert permissions
    $permissions = [
        // Registrations
        ['registrations.view', 'View Registrations', 'Can view child registrations', 'registrations'],
        ['registrations.create', 'Create Registrations', 'Can create new registrations', 'registrations'],
        ['registrations.edit', 'Edit Registrations', 'Can edit existing registrations', 'registrations'],
        ['registrations.delete', 'Delete Registrations', 'Can delete registrations', 'registrations'],
        ['registrations.approve', 'Approve Registrations', 'Can approve pending registrations', 'registrations'],

        // Calendar
        ['calendar.view', 'View Calendar', 'Can view calendar events', 'calendar'],
        ['calendar.create', 'Create Calendar Events', 'Can create calendar events', 'calendar'],
        ['calendar.edit', 'Edit Calendar Events', 'Can edit calendar events', 'calendar'],
        ['calendar.delete', 'Delete Calendar Events', 'Can delete calendar events', 'calendar'],
        ['calendar.publish', 'Publish Calendar Events', 'Can publish events to public calendar', 'calendar'],

        // Attendance
        ['attendance.view', 'View Attendance', 'Can view attendance records', 'attendance'],
        ['attendance.checkin', 'Check-in/Check-out', 'Can perform check-in/check-out', 'attendance'],
        ['attendance.edit', 'Edit Attendance', 'Can edit attendance records', 'attendance'],
        ['attendance.report', 'Generate Reports', 'Can generate attendance reports', 'attendance'],

        // Communications
        ['communications.view', 'View Communications', 'Can view internal communications', 'communications'],
        ['communications.send', 'Send Messages', 'Can send internal messages', 'communications'],
        ['communications.broadcast', 'Broadcast Messages', 'Can send broadcast messages', 'communications'],
        ['communications.manage', 'Manage Communications', 'Can manage communication settings', 'communications'],

        // Media
        ['media.view', 'View Media', 'Can view media files', 'media'],
        ['media.upload', 'Upload Media', 'Can upload media files', 'media'],
        ['media.approve', 'Approve Media', 'Can approve media for publication', 'media'],
        ['media.delete', 'Delete Media', 'Can delete media files', 'media'],

        // Wiki
        ['wiki.view', 'View Wiki', 'Can view wiki content', 'wiki'],
        ['wiki.edit', 'Edit Wiki', 'Can edit wiki content', 'wiki'],
        ['wiki.create', 'Create Wiki Entries', 'Can create new wiki entries', 'wiki'],
        ['wiki.moderate', 'Moderate Wiki', 'Can moderate user feedback', 'wiki'],

        // Spaces
        ['spaces.view', 'View Spaces', 'Can view space bookings', 'spaces'],
        ['spaces.book', 'Book Spaces', 'Can create space bookings', 'spaces'],
        ['spaces.edit', 'Edit Space Bookings', 'Can edit space bookings', 'spaces'],
        ['spaces.manage', 'Manage Spaces', 'Can manage space configurations', 'spaces'],

        // Reports
        ['reports.view', 'View Reports', 'Can view reports and KPIs', 'reports'],
        ['reports.generate', 'Generate Reports', 'Can generate custom reports', 'reports'],
        ['reports.export', 'Export Reports', 'Can export report data', 'reports'],

        // Admin
        ['admin.users', 'Manage Users', 'Can manage users', 'admin'],
        ['admin.roles', 'Manage Roles', 'Can manage roles and permissions', 'admin'],
        ['admin.system', 'System Administration', 'Can manage system settings', 'admin'],
        ['admin.backup', 'Backup System', 'Can perform system backups', 'admin'],
    ];

    $stmt = $pdo->prepare("
        INSERT OR IGNORE INTO permissions (name, display_name, description, module)
        VALUES (?, ?, ?, ?)
    ");

    foreach ($permissions as $permission) {
        $stmt->execute($permission);
    }

    // Assign permissions to roles
    assignPermissionsToRoles($pdo);
}

function assignPermissionsToRoles(PDO $pdo) {
    // Get role IDs
    $roles = $pdo->query("SELECT id, name FROM roles")->fetchAll(PDO::FETCH_KEY_PAIR);

    // Get permission IDs
    $permissions = $pdo->query("SELECT id, name FROM permissions")->fetchAll(PDO::FETCH_KEY_PAIR);

    // Define role-permission mappings
    $rolePermissions = [
        'technical_admin' => array_keys($permissions), // All permissions

        'organizzatore' => [
            // Registrations
            'registrations.view', 'registrations.create', 'registrations.edit', 'registrations.approve',
            // Calendar
            'calendar.view', 'calendar.create', 'calendar.edit', 'calendar.publish',
            // Attendance
            'attendance.view', 'attendance.report',
            // Communications
            'communications.view', 'communications.send', 'communications.broadcast',
            // Media
            'media.view', 'media.upload', 'media.approve',
            // Wiki
            'wiki.view', 'wiki.edit', 'wiki.create',
            // Spaces
            'spaces.view', 'spaces.book', 'spaces.edit',
            // Reports
            'reports.view', 'reports.generate', 'reports.export',
        ],

        'responsabile' => [
            // Registrations
            'registrations.view', 'registrations.edit',
            // Calendar
            'calendar.view', 'calendar.create', 'calendar.edit',
            // Attendance
            'attendance.view', 'attendance.checkin', 'attendance.edit', 'attendance.report',
            // Communications
            'communications.view', 'communications.send',
            // Media
            'media.view', 'media.upload',
            // Wiki
            'wiki.view', 'wiki.edit',
            // Spaces
            'spaces.view', 'spaces.book', 'spaces.edit',
            // Reports
            'reports.view', 'reports.generate',
        ],

        'animatore' => [
            // Registrations
            'registrations.view',
            // Calendar
            'calendar.view', 'calendar.edit',
            // Attendance
            'attendance.view', 'attendance.checkin', 'attendance.edit',
            // Communications
            'communications.view', 'communications.send',
            // Media
            'media.view', 'media.upload',
            // Wiki
            'wiki.view', 'wiki.edit',
            // Spaces
            'spaces.view', 'spaces.book',
        ],

        'aiutoanimatore' => [
            // Registrations
            'registrations.view',
            // Calendar
            'calendar.view',
            // Attendance
            'attendance.view', 'attendance.checkin',
            // Communications
            'communications.view',
            // Media
            'media.view',
            // Wiki
            'wiki.view',
            // Spaces
            'spaces.view',
        ],
    ];

    $stmt = $pdo->prepare("
        INSERT OR IGNORE INTO role_permissions (role_id, permission_id)
        VALUES (?, ?)
    ");

    foreach ($rolePermissions as $roleName => $permissionNames) {
        if (!isset($roles[$roleName])) continue;

        $roleId = $roles[$roleName];

        foreach ($permissionNames as $permissionName) {
            if (!isset($permissions[$permissionName])) continue;

            $permissionId = $permissions[$permissionName];
            $stmt->execute([$roleId, $permissionId]);
        }
    }
}

function createDefaultAdmin(PDO $pdo, array $config) {
    // Check if admin already exists
    $stmt = $pdo->prepare("SELECT id FROM users WHERE username = ?");
    $stmt->execute([$config['default_admin']['username']]);

    if ($stmt->fetch()) {
        echo "Default admin account already exists.\n";
        return;
    }

    // Create admin user
    $passwordHash = password_hash($config['default_admin']['password'], PASSWORD_BCRYPT, [
        'cost' => $config['security']['bcrypt_cost']
    ]);

    $stmt = $pdo->prepare("
        INSERT INTO users (username, email, password_hash)
        VALUES (?, ?, ?)
    ");
    $stmt->execute([
        $config['default_admin']['username'],
        $config['default_admin']['email'],
        $passwordHash
    ]);

    $adminId = $pdo->lastInsertId();

    // Assign technical admin role
    $stmt = $pdo->prepare("
        INSERT INTO user_roles (user_id, role_id, is_primary)
        SELECT ?, id, 1 FROM roles WHERE name = 'technical_admin'
    ");
    $stmt->execute([$adminId]);

    echo "Default admin account created with ID: $adminId\n";
}
