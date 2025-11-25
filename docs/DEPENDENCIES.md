# AnimaID System Dependencies

## Required Dependencies

AnimaID requires the following software to be installed on your server:

### 1. PHP 8.1 or Higher

**Check if installed:**
```bash
php -v
```

**Install on Ubuntu/Debian:**
```bash
sudo apt update
sudo apt install -y php8.1 php8.1-cli php8.1-common
```

**Install on CentOS/RHEL:**
```bash
sudo yum install -y php php-cli
```

### 2. PHP Extensions

Required extensions:
- `pdo` - Database abstraction
- `sqlite3` - SQLite database support
- `mbstring` - Multibyte string handling
- `openssl` - Encryption and security
- `json` - JSON parsing
- `xml` - XML processing
- `curl` - HTTP requests

**Check installed extensions:**
```bash
php -m
```

**Install on Ubuntu/Debian:**
```bash
sudo apt install -y php8.1-sqlite3 php8.1-mbstring php8.1-xml php8.1-curl
```

**Install on CentOS/RHEL:**
```bash
sudo yum install -y php-pdo php-mbstring php-xml php-json
```

### 3. Composer

PHP dependency manager.

**Check if installed:**
```bash
composer --version
```

**Install globally:**
```bash
# Download installer
curl -sS https://getcomposer.org/installer | php

# Move to global location
sudo mv composer.phar /usr/local/bin/composer

# Make executable
sudo chmod +x /usr/local/bin/composer

# Verify installation
composer --version
```

### 4. Git

Version control system.

**Check if installed:**
```bash
git --version
```

**Install on Ubuntu/Debian:**
```bash
sudo apt install -y git
```

**Install on CentOS/RHEL:**
```bash
sudo yum install -y git
```

### 5. Web Server

One of the following:

#### Apache (Recommended)

**Install on Ubuntu/Debian:**
```bash
sudo apt install -y apache2 libapache2-mod-php8.1
sudo a2enmod rewrite
sudo systemctl enable apache2
sudo systemctl start apache2
```

**Install on CentOS/RHEL:**
```bash
sudo yum install -y httpd
sudo systemctl enable httpd
sudo systemctl start httpd
```

#### Nginx (Alternative)

**Install on Ubuntu/Debian:**
```bash
sudo apt install -y nginx php8.1-fpm
sudo systemctl enable nginx
sudo systemctl start nginx
```

**Install on CentOS/RHEL:**
```bash
sudo yum install -y nginx php-fpm
sudo systemctl enable nginx
sudo systemctl start nginx
```

## Quick Install Scripts

### Ubuntu/Debian (Complete Setup)

```bash
#!/bin/bash

# Update package list
sudo apt update

# Install PHP and extensions
sudo apt install -y php8.1 php8.1-cli php8.1-common php8.1-sqlite3 \
                    php8.1-mbstring php8.1-xml php8.1-curl php8.1-zip

# Install Git
sudo apt install -y git

# Install Composer
curl -sS https://getcomposer.org/installer | php
sudo mv composer.phar /usr/local/bin/composer
sudo chmod +x /usr/local/bin/composer

# Install Apache
sudo apt install -y apache2 libapache2-mod-php8.1
sudo a2enmod rewrite
sudo systemctl enable apache2
sudo systemctl start apache2

# Verify installations
echo "PHP Version:"
php -v
echo ""
echo "Composer Version:"
composer --version
echo ""
echo "Git Version:"
git --version
```

### CentOS/RHEL (Complete Setup)

```bash
#!/bin/bash

# Install EPEL repository
sudo yum install -y epel-release

# Install PHP and extensions
sudo yum install -y php php-cli php-pdo php-mbstring php-xml php-json

# Install Git
sudo yum install -y git

# Install Composer
curl -sS https://getcomposer.org/installer | php
sudo mv composer.phar /usr/local/bin/composer
sudo chmod +x /usr/local/bin/composer

# Install Apache
sudo yum install -y httpd
sudo systemctl enable httpd
sudo systemctl start httpd

# Verify installations
echo "PHP Version:"
php -v
echo ""
echo "Composer Version:"
composer --version
echo ""
echo "Git Version:"
git --version
```

## Verification

After installation, verify all dependencies:

```bash
# Check PHP
php -v

# Check PHP extensions
php -m | grep -E 'pdo|sqlite|mbstring|openssl|json'

# Check Composer
composer --version

# Check Git
git --version

# Check web server
sudo systemctl status apache2  # or nginx
```

## Troubleshooting

### Composer not found after installation

```bash
# Check if composer.phar exists
ls -la /usr/local/bin/composer

# If not, reinstall
curl -sS https://getcomposer.org/installer | php
sudo mv composer.phar /usr/local/bin/composer
sudo chmod +x /usr/local/bin/composer
```

### PHP extension missing

```bash
# List available PHP packages
apt search php8.1-  # Ubuntu/Debian
yum search php-     # CentOS/RHEL

# Install missing extension
sudo apt install php8.1-<extension>  # Ubuntu/Debian
sudo yum install php-<extension>     # CentOS/RHEL

# Restart web server
sudo systemctl restart apache2  # or nginx
```

### Permission issues

```bash
# Fix ownership
sudo chown -R www-data:www-data /var/www/html/AnimaID  # Ubuntu/Debian
sudo chown -R apache:apache /var/www/html/AnimaID      # CentOS/RHEL

# Fix permissions
sudo chmod -R 755 /var/www/html/AnimaID
```

## Automated Dependency Check

The deployment script (`scripts/deploy.sh`) automatically checks for all required dependencies and provides installation instructions if any are missing.

Run the script to check your system:

```bash
sudo bash scripts/deploy.sh
```

If dependencies are missing, the script will:
1. List what's missing
2. Provide installation commands for your OS
3. Exit before making any changes

---

**Last Updated:** 2025-11-25  
**AnimaID Version:** 0.9
