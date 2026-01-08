// public/js/i18nInit.js
// i18n initialization and theme switching logic (without language selector UI)
import { initI18n, getCurrentLanguage, t } from '../src/js/i18n.js';

// Apply saved theme immediately to prevent flash of wrong theme
(function applyThemeImmediately() {
    const savedTheme = localStorage.getItem('theme');
    if (savedTheme === 'dark') {
        document.body.classList.add('dark');
    }
})();

// Track initialization state
let initialized = false;

// Function to initialize i18n and theme
async function initializeI18nAndTheme() {
    if (initialized) return;
    initialized = true;

    // Initialize i18n
    await initI18n();
    applyTranslations();
    document.documentElement.lang = getCurrentLanguage();

    // Dispatch a custom event to signal that i18n is initialized and available globally
    document.dispatchEvent(new CustomEvent('i18nInitialized'));

    // Initialize theme switcher button
    initThemeSwitcher();
}

// Initialize theme switcher button
function initThemeSwitcher() {
    const themeSwitcher = document.getElementById('theme-switcher');
    const body = document.body;

    if (themeSwitcher) {
        // Remove any existing listeners (in case of re-init)
        const newSwitcher = themeSwitcher.cloneNode(true);
        themeSwitcher.parentNode.replaceChild(newSwitcher, themeSwitcher);

        newSwitcher.addEventListener('click', () => {
            body.classList.toggle('dark');
            const isDarkMode = body.classList.contains('dark');
            localStorage.setItem('theme', isDarkMode ? 'dark' : 'light');
            newSwitcher.innerHTML = isDarkMode ? '<i class="fas fa-moon"></i>' : '<i class="fas fa-sun"></i>';
        });

        // Set initial icon based on current theme
        const savedTheme = localStorage.getItem('theme');
        if (savedTheme === 'dark') {
            body.classList.add('dark');
            newSwitcher.innerHTML = '<i class="fas fa-moon"></i>';
        } else {
            body.classList.remove('dark');
            newSwitcher.innerHTML = '<i class="fas fa-sun"></i>';
        }
    }
}

// Function to apply translations to elements with data-i18n attribute
function applyTranslations() {
    document.querySelectorAll('[data-i18n]').forEach(element => {
        const key = element.dataset.i18n;
        element.textContent = t(key);
    });
    document.querySelectorAll('[data-i18n-placeholder]').forEach(element => {
        const key = element.dataset.i18nPlaceholder;
        element.placeholder = t(key);
    });
}

// Initialize after header is loaded - check periodically for UI elements
let checkAttempts = 0;
const maxAttempts = 50; // 5 seconds max

function checkAndInitialize() {
    const themeSwitcher = document.getElementById('theme-switcher');
    if (themeSwitcher || checkAttempts >= maxAttempts) {
        initializeI18nAndTheme();
    } else {
        checkAttempts++;
        setTimeout(checkAndInitialize, 100);
    }
}

// Start checking when DOM is loaded
document.addEventListener('DOMContentLoaded', () => {
    checkAndInitialize();
});

// Also check when header.js has finished loading
document.addEventListener('headerLoaded', () => {
    // Re-init theme switcher after header loads (in case it loads after initial check)
    initThemeSwitcher();
});
