#!/bin/bash
# AnimaID Deployment Script
# Usage: sudo bash scripts/deploy.sh [--skip-pull] [--skip-tests] [--no-backup]

set -euo pipefail

# ── Colours ──────────────────────────────────────────────────────────────────
RED='\033[0;31m'; GREEN='\033[0;32m'; YELLOW='\033[1;33m'
BLUE='\033[0;34m'; BOLD='\033[1m'; NC='\033[0m'

info()  { echo -e "${BLUE}[INFO]${NC}  $*"; }
ok()    { echo -e "${GREEN}[OK]${NC}    $*"; }
warn()  { echo -e "${YELLOW}[WARN]${NC}  $*"; }
error() { echo -e "${RED}[ERROR]${NC} $*" >&2; }
die()   { error "$*"; exit 1; }
step()  { echo; echo -e "${BOLD}── $* ──────────────────────────────────────${NC}"; }

# ── Flags ────────────────────────────────────────────────────────────────────
SKIP_PULL=false
SKIP_TESTS=false
NO_BACKUP=false

for arg in "$@"; do
    case $arg in
        --skip-pull)   SKIP_PULL=true ;;
        --skip-tests)  SKIP_TESTS=true ;;
        --no-backup)   NO_BACKUP=true ;;
        --help)
            echo "Usage: sudo bash scripts/deploy.sh [--skip-pull] [--skip-tests] [--no-backup]"
            exit 0 ;;
        *) die "Unknown argument: $arg" ;;
    esac
done

# ── Preflight ─────────────────────────────────────────────────────────────────
echo -e "${BOLD}AnimaID Deployment${NC}"
echo "────────────────────────────────────────────"

[ "$EUID" -ne 0 ] && die "Run with sudo: sudo bash scripts/deploy.sh"

ACTUAL_USER=${SUDO_USER:-$USER}
PROJECT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
cd "$PROJECT_DIR"

info "Project: $PROJECT_DIR"
info "User:    $ACTUAL_USER"
info "Branch:  $(git rev-parse --abbrev-ref HEAD)"
info "Commit:  $(git rev-parse --short HEAD)"

# ── Dependency checks ─────────────────────────────────────────────────────────
step "Checking dependencies"

MISSING=0

check_cmd() {
    if command -v "$1" &>/dev/null; then
        ok "$1 found"
    else
        error "$1 not found"
        MISSING=1
    fi
}

check_cmd php
check_cmd composer
check_cmd git

PHP_MAJOR=$(php -r "echo PHP_MAJOR_VERSION;" 2>/dev/null || echo 0)
PHP_MINOR=$(php -r "echo PHP_MINOR_VERSION;" 2>/dev/null || echo 0)
if [[ "$PHP_MAJOR" -lt 8 ]] || [[ "$PHP_MAJOR" -eq 8 && "$PHP_MINOR" -lt 1 ]]; then
    error "PHP 8.1+ required (found $(php -r 'echo PHP_VERSION;'))"
    MISSING=1
fi

for ext in pdo sqlite3 mbstring openssl json; do
    php -m 2>/dev/null | grep -qi "^${ext}$" \
        && ok "php-$ext" \
        || { error "php-$ext missing"; MISSING=1; }
done

[ "$MISSING" -eq 1 ] && die "Fix missing dependencies and re-run."

# ── Environment file ──────────────────────────────────────────────────────────
step "Environment"

if [ ! -f .env ]; then
    warn ".env not found — copying from .env.example"
    cp .env.example .env
    JWT=$(openssl rand -base64 48)
    sed -i "s|^JWT_SECRET=.*|JWT_SECRET=${JWT}|" .env
    ok "Generated JWT_SECRET"
    warn "Review .env and set ADMIN_PASSWORD, CORS_ORIGINS, etc. before continuing."
    read -rp "Press Enter after editing .env, or Ctrl-C to abort..."
fi

# Warn about unset critical vars
for var in JWT_SECRET APP_ENV ADMIN_USERNAME ADMIN_PASSWORD CORS_ORIGINS; do
    grep -qE "^${var}=.+" .env || warn "$var is not set in .env"
done

APP_ENV=$(grep -oP "(?<=^APP_ENV=).+" .env 2>/dev/null || echo "production")
info "APP_ENV=$APP_ENV"

if [ ! -f config/config.php ]; then
    cp config/configDefault.php config/config.php
    ok "Created config/config.php from default"
fi

# ── Backup ────────────────────────────────────────────────────────────────────
step "Backup"

if [ "$NO_BACKUP" = true ]; then
    warn "Backup skipped (--no-backup)"
elif [ -f database/animaid.db ]; then
    BACKUP_DIR="database/backups"
    mkdir -p "$BACKUP_DIR"
    BACKUP_FILE="$BACKUP_DIR/animaid_$(date +%Y%m%d_%H%M%S).db"
    cp database/animaid.db "$BACKUP_FILE"
    ok "Database backed up → $BACKUP_FILE"

    # Keep only the last 10 backups
    ls -t "$BACKUP_DIR"/*.db 2>/dev/null | tail -n +11 | xargs -r rm --
    info "Retained last 10 backups"
else
    info "No database yet — skipping backup"
fi

# ── Git pull ──────────────────────────────────────────────────────────────────
step "Code update"

if [ "$SKIP_PULL" = true ]; then
    warn "Git pull skipped (--skip-pull)"
else
    BRANCH=$(git rev-parse --abbrev-ref HEAD)
    sudo -u "$ACTUAL_USER" git pull origin "$BRANCH"
    ok "Pulled latest from $BRANCH ($(git rev-parse --short HEAD))"
fi

# ── Composer install ──────────────────────────────────────────────────────────
step "Dependencies"

COMPOSER_FLAGS="--no-interaction --no-ansi --optimize-autoloader"
if [ "$APP_ENV" = "production" ]; then
    COMPOSER_FLAGS="$COMPOSER_FLAGS --no-dev"
    info "Installing production dependencies only"
else
    info "Installing all dependencies (dev environment)"
fi

# shellcheck disable=SC2086
sudo -u "$ACTUAL_USER" composer install $COMPOSER_FLAGS
ok "Composer dependencies installed"

# ── Database migrations ───────────────────────────────────────────────────────
step "Database migrations"

sudo -u "$ACTUAL_USER" php database/migrate.php migrate
ok "Migrations complete"

# ── Admin + permissions seeding ───────────────────────────────────────────────
step "Admin user & permissions"

ADMIN_USER=$(grep -oP "(?<=^ADMIN_USERNAME=).+" .env 2>/dev/null || echo "")
ADMIN_PASS=$(grep -oP "(?<=^ADMIN_PASSWORD=).+" .env 2>/dev/null || echo "")
ADMIN_EMAIL=$(grep -oP "(?<=^ADMIN_EMAIL=).+" .env 2>/dev/null || echo "")

if [ -n "$ADMIN_USER" ] && [ -n "$ADMIN_PASS" ]; then
    RESULT=$(ADMIN_USERNAME="$ADMIN_USER" \
             ADMIN_EMAIL="$ADMIN_EMAIL" \
             ADMIN_PASSWORD="$ADMIN_PASS" \
             sudo -u "$ACTUAL_USER" php docker/seed_admin.php 2>&1)
    case "$RESULT" in
        created*) ok "Admin user '${ADMIN_USER}' seeded with all permissions" ;;
        exists*)  info "Admin user already exists — permissions refreshed" ;;
        skipped*) warn "$RESULT" ;;
        *)        warn "Seeder: $RESULT" ;;
    esac
else
    warn "ADMIN_USERNAME or ADMIN_PASSWORD not set in .env — skipping admin seed"
fi

# ── Permissions ───────────────────────────────────────────────────────────────
step "File permissions"

bash scripts/maintenance/fix-permissions.sh
ok "Permissions set"

# ── Tests (non-production only) ───────────────────────────────────────────────
if [ "$SKIP_TESTS" = false ] && [ "$APP_ENV" != "production" ]; then
    step "Tests"
    if sudo -u "$ACTUAL_USER" composer test 2>&1; then
        ok "All tests passed"
    else
        warn "Some tests failed — review output above"
        read -rp "Continue deploy anyway? [y/N] " CONTINUE
        [[ "$CONTINUE" =~ ^[Yy]$ ]] || die "Deploy aborted."
    fi
elif [ "$SKIP_TESTS" = true ]; then
    warn "Tests skipped (--skip-tests)"
else
    info "Tests skipped in production (run manually with: composer test)"
fi

# ── Web server reload ─────────────────────────────────────────────────────────
step "Web server"

if systemctl is-active --quiet apache2 2>/dev/null; then
    systemctl reload apache2
    ok "Apache reloaded"
elif systemctl is-active --quiet nginx 2>/dev/null; then
    systemctl reload nginx
    ok "Nginx reloaded"
else
    warn "No running web server detected (Apache/Nginx) — skipping reload"
fi

# ── Summary ───────────────────────────────────────────────────────────────────
echo
echo -e "${BOLD}${GREEN}────────────────────────────────────────────"
echo -e " Deployment complete"
echo -e "────────────────────────────────────────────${NC}"
echo
info "Commit:  $(git rev-parse --short HEAD) — $(git log -1 --pretty=%s)"
BACKUP_COUNT=$(ls database/backups/*.db 2>/dev/null | wc -l || echo 0)
info "Backups: $BACKUP_COUNT file(s) in database/backups/"
if [ -n "${ADMIN_USER:-}" ] && [ "${ADMIN_EXISTS:-1}" = "0" ]; then
    ok "Admin login: ${ADMIN_USER} (password from ADMIN_PASSWORD in .env)"
fi
echo
