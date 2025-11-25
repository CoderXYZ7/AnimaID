#!/bin/bash
# Fix database permissions and run migrations

echo "Fixing database permissions..."
sudo chmod 666 database/animaid.db
sudo chown maintainer:maintainer database/animaid.db

echo "Running migrations..."
php database/migrate.php migrate

echo "Checking migration status..."
php database/migrate.php status

echo "Done!"
