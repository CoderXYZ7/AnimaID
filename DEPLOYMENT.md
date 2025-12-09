# Attendance System - Deployment Instructions

## Current Status
The new "School Register" style attendance system has been implemented and pushed to GitHub, but the production server needs to pull the latest changes.

## Changes Made
1. **Backend (`src/Auth.php`)**: Added `getEventRegister()` method to fetch participants with attendance status
2. **API (`api/index.php`)**: Added `/attendance/register` endpoint
3. **Frontend (`public/pages/attendance.html`)**: Complete redesign with register-style interface
4. **Path Fixes**: Changed all script paths from relative to absolute to fix 404 errors

## Deployment Steps

### On Production Server (`animaidsgn.mywire.org`):

```bash
# 1. Navigate to the application directory
cd /path/to/AnimaID

# 2. Pull the latest changes
git pull origin master

# 3. Check if there are any file permission issues
chmod 644 public/config.js.php
chmod 644 public/pages/attendance.html
chmod 755 api

# 4. Clear any PHP opcode cache (if applicable)
# For PHP-FPM:
sudo systemctl reload php-fpm
# OR for Apache with mod_php:
sudo systemctl reload apache2

# 5. Verify the files are in place
ls -la public/config.js.php
ls -la public/pages/attendance.html
ls -la api/index.php
```

## Troubleshooting

### If `/config.js.php` still returns 404:
1. Check that `public/config.js.php` exists
2. Check file permissions (should be readable by web server)
3. Check that `index.php` in root is being executed by Apache
4. Review `debug_log.txt` in the root directory for routing information

### If API returns HTML instead of JSON:
1. Check that `api/index.php` exists and is executable
2. Verify `.htaccess` is being processed by Apache (`AllowOverride All`)
3. Check Apache error logs: `tail -f /var/log/apache2/error.log`
4. Review `debug_log.txt` for API request routing

### If the page loads but shows no events:
1. Check browser console for JavaScript errors
2. Verify the API endpoint is accessible: `curl https://animaidsgn.mywire.org/api/calendar`
3. Check database has events: `sqlite3 database/animaid.db "SELECT * FROM calendar_events;"`

## Testing the New System

1. Navigate to `https://animaidsgn.mywire.org/attendance.html`
2. Select an event from the dropdown
3. Select a date
4. You should see a table of registered participants
5. Click "Add Child to Register" to add new children
6. Click "Present" or "Absent" to mark attendance

## Next Steps After Deployment

1. Run the attendance link repair script:
   ```bash
   php scripts/fix_attendance_links.php --create-missing
   ```

2. This will:
   - Link existing participants to children
   - Create child records for any missing participants
   - Display a report of all connections

## Rollback (if needed)

If there are critical issues, you can rollback to the previous version:

```bash
git log --oneline -5  # Find the commit before the changes
git checkout <commit-hash>
```

The last stable commit before the register redesign was around commit `ed5cb06`.
