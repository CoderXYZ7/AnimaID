# AnimaID User Manual

## Table of Contents
1. [Introduction](#introduction)
2. [Getting Started](#getting-started)
3. [User Roles and Permissions](#user-roles-and-permissions)
4. [Core Features](#core-features)
5. [Module Guides](#module-guides)
6. [System Administration](#system-administration)
7. [Troubleshooting](#troubleshooting)

## Introduction

### What is AnimaID?
AnimaID is a comprehensive management platform designed specifically for animation centers, educational labs, and recreational activity organizations. It provides a unified digital environment to coordinate staff, organize activities, manage attendance, and facilitate communication between staff, parents, and families.

### Key Benefits
- **Centralized Management**: All operations in one platform
- **Multi-language Support**: Available in English and Italian
- **Role-based Access**: Progressive permissions system
- **Mobile-friendly**: Responsive design works on all devices
- **Extensible**: Modular architecture with applet system

## Getting Started

### System Requirements
- Modern web browser (Chrome, Firefox, Safari, Edge)
- Internet connection
- For staff: Valid login credentials

### Accessing the Platform

#### Public Access
1. Navigate to your organization's AnimaID URL
2. Browse the public portal without login
3. View calendars, events, and public communications

#### Staff Login
1. Go to the login page (`/login.html`)
2. Enter your username and password
3. Click "Sign in" to access the dashboard

**Demo Credentials** (if available):
- Username: `admin`
- Password: `Admin123!@#`

### First Time Setup
1. **Change Default Password**: After first login, change your password
2. **Configure Profile**: Update your user information
3. **Set Preferences**: Choose your preferred language and theme

## User Roles and Permissions

### Role Hierarchy
AnimaID uses cumulative role tags that provide progressive access:

1. **@aiutoanimatore** (Assistant Animator)
   - Basic activity assistance
   - View assigned schedules

2. **@animatore** (Animator)
   - All assistant permissions
   - Manage activity groups
   - Record attendance

3. **@responsabile** (Manager)
   - All animator permissions
   - Staff coordination
   - Activity planning

4. **@organizzatore** (Organizer)
   - All manager permissions
   - System configuration
   - User management

5. **Technical Admin**
   - Full system access
   - Center configuration
   - Backup and maintenance

### Permission Levels
Each role has access to specific modules and functions based on their cumulative tags.

## Core Features

### Dashboard
The dashboard is your central workspace after login:

- **Welcome Section**: Personalized greeting and quick overview
- **Statistics Cards**: Real-time data on users, activities, children, and reports
- **Quick Actions**: Direct links to frequently used modules
- **Permissions Display**: Shows your current access levels

### Navigation
- Use the Quick Actions grid for one-click access to modules
- Breadcrumb navigation shows your current location
- Sidebar navigation (if implemented) for detailed menu structure

## Module Guides

### Calendar Management
**Purpose**: Schedule and manage activities, events, and staff shifts

**Key Functions**:
- View daily, weekly, and monthly calendars
- Create new activities and events
- Assign staff to activities
- Set up recurring events
- Manage room and resource bookings

**User Access**:
- All staff: View assigned activities
- Animators+: Create and edit activities
- Managers+: Schedule staff and resources

### Attendance Tracking
**Purpose**: Record and monitor participant attendance

**Key Functions**:
- Check-in/check-out system
- Real-time attendance monitoring
- Attendance reports and statistics
- Absence tracking and notifications

**User Access**:
- Animators+: Record attendance for their activities
- Managers+: View all attendance data

### Children Management
**Purpose**: Manage child profiles, registrations, and information

**Key Functions**:
- Child profile creation and maintenance
- Registration management
- Medical and emergency information
- Parent/guardian contacts
- Activity enrollment

**User Access**:
- Animators+: View assigned children
- Managers+: Full child management

### Animators Management
**Purpose**: Coordinate and manage staff members

**Key Functions**:
- Staff profile management
- Role and permission assignment
- Availability scheduling
- Performance tracking
- Communication with staff

**User Access**:
- Managers+: View and manage staff
- Organizers+: Role and permission management

### Communications
**Purpose**: Internal and external communication hub

**Key Functions**:
- Internal messaging system
- Public announcements
- Notice boards
- Email and notification system
- Media sharing

**User Access**:
- All staff: Send/receive messages
- Managers+: Create public announcements
- Organizers+: System-wide communications

### Media Manager
**Purpose**: Organize and share photos, videos, and documents

**Key Functions**:
- File upload and organization
- Media categorization
- Access control
- Public gallery management
- Document sharing

**User Access**:
- Animators+: Upload media for their activities
- Managers+: Organize and categorize media
- Organizers+: Media approval and publishing

### Wiki / Knowledge Base
**Purpose**: Central repository for activities, games, and procedures

**Key Functions**:
- Activity and game database
- Search and filter capabilities
- Category organization
- User feedback and ratings
- Procedure documentation

**User Access**:
- All staff: Browse and search
- Animators+: Contribute content
- Managers+: Content approval and organization

## System Administration

### User Management
**Access**: Admin and Organizer roles

**Functions**:
- Create and manage user accounts
- Assign roles and permissions
- Reset passwords
- Manage user groups
- Monitor user activity

### Role Management
**Access**: Admin role only

**Functions**:
- Define role hierarchies
- Set permission levels
- Configure cumulative tags
- Manage access policies

### System Status
**Access**: Admin and Organizer roles

**Functions**:
- Monitor system health
- View performance metrics
- Check database status
- Review system logs
- Backup management

### Reports and Analytics
**Access**: Managers+

**Functions**:
- Generate activity reports
- Attendance statistics
- Financial reports (if applicable)
- Export data to various formats
- Custom report creation

## Multi-language and Theme Support

### Language Selection
AnimaID supports multiple languages:

1. **Automatic Detection**: System detects your browser language
2. **Manual Selection**: Use the language selector in the header
3. **Persistent Preference**: Your choice is saved for future sessions

**Available Languages**:
- English (en)
- Italian (it)

### Theme Selection
Choose between light and dark themes:

1. Click the theme switcher button (sun/moon icon)
2. Theme preference is saved automatically
3. System respects device dark mode settings

## Troubleshooting

### Common Issues

#### Login Problems
- **Forgot Password**: Use "Forgot your password?" link (if implemented)
- **Account Locked**: Contact system administrator
- **Invalid Credentials**: Verify username and password

#### Access Denied
- Check your role permissions
- Contact administrator for access requests
- Verify you're logged into the correct account

#### System Errors
- Refresh the page
- Clear browser cache and cookies
- Check internet connection
- Contact technical support if issue persists

### Getting Help

#### Internal Support
- Contact your system administrator
- Check the internal wiki for documentation
- Use the communications module to reach support staff

#### Technical Support
- System logs available to administrators
- Backup and restore procedures
- Update and maintenance schedules

## Best Practices

### Data Management
- Regular backups of important information
- Keep child and staff information up to date
- Use consistent naming conventions

### Security
- Use strong, unique passwords
- Log out when not using the system
- Report suspicious activity immediately
- Keep system updated

### Communication
- Use appropriate channels for different types of communication
- Keep announcements clear and concise
- Regular updates to parents and staff

## Version Information

This manual applies to AnimaID version 0.9. Features and functionality may change in future updates. Check the system documentation for the most current information.

---

*Last Updated: November 2025*  
*AnimaID Version: 0.9*
