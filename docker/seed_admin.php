<?php
/**
 * Admin seeder — run by entrypoint.sh via:
 *   php /tmp/seed_admin.php
 * Reads ADMIN_USERNAME / ADMIN_EMAIL / ADMIN_PASSWORD from env.
 */

$db = new PDO('sqlite:database/animaid.db');
$db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

// ── All permissions used in the application ───────────────────────────────────
$allPermissions = [
    // Users
    'admin.users'                  => 'Manage users (admin)',
    'users.manage'                 => 'Manage users',
    'users.view'                   => 'View users',
    // Calendar
    'calendar.view'                => 'View calendar events',
    'calendar.create'              => 'Create calendar events',
    'calendar.edit'                => 'Edit calendar events',
    'calendar.delete'              => 'Delete calendar events',
    'calendar.participants.view'   => 'View event participants',
    'calendar.participants.manage' => 'Manage event participants',
    // Children
    'children.view'                => 'View children',
    'children.manage'              => 'Manage children',
    // Animators
    'animators.view'               => 'View animators',
    'animators.manage'             => 'Manage animators',
    // Attendance
    'attendance.view'              => 'View attendance',
    'attendance.checkin'           => 'Check in participants',
    'attendance.edit'              => 'Edit attendance records',
    // Communications
    'communications.view'          => 'View communications',
    'communications.send'          => 'Send communications',
    'communications.manage'        => 'Manage communications',
    // Media
    'media.view'                   => 'View media',
    'media.upload'                 => 'Upload media',
    'media.manage'                 => 'Manage media',
    // Wiki
    'wiki.view'                    => 'View wiki',
    'wiki.create'                  => 'Create wiki pages',
    'wiki.edit'                    => 'Edit wiki pages',
    'wiki.moderate'                => 'Moderate wiki',
    // Spaces
    'spaces.view'                  => 'View spaces',
    'spaces.book'                  => 'Book spaces',
    'spaces.manage'                => 'Manage spaces',
    // Reports
    'reports.view'                 => 'View reports',
    // System
    'admin.system.view'            => 'View system status',
    'admin.system.manage'          => 'Manage system settings',
];

// ── Seed permissions ──────────────────────────────────────────────────────────
$insertPerm = $db->prepare(
    "INSERT OR IGNORE INTO permissions (name, description, created_at)
     VALUES (?, ?, datetime('now'))"
);
foreach ($allPermissions as $name => $description) {
    $insertPerm->execute([$name, $description]);
}

// ── Find or create admin role ─────────────────────────────────────────────────
$roleRow = $db->query("SELECT id FROM roles WHERE name = 'admin' LIMIT 1")->fetch(PDO::FETCH_ASSOC);
if ($roleRow) {
    $roleId = (int) $roleRow['id'];
} else {
    $db->exec("INSERT INTO roles (name, display_name, is_system_role, created_at)
               VALUES ('admin', 'Administrator', 1, datetime('now'))");
    $roleId = (int) $db->lastInsertId();
}

// ── Assign ALL permissions to the admin role ──────────────────────────────────
$permIds = $db->query("SELECT id FROM permissions")->fetchAll(PDO::FETCH_COLUMN);
$assignPerm = $db->prepare(
    "INSERT OR IGNORE INTO role_permissions (role_id, permission_id) VALUES (?, ?)"
);
foreach ($permIds as $permId) {
    $assignPerm->execute([$roleId, $permId]);
}

// ── Check whether an admin user already exists ────────────────────────────────
$stmt = $db->query("
    SELECT COUNT(*) FROM users u
    JOIN user_roles ur ON u.id = ur.user_id
    WHERE ur.role_id = {$roleId}
");
if ((int) $stmt->fetchColumn() > 0) {
    echo "exists\n";
    exit(0);
}

// ── Create admin user ─────────────────────────────────────────────────────────
$username = getenv('ADMIN_USERNAME') ?: 'admin';
$email    = getenv('ADMIN_EMAIL')    ?: ($username . '@animaid.local');
$password = getenv('ADMIN_PASSWORD');

if (!$password) {
    echo "skipped (no password)\n";
    exit(0);
}

$hash = password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]);

$stmt = $db->prepare(
    "INSERT INTO users (username, email, password_hash, is_active, created_at)
     VALUES (?, ?, ?, 1, datetime('now'))"
);
$stmt->execute([$username, $email, $hash]);
$userId = (int) $db->lastInsertId();

$db->prepare("INSERT INTO user_roles (user_id, role_id) VALUES (?, ?)")
   ->execute([$userId, $roleId]);

echo "created\n";
