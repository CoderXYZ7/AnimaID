<?php
/**
 * Test the getEventRegister endpoint
 * Access at: https://animaidsgn.mywire.org/test-register.php?event_id=35&date=2025-12-01
 */

require_once __DIR__ . '/src/Database.php';
require_once __DIR__ . '/src/Auth.php';

header('Content-Type: application/json');

try {
    $eventId = isset($_GET['event_id']) ? (int)$_GET['event_id'] : 0;
    $date = $_GET['date'] ?? date('Y-m-d');
    
    if (!$eventId) {
        throw new Exception('event_id parameter is required');
    }
    
    $auth = new Auth();
    $register = $auth->getEventRegister($eventId, $date);
    
    echo json_encode([
        'success' => true,
        'event_id' => $eventId,
        'date' => $date,
        'register' => $register,
        'count' => count($register)
    ], JSON_PRETTY_PRINT);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage(),
        'trace' => $e->getTraceAsString()
    ], JSON_PRETTY_PRINT);
}
