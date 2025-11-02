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
     * Check in/out participant
     */
    public function checkInOutParticipant(int $participantId, int $eventId, string $action, int $staffId, string $notes = ''): void {
        $now = date('Y-m-d H:i:s');

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
        // Generate registration number if not provided
        if (empty($childData['registration_number'])) {
            $childData['registration_number'] = 'REG-' . date('Y') . '-' . str_pad(rand(1, 9999), 4, '0', STR_PAD_LEFT);
        }

        $childId = $this->db->insert('children', array_merge($childData, [
            'created_by' => $createdBy
        ]));

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
        $child['medical'] = $this->db->fetchOne("SELECT * FROM child_medical WHERE child_id = ?", [$childId]);

        // Get guardians
        $child['guardians'] = $this->db->fetchAll("SELECT * FROM child_guardians WHERE child_id = ? ORDER BY is_primary DESC, created_at", [$childId]);

        // Get documents
        $child['documents'] = $this->db->fetchAll("SELECT * FROM child_documents WHERE child_id = ? ORDER BY created_at DESC", [$childId]);

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
