#!/bin/bash

# AnimaID Server Permissions Fix Script
# This script fixes file permissions and runs database initialization

echo "=== AnimaID Server Permissions Fix ==="
echo "Current directory: $(pwd)"

# Create necessary directories if they don't exist
echo "Creating directories if needed..."
mkdir -p database
mkdir -p logs
mkdir -p uploads
mkdir -p backups

# Set ownership for web server user (www-data)
echo "Setting ownership to www-data..."
sudo chown www-data:www-data database/
sudo chown www-data:www-data logs/
sudo chown www-data:www-data uploads/
sudo chown www-data:www-data backups/

# Set permissions for directories
echo "Setting directory permissions..."
sudo chmod 755 database/
sudo chmod 755 logs/
sudo chmod 755 uploads/
sudo chmod 755 backups/

# Set permissions for database file (if exists)
if [ -f "database/animaid.db" ]; then
    echo "Setting database file permissions..."
    sudo chown www-data:www-data database/animaid.db
    sudo chmod 664 database/animaid.db
else
    echo "Warning: database/animaid.db not found"
fi

# Set permissions for database backup file (if exists)
if [ -f "database/animaid.db.old" ]; then
    echo "Setting backup database file permissions..."
    sudo chown www-data:www-data database/animaid.db.old
    sudo chmod 664 database/animaid.db.old
fi

# Recursively set permissions for logs and uploads contents
echo "Setting recursive permissions for logs and uploads..."
sudo chown -R www-data:www-data logs/
sudo chown -R www-data:www-data uploads/
sudo chmod -R 755 logs/
sudo chmod -R 755 uploads/

# Run database initialization
echo "Running database initialization..."
if [ ! -f "config/config.php" ]; then
    echo "Warning: config/config.php not found, creating from default..."
    cp config/configDefault.php config/config.php
fi

if [ -f "database/init.php" ]; then
    sudo -u www-data php database/init.php || echo "Warning: Database initialization had errors (this may be normal if already initialized)"
    echo "Database initialization completed"
else
    echo "Error: database/init.php not found"
    exit 1
fi

echo "=== Permissions fix completed ==="
echo "You can now test the login at: https://animaidsgn.mywire.org/login.html"
echo "Test credentials:"
echo "  Username: testuser"
echo "  Password: Testpass123"
echo "Or use default admin:"
echo "  Username: admin" 
echo "  Password: Admin123!@#"
