#!/bin/bash
set -euo pipefail

echo "[AnimaID] Starting up..."

# ── Environment file ──────────────────────────────────────────────────────────
if [ ! -f /var/www/html/.env ]; then
    echo "[AnimaID] Creating .env from template..."
    cp /var/www/html/.env.example /var/www/html/.env
fi

# Write env vars from Docker environment into the .env file
set_env() {
    local key="$1" val="$2"
    if [ -n "$val" ]; then
        if grep -q "^${key}=" /var/www/html/.env; then
            sed -i "s|^${key}=.*|${key}=${val}|" /var/www/html/.env
        else
            echo "${key}=${val}" >> /var/www/html/.env
        fi
    fi
}

set_env "APP_ENV"        "${APP_ENV:-development}"
set_env "APP_DEBUG"      "${APP_DEBUG:-false}"
set_env "JWT_SECRET"     "${JWT_SECRET:-}"
set_env "CORS_ORIGINS"   "${CORS_ORIGINS:-http://localhost:8080}"
set_env "ADMIN_USERNAME" "${ADMIN_USERNAME:-admin}"
set_env "ADMIN_EMAIL"    "${ADMIN_EMAIL:-admin@animaid.local}"
set_env "ADMIN_PASSWORD" "${ADMIN_PASSWORD:-}"

# Generate JWT secret if not provided
if ! grep -qE "^JWT_SECRET=.+" /var/www/html/.env; then
    JWT=$(openssl rand -base64 48)
    set_env "JWT_SECRET" "$JWT"
    echo "[AnimaID] Generated JWT_SECRET (ephemeral — mount a volume or set it explicitly for persistence)"
fi

# ── Ensure config.php exists ──────────────────────────────────────────────────
if [ ! -f /var/www/html/config/config.php ]; then
    cp /var/www/html/config/configDefault.php /var/www/html/config/config.php
fi

# ── Database migrations ───────────────────────────────────────────────────────
echo "[AnimaID] Running migrations..."
cd /var/www/html
php database/migrate.php migrate

# ── Admin seeding ─────────────────────────────────────────────────────────────
ADMIN_USER="${ADMIN_USERNAME:-admin}"
ADMIN_PASS="${ADMIN_PASSWORD:-}"

if [ -n "$ADMIN_PASS" ]; then
    ADMIN_EXISTS=$(php -r "
        \$db = new PDO('sqlite:database/animaid.db');
        \$stmt = \$db->query('SELECT COUNT(*) FROM users WHERE is_admin = 1 LIMIT 1');
        echo \$stmt->fetchColumn();
    " 2>/dev/null || echo "0")

    if [ "$ADMIN_EXISTS" = "0" ]; then
        ADMIN_EMAIL="${ADMIN_EMAIL:-${ADMIN_USER}@animaid.local}"
        HASH=$(php -r "echo password_hash('${ADMIN_PASS}', PASSWORD_BCRYPT, ['cost' => 12]);")
        php -r "
            \$db = new PDO('sqlite:database/animaid.db');
            \$db->exec(\"INSERT INTO users (username, email, password_hash, is_active, is_admin, created_at)
                VALUES ('${ADMIN_USER}', '${ADMIN_EMAIL}', '${HASH}', 1, 1, datetime('now'))\");
        "
        echo "[AnimaID] Admin user '${ADMIN_USER}' created"
    else
        echo "[AnimaID] Admin user already exists — skipping seed"
    fi
else
    echo "[AnimaID] ADMIN_PASSWORD not set — skipping admin seed"
fi

# ── Permissions ───────────────────────────────────────────────────────────────
chown -R www-data:www-data /var/www/html/database /var/www/html/uploads 2>/dev/null || true
chmod -R 775 /var/www/html/database /var/www/html/uploads 2>/dev/null || true

echo "[AnimaID] Ready — starting Apache"
exec apache2-foreground
