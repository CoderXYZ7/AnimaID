# 🔗 AnimaID API Endpoints

## Overview

The AnimaID API provides RESTful endpoints for authentication, user management, and system administration. All endpoints return JSON responses and use JWT tokens for authentication (except login/register).

## Authentication Endpoints

### POST /api/auth/login
Authenticate a user and return JWT token.

**Request Body:**
```json
{
  "username": "string",
  "password": "string"
}
```

**Response (Success - 200):**
```json
{
  "success": true,
  "token": "jwt_token_here",
  "user": {
    "id": 1,
    "username": "admin",
    "email": "admin@example.com",
    "roles": [
      {
        "id": 1,
        "name": "technical_admin",
        "display_name": "Technical Admin",
        "is_primary": true
      }
    ]
  },
  "expires_at": "2025-11-02T15:29:49Z"
}
```

**Response (Error - 401):**
```json
{
  "success": false,
  "error": "Invalid credentials"
}
```

### POST /api/auth/logout
Invalidate the current session token.

**Headers:**
```
Authorization: Bearer jwt_token_here
```

**Response (Success - 200):**
```json
{
  "success": true,
  "message": "Logged out successfully"
}
```

### POST /api/auth/refresh
Refresh an expired JWT token using a valid refresh token.

**Request Body:**
```json
{
  "refresh_token": "refresh_token_here"
}
```

**Response (Success - 200):**
```json
{
  "success": true,
  "token": "new_jwt_token",
  "expires_at": "2025-11-02T16:29:49Z"
}
```

### GET /api/auth/me
Get current user information and roles.

**Headers:**
```
Authorization: Bearer jwt_token_here
```

**Response (Success - 200):**
```json
{
  "success": true,
  "user": {
    "id": 1,
    "username": "admin",
    "email": "admin@example.com",
    "roles": [
      {
        "id": 1,
        "name": "technical_admin",
        "display_name": "Technical Admin",
        "is_primary": true
      }
    ],
    "last_login": "2025-11-02T14:29:49Z"
  }
}
```

## User Management Endpoints

### GET /api/users
Get paginated list of users (Admin only).

**Headers:**
```
Authorization: Bearer jwt_token_here
```

**Query Parameters:**
- `page` (integer, default: 1)
- `limit` (integer, default: 20)
- `search` (string, optional)

**Response (Success - 200):**
```json
{
  "success": true,
  "users": [
    {
      "id": 1,
      "username": "admin",
      "email": "admin@example.com",
      "is_active": true,
      "roles": ["technical_admin"],
      "created_at": "2025-11-01T10:00:00Z",
      "last_login": "2025-11-02T14:29:49Z"
    }
  ],
  "pagination": {
    "page": 1,
    "limit": 20,
    "total": 1,
    "pages": 1
  }
}
```

### POST /api/users
Create a new user (Admin only).

**Headers:**
```
Authorization: Bearer jwt_token_here
```

**Request Body:**
```json
{
  "username": "newuser",
  "email": "user@example.com",
  "password": "securepassword123",
  "role_ids": [2, 3]  // Array of role IDs to assign
}
```

**Response (Success - 201):**
```json
{
  "success": true,
  "user": {
    "id": 2,
    "username": "newuser",
    "email": "user@example.com",
    "roles": ["organizzatore"]
  }
}
```

### GET /api/users/{id}
Get specific user details (Admin only).

**Headers:**
```
Authorization: Bearer jwt_token_here
```

**Response (Success - 200):**
```json
{
  "success": true,
  "user": {
    "id": 2,
    "username": "newuser",
    "email": "user@example.com",
    "is_active": true,
    "roles": [
      {
        "id": 2,
        "name": "organizzatore",
        "display_name": "Organizzatore",
        "is_primary": false
      }
    ],
    "created_at": "2025-11-02T10:00:00Z",
    "last_login": null
  }
}
```

### PUT /api/users/{id}
Update user information (Admin only).

**Headers:**
```
Authorization: Bearer jwt_token_here
```

**Request Body:**
```json
{
  "email": "newemail@example.com",
  "is_active": true,
  "role_ids": [2, 4]  // Update roles
}
```

**Response (Success - 200):**
```json
{
  "success": true,
  "user": {
    "id": 2,
    "username": "newuser",
    "email": "newemail@example.com",
    "roles": ["organizzatore", "animatore"]
  }
}
```

### DELETE /api/users/{id}
Deactivate a user (Admin only).

**Headers:**
```
Authorization: Bearer jwt_token_here
```

**Response (Success - 200):**
```json
{
  "success": true,
  "message": "User deactivated successfully"
}
```

## Role Management Endpoints

### GET /api/roles
Get all available roles.

**Headers:**
```
Authorization: Bearer jwt_token_here
```

**Response (Success - 200):**
```json
{
  "success": true,
  "roles": [
    {
      "id": 1,
      "name": "technical_admin",
      "display_name": "Technical Admin",
      "description": "Full system access",
      "is_system_role": true,
      "permissions": ["admin.users", "admin.system", "admin.roles"]
    }
  ]
}
```

### POST /api/roles
Create a new custom role (Admin only).

**Headers:**
```
Authorization: Bearer jwt_token_here
```

**Request Body:**
```json
{
  "name": "custom_role",
  "display_name": "Custom Role",
  "description": "A custom role for specific needs",
  "permission_ids": [1, 2, 3]
}
```

**Response (Success - 201):**
```json
{
  "success": true,
  "role": {
    "id": 6,
    "name": "custom_role",
    "display_name": "Custom Role",
    "permissions": ["registrations.view", "calendar.view"]
  }
}
```

### PUT /api/roles/{id}
Update role permissions (Admin only).

**Headers:**
```
Authorization: Bearer jwt_token_here
```

**Request Body:**
```json
{
  "permission_ids": [1, 2, 4, 5]
}
```

**Response (Success - 200):**
```json
{
  "success": true,
  "role": {
    "id": 6,
    "name": "custom_role",
    "permissions": ["registrations.view", "calendar.view", "attendance.view"]
  }
}
```

## Permission Endpoints

### GET /api/permissions
Get all available permissions grouped by module.

**Headers:**
```
Authorization: Bearer jwt_token_here
```

**Response (Success - 200):**
```json
{
  "success": true,
  "permissions": {
    "registrations": [
      {
        "id": 1,
        "name": "registrations.view",
        "display_name": "View Registrations",
        "description": "Can view child registrations"
      }
    ],
    "calendar": [
      {
        "id": 2,
        "name": "calendar.view",
        "display_name": "View Calendar",
        "description": "Can view calendar events"
      }
    ]
  }
}
```

## System Endpoints

### GET /api/system/status
Get system status and health check.

**Headers:**
```
Authorization: Bearer jwt_token_here
```

**Response (Success - 200):**
```json
{
  "success": true,
  "status": "healthy",
  "version": "0.9",
  "database": "connected",
  "timestamp": "2025-11-02T15:29:49Z"
}
```

### POST /api/system/backup
Trigger system backup (Admin only).

**Headers:**
```
Authorization: Bearer jwt_token_here
```

**Response (Success - 200):**
```json
{
  "success": true,
  "message": "Backup completed successfully",
  "backup_file": "backup_20251102_152949.sql"
}
```

## Error Responses

All endpoints may return the following error responses:

**401 Unauthorized:**
```json
{
  "success": false,
  "error": "Authentication required"
}
```

**403 Forbidden:**
```json
{
  "success": false,
  "error": "Insufficient permissions"
}
```

**404 Not Found:**
```json
{
  "success": false,
  "error": "Resource not found"
}
```

**422 Unprocessable Entity:**
```json
{
  "success": false,
  "error": "Validation failed",
  "details": {
    "username": ["Username is required"],
    "email": ["Invalid email format"]
  }
}
```

**500 Internal Server Error:**
```json
{
  "success": false,
  "error": "Internal server error"
}
```

## Rate Limiting

- **Authenticated requests**: 1000 requests per hour per user
- **Unauthenticated requests**: 100 requests per hour per IP
- Rate limit headers are included in responses:
  - `X-RateLimit-Limit`: Maximum requests per hour
  - `X-RateLimit-Remaining`: Remaining requests
  - `X-RateLimit-Reset`: Time when limit resets (Unix timestamp)

## API Versioning

All endpoints are prefixed with `/api/`. Future versions will use `/api/v2/`, `/api/v3/`, etc.

## Content Types

- **Request**: `application/json`
- **Response**: `application/json`
- **Charset**: UTF-8
