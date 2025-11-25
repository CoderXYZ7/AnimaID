# Frontend Configuration Guide

## Overview

AnimaID now uses a centralized configuration system that automatically adapts to different environments (development, staging, production).

## How It Works

### 1. Configuration Loader (`js/config.js`)

The configuration loader:
- Attempts to fetch configuration from `/config.js.php` (server-side)
- Falls back to environment-aware defaults if server config unavailable
- Automatically detects development vs production environment
- Provides backward compatibility with existing code

### 2. Environment Detection

**Development Environment:**
- Hostname is `localhost` or `127.0.0.1`
- API URL: `http://localhost/api`
- Demo credentials: **Enabled**

**Production Environment:**
- Any other hostname
- API URL: `https://animaidsgn.mywire.org/api`
- Demo credentials: **Disabled**

### 3. Configuration Structure

```javascript
window.ANIMAID_CONFIG = {
    api: {
        baseUrl: string,  // API base URL
        port: number      // API port (80 or 443)
    },
    system: {
        name: string,        // Application name
        version: string,     // Version number
        environment: string, // 'development' or 'production'
        locale: string       // Default locale
    },
    features: {
        show_demo_credentials: boolean,  // Show demo login info
        user_registration: boolean,
        password_reset: boolean,
        email_notifications: boolean,
        two_factor_auth: boolean
    }
}
```

## Usage in HTML Files

### Method 1: Module Import (Recommended)

```html
<script type="module">
    import { initConfig } from './js/config.js';
    
    // Initialize config before using it
    await initConfig();
    
    // Now you can use window.ANIMAID_CONFIG
    console.log(window.ANIMAID_CONFIG.api.baseUrl);
</script>
```

### Method 2: Script Tag (Legacy Support)

```html
<script src="js/config.js"></script>
<script>
    // Config is auto-initialized
    // Access via window.ANIMAID_CONFIG
</script>
```

## Updated Files

The following files have been updated to use centralized configuration:

- ✅ `login.html` - Uses config loader
- ✅ `dashboard.html` - Uses config loader
- ✅ `js/config.js` - New configuration loader

## Files That Need Updating

The following files may still have hardcoded configuration and should be updated:

- `public.html`
- `pages/*.html` (all page files)
- `admin/*.html` (all admin files)

## Server-Side Configuration

### config.js.php

The server can provide dynamic configuration via `/config.js.php`:

```php
<?php
header('Content-Type: application/javascript');

$config = require __DIR__ . '/../config/config.php';

echo "window.ANIMAID_CONFIG = " . json_encode([
    'api' => [
        'baseUrl' => $_SERVER['REQUEST_SCHEME'] . '://' . $_SERVER['HTTP_HOST'] . '/api',
        'port' => $config['api']['port']
    ],
    'system' => [
        'name' => $config['system']['name'],
        'version' => $config['system']['version'],
        'environment' => $config['system']['environment'],
        'locale' => $config['system']['locale']
    ],
    'features' => $config['features']
], JSON_PRETTY_PRINT) . ";";
?>
```

## Benefits

1. **Environment Awareness**
   - Automatically uses correct API URL for dev/prod
   - No manual configuration changes needed

2. **Security**
   - Demo credentials only shown in development
   - Production-safe defaults

3. **Maintainability**
   - Single source of truth for configuration
   - Easy to update across all pages

4. **Flexibility**
   - Can override via server-side config
   - Falls back to sensible defaults

## Migration Checklist

To migrate a page to use centralized config:

1. Remove hardcoded `window.ANIMAID_CONFIG` object
2. Add config loader import:
   ```html
   <script type="module">
       import { initConfig } from './js/config.js';
       await initConfig();
   </script>
   ```
3. Ensure code uses `window.ANIMAID_CONFIG` or `window.API_BASE_URL`
4. Test in both development and production environments

## Testing

### Development
```bash
# Run on localhost
# Should use http://localhost/api
# Should show demo credentials
```

### Production
```bash
# Deploy to production server
# Should use https://animaidsgn.mywire.org/api
# Should NOT show demo credentials
```

## Troubleshooting

**Config not loading:**
- Check browser console for errors
- Verify `js/config.js` is accessible
- Check network tab for `/config.js.php` request

**Wrong API URL:**
- Check `window.ANIMAID_CONFIG.api.baseUrl`
- Verify environment detection is correct
- Override via server config if needed

**Demo credentials showing in production:**
- Check `window.ANIMAID_CONFIG.features.show_demo_credentials`
- Should be `false` in production
- Verify environment detection

---

**Last Updated:** 2025-11-25  
**Version:** 1.0
