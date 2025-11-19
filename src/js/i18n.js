// src/js/i18n.js (modified)

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
            "common.backToDashboard": "Back to Dashboard",
            "common.logout": "Logout",
            "common.status": "Status",
            "common.allStatus": "All Status",
            "common.active": "Active",
            "common.inactive": "Inactive",
            "common.suspended": "Suspended",
            "common.terminated": "Terminated",
            "common.showing": "Showing",
            "common.to": "to",
            "common.of": "of",
            "common.previous": "Previous",
            "common.next": "Next",

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
            "footer.version": "AnimaID v0.9 - Animation Center Management Platform",

            // Animators Management
            "animators.loading": "Loading animators management...",
            "animators.title": "Animators Management",
            "animators.subtitle": "Manage animator profiles, documents, and availability",
            "animators.addAnimator": "Add Animator",
            "animators.searchPlaceholder": "Name or animator #",
            "animators.specialization": "Specialization",
            "animators.specializationPlaceholder": "e.g., Music, Art, Sports",
            "animators.registeredAnimators": "Registered Animators",
            "animators.animators": "animators",
            "animators.addNewAnimator": "Add New Animator",
            "animators.basicInfo": "Basic Information",
            "animators.linkedUsers": "Linked Users",
            "animators.documents": "Documents",
            "animators.notes": "Notes",
            "animators.firstName": "First Name",
            "animators.lastName": "Last Name",
            "animators.birthDate": "Birth Date",
            "animators.gender": "Gender",
            "animators.selectGender": "Select Gender",
            "animators.male": "Male",
            "animators.female": "Female",
            "animators.hireDate": "Hire Date",
            "animators.nationality": "Nationality",
            "animators.language": "Language",
            "animators.education": "Education",
            "animators.address": "Address",
            "animators.phone": "Phone",
            "animators.email": "Email",
            "animators.linkedUserAccounts": "Linked User Accounts",
            "animators.linkUser": "Link User",
            "animators.documentsFiles": "Documents & Files",
            "animators.addDocument": "Add Document",
            "animators.notesObservations": "Notes & Observations",
            "animators.addNote": "Add Note",
            "animators.saveAnimator": "Save Animator",
            "animators.uploadDocument": "Upload Document",
            "animators.selectFile": "Select File",
            "animators.uploadFile": "Upload a file",
            "animators.orDragDrop": "or drag and drop",
            "animators.fileTypes": "PDF, DOC, DOCX, JPG, PNG up to 10MB",
            "animators.documentType": "Document Type",
            "animators.selectDocumentType": "Select Document Type",
            "animators.birthCertificate": "Birth Certificate",
            "animators.medicalForm": "Medical Form",
            "animators.vaccinationRecord": "Vaccination Record",
            "animators.insuranceCard": "Insurance Card",
            "animators.photo": "Photo",
            "animators.registrationForm": "Registration Form",
            "animators.consentForm": "Consent Form",
            "animators.emergencyContact": "Emergency Contact Info",
            "animators.cv": "CV/Resume",
            "animators.certification": "Certification",
            "animators.contract": "Contract",
            "animators.expiryDate": "Expiry Date",
            "animators.notesPlaceholder": "Any additional notes about this document",
            "animators.noteTitle": "Title",
            "animators.noteTitlePlaceholder": "Brief title for this note",
            "animators.noteType": "Note Type",
            "animators.selectNoteType": "Select Note Type",
            "animators.observation": "Observation",
            "animators.incident": "Incident",
            "animators.achievement": "Achievement",
            "animators.medical": "Medical",
            "animators.behavioral": "Behavioral",
            "animators.developmental": "Developmental",
            "animators.social": "Social",
            "animators.academic": "Academic",
            "animators.performance": "Performance",
            "animators.training": "Training",
            "animators.noteContent": "Content",
            "animators.noteContentPlaceholder": "Detailed content of the note",
            "animators.markPrivate": "Mark as private (only staff can view)",
            "animators.saveNote": "Save Note",
            "animators.accessDenied": "Access Denied",
            "animators.accessDeniedMessage": "You don't have permission to access animators management.",

            // Children Management
            "children.loading": "Loading children management...",
            "children.title": "Children Management",
            "children.subtitle": "Manage child registrations, profiles, and documentation",
            "children.addChild": "Add Child",
            "children.searchPlaceholder": "Name or registration #",
            "children.minAge": "Min Age",
            "children.maxAge": "Max Age",
            "children.registeredChildren": "Registered Children",
            "children.pagination": "Showing {{showingFrom}} to {{showingTo}} of {{totalChildren}} children",
            "children.graduated": "Graduated",

            // Attendance Management
            "attendance.loading": "Loading attendance...",
            "attendance.title": "Attendance Management",
            "attendance.subtitle": "Check-in/check-out children and view attendance records",
            "attendance.newChild": "New Child",
            "attendance.quickCheckin": "Quick Check-in",
            "attendance.viewRecords": "View Records",
            "attendance.todaysEvents": "Today's Events",
            "attendance.quickCheckinDescription": "Select an event, then search for children to check them in",
            "attendance.selectEvent": "Select Event *",
            "attendance.chooseEvent": "Choose an event first...",
            "attendance.selectEventHelp": "You must select an event before searching for children",
            "attendance.searchChild": "Search Child",
            "attendance.searchChildPlaceholder": "Enter child name or registration #...",
            "attendance.attendanceRecords": "Attendance Records",
            "attendance.clickViewRecords": "Click \"View Records\" to load attendance records",
            "attendance.pagination": "Showing {{showingFrom}} to {{showingTo}} of {{totalRecords}} records",
            "attendance.checkinChild": "Check-in Child",
            "attendance.confirmCheckin": "Confirm Check-in",
            "attendance.noPermission": "You don't have permission to access attendance management."
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
            "common.backToDashboard": "Torna alla Dashboard",
            "common.logout": "Esci",
            "common.status": "Stato",
            "common.allStatus": "Tutti gli Stati",
            "common.active": "Attivo",
            "common.inactive": "Inattivo",
            "common.suspended": "Sospeso",
            "common.terminated": "Terminato",
            "common.showing": "Mostrando",
            "common.to": "a",
            "common.of": "di",
            "common.previous": "Precedente",
            "common.next": "Successivo",

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
            "footer.version": "AnimaID v0.9 - Piattaforma di Gestione Centro di Animazione",

            // Animators Management - Italian
            "animators.loading": "Caricamento gestione animatori...",
            "animators.title": "Gestione Animatori",
            "animators.subtitle": "Gestisci profili animatori, documenti e disponibilità",
            "animators.addAnimator": "Aggiungi Animatore",
            "animators.searchPlaceholder": "Nome o numero animatore",
            "animators.specialization": "Specializzazione",
            "animators.specializationPlaceholder": "es. Musica, Arte, Sport",
            "animators.registeredAnimators": "Animatori Registrati",
            "animators.animators": "animatori",
            "animators.addNewAnimator": "Aggiungi Nuovo Animatore",
            "animators.basicInfo": "Informazioni Base",
            "animators.linkedUsers": "Utenti Collegati",
            "animators.documents": "Documenti",
            "animators.notes": "Note",
            "animators.firstName": "Nome",
            "animators.lastName": "Cognome",
            "animators.birthDate": "Data di Nascita",
            "animators.gender": "Genere",
            "animators.selectGender": "Seleziona Genere",
            "animators.male": "Maschio",
            "animators.female": "Femmina",
            "animators.hireDate": "Data Assunzione",
            "animators.nationality": "Nazionalità",
            "animators.language": "Lingua",
            "animators.education": "Istruzione",
            "animators.address": "Indirizzo",
            "animators.phone": "Telefono",
            "animators.email": "Email",
            "animators.linkedUserAccounts": "Account Utenti Collegati",
            "animators.linkUser": "Collega Utente",
            "animators.documentsFiles": "Documenti & File",
            "animators.addDocument": "Aggiungi Documento",
            "animators.notesObservations": "Note & Osservazioni",
            "animators.addNote": "Aggiungi Nota",
            "animators.saveAnimator": "Salva Animatore",
            "animators.uploadDocument": "Carica Documento",
            "animators.selectFile": "Seleziona File",
            "animators.uploadFile": "Carica un file",
            "animators.orDragDrop": "o trascina e rilascia",
            "animators.fileTypes": "PDF, DOC, DOCX, JPG, PNG fino a 10MB",
            "animators.documentType": "Tipo Documento",
            "animators.selectDocumentType": "Seleziona Tipo Documento",
            "animators.birthCertificate": "Certificato di Nascita",
            "animators.medicalForm": "Modulo Medico",
            "animators.vaccinationRecord": "Registro Vaccinazioni",
            "animators.insuranceCard": "Tessera Assicurativa",
            "animators.photo": "Foto",
            "animators.registrationForm": "Modulo Iscrizione",
            "animators.consentForm": "Modulo Consenso",
            "animators.emergencyContact": "Info Contatto Emergenza",
            "animators.cv": "CV/Curriculum",
            "animators.certification": "Certificazione",
            "animators.contract": "Contratto",
            "animators.expiryDate": "Data Scadenza",
            "animators.notesPlaceholder": "Eventuali note aggiuntive su questo documento",
            "animators.noteTitle": "Titolo",
            "animators.noteTitlePlaceholder": "Breve titolo per questa nota",
            "animators.noteType": "Tipo Nota",
            "animators.selectNoteType": "Seleziona Tipo Nota",
            "animators.observation": "Osservazione",
            "animators.incident": "Incidente",
            "animators.achievement": "Risultato",
            "animators.medical": "Medico",
            "animators.behavioral": "Comportamentale",
            "animators.developmental": "Sviluppo",
            "animators.social": "Sociale",
            "animators.academic": "Accademico",
            "animators.performance": "Prestazione",
            "animators.training": "Formazione",
            "animators.noteContent": "Contenuto",
            "animators.noteContentPlaceholder": "Contenuto dettagliato della nota",
            "animators.markPrivate": "Segna come privato (solo staff può visualizzare)",
            "animators.saveNote": "Salva Nota",
            "animators.accessDenied": "Accesso Negato",
            "animators.accessDeniedMessage": "Non hai i permessi per accedere alla gestione animatori.",

            // Children Management - Italian
            "children.loading": "Caricamento gestione bambini...",
            "children.title": "Gestione Bambini",
            "children.subtitle": "Gestisci registrazioni, profili e documentazione dei bambini",
            "children.addChild": "Aggiungi Bambino",
            "children.searchPlaceholder": "Nome o numero di registrazione",
            "children.minAge": "Età Minima",
            "children.maxAge": "Età Massima",
            "children.registeredChildren": "Bambini Registrati",
            "children.pagination": "Mostrando {{showingFrom}} a {{showingTo}} di {{totalChildren}} bambini",
            "children.graduated": "Completato",

            // Attendance Management - Italian
            "attendance.loading": "Caricamento presenze...",
            "attendance.title": "Gestione Presenze",
            "attendance.subtitle": "Check-in/check-out bambini e visualizza registri presenze",
            "attendance.newChild": "Nuovo Bambino",
            "attendance.quickCheckin": "Check-in Rapido",
            "attendance.viewRecords": "Visualizza Registri",
            "attendance.todaysEvents": "Eventi di Oggi",
            "attendance.quickCheckinDescription": "Seleziona un evento, poi cerca bambini per registrarli",
            "attendance.selectEvent": "Seleziona Evento *",
            "attendance.chooseEvent": "Scegli un evento prima...",
            "attendance.selectEventHelp": "Devi selezionare un evento prima di cercare bambini",
            "attendance.searchChild": "Cerca Bambino",
            "attendance.searchChildPlaceholder": "Inserisci nome o numero di registrazione...",
            "attendance.attendanceRecords": "Registri Presenze",
            "attendance.clickViewRecords": "Clicca \"Visualizza Registri\" per caricare i registri presenze",
            "attendance.pagination": "Mostrando {{showingFrom}} a {{showingTo}} di {{totalRecords}} registri",
            "attendance.checkinChild": "Check-in Bambino",
            "attendance.confirmCheckin": "Conferma Check-in",
            "attendance.noPermission": "Non hai i permessi per accedere alla gestione presenze."
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

// Removed createLanguageSelector as it will be handled by themeLanguageSwitcher.js
