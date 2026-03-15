<?php
/**
 * Admin seeder — run by entrypoint.sh and deploy.sh
 * Reads ADMIN_USERNAME / ADMIN_EMAIL / ADMIN_PASSWORD from env.
 */

$db = new PDO('sqlite:database/animaid.db');
$db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

// ── All permissions: [name, display_name, module, description] ────────────────
$allPermissions = [
    ['admin.users',                  'Manage Users (Admin)',        'admin',          'Full user administration'],
    ['admin.system.view',            'View System Status',          'admin',          'View system health and status'],
    ['admin.system.manage',          'Manage System Settings',      'admin',          'Modify system configuration'],
    ['users.manage',                 'Manage Users',                'users',          'Create, edit and delete users'],
    ['users.view',                   'View Users',                  'users',          'View user list and profiles'],
    ['calendar.view',                'View Calendar',               'calendar',       'View events and calendar'],
    ['calendar.create',              'Create Events',               'calendar',       'Create new calendar events'],
    ['calendar.edit',                'Edit Events',                 'calendar',       'Edit existing calendar events'],
    ['calendar.delete',              'Delete Events',               'calendar',       'Delete calendar events'],
    ['calendar.participants.view',   'View Participants',           'calendar',       'View event participants'],
    ['calendar.participants.manage', 'Manage Participants',         'calendar',       'Add and remove event participants'],
    ['children.view',                'View Children',               'children',       'View children profiles'],
    ['children.manage',              'Manage Children',             'children',       'Create, edit and delete children'],
    ['animators.view',               'View Animators',              'animators',      'View animator profiles'],
    ['animators.manage',             'Manage Animators',            'animators',      'Create, edit and delete animators'],
    ['attendance.view',              'View Attendance',             'attendance',     'View attendance records'],
    ['attendance.checkin',           'Check In Participants',       'attendance',     'Perform check-in and check-out'],
    ['attendance.edit',              'Edit Attendance',             'attendance',     'Edit attendance records'],
    ['communications.view',          'View Communications',         'communications', 'View announcements and messages'],
    ['communications.send',          'Send Communications',         'communications', 'Create new communications'],
    ['communications.manage',        'Manage Communications',       'communications', 'Edit and delete communications'],
    ['media.view',                   'View Media',                  'media',          'View media library'],
    ['media.upload',                 'Upload Media',                'media',          'Upload files to media library'],
    ['media.manage',                 'Manage Media',                'media',          'Delete and organise media'],
    ['wiki.view',                    'View Wiki',                   'wiki',           'View wiki pages'],
    ['wiki.create',                  'Create Wiki Pages',           'wiki',           'Create new wiki pages'],
    ['wiki.edit',                    'Edit Wiki Pages',             'wiki',           'Edit existing wiki pages'],
    ['wiki.moderate',                'Moderate Wiki',               'wiki',           'Delete and lock wiki pages'],
    ['spaces.view',                  'View Spaces',                 'spaces',         'View spaces and bookings'],
    ['spaces.book',                  'Book Spaces',                 'spaces',         'Create space bookings'],
    ['spaces.manage',                'Manage Spaces',               'spaces',         'Create, edit and delete spaces'],
    ['reports.view',                 'View Reports',                'reports',        'View reports and analytics'],
];

// ── Seed permissions ──────────────────────────────────────────────────────────
$insertPerm = $db->prepare(
    "INSERT OR IGNORE INTO permissions (name, display_name, module, description, created_at)
     VALUES (?, ?, ?, ?, datetime('now'))"
);
foreach ($allPermissions as [$name, $displayName, $module, $description]) {
    $insertPerm->execute([$name, $displayName, $module, $description]);
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
