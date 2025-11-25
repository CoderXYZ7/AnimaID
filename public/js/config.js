// AnimaID Configuration Loader
// This module provides centralized configuration for the frontend

/**
 * Load configuration from the server
 * Falls back to defaults if server config is unavailable
 */
async function loadConfig() {
    try {
        const response = await fetch('/config.js.php');
        if (response.ok) {
            const text = await response.text();
            // Execute the PHP-generated JavaScript
            const configScript = new Function(text + '; return window.ANIMAID_CONFIG;');
            return configScript();
        }
    } catch (error) {
        console.warn('Failed to load server config, using defaults:', error);
    }

    // Fallback to defaults
    return getDefaultConfig();
}

/**
 * Get default configuration
 * Used when server config is unavailable
 */
function getDefaultConfig() {
    // Detect if we're in development or production
    const isDevelopment = window.location.hostname === 'localhost' ||
        window.location.hostname === '127.0.0.1';

    return {
        api: {
            baseUrl: isDevelopment
                ? window.location.origin + '/api'
                : 'https://animaidsgn.mywire.org/api',
            port: isDevelopment ? 80 : 443
        },
        system: {
            name: 'AnimaID',
            version: '0.9',
            environment: isDevelopment ? 'development' : 'production',
            locale: 'it_IT'
        },
        features: {
            show_demo_credentials: isDevelopment, // Only show in development
            user_registration: false,
            password_reset: true,
            email_notifications: false,
            two_factor_auth: false
        }
    };
}

/**
 * Initialize configuration
 * Call this before using any API or config-dependent features
 */
async function initConfig() {
    if (!window.ANIMAID_CONFIG) {
        window.ANIMAID_CONFIG = await loadConfig();

        // Backward compatibility
        window.API_BASE_URL = window.ANIMAID_CONFIG.api.baseUrl;

        console.log('AnimaID Config loaded:', {
            environment: window.ANIMAID_CONFIG.system.environment,
            apiUrl: window.ANIMAID_CONFIG.api.baseUrl
        });
    }
    return window.ANIMAID_CONFIG;
}

// Auto-initialize if not already done
if (typeof window !== 'undefined' && !window.ANIMAID_CONFIG) {
    initConfig();
}

// Export for module usage
export { initConfig, loadConfig, getDefaultConfig };
