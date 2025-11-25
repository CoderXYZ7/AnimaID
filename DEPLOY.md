# Quick Deployment Guide

## For Production Server

### Option 1: Automated Deployment (Recommended)

```bash
# On your production server
cd /var/www/html
git clone https://github.com/CoderXYZ7/AnimaID.git
cd AnimaID

# Run automated deployment
sudo bash scripts/deploy.sh
```

The script will:
1. Pull latest changes
2. Install dependencies
3. Check .env configuration
4. Set permissions
5. Run migrations
6. Restart web server

### Option 2: Manual Deployment

```bash
# 1. Clone or pull
git clone https://github.com/CoderXYZ7/AnimaID.git
# or
git pull origin master

# 2. Install dependencies
composer install --no-dev --optimize-autoloader

# 3. Configure environment
cp .env.example .env
nano .env  # Set JWT_SECRET and other settings

# 4. Set permissions
bash scripts/maintenance/fix-permissions.sh

# 5. Run migrations
php database/migrate.php migrate

# 6. Restart web server
sudo systemctl restart apache2  # or nginx
```

## Critical Configuration

### Generate JWT Secret

```bash
openssl rand -base64 64
```

### Edit .env File

```bash
APP_ENV=production
APP_DEBUG=false
JWT_SECRET=<your-generated-secret>
FEATURE_SHOW_DEMO_CREDENTIALS=false
```

## First Login

1. Go to: `https://your-domain.com/login.html`
2. Username: `admin`
3. Password: `Admin123!@#`
4. **Change password immediately!**

## For Updates

```bash
cd /var/www/html/AnimaID
sudo bash scripts/deploy.sh
```

## Full Documentation

See `docs/DEPLOYMENT.md` for complete deployment guide including:
- Server requirements
- Web server configuration (Apache/Nginx)
- SSL setup
- Troubleshooting
- Monitoring
- Backups

---

**Quick Start:** Run `sudo bash scripts/deploy.sh` on your server!
