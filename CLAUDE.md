# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Project Overview

AnimaID is a PHP 8.1+ web management platform for animation centers and youth organizations. It manages children, animators, events, attendance, communications, wiki, and media through a REST API consumed by a Vanilla JS frontend.

## Commands

```bash
# Development server
php -S localhost:8000

# Testing
composer test              # All tests
composer test:unit         # Unit tests only
composer test:integration  # Integration tests only

# Code style
composer cs:check          # Check PSR-12 compliance
composer cs:fix            # Auto-fix PSR-12 issues

# Database migrations
php database/migrate.php migrate    # Run pending migrations
php database/migrate.php            # Check migration status
php database/migrate.php rollback   # Rollback last migration
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
- `src/Controllers/` — thin HTTP layer: AuthController, UserController, CalendarController, WikiController, SpaceController, ChildController, AnimatorController, AttendanceController, CommunicationController, MediaController, ReportController, SystemController
- `src/Services/` — business logic: AuthService, UserService, CalendarService, WikiService, SpaceService, ChildService, AnimatorService, AttendanceService, CommunicationService, MediaService, ReportService, **EmailService**, **AuditService**
- `src/Repositories/` — data access via BaseRepository pattern
- `src/Middleware/` — CorsMiddleware, AuthMiddleware, PermissionMiddleware, RateLimitMiddleware, **AuditMiddleware**
- `src/Exceptions/` — typed exception classes (NotFoundException, ValidationException, ForbiddenException, ConflictException, UnauthorizedException) mapped to HTTP status codes in the error handler

### Remaining Legacy Code

- `src/Auth.php` — legacy god class, still referenced by `scripts/check_permissions.php`. Do not delete until that script is updated or removed.
- `api/index-legacy.php` — **deleted**
- `database/init.php` — **deleted**; all schema setup is migration-based
- `src/JWT.php` — **deleted**; all JWT handling now uses `src/Security/JwtManager.php` (firebase/php-jwt)

### Database

SQLite3 with FTS5. File at `database/animaid.db`. Migrations in `database/migrations/`.

### Frontend

Vanilla JS in `public/js/`. Page-specific logic in `public/js/pages/`. `apiService.js` handles all REST calls. Tailwind CSS for styling.

### Authentication

JWT-based with token blacklisting for logout. `firebase/php-jwt` used throughout. Roles: Admin, Coordinator, Animator, Parent.

## Key Docs

- `docs/PROJECT_ANALYSIS.md` — comprehensive code audit with known issues
- `docs/API_MIGRATION.md` — Slim 4 migration guide
- `docs/APIEndpoints.md` — REST endpoint reference
- `TODO.md` — roadmap and outstanding work
