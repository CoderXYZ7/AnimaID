<?php

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../src/Database.php';

use AnimaID\Config\ConfigManager;

// Load environment variables
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/..');
$dotenv->safeLoad();

echo "Starting Attendance Link Repair...\n";

$db = Database::getInstance();

// 1. Fix Event Participants -> Children links
echo "Checking Event Participants links...\n";

// Get all participants with NULL child_id
$participants = $db->fetchAll("
    SELECT id, child_name, child_surname 
    FROM event_participants 
    WHERE child_id IS NULL
");

echo "Found " . count($participants) . " participants without child link.\n";

$fixed = 0;
foreach ($participants as $p) {
    // Try to find matching child (Case-insensitive and trimmed)
    $child = $db->fetchOne("
        SELECT id FROM children 
        WHERE TRIM(UPPER(first_name)) = TRIM(UPPER(?)) 
        AND TRIM(UPPER(last_name)) = TRIM(UPPER(?))
    ", [$p['child_name'], $p['child_surname']]);

    if ($child) {
        $db->update('event_participants', ['child_id' => $child['id']], 'id = ?', [$p['id']]);
        echo "  [FIXED] Linked participant '{$p['child_name']} {$p['child_surname']}' to Child ID {$child['id']}\n";
        $fixed++;
    } else {
        echo "  [WARNING] No matching child found for '{$p['child_name']} {$p['child_surname']}'\n";
    }
}

echo "Fixed $fixed participant links.\n\n";

// 2. Fix Attendance Records -> Event Participants links
// (This is less likely to be broken as it uses IDs, but good to check)
echo "Checking Attendance Records integrity...\n";

$orphans = $db->fetchAll("
    SELECT ar.id, ar.participant_id 
    FROM attendance_records ar 
    LEFT JOIN event_participants ep ON ar.participant_id = ep.id 
    WHERE ep.id IS NULL
");

if (count($orphans) > 0) {
    echo "WARNING: Found " . count($orphans) . " attendance records pointing to non-existent participants!\n";
    foreach ($orphans as $o) {
        echo "  Attendance Record ID {$o['id']} has invalid Participant ID {$o['participant_id']}\n";
    }
} else {
    echo "All attendance records have valid participants.\n";
}

echo "\nRepair complete.\n";

echo "\n---------------------------------------------------\n";
echo "PARTICIPANT CONNECTION REPORT\n";
echo "---------------------------------------------------\n";

$allParticipants = $db->fetchAll("
    SELECT ep.id, ep.child_name, ep.child_surname, ep.child_id, c.first_name, c.last_name
    FROM event_participants ep
    LEFT JOIN children c ON ep.child_id = c.id
    ORDER BY ep.child_surname, ep.child_name
");

printf("%-5s | %-30s | %-10s | %-30s\n", "ID", "Participant Name", "Child ID", "Connected Child Name");
echo str_repeat("-", 85) . "\n";

foreach ($allParticipants as $p) {
    $pName = $p['child_name'] . ' ' . $p['child_surname'];
    $cName = $p['first_name'] ? ($p['first_name'] . ' ' . $p['last_name']) : "---";
    $status = $p['child_id'] ? $p['child_id'] : "MISSING";
    
    printf("%-5d | %-30s | %-10s | %-30s\n", $p['id'], substr($pName, 0, 30), $status, substr($cName, 0, 30));
}
echo str_repeat("-", 85) . "\n";
