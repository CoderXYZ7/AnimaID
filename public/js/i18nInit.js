// public/js/i18nInit.js
// i18n initialization and theme switching logic (without language selector UI)
import { initI18n, getCurrentLanguage, t } from '../src/js/i18n.js';

// Function to initialize i18n and theme
async function initializeI18nAndTheme() {
    // Initialize i18n
    await initI18n();
    applyTranslations();
    document.documentElement.lang = getCurrentLanguage();

    // Dispatch a custom event to signal that i18n is initialized and available globally
    document.dispatchEvent(new CustomEvent('i18nInitialized'));

    // Theme Switcher Logic
    const themeSwitcher = document.getElementById('theme-switcher');
    const body = document.body;

    if (themeSwitcher) {
        themeSwitcher.addEventListener('click', () => {
            body.classList.toggle('dark');
            const isDarkMode = body.classList.contains('dark');
            localStorage.setItem('theme', isDarkMode ? 'dark' : 'light');
            themeSwitcher.innerHTML = isDarkMode ? '<i class="fas fa-moon"></i>' : '<i class="fas fa-sun"></i>';
        });

        // On page load
        const savedTheme = localStorage.getItem('theme');
        if (savedTheme === 'dark') {
            body.classList.add('dark');
            themeSwitcher.innerHTML = '<i class="fas fa-moon"></i>';
        } else {
            themeSwitcher.innerHTML = '<i class="fas fa-sun"></i>';
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
function checkAndInitialize() {
    const themeSwitcher = document.getElementById('theme-switcher');
    if (themeSwitcher) {
        initializeI18nAndTheme();
    } else {
        // Check again in a short timeout
        setTimeout(checkAndInitialize, 100);
    }
}

// Start checking when DOM is loaded
document.addEventListener('DOMContentLoaded', () => {
    checkAndInitialize();
});

// Also check when header.js has finished loading
document.addEventListener('headerLoaded', () => {
    checkAndInitialize();
});
