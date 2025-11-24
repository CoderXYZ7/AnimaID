// public/js/themeLanguageSwitcher.js
import { initI18n, changeLanguage, getCurrentLanguage, availableLanguages, t } from '../../src/js/i18n.js';

// Function to initialize theme and language switcher
async function initializeUISwitcher() {
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

    // Language Switcher Logic
    const languageSelectorContainer = document.getElementById('language-selector-container');
    if (languageSelectorContainer) {
        const select = document.createElement('select');
        select.id = 'language-select';
        select.className = 'px-2 py-1 border border-gray-300 rounded-md text-sm focus:outline-none focus:ring-2 focus:ring-blue-500';

        Object.values(availableLanguages).forEach(lang => {
            const option = document.createElement('option');
            option.value = lang.code;
            option.textContent = `${lang.flag} ${lang.name}`;
            if (lang.code === getCurrentLanguage()) {
                option.selected = true;
            }
            select.appendChild(option);
        });

        select.addEventListener('change', async (event) => {
            const newLang = event.target.value;
            if (await changeLanguage(newLang)) {
                // Refresh page to apply changes
                window.location.reload();
            }
        });

        languageSelectorContainer.appendChild(select);
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
function checkAndInitializeSwitchers() {
    if (document.getElementById('theme-switcher') && document.getElementById('language-selector-container')) {
        initializeUISwitcher();
    } else {
        // Check again in a short timeout
        setTimeout(checkAndInitializeSwitchers, 100);
    }
}

// Start checking when DOM is loaded
document.addEventListener('DOMContentLoaded', () => {
    checkAndInitializeSwitchers();
});

// Also check when header.js has finished loading
document.addEventListener('headerLoaded', () => {
    checkAndInitializeSwitchers();
});
