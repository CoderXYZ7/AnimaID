#!/bin/bash
set -euo pipefail

echo "[AnimaID] Starting up..."

# ── Environment file ──────────────────────────────────────────────────────────
if [ ! -f /var/www/html/.env ]; then
    echo "[AnimaID] Creating .env from template..."
    cp /var/www/html/.env.example /var/www/html/.env
fi

# Write env vars from Docker environment into the .env file.
# Uses | as sed delimiter so values with / are safe.
set_env() {
    local key="$1" val="$2"
    [ -z "$val" ] && return 0
    if grep -q "^${key}=" /var/www/html/.env; then
        sed -i "s|^${key}=.*|${key}=${val}|" /var/www/html/.env
    else
        echo "${key}=${val}" >> /var/www/html/.env
    fi
}

set_env "APP_ENV"        "${APP_ENV:-development}"
set_env "APP_DEBUG"      "${APP_DEBUG:-false}"
set_env "CORS_ORIGINS"   "${CORS_ORIGINS:-http://localhost:8080}"
set_env "ADMIN_USERNAME" "${ADMIN_USERNAME:-admin}"
set_env "ADMIN_EMAIL"    "${ADMIN_EMAIL:-admin@animaid.local}"
set_env "ADMIN_PASSWORD" "${ADMIN_PASSWORD:-}"

# JWT_SECRET: write if provided, otherwise generate a stable random one
if [ -n "${JWT_SECRET:-}" ]; then
    set_env "JWT_SECRET" "$JWT_SECRET"
elif ! grep -qE "^JWT_SECRET=.+" /var/www/html/.env; then
    GENERATED=$(openssl rand -base64 48 | tr -d '\n')
    set_env "JWT_SECRET" "$GENERATED"
    echo "[AnimaID] Generated ephemeral JWT_SECRET (set JWT_SECRET env var for persistence)"
fi

# ── config.php ────────────────────────────────────────────────────────────────
if [ ! -f /var/www/html/config/config.php ]; then
    cp /var/www/html/config/configDefault.php /var/www/html/config/config.php
fi

# ── Pass key env vars to Apache so PHP can read them via getenv() ─────────────
# (phpdotenv reads .env, but Apache also needs to export them for CLI subprocs)
{
    echo "export JWT_SECRET=\"$(grep '^JWT_SECRET=' /var/www/html/.env | cut -d= -f2-)\""
    echo "export APP_ENV=\"${APP_ENV:-development}\""
    echo "export APP_DEBUG=\"${APP_DEBUG:-false}\""
} >> /etc/apache2/envvars

# ── Database migrations ───────────────────────────────────────────────────────
echo "[AnimaID] Running migrations..."
cd /var/www/html
php database/migrate.php migrate

# ── Admin seeding ─────────────────────────────────────────────────────────────
if [ -n "${ADMIN_PASSWORD:-}" ]; then
    cp /docker/seed_admin.php /tmp/seed_admin.php
    RESULT=$(ADMIN_USERNAME="${ADMIN_USERNAME:-admin}" \
             ADMIN_EMAIL="${ADMIN_EMAIL:-}" \
             ADMIN_PASSWORD="${ADMIN_PASSWORD}" \
             php /tmp/seed_admin.php 2>&1)
    case "$RESULT" in
        created*)  echo "[AnimaID] Admin user '${ADMIN_USERNAME:-admin}' created" ;;
        exists*)   echo "[AnimaID] Admin user already exists — skipping seed" ;;
        skipped*)  echo "[AnimaID] $RESULT" ;;
        *)         echo "[AnimaID] Seeder: $RESULT" ;;
    esac
    rm -f /tmp/seed_admin.php
else
    echo "[AnimaID] ADMIN_PASSWORD not set — skipping admin seed"
fi

# ── Permissions ───────────────────────────────────────────────────────────────
chown -R www-data:www-data /var/www/html/database /var/www/html/uploads 2>/dev/null || true
chmod -R 775 /var/www/html/database /var/www/html/uploads 2>/dev/null || true

echo "[AnimaID] Ready — starting Apache"
exec apache2-foreground
