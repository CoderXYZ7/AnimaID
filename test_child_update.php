<?php

// Test child update API
require_once 'config.php';
require_once 'src/Database.php';
require_once 'src/Auth.php';

echo "Testing child update API...\n";

try {
    $auth = new Auth();

    // Login as admin to get a valid token
    $loginResult = $auth->login('admin', 'Admin123!@#');
    $token = $loginResult['token'];

    echo "Got token for admin\n";

    // Check if child with ID 1 exists
    $db = Database::getInstance();
    $child = $db->fetchOne("SELECT * FROM children WHERE id = 1");
    if (!$child) {
        echo "Child with ID 1 does not exist. Creating one...\n";

        // Create a test child
        $childId = $auth->createChild([
            'first_name' => 'Test',
            'last_name' => 'Child',
            'birth_date' => '2018-01-01',
            'gender' => 'male'
        ], 1);

        echo "Created child with ID: $childId\n";
    } else {
        echo "Child exists: " . json_encode($child) . "\n";
    }

    // Test the update method directly
    echo "Testing updateChild method directly...\n";

    // Create a large request body similar to what might be causing the issue
    $updateData = [
        'first_name' => 'Updated',
        'last_name' => 'Child',
        'birth_date' => '2018-01-01',
        'gender' => 'male',
        'address' => '123 Test Street, Test City',
        'phone' => '+39 123 456 7890',
        'email' => 'test@example.com',
        'nationality' => 'Italian',
        'language' => 'Italian',
        'school' => 'Test Elementary School',
        'grade' => '2nd Grade',
        'status' => 'active',
        // Medical data
        'blood_type' => 'A+',
        'allergies' => 'Peanuts, Shellfish',
        'medications' => 'None',
        'medical_conditions' => 'None',
        'doctor_name' => 'Dr. Test',
        'doctor_phone' => '+39 098 765 4321',
        'insurance_provider' => 'Test Insurance',
        'insurance_number' => 'INS123456',
        'emergency_contact_name' => 'Emergency Contact',
        'emergency_contact_phone' => '+39 111 222 3333',
        'emergency_contact_relationship' => 'Grandparent',
        'special_needs' => 'None',
        'medical_notes' => 'Some additional medical notes that might make the request body larger'
    ];

    echo "Request body size: " . strlen(json_encode($updateData)) . " bytes\n";

    try {
        $result = $auth->updateChild(1, $updateData, 1);
        echo "Update successful: " . json_encode($result) . "\n";
    } catch (Exception $e) {
        echo "Update failed: " . $e->getMessage() . "\n";
        echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
    }

} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
}
