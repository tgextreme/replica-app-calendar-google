<?php
// Direct login test - create a valid session

// Start session with proper name
session_name('calendar_session');
session_start();

require_once '../config/Database.php';
require_once '../app/models/User.php';

echo "=== CREATING VALID SESSION ===\n";

try {
    $db = Database::getInstance();
    
    // Find admin user
    $user = $db->fetch("SELECT * FROM users WHERE email = ?", ['admin@admin.com']);
    
    if (!$user) {
        echo "Admin user not found!\n";
        exit;
    }
    
    echo "Found admin user: {$user['full_name']} (ID: {$user['id']})\n";
    
    // Verify password
    $password = 'passwd12345@';
    $hashedPassword = md5($password); // Using MD5 as per your setup
    
    if ($user['password'] === $hashedPassword) {
        echo "Password verified!\n";
        
        // Create session
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['user_email'] = $user['email'];
        $_SESSION['user_name'] = $user['full_name'];
        
        // Generate session ID and save to database
        $sessionId = session_id();
        $userId = $user['id'];
        $ipAddress = '127.0.0.1';
        $userAgent = 'CLI Test';
        $expiresAt = date('Y-m-d H:i:s', time() + 3600); // 1 hour from now
        
        // Insert session into database
        $db->query("INSERT INTO user_sessions (id, user_id, ip_address, user_agent, expires_at, is_active) VALUES (?, ?, ?, ?, ?, 1)
                   ON DUPLICATE KEY UPDATE last_activity = NOW(), expires_at = ?", 
                   [$sessionId, $userId, $ipAddress, $userAgent, $expiresAt, $expiresAt]);
        
        echo "Session created successfully!\n";
        echo "Session ID: $sessionId\n";
        echo "Session expires at: $expiresAt\n";
        
        // Test the session
        echo "\n=== TESTING SESSION ===\n";
        echo "Session variables:\n";
        foreach ($_SESSION as $key => $value) {
            echo "  $key = $value\n";
        }
        
    } else {
        echo "Password verification failed!\n";
        echo "Expected: $hashedPassword\n";
        echo "Got: {$user['password']}\n";
    }
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?>