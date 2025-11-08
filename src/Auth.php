<?php

require_once __DIR__ . '/Database.php';
require_once __DIR__ . '/JWT.php';

/**
 * Authentication and Authorization Class
 * Handles user authentication, JWT tokens, and permission checking
 */

class Auth {
    private $db;
    private $config;

    public function __construct() {
        $this->db = Database::getInstance();
        $this->config = require __DIR__ . '/../config.php';
    }

    /**
     * Get database instance
     */
    public function getDb() {
        return $this->db;
    }

    /**
     * Authenticate user and return JWT token
     */
    public function login(string $username, string $password): array {
        // Find user by username or email
        $user = $this->db->fetchOne(
            "SELECT * FROM users WHERE (username = ? OR email = ?) AND is_active = 1",
            [$username, $username]
        );

        if (!$user || !password_verify($password, $user['password_hash'])) {
            throw new Exception("Invalid credentials");
        }

        // Update last login
        $this->db->update('users', ['last_login' => date('Y-m-d H:i:s')], 'id = ?', [$user['id']]);

        // Get user roles
        $roles = $this->getUserRoles($user['id']);

        // Generate JWT token
        $token = $this->generateToken($user, $roles);

        // Store session
        $this->createSession($user['id'], $token);

        return [
            'token' => $token,
            'user' => [
                'id' => $user['id'],
                'username' => $user['username'],
                'email' => $user['email'],
                'roles' => $roles
            ],
            'expires_at' => date('c', strtotime("+{$this->config['jwt']['expiration_hours']} hours"))
        ];
    }

    /**
     * Verify JWT token and return user data
     */
    public function verifyToken(string $token): array {
        try {
            $decoded = JWT::decode($token, $this->config['jwt']['secret']);

            // Check if session exists and is valid
            $session = $this->db->fetchOne(
                "SELECT * FROM user_sessions WHERE session_token = ? AND expires_at > datetime('now')",
                [$token]
            );

            if (!$session) {
                throw new Exception("Invalid session");
            }

            // Get current user roles (in case they changed)
            $user = $this->db->fetchOne("SELECT * FROM users WHERE id = ? AND is_active = 1", [$decoded->user_id]);
            if (!$user) {
                throw new Exception("User not found or inactive");
            }

            $roles = $this->getUserRoles($user['id']);

            return [
                'id' => $user['id'],
                'username' => $user['username'],
                'email' => $user['email'],
                'roles' => $roles
            ];

        } catch (Exception $e) {
            throw new Exception("Invalid token: " . $e->getMessage());
        }
    }

    /**
     * Check if user has specific permission
     */
    public function checkPermission(int $userId, string $permission): bool {
        // Get all user roles
        $userRoles = $this->db->fetchAll(
            "SELECT r.id, r.name FROM roles r INNER JOIN user_roles ur ON r.id = ur.role_id WHERE ur.user_id = ?",
            [$userId]
        );

        if (empty($userRoles)) {
            return false;
        }

        // Check if any role has the permission
        $roleIds = array_column($userRoles, 'id');
        $placeholders = str_repeat('?,', count($roleIds) - 1) . '?';

        $result = $this->db->fetchOne(
            "SELECT COUNT(*) as count FROM role_permissions rp INNER JOIN permissions p ON rp.permission_id = p.id WHERE rp.role_id IN ($placeholders) AND p.name = ?",
            array_merge($roleIds, [$permission])
        );

        return $result['count'] > 0;
    }

    /**
     * Get all roles for a user
     */
    public function getUserRoles(int $userId): array {
        return $this->db->fetchAll(
            "SELECT r.*, ur.is_primary FROM roles r INNER JOIN user_roles ur ON r.id = ur.role_id WHERE ur.user_id = ? ORDER BY ur.is_primary DESC",
            [$userId]
        );
    }

    /**
     * Logout user by invalidating session
     */
    public function logout(string $token): void {
        $this->db->delete('user_sessions', 'session_token = ?', [$token]);
    }

    /**
     * Create a new user
     */
    public function createUser(string $username, string $email, string $password, array $roleIds = []): int {
        // Validate password
        $this->validatePassword($password);

        // Hash password
        $passwordHash = password_hash($password, PASSWORD_BCRYPT, [
            'cost' => $this->config['security']['bcrypt_cost']
        ]);

        // Create user
        $userId = $this->db->insert('users', [
            'username' => $username,
            'email' => $email,
            'password_hash' => $passwordHash
        ]);

        // Assign roles
        if (!empty($roleIds)) {
            $this->assignRolesToUser($userId, $roleIds, 1); // User ID 1 is the creator (admin)
        }

        return $userId;
    }

    /**
     * Update user information
     */
    public function updateUser(int $userId, array $data): void {
        $allowedFields = ['email', 'is_active'];
        $updateData = array_intersect_key($data, array_flip($allowedFields));

        if (!empty($updateData)) {
            $this->db->update('users', $updateData, 'id = ?', [$userId]);
        }

        // Update roles if provided
        if (isset($data['role_ids'])) {
            $this->assignRolesToUser($userId, $data['role_ids'], 1);
        }
    }

    /**
     * Assign roles to user
     */
    public function assignRolesToUser(int $userId, array $roleIds, int $assignedBy): void {
        // Remove existing roles
        $this->db->delete('user_roles', 'user_id = ?', [$userId]);

        // Assign new roles
        foreach ($roleIds as $roleId) {
            $this->db->insert('user_roles', [
                'user_id' => $userId,
                'role_id' => $roleId,
                'assigned_by' => $assignedBy,
                'is_primary' => ($roleId == $roleIds[0] ? 1 : 0) // First role is primary
            ]);
        }
    }

    /**
     * Get all users with pagination
     */
    public function getUsers(int $page = 1, int $limit = 20, string $search = ''): array {
        $offset = ($page - 1) * $limit;

        $whereClause = '';
        $params = [];

        if (!empty($search)) {
            $whereClause = 'WHERE username LIKE ? OR email LIKE ?';
            $params = ["%{$search}%", "%{$search}%"];
        }

        // Get users
        $users = $this->db->fetchAll(
            "SELECT id, username, email, is_active, created_at, last_login FROM users {$whereClause} ORDER BY created_at DESC LIMIT ? OFFSET ?",
            array_merge($params, [$limit, $offset])
        );

        // Add roles to each user
        foreach ($users as &$user) {
            $user['roles'] = array_column($this->getUserRoles($user['id']), 'name');
        }

        // Get total count
        $total = $this->db->fetchOne(
            "SELECT COUNT(*) as count FROM users {$whereClause}",
            $params
        )['count'];

        return [
            'users' => $users,
            'pagination' => [
                'page' => $page,
                'limit' => $limit,
                'total' => $total,
                'pages' => ceil($total / $limit)
            ]
        ];
    }

    /**
     * Get all roles
     */
    public function getRoles(): array {
        $roles = $this->db->fetchAll("SELECT * FROM roles ORDER BY name");

        // Add permissions to each role
        foreach ($roles as &$role) {
            $role['permissions'] = $this->db->fetchAll(
                "SELECT p.name FROM permissions p INNER JOIN role_permissions rp ON p.id = rp.permission_id WHERE rp.role_id = ?",
                [$role['id']]
            );
            $role['permissions'] = array_column($role['permissions'], 'name');
        }

        return $roles;
    }

    /**
     * Get all permissions grouped by module
     */
    public function getPermissions(): array {
        $permissions = $this->db->fetchAll("SELECT * FROM permissions ORDER BY module, name");

        $grouped = [];
        foreach ($permissions as $permission) {
            $grouped[$permission['module']][] = $permission;
        }

        return $grouped;
    }

    /**
     * Create a new role
     */
    public function createRole(string $name, string $displayName, array $permissionIds = []): int {
        // Create role
        $roleId = $this->db->insert('roles', [
            'name' => $name,
            'display_name' => $displayName
        ]);

        // Assign permissions
        if (!empty($permissionIds)) {
            $this->assignPermissionsToRole($roleId, $permissionIds);
        }

        return $roleId;
    }

    /**
     * Update role information
     */
    public function updateRole(int $roleId, array $data): void {
        $allowedFields = ['name', 'display_name'];
        $updateData = array_intersect_key($data, array_flip($allowedFields));

        if (!empty($updateData)) {
            $this->db->update('roles', $updateData, 'id = ?', [$roleId]);
        }

        // Update permissions if provided
        if (isset($data['permission_ids'])) {
            $this->assignPermissionsToRole($roleId, $data['permission_ids']);
        }
    }

    /**
     * Delete a role
     */
    public function deleteRole(int $roleId): void {
        // Remove role permissions
        $this->db->delete('role_permissions', 'role_id = ?', [$roleId]);

        // Remove user role assignments
        $this->db->delete('user_roles', 'role_id = ?', [$roleId]);

        // Delete the role
        $this->db->delete('roles', 'id = ?', [$roleId]);
    }

    /**
     * Assign permissions to role
     */
    private function assignPermissionsToRole(int $roleId, array $permissionIds): void {
        // Remove existing permissions
        $this->db->delete('role_permissions', 'role_id = ?', [$roleId]);

        // Assign new permissions
        foreach ($permissionIds as $permissionId) {
            $this->db->insert('role_permissions', [
                'role_id' => $roleId,
                'permission_id' => $permissionId
            ]);
        }
    }

    /**
     * Get calendar events
     */
    public function getCalendarEvents(int $page = 1, int $limit = 20, array $filters = []): array {
        $offset = ($page - 1) * $limit;

        $whereClause = '';
        $params = [];

        if (!empty($filters['status'])) {
            $whereClause .= ' AND status = ?';
            $params[] = $filters['status'];
        }

        if (!empty($filters['event_type'])) {
            $whereClause .= ' AND event_type = ?';
            $params[] = $filters['event_type'];
        }

        if (!empty($filters['start_date'])) {
            $whereClause .= ' AND start_date >= ?';
            $params[] = $filters['start_date'];
        }

        if (!empty($filters['end_date'])) {
            $whereClause .= ' AND end_date <= ?';
            $params[] = $filters['end_date'];
        }

        if (!empty($filters['is_public'])) {
            $whereClause .= ' AND is_public = ?';
            $params[] = $filters['is_public'];
        }

        // Get events
        $events = $this->db->fetchAll(
            "SELECT * FROM calendar_events WHERE 1=1 {$whereClause} ORDER BY start_date, start_time LIMIT ? OFFSET ?",
            array_merge($params, [$limit, $offset])
        );

        // Get total count
        $total = $this->db->fetchOne(
            "SELECT COUNT(*) as count FROM calendar_events WHERE 1=1 {$whereClause}",
            $params
        )['count'];

        return [
            'events' => $events,
            'pagination' => [
                'page' => $page,
                'limit' => $limit,
                'total' => $total,
                'pages' => ceil($total / $limit)
            ]
        ];
    }

    /**
     * Create calendar event
     */
    public function createCalendarEvent(array $eventData, int $createdBy): int {
        return $this->db->insert('calendar_events', array_merge($eventData, [
            'created_by' => $createdBy
        ]));
    }

    /**
     * Update calendar event
     */
    public function updateCalendarEvent(int $eventId, array $eventData): void {
        $allowedFields = ['title', 'description', 'event_type', 'start_date', 'end_date', 'start_time', 'end_time', 'is_all_day', 'location', 'max_participants', 'age_min', 'age_max', 'status', 'is_public'];
        $updateData = array_intersect_key($eventData, array_flip($allowedFields));

        if (!empty($updateData)) {
            $this->db->update('calendar_events', $updateData, 'id = ?', [$eventId]);
        }
    }

    /**
     * Delete calendar event
     */
    public function deleteCalendarEvent(int $eventId): void {
        $this->db->delete('calendar_events', 'id = ?', [$eventId]);
    }

    /**
     * Get event participants
     */
    public function getEventParticipants(int $eventId): array {
        return $this->db->fetchAll(
            "SELECT * FROM event_participants WHERE event_id = ? ORDER BY registration_date",
            [$eventId]
        );
    }

    /**
     * Add participant to event
     */
    public function addEventParticipant(int $eventId, array $participantData): int {
        return $this->db->insert('event_participants', array_merge($participantData, [
            'event_id' => $eventId
        ]));
    }

    /**
     * Update participant
     */
    public function updateEventParticipant(int $participantId, array $participantData): void {
        $allowedFields = ['child_name', 'child_surname', 'birth_date', 'parent_name', 'parent_email', 'parent_phone', 'emergency_contact', 'medical_notes', 'status', 'notes'];
        $updateData = array_intersect_key($participantData, array_flip($allowedFields));

        if (!empty($updateData)) {
            $this->db->update('event_participants', $updateData, 'id = ?', [$participantId]);
        }
    }

    /**
     * Check in/out child for an event
     */
    public function checkInOutChild(int $childId, int $eventId, string $action, int $staffId, string $notes = ''): void {
        $now = date('Y-m-d H:i:s');

        // Check if child is already registered for this event, if not, add them
        $existingParticipant = $this->db->fetchOne(
            "SELECT id FROM event_participants WHERE event_id = ? AND child_name = (SELECT first_name FROM children WHERE id = ?) AND child_surname = (SELECT last_name FROM children WHERE id = ?)",
            [$eventId, $childId, $childId]
        );

        if (!$existingParticipant) {
            // Add child to event participants
            $child = $this->db->fetchOne("SELECT * FROM children WHERE id = ?", [$childId]);
            if (!$child) {
                throw new Exception('Child not found');
            }

            // Get primary guardian
            $guardian = $this->db->fetchOne("SELECT * FROM child_guardians WHERE child_id = ? AND is_primary = 1", [$childId]);

            $participantId = $this->db->insert('event_participants', [
                'event_id' => $eventId,
                'child_name' => $child['first_name'],
                'child_surname' => $child['last_name'],
                'birth_date' => $child['birth_date'],
                'parent_name' => $guardian ? $guardian['first_name'] . ' ' . $guardian['last_name'] : '',
                'parent_email' => $guardian ? $guardian['email'] : '',
                'parent_phone' => $guardian ? $guardian['phone'] : '',
                'emergency_contact' => $child['medical'] ? $child['medical']['emergency_contact_name'] . ' ' . $child['medical']['emergency_contact_phone'] : '',
                'medical_notes' => $child['medical'] ? $child['medical']['allergies'] : '',
                'status' => 'registered'
            ]);
        } else {
            $participantId = $existingParticipant['id'];
        }

        // Now handle attendance
        if ($action === 'checkin') {
            $this->db->insert('attendance_records', [
                'participant_id' => $participantId,
                'event_id' => $eventId,
                'check_in_time' => $now,
                'check_in_staff' => $staffId,
                'status' => 'present',
                'notes' => $notes
            ]);
        } elseif ($action === 'checkout') {
            // Update existing record or create new one
            $existing = $this->db->fetchOne(
                "SELECT id FROM attendance_records WHERE participant_id = ? AND event_id = ? AND DATE(check_in_time) = DATE(?)",
                [$participantId, $eventId, $now]
            );

            if ($existing) {
                $this->db->update('attendance_records', [
                    'check_out_time' => $now,
                    'check_out_staff' => $staffId,
                    'notes' => $notes
                ], 'id = ?', [$existing['id']]);
            } else {
                $this->db->insert('attendance_records', [
                    'participant_id' => $participantId,
                    'event_id' => $eventId,
                    'check_out_time' => $now,
                    'check_out_staff' => $staffId,
                    'status' => 'present',
                    'notes' => $notes
                ]);
            }
        }
    }

    /**
     * Get attendance records
     */
    public function getAttendanceRecords(int $eventId = null, int $participantId = null, string $date = null): array {
        $whereClause = '';
        $params = [];

        if ($eventId) {
            $whereClause .= ' AND ar.event_id = ?';
            $params[] = $eventId;
        }

        if ($participantId) {
            $whereClause .= ' AND ar.participant_id = ?';
            $params[] = $participantId;
        }

        if ($date) {
            $whereClause .= ' AND DATE(ar.check_in_time) = ?';
            $params[] = $date;
        }

        return $this->db->fetchAll("
            SELECT ar.*, ep.child_name, ep.child_surname, ce.title as event_title
            FROM attendance_records ar
            JOIN event_participants ep ON ar.participant_id = ep.id
            JOIN calendar_events ce ON ar.event_id = ce.id
            WHERE 1=1 {$whereClause}
            ORDER BY ar.check_in_time DESC
        ", $params);
    }

    /**
     * Delete attendance record
     */
    public function deleteAttendanceRecord(int $recordId): void {
        // Check if record exists
        $record = $this->db->fetchOne("SELECT id FROM attendance_records WHERE id = ?", [$recordId]);
        if (!$record) {
            throw new Exception('Attendance record not found');
        }

        $this->db->delete('attendance_records', 'id = ?', [$recordId]);
    }

    /**
     * Get spaces
     */
    public function getSpaces(): array {
        return $this->db->fetchAll("SELECT * FROM spaces WHERE is_active = 1 ORDER BY name");
    }

    /**
     * Create space booking
     */
    public function createSpaceBooking(array $bookingData, int $bookedBy): int {
        return $this->db->insert('space_bookings', array_merge($bookingData, [
            'booked_by' => $bookedBy
        ]));
    }

    /**
     * Get space bookings
     */
    public function getSpaceBookings(string $startDate = null, string $endDate = null): array {
        $whereClause = '';
        $params = [];

        if ($startDate) {
            $whereClause .= ' AND start_time >= ?';
            $params[] = $startDate . ' 00:00:00';
        }

        if ($endDate) {
            $whereClause .= ' AND end_time <= ?';
            $params[] = $endDate . ' 23:59:59';
        }

        return $this->db->fetchAll("
            SELECT sb.*, s.name as space_name, s.location, ce.title as event_title, u.username as booked_by_name
            FROM space_bookings sb
            JOIN spaces s ON sb.space_id = s.id
            LEFT JOIN calendar_events ce ON sb.event_id = ce.id
            JOIN users u ON sb.booked_by = u.id
            WHERE 1=1 {$whereClause}
            ORDER BY sb.start_time
        ", $params);
    }

    /**
     * Get children with pagination and filtering
     */
    public function getChildren(int $page = 1, int $limit = 20, array $filters = []): array {
        $offset = ($page - 1) * $limit;

        $whereClause = '';
        $params = [];

        if (!empty($filters['status'])) {
            $whereClause .= ' AND c.status = ?';
            $params[] = $filters['status'];
        }

        if (!empty($filters['search'])) {
            $whereClause .= ' AND (c.first_name LIKE ? OR c.last_name LIKE ? OR c.registration_number LIKE ?)';
            $searchTerm = '%' . $filters['search'] . '%';
            $params[] = $searchTerm;
            $params[] = $searchTerm;
            $params[] = $searchTerm;
        }

        if (!empty($filters['age_min'])) {
            $whereClause .= ' AND c.birth_date <= ?';
            $params[] = date('Y-m-d', strtotime('-' . $filters['age_min'] . ' years'));
        }

        if (!empty($filters['age_max'])) {
            $whereClause .= ' AND c.birth_date >= ?';
            $params[] = date('Y-m-d', strtotime('-' . ($filters['age_max'] + 1) . ' years +1 day'));
        }

        // Get children
        $children = $this->db->fetchAll("
            SELECT c.*,
                   COUNT(DISTINCT cg.id) as guardians_count,
                   COUNT(DISTINCT cn.id) as notes_count,
                   COUNT(DISTINCT cd.id) as documents_count
            FROM children c
            LEFT JOIN child_guardians cg ON c.id = cg.child_id
            LEFT JOIN child_notes cn ON c.id = cn.child_id
            LEFT JOIN child_documents cd ON c.id = cd.child_id
            WHERE 1=1 {$whereClause}
            GROUP BY c.id
            ORDER BY c.registration_date DESC
            LIMIT ? OFFSET ?
        ", array_merge($params, [$limit, $offset]));

        // Get total count
        $total = $this->db->fetchOne("
            SELECT COUNT(*) as count FROM children c WHERE 1=1 {$whereClause}
        ", $params)['count'];

        return [
            'children' => $children,
            'pagination' => [
                'page' => $page,
                'limit' => $limit,
                'total' => $total,
                'pages' => ceil($total / $limit)
            ]
        ];
    }

    /**
     * Create a new child
     */
    public function createChild(array $childData, int $createdBy): int {
        // Separate basic child data from medical data
        $basicFields = ['first_name', 'last_name', 'birth_date', 'gender', 'address', 'phone', 'email', 'nationality', 'language', 'school', 'grade', 'status', 'registration_number'];
        $basicData = array_intersect_key($childData, array_flip($basicFields));

        // Generate registration number if not provided
        if (empty($basicData['registration_number'])) {
            $basicData['registration_number'] = 'REG-' . date('Y') . '-' . str_pad(rand(1, 9999), 4, '0', STR_PAD_LEFT);
        }

        $childId = $this->db->insert('children', array_merge($basicData, [
            'created_by' => $createdBy
        ]));

        // Handle medical data separately
        $medicalFields = ['blood_type', 'allergies', 'medications', 'medical_conditions', 'doctor_name', 'doctor_phone', 'insurance_provider', 'insurance_number', 'emergency_contact_name', 'emergency_contact_phone', 'emergency_contact_relationship', 'special_needs', 'medical_notes'];
        $medicalData = array_intersect_key($childData, array_flip($medicalFields));

        if (!empty($medicalData)) {
            $this->updateChildMedical($childId, $medicalData);
        }

        return $childId;
    }

    /**
     * Update child information
     */
    public function updateChild(int $childId, array $childData, int $updatedBy): void {
        $allowedFields = ['first_name', 'last_name', 'birth_date', 'gender', 'address', 'phone', 'email', 'nationality', 'language', 'school', 'grade', 'status'];
        $updateData = array_intersect_key($childData, array_flip($allowedFields));

        if (!empty($updateData)) {
            $updateData['updated_by'] = $updatedBy;
            $this->db->update('children', $updateData, 'id = ?', [$childId]);
        }

        // Handle medical data separately
        $medicalFields = ['blood_type', 'allergies', 'medications', 'medical_conditions', 'doctor_name', 'doctor_phone', 'insurance_provider', 'insurance_number', 'emergency_contact_name', 'emergency_contact_phone', 'emergency_contact_relationship', 'special_needs', 'medical_notes'];
        $medicalData = array_intersect_key($childData, array_flip($medicalFields));

        if (!empty($medicalData)) {
            $this->updateChildMedical($childId, $medicalData);
        }
    }

    /**
     * Get child details with all related information
     */
    public function getChildDetails(int $childId): array {
        // Get basic child info
        $child = $this->db->fetchOne("SELECT * FROM children WHERE id = ?", [$childId]);
        if (!$child) {
            throw new Exception('Child not found');
        }

        // Get medical information
        $medical = $this->db->fetchOne("SELECT * FROM child_medical WHERE child_id = ?", [$childId]);
        if ($medical && isset($medical['notes'])) {
            $medical['medical_notes'] = $medical['notes'];
            unset($medical['notes']);
        }
        $child['medical'] = $medical;

        // Get guardians
        $child['guardians'] = $this->db->fetchAll("SELECT * FROM child_guardians WHERE child_id = ? ORDER BY is_primary DESC, created_at", [$childId]);

        // Get documents
        $child['documents'] = $this->db->fetchAll("SELECT * FROM child_documents WHERE child_id = ? ORDER BY created_at DESC", [$childId]);

        // Map document fields for frontend compatibility
        foreach ($child['documents'] as &$doc) {
            $doc['name'] = $doc['original_name'];
            $doc['type'] = $doc['document_type'];
            $doc['size'] = $doc['file_size'];
        }

        // Get notes
        $child['notes'] = $this->db->fetchAll("SELECT cn.*, u.username as created_by_name FROM child_notes cn JOIN users u ON cn.created_by = u.id WHERE cn.child_id = ? ORDER BY cn.created_at DESC", [$childId]);

        // Get activity history
        $child['activity_history'] = $this->db->fetchAll("SELECT cah.*, u.username as staff_name FROM child_activity_history cah LEFT JOIN users u ON cah.staff_member = u.id WHERE cah.child_id = ? ORDER BY cah.activity_date DESC", [$childId]);

        return $child;
    }

    /**
     * Update child medical information
     */
    public function updateChildMedical(int $childId, array $medicalData): void {
        // Map 'medical_notes' field to 'notes' for database compatibility
        if (isset($medicalData['medical_notes'])) {
            $medicalData['notes'] = $medicalData['medical_notes'];
            unset($medicalData['medical_notes']);
        }

        $existing = $this->db->fetchOne("SELECT id FROM child_medical WHERE child_id = ?", [$childId]);

        if ($existing) {
            $this->db->update('child_medical', $medicalData, 'child_id = ?', [$childId]);
        } else {
            $this->db->insert('child_medical', array_merge($medicalData, ['child_id' => $childId]));
        }
    }

    /**
     * Add child guardian
     */
    public function addChildGuardian(int $childId, array $guardianData): int {
        return $this->db->insert('child_guardians', array_merge($guardianData, [
            'child_id' => $childId
        ]));
    }

    /**
     * Update child guardian
     */
    public function updateChildGuardian(int $guardianId, array $guardianData): void {
        $allowedFields = ['relationship', 'first_name', 'last_name', 'phone', 'mobile', 'email', 'address', 'workplace', 'work_phone', 'is_primary', 'can_pickup', 'emergency_contact', 'notes'];
        $updateData = array_intersect_key($guardianData, array_flip($allowedFields));

        if (!empty($updateData)) {
            $this->db->update('child_guardians', $updateData, 'id = ?', [$guardianId]);
        }
    }

    /**
     * Add child note
     */
    public function addChildNote(int $childId, array $noteData, int $createdBy): int {
        return $this->db->insert('child_notes', array_merge($noteData, [
            'child_id' => $childId,
            'created_by' => $createdBy
        ]));
    }

    /**
     * Add child document
     */
    public function addChildDocument(int $childId, array $documentData, int $uploadedBy): int {
        return $this->db->insert('child_documents', array_merge($documentData, [
            'child_id' => $childId,
            'uploaded_by' => $uploadedBy
        ]));
    }

    /**
     * Add child activity history
     */
    public function addChildActivity(int $childId, array $activityData): int {
        return $this->db->insert('child_activity_history', array_merge($activityData, [
            'child_id' => $childId
        ]));
    }

    /**
     * Delete child
     */
    public function deleteChild(int $childId): void {
        $this->db->delete('children', 'id = ?', [$childId]);
    }

    /**
     * Get communications with pagination and filtering
     */
    public function getCommunications(int $page = 1, int $limit = 20, array $filters = []): array {
        $offset = ($page - 1) * $limit;

        $whereClause = '';
        $params = [];

        if (!empty($filters['status'])) {
            $whereClause .= ' AND status = ?';
            $params[] = $filters['status'];
        }

        if (!empty($filters['communication_type'])) {
            $whereClause .= ' AND communication_type = ?';
            $params[] = $filters['communication_type'];
        }

        if (!empty($filters['priority'])) {
            $whereClause .= ' AND priority = ?';
            $params[] = $filters['priority'];
        }

        if (!empty($filters['target_audience'])) {
            $whereClause .= ' AND target_audience LIKE ?';
            $params[] = '%' . $filters['target_audience'] . '%';
        }

        if (!empty($filters['is_public'])) {
            $whereClause .= ' AND is_public = ?';
            $params[] = $filters['is_public'];
        }

        // Get communications
        $communications = $this->db->fetchAll("
            SELECT c.*, u.username as created_by_name
            FROM communications c
            LEFT JOIN users u ON c.created_by = u.id
            WHERE 1=1 {$whereClause}
            ORDER BY c.created_at DESC
            LIMIT ? OFFSET ?
        ", array_merge($params, [$limit, $offset]));

        // Get total count
        $total = $this->db->fetchOne("
            SELECT COUNT(*) as count FROM communications c WHERE 1=1 {$whereClause}
        ", $params)['count'];

        return [
            'communications' => $communications,
            'pagination' => [
                'page' => $page,
                'limit' => $limit,
                'total' => $total,
                'pages' => ceil($total / $limit)
            ]
        ];
    }

    /**
     * Create a new communication
     */
    public function createCommunication(array $communicationData, int $createdBy): int {
        // Set default values
        $communicationData['created_by'] = $createdBy;
        $communicationData['status'] = $communicationData['status'] ?? 'draft';

        // If publishing immediately, set published info
        if ($communicationData['status'] === 'published') {
            $communicationData['published_by'] = $createdBy;
            $communicationData['published_at'] = date('Y-m-d H:i:s');
        }

        return $this->db->insert('communications', $communicationData);
    }

    /**
     * Get communication details with attachments and comments
     */
    public function getCommunicationDetails(int $communicationId): ?array {
        $communication = $this->db->fetchOne("
            SELECT c.*, u.username as created_by_name, pu.username as published_by_name
            FROM communications c
            LEFT JOIN users u ON c.created_by = u.id
            LEFT JOIN users pu ON c.published_by = pu.id
            WHERE c.id = ?
        ", [$communicationId]);

        if (!$communication) {
            return null;
        }

        // Get attachments
        $communication['attachments'] = $this->db->fetchAll("
            SELECT ca.*, u.username as uploaded_by_name
            FROM communication_attachments ca
            LEFT JOIN users u ON ca.uploaded_by = u.id
            WHERE ca.communication_id = ?
            ORDER BY ca.created_at
        ", [$communicationId]);

        // Get comments
        $communication['comments'] = $this->db->fetchAll("
            SELECT cc.*, u.username as created_by_name, m.username as moderated_by_name
            FROM communication_comments cc
            LEFT JOIN users u ON cc.created_by = u.id
            LEFT JOIN users m ON cc.moderated_by = m.id
            WHERE cc.communication_id = ? AND cc.status = 'approved'
            ORDER BY cc.created_at
        ", [$communicationId]);

        return $communication;
    }

    /**
     * Update communication
     */
    public function updateCommunication(int $communicationId, array $communicationData): void {
        $allowedFields = ['title', 'content', 'communication_type', 'priority', 'is_public', 'status', 'target_audience', 'expires_at'];
        $updateData = array_intersect_key($communicationData, array_flip($allowedFields));

        if (!empty($updateData)) {
            $updateData['updated_at'] = date('Y-m-d H:i:s');

            // If publishing, set published info
            if (isset($communicationData['status']) && $communicationData['status'] === 'published' && !isset($communicationData['published_at'])) {
                $updateData['published_by'] = $communicationData['published_by'] ?? 1; // Default to admin
                $updateData['published_at'] = date('Y-m-d H:i:s');
            }

            $this->db->update('communications', $updateData, 'id = ?', [$communicationId]);
        }
    }

    /**
     * Delete communication
     */
    public function deleteCommunication(int $communicationId): void {
        $this->db->delete('communications', 'id = ?', [$communicationId]);
    }

    /**
     * Record communication view for analytics
     */
    public function recordCommunicationView(int $communicationId, ?int $userId): void {
        // Update view count
        $this->db->query("UPDATE communications SET view_count = view_count + 1 WHERE id = ?", [$communicationId]);

        // Record individual view
        $this->db->insert('communication_reads', [
            'communication_id' => $communicationId,
            'user_id' => $userId,
            'ip_address' => $_SERVER['REMOTE_ADDR'] ?? null,
            'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? null
        ]);
    }

    /**
     * Add comment to communication
     */
    public function addCommunicationComment(int $communicationId, array $commentData, ?int $createdBy): int {
        return $this->db->insert('communication_comments', array_merge($commentData, [
            'communication_id' => $communicationId,
            'created_by' => $createdBy
        ]));
    }

    /**
     * Get comments for a communication
     */
    public function getCommunicationComments(int $communicationId): array {
        return $this->db->fetchAll("
            SELECT cc.*, u.username as created_by_name, m.username as moderated_by_name
            FROM communication_comments cc
            LEFT JOIN users u ON cc.created_by = u.id
            LEFT JOIN users m ON cc.moderated_by = m.id
            WHERE cc.communication_id = ?
            ORDER BY cc.created_at
        ", [$communicationId]);
    }

    /**
     * Update comment status (approve/reject)
     */
    public function updateCommunicationComment(int $commentId, string $status, int $moderatedBy): void {
        $this->db->update('communication_comments', [
            'status' => $status,
            'moderated_by' => $moderatedBy,
            'moderated_at' => date('Y-m-d H:i:s')
        ], 'id = ?', [$commentId]);
    }

    /**
     * Get media folders with pagination
     */
    public function getMediaFolders(int $page = 1, int $limit = 20, int $parentId = null): array {
        $offset = ($page - 1) * $limit;

        $whereClause = '';
        $params = [];

        if ($parentId !== null) {
            $whereClause = 'WHERE parent_id = ?';
            $params[] = $parentId;
        } else {
            $whereClause = 'WHERE parent_id IS NULL'; // Root folders
        }

        // Get folders
        $folders = $this->db->fetchAll("
            SELECT f.*, u.username as created_by_name,
                   (SELECT COUNT(*) FROM media_folders WHERE parent_id = f.id) as subfolder_count,
                   (SELECT COUNT(*) FROM media_files WHERE folder_id = f.id) as file_count
            FROM media_folders f
            LEFT JOIN users u ON f.created_by = u.id
            {$whereClause}
            ORDER BY f.name
            LIMIT ? OFFSET ?
        ", array_merge($params, [$limit, $offset]));

        // Get total count
        $total = $this->db->fetchOne("
            SELECT COUNT(*) as count FROM media_folders f {$whereClause}
        ", $params)['count'];

        return [
            'folders' => $folders,
            'pagination' => [
                'page' => $page,
                'limit' => $limit,
                'total' => $total,
                'pages' => ceil($total / $limit)
            ]
        ];
    }

    /**
     * Create media folder
     */
    public function createMediaFolder(array $folderData, int $createdBy): int {
        // Build path
        $path = $folderData['name'];
        if (!empty($folderData['parent_id'])) {
            $parentPath = $this->db->fetchOne("SELECT path FROM media_folders WHERE id = ?", [$folderData['parent_id']])['path'];
            $path = $parentPath . '/' . $folderData['name'];
        }

        return $this->db->insert('media_folders', array_merge($folderData, [
            'path' => $path,
            'created_by' => $createdBy
        ]));
    }

    /**
     * Get media files with pagination
     */
    public function getMediaFiles(int $page = 1, int $limit = 20, ?int $folderId = null, string $search = ''): array {
        $offset = ($page - 1) * $limit;

        $whereClause = '';
        $params = [];

        if ($folderId !== null) {
            $whereClause .= ' AND mf.folder_id = ?';
            $params[] = $folderId;
        } else {
            // When no folder is specified, show only root-level files (folder_id IS NULL)
            $whereClause .= ' AND mf.folder_id IS NULL';
        }

        if (!empty($search)) {
            $whereClause .= ' AND (mf.original_name LIKE ? OR mf.filename LIKE ?)';
            $searchTerm = '%' . $search . '%';
            $params[] = $searchTerm;
            $params[] = $searchTerm;
        }

        // Get files
        $files = $this->db->fetchAll("
            SELECT mf.*, u.username as uploaded_by_name, f.name as folder_name
            FROM media_files mf
            LEFT JOIN users u ON mf.uploaded_by = u.id
            LEFT JOIN media_folders f ON mf.folder_id = f.id
            WHERE 1=1 {$whereClause}
            ORDER BY mf.created_at DESC
            LIMIT ? OFFSET ?
        ", array_merge($params, [$limit, $offset]));

        // Get total count
        $total = $this->db->fetchOne("
            SELECT COUNT(*) as count FROM media_files mf WHERE 1=1 {$whereClause}
        ", $params)['count'];

        return [
            'files' => $files,
            'pagination' => [
                'page' => $page,
                'limit' => $limit,
                'total' => $total,
                'pages' => ceil($total / $limit)
            ]
        ];
    }

    /**
     * Upload media file
     */
    public function uploadMediaFile(array $fileData, int $uploadedBy): int {
        // Generate unique filename
        $extension = pathinfo($fileData['original_name'], PATHINFO_EXTENSION);
        $filename = uniqid('media_', true) . '.' . $extension;

        // Determine file type
        $fileType = $this->getFileType($fileData['mime_type']);

        return $this->db->insert('media_files', array_merge($fileData, [
            'filename' => $filename,
            'file_type' => $fileType,
            'uploaded_by' => $uploadedBy
        ]));
    }

    /**
     * Get file type from MIME type
     */
    private function getFileType(string $mimeType): string {
        if (strpos($mimeType, 'image/') === 0) return 'image';
        if (strpos($mimeType, 'video/') === 0) return 'video';
        if (strpos($mimeType, 'audio/') === 0) return 'audio';
        if (in_array($mimeType, ['application/pdf', 'application/msword', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document'])) return 'document';
        return 'other';
    }

    /**
     * Get media file details
     */
    public function getMediaFile(int $fileId): ?array {
        $result = $this->db->fetchOne("
            SELECT mf.*, u.username as uploaded_by_name, f.name as folder_name
            FROM media_files mf
            LEFT JOIN users u ON mf.uploaded_by = u.id
            LEFT JOIN media_folders f ON mf.folder_id = f.id
            WHERE mf.id = ?
        ", [$fileId]);

        return $result ?: null;
    }

    /**
     * Delete media file
     */
    public function deleteMediaFile(int $fileId): void {
        $file = $this->getMediaFile($fileId);
        if ($file && file_exists($file['file_path'])) {
            unlink($file['file_path']);
        }

        $this->db->delete('media_files', 'id = ?', [$fileId]);
    }

    /**
     * Create sharing link for file or folder
     */
    public function createShareLink(string $resourceType, int $resourceId, int $sharedBy, string $permission = 'view', int $expiresHours = 24): string {
        $shareToken = bin2hex(random_bytes(16));

        $this->db->insert('media_sharing', [
            'resource_type' => $resourceType,
            'resource_id' => $resourceId,
            'shared_by_user_id' => $sharedBy,
            'permission' => $permission,
            'share_token' => $shareToken,
            'expires_at' => date('Y-m-d H:i:s', strtotime("+{$expiresHours} hours"))
        ]);

        // Update the resource as shared
        if ($resourceType === 'file') {
            $this->db->update('media_files', ['is_shared' => 1], 'id = ?', [$resourceId]);
        } else {
            $this->db->update('media_folders', ['is_shared' => 1], 'id = ?', [$resourceId]);
        }

        return $shareToken;
    }

    /**
     * Get shared resource by token
     */
    public function getSharedResource(string $token): ?array {
        $sharing = $this->db->fetchOne("
            SELECT * FROM media_sharing
            WHERE share_token = ? AND (expires_at IS NULL OR expires_at > datetime('now'))
        ", [$token]);

        if (!$sharing) return null;

        if ($sharing['resource_type'] === 'file') {
            $resource = $this->getMediaFile($sharing['resource_id']);
        } else {
            $resource = $this->db->fetchOne("
                SELECT f.*, u.username as created_by_name
                FROM media_folders f
                LEFT JOIN users u ON f.created_by = u.id
                WHERE f.id = ?
            ", [$sharing['resource_id']]);
        }

        if ($resource) {
            $resource['sharing'] = $sharing;
        }

        return $resource;
    }

    /**
     * Get folder contents (both folders and files)
     */
    public function getFolderContents(int $folderId, int $page = 1, int $limit = 50): array {
        $folders = $this->getMediaFolders(1, 100, $folderId)['folders'];
        $files = $this->getMediaFiles(1, 100, $folderId)['files'];

        return [
            'folders' => $folders,
            'files' => $files,
            'total_items' => count($folders) + count($files)
        ];
    }

    /**
     * Move file to different folder
     */
    public function moveMediaFile(int $fileId, ?int $newFolderId): void {
        $this->db->update('media_files', ['folder_id' => $newFolderId], 'id = ?', [$fileId]);
    }

    /**
     * Move folder to different parent
     */
    public function moveMediaFolder(int $folderId, ?int $newParentId): void {
        $folder = $this->db->fetchOne("SELECT * FROM media_folders WHERE id = ?", [$folderId]);

        // Build new path
        $newPath = $folder['name'];
        if ($newParentId) {
            $parentPath = $this->db->fetchOne("SELECT path FROM media_folders WHERE id = ?", [$newParentId])['path'];
            $newPath = $parentPath . '/' . $folder['name'];
        }

        $this->db->update('media_folders', [
            'parent_id' => $newParentId,
            'path' => $newPath
        ], 'id = ?', [$folderId]);

        // Update paths of all subfolders
        $this->updateSubfolderPaths($folderId, $newPath);
    }

    /**
     * Update paths of subfolders recursively
     */
    private function updateSubfolderPaths(int $parentId, string $parentPath): void {
        $subfolders = $this->db->fetchAll("SELECT id, name FROM media_folders WHERE parent_id = ?", [$parentId]);

        foreach ($subfolders as $subfolder) {
            $newPath = $parentPath . '/' . $subfolder['name'];
            $this->db->update('media_folders', ['path' => $newPath], 'id = ?', [$subfolder['id']]);
            $this->updateSubfolderPaths($subfolder['id'], $newPath);
        }
    }

    /**
     * Delete media folder and all its contents recursively
     */
    public function deleteMediaFolder(int $folderId): void {
        // Get all subfolders recursively
        $allFolderIds = $this->getAllSubfolderIds($folderId);
        $allFolderIds[] = $folderId; // Include the main folder

        // Delete all files in these folders
        foreach ($allFolderIds as $fid) {
            $files = $this->db->fetchAll("SELECT file_path FROM media_files WHERE folder_id = ?", [$fid]);
            foreach ($files as $file) {
                if (file_exists($file['file_path'])) {
                    unlink($file['file_path']);
                }
            }
            $this->db->delete('media_files', 'folder_id = ?', [$fid]);
        }

        // Delete all folders (subfolders first due to foreign key constraints)
        $allFolderIds = array_reverse($allFolderIds); // Delete subfolders first
        foreach ($allFolderIds as $fid) {
            $this->db->delete('media_folders', 'id = ?', [$fid]);
        }
    }

    /**
     * Get all subfolder IDs recursively
     */
    private function getAllSubfolderIds(int $parentId): array {
        $subfolderIds = [];
        $directSubfolders = $this->db->fetchAll("SELECT id FROM media_folders WHERE parent_id = ?", [$parentId]);

        foreach ($directSubfolders as $subfolder) {
            $subfolderIds[] = $subfolder['id'];
            // Recursively get subfolders of this subfolder
            $subfolderIds = array_merge($subfolderIds, $this->getAllSubfolderIds($subfolder['id']));
        }

        return $subfolderIds;
    }

    /**
     * Get animators with pagination and filtering
     */
    public function getAnimators(int $page = 1, int $limit = 20, array $filters = []): array {
        $offset = ($page - 1) * $limit;

        $whereClause = '';
        $params = [];

        if (!empty($filters['status'])) {
            $whereClause .= ' AND a.status = ?';
            $params[] = $filters['status'];
        }

        if (!empty($filters['search'])) {
            $whereClause .= ' AND (a.first_name LIKE ? OR a.last_name LIKE ? OR a.animator_number LIKE ? OR a.email LIKE ?)';
            $searchTerm = '%' . $filters['search'] . '%';
            $params[] = $searchTerm;
            $params[] = $searchTerm;
            $params[] = $searchTerm;
            $params[] = $searchTerm;
        }

        // Get animators
        $animators = $this->db->fetchAll("
            SELECT a.*,
                   COUNT(DISTINCT au.user_id) as linked_users_count,
                   COUNT(DISTINCT an.id) as notes_count,
                   COUNT(DISTINCT ad.id) as documents_count
            FROM animators a
            LEFT JOIN animator_users au ON a.id = au.animator_id
            LEFT JOIN animator_notes an ON a.id = an.animator_id
            LEFT JOIN animator_documents ad ON a.id = ad.animator_id
            WHERE 1=1 {$whereClause}
            GROUP BY a.id
            ORDER BY a.hire_date DESC
            LIMIT ? OFFSET ?
        ", array_merge($params, [$limit, $offset]));

        // Get total count
        $total = $this->db->fetchOne("
            SELECT COUNT(*) as count FROM animators a WHERE 1=1 {$whereClause}
        ", $params)['count'];

        return [
            'animators' => $animators,
            'pagination' => [
                'page' => $page,
                'limit' => $limit,
                'total' => $total,
                'pages' => ceil($total / $limit)
            ]
        ];
    }

    /**
     * Create a new animator
     */
    public function createAnimator(array $animatorData, int $createdBy): int {
        // Generate animator number if not provided
        if (empty($animatorData['animator_number'])) {
            $animatorData['animator_number'] = 'ANIM-' . date('Y') . '-' . str_pad(rand(1, 9999), 4, '0', STR_PAD_LEFT);
        }

        $animatorId = $this->db->insert('animators', array_merge($animatorData, [
            'created_by' => $createdBy
        ]));

        return $animatorId;
    }

    /**
     * Update animator information
     */
    public function updateAnimator(int $animatorId, array $animatorData, int $updatedBy): void {
        $allowedFields = ['first_name', 'last_name', 'birth_date', 'gender', 'address', 'phone', 'email', 'nationality', 'language', 'education', 'specialization', 'hire_date', 'status'];
        $updateData = array_intersect_key($animatorData, array_flip($allowedFields));

        if (!empty($updateData)) {
            $updateData['updated_by'] = $updatedBy;
            $this->db->update('animators', $updateData, 'id = ?', [$animatorId]);
        }
    }

    /**
     * Get animator details with all related information
     */
    public function getAnimatorDetails(int $animatorId): array {
        // Get basic animator info
        $animator = $this->db->fetchOne("SELECT * FROM animators WHERE id = ?", [$animatorId]);
        if (!$animator) {
            throw new Exception('Animator not found');
        }

        // Get linked users
        $animator['linked_users'] = $this->db->fetchAll("
            SELECT au.*, u.username, u.email
            FROM animator_users au
            JOIN users u ON au.user_id = u.id
            WHERE au.animator_id = ? AND au.is_active = 1
            ORDER BY au.relationship_type, au.assigned_at
        ", [$animatorId]);

        // Get documents
        $animator['documents'] = $this->db->fetchAll("
            SELECT ad.*, u.username as uploaded_by_name
            FROM animator_documents ad
            LEFT JOIN users u ON ad.uploaded_by = u.id
            WHERE ad.animator_id = ?
            ORDER BY ad.created_at DESC
        ", [$animatorId]);

        // Get notes
        $animator['notes'] = $this->db->fetchAll("
            SELECT an.*, u.username as created_by_name
            FROM animator_notes an
            JOIN users u ON an.created_by = u.id
            WHERE an.animator_id = ?
            ORDER BY an.created_at DESC
        ", [$animatorId]);

        // Get activity history
        $animator['activity_history'] = $this->db->fetchAll("
            SELECT aah.*, ce.title as event_title
            FROM animator_activity_history aah
            LEFT JOIN calendar_events ce ON aah.event_id = ce.id
            WHERE aah.animator_id = ?
            ORDER BY aah.activity_date DESC
        ", [$animatorId]);

        // Get availability
        $animator['availability'] = $this->db->fetchAll("
            SELECT * FROM animator_availability
            WHERE animator_id = ?
            ORDER BY day_of_week, start_time
        ", [$animatorId]);

        return $animator;
    }

    /**
     * Link animator to user account
     */
    public function linkAnimatorToUser(int $animatorId, int $userId, string $relationshipType, int $assignedBy, string $notes = ''): int {
        // Check if link already exists
        $existing = $this->db->fetchOne("
            SELECT id FROM animator_users 
            WHERE animator_id = ? AND user_id = ?
        ", [$animatorId, $userId]);

        if ($existing) {
            throw new Exception('Animator is already linked to this user');
        }

        return $this->db->insert('animator_users', [
            'animator_id' => $animatorId,
            'user_id' => $userId,
            'relationship_type' => $relationshipType,
            'assigned_by' => $assignedBy,
            'notes' => $notes
        ]);
    }

    /**
     * Unlink animator from user account
     */
    public function unlinkAnimatorFromUser(int $animatorId, int $userId): void {
        $this->db->delete('animator_users', 'animator_id = ? AND user_id = ?', [$animatorId, $userId]);
    }

    /**
     * Update animator-user relationship
     */
    public function updateAnimatorUserLink(int $animatorId, int $userId, array $updateData): void {
        $allowedFields = ['relationship_type', 'is_active', 'notes'];
        $updateData = array_intersect_key($updateData, array_flip($allowedFields));

        if (!empty($updateData)) {
            $this->db->update('animator_users', $updateData, 'animator_id = ? AND user_id = ?', [$animatorId, $userId]);
        }
    }

    /**
     * Get animators linked to a user
     */
    public function getAnimatorsByUser(int $userId): array {
        return $this->db->fetchAll("
            SELECT a.*, au.relationship_type, au.assigned_at
            FROM animators a
            JOIN animator_users au ON a.id = au.animator_id
            WHERE au.user_id = ? AND au.is_active = 1
            ORDER BY au.relationship_type, au.assigned_at
        ", [$userId]);
    }

    /**
     * Get users linked to an animator
     */
    public function getUsersByAnimator(int $animatorId): array {
        return $this->db->fetchAll("
            SELECT u.*, au.relationship_type, au.assigned_at, au.notes
            FROM users u
            JOIN animator_users au ON u.id = au.user_id
            WHERE au.animator_id = ? AND au.is_active = 1
            ORDER BY au.relationship_type, au.assigned_at
        ", [$animatorId]);
    }

    /**
     * Add animator document
     */
    public function addAnimatorDocument(int $animatorId, array $documentData, int $uploadedBy): int {
        return $this->db->insert('animator_documents', array_merge($documentData, [
            'animator_id' => $animatorId,
            'uploaded_by' => $uploadedBy
        ]));
    }

    /**
     * Add animator note
     */
    public function addAnimatorNote(int $animatorId, array $noteData, int $createdBy): int {
        return $this->db->insert('animator_notes', array_merge($noteData, [
            'animator_id' => $animatorId,
            'created_by' => $createdBy
        ]));
    }

    /**
     * Add animator activity history
     */
    public function addAnimatorActivity(int $animatorId, array $activityData): int {
        return $this->db->insert('animator_activity_history', array_merge($activityData, [
            'animator_id' => $animatorId
        ]));
    }

    /**
     * Set animator availability
     */
    public function setAnimatorAvailability(int $animatorId, array $availabilityData): int {
        // First remove existing availability for this animator
        $this->db->delete('animator_availability', 'animator_id = ?', [$animatorId]);

        // Insert new availability records
        foreach ($availabilityData as $dayData) {
            $this->db->insert('animator_availability', array_merge($dayData, [
                'animator_id' => $animatorId
            ]));
        }

        return count($availabilityData);
    }

    /**
     * Delete animator
     */
    public function deleteAnimator(int $animatorId): void {
        $this->db->delete('animators', 'id = ?', [$animatorId]);
    }

    /**
     * Validate password strength
     */
    private function validatePassword(string $password): void {
        $config = $this->config['security'];

        if (strlen($password) < $config['password_min_length']) {
            throw new Exception("Password must be at least {$config['password_min_length']} characters long");
        }

        if ($config['password_require_uppercase'] && !preg_match('/[A-Z]/', $password)) {
            throw new Exception("Password must contain at least one uppercase letter");
        }

        if ($config['password_require_lowercase'] && !preg_match('/[a-z]/', $password)) {
            throw new Exception("Password must contain at least one lowercase letter");
        }

        if ($config['password_require_numbers'] && !preg_match('/[0-9]/', $password)) {
            throw new Exception("Password must contain at least one number");
        }
    }

    /**
     * Generate JWT token
     */
    private function generateToken(array $user, array $roles): string {
        $payload = [
            'iss' => $this->config['system']['name'],
            'aud' => $this->config['system']['name'],
            'iat' => time(),
            'exp' => time() + ($this->config['jwt']['expiration_hours'] * 3600),
            'user_id' => $user['id'],
            'username' => $user['username'],
            'roles' => array_column($roles, 'name')
        ];

        return JWT::encode($payload, $this->config['jwt']['secret'], 'HS256');
    }

    /**
     * Create session record
     */
    private function createSession(int $userId, string $token): void {
        $expiresAt = date('Y-m-d H:i:s', strtotime("+{$this->config['jwt']['expiration_hours']} hours"));

        $this->db->insert('user_sessions', [
            'user_id' => $userId,
            'session_token' => $token,
            'ip_address' => $_SERVER['REMOTE_ADDR'] ?? null,
            'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? null,
            'expires_at' => $expiresAt
        ]);
    }
}
