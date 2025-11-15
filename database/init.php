<?php

/**
 * AnimaID Database Initialization Script
 * This script creates the SQLite database and populates it with initial data
 */

require_once __DIR__ . '/../config/config.php';

$config = require __DIR__ . '/../config/config.php';

// Create database directory if it doesn't exist
$dbDir = dirname($config['database']['file']);
if (!is_dir($dbDir)) {
    mkdir($dbDir, 0755, true);
}

// Connect to SQLite database (creates file if it doesn't exist)
try {
    $pdo = new PDO('sqlite:' . $config['database']['file']);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);

    echo "Connected to database successfully.\n";

    // Create tables
    createTables($pdo);
    echo "Database tables created.\n";

    // Insert initial data
    insertInitialData($pdo, $config);
    echo "Initial data inserted.\n";

    // Create default admin account
    if ($config['default_admin']['auto_create']) {
        createDefaultAdmin($pdo, $config);
        echo "Default admin account created.\n";
    }

    echo "Database initialization completed successfully!\n";
    echo "Default admin credentials:\n";
    echo "Username: " . $config['default_admin']['username'] . "\n";
    echo "Password: " . $config['default_admin']['password'] . "\n";
    echo "Please change the password immediately after first login.\n";

} catch (PDOException $e) {
    die("Database initialization failed: " . $e->getMessage() . "\n");
}

function createTables(PDO $pdo) {
    // Users table
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS users (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            username VARCHAR(50) UNIQUE NOT NULL,
            email VARCHAR(255) UNIQUE NOT NULL,
            password_hash VARCHAR(255) NOT NULL,
            is_active BOOLEAN DEFAULT 1,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            last_login DATETIME NULL
        )
    ");

    // Roles table
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS roles (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            name VARCHAR(50) UNIQUE NOT NULL,
            display_name VARCHAR(100) NOT NULL,
            description TEXT,
            is_system_role BOOLEAN DEFAULT 0,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP
        )
    ");

    // User roles junction table
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS user_roles (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            user_id INTEGER NOT NULL,
            role_id INTEGER NOT NULL,
            assigned_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            assigned_by INTEGER,
            is_primary BOOLEAN DEFAULT 0,
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
            FOREIGN KEY (role_id) REFERENCES roles(id) ON DELETE CASCADE,
            FOREIGN KEY (assigned_by) REFERENCES users(id),
            UNIQUE(user_id, role_id)
        )
    ");

    // Permissions table
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS permissions (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            name VARCHAR(100) UNIQUE NOT NULL,
            display_name VARCHAR(150) NOT NULL,
            description TEXT,
            module VARCHAR(50) NOT NULL,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP
        )
    ");

    // Role permissions junction table
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS role_permissions (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            role_id INTEGER NOT NULL,
            permission_id INTEGER NOT NULL,
            granted_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            granted_by INTEGER,
            FOREIGN KEY (role_id) REFERENCES roles(id) ON DELETE CASCADE,
            FOREIGN KEY (permission_id) REFERENCES permissions(id) ON DELETE CASCADE,
            FOREIGN KEY (granted_by) REFERENCES users(id),
            UNIQUE(role_id, permission_id)
        )
    ");

    // User sessions table
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS user_sessions (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            user_id INTEGER NOT NULL,
            session_token VARCHAR(255) UNIQUE NOT NULL,
            ip_address VARCHAR(45),
            user_agent TEXT,
            expires_at DATETIME NOT NULL,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
        )
    ");

    // Password resets table
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS password_resets (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            user_id INTEGER NOT NULL,
            reset_token VARCHAR(255) UNIQUE NOT NULL,
            expires_at DATETIME NOT NULL,
            used BOOLEAN DEFAULT 0,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
        )
    ");

    // Calendar events table
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS calendar_events (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            title VARCHAR(255) NOT NULL,
            description TEXT,
            event_type VARCHAR(50) NOT NULL DEFAULT 'activity', -- activity, event, shift, maintenance
            start_date DATE NOT NULL,
            end_date DATE NOT NULL,
            start_time TIME,
            end_time TIME,
            is_all_day BOOLEAN DEFAULT 0,
            location VARCHAR(255),
            max_participants INTEGER,
            age_min INTEGER,
            age_max INTEGER,
            status VARCHAR(20) DEFAULT 'draft', -- draft, published, cancelled, completed
            is_public BOOLEAN DEFAULT 0,
            created_by INTEGER NOT NULL,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (created_by) REFERENCES users(id)
        )
    ");

    // Event participants (children) table
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS event_participants (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            event_id INTEGER NOT NULL,
            child_name VARCHAR(255) NOT NULL,
            child_surname VARCHAR(255) NOT NULL,
            birth_date DATE,
            parent_name VARCHAR(255),
            parent_email VARCHAR(255),
            parent_phone VARCHAR(50),
            emergency_contact VARCHAR(255),
            medical_notes TEXT,
            registration_date DATETIME DEFAULT CURRENT_TIMESTAMP,
            status VARCHAR(20) DEFAULT 'registered', -- registered, confirmed, cancelled, attended
            notes TEXT,
            FOREIGN KEY (event_id) REFERENCES calendar_events(id) ON DELETE CASCADE
        )
    ");

    // Attendance records table
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS attendance_records (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            participant_id INTEGER NOT NULL,
            event_id INTEGER NOT NULL,
            check_in_time DATETIME,
            check_out_time DATETIME,
            check_in_staff INTEGER,
            check_out_staff INTEGER,
            status VARCHAR(20) DEFAULT 'present', -- present, absent, late, early_departure
            notes TEXT,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (participant_id) REFERENCES event_participants(id) ON DELETE CASCADE,
            FOREIGN KEY (event_id) REFERENCES calendar_events(id) ON DELETE CASCADE,
            FOREIGN KEY (check_in_staff) REFERENCES users(id),
            FOREIGN KEY (check_out_staff) REFERENCES users(id)
        )
    ");

    // Spaces/Rooms table
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS spaces (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            name VARCHAR(100) NOT NULL,
            description TEXT,
            capacity INTEGER,
            location VARCHAR(255),
            is_active BOOLEAN DEFAULT 1,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME DEFAULT CURRENT_TIMESTAMP
        )
    ");

    // Space bookings table
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS space_bookings (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            space_id INTEGER NOT NULL,
            event_id INTEGER,
            booked_by INTEGER NOT NULL,
            start_time DATETIME NOT NULL,
            end_time DATETIME NOT NULL,
            purpose VARCHAR(255),
            status VARCHAR(20) DEFAULT 'confirmed', -- pending, confirmed, cancelled
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (space_id) REFERENCES spaces(id) ON DELETE CASCADE,
            FOREIGN KEY (event_id) REFERENCES calendar_events(id) ON DELETE SET NULL,
            FOREIGN KEY (booked_by) REFERENCES users(id)
        )
    ");

    // Children/Registrations table
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS children (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            first_name VARCHAR(100) NOT NULL,
            last_name VARCHAR(100) NOT NULL,
            birth_date DATE,
            gender VARCHAR(20),
            address TEXT,
            phone VARCHAR(50),
            email VARCHAR(255),
            nationality VARCHAR(100),
            language VARCHAR(100),
            school VARCHAR(255),
            grade VARCHAR(50),
            registration_date DATETIME DEFAULT CURRENT_TIMESTAMP,
            status VARCHAR(20) DEFAULT 'active', -- active, inactive, suspended, graduated
            registration_number VARCHAR(50) UNIQUE,
            created_by INTEGER NOT NULL,
            updated_by INTEGER,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (created_by) REFERENCES users(id),
            FOREIGN KEY (updated_by) REFERENCES users(id)
        )
    ");

    // Child medical information
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS child_medical (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            child_id INTEGER NOT NULL,
            blood_type VARCHAR(10),
            allergies TEXT,
            medications TEXT,
            medical_conditions TEXT,
            doctor_name VARCHAR(255),
            doctor_phone VARCHAR(50),
            insurance_provider VARCHAR(255),
            insurance_number VARCHAR(100),
            emergency_contact_name VARCHAR(255),
            emergency_contact_phone VARCHAR(50),
            emergency_contact_relationship VARCHAR(100),
            special_needs TEXT,
            notes TEXT,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (child_id) REFERENCES children(id) ON DELETE CASCADE
        )
    ");

    // Child parents/guardians
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS child_guardians (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            child_id INTEGER NOT NULL,
            relationship VARCHAR(50) NOT NULL, -- mother, father, guardian, etc.
            first_name VARCHAR(100) NOT NULL,
            last_name VARCHAR(100) NOT NULL,
            phone VARCHAR(50),
            mobile VARCHAR(50),
            email VARCHAR(255),
            address TEXT,
            workplace VARCHAR(255),
            work_phone VARCHAR(50),
            is_primary BOOLEAN DEFAULT 0,
            can_pickup BOOLEAN DEFAULT 1,
            emergency_contact BOOLEAN DEFAULT 1,
            notes TEXT,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (child_id) REFERENCES children(id) ON DELETE CASCADE
        )
    ");

    // Child documents and files
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS child_documents (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            child_id INTEGER NOT NULL,
            document_type VARCHAR(50) NOT NULL, -- birth_certificate, medical_form, consent_form, photo, etc.
            file_name VARCHAR(255) NOT NULL,
            original_name VARCHAR(255) NOT NULL,
            file_path VARCHAR(500),
            file_size INTEGER,
            mime_type VARCHAR(100),
            uploaded_by INTEGER NOT NULL,
            expiry_date DATE,
            is_verified BOOLEAN DEFAULT 0,
            verified_by INTEGER,
            verified_at DATETIME,
            notes TEXT,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (child_id) REFERENCES children(id) ON DELETE CASCADE,
            FOREIGN KEY (uploaded_by) REFERENCES users(id),
            FOREIGN KEY (verified_by) REFERENCES users(id)
        )
    ");

    // Child notes and observations
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS child_notes (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            child_id INTEGER NOT NULL,
            note_type VARCHAR(50) NOT NULL, -- observation, incident, achievement, medical, behavioral
            title VARCHAR(255) NOT NULL,
            content TEXT NOT NULL,
            is_private BOOLEAN DEFAULT 0,
            created_by INTEGER NOT NULL,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (child_id) REFERENCES children(id) ON DELETE CASCADE,
            FOREIGN KEY (created_by) REFERENCES users(id)
        )
    ");

    // Child activity history (linking to events)
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS child_activity_history (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            child_id INTEGER NOT NULL,
            event_id INTEGER,
            activity_type VARCHAR(50) NOT NULL, -- event, workshop, outing, etc.
            activity_name VARCHAR(255) NOT NULL,
            activity_date DATE NOT NULL,
            duration_hours DECIMAL(4,2),
            staff_member INTEGER,
            participation_status VARCHAR(20) DEFAULT 'attended', -- attended, absent, partial, excused
            notes TEXT,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (child_id) REFERENCES children(id) ON DELETE CASCADE,
            FOREIGN KEY (event_id) REFERENCES calendar_events(id) ON DELETE SET NULL,
            FOREIGN KEY (staff_member) REFERENCES users(id)
        )
    ");

    // Communications tables
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS communications (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            title VARCHAR(255) NOT NULL,
            content TEXT NOT NULL,
            communication_type VARCHAR(50) NOT NULL, -- notice, announcement, news, alert, message
            priority VARCHAR(20) DEFAULT 'normal', -- low, normal, high, urgent
            is_public BOOLEAN DEFAULT 0, -- 0 = internal staff only, 1 = public/parents
            status VARCHAR(20) DEFAULT 'draft', -- draft, published, archived
            target_audience VARCHAR(100), -- specific roles, all_staff, parents, etc.
            event_id INTEGER, -- link to calendar event if related
            created_by INTEGER NOT NULL,
            published_by INTEGER,
            published_at DATETIME,
            expires_at DATETIME, -- optional expiration date
            view_count INTEGER DEFAULT 0,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (event_id) REFERENCES calendar_events(id) ON DELETE SET NULL,
            FOREIGN KEY (created_by) REFERENCES users(id),
            FOREIGN KEY (published_by) REFERENCES users(id)
        )
    ");

    // Communication attachments (for files/images)
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS communication_attachments (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            communication_id INTEGER NOT NULL,
            file_name VARCHAR(255) NOT NULL,
            original_name VARCHAR(255) NOT NULL,
            file_path VARCHAR(500),
            file_size INTEGER,
            mime_type VARCHAR(100),
            uploaded_by INTEGER NOT NULL,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (communication_id) REFERENCES communications(id) ON DELETE CASCADE,
            FOREIGN KEY (uploaded_by) REFERENCES users(id)
        )
    ");

    // Communication reads/views tracking
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS communication_reads (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            communication_id INTEGER NOT NULL,
            user_id INTEGER, -- NULL for anonymous public views
            ip_address VARCHAR(45),
            user_agent TEXT,
            read_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (communication_id) REFERENCES communications(id) ON DELETE CASCADE,
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
        )
    ");

    // Communication comments/replies
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS communication_comments (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            communication_id INTEGER NOT NULL,
            parent_comment_id INTEGER, -- for threaded replies
            content TEXT NOT NULL,
            is_internal BOOLEAN DEFAULT 1, -- 0 = public comment, 1 = internal staff comment
            created_by INTEGER, -- NULL for anonymous public comments
            author_name VARCHAR(255), -- for anonymous public comments
            author_email VARCHAR(255), -- for anonymous public comments
            status VARCHAR(20) DEFAULT 'approved', -- approved, pending, rejected
            moderated_by INTEGER,
            moderated_at DATETIME,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (communication_id) REFERENCES communications(id) ON DELETE CASCADE,
            FOREIGN KEY (parent_comment_id) REFERENCES communication_comments(id) ON DELETE CASCADE,
            FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL,
            FOREIGN KEY (moderated_by) REFERENCES users(id)
        )
    ");

    // Notification preferences
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS notification_preferences (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            user_id INTEGER NOT NULL,
            communication_type VARCHAR(50) NOT NULL,
            email_enabled BOOLEAN DEFAULT 1,
            push_enabled BOOLEAN DEFAULT 1,
            sms_enabled BOOLEAN DEFAULT 0,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
            UNIQUE(user_id, communication_type)
        )
    ");

    // Media folders table
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS media_folders (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            name VARCHAR(255) NOT NULL,
            description TEXT,
            parent_id INTEGER,
            path VARCHAR(1000) NOT NULL,
            is_shared BOOLEAN DEFAULT 0,
            created_by INTEGER NOT NULL,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (parent_id) REFERENCES media_folders(id) ON DELETE CASCADE,
            FOREIGN KEY (created_by) REFERENCES users(id)
        )
    ");

    // Media files table
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS media_files (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            filename VARCHAR(255) NOT NULL,
            original_name VARCHAR(255) NOT NULL,
            file_path VARCHAR(1000) NOT NULL,
            file_size INTEGER NOT NULL,
            mime_type VARCHAR(100) NOT NULL,
            file_type VARCHAR(50) NOT NULL, -- image, video, document, audio, etc.
            folder_id INTEGER,
            uploaded_by INTEGER NOT NULL,
            is_shared BOOLEAN DEFAULT 0,
            share_token VARCHAR(255) UNIQUE,
            download_count INTEGER DEFAULT 0,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (folder_id) REFERENCES media_folders(id) ON DELETE SET NULL,
            FOREIGN KEY (uploaded_by) REFERENCES users(id)
        )
    ");

    // Media sharing permissions
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS media_sharing (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            resource_type VARCHAR(20) NOT NULL, -- 'file' or 'folder'
            resource_id INTEGER NOT NULL,
            shared_with_user_id INTEGER, -- NULL for public sharing
            shared_by_user_id INTEGER NOT NULL,
            permission VARCHAR(20) DEFAULT 'view', -- view, download, edit
            share_token VARCHAR(255) UNIQUE,
            expires_at DATETIME,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (shared_with_user_id) REFERENCES users(id) ON DELETE CASCADE,
            FOREIGN KEY (shared_by_user_id) REFERENCES users(id)
        )
    ");

    // Media file versions (for version control)
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS media_file_versions (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            file_id INTEGER NOT NULL,
            version_number INTEGER NOT NULL,
            filename VARCHAR(255) NOT NULL,
            file_path VARCHAR(1000) NOT NULL,
            file_size INTEGER NOT NULL,
            uploaded_by INTEGER NOT NULL,
            change_description TEXT,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (file_id) REFERENCES media_files(id) ON DELETE CASCADE,
            FOREIGN KEY (uploaded_by) REFERENCES users(id)
        )
    ");

    // Animators table (similar to children but for staff/animators)
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS animators (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            first_name VARCHAR(100) NOT NULL,
            last_name VARCHAR(100) NOT NULL,
            birth_date DATE,
            gender VARCHAR(20),
            address TEXT,
            phone VARCHAR(50),
            email VARCHAR(255),
            nationality VARCHAR(100),
            language VARCHAR(100),
            education VARCHAR(255),
            specialization VARCHAR(255),
            hire_date DATE,
            status VARCHAR(20) DEFAULT 'active', -- active, inactive, suspended, terminated
            animator_number VARCHAR(50) UNIQUE,
            created_by INTEGER NOT NULL,
            updated_by INTEGER,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (created_by) REFERENCES users(id),
            FOREIGN KEY (updated_by) REFERENCES users(id)
        )
    ");

    // Animator-User relationship table (many-to-many)
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS animator_users (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            animator_id INTEGER NOT NULL,
            user_id INTEGER NOT NULL,
            relationship_type VARCHAR(50) DEFAULT 'primary', -- primary, secondary, backup
            is_active BOOLEAN DEFAULT 1,
            assigned_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            assigned_by INTEGER NOT NULL,
            notes TEXT,
            FOREIGN KEY (animator_id) REFERENCES animators(id) ON DELETE CASCADE,
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
            FOREIGN KEY (assigned_by) REFERENCES users(id),
            UNIQUE(animator_id, user_id)
        )
    ");

    // Animator documents and files
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS animator_documents (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            animator_id INTEGER NOT NULL,
            document_type VARCHAR(50) NOT NULL, -- id_card, diploma, contract, photo, cv, etc.
            file_name VARCHAR(255) NOT NULL,
            original_name VARCHAR(255) NOT NULL,
            file_path VARCHAR(500),
            file_size INTEGER,
            mime_type VARCHAR(100),
            uploaded_by INTEGER NOT NULL,
            expiry_date DATE,
            is_verified BOOLEAN DEFAULT 0,
            verified_by INTEGER,
            verified_at DATETIME,
            notes TEXT,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (animator_id) REFERENCES animators(id) ON DELETE CASCADE,
            FOREIGN KEY (uploaded_by) REFERENCES users(id),
            FOREIGN KEY (verified_by) REFERENCES users(id)
        )
    ");

    // Animator notes and observations
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS animator_notes (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            animator_id INTEGER NOT NULL,
            note_type VARCHAR(50) NOT NULL, -- observation, performance, training, incident, feedback
            title VARCHAR(255) NOT NULL,
            content TEXT NOT NULL,
            is_private BOOLEAN DEFAULT 0,
            created_by INTEGER NOT NULL,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (animator_id) REFERENCES animators(id) ON DELETE CASCADE,
            FOREIGN KEY (created_by) REFERENCES users(id)
        )
    ");

    // Animator activity history (linking to events)
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS animator_activity_history (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            animator_id INTEGER NOT NULL,
            event_id INTEGER,
            activity_type VARCHAR(50) NOT NULL, -- event, workshop, training, meeting, etc.
            activity_name VARCHAR(255) NOT NULL,
            activity_date DATE NOT NULL,
            duration_hours DECIMAL(4,2),
            role VARCHAR(50) NOT NULL, -- lead, assistant, observer, trainer, etc.
            notes TEXT,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (animator_id) REFERENCES animators(id) ON DELETE CASCADE,
            FOREIGN KEY (event_id) REFERENCES calendar_events(id) ON DELETE SET NULL
        )
    ");

    // Wiki pages table
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS wiki_pages (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            title VARCHAR(255) NOT NULL,
            slug VARCHAR(255) UNIQUE NOT NULL,
            content TEXT,
            summary TEXT,
            category_id INTEGER,
            is_published BOOLEAN DEFAULT 1,
            is_featured BOOLEAN DEFAULT 0,
            view_count INTEGER DEFAULT 0,
            created_by INTEGER NOT NULL,
            updated_by INTEGER,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (category_id) REFERENCES wiki_categories(id) ON DELETE SET NULL,
            FOREIGN KEY (created_by) REFERENCES users(id),
            FOREIGN KEY (updated_by) REFERENCES users(id)
        )
    ");

    // Wiki categories table
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS wiki_categories (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            name VARCHAR(100) NOT NULL,
            slug VARCHAR(100) UNIQUE NOT NULL,
            description TEXT,
            color VARCHAR(7) DEFAULT '#3B82F6', -- Hex color code
            icon VARCHAR(50) DEFAULT 'book', -- FontAwesome icon name
            parent_id INTEGER,
            sort_order INTEGER DEFAULT 0,
            is_active BOOLEAN DEFAULT 1,
            created_by INTEGER NOT NULL,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (parent_id) REFERENCES wiki_categories(id) ON DELETE CASCADE,
            FOREIGN KEY (created_by) REFERENCES users(id)
        )
    ");

    // Wiki page tags table
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS wiki_tags (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            name VARCHAR(50) UNIQUE NOT NULL,
            slug VARCHAR(50) UNIQUE NOT NULL,
            color VARCHAR(7) DEFAULT '#6B7280',
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP
        )
    ");

    // Wiki page-tag relationships
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS wiki_page_tags (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            page_id INTEGER NOT NULL,
            tag_id INTEGER NOT NULL,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (page_id) REFERENCES wiki_pages(id) ON DELETE CASCADE,
            FOREIGN KEY (tag_id) REFERENCES wiki_tags(id) ON DELETE CASCADE,
            UNIQUE(page_id, tag_id)
        )
    ");

    // Wiki page revisions table
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS wiki_page_revisions (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            page_id INTEGER NOT NULL,
            title VARCHAR(255) NOT NULL,
            content TEXT,
            summary TEXT,
            change_description TEXT,
            edited_by INTEGER NOT NULL,
            edited_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            revision_number INTEGER NOT NULL,
            word_count INTEGER DEFAULT 0,
            char_count INTEGER DEFAULT 0,
            FOREIGN KEY (page_id) REFERENCES wiki_pages(id) ON DELETE CASCADE,
            FOREIGN KEY (edited_by) REFERENCES users(id)
        )
    ");

    // Wiki page links table (for tracking internal links)
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS wiki_page_links (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            from_page_id INTEGER NOT NULL,
            to_page_id INTEGER,
            link_text VARCHAR(255) NOT NULL,
            link_url VARCHAR(500), -- For external links or manual URLs
            link_type VARCHAR(20) DEFAULT 'internal', -- internal, external, file
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (from_page_id) REFERENCES wiki_pages(id) ON DELETE CASCADE,
            FOREIGN KEY (to_page_id) REFERENCES wiki_pages(id) ON DELETE SET NULL
        )
    ");

    // Wiki page attachments table
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS wiki_page_attachments (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            page_id INTEGER NOT NULL,
            file_name VARCHAR(255) NOT NULL,
            original_name VARCHAR(255) NOT NULL,
            file_path VARCHAR(500) NOT NULL,
            file_size INTEGER NOT NULL,
            mime_type VARCHAR(100) NOT NULL,
            uploaded_by INTEGER NOT NULL,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (page_id) REFERENCES wiki_pages(id) ON DELETE CASCADE,
            FOREIGN KEY (uploaded_by) REFERENCES users(id)
        )
    ");

    // Wiki search index table (for full-text search)
    $pdo->exec("
        CREATE VIRTUAL TABLE IF NOT EXISTS wiki_search_index USING fts5(
            page_id UNINDEXED,
            title,
            content,
            summary,
            tags,
            tokenize = 'porter unicode61'
        )
    ");














}

function insertInitialData(PDO $pdo, array $config) {
    // Insert default roles
    $roles = [
        ['technical_admin', 'Technical Admin', 'Full system access', 1],
        ['organizzatore', 'Organizzatore', 'Center organization', 1],
        ['responsabile', 'Responsabile', 'Department responsibility', 1],
        ['animatore', 'Animatore', 'Activity animator', 1],
        ['aiutoanimatore', 'Aiutoanimatore', 'Assistant animator', 1],
    ];

    $stmt = $pdo->prepare("
        INSERT OR IGNORE INTO roles (name, display_name, description, is_system_role)
        VALUES (?, ?, ?, ?)
    ");

    foreach ($roles as $role) {
        $stmt->execute($role);
    }

    // Insert permissions
    $permissions = [
        // Admin - Users
        ['admin.users.view', 'View Users', 'Can view user list and details', 'admin'],
        ['admin.users.create', 'Create Users', 'Can create new users', 'admin'],
        ['admin.users.edit', 'Edit Users', 'Can edit existing users', 'admin'],
        ['admin.users.delete', 'Delete Users', 'Can delete/deactivate users', 'admin'],

        // Admin - Roles
        ['admin.roles.view', 'View Roles', 'Can view roles and their permissions', 'admin'],
        ['admin.roles.create', 'Create Roles', 'Can create new roles', 'admin'],
        ['admin.roles.edit', 'Edit Roles', 'Can edit existing roles and their permissions', 'admin'],
        ['admin.roles.delete', 'Delete Roles', 'Can delete roles', 'admin'],

        // Admin - System
        ['admin.system.view', 'View System Status', 'Can view system status and configuration', 'admin'],
        ['admin.system.edit', 'Edit System Settings', 'Can edit system settings', 'admin'],
        ['admin.system.backup', 'Perform System Backups', 'Can perform system backups', 'admin'],

        // Registrations - Children
        ['registrations.view', 'View Children', 'Can view children list and details', 'registrations'],
        ['registrations.create', 'Register Children', 'Can register new children', 'registrations'],
        ['registrations.edit', 'Edit Children', 'Can edit child information', 'registrations'],
        ['registrations.delete', 'Delete Children', 'Can delete child records', 'registrations'],

        // Registrations - Child Guardians
        ['registrations.guardians.view', 'View Child Guardians', 'Can view child guardians', 'registrations'],
        ['registrations.guardians.create', 'Add Guardians', 'Can add guardians to children', 'registrations'],
        ['registrations.guardians.edit', 'Edit Guardians', 'Can edit guardian information', 'registrations'],
        ['registrations.guardians.delete', 'Remove Guardians', 'Can remove guardians from children', 'registrations'],

        // Registrations - Child Documents
        ['registrations.documents.view', 'View Child Documents', 'Can view child documents', 'registrations'],
        ['registrations.documents.create', 'Upload Child Documents', 'Can upload documents for children', 'registrations'],
        ['registrations.documents.edit', 'Edit Child Documents', 'Can edit document metadata', 'registrations'],
        ['registrations.documents.delete', 'Delete Child Documents', 'Can delete child documents', 'registrations'],

        // Registrations - Child Notes
        ['registrations.notes.view', 'View Child Notes', 'Can view child notes and observations', 'registrations'],
        ['registrations.notes.create', 'Add Child Notes', 'Can add notes to child records', 'registrations'],
        ['registrations.notes.edit', 'Edit Child Notes', 'Can edit existing notes', 'registrations'],
        ['registrations.notes.delete', 'Delete Child Notes', 'Can delete child notes', 'registrations'],

        // Registrations - Animators
        ['registrations.animators.view', 'View Animators', 'Can view animators list and details', 'registrations'],
        ['registrations.animators.create', 'Register Animators', 'Can register new animators', 'registrations'],
        ['registrations.animators.edit', 'Edit Animators', 'Can edit animator information', 'registrations'],
        ['registrations.animators.delete', 'Delete Animators', 'Can delete animator records', 'registrations'],

        // Registrations - Animator User Linking
        ['registrations.animators.users.view', 'View Animator-User Links', 'Can view animator-user relationships', 'registrations'],
        ['registrations.animators.users.create', 'Link Users to Animators', 'Can link users to animators', 'registrations'],
        ['registrations.animators.users.edit', 'Edit Animator-User Links', 'Can edit animator-user relationships', 'registrations'],
        ['registrations.animators.users.delete', 'Unlink Users from Animators', 'Can unlink users from animators', 'registrations'],

        // Registrations - Animator Documents
        ['registrations.animators.documents.view', 'View Animator Documents', 'Can view animator documents', 'registrations'],
        ['registrations.animators.documents.create', 'Upload Animator Documents', 'Can upload documents for animators', 'registrations'],
        ['registrations.animators.documents.edit', 'Edit Animator Documents', 'Can edit document metadata', 'registrations'],
        ['registrations.animators.documents.delete', 'Delete Animator Documents', 'Can delete animator documents', 'registrations'],

        // Registrations - Animator Notes
        ['registrations.animators.notes.view', 'View Animator Notes', 'Can view animator notes', 'registrations'],
        ['registrations.animators.notes.create', 'Add Animator Notes', 'Can add notes to animator records', 'registrations'],
        ['registrations.animators.notes.edit', 'Edit Animator Notes', 'Can edit existing notes', 'registrations'],
        ['registrations.animators.notes.delete', 'Delete Animator Notes', 'Can delete animator notes', 'registrations'],

        // Registrations - Animator Availability
        ['registrations.animators.availability.view', 'View Animator Availability', 'Can view animator availability', 'registrations'],
        ['registrations.animators.availability.edit', 'Edit Animator Availability', 'Can edit animator availability schedules', 'registrations'],

        // Registrations - Animator Week Types
        ['registrations.animators.weektypes.view', 'View Animator Week Types', 'Can view animator week types', 'registrations'],
        ['registrations.animators.weektypes.create', 'Create Week Types', 'Can create week types for animators', 'registrations'],
        ['registrations.animators.weektypes.edit', 'Edit Week Types', 'Can edit week types', 'registrations'],
        ['registrations.animators.weektypes.delete', 'Delete Week Types', 'Can delete week types', 'registrations'],

        // Registrations - Availability Templates
        ['registrations.templates.view', 'View Availability Templates', 'Can view availability templates', 'registrations'],
        ['registrations.templates.create', 'Create Availability Templates', 'Can create availability templates', 'registrations'],
        ['registrations.templates.edit', 'Edit Availability Templates', 'Can edit availability templates', 'registrations'],
        ['registrations.templates.delete', 'Delete Availability Templates', 'Can delete availability templates', 'registrations'],

        // Calendar
        ['calendar.view', 'View Calendar', 'Can view calendar events', 'calendar'],
        ['calendar.create', 'Create Events', 'Can create new events', 'calendar'],
        ['calendar.edit', 'Edit Events', 'Can edit existing events', 'calendar'],
        ['calendar.delete', 'Delete Events', 'Can delete events', 'calendar'],
        ['calendar.publish', 'Publish Events', 'Can publish events to public calendar', 'calendar'],
        ['calendar.participants.view', 'View Event Participants', 'Can view event participants', 'calendar'],
        ['calendar.participants.manage', 'Manage Event Participants', 'Can manage event registrations', 'calendar'],

        // Attendance
        ['attendance.view', 'View Attendance', 'Can view attendance records', 'attendance'],
        ['attendance.checkin', 'Perform Check-in/Check-out', 'Can perform check-in/check-out operations', 'attendance'],
        ['attendance.edit', 'Edit Attendance', 'Can edit attendance records', 'attendance'],
        ['attendance.delete', 'Delete Attendance', 'Can delete attendance records', 'attendance'],
        ['attendance.report', 'Generate Attendance Reports', 'Can generate attendance reports', 'attendance'],

        // Communications
        ['communications.view', 'View Communications', 'Can view internal communications', 'communications'],
        ['communications.send', 'Send Messages', 'Can send internal messages', 'communications'],
        ['communications.broadcast', 'Send Broadcast Messages', 'Can send broadcast messages', 'communications'],
        ['communications.manage', 'Manage Communications', 'Can manage communication settings', 'communications'],
        ['communications.comments.view', 'View Communication Comments', 'Can view communication comments', 'communications'],
        ['communications.comments.create', 'Add Comments', 'Can add comments to communications', 'communications'],
        ['communications.comments.moderate', 'Moderate Comments', 'Can moderate user comments', 'communications'],

        // Media
        ['media.view', 'View Media', 'Can view media files and folders', 'media'],
        ['media.upload', 'Upload Media', 'Can upload media files', 'media'],
        ['media.edit', 'Edit Media', 'Can edit media metadata and organization', 'media'],
        ['media.delete', 'Delete Media', 'Can delete media files and folders', 'media'],
        ['media.share', 'Share Media', 'Can create share links for media resources', 'media'],

        // Spaces
        ['spaces.view', 'View Spaces', 'Can view spaces and bookings', 'spaces'],
        ['spaces.book', 'Book Spaces', 'Can create space bookings', 'spaces'],
        ['spaces.edit', 'Edit Space Bookings', 'Can edit space bookings', 'spaces'],
        ['spaces.manage', 'Manage Spaces', 'Can manage space configurations', 'spaces'],

        // Reports
        ['reports.view', 'View Reports', 'Can view reports and KPIs', 'reports'],
        ['reports.generate', 'Generate Reports', 'Can generate custom reports', 'reports'],
        ['reports.export', 'Export Reports', 'Can export report data', 'reports'],

        // Wiki
        ['wiki.view', 'View Wiki', 'Can view wiki content', 'wiki'],
        ['wiki.create', 'Create Wiki Pages', 'Can create new wiki pages', 'wiki'],
        ['wiki.edit', 'Edit Wiki Pages', 'Can edit existing wiki pages', 'wiki'],
        ['wiki.moderate', 'Moderate Wiki', 'Can moderate user contributions and manage categories', 'wiki'],
        ['wiki.categories.view', 'View Wiki Categories', 'Can view wiki categories', 'wiki'],
        ['wiki.categories.create', 'Create Wiki Categories', 'Can create new wiki categories', 'wiki'],
        ['wiki.categories.edit', 'Edit Wiki Categories', 'Can edit existing wiki categories', 'wiki'],
        ['wiki.categories.delete', 'Delete Wiki Categories', 'Can delete wiki categories', 'wiki'],
        ['wiki.tags.view', 'View Wiki Tags', 'Can view wiki tags', 'wiki'],
        ['wiki.tags.create', 'Create Wiki Tags', 'Can create new wiki tags', 'wiki'],
        ['wiki.tags.edit', 'Edit Wiki Tags', 'Can edit existing wiki tags', 'wiki'],
        ['wiki.tags.delete', 'Delete Wiki Tags', 'Can delete wiki tags', 'wiki'],
    ];

    $stmt = $pdo->prepare("
        INSERT OR IGNORE INTO permissions (name, display_name, description, module)
        VALUES (?, ?, ?, ?)
    ");

    foreach ($permissions as $permission) {
        $stmt->execute($permission);
    }

    // Assign permissions to roles
    assignPermissionsToRoles($pdo);

    echo "Role permissions assigned.\n";

    // Insert sample spaces
    insertSampleSpaces($pdo);

    // Insert sample calendar events
    insertSampleEvents($pdo);

    // Insert sample communications
    insertSampleCommunications($pdo);

    // Insert sample wiki data
    insertSampleWikiData($pdo);


}

function assignPermissionsToRoles(PDO $pdo) {
    // Get role IDs (name => id mapping)
    $roles = $pdo->query("SELECT name, id FROM roles")->fetchAll(PDO::FETCH_KEY_PAIR);

    // Get permission IDs (name => id mapping)
    $permissions = $pdo->query("SELECT name, id FROM permissions")->fetchAll(PDO::FETCH_KEY_PAIR);

    // Define role-permission mappings
    $rolePermissions = [
        'technical_admin' => array_keys($permissions), // All permissions - explicitly assign all permissions to admin

        'organizzatore' => [
            // Admin - System (limited)
            'admin.system.view',
            // Registrations - Full access
            'registrations.view', 'registrations.create', 'registrations.edit', 'registrations.delete',
            'registrations.guardians.view', 'registrations.guardians.create', 'registrations.guardians.edit', 'registrations.guardians.delete',
            'registrations.documents.view', 'registrations.documents.create', 'registrations.documents.edit', 'registrations.documents.delete',
            'registrations.notes.view', 'registrations.notes.create', 'registrations.notes.edit', 'registrations.notes.delete',
            'registrations.animators.view', 'registrations.animators.create', 'registrations.animators.edit', 'registrations.animators.delete',
            'registrations.animators.users.view', 'registrations.animators.users.create', 'registrations.animators.users.edit', 'registrations.animators.users.delete',
            'registrations.animators.documents.view', 'registrations.animators.documents.create', 'registrations.animators.documents.edit', 'registrations.animators.documents.delete',
            'registrations.animators.notes.view', 'registrations.animators.notes.create', 'registrations.animators.notes.edit', 'registrations.animators.notes.delete',
            'registrations.animators.availability.view', 'registrations.animators.availability.edit',
            'registrations.animators.weektypes.view', 'registrations.animators.weektypes.create', 'registrations.animators.weektypes.edit', 'registrations.animators.weektypes.delete',
            'registrations.templates.view', 'registrations.templates.create', 'registrations.templates.edit', 'registrations.templates.delete',
            // Calendar - Full access
            'calendar.view', 'calendar.create', 'calendar.edit', 'calendar.delete', 'calendar.publish', 'calendar.participants.view', 'calendar.participants.manage',
            // Attendance - Full access
            'attendance.view', 'attendance.checkin', 'attendance.edit', 'attendance.delete', 'attendance.report',
            // Communications - Full access
            'communications.view', 'communications.send', 'communications.broadcast', 'communications.manage',
            'communications.comments.view', 'communications.comments.create', 'communications.comments.moderate',
            // Media - Full access
            'media.view', 'media.upload', 'media.edit', 'media.delete', 'media.share',
            // Wiki - Full access
            'wiki.view', 'wiki.edit', 'wiki.create', 'wiki.moderate',
            'wiki.categories.view', 'wiki.categories.create', 'wiki.categories.edit', 'wiki.categories.delete',
            'wiki.tags.view', 'wiki.tags.create', 'wiki.tags.edit', 'wiki.tags.delete',
            // Spaces - Full access
            'spaces.view', 'spaces.book', 'spaces.edit', 'spaces.manage',
            // Reports - Full access
            'reports.view', 'reports.generate', 'reports.export',
        ],

        'responsabile' => [
            // Registrations - View and edit
            'registrations.view', 'registrations.edit',
            'registrations.guardians.view', 'registrations.guardians.edit',
            'registrations.documents.view', 'registrations.documents.edit',
            'registrations.notes.view', 'registrations.notes.create', 'registrations.notes.edit',
            'registrations.animators.view', 'registrations.animators.edit',
            'registrations.animators.users.view', 'registrations.animators.users.edit',
            'registrations.animators.documents.view', 'registrations.animators.documents.edit',
            'registrations.animators.notes.view', 'registrations.animators.notes.create', 'registrations.animators.notes.edit',
            'registrations.animators.availability.view', 'registrations.animators.availability.edit',
            'registrations.animators.weektypes.view', 'registrations.animators.weektypes.edit',
            'registrations.templates.view',
            // Calendar - Create and edit
            'calendar.view', 'calendar.create', 'calendar.edit', 'calendar.participants.view', 'calendar.participants.manage',
            // Attendance - Full access
            'attendance.view', 'attendance.checkin', 'attendance.edit', 'attendance.delete', 'attendance.report',
            // Communications - Send and view
            'communications.view', 'communications.send',
            'communications.comments.view', 'communications.comments.create',
            // Media - Upload and view
            'media.view', 'media.upload', 'media.edit',
            // Wiki - Edit and view
            'wiki.view', 'wiki.edit',
            // Spaces - Book and view
            'spaces.view', 'spaces.book', 'spaces.edit',
            // Reports - View and generate
            'reports.view', 'reports.generate',
        ],

        'animatore' => [
            // Registrations - View only
            'registrations.view',
            'registrations.guardians.view',
            'registrations.documents.view',
            'registrations.notes.view',
            'registrations.animators.view',
            'registrations.animators.users.view',
            'registrations.animators.documents.view',
            'registrations.animators.notes.view',
            'registrations.animators.availability.view',
            'registrations.animators.weektypes.view',
            'registrations.templates.view',
            // Calendar - View and limited edit
            'calendar.view', 'calendar.edit', 'calendar.participants.view',
            // Attendance - Check-in/out and view
            'attendance.view', 'attendance.checkin', 'attendance.edit',
            // Communications - Send and view
            'communications.view', 'communications.send',
            'communications.comments.view', 'communications.comments.create',
            // Media - Upload and view
            'media.view', 'media.upload',
            // Wiki - Edit and view
            'wiki.view', 'wiki.edit',
            // Spaces - Book and view
            'spaces.view', 'spaces.book',
        ],

        'aiutoanimatore' => [
            // Registrations - View only
            'registrations.view',
            'registrations.guardians.view',
            'registrations.documents.view',
            'registrations.notes.view',
            'registrations.animators.view',
            'registrations.animators.users.view',
            'registrations.animators.documents.view',
            'registrations.animators.notes.view',
            'registrations.animators.availability.view',
            'registrations.animators.weektypes.view',
            'registrations.templates.view',
            // Calendar - View only
            'calendar.view', 'calendar.participants.view',
            // Attendance - Check-in and view
            'attendance.view', 'attendance.checkin',
            // Communications - View only
            'communications.view',
            'communications.comments.view',
            // Media - View only
            'media.view',
            // Wiki - View only
            'wiki.view',
            // Spaces - View only
            'spaces.view',
        ],
    ];

    // Clear existing role permissions to ensure clean assignment
    foreach ($roles as $roleId) {
        $pdo->exec("DELETE FROM role_permissions WHERE role_id = " . $roleId);
    }

    $stmt = $pdo->prepare("
        INSERT INTO role_permissions (role_id, permission_id)
        VALUES (?, ?)
    ");

    foreach ($rolePermissions as $roleName => $permissionNames) {
        if (!isset($roles[$roleName])) continue;

        $roleId = $roles[$roleName];

        foreach ($permissionNames as $permissionName) {
            if (!isset($permissions[$permissionName])) continue;

            $permissionId = $permissions[$permissionName];
            $stmt->execute([$roleId, $permissionId]);
        }
    }
}

function insertSampleWikiData(PDO $pdo) {
    // Get admin user ID (assuming admin user exists)
    $adminId = $pdo->query("SELECT id FROM users WHERE username = 'admin' LIMIT 1")->fetchColumn();

    if (!$adminId) {
        // If no admin user, skip sample wiki data
        return;
    }

    // Insert sample wiki categories
    $categories = [
        ['Getting Started', 'getting-started', 'Basic information for new staff members', '#10B981', 'rocket', null, 1, $adminId],
        ['Activities & Games', 'activities-games', 'Activity planning and game ideas', '#3B82F6', 'gamepad', null, 2, $adminId],
        ['Safety & Procedures', 'safety-procedures', 'Safety protocols and emergency procedures', '#EF4444', 'shield-alt', null, 3, $adminId],
        ['Child Development', 'child-development', 'Child development and educational approaches', '#8B5CF6', 'brain', null, 4, $adminId],
        ['Administrative', 'administrative', 'Administrative procedures and policies', '#F59E0B', 'clipboard-list', null, 5, $adminId],
        ['Indoor Activities', 'indoor-activities', 'Indoor activity ideas and planning', '#06B6D4', 'home', 2, 6, $adminId],
        ['Outdoor Activities', 'outdoor-activities', 'Outdoor activity ideas and planning', '#10B981', 'sun', 2, 7, $adminId],
    ];

    $stmt = $pdo->prepare("
        INSERT OR IGNORE INTO wiki_categories
        (name, slug, description, color, icon, parent_id, sort_order, created_by)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?)
    ");

    foreach ($categories as $category) {
        $stmt->execute($category);
    }

    // Insert sample wiki tags
    $tags = [
        ['beginner', 'beginner', '#10B981'],
        ['advanced', 'advanced', '#EF4444'],
        ['safety', 'safety', '#F59E0B'],
        ['emergency', 'emergency', '#DC2626'],
        ['activity', 'activity', '#3B82F6'],
        ['game', 'game', '#8B5CF6'],
        ['planning', 'planning', '#06B6D4'],
        ['development', 'development', '#10B981'],
    ];

    $stmt = $pdo->prepare("
        INSERT OR IGNORE INTO wiki_tags (name, slug, color)
        VALUES (?, ?, ?)
    ");

    foreach ($tags as $tag) {
        $stmt->execute($tag);
    }

    // Insert sample wiki pages
    $pages = [
        [
            'Welcome to the Wiki',
            'welcome',
            '# Welcome to the AnimaID Wiki

This wiki serves as a comprehensive knowledge base for our animation center. Here you\'ll find information about activities, procedures, safety protocols, and best practices.

## Getting Started

If you\'re new to our center, start by reading the [Getting Started](getting-started) section.

## Quick Links

- [Safety Procedures](safety-procedures)
- [Activity Planning](activities-games)
- [Emergency Protocols](emergency-protocols)

## How to Use This Wiki

- Use the search function to find specific information
- Browse by categories using the sidebar
- Click on links to navigate between related topics
- Edit pages to add new information or update existing content

## Contributing

All staff members can contribute to this wiki. Please ensure information is accurate and follows our center\'s guidelines.',
            'Introduction to the wiki system and navigation guide',
            null, // category_id will be set after categories are inserted
            1, // is_published
            1, // is_featured
            0, // view_count
            $adminId,
            null // updated_by
        ],
        [
            'Daily Routine Overview',
            'daily-routine',
            '# Daily Routine Overview

## Morning Schedule (8:00 AM - 12:00 PM)

- **8:00-8:30**: Staff arrival and preparation
- **8:30-9:00**: Welcome and free play
- **9:00-9:30**: Morning circle time
- **9:30-10:30**: Planned activities
- **10:30-11:00**: Snack time
- **11:00-11:45**: Outdoor play (weather permitting)
- **11:45-12:00**: Story time and wind down

## Afternoon Schedule (12:00 PM - 4:00 PM)

- **12:00-12:30**: Lunch
- **12:30-1:00**: Quiet time/rest
- **1:00-2:00**: Afternoon activities
- **2:00-2:30**: Snack time
- **2:30-3:30**: Free choice activities
- **3:30-4:00**: Departure preparations

## Key Considerations

- Always maintain child-to-staff ratios
- Adapt activities based on children\'s energy levels
- Weather conditions may affect outdoor activities
- Special needs children may require modified schedules',
            'Complete overview of daily activities and schedule',
            null, // category_id
            1,
            0,
            0,
            $adminId,
            null
        ],
        [
            'Emergency Procedures',
            'emergency-procedures',
            '# Emergency Procedures

## Medical Emergencies

### Immediate Response
1. Ensure child safety first
2. Call for medical assistance (112)
3. Notify parents immediately
4. Document incident in child\'s file

### Common Medical Issues
- **Allergic Reactions**: Use EpiPen if available, call emergency services
- **Asthma Attacks**: Use inhaler, monitor breathing, seek medical help if severe
- **Fever**: Monitor temperature, contact parents, administer medication if authorized
- **Minor Injuries**: Clean wound, apply bandage, document incident

## Fire Emergency

### Evacuation Procedure
1. Sound fire alarm
2. Evacuate all children to designated assembly point
3. Take attendance using emergency roll
4. Do not re-enter building until declared safe
5. Contact emergency services

### Fire Prevention
- Check electrical equipment regularly
- Keep flammable materials stored safely
- Teach children about fire safety
- Maintain clear exit routes

## Weather Emergencies

### Severe Weather
- Monitor weather alerts
- Move children to interior rooms if needed
- Cancel outdoor activities if conditions are dangerous
- Keep parents informed of schedule changes

## Missing Child

### Immediate Actions
1. Alert all staff immediately
2. Search facility thoroughly
3. Check perimeter and nearby areas
4. Contact parents and authorities if not found within 5 minutes
5. Document all actions taken

## Contact Information

- Emergency Services: 112
- Local Hospital: [Hospital Name] - [Phone Number]
- Poison Control: [Poison Control Number]
- Center Director: [Director Name] - [Phone Number]',
            'Complete emergency response procedures and contact information',
            null, // category_id
            1,
            1,
            0,
            $adminId,
            null
        ],
        [
            'Activity Planning Guide',
            'activity-planning',
            '# Activity Planning Guide

## Planning Principles

### Age-Appropriate Activities
- **2-3 years**: Focus on sensory experiences, basic motor skills, simple games
- **4-5 years**: Introduce group activities, basic crafts, simple sports
- **6-8 years**: Complex games, team activities, creative projects
- **9-12 years**: Leadership activities, advanced crafts, community projects

### Developmental Goals
- Physical development
- Social skills
- Cognitive development
- Emotional development
- Creative expression

## Activity Categories

### Creative Arts
- Painting and drawing
- Music and movement
- Drama and role-play
- Craft projects

### Physical Activities
- Gross motor games
- Fine motor activities
- Sports and games
- Outdoor exploration

### Educational Activities
- Science experiments
- Math games
- Language activities
- Cultural exploration

### Social Activities
- Group games
- Team building
- Community projects
- Friendship activities

## Planning Checklist

- [ ] Age-appropriate materials
- [ ] Safety considerations
- [ ] Required adult supervision
- [ ] Space and equipment needs
- [ ] Time requirements
- [ ] Cleanup requirements
- [ ] Extension activities
- [ ] Assessment methods

## Seasonal Planning

### Fall Activities
- Leaf rubbings and nature crafts
- Harvest themes
- Indoor/outdoor transitions

### Winter Activities
- Holiday crafts
- Indoor physical activities
- Warm weather alternatives

### Spring Activities
- Garden and plant activities
- Outdoor games
- Weather-dependent planning

### Summer Activities
- Water play
- Extended outdoor time
- Travel and excursion planning',
            'Comprehensive guide for planning age-appropriate activities',
            null, // category_id
            1,
            0,
            0,
            $adminId,
            null
        ],
    ];

    // Get category IDs for assignment
    $categoryIds = $pdo->query("SELECT slug, id FROM wiki_categories")->fetchAll(PDO::FETCH_KEY_PAIR);

    // Update pages with category IDs
    $pages[0][4] = $categoryIds['getting-started'] ?? null; // Welcome page
    $pages[1][4] = $categoryIds['getting-started'] ?? null; // Daily routine
    $pages[2][4] = $categoryIds['safety-procedures'] ?? null; // Emergency procedures
    $pages[3][4] = $categoryIds['activities-games'] ?? null; // Activity planning

    $stmt = $pdo->prepare("
        INSERT OR IGNORE INTO wiki_pages
        (title, slug, content, summary, category_id, is_published, is_featured, view_count, created_by, updated_by)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
    ");

    foreach ($pages as $page) {
        $stmt->execute($page);
    }

    // Insert sample tags for pages
    $pageTags = [
        [1, 1], // Welcome page - beginner
        [2, 1], // Daily routine - beginner
        [2, 7], // Daily routine - planning
        [3, 3], // Emergency procedures - safety
        [3, 4], // Emergency procedures - emergency
        [4, 5], // Activity planning - activity
        [4, 7], // Activity planning - planning
        [4, 8], // Activity planning - development
    ];

    $stmt = $pdo->prepare("
        INSERT OR IGNORE INTO wiki_page_tags (page_id, tag_id)
        VALUES (?, ?)
    ");

    foreach ($pageTags as $tagRelation) {
        $stmt->execute($tagRelation);
    }

    // Update search index
    updateWikiSearchIndex($pdo);

    echo "Sample wiki data inserted.\n";
}

function updateWikiSearchIndex(PDO $pdo) {
    // Get all wiki pages with their tags
    $pages = $pdo->query("
        SELECT wp.id, wp.title, wp.content, wp.summary,
               GROUP_CONCAT(wt.name) as tags
        FROM wiki_pages wp
        LEFT JOIN wiki_page_tags wpt ON wp.id = wpt.page_id
        LEFT JOIN wiki_tags wt ON wpt.tag_id = wt.id
        WHERE wp.is_published = 1
        GROUP BY wp.id
    ")->fetchAll(PDO::FETCH_ASSOC);

    // Insert into search index
    $stmt = $pdo->prepare("
        INSERT OR REPLACE INTO wiki_search_index (page_id, title, content, summary, tags)
        VALUES (?, ?, ?, ?, ?)
    ");

    foreach ($pages as $page) {
        $stmt->execute([
            $page['id'],
            $page['title'],
            $page['content'],
            $page['summary'] ?? '',
            $page['tags'] ?? ''
        ]);
    }
}



function insertSampleCommunications(PDO $pdo) {
    // Get admin user ID (assuming admin user exists)
    $adminId = $pdo->query("SELECT id FROM users WHERE username = 'admin' LIMIT 1")->fetchColumn();

    if (!$adminId) {
        // If no admin user, skip sample communications
        return;
    }

    $communications = [
        [
            'Welcome to AnimaID!',
            'Welcome to our new digital management platform. This system will help us coordinate activities, manage registrations, and communicate more effectively with families. Please take a moment to familiarize yourself with the interface.',
            'announcement',
            'high',
            0, // internal staff only
            'published',
            'all_staff',
            null, // no event link
            $adminId,
            $adminId,
            date('Y-m-d H:i:s'), // published immediately
            null, // no expiration
            0
        ],
        [
            'New Registration Process',
            'We have updated our registration process to be more streamlined. Parents can now register their children online and upload required documents digitally. All registrations require approval before children can participate in activities.',
            'notice',
            'normal',
            0, // internal staff only
            'published',
            'organizzatore,responsabile',
            null,
            $adminId,
            $adminId,
            date('Y-m-d H:i:s'),
            null,
            0
        ],
        [
            'Parent Information: Holiday Schedule',
            'Dear Parents, We would like to inform you that the center will be closed from December 24th to January 6th for the holiday season. Regular activities will resume on January 7th. Happy Holidays!',
            'news',
            'normal',
            1, // public
            'published',
            'parents',
            null,
            $adminId,
            $adminId,
            date('Y-m-d H:i:s'),
            date('Y-m-d H:i:s', strtotime('+30 days')), // expires in 30 days
            0
        ],
        [
            'Staff Meeting - Tomorrow 9:00 AM',
            'Important staff meeting tomorrow at 9:00 AM in the Main Activity Room. We will discuss the upcoming holiday activities and review safety protocols. Attendance is mandatory for all staff members.',
            'alert',
            'high',
            0, // internal staff only
            'published',
            'all_staff',
            null,
            $adminId,
            $adminId,
            date('Y-m-d H:i:s'),
            date('Y-m-d H:i:s', strtotime('+1 day')), // expires tomorrow
            0
        ],
        [
            'Photo Day Reminder',
            'This Friday is our monthly photo day! Please remind parents to dress their children in their best clothes. Professional photos will be taken between 10:00 AM and 12:00 PM in the Art Studio.',
            'announcement',
            'normal',
            0, // internal staff only
            'published',
            'animatore,aiutoanimatore',
            null,
            $adminId,
            $adminId,
            date('Y-m-d H:i:s'),
            null,
            0
        ],
        [
            'Medical Emergency Protocol Update',
            'We have updated our medical emergency protocols. Please review the new procedures in the staff handbook. Key changes include: immediate notification to parents, updated emergency contact procedures, and enhanced documentation requirements.',
            'alert',
            'urgent',
            0, // internal staff only
            'published',
            'all_staff',
            null,
            $adminId,
            $adminId,
            date('Y-m-d H:i:s'),
            null,
            0
        ]
    ];

    $stmt = $pdo->prepare("
        INSERT OR IGNORE INTO communications
        (title, content, communication_type, priority, is_public, status, target_audience, event_id, created_by, published_by, published_at, expires_at, view_count)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
    ");

    foreach ($communications as $comm) {
        $stmt->execute($comm);
    }

    // Add some sample comments to the first communication
    $commId = $pdo->query("SELECT id FROM communications WHERE title = 'Welcome to AnimaID!' LIMIT 1")->fetchColumn();

    if ($commId) {
        $comments = [
            [
                $commId,
                null, // no parent comment
                'Great! This looks much more organized than our previous system.',
                1, // internal comment
                $adminId,
                null,
                null,
                'approved',
                null,
                null
            ],
            [
                $commId,
                1, // reply to first comment
                'Agreed! The calendar integration will be very helpful for planning.',
                1, // internal comment
                $adminId,
                null,
                null,
                'approved',
                null,
                null
            ]
        ];

        $stmt = $pdo->prepare("
            INSERT OR IGNORE INTO communication_comments
            (communication_id, parent_comment_id, content, is_internal, created_by, author_name, author_email, status, moderated_by, moderated_at)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");

        foreach ($comments as $comment) {
            $stmt->execute($comment);
        }
    }
}

function createDefaultAdmin(PDO $pdo, array $config) {
    // Check if admin already exists
    $stmt = $pdo->prepare("SELECT id FROM users WHERE username = ?");
    $stmt->execute([$config['default_admin']['username']]);

    if ($stmt->fetch()) {
        echo "Default admin account already exists.\n";
        return;
    }

    // Create admin user
    $passwordHash = password_hash($config['default_admin']['password'], PASSWORD_BCRYPT, [
        'cost' => $config['security']['bcrypt_cost']
    ]);

    $stmt = $pdo->prepare("
        INSERT INTO users (username, email, password_hash)
        VALUES (?, ?, ?)
    ");
    $stmt->execute([
        $config['default_admin']['username'],
        $config['default_admin']['email'],
        $passwordHash
    ]);

    $adminId = $pdo->lastInsertId();

    // Assign technical admin role
    $stmt = $pdo->prepare("
        INSERT INTO user_roles (user_id, role_id, is_primary)
        SELECT ?, id, 1 FROM roles WHERE name = 'technical_admin'
    ");
    $stmt->execute([$adminId]);

    echo "Default admin account created with ID: $adminId\n";
}

function insertSampleSpaces(PDO $pdo) {
    $spaces = [
        ['Main Activity Room', 'Large room for group activities and games', 25, 'Building A - Ground Floor'],
        ['Art Studio', 'Creative space for painting and crafts', 15, 'Building A - First Floor'],
        ['Music Room', 'Space for music activities and singing', 12, 'Building A - First Floor'],
        ['Outdoor Playground', 'External play area with equipment', 30, 'Garden Area'],
        ['Library', 'Reading and quiet activities space', 10, 'Building B - Ground Floor'],
        ['Gym', 'Physical activities and sports', 20, 'Building B - Basement'],
    ];

    $stmt = $pdo->prepare("
        INSERT OR IGNORE INTO spaces (name, description, capacity, location)
        VALUES (?, ?, ?, ?)
    ");

    foreach ($spaces as $space) {
        $stmt->execute($space);
    }
}

function insertSampleEvents(PDO $pdo) {
    // Get admin user ID (assuming admin user exists)
    $adminId = $pdo->query("SELECT id FROM users WHERE username = 'admin' LIMIT 1")->fetchColumn();

    if (!$adminId) {
        // If no admin user, skip sample events
        return;
    }

    $events = [
        [
            'Welcome Day Activities',
            'First day activities for new children and families',
            'event',
            date('Y-m-d', strtotime('+7 days')),
            date('Y-m-d', strtotime('+7 days')),
            '09:00',
            '12:00',
            0,
            'Main Activity Room',
            20,
            3,
            6,
            'published',
            1,
            $adminId
        ],
        [
            'Art Workshop',
            'Creative painting and crafts session',
            'activity',
            date('Y-m-d', strtotime('+10 days')),
            date('Y-m-d', strtotime('+10 days')),
            '14:00',
            '16:00',
            0,
            'Art Studio',
            12,
            4,
            8,
            'published',
            1,
            $adminId
        ],
        [
            'Music and Movement',
            'Songs, dances and rhythm activities',
            'activity',
            date('Y-m-d', strtotime('+14 days')),
            date('Y-m-d', strtotime('+14 days')),
            '10:00',
            '11:30',
            0,
            'Music Room',
            15,
            2,
            5,
            'draft',
            0,
            $adminId
        ],
        [
            'Outdoor Games Day',
            'Sports and games in the playground',
            'event',
            date('Y-m-d', strtotime('+21 days')),
            date('Y-m-d', strtotime('+21 days')),
            '09:30',
            '16:30',
            0,
            'Outdoor Playground',
            25,
            5,
            10,
            'draft',
            0,
            $adminId
        ],
    ];

    $stmt = $pdo->prepare("
        INSERT OR IGNORE INTO calendar_events
        (title, description, event_type, start_date, end_date, start_time, end_time, is_all_day, location, max_participants, age_min, age_max, status, is_public, created_by)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
    ");

    foreach ($events as $event) {
        $stmt->execute($event);
    }

    // Add some sample participants to the first event
    $eventId = $pdo->query("SELECT id FROM calendar_events WHERE title = 'Welcome Day Activities' LIMIT 1")->fetchColumn();

    if ($eventId) {
        $participants = [
            ['Mario', 'Rossi', '2018-03-15', 'Maria Rossi', 'maria.rossi@email.com', '+39 333 1234567', 'Nonna Anna +39 334 7654321', 'No allergies', 'registered', ''],
            ['Giulia', 'Bianchi', '2017-08-22', 'Luca Bianchi', 'luca.bianchi@email.com', '+39 334 2345678', 'Zia Sofia +39 335 8765432', 'Mild asthma', 'confirmed', ''],
            ['Alessandro', 'Verdi', '2019-01-10', 'Elena Verdi', 'elena.verdi@email.com', '+39 335 3456789', 'Padre Marco +39 336 9876543', '', 'registered', ''],
        ];

        $stmt = $pdo->prepare("
            INSERT OR IGNORE INTO event_participants
            (event_id, child_name, child_surname, birth_date, parent_name, parent_email, parent_phone, emergency_contact, medical_notes, status, notes)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");

        foreach ($participants as $participant) {
            $stmt->execute(array_merge([$eventId], $participant));
        }
    }
}
