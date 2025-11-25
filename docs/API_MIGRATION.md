# API Migration Guide

## Overview

The AnimaID API is being migrated from a monolithic router (`api/index.php` - 2,751 lines) to a modern Slim Framework-based architecture with proper controllers, services, and middleware.

## New Architecture

### Files Created

- **Controllers:**
  - `src/Controllers/AuthController.php` - Authentication endpoints
  - `src/Controllers/UserController.php` - User management endpoints

- **Middleware:**
  - `src/Middleware/AuthMiddleware.php` - JWT token validation
  - `src/Middleware/PermissionMiddleware.php` - Permission checking

- **New Router:**
  - `api/index-new.php` - Slim-based router with dependency injection

### Migration Status

#### ✅ Migrated Endpoints

**Authentication:**
- `POST /api/auth/login` - Login with username/password
- `POST /api/auth/logout` - Logout and revoke token
- `POST /api/auth/refresh` - Refresh JWT token
- `GET /api/auth/me` - Get current user info

**Users:** (Requires `admin.users` or `users.manage` permission)
- `GET /api/users` - List users with pagination
- `GET /api/users/stats` - Get user statistics
- `GET /api/users/{id}` - Get single user
- `POST /api/users` - Create new user
- `PUT /api/users/{id}` - Update user
- `DELETE /api/users/{id}` - Delete user

#### ⏳ Pending Migration

All other endpoints are still in the old `api/index.php`:
- Roles
- Permissions
- Children
- Animators
- Calendar
- Attendance
- Communications
- Wiki
- Media
- Reports
- System

## Testing the New API

### 1. Rename Files

To test the new API:

```bash
# Backup old API
mv api/index.php api/index-old.php

# Activate new API
mv api/index-new.php api/index.php
```

### 2. Test Authentication

```bash
# Login
curl -X POST http://localhost/api/auth/login \
  -H "Content-Type: application/json" \
  -d '{"username":"admin","password":"Admin123!@#"}'

# Response:
# {
#   "success": true,
#   "token": "eyJ0eXAiOiJKV1QiLCJhbGc...",
#   "expires_at": "2025-11-25T17:24:28Z",
#   "user": { ... }
# }
```

### 3. Test Protected Endpoints

```bash
# Get current user
curl -X GET http://localhost/api/auth/me \
  -H "Authorization: Bearer YOUR_TOKEN_HERE"

# List users
curl -X GET http://localhost/api/users \
  -H "Authorization: Bearer YOUR_TOKEN_HERE"
```

## Key Improvements

### 1. Dependency Injection
- All dependencies injected through constructors
- Easy to test and mock
- Clear dependency graph

### 2. Middleware Stack
- **AuthMiddleware** - Validates JWT tokens
- **PermissionMiddleware** - Checks user permissions
- **CORS Middleware** - Handles cross-origin requests

### 3. Proper Separation of Concerns
- **Controllers** - Handle HTTP requests/responses
- **Services** - Business logic
- **Repositories** - Data access
- **Middleware** - Cross-cutting concerns

### 4. PSR-7 Compliance
- Standard HTTP message interfaces
- Compatible with PSR-15 middleware
- Interoperable with other PSR-7 libraries

### 5. Route Groups
- Logical grouping of related endpoints
- Shared middleware for route groups
- Clear API structure

## Rollback Plan

If issues arise:

```bash
# Restore old API
mv api/index.php api/index-new.php
mv api/index-old.php api/index.php
```

## Next Steps

1. **Test migrated endpoints** thoroughly
2. **Migrate remaining endpoints** one module at a time:
   - RoleController
   - ChildController
   - AnimatorController
   - CalendarController
   - etc.
3. **Add integration tests**
4. **Update frontend** to use new API structure
5. **Remove old API** once fully migrated

## Benefits

- **Maintainability:** Clear structure, easy to find code
- **Testability:** Dependency injection makes testing easy
- **Scalability:** Easy to add new endpoints
- **Security:** Middleware-based auth and permissions
- **Standards:** PSR-7, PSR-15 compliance
- **Performance:** Slim is lightweight and fast

---

**Status:** Phase 5 Complete - Core infrastructure ready
**Next:** Migrate remaining endpoints and add tests
