# Manuale Utente AnimaID

## Indice
1. [Introduzione](#introduzione)
2. [Iniziare](#iniziare)
3. [Ruoli Utente e Permessi](#ruoli-utente-e-permessi)
4. [Funzionalità Principali](#funzionalità-principali)
5. [Guide ai Moduli](#guide-ai-moduli)
6. [Amministrazione del Sistema](#amministrazione-del-sistema)
7. [Risoluzione dei Problemi](#risoluzione-dei-problemi)

## Introduzione

### Cos'è AnimaID?
AnimaID è una piattaforma di gestione completa progettata specificamente per centri di animazione, laboratori educativi e organizzazioni di attività ricreative. Fornisce un ambiente digitale unificato per coordinare il personale, organizzare attività, gestire le presenze e facilitare la comunicazione tra staff, genitori e famiglie.

### Vantaggi Principali
- **Gestione Centralizzata**: Tutte le operazioni in un'unica piattaforma
- **Supporto Multilingua**: Disponibile in italiano e inglese
- **Accesso Basato sui Ruoli**: Sistema di permessi progressivi
- **Mobile-friendly**: Design responsive che funziona su tutti i dispositivi
- **Estensibile**: Architettura modulare con sistema di applet

## Iniziare

### Requisiti di Sistema
- Browser web moderno (Chrome, Firefox, Safari, Edge)
- Connessione internet
- Per lo staff: Credenziali di accesso valide

### Accesso alla Piattaforma

#### Accesso Pubblico
1. Naviga all'URL AnimaID della tua organizzazione
2. Esplora il portale pubblico senza login
3. Visualizza calendari, eventi e comunicazioni pubbliche

#### Login Staff
1. Vai alla pagina di login (`/login.html`)
2. Inserisci nome utente e password
3. Clicca "Accedi" per accedere alla dashboard

**Credenziali Demo** (se disponibili):
- Nome utente: `admin`
- Password: `Admin123!@#`

### Configurazione Iniziale
1. **Cambia Password Predefinita**: Dopo il primo login, cambia la tua password
2. **Configura Profilo**: Aggiorna le tue informazioni utente
3. **Imposta Preferenze**: Scegli la lingua e il tema preferiti

## Ruoli Utente e Permessi

### Gerarchia dei Ruoli
AnimaID utilizza tag di ruolo cumulativi che forniscono accesso progressivo:

1. **@aiutoanimatore** (Aiuto Animatore)
   - Assistenza base alle attività
   - Visualizzazione degli orari assegnati

2. **@animatore** (Animatore)
   - Tutti i permessi dell'aiuto animatore
   - Gestione dei gruppi di attività
   - Registrazione delle presenze

3. **@responsabile** (Responsabile)
   - Tutti i permessi dell'animatore
   - Coordinamento dello staff
   - Pianificazione delle attività

4. **@organizzatore** (Organizzatore)
   - Tutti i permessi del responsabile
   - Configurazione del sistema
   - Gestione utenti

5. **Amministratore Tecnico**
   - Accesso completo al sistema
   - Configurazione del centro
   - Backup e manutenzione

### Livelli di Permesso
Ogni ruolo ha accesso a moduli e funzioni specifiche in base ai loro tag cumulativi.

## Funzionalità Principali

### Dashboard
La dashboard è il tuo spazio di lavoro centrale dopo il login:

- **Sezione Benvenuto**: Saluto personalizzato e panoramica rapida
- **Carte Statistiche**: Dati in tempo reale su utenti, attività, bambini e report
- **Azioni Rapide**: Collegamenti diretti ai moduli più utilizzati
- **Visualizzazione Permessi**: Mostra i tuoi attuali livelli di accesso

### Navigazione
- Usa la griglia Azioni Rapide per accesso rapido ai moduli
- La navigazione breadcrumb mostra la tua posizione corrente
- Navigazione sidebar (se implementata) per struttura menu dettagliata

## Guide ai Moduli

### Gestione Calendario
**Scopo**: Programmazione e gestione di attività, eventi e turni del personale

**Funzioni Principali**:
- Visualizzazione calendari giornalieri, settimanali e mensili
- Creazione nuove attività ed eventi
- Assegnazione personale alle attività
- Impostazione eventi ricorrenti
- Gestione prenotazioni aule e risorse

**Accesso Utente**:
- Tutto lo staff: Visualizza attività assegnate
- Animatori+: Crea e modifica attività
- Responsabili+: Programma personale e risorse

### Tracciamento Presenze
**Scopo**: Registrazione e monitoraggio delle presenze dei partecipanti

**Funzioni Principali**:
- Sistema check-in/check-out
- Monitoraggio presenze in tempo reale
- Report e statistiche presenze
- Tracciamento assenze e notifiche

**Accesso Utente**:
- Animatori+: Registra presenze per le loro attività
- Responsabili+: Visualizza tutti i dati presenze

### Gestione Bambini
**Scopo**: Gestione profili bambini, registrazioni e informazioni

**Funzioni Principali**:
- Creazione e manutenzione profili bambini
- Gestione registrazioni
- Informazioni mediche e di emergenza
- Contatti genitori/tutori
- Iscrizione attività

**Accesso Utente**:
- Animatori+: Visualizza bambini assegnati
- Responsabili+: Gestione completa bambini

### Gestione Animatori
**Scopo**: Coordinamento e gestione dei membri dello staff

**Funzioni Principali**:
- Gestione profili staff
- Assegnazione ruoli e permessi
- Programmazione disponibilità
- Tracciamento performance
- Comunicazione con lo staff

**Accesso Utente**:
- Responsabili+: Visualizza e gestisce staff
- Organizzatori+: Gestione ruoli e permessi

### Comunicazioni
**Scopo**: Hub di comunicazione interno ed esterno

**Funzioni Principali**:
- Sistema di messaggistica interna
- Annunci pubblici
- Bacheche
- Sistema email e notifiche
- Condivisione media

**Accesso Utente**:
- Tutto lo staff: Invia/riceve messaggi
- Responsabili+: Crea annunci pubblici
- Organizzatori+: Comunicazioni sistema-wide

### Gestione Media
**Scopo**: Organizzare e condividere foto, video e documenti

**Funzioni Principali**:
- Upload e organizzazione file
- Categorizzazione media
- Controllo accessi
- Gestione galleria pubblica
- Condivisione documenti

**Accesso Utente**:
- Animatori+: Upload media per le loro attività
- Responsabili+: Organizza e categorizza media
- Organizzatori+: Approvazione e pubblicazione media

### Wiki / Base di Conoscenza
**Scopo**: Repository centrale per attività, giochi e procedure

**Funzioni Principali**:
- Database attività e giochi
- Funzionalità di ricerca e filtro
- Organizzazione per categorie
- Feedback e valutazioni utenti
- Documentazione procedure

**Accesso Utente**:
- Tutto lo staff: Naviga e cerca
- Animatori+: Contribuisce contenuti
- Responsabili+: Approvazione e organizzazione contenuti

## Amministrazione del Sistema

### Gestione Utenti
**Accesso**: Ruoli Admin e Organizzatore

**Funzioni**:
- Creazione e gestione account utente
- Assegnazione ruoli e permessi
- Reset password
- Gestione gruppi utenti
- Monitoraggio attività utenti

### Gestione Ruoli
**Accesso**: Solo ruolo Admin

**Funzioni**:
- Definizione gerarchie ruoli
- Impostazione livelli permessi
- Configurazione tag cumulativi
- Gestione politiche di accesso

### Stato Sistema
**Accesso**: Ruoli Admin e Organizzatore

**Funzioni**:
- Monitoraggio salute sistema
- Visualizzazione metriche performance
- Controllo stato database
- Revisione log sistema
- Gestione backup

### Report e Analisi
**Accesso**: Responsabili+

**Funzioni**:
- Generazione report attività
- Statistiche presenze
- Report finanziari (se applicabile)
- Esportazione dati in vari formati
- Creazione report personalizzati

## Supporto Multilingua e Tema

### Selezione Lingua
AnimaID supporta più lingue:

1. **Rilevamento Automatico**: Il sistema rileva la lingua del browser
2. **Selezione Manuale**: Usa il selettore lingua nell'header
3. **Preferenza Persistente**: La tua scelta viene salvata per sessioni future

**Lingue Disponibili**:
- Inglese (en)
- Italiano (it)

### Selezione Tema
Scegli tra temi chiaro e scuro:

1. Clicca il pulsante cambia tema (icona sole/luna)
2. La preferenza del tema viene salvata automaticamente
3. Il sistema rispetta le impostazioni dark mode del dispositivo

## Risoluzione dei Problemi

### Problemi Comuni

#### Problemi di Login
- **Password Dimenticata**: Usa il link "Password dimenticata?" (se implementato)
- **Account Bloccato**: Contatta l'amministratore di sistema
- **Credenziali Non Valide**: Verifica nome utente e password

#### Accesso Negato
- Controlla i permessi del tuo ruolo
- Contatta l'amministratore per richieste di accesso
- Verifica di aver effettuato il login con l'account corretto

#### Errori di Sistema
- Ricarica la pagina
- Pulisci cache e cookie del browser
- Controlla connessione internet
- Contatta supporto tecnico se il problema persiste

### Ottenere Aiuto

#### Supporto Interno
- Contatta il tuo amministratore di sistema
- Controlla la wiki interna per documentazione
- Usa il modulo comunicazioni per raggiungere lo staff di supporto

#### Supporto Tecnico
- Log di sistema disponibili per gli amministratori
- Procedure di backup e ripristino
- Programmi di aggiornamento e manutenzione

## Best Practices

### Gestione Dati
- Backup regolari di informazioni importanti
- Mantieni aggiornate le informazioni su bambini e staff
- Usa convenzioni di denominazione consistenti

### Sicurezza
- Usa password forti e uniche
- Effettua il logout quando non usi il sistema
- Segnala attività sospette immediatamente
- Mantieni il sistema aggiornato

### Comunicazione
- Usa canali appropriati per diversi tipi di comunicazione
- Mantieni annunci chiari e concisi
- Aggiornamenti regolari a genitori e staff

## Informazioni Versione

Questo manuale si applica a AnimaID versione 0.9. Caratteristiche e funzionalità potrebbero cambiare in aggiornamenti futuri. Controlla la documentazione del sistema per le informazioni più aggiornate.

---

*Ultimo Aggiornamento: Novembre 2025*  
*Versione AnimaID: 0.9*
