# AnimaID Deployment Guide

## Quick Deployment Guide

This guide will help you deploy AnimaID on a production server.

## Prerequisites

### Server Requirements
- **PHP:** 8.1 or higher
- **Extensions:** PDO, SQLite3, mbstring, openssl, json
- **Composer:** Latest version
- **Web Server:** Apache or Nginx
- **Git:** For pulling updates

### Recommended Server Specs
- **RAM:** 2GB minimum
- **Storage:** 10GB minimum
- **OS:** Ubuntu 20.04+ or similar Linux distribution

## Step-by-Step Deployment

### 1. Clone the Repository

```bash
# Navigate to your web directory
cd /var/www/html

# Clone the repository
git clone https://github.com/CoderXYZ7/AnimaID.git
cd AnimaID

# Or if already cloned, pull latest changes
git pull origin master
```

### 2. Install Dependencies

```bash
# Install Composer dependencies
composer install --no-dev --optimize-autoloader

# The --no-dev flag excludes development dependencies
# The --optimize-autoloader flag optimizes the autoloader for production
```

### 3. Configure Environment

```bash
# Copy the environment template
cp .env.example .env

# Generate a secure JWT secret
openssl rand -base64 64

# Edit the .env file
nano .env
```

**Critical .env Settings:**

```bash
# Application
APP_ENV=production
APP_DEBUG=false

# Security - IMPORTANT: Use the generated secret!
JWT_SECRET=<paste-your-generated-secret-here>
JWT_EXPIRATION_HOURS=2

# Database
DB_FILE=database/animaid.db

# Features
FEATURE_SHOW_DEMO_CREDENTIALS=false
```

### 4. Set File Permissions

```bash
# Run the permissions script
bash scripts/maintenance/fix-permissions.sh

# Or manually set permissions
chmod 755 database
chmod 664 database/animaid.db
chmod 755 uploads
chmod 755 logs

# Ensure web server can write to these directories
chown -R www-data:www-data database uploads logs
```

### 5. Run Database Migrations

```bash
# Run all pending migrations
php database/migrate.php migrate

# Check migration status
php database/migrate.php status
```

### 6. Configure Web Server

#### Option A: Apache

Create a virtual host configuration:

```bash
sudo nano /etc/apache2/sites-available/animaid.conf
```

```apache
<VirtualHost *:443>
    ServerName animaidsgn.mywire.org
    DocumentRoot /var/www/html/AnimaID

    # SSL Configuration
    SSLEngine on
    SSLCertificateFile /path/to/certificate.crt
    SSLCertificateKeyFile /path/to/private.key

    <Directory /var/www/html/AnimaID>
        Options -Indexes +FollowSymLinks
        AllowOverride All
        Require all granted
    </Directory>

    # Logging
    ErrorLog ${APACHE_LOG_DIR}/animaid-error.log
    CustomLog ${APACHE_LOG_DIR}/animaid-access.log combined
</VirtualHost>

# Redirect HTTP to HTTPS
<VirtualHost *:80>
    ServerName animaidsgn.mywire.org
    Redirect permanent / https://animaidsgn.mywire.org/
</VirtualHost>
```

Enable the site:

```bash
# Enable required modules
sudo a2enmod rewrite ssl

# Enable the site
sudo a2ensite animaid.conf

# Restart Apache
sudo systemctl restart apache2
```

#### Option B: Nginx

Create a server block:

```bash
sudo nano /etc/nginx/sites-available/animaid
```

```nginx
server {
    listen 80;
    server_name animaidsgn.mywire.org;
    return 301 https://$server_name$request_uri;
}

server {
    listen 443 ssl http2;
    server_name animaidsgn.mywire.org;
    root /var/www/html/AnimaID;
    index index.php index.html;

    # SSL Configuration
    ssl_certificate /path/to/certificate.crt;
    ssl_certificate_key /path/to/private.key;

    # Security headers
    add_header X-Frame-Options "SAMEORIGIN" always;
    add_header X-Content-Type-Options "nosniff" always;
    add_header X-XSS-Protection "1; mode=block" always;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php8.1-fpm.sock;
        fastcgi_index index.php;
        fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
        include fastcgi_params;
    }

    location ~ /\.(?!well-known).* {
        deny all;
    }

    # Logging
    access_log /var/log/nginx/animaid-access.log;
    error_log /var/log/nginx/animaid-error.log;
}
```

Enable the site:

```bash
# Create symlink
sudo ln -s /etc/nginx/sites-available/animaid /etc/nginx/sites-enabled/

# Test configuration
sudo nginx -t

# Restart Nginx
sudo systemctl restart nginx
```

### 7. Verify Installation

```bash
# Check PHP version
php -v

# Check required extensions
php -m | grep -E 'pdo|sqlite|mbstring|openssl|json'

# Test database connection
php -r "new PDO('sqlite:database/animaid.db');"

# Check file permissions
ls -la database/
ls -la uploads/
```

### 8. Access the Application

1. Open browser: `https://animaidsgn.mywire.org`
2. You should see the landing page
3. Click "Login" or go to `/login.html`
4. **First login:**
   - Username: `admin`
   - Password: `Admin123!@#`
5. **IMPORTANT:** Change the admin password immediately!

## Post-Deployment Checklist

### Security Checklist

- [ ] `.env` file created with secure `JWT_SECRET`
- [ ] `APP_ENV=production` set
- [ ] `APP_DEBUG=false` set
- [ ] Demo credentials disabled (`FEATURE_SHOW_DEMO_CREDENTIALS=false`)
- [ ] HTTPS/SSL configured
- [ ] Admin password changed from default
- [ ] File permissions set correctly
- [ ] `.env` file NOT accessible via web (check `.htaccess`)

### Functionality Checklist

- [ ] Landing page loads
- [ ] Login works
- [ ] Dashboard accessible
- [ ] API endpoints respond
- [ ] Database migrations ran successfully
- [ ] File uploads work (test in media section)

### Performance Checklist

- [ ] Composer autoloader optimized (`--optimize-autoloader`)
- [ ] No development dependencies installed (`--no-dev`)
- [ ] Logs directory writable
- [ ] Database file has correct permissions

## Updating the Application

When you need to deploy updates:

```bash
# Navigate to project directory
cd /var/www/html/AnimaID

# Pull latest changes
git pull origin master

# Update dependencies
composer install --no-dev --optimize-autoloader

# Run any new migrations
php database/migrate.php migrate

# Clear any caches if applicable
# (Not needed for current setup)

# Restart web server (if needed)
sudo systemctl restart apache2
# or
sudo systemctl restart nginx
```

## Troubleshooting

### Issue: "Permission denied" errors

```bash
# Fix permissions
bash scripts/maintenance/fix-permissions.sh

# Ensure web server user owns files
sudo chown -R www-data:www-data /var/www/html/AnimaID
```

### Issue: "Database is locked"

```bash
# Check database permissions
chmod 664 database/animaid.db
chmod 755 database

# Ensure web server can write
chown www-data:www-data database/animaid.db
```

### Issue: "Invalid JWT secret"

```bash
# Generate new secret
openssl rand -base64 64

# Update .env file
nano .env
# Set JWT_SECRET=<new-secret>
```

### Issue: API returns 404

```bash
# Check .htaccess exists
ls -la .htaccess

# Ensure mod_rewrite is enabled (Apache)
sudo a2enmod rewrite
sudo systemctl restart apache2

# Check virtual host AllowOverride
# Should be: AllowOverride All
```

### Issue: "Class not found" errors

```bash
# Regenerate autoloader
composer dump-autoload --optimize

# Verify PSR-4 autoloading
composer validate
```

## Monitoring

### Log Files

```bash
# Application logs
tail -f logs/animaid.log

# Apache logs
tail -f /var/log/apache2/animaid-error.log

# Nginx logs
tail -f /var/log/nginx/animaid-error.log

# PHP-FPM logs
tail -f /var/log/php8.1-fpm.log
```

### Database Backups

```bash
# Create backup script
nano /usr/local/bin/backup-animaid.sh
```

```bash
#!/bin/bash
BACKUP_DIR="/var/backups/animaid"
DATE=$(date +%Y%m%d_%H%M%S)

mkdir -p $BACKUP_DIR
cp /var/www/html/AnimaID/database/animaid.db $BACKUP_DIR/animaid_$DATE.db

# Keep only last 30 days
find $BACKUP_DIR -name "animaid_*.db" -mtime +30 -delete
```

```bash
# Make executable
chmod +x /usr/local/bin/backup-animaid.sh

# Add to crontab (daily at 2 AM)
crontab -e
# Add: 0 2 * * * /usr/local/bin/backup-animaid.sh
```

## Production Optimization

### PHP Configuration

Edit `php.ini`:

```ini
# Performance
opcache.enable=1
opcache.memory_consumption=128
opcache.max_accelerated_files=10000
opcache.revalidate_freq=2

# Security
expose_php=Off
display_errors=Off
log_errors=On

# Limits
upload_max_filesize=10M
post_max_size=10M
memory_limit=256M
```

### Database Optimization

```bash
# Optimize database
sqlite3 database/animaid.db "VACUUM;"
sqlite3 database/animaid.db "ANALYZE;"
```

## Support

If you encounter issues:

1. Check logs: `logs/animaid.log`
2. Review documentation: `docs/`
3. Check GitHub issues
4. Verify all checklist items above

## Quick Reference Commands

```bash
# Pull updates
git pull origin master && composer install --no-dev --optimize-autoloader

# Run migrations
php database/migrate.php migrate

# Check status
php database/migrate.php status

# Fix permissions
bash scripts/maintenance/fix-permissions.sh

# View logs
tail -f logs/animaid.log

# Backup database
cp database/animaid.db database/animaid.db.backup.$(date +%Y%m%d)
```

---

**Deployment Guide Version:** 1.0  
**Last Updated:** 2025-11-25  
**AnimaID Version:** 0.9
