// Note: In vanilla JavaScript, we'll use the CDN version in the HTML
// We'll use the global i18next from the CDN
let i18next = window.i18next;

// Define available languages
export const availableLanguages = {
    en: { name: 'English', code: 'en', flag: '🇺🇸' },
    it: { name: 'Italiano', code: 'it', flag: '🇮🇹' }
};

// Get current language from localStorage or defaults
export const getCurrentLanguage = () => {
    const stored = localStorage.getItem('animaid_language');
    const browserLang = navigator.language.split('-')[0];
    const defaultLang = 'it'; // Default to Italian based on config

    return stored || (availableLanguages[browserLang] ? browserLang : defaultLang);
};

// Translation resources
const resources = {
    en: {
        translation: {
            // Auth/Login
            "auth.login.title": "Sign in to your account",
            "auth.login.subtitle": "Access the AnimaID management platform",
            "auth.login.username": "Username or Email",
            "auth.login.password": "Password",
            "auth.login.button": "Sign in",
            "auth.login.remember": "Remember me",
            "auth.login.forgot": "Forgot your password?",
            "auth.login.loading": "Signing in...",
            "auth.logout": "Logout",
            "auth.login.demo.title": "Demo Credentials",
            "auth.login.demo.username": "Username:",
            "auth.login.demo.password": "Password:",
            "auth.login.demo.note": "Change password after first login",

            // Navigation
            "nav.dashboard": "Dashboard",
            "nav.calendar": "Calendar",
            "nav.attendance": "Attendance",
            "nav.children": "Children",
            "nav.animators": "Animators",
            "nav.communications": "Communications",
            "nav.media": "Media Manager",
            "nav.wiki": "Wiki",
            "nav.reports": "Reports",

            // Common
            "common.loading": "Loading...",
            "common.error": "Error",
            "common.success": "Success",
            "common.cancel": "Cancel",
            "common.save": "Save",
            "common.delete": "Delete",
            "common.edit": "Edit",
            "common.view": "View",
            "common.add": "Add",
            "common.search": "Search",
            "common.filter": "Filter",
            "common.export": "Export",
            "common.import": "Import",

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
            // Auth/Login
            "auth.login.title": "Accedi al tuo account",
            "auth.login.subtitle": "Accedi alla piattaforma di gestione AnimaID",
            "auth.login.username": "Nome utente o Email",
            "auth.login.password": "Password",
            "auth.login.button": "Accedi",
            "auth.login.remember": "Ricordami",
            "auth.login.forgot": "Password dimenticata?",
            "auth.login.loading": "Accesso in corso...",
            "auth.logout": "Esci",
            "auth.login.demo.title": "Credenziali Demo",
            "auth.login.demo.username": "Nome utente:",
            "auth.login.demo.password": "Password:",
            "auth.login.demo.note": "Cambia la password dopo il primo accesso",

            // Navigation
            "nav.dashboard": "Dashboard",
            "nav.calendar": "Calendario",
            "nav.attendance": "Presenze",
            "nav.children": "Bambini",
            "nav.animators": "Animatori",
            "nav.communications": "Comunicazioni",
            "nav.media": "Gestore Media",
            "nav.wiki": "Wiki",
            "nav.reports": "Rapporti",

            // Common
            "common.loading": "Caricamento...",
            "common.error": "Errore",
            "common.success": "Successo",
            "common.cancel": "Annulla",
            "common.save": "Salva",
            "common.delete": "Elimina",
            "common.edit": "Modifica",
            "common.view": "Visualizza",
            "common.add": "Aggiungi",
            "common.search": "Cerca",
            "common.filter": "Filtra",
            "common.export": "Esporta",
            "common.import": "Importa",

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
};

// Initialize i18next
export const initI18n = async () => {
    // Wait for i18next to be available
    if (!i18next) {
        let attempts = 0;
        while (!i18next && attempts < 50) { // Wait up to 5 seconds
            await new Promise(resolve => setTimeout(resolve, 100));
            i18next = window.i18next;
            attempts++;
        }
        if (!i18next) {
            throw new Error('i18next library failed to load');
        }
    }

    await i18next.init({
        lng: getCurrentLanguage(),
        fallbackLng: 'en',
        debug: false,
        resources,
        interpolation: {
            escapeValue: false // React already escapes values
        },
        detection: {
            order: ['localStorage', 'navigator'],
            lookupLocalStorage: 'animaid_language'
        }
    });
};

// Change language
export const changeLanguage = async (lng) => {
    try {
        await i18next.changeLanguage(lng);
        localStorage.setItem('animaid_language', lng);
        return true;
    } catch (error) {
        console.error('Failed to change language:', error);
        return false;
    }
};

// Get translation
export const t = (key, options) => i18next.t(key, options);

// Create language selector
export const createLanguageSelector = () => {
    const selector = document.createElement('div');
    selector.id = 'language-selector';
    selector.className = 'flex items-center space-x-2';

    Object.values(availableLanguages).forEach(lang => {
        const button = document.createElement('button');
        button.className = 'px-3 py-1 text-sm rounded-md border hover:bg-gray-50 transition-colors';
        button.dataset.lang = lang.code;
        button.textContent = `${lang.flag} ${lang.name}`;

        button.addEventListener('click', async () => {
            if (await changeLanguage(lang.code)) {
                // Refresh page to apply changes
                window.location.reload();
            }
        });

        selector.appendChild(button);
    });

    return selector;
};
