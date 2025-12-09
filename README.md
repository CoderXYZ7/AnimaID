# AnimaID - Animation Center Management System

![Version](https://img.shields.io/badge/version-0.9-blue.svg)
![PHP](https://img.shields.io/badge/PHP-8.1+-purple.svg)
![License](https://img.shields.io/badge/license-Proprietary-red.svg)

A comprehensive management platform for animation centers, connecting staff, activities, and families through a unified digital environment.

## 🎯 What is AnimaID?

AnimaID is a complete web-based management system designed for animation centers, summer camps, and youth organizations. It provides tools for managing children, staff (animators), events, attendance, communications, and more—all in one integrated platform.

## ✨ Core Features

### 👥 **User & Role Management**
- Multi-role authentication system (Admin, Coordinator, Animator, Parent)
- Granular permission-based access control
- JWT-based secure authentication
- User session management with token blacklisting

### 👶 **Children Management**
- Complete child registration system
- Medical information and emergency contacts
- Guardian/parent relationship tracking
- Registration numbers and status tracking
- Activity history and notes

### 🎭 **Animator Management**
- Animator profiles and availability tracking
- Week-type based scheduling
- Availability exceptions and time-off management
- User account linking for animators
- Activity history and performance notes

### 📅 **Calendar & Events**
- Event creation and management
- Multi-day event support
- Location and capacity tracking
- Age restrictions (min/max age)
- Public/private event visibility
- Event participant registration

### ✅ **Attendance System**
- Quick check-in/check-out interface
- Real-time attendance tracking
- Event-based attendance records
- Date-filtered attendance reports
- Participant status tracking

### 💬 **Communications Hub**
- Internal announcements and notices
- Public communications for parents
- Comment system for discussions
- File attachments support
- Read/unread tracking
- Priority and category management

### 📚 **Wiki & Knowledge Base**
- Full-featured wiki system with markdown support
- Categories and tags organization
- Full-text search (FTS5)
- Revision history tracking
- File attachments
- Featured pages

### 📁 **Media Library**
- Folder-based organization
- File versioning system
- Sharing and permissions
- Multiple file format support
- Upload and download tracking

### 📊 **Reports & Analytics**
- Attendance reports by date range
- Children statistics and demographics
- Animator performance reports
- Event participation tracking

### 🏢 **Space Management**
- Room/space booking system
- Availability tracking
- Capacity management

## 🚀 Quick Start

### Prerequisites

- **PHP 8.1+** with extensions:
  - PDO
  - SQLite3
  - mbstring
  - openssl
  - json
- **Composer** (dependency manager)
- **Git**
- **Web Server** (Apache or Nginx)

### Local Development Setup

1. **Clone the repository**
   ```bash
   git clone https://github.com/CoderXYZ7/AnimaID.git
   cd AnimaID
   ```

2. **Install dependencies**
   ```bash
   composer install
   ```

3. **Configure environment**
   ```bash
   cp .env.example .env
   # Generate a secure JWT secret
   openssl rand -base64 64
   # Edit .env and paste the secret into JWT_SECRET
   ```

4. **Create config file**
   ```bash
   cp config/configDefault.php config/config.php
   # Edit config/config.php if needed
   ```

5. **Run database migrations**
   ```bash
   php database/migrate.php migrate
   ```

6. **Start development server**
   ```bash
   php -S localhost:8000
   ```

7. **Access the application**
   - Open browser: `http://localhost:8000`
   - Login with default credentials:
     - Username: `admin`
     - Password: `Admin123!@#`
   - **⚠️ Change the password immediately after first login!**

## 🚢 Production Deployment

### Automated Deployment (Recommended)

```bash
# On your production server
cd /var/www/html
git clone https://github.com/CoderXYZ7/AnimaID.git
cd AnimaID

# Run automated deployment script
sudo bash scripts/deploy.sh
```

The deployment script will:
1. ✅ Pull latest changes from the current branch
2. ✅ Install Composer dependencies
3. ✅ Create `.env` and `config/config.php` if missing
4. ✅ Set proper file permissions
5. ✅ Run database migrations
6. ✅ Verify installation
7. ✅ Restart web server (Apache/Nginx)

### Manual Deployment

```bash
# 1. Clone or update repository
git clone https://github.com/CoderXYZ7/AnimaID.git
cd AnimaID
# or if already cloned:
git pull origin master

# 2. Install dependencies
composer install --no-dev --optimize-autoloader

# 3. Configure environment
cp .env.example .env
cp config/configDefault.php config/config.php

# Generate secure JWT secret
openssl rand -base64 64

# Edit .env and set:
# - JWT_SECRET (paste generated secret)
# - APP_ENV=production
# - APP_DEBUG=false
# - FEATURE_SHOW_DEMO_CREDENTIALS=false

# 4. Set permissions
sudo bash scripts/maintenance/fix-permissions.sh

# 5. Run migrations
php database/migrate.php migrate

# 6. Restart web server
sudo systemctl restart apache2  # or nginx
```

### Production Checklist

- [ ] Set `APP_ENV=production` in `.env`
- [ ] Set `APP_DEBUG=false` in `.env`
- [ ] Generate and set secure `JWT_SECRET` (64+ characters)
- [ ] Set `FEATURE_SHOW_DEMO_CREDENTIALS=false`
- [ ] Change default admin password immediately
- [ ] Configure HTTPS/SSL certificate
- [ ] Set proper file permissions (www-data user)
- [ ] Configure database backups
- [ ] Test all critical features
- [ ] Set up monitoring/logging

### Restoring Production Data

If you have a backup database from production:

```bash
# Place your backup in workfiles/
cp /path/to/backup.db workfiles/animaid.db

# Run restoration script
php scripts/restore_production_data.php workfiles/animaid.db
```

This will safely import all data while preserving the current schema.

## 📁 Project Structure

```
AnimaID/
├── api/                      # API endpoints
│   └── index.php            # Main API router
├── config/                  # Configuration files
│   ├── configDefault.php    # Default config template
│   └── config.php          # Active config (gitignored)
├── database/                # Database and migrations
│   ├── migrations/          # Migration files
│   ├── migrate.php         # Migration runner
│   ├── init.php            # Database initialization
│   └── animaid.db          # SQLite database (gitignored)
├── docs/                    # Documentation
│   ├── API_MIGRATION.md    # API architecture guide
│   ├── DEPLOYMENT.md       # Detailed deployment guide
│   └── PROJECT_ANALYSIS.md # Project analysis
├── public/                  # Frontend files (document root)
│   ├── admin/              # Admin pages
│   │   ├── users.html      # User management
│   │   ├── roles.html      # Role management
│   │   ├── reports.html    # Reports dashboard
│   │   └── status.html     # System status
│   ├── pages/              # Main application pages
│   │   ├── children.html   # Children management
│   │   ├── animators.html  # Animator management
│   │   ├── calendar.html   # Event calendar
│   │   ├── attendance.html # Attendance tracking
│   │   ├── communications.html # Communications
│   │   ├── media.html      # Media library
│   │   ├── wiki.html       # Wiki pages
│   │   └── wiki-categories.html # Wiki categories
│   ├── js/                 # JavaScript modules
│   │   ├── config.js       # Configuration loader
│   │   ├── apiService.js   # API client
│   │   └── ui.js           # UI utilities
│   ├── css/                # Stylesheets
│   ├── dashboard.html      # Main dashboard
│   ├── login.html          # Login page
│   ├── index.html          # Public homepage
│   └── config.js.php       # Dynamic config generator
├── scripts/                 # Utility scripts
│   ├── deploy.sh           # Automated deployment
│   ├── restore_production_data.php # Data restoration
│   ├── check_server_health.php # Health diagnostics
│   └── maintenance/        # Maintenance scripts
│       └── fix-permissions.sh # Permission fixer
├── src/                     # Backend source code
│   ├── Auth.php            # Authentication & authorization
│   ├── Database.php        # Database connection
│   ├── JWT.php             # JWT token handling
│   ├── Controllers/        # API controllers (new architecture)
│   ├── Services/           # Business logic services
│   ├── Repositories/       # Data access layer
│   ├── Middleware/         # Request middleware
│   └── Security/           # Security components
├── tests/                   # Test files
├── vendor/                  # Composer dependencies (gitignored)
├── logs/                    # Application logs (gitignored)
├── uploads/                 # User uploads (gitignored)
├── backups/                 # Database backups (gitignored)
├── .env.example            # Environment template
├── .env                    # Environment config (gitignored)
├── composer.json           # PHP dependencies
└── README.md               # This file
```

## 🔧 Development

### Database Migrations

```bash
# Check migration status
php database/migrate.php

# Run pending migrations
php database/migrate.php migrate

# Rollback last migration
php database/migrate.php rollback
```

### Maintenance Scripts

```bash
# Fix file permissions
sudo bash scripts/maintenance/fix-permissions.sh

# Check server health
php scripts/check_server_health.php

# Full deployment (pull + install + migrate + permissions)
sudo bash scripts/deploy.sh
```

### API Endpoints

The API is RESTful and located at `/api/*`:

- **Authentication**: `/api/auth/*`
  - POST `/api/auth/login` - User login
  - POST `/api/auth/logout` - User logout
  - GET `/api/auth/me` - Get current user

- **Users**: `/api/users/*` (Admin only)
- **Roles**: `/api/roles/*` (Admin only)
- **Permissions**: `/api/permissions/*` (Admin only)
- **Children**: `/api/children/*`
- **Animators**: `/api/animators/*`
- **Calendar**: `/api/calendar/*`
- **Attendance**: `/api/attendance/*`
- **Communications**: `/api/communications/*`
- **Media**: `/api/media/*`
- **Wiki**: `/api/wiki/*`
- **Reports**: `/api/reports/*`
- **System**: `/api/system/*`

## 🔐 Security

### Implemented Security Features

- ✅ **JWT Authentication** - Industry-standard token-based auth
- ✅ **Token Blacklisting** - Revoke compromised tokens
- ✅ **Password Hashing** - Bcrypt with configurable cost
- ✅ **SQL Injection Protection** - Prepared statements throughout
- ✅ **Permission System** - Granular role-based access control
- ✅ **CORS Configuration** - Configurable cross-origin policies
- ✅ **Environment Variables** - Secrets stored in `.env`
- ✅ **Session Management** - Secure session handling

### Security Best Practices

1. **Never commit sensitive files**:
   - `.env` (contains JWT_SECRET)
   - `config/config.php` (may contain secrets)
   - `database/*.db` (production data)

2. **Change default credentials** immediately after first login

3. **Use HTTPS in production** - Never run production over HTTP

4. **Generate strong JWT_SECRET**:
   ```bash
   openssl rand -base64 64
   ```

5. **Keep dependencies updated**:
   ```bash
   composer update
   ```

6. **Set proper file permissions** - Run `fix-permissions.sh`

7. **Regular backups** - Database is in `database/animaid.db`

## 🛠️ Troubleshooting

### Common Issues

**500 Internal Server Error on login:**
- Check file permissions: `sudo bash scripts/maintenance/fix-permissions.sh`
- Verify `config/config.php` exists
- Check database is writable by www-data

**404 on config.js.php:**
- Ensure `vendor/` directory exists: `composer install`
- Check `.htaccess` is present and mod_rewrite is enabled

**Database errors:**
- Run migrations: `php database/migrate.php migrate`
- Check database permissions
- Verify SQLite3 PHP extension is installed

**Token expired errors:**
- Clear browser localStorage
- Log in again
- Check JWT_SECRET is set in `.env`

### Health Check

Run the diagnostic script:

```bash
php scripts/check_server_health.php
```

This will check:
- Database file existence and permissions
- Composer dependencies
- Environment configuration
- Database connectivity
- Recent error logs

## 📚 Documentation

- **[DEPLOY.md](DEPLOY.md)** - Quick deployment guide
- **[docs/DEPLOYMENT.md](docs/DEPLOYMENT.md)** - Complete deployment documentation
- **[docs/API_MIGRATION.md](docs/API_MIGRATION.md)** - API architecture details
- **[docs/PROJECT_ANALYSIS.md](docs/PROJECT_ANALYSIS.md)** - Project analysis

## 📊 Technology Stack

- **Backend**: PHP 8.1+ (vanilla PHP, no framework)
- **Database**: SQLite3 with FTS5 (full-text search)
- **Authentication**: JWT (firebase/php-jwt)
- **Frontend**: Vanilla JavaScript + Tailwind CSS
- **Dependencies**: Managed via Composer
- **Web Server**: Apache or Nginx

## 📝 License

Proprietary - All rights reserved

## 👥 Support

For issues and questions:
1. Check this README
2. Review documentation in `docs/`
3. Run health check: `php scripts/check_server_health.php`
4. Check logs in `logs/animaid.log`

---

**AnimaID** - Bridging the gap between coordinators, animators, and families.

*Version 0.9 - 2025*
