// public/js/themeLanguageSwitcher.js

// Language and i18n utilities (simplified inline implementation)
const availableLanguages = {
    en: { name: 'English', code: 'en', flag: '🇺🇸' },
    it: { name: 'Italiano', code: 'it', flag: '🇮🇹' }
};

function getCurrentLanguage() {
    const stored = localStorage.getItem('animaid_language');
    const browserLang = navigator.language.split('-')[0];
    const defaultLang = 'it';
    return stored || (availableLanguages[browserLang] ? browserLang : defaultLang);
}

async function changeLanguage(lng) {
    try {
        await window.i18next.changeLanguage(lng);
        localStorage.setItem('animaid_language', lng);
        return true;
    } catch (error) {
        console.error('Failed to change language:', error);
        return false;
    }
}

async function initI18n() {
    if (!window.i18next) {
        console.error('i18next library not loaded');
        return;
    }

    // Check if i18next is already initialized (e.g., by another script)
    if (window.i18next.isInitialized) {
        console.log('i18next already initialized by another script');
        return;
    }

    await window.i18next.init({
        lng: getCurrentLanguage(),
        fallbackLng: 'en',
        debug: false,
        resources: {
            en: {
                translation: {
                    "auth.login.title": "Sign in to your account",
                    "auth.login.subtitle": "Access the AnimaID management platform",
                    "auth.login.username": "Username or Email",
                    "auth.login.password": "Password",
                    "auth.login.button": "Sign in",
                    "auth.login.remember": "Remember me",
                    "auth.login.forgot": "Forgot your password?",
                    "auth.login.demo.title": "Demo Credentials",
                    "auth.login.demo.username": "Username:",
                    "auth.login.demo.password": "Password:",
                    "auth.login.demo.note": "Change password after first login",

                    // Common
                    "common.loading": "Loading...",
                    "common.error": "Error",
                    "common.success": "Success",
                    "common.cancel": "Cancel",
                    "common.save": "Save",
                    "common.backToDashboard": "Back to Dashboard",

                    // Status messages
                    "status.denied": "Access Denied",
                    "status.denied.message": "You need to be logged in to access this page.",
                    "status.denied.button": "Go to Login",

                    // Dashboard
                    "dashboard.welcome": "Welcome to AnimaID",
                    "dashboard.subtitle": "Manage your animation center efficiently with our comprehensive platform.",
                    "dashboard.quick_actions": "Quick Actions",
                    "dashboard.stats.users": "Total Users",
                    "dashboard.stats.activities": "Activities",
                    "dashboard.stats.children": "Children",
                    "dashboard.stats.reports": "Reports",
                    "dashboard.permissions": "Your Permissions",

                    // Footer
                    "footer.version": "AnimaID v0.9 - Animation Center Management Platform"
                }
            },
            it: {
                translation: {
                    "auth.login.title": "Accedi al tuo account",
                    "auth.login.subtitle": "Accedi alla piattaforma di gestione AnimaID",
                    "auth.login.username": "Nome utente o Email",
                    "auth.login.password": "Password",
                    "auth.login.button": "Accedi",
                    "auth.login.remember": "Ricordami",
                    "auth.login.forgot": "Password dimenticata?",
                    "auth.login.demo.title": "Credenziali Demo",
                    "auth.login.demo.username": "Nome utente:",
                    "auth.login.demo.password": "Password:",
                    "auth.login.demo.note": "Cambia la password dopo il primo accesso",

                    // Common
                    "common.loading": "Caricamento...",
                    "common.error": "Errore",
                    "common.success": "Successo",
                    "common.cancel": "Annulla",
                    "common.save": "Salva",
                    "common.backToDashboard": "Torna alla Dashboard",

                    // Status messages
                    "status.denied": "Accesso Negato",
                    "status.denied.message": "Devi essere loggato per accedere a questa pagina.",
                    "status.denied.button": "Vai al Login",

                    // Dashboard
                    "dashboard.welcome": "Benvenuto in AnimaID",
                    "dashboard.subtitle": "Gestisci il tuo centro di animazione in modo efficiente con la nostra piattaforma completa.",
                    "dashboard.quick_actions": "Azioni Rapide",
                    "dashboard.stats.users": "Utenti Totali",
                    "dashboard.stats.activities": "Attività",
                    "dashboard.stats.children": "Bambini",
                    "dashboard.stats.reports": "Rapporti",
                    "dashboard.permissions": "I Tuoi Permessi",

                    // Footer
                    "footer.version": "AnimaID v0.9 - Piattaforma di Gestione Centro di Animazione"
                }
            }
        },
        interpolation: {
            escapeValue: false
        }
    });
}

// Function to initialize theme and language switcher
async function initializeUISwitcher() {
    // Initialize i18n
    await initI18n();
    applyTranslations();
    document.documentElement.lang = getCurrentLanguage();

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
        if (window.i18next) {
            element.textContent = window.i18next.t(key);
        }
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
