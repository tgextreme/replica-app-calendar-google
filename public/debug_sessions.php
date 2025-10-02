<?php
require_once '../config/Database.php';

echo "=== CHECKING USER SESSIONS ===\n";

try {
    $db = Database::getInstance();
    
    // Check active sessions
    echo "Active sessions in database:\n";
    $sessions = $db->fetchAll("SELECT * FROM user_sessions ORDER BY created_at DESC LIMIT 5");
    foreach ($sessions as $session) {
        echo "Session ID: {$session['session_id']}, User ID: {$session['user_id']}, Created: {$session['created_at']}\n";
    }
    
    if (empty($sessions)) {
        echo "No active sessions found.\n";
    }
    
    echo "\n=== TESTING DIRECT LOGIN ===\n";
    
    // Test direct login
    $loginData = [
        'email' => 'admin@admin.com',
        'password' => 'passwd12345@'
    ];
    
    // Include auth controller
    require_once '../app/models/User.php';
    require_once '../app/controllers/AuthController.php';
    
    $authController = new AuthController();
    
    // Start session for test
    session_start();
    
    // Simulate POST data
    $_POST = $loginData;
    
    echo "Attempting login with admin@admin.com...\n";
    
    // Capture output
    ob_start();
    $authController->login();
    $output = ob_get_clean();
    
    echo "Login response: $output\n";
    
    // Check if session was created
    if (isset($_SESSION['user_id'])) {
        echo "Session created successfully! User ID: {$_SESSION['user_id']}\n";
    } else {
        echo "No session created.\n";
    }
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?>