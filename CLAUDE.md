# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Project Overview

AnimaID is a PHP 8.1+ web management platform for animation centers and youth organizations. It manages children, animators, events, attendance, communications, wiki, and media through a REST API consumed by a Vanilla JS frontend.

## Setup

```bash
cp .env.example .env
# Set JWT_SECRET: openssl rand -base64 64
# cp config/configDefault.php config/config.php  (only needed if env-based config doesn't cover your case)
composer install
php database/migrate.php migrate
```

## Commands

```bash
# Development server (serves both frontend and API)
php -S localhost:8000

# Testing
composer test                                           # All tests
composer test:unit                                      # Unit tests only
composer test:integration                               # Integration tests only
./vendor/bin/phpunit --filter testMethodName            # Single test method
./vendor/bin/phpunit tests/Unit/Services/AuthServiceTest.php  # Single test file

# Code style
composer cs:check          # Check PSR-12 compliance
composer cs:fix            # Auto-fix PSR-12 issues

# Database migrations
php database/migrate.php migrate    # Run pending migrations
php database/migrate.php            # Check migration status
php database/migrate.php rollback   # Rollback last migration

# Tailwind CSS (run from config/ directory)
cd config && npm run build-css       # Watch mode (development)
cd config && npm run build-css-prod  # Minified build (production)
```

## Architecture

The migration from the legacy monolith to Slim 4 is **complete**. All requests are handled by the Slim 4 application.

### Request Flow

```
HTTP Request
    ↓
api/index.php  (Slim 4 entry point)
    ↓
Middleware stack (outermost → innermost):
  CorsMiddleware       — sets CORS headers, short-circuits OPTIONS preflight
  AuditMiddleware      — logs mutating requests (POST/PUT/DELETE) after response
  RateLimitMiddleware  — enforces per-IP request limits
  AuthMiddleware       — validates JWT, injects user attribute
  PermissionMiddleware — route-level permission checks (applied per route/group)
    ↓
src/Controllers/*  →  src/Services/*  →  src/Repositories/*  →  database/animaid.db
```

### Modern Architecture (Slim 4)

- `api/index.php` — Slim 4 entry point; registers all middleware, services, and routes
- `src/Controllers/` — thin HTTP layer: AuthController, UserController, CalendarController, WikiController, SpaceController, ChildController, AnimatorController, AttendanceController, CommunicationController, MediaController, ReportController, SystemController, PermissionController, RoleController
- `src/Services/` — business logic: AuthService, UserService, CalendarService, WikiService, SpaceService, ChildService, AnimatorService, AttendanceService, CommunicationService, MediaService, ReportService, PermissionService, RoleService, **EmailService**, **AuditService**
- `src/Repositories/` — data access via BaseRepository pattern; all repositories extend BaseRepository (PDO-based CRUD)
- `src/Middleware/` — CorsMiddleware, AuthMiddleware, PermissionMiddleware, RateLimitMiddleware, **AuditMiddleware**
- `src/Exceptions/` — typed exception classes (NotFoundException, ValidationException, ForbiddenException, ConflictException, UnauthorizedException) mapped to HTTP status codes in the error handler
- `src/Config/ConfigManager.php` — singleton; loads `.env` via phpdotenv then falls back to server env vars

### Remaining Legacy Code

- `src/Auth.php` — legacy god class, still referenced by `scripts/check_permissions.php`. Do not delete until that script is updated or removed.
- `api/index-legacy.php` — **deleted**
- `database/init.php` — **deleted**; all schema setup is migration-based
- `src/JWT.php` — **deleted**; all JWT handling now uses `src/Security/JwtManager.php` (firebase/php-jwt)

### Database

SQLite3 with FTS5. File at `database/animaid.db`. Migrations in `database/migrations/`.

### Frontend

Vanilla JS in `public/js/`. Page-specific logic in `public/js/pages/`. `apiService.js` handles all REST calls. Tailwind CSS for styling; source at `src/css/input.css`, compiled to `src/css/output.css` (build from `config/` with npm). Internationalization via i18next (`src/js/i18n.js`); templates use `data-i18n` attributes.

### Authentication

JWT-based with token blacklisting for logout. `firebase/php-jwt` used throughout. Roles: Admin, Coordinator, Animator, Parent.

### Permissions

Permissions follow the pattern `module.action` (e.g., `registrations.view`, `admin.users.edit`). Full list in `docs/permissions_list.md`. `PermissionMiddleware` is applied per route/group using `PermissionMiddleware::any()` or `PermissionMiddleware::all()` for OR vs AND semantics.

### Tests

Unit tests in `tests/Unit/Services/` use PHPUnit mocks for all dependencies (no real database). Integration tests in `tests/Integration/` are pending — no integration test files exist yet. Run `./vendor/bin/phpunit --filter testName` to target a single test.

## Key Docs

- `docs/PROJECT_ANALYSIS.md` — comprehensive code audit with known issues
- `docs/API_MIGRATION.md` — Slim 4 migration guide
- `docs/APIEndpoints.md` — REST endpoint reference
- `TODO.md` — roadmap and outstanding work
