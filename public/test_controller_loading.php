<?php
// Test CalendarController loading

echo "=== TESTING CONTROLLER LOADING ===\n";

// Include required dependencies in correct order
try {
    echo "Loading Database...\n";
    require_once '../config/Database.php';
    
    echo "Loading User model...\n";
    require_once '../app/models/User.php';
    
    echo "Loading Calendar model...\n";
    require_once '../app/models/Calendar.php';
    
    echo "Loading AuthController...\n";
    require_once '../app/controllers/AuthController.php';
    
    echo "Loading CalendarController...\n";
    require_once '../app/controllers/CalendarController.php';
    
    echo "Creating CalendarController instance...\n";
    $controller = new CalendarController();
    
    echo "SUCCESS: CalendarController loaded and instantiated!\n";
    
} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
} catch (Error $e) {
    echo "FATAL ERROR: " . $e->getMessage() . "\n";
    echo "In file: " . $e->getFile() . " line " . $e->getLine() . "\n";
}
?>