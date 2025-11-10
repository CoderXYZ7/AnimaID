<?php
/**
 * Migration script to convert from template-based availability to week types system
 */

require_once 'config.php';
require_once 'database/init.php';
require_once 'src/Database.php';

try {
    $db = Database::getInstance();
    $pdo = $db->getConnection();

    // Start transaction
    $pdo->beginTransaction();

    echo "Starting migration from template-based to week types system...\n";

    // Check if we have any animators in the database
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM animators");
    $animatorCount = $stmt->fetch(PDO::FETCH_ASSOC)['count'];

    if ($animatorCount == 0) {
        echo "No animators found in database. Creating sample animators for testing...\n";

        // Create sample animators
        $sampleAnimators = [
            ['Mario', 'Rossi', '1985-03-15', 'M', 'mario.rossi@email.com', '+39 333 1234567', 'Active', 1],
            ['Giulia', 'Bianchi', '1990-07-22', 'F', 'giulia.bianchi@email.com', '+39 334 2345678', 'Active', 1],
            ['Luca', 'Verdi', '1988-11-10', 'M', 'luca.verdi@email.com', '+39 335 3456789', 'Active', 1],
        ];

        $stmt = $pdo->prepare("
            INSERT INTO animators (first_name, last_name, birth_date, gender, email, phone, status, created_by)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?)
        ");

        foreach ($sampleAnimators as $animator) {
            $stmt->execute($animator);
        }

        echo "Created " . count($sampleAnimators) . " sample animators\n";
    }

    // Step 1: Get all animators with template assignments (if old system exists)
    $animatorsWithTemplates = [];
    try {
        $stmt = $pdo->query("
            SELECT DISTINCT a.id, a.first_name, a.last_name, ta.template_id, t.name as template_name
            FROM animators a
            LEFT JOIN template_assignments ta ON a.id = ta.animator_id
            LEFT JOIN availability_templates t ON ta.template_id = t.id
            WHERE ta.template_id IS NOT NULL
        ");
        $animatorsWithTemplates = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (Exception $e) {
        echo "Old template system tables not found, skipping template migration\n";
    }

    echo "Found " . count($animatorsWithTemplates) . " animators with template assignments\n";

    foreach ($animatorsWithTemplates as $animator) {
        echo "Processing animator: {$animator['first_name']} {$animator['last_name']} (ID: {$animator['id']})\n";

        // Step 2: Create a week type for this animator based on their template
        $weekTypeName = $animator['template_name'] ?: "Default Schedule";
        $weekTypeDescription = "Migrated from template: {$weekTypeName}";

        // Insert week type
        $stmt = $pdo->prepare("
            INSERT INTO animator_week_types (animator_id, name, description, created_by, created_at, updated_at)
            VALUES (?, ?, ?, 1, CURRENT_TIMESTAMP, CURRENT_TIMESTAMP)
        ");
        $stmt->execute([$animator['id'], $weekTypeName, $weekTypeDescription]);
        $weekTypeId = $pdo->lastInsertId();

        echo "  Created week type: {$weekTypeName} (ID: {$weekTypeId})\n";

        // Step 3: Get availability data for this template
        $stmt = $pdo->prepare("
            SELECT * FROM availability_templates_availability
            WHERE template_id = ?
            ORDER BY day_of_week
        ");
        $stmt->execute([$animator['template_id']]);
        $templateAvailability = $stmt->fetchAll(PDO::FETCH_ASSOC);

        echo "  Found " . count($templateAvailability) . " availability records for template\n";

        // Step 4: Insert availability data for the new week type
        foreach ($templateAvailability as $availability) {
            $stmt = $pdo->prepare("
                INSERT INTO animator_week_availability
                (week_type_id, day_of_week, start_time, end_time, is_available, notes, created_at, updated_at)
                VALUES (?, ?, ?, ?, ?, ?, CURRENT_TIMESTAMP, CURRENT_TIMESTAMP)
            ");
            $stmt->execute([
                $weekTypeId,
                $availability['day_of_week'],
                $availability['start_time'],
                $availability['end_time'],
                $availability['is_available'],
                $availability['notes']
            ]);
        }

        echo "  Migrated " . count($templateAvailability) . " availability records\n";
    }

    // Step 5: Create default week types for animators without templates
    // Since old system doesn't exist, all animators need default week types
    $stmt = $pdo->query("
        SELECT a.id, a.first_name, a.last_name
        FROM animators a
        WHERE NOT EXISTS (
            SELECT 1 FROM animator_week_types awt WHERE awt.animator_id = a.id
        )
    ");
    $animatorsWithoutTemplates = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo "Found " . count($animatorsWithoutTemplates) . " animators without template assignments\n";

    foreach ($animatorsWithoutTemplates as $animator) {
        echo "Processing animator without template: {$animator['first_name']} {$animator['last_name']} (ID: {$animator['id']})\n";

        // Create a default week type
        $weekTypeName = "Default Schedule";
        $weekTypeDescription = "Default availability schedule";

        $stmt = $pdo->prepare("
            INSERT INTO animator_week_types (animator_id, name, description, created_by, created_at, updated_at)
            VALUES (?, ?, ?, 1, CURRENT_TIMESTAMP, CURRENT_TIMESTAMP)
        ");
        $stmt->execute([$animator['id'], $weekTypeName, $weekTypeDescription]);
        $weekTypeId = $pdo->lastInsertId();

        echo "  Created default week type: {$weekTypeName} (ID: {$weekTypeId})\n";

        // Create default availability (Monday-Friday, 9 AM - 5 PM)
        $defaultAvailability = [
            ['day_of_week' => 'Monday', 'start_time' => '09:00', 'end_time' => '17:00', 'is_available' => 1],
            ['day_of_week' => 'Tuesday', 'start_time' => '09:00', 'end_time' => '17:00', 'is_available' => 1],
            ['day_of_week' => 'Wednesday', 'start_time' => '09:00', 'end_time' => '17:00', 'is_available' => 1],
            ['day_of_week' => 'Thursday', 'start_time' => '09:00', 'end_time' => '17:00', 'is_available' => 1],
            ['day_of_week' => 'Friday', 'start_time' => '09:00', 'end_time' => '17:00', 'is_available' => 1],
            ['day_of_week' => 'Saturday', 'start_time' => '09:00', 'end_time' => '17:00', 'is_available' => 0],
            ['day_of_week' => 'Sunday', 'start_time' => '09:00', 'end_time' => '17:00', 'is_available' => 0],
        ];

        foreach ($defaultAvailability as $availability) {
            $stmt = $pdo->prepare("
                INSERT INTO animator_week_availability
                (week_type_id, day_of_week, start_time, end_time, is_available, notes, created_at, updated_at)
                VALUES (?, ?, ?, ?, ?, '', CURRENT_TIMESTAMP, CURRENT_TIMESTAMP)
            ");
            $stmt->execute([
                $weekTypeId,
                $availability['day_of_week'],
                $availability['start_time'],
                $availability['end_time'],
                $availability['is_available']
            ]);
        }

        echo "  Created default availability schedule\n";
    }

    // Step 6: Migrate availability exceptions
    echo "Migrating availability exceptions...\n";

    $exceptions = [];
    try {
        $stmt = $pdo->query("
            SELECT ae.*, a.first_name, a.last_name
            FROM availability_exceptions ae
            JOIN animators a ON ae.animator_id = a.id
        ");
        $exceptions = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (Exception $e) {
        echo "Old availability_exceptions table not found, skipping exceptions migration\n";
    }

    echo "Found " . count($exceptions) . " availability exceptions\n";

    foreach ($exceptions as $exception) {
        // Get the week type for this animator (should be the first one created)
        $stmt = $pdo->prepare("
            SELECT id FROM animator_week_types
            WHERE animator_id = ?
            ORDER BY created_at ASC
            LIMIT 1
        ");
        $stmt->execute([$exception['animator_id']]);
        $weekType = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($weekType) {
            // Insert exception for the week type
            $stmt = $pdo->prepare("
                INSERT INTO animator_week_type_exceptions
                (week_type_id, exception_date, is_available, notes, created_at, updated_at)
                VALUES (?, ?, ?, ?, CURRENT_TIMESTAMP, CURRENT_TIMESTAMP)
            ");
            $stmt->execute([
                $weekType['id'],
                $exception['exception_date'],
                $exception['is_available'],
                $exception['notes']
            ]);

            echo "  Migrated exception for {$exception['first_name']} {$exception['last_name']} on {$exception['exception_date']}\n";
        }
    }

    // Step 7: Update any code that references the old system
    // This would need to be done in the API endpoints and any other places that use the old system

    // Commit transaction
    $pdo->commit();

    echo "\nMigration completed successfully!\n";
    echo "Summary:\n";
    echo "- Migrated " . count($animatorsWithTemplates) . " animators with existing templates\n";
    echo "- Created default week types for " . count($animatorsWithoutTemplates) . " animators\n";
    echo "- Migrated " . count($exceptions) . " availability exceptions\n";

    echo "\nNext steps:\n";
    echo "1. Update API endpoints to use the new week types system\n";
    echo "2. Update frontend code to use new endpoints\n";
    echo "3. Test the new system thoroughly\n";
    echo "4. Remove old template-related tables after confirming everything works\n";

} catch (Exception $e) {
    // Rollback transaction on error
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }

    echo "Migration failed: " . $e->getMessage() . "\n";
    exit(1);
}
?>
