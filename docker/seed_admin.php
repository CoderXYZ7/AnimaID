<?php
/**
 * Admin seeder — run by entrypoint.sh via:
 *   php /tmp/seed_admin.php
 * Reads ADMIN_USERNAME / ADMIN_EMAIL / ADMIN_PASSWORD from env.
 */

$db = new PDO('sqlite:database/animaid.db');
$db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

// Check whether any user with the 'admin' role already exists
$stmt = $db->query("
    SELECT COUNT(*) FROM users u
    JOIN user_roles ur ON u.id = ur.user_id
    JOIN roles r       ON ur.role_id = r.id
    WHERE r.name = 'admin'
");
if ((int) $stmt->fetchColumn() > 0) {
    echo "exists\n";
    exit(0);
}

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

// Find or create the admin role
$roleRow = $db->query("SELECT id FROM roles WHERE name = 'admin' LIMIT 1")->fetch(PDO::FETCH_ASSOC);
if ($roleRow) {
    $roleId = (int) $roleRow['id'];
} else {
    $db->exec("INSERT INTO roles (name, display_name, is_system_role, created_at)
               VALUES ('admin', 'Administrator', 1, datetime('now'))");
    $roleId = (int) $db->lastInsertId();
}

$db->prepare("INSERT INTO user_roles (user_id, role_id) VALUES (?, ?)")
   ->execute([$userId, $roleId]);

echo "created\n";
