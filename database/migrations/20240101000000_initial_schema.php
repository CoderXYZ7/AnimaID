<?php

namespace AnimaID\Database\Migrations;

use AnimaID\Database\Migration;

/**
 * Initial Schema Migration
 * derived from legacy init.php
 */
class InitialSchema extends Migration
{
    public function getName(): string
    {
        return '20240101000000_initial_schema';
    }

    public function up(): void
    {
        // Users table
        $this->execute("
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
        $this->execute("
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
        $this->execute("
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
        $this->execute("
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
        $this->execute("
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
        $this->execute("
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
        $this->execute("
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
        $this->execute("
            CREATE TABLE IF NOT EXISTS calendar_events (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                title VARCHAR(255) NOT NULL,
                description TEXT,
                event_type VARCHAR(50) NOT NULL DEFAULT 'activity',
                start_date DATE NOT NULL,
                end_date DATE NOT NULL,
                start_time TIME,
                end_time TIME,
                is_all_day BOOLEAN DEFAULT 0,
                location VARCHAR(255),
                max_participants INTEGER,
                age_min INTEGER,
                age_max INTEGER,
                status VARCHAR(20) DEFAULT 'draft',
                is_public BOOLEAN DEFAULT 0,
                created_by INTEGER NOT NULL,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                updated_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (created_by) REFERENCES users(id)
            )
        ");

        // Event participants table
        $this->execute("
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
                status VARCHAR(20) DEFAULT 'registered',
                notes TEXT,
                FOREIGN KEY (event_id) REFERENCES calendar_events(id) ON DELETE CASCADE
            )
        ");

        // Attendance records table
        $this->execute("
            CREATE TABLE IF NOT EXISTS attendance_records (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                participant_id INTEGER NOT NULL,
                event_id INTEGER NOT NULL,
                check_in_time DATETIME,
                check_out_time DATETIME,
                check_in_staff INTEGER,
                check_out_staff INTEGER,
                status VARCHAR(20) DEFAULT 'present',
                notes TEXT,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                updated_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (participant_id) REFERENCES event_participants(id) ON DELETE CASCADE,
                FOREIGN KEY (event_id) REFERENCES calendar_events(id) ON DELETE CASCADE,
                FOREIGN KEY (check_in_staff) REFERENCES users(id),
                FOREIGN KEY (check_out_staff) REFERENCES users(id)
            )
        ");

        // Spaces table
        $this->execute("
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
        $this->execute("
            CREATE TABLE IF NOT EXISTS space_bookings (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                space_id INTEGER NOT NULL,
                event_id INTEGER,
                booked_by INTEGER NOT NULL,
                start_time DATETIME NOT NULL,
                end_time DATETIME NOT NULL,
                purpose VARCHAR(255),
                status VARCHAR(20) DEFAULT 'confirmed',
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (space_id) REFERENCES spaces(id) ON DELETE CASCADE,
                FOREIGN KEY (event_id) REFERENCES calendar_events(id) ON DELETE SET NULL,
                FOREIGN KEY (booked_by) REFERENCES users(id)
            )
        ");

        // Children table
        $this->execute("
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
                status VARCHAR(20) DEFAULT 'active',
                registration_number VARCHAR(50) UNIQUE,
                created_by INTEGER NOT NULL,
                updated_by INTEGER,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                updated_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL,
                FOREIGN KEY (updated_by) REFERENCES users(id) ON DELETE SET NULL
            )
        ");

        // Child medical information
        $this->execute("
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

        // Child guardians
        $this->execute("
            CREATE TABLE IF NOT EXISTS child_guardians (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                child_id INTEGER NOT NULL,
                relationship VARCHAR(50) NOT NULL,
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

        // Child documents
        $this->execute("
            CREATE TABLE IF NOT EXISTS child_documents (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                child_id INTEGER NOT NULL,
                document_type VARCHAR(50) NOT NULL,
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
                FOREIGN KEY (uploaded_by) REFERENCES users(id) ON DELETE SET NULL,
                FOREIGN KEY (verified_by) REFERENCES users(id) ON DELETE SET NULL
            )
        ");

        // Child notes
        $this->execute("
            CREATE TABLE IF NOT EXISTS child_notes (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                child_id INTEGER NOT NULL,
                note_type VARCHAR(50) NOT NULL,
                title VARCHAR(255) NOT NULL,
                content TEXT NOT NULL,
                is_private BOOLEAN DEFAULT 0,
                created_by INTEGER NOT NULL,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                updated_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (child_id) REFERENCES children(id) ON DELETE CASCADE,
                FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL
            )
        ");

        // Child activity history
        $this->execute("
            CREATE TABLE IF NOT EXISTS child_activity_history (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                child_id INTEGER NOT NULL,
                event_id INTEGER,
                activity_type VARCHAR(50) NOT NULL,
                activity_name VARCHAR(255) NOT NULL,
                activity_date DATE NOT NULL,
                duration_hours DECIMAL(4,2),
                staff_member INTEGER,
                participation_status VARCHAR(20) DEFAULT 'attended',
                notes TEXT,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (child_id) REFERENCES children(id) ON DELETE CASCADE,
                FOREIGN KEY (event_id) REFERENCES calendar_events(id) ON DELETE SET NULL,
                FOREIGN KEY (staff_member) REFERENCES users(id)
            )
        ");

        // Communications
        $this->execute("
            CREATE TABLE IF NOT EXISTS communications (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                title VARCHAR(255) NOT NULL,
                content TEXT NOT NULL,
                communication_type VARCHAR(50) NOT NULL,
                priority VARCHAR(20) DEFAULT 'normal',
                is_public BOOLEAN DEFAULT 0,
                status VARCHAR(20) DEFAULT 'draft',
                target_audience VARCHAR(100),
                event_id INTEGER,
                created_by INTEGER NOT NULL,
                published_by INTEGER,
                published_at DATETIME,
                expires_at DATETIME,
                view_count INTEGER DEFAULT 0,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                updated_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (event_id) REFERENCES calendar_events(id) ON DELETE SET NULL,
                FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL,
                FOREIGN KEY (published_by) REFERENCES users(id) ON DELETE SET NULL
            )
        ");

        // Communication attachments
        $this->execute("
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
                FOREIGN KEY (uploaded_by) REFERENCES users(id) ON DELETE SET NULL
            )
        ");

        // Communication reads
        $this->execute("
            CREATE TABLE IF NOT EXISTS communication_reads (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                communication_id INTEGER NOT NULL,
                user_id INTEGER,
                ip_address VARCHAR(45),
                user_agent TEXT,
                read_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (communication_id) REFERENCES communications(id) ON DELETE CASCADE,
                FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
            )
        ");

        // Communication comments
        $this->execute("
            CREATE TABLE IF NOT EXISTS communication_comments (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                communication_id INTEGER NOT NULL,
                parent_comment_id INTEGER,
                content TEXT NOT NULL,
                is_internal BOOLEAN DEFAULT 1,
                created_by INTEGER,
                author_name VARCHAR(255),
                author_email VARCHAR(255),
                status VARCHAR(20) DEFAULT 'approved',
                moderated_by INTEGER,
                moderated_at DATETIME,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                updated_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (communication_id) REFERENCES communications(id) ON DELETE CASCADE,
                FOREIGN KEY (parent_comment_id) REFERENCES communication_comments(id) ON DELETE CASCADE,
                FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL,
                FOREIGN KEY (moderated_by) REFERENCES users(id) ON DELETE SET NULL
            )
        ");

        // Notification preferences
        $this->execute("
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

        // Media folders
        $this->execute("
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
                FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL
            )
        ");

        // Media files
        $this->execute("
            CREATE TABLE IF NOT EXISTS media_files (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                filename VARCHAR(255) NOT NULL,
                original_name VARCHAR(255) NOT NULL,
                file_path VARCHAR(1000) NOT NULL,
                file_size INTEGER NOT NULL,
                mime_type VARCHAR(100) NOT NULL,
                file_type VARCHAR(50) NOT NULL,
                folder_id INTEGER,
                uploaded_by INTEGER NOT NULL,
                is_shared BOOLEAN DEFAULT 0,
                share_token VARCHAR(255) UNIQUE,
                download_count INTEGER DEFAULT 0,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                updated_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (folder_id) REFERENCES media_folders(id) ON DELETE SET NULL,
                FOREIGN KEY (uploaded_by) REFERENCES users(id) ON DELETE SET NULL
            )
        ");

        // Media sharing
        $this->execute("
            CREATE TABLE IF NOT EXISTS media_sharing (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                resource_type VARCHAR(20) NOT NULL,
                resource_id INTEGER NOT NULL,
                shared_with_user_id INTEGER,
                shared_by_user_id INTEGER NOT NULL,
                permission VARCHAR(20) DEFAULT 'view',
                share_token VARCHAR(255) UNIQUE,
                expires_at DATETIME,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (shared_with_user_id) REFERENCES users(id) ON DELETE CASCADE,
                FOREIGN KEY (shared_by_user_id) REFERENCES users(id) ON DELETE SET NULL
            )
        ");

        // Media file versions
        $this->execute("
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
                FOREIGN KEY (uploaded_by) REFERENCES users(id) ON DELETE SET NULL
            )
        ");

        // Animators table
        $this->execute("
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
                status VARCHAR(20) DEFAULT 'active',
                animator_number VARCHAR(50) UNIQUE,
                created_by INTEGER NOT NULL,
                updated_by INTEGER,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                updated_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL,
                FOREIGN KEY (updated_by) REFERENCES users(id) ON DELETE SET NULL
            )
        ");

        // Animator-User relationship
        $this->execute("
            CREATE TABLE IF NOT EXISTS animator_users (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                animator_id INTEGER NOT NULL,
                user_id INTEGER NOT NULL,
                relationship_type VARCHAR(50) DEFAULT 'primary',
                is_active BOOLEAN DEFAULT 1,
                assigned_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                assigned_by INTEGER NOT NULL,
                notes TEXT,
                FOREIGN KEY (animator_id) REFERENCES animators(id) ON DELETE CASCADE,
                FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
                FOREIGN KEY (assigned_by) REFERENCES users(id) ON DELETE SET NULL,
                UNIQUE(animator_id, user_id)
            )
        ");

        // Animator documents
        $this->execute("
            CREATE TABLE IF NOT EXISTS animator_documents (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                animator_id INTEGER NOT NULL,
                document_type VARCHAR(50) NOT NULL,
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
                FOREIGN KEY (uploaded_by) REFERENCES users(id) ON DELETE SET NULL,
                FOREIGN KEY (verified_by) REFERENCES users(id) ON DELETE SET NULL
            )
        ");

        // Animator notes
        $this->execute("
            CREATE TABLE IF NOT EXISTS animator_notes (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                animator_id INTEGER NOT NULL,
                note_type VARCHAR(50) NOT NULL,
                title VARCHAR(255) NOT NULL,
                content TEXT NOT NULL,
                is_private BOOLEAN DEFAULT 0,
                created_by INTEGER NOT NULL,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                updated_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (animator_id) REFERENCES animators(id) ON DELETE CASCADE,
                FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL
            )
        ");

        // Animator activity history
        $this->execute("
            CREATE TABLE IF NOT EXISTS animator_activity_history (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                animator_id INTEGER NOT NULL,
                event_id INTEGER,
                activity_type VARCHAR(50) NOT NULL,
                activity_name VARCHAR(255) NOT NULL,
                activity_date DATE NOT NULL,
                duration_hours DECIMAL(4,2),
                role VARCHAR(50) NOT NULL,
                notes TEXT,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (animator_id) REFERENCES animators(id) ON DELETE CASCADE,
                FOREIGN KEY (event_id) REFERENCES calendar_events(id) ON DELETE SET NULL
            )
        ");

        // Wiki pages
        $this->execute("
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
                FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL,
                FOREIGN KEY (updated_by) REFERENCES users(id) ON DELETE SET NULL
            )
        ");

        // Wiki categories
        $this->execute("
            CREATE TABLE IF NOT EXISTS wiki_categories (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                name VARCHAR(100) NOT NULL,
                slug VARCHAR(100) UNIQUE NOT NULL,
                description TEXT,
                color VARCHAR(7) DEFAULT '#3B82F6',
                icon VARCHAR(50) DEFAULT 'book',
                parent_id INTEGER,
                sort_order INTEGER DEFAULT 0,
                is_active BOOLEAN DEFAULT 1,
                created_by INTEGER NOT NULL,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                updated_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (parent_id) REFERENCES wiki_categories(id) ON DELETE CASCADE,
                FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL
            )
        ");

        // Wiki tags
        $this->execute("
            CREATE TABLE IF NOT EXISTS wiki_tags (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                name VARCHAR(50) UNIQUE NOT NULL,
                slug VARCHAR(50) UNIQUE NOT NULL,
                color VARCHAR(7) DEFAULT '#6B7280',
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP
            )
        ");

        // Wiki page tags
        $this->execute("
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

        // Wiki revisions
        $this->execute("
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
                FOREIGN KEY (edited_by) REFERENCES users(id) ON DELETE SET NULL
            )
        ");

        // Wiki links
        $this->execute("
            CREATE TABLE IF NOT EXISTS wiki_page_links (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                from_page_id INTEGER NOT NULL,
                to_page_id INTEGER,
                link_text VARCHAR(255) NOT NULL,
                link_url VARCHAR(500),
                link_type VARCHAR(20) DEFAULT 'internal',
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (from_page_id) REFERENCES wiki_pages(id) ON DELETE CASCADE,
                FOREIGN KEY (to_page_id) REFERENCES wiki_pages(id) ON DELETE SET NULL
            )
        ");

        // Wiki attachments
        $this->execute("
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
                FOREIGN KEY (uploaded_by) REFERENCES users(id) ON DELETE SET NULL
            )
        ");

        // Wiki search index
        $this->execute("
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

    public function down(): void
    {
        $tables = [
            'wiki_search_index',
            'wiki_page_attachments',
            'wiki_page_links',
            'wiki_page_revisions',
            'wiki_page_tags',
            'wiki_tags',
            'wiki_categories',
            'wiki_pages',
            'animator_activity_history',
            'animator_notes',
            'animator_documents',
            'animator_users',
            'animators',
            'media_file_versions',
            'media_sharing',
            'media_files',
            'media_folders',
            'notification_preferences',
            'communication_comments',
            'communication_reads',
            'communication_attachments',
            'communications',
            'child_activity_history',
            'child_notes',
            'child_documents',
            'child_guardians',
            'child_medical',
            'children',
            'space_bookings',
            'spaces',
            'attendance_records',
            'event_participants',
            'calendar_events',
            'password_resets',
            'user_sessions',
            'role_permissions',
            'permissions',
            'user_roles',
            'roles',
            'users'
        ];

        foreach ($tables as $table) {
            $this->execute("DROP TABLE IF EXISTS {$table}");
        }
    }
}
