#!/bin/bash

# AnimaID Quick Deployment Script
# This script automates the deployment process

set -e  # Exit on error

echo "========================================="
echo "AnimaID Deployment Script"
echo "========================================="
echo ""

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

# Check if running as root
if [ "$EUID" -ne 0 ]; then 
    echo -e "${RED}Please run as root or with sudo${NC}"
    exit 1
fi

# Get the actual user (not root when using sudo)
ACTUAL_USER=${SUDO_USER:-$USER}
PROJECT_DIR=$(pwd)

echo "Project directory: $PROJECT_DIR"
echo "Running as: $ACTUAL_USER"
echo ""

# Check dependencies
echo -e "${YELLOW}Checking system dependencies...${NC}"
echo ""

MISSING_DEPS=0

# Check PHP
if command -v php &> /dev/null; then
    PHP_VERSION=$(php -r "echo PHP_VERSION;")
    echo -e "${GREEN}✓ PHP installed${NC} (version $PHP_VERSION)"
    
    # Check PHP version
    PHP_MAJOR=$(php -r "echo PHP_MAJOR_VERSION;")
    PHP_MINOR=$(php -r "echo PHP_MINOR_VERSION;")
    if [ "$PHP_MAJOR" -lt 8 ] || ([ "$PHP_MAJOR" -eq 8 ] && [ "$PHP_MINOR" -lt 1 ]); then
        echo -e "${RED}✗ PHP 8.1 or higher required (found $PHP_VERSION)${NC}"
        MISSING_DEPS=1
    fi
else
    echo -e "${RED}✗ PHP not installed${NC}"
    MISSING_DEPS=1
fi

# Check Composer
if command -v composer &> /dev/null; then
    COMPOSER_VERSION=$(composer --version --no-ansi 2>/dev/null | head -n1)
    echo -e "${GREEN}✓ Composer installed${NC} ($COMPOSER_VERSION)"
else
    echo -e "${RED}✗ Composer not installed${NC}"
    MISSING_DEPS=1
fi

# Check Git
if command -v git &> /dev/null; then
    GIT_VERSION=$(git --version)
    echo -e "${GREEN}✓ Git installed${NC} ($GIT_VERSION)"
else
    echo -e "${RED}✗ Git not installed${NC}"
    MISSING_DEPS=1
fi

# Check required PHP extensions
echo ""
echo "Checking PHP extensions..."
REQUIRED_EXTS=("pdo" "sqlite3" "mbstring" "openssl" "json")
for ext in "${REQUIRED_EXTS[@]}"; do
    if php -m 2>/dev/null | grep -q "^$ext$"; then
        echo -e "${GREEN}✓ $ext${NC}"
    else
        echo -e "${RED}✗ $ext (missing)${NC}"
        MISSING_DEPS=1
    fi
done

echo ""

# If dependencies are missing, show installation instructions
if [ $MISSING_DEPS -eq 1 ]; then
    echo -e "${RED}=========================================${NC}"
    echo -e "${RED}Missing Dependencies Detected!${NC}"
    echo -e "${RED}=========================================${NC}"
    echo ""
    echo "Please install the missing dependencies before continuing."
    echo ""
    echo -e "${YELLOW}For Ubuntu/Debian:${NC}"
    echo "  sudo apt update"
    echo "  sudo apt install -y php php-cli php-common php-sqlite3 \\"
    echo "                      php-mbstring php-xml php-curl git"
    echo ""
    echo "  # Install Composer"
    echo "  curl -sS https://getcomposer.org/installer | php"
    echo "  sudo mv composer.phar /usr/local/bin/composer"
    echo "  sudo chmod +x /usr/local/bin/composer"
    echo ""
    echo -e "${YELLOW}For CentOS/RHEL:${NC}"
    echo "  sudo yum install -y php php-cli php-pdo php-mbstring php-xml git"
    echo ""
    echo "  # Install Composer"
    echo "  curl -sS https://getcomposer.org/installer | php"
    echo "  sudo mv composer.phar /usr/local/bin/composer"
    echo "  sudo chmod +x /usr/local/bin/composer"
    echo ""
    echo -e "${YELLOW}After installing dependencies, run this script again.${NC}"
    echo ""
    exit 1
fi

echo -e "${GREEN}✓ All dependencies satisfied${NC}"
echo ""

# Step 1: Pull latest changes
echo -e "${YELLOW}[1/7] Pulling latest changes...${NC}"
sudo -u $ACTUAL_USER git pull origin master
echo -e "${GREEN}✓ Changes pulled${NC}"
echo ""

# Step 2: Install dependencies
echo -e "${YELLOW}[2/7] Installing dependencies...${NC}"
sudo -u $ACTUAL_USER composer install --no-dev --optimize-autoloader
echo -e "${GREEN}✓ Dependencies installed${NC}"
echo ""

# Step 3: Check .env file
echo -e "${YELLOW}[3/7] Checking environment configuration...${NC}"
if [ ! -f .env ]; then
    echo -e "${RED}✗ .env file not found!${NC}"
    echo "Creating .env from template..."
    cp .env.example .env
    echo -e "${YELLOW}⚠ IMPORTANT: Edit .env and set JWT_SECRET!${NC}"
    echo "Generate secret with: openssl rand -base64 64"
    read -p "Press enter to continue after editing .env..."
else
    echo -e "${GREEN}✓ .env file exists${NC}"
fi
echo ""

# Step 4: Set permissions
echo -e "${YELLOW}[4/7] Setting file permissions...${NC}"
bash scripts/maintenance/fix-permissions.sh
echo -e "${GREEN}✓ Permissions set${NC}"
echo ""

# Step 5: Run migrations
echo -e "${YELLOW}[5/7] Running database migrations...${NC}"
sudo -u $ACTUAL_USER php database/migrate.php migrate
echo -e "${GREEN}✓ Migrations completed${NC}"
echo ""

# Step 6: Verify installation
echo -e "${YELLOW}[6/7] Verifying installation...${NC}"

# Check PHP version
PHP_VERSION=$(php -r "echo PHP_VERSION;")
echo "PHP Version: $PHP_VERSION"

# Check required extensions
REQUIRED_EXTS=("pdo" "sqlite3" "mbstring" "openssl" "json")
for ext in "${REQUIRED_EXTS[@]}"; do
    if php -m | grep -q "^$ext$"; then
        echo -e "${GREEN}✓ $ext extension installed${NC}"
    else
        echo -e "${RED}✗ $ext extension missing${NC}"
    fi
done

# Check database
if [ -f database/animaid.db ]; then
    echo -e "${GREEN}✓ Database file exists${NC}"
else
    echo -e "${RED}✗ Database file not found${NC}"
fi

echo ""

# Step 7: Restart web server
echo -e "${YELLOW}[7/7] Restarting web server...${NC}"
if systemctl is-active --quiet apache2; then
    systemctl restart apache2
    echo -e "${GREEN}✓ Apache restarted${NC}"
elif systemctl is-active --quiet nginx; then
    systemctl restart nginx
    echo -e "${GREEN}✓ Nginx restarted${NC}"
else
    echo -e "${YELLOW}⚠ No web server detected (Apache/Nginx)${NC}"
fi
echo ""

# Final summary
echo "========================================="
echo -e "${GREEN}Deployment Complete!${NC}"
echo "========================================="
echo ""
echo "Next steps:"
echo "1. Verify .env configuration (especially JWT_SECRET)"
echo "2. Access the application in your browser"
echo "3. Login with default credentials (change immediately!)"
echo "   - Username: admin"
echo "   - Password: Admin123!@#"
echo ""
echo "For more information, see docs/DEPLOYMENT.md"
echo ""
