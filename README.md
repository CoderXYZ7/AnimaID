# AnimaID - Animation Center Management Platform

![Version](https://img.shields.io/badge/version-0.9-blue.svg)
![PHP](https://img.shields.io/badge/PHP-8.1+-purple.svg)
![License](https://img.shields.io/badge/license-Proprietary-red.svg)

A comprehensive management platform for animation centers, connecting staff, activities, and families through a unified digital environment.

## 🚀 Quick Start

### Prerequisites
- PHP 8.1 or higher
- Composer
- SQLite3
- Web server (Apache/Nginx)

### Installation

1. **Clone the repository**
   ```bash
   git clone <repository-url>
   cd AnimaID
   ```

2. **Install dependencies**
   ```bash
   composer install
   ```

3. **Configure environment**
   ```bash
   cp .env.example .env
   # Edit .env and set JWT_SECRET
   openssl rand -base64 64  # Generate a secure secret
   ```

4. **Run database migrations**
   ```bash
   php database/migrate.php migrate
   ```

5. **Set permissions** (Linux/Mac)
   ```bash
   bash scripts/maintenance/fix-permissions.sh
   ```

6. **Start development server**
   ```bash
   php -S localhost:8000
   ```

7. **Access the application**
   - Open browser: `http://localhost:8000`
   - Login with default credentials (development only):
     - Username: `admin`
     - Password: `Admin123!@#`

## 📁 Project Structure

```
AnimaID/
├── api/                    # API endpoints
│   ├── index.php          # Main API router (legacy)
│   └── index-new.php      # New Slim-based router
├── config/                # Configuration files
├── database/              # Database and migrations
│   ├── migrations/        # Database migration files
│   └── migrate.php        # Migration runner
├── docs/                  # Documentation
│   ├── reviews/           # Code review reports
│   ├── progress/          # Progress tracking
│   ├── API_MIGRATION.md   # API migration guide
│   ├── FRONTEND_CONFIG.md # Frontend configuration guide
│   └── PROJECT_ANALYSIS.md # Detailed project analysis
├── public/                # Frontend files
│   ├── css/              # Stylesheets
│   ├── js/               # JavaScript files
│   ├── pages/            # Application pages
│   └── admin/            # Admin pages
├── scripts/               # Utility scripts
│   └── maintenance/       # Maintenance scripts
├── src/                   # Backend source code
│   ├── Controllers/       # API controllers
│   ├── Services/          # Business logic
│   ├── Repositories/      # Data access layer
│   ├── Middleware/        # Request middleware
│   ├── Security/          # Security components
│   └── Config/            # Configuration management
├── tests/                 # Test files
├── vendor/                # Composer dependencies
├── .env.example          # Environment template
├── composer.json         # PHP dependencies
└── phpunit.xml           # Testing configuration
```

## 🎯 Features

- **Staff Coordination** - Manage roles, permissions, and shifts
- **Activity Management** - Organize calendars, registrations, and attendance
- **Communication Hub** - Internal messaging and public notices
- **Modular Applets** - Extensible system for custom features
- **Multi-Device Access** - Responsive web interfaces
- **Analytics & Reporting** - KPIs and insights

## 🔧 Development

### Architecture

The project follows a modern layered architecture:

```
Controllers → Services → Repositories → Database
     ↓           ↓            ↓
Middleware ← Security ← Configuration
```

**Key Components:**
- **Controllers**: Handle HTTP requests/responses (PSR-7)
- **Services**: Business logic and validation
- **Repositories**: Data access and queries
- **Middleware**: Authentication, permissions, CORS

### Running Tests

```bash
# Run all tests
composer test

# Run unit tests only
composer test:unit

# Run integration tests
composer test:integration
```

### Code Quality

```bash
# Check code style
composer cs:check

# Fix code style
composer cs:fix
```

## 📚 Documentation

- **[API Migration Guide](docs/API_MIGRATION.md)** - New API architecture
- **[Frontend Configuration](docs/FRONTEND_CONFIG.md)** - Frontend setup
- **[Project Analysis](docs/PROJECT_ANALYSIS.md)** - Detailed analysis
- **[Code Reviews](docs/reviews/)** - Quality assessments
- **[Progress Tracking](docs/progress/)** - Development progress

## 🔐 Security

### Critical Security Features
- ✅ Industry-standard JWT authentication (firebase/php-jwt)
- ✅ Environment-based configuration (.env)
- ✅ Token revocation (blacklist)
- ✅ Password hashing (bcrypt)
- ✅ Prepared statements (SQL injection protection)
- ✅ Permission-based access control

### Security Best Practices
1. **Never commit `.env` file** - Contains secrets
2. **Change default admin password** - Immediately after first login
3. **Use HTTPS in production** - Encrypt all traffic
4. **Set strong JWT_SECRET** - Use `openssl rand -base64 64`
5. **Regular updates** - Keep dependencies up to date

## 🚢 Deployment

### Production Checklist

- [ ] Set `APP_ENV=production` in `.env`
- [ ] Generate secure `JWT_SECRET`
- [ ] Disable `APP_DEBUG`
- [ ] Configure database backups
- [ ] Set up HTTPS/SSL
- [ ] Configure proper file permissions
- [ ] Disable demo credentials display
- [ ] Run database migrations
- [ ] Test all critical features

### Environment Variables

Key variables to configure in `.env`:

```bash
# Application
APP_ENV=production
APP_DEBUG=false

# Security
JWT_SECRET=<your-secure-secret>

# Database
DB_FILE=database/animaid.db

# Features
FEATURE_SHOW_DEMO_CREDENTIALS=false
```

See `.env.example` for all available options.

## 🛠️ Maintenance

### Database Migrations

```bash
# Run pending migrations
php database/migrate.php migrate

# Check migration status
php database/migrate.php status

# Rollback last migration
php database/migrate.php rollback
```

### Maintenance Scripts

Located in `scripts/maintenance/`:
- `fix-permissions.sh` - Fix file permissions
- `fix-db-and-migrate.sh` - Fix database and run migrations
- `test-server.php` - Development server

## 🤝 Contributing

### Development Workflow

1. Create a feature branch
2. Make your changes
3. Run tests and code quality checks
4. Submit a pull request

### Code Standards

- Follow PSR-12 coding style
- Write unit tests for new features
- Document public APIs
- Use type hints throughout

## 📊 Project Status

**Current Version:** 0.9 (Draft)

**Refactoring Progress:** 62% Complete (5/8 phases)

**Completed:**
- ✅ Phase 1: Foundation Setup
- ✅ Phase 2: Security Fixes
- ✅ Phase 3: Repository Layer
- ✅ Phase 4: Service Layer
- ✅ Phase 5: Controllers & Middleware

**Remaining:**
- ⏳ Phase 6: Testing
- ⏳ Phase 7: Documentation
- ⏳ Phase 8: Final Verification

See [Progress Tracking](docs/progress/) for detailed status.

## 📝 License

Proprietary - All rights reserved

## 👥 Support

For support and questions:
- Check the [documentation](docs/)
- Review [code reviews](docs/reviews/)
- See [project analysis](docs/PROJECT_ANALYSIS.md)

---

**AnimaID** - Bridging the gap between coordinators, animators, and families.

*Version 0.9 - 2025*
