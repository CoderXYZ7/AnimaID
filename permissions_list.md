# AnimaID Permission System - Complete Permissions List

This document outlines all permissions that should be implemented in the AnimaID system. Each permission follows the pattern `module.action` and includes create, edit, and delete permissions for resources that support these operations.

## Authentication & User Management

### Users
- `admin.users.view` - View user list and details
- `admin.users.create` - Create new users
- `admin.users.edit` - Edit existing users
- `admin.users.delete` - Delete/deactivate users

### Roles & Permissions
- `admin.roles.view` - View roles and their permissions
- `admin.roles.create` - Create new roles
- `admin.roles.edit` - Edit existing roles and their permissions
- `admin.roles.delete` - Delete roles

### System Administration
- `admin.system.view` - View system status and configuration
- `admin.system.edit` - Edit system settings
- `admin.system.backup` - Perform system backups

## Registrations (Children & Animators)

### Children Management
- `registrations.view` - View children list and details
- `registrations.create` - Register new children
- `registrations.edit` - Edit child information
- `registrations.delete` - Delete child records

### Child Guardians
- `registrations.guardians.view` - View child guardians
- `registrations.guardians.create` - Add guardians to children
- `registrations.guardians.edit` - Edit guardian information
- `registrations.guardians.delete` - Remove guardians from children

### Child Documents
- `registrations.documents.view` - View child documents
- `registrations.documents.create` - Upload documents for children
- `registrations.documents.edit` - Edit document metadata
- `registrations.documents.delete` - Delete child documents

### Child Notes
- `registrations.notes.view` - View child notes and observations
- `registrations.notes.create` - Add notes to child records
- `registrations.notes.edit` - Edit existing notes
- `registrations.notes.delete` - Delete child notes

### Animators Management
- `registrations.animators.view` - View animators list and details
- `registrations.animators.create` - Register new animators
- `registrations.animators.edit` - Edit animator information
- `registrations.animators.delete` - Delete animator records

### Animator User Linking
- `registrations.animators.users.view` - View animator-user relationships
- `registrations.animators.users.create` - Link users to animators
- `registrations.animators.users.edit` - Edit animator-user relationships
- `registrations.animators.users.delete` - Unlink users from animators

### Animator Documents
- `registrations.animators.documents.view` - View animator documents
- `registrations.animators.documents.create` - Upload documents for animators
- `registrations.animators.documents.edit` - Edit document metadata
- `registrations.animators.documents.delete` - Delete animator documents

### Animator Notes
- `registrations.animators.notes.view` - View animator notes
- `registrations.animators.notes.create` - Add notes to animator records
- `registrations.animators.notes.edit` - Edit existing notes
- `registrations.animators.notes.delete` - Delete animator notes

### Animator Availability
- `registrations.animators.availability.view` - View animator availability
- `registrations.animators.availability.edit` - Edit animator availability schedules

### Animator Week Types
- `registrations.animators.weektypes.view` - View animator week types
- `registrations.animators.weektypes.create` - Create week types for animators
- `registrations.animators.weektypes.edit` - Edit week types
- `registrations.animators.weektypes.delete` - Delete week types

### Availability Templates
- `registrations.templates.view` - View availability templates
- `registrations.templates.create` - Create availability templates
- `registrations.templates.edit` - Edit availability templates
- `registrations.templates.delete` - Delete availability templates

## Calendar & Events

### Calendar Events
- `calendar.view` - View calendar events
- `calendar.create` - Create new events
- `calendar.edit` - Edit existing events
- `calendar.delete` - Delete events
- `calendar.publish` - Publish events to public calendar

### Event Participants
- `calendar.participants.view` - View event participants
- `calendar.participants.manage` - Manage event registrations

## Attendance Management

### Attendance Records
- `attendance.view` - View attendance records
- `attendance.checkin` - Perform check-in operations
- `attendance.edit` - Edit attendance records
- `attendance.delete` - Delete attendance records
- `attendance.report` - Generate attendance reports

## Communications

### Communications
- `communications.view` - View internal communications
- `communications.send` - Send internal messages
- `communications.broadcast` - Send broadcast messages
- `communications.manage` - Manage communication settings

### Communication Comments
- `communications.comments.view` - View communication comments
- `communications.comments.create` - Add comments to communications
- `communications.comments.moderate` - Moderate user comments

## Media Management

### Media Files & Folders
- `media.view` - View media files and folders
- `media.upload` - Upload media files
- `media.edit` - Edit media metadata and organization
- `media.delete` - Delete media files and folders

### Media Sharing
- `media.share` - Create share links for media resources

## Space Management

### Spaces & Bookings
- `spaces.view` - View spaces and bookings
- `spaces.book` - Create space bookings
- `spaces.edit` - Edit space bookings
- `spaces.manage` - Manage space configurations

## Reports & Analytics

### Reports
- `reports.view` - View reports and KPIs
- `reports.generate` - Generate custom reports
- `reports.export` - Export report data

## Wiki/Knowledge Base

### Wiki Content
- `wiki.view` - View wiki content
- `wiki.edit` - Edit wiki pages
- `wiki.create` - Create new wiki pages
- `wiki.moderate` - Moderate user contributions

---

## Permission Implementation Notes

### Permission Naming Convention
- All permissions follow the pattern: `module.submodule.action`
- Actions are: `view`, `create`, `edit`, `delete`, `manage`, `moderate`, etc.
- For complex operations, use specific action names like `checkin`, `publish`, `share`

### Permission Groups
Permissions are organized into logical groups:
- **Admin**: System administration, users, roles
- **Registrations**: Children and animators management
- **Calendar**: Event management
- **Attendance**: Check-in/check-out operations
- **Communications**: Internal messaging
- **Media**: File management
- **Spaces**: Room booking
- **Reports**: Analytics and reporting
- **Wiki**: Knowledge base

### Role-Based Access Control (RBAC)
- Permissions are assigned to roles
- Users are assigned to roles
- Roles can have multiple permissions
- Users inherit permissions from their roles

### Special Considerations
- Some permissions may have dependencies (e.g., to edit something, you need view permission)
- Public resources may not require authentication
- File downloads may use token-based access for shared links
- System status endpoints may have restricted access

### Current Implementation Status
Based on the existing code, these permissions need to be added to the database initialization script and properly enforced in the API endpoints.
