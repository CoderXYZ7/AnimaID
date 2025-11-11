# 🚀 Running AnimaID

## Prerequisites

- **PHP 8.1+** with PDO SQLite extension
- **Web server** (Apache, Nginx, or PHP built-in server)
- **Modern web browser** with JavaScript enabled

## Quick Start

### 1. Initialize the Database

Run the database initialization script to create the database and default admin account:

```bash
php database/init.php
```

This will:
- Create the SQLite database file at `database/animaid.db`
- Create all necessary tables
- Insert default roles and permissions
- Create a default admin account

**Default Admin Credentials:**
- Username: `admin`
- Password: `Admin123!@#`
- Email: `admin@animaid.local`

⚠️ **Important:** Change the default password immediately after first login!

### 2. Start the Web Server

#### Option A: PHP Built-in Server (Development)

```bash
php -S localhost:8000 index.php
```

Then open http://localhost:8000 in your browser.

#### Option B: Apache/Nginx

Configure your web server to serve files from the AnimaID directory with `index.php` as the directory index.

### 3. Access the Application

1. **Landing Page**: http://localhost:8000/ or http://localhost:8000/index.html
2. **Login Page**: http://localhost:8000/login.html
3. **Dashboard**: http://localhost:8000/dashboard.html (requires login)

## API Endpoints

The API is available at `/api/` and includes:

### Authentication
- `POST /api/auth/login` - User login
- `POST /api/auth/logout` - User logout
- `GET /api/auth/me` - Get current user info

### User Management (Admin only)
- `GET /api/users` - List users
- `POST /api/users` - Create user
- `GET /api/users/{id}` - Get user details
- `PUT /api/users/{id}` - Update user
- `DELETE /api/users/{id}` - Deactivate user

### System
- `GET /api/system/status` - System health check

## Testing the System

### Run the PHP Test Suite

```bash
php test_auth.php
```

This will test:
- Admin login and token generation
- Permission checking
- User creation
- Session management

### Manual Testing

1. Open the login page in your browser
2. Login with admin credentials
3. Verify dashboard loads with user information
4. Test logout functionality
5. Try accessing dashboard without login (should redirect to login)

## Configuration

### Main Configuration File

Edit `config.php` to customize:

- Database settings
- JWT secrets and expiration
- Default admin account
- Security policies
- API settings

### Important Security Notes

1. **Change JWT Secret**: Update the `jwt.secret` in `config.php` for production
2. **Change Admin Password**: Never use default credentials in production
3. **HTTPS**: Enable HTTPS in production
4. **Environment**: Set `environment` to `'production'` when deploying

## File Structure

```
animaid/
├── api/                    # API endpoints
│   └── index.php          # Main API router
├── database/              # Database files and scripts
│   ├── init.php          # Database initialization
│   └── animaid.db        # SQLite database (created)
├── src/                   # PHP source code
│   ├── Auth.php          # Authentication class
│   ├── Database.php      # Database connection
│   └── JWT.php           # JWT implementation
├── index.php              # Main entry point
├── login.html             # Login page
├── dashboard.html         # User dashboard
├── index.html             # Landing page
├── config.php             # Configuration
├── test_auth.php          # Test script
└── *.md                   # Documentation
```

## Troubleshooting

### Database Issues
- Ensure the `database/` directory is writable
- Check that PDO SQLite extension is enabled in PHP
- Run `database/init.php` again if tables are missing

### Permission Issues
- Ensure web server can read/write to necessary directories
- Check file permissions on `database/`, `uploads/`, `logs/`

### Login Issues
- Verify admin account exists in database
- Check that JWT secret matches between requests
- Clear browser localStorage if having token issues

### API Issues
- Ensure `/api/` requests are routed to `api/index.php`
- Check CORS settings if making cross-origin requests
- Verify authentication tokens are included in headers

## Development

### Adding New API Endpoints

1. Add route handling in `api/index.php`
2. Implement logic in `src/Auth.php` if needed
3. Update `APIEndpoints.md` documentation
4. Test with the test script

### Adding New Permissions

1. Insert into `permissions` table via database script
2. Assign to roles in `role_permissions` table
3. Update permission checks in code

### Frontend Development

- Use the established style guide in `StyleGuide.md`
- Follow the color scheme and component patterns
- Include authentication checks for protected pages

## Production Deployment

1. Set `environment` to `'production'` in `config.php`
2. Use a proper web server (Apache/Nginx) instead of PHP built-in server
3. Enable HTTPS
4. Set secure JWT secrets
5. Configure proper logging
6. Set up database backups
7. Use environment variables for sensitive configuration

## Support

For issues or questions:
1. Check the troubleshooting section above
2. Review the API documentation in `APIEndpoints.md`
3. Check the authentication system docs in `AuthSystem.md`
4. Run the test script to verify system health
