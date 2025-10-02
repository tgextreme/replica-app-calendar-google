<?php
require_once '../config/Database.php';

echo "=== CHECKING ADMIN CREDENTIALS ===\n";

try {
    $db = Database::getInstance();
    
    // Check admin user
    $admin = $db->fetch("SELECT * FROM users WHERE email = ?", ['admin@calendario.local']);
    
    if ($admin) {
        echo "Admin user found:\n";
        echo "- Email: {$admin['email']}\n";
        echo "- Name: {$admin['full_name']}\n";
        echo "- Password hash: {$admin['password']}\n";
        
        // Test different password combinations
        $testPasswords = [
            'passwd12345@',
            'admin',
            'password',
            '123456'
        ];
        
        echo "\nTesting password hashes:\n";
        foreach ($testPasswords as $password) {
            $md5Hash = md5($password);
            echo "- '$password' -> MD5: $md5Hash";
            if ($md5Hash === $admin['password']) {
                echo " ✓ MATCH!";
            }
            echo "\n";
        }
        
    } else {
        echo "Admin user NOT found!\n";
        
        echo "\nAll users in database:\n";
        $users = $db->fetchAll("SELECT id, email, full_name FROM users");
        foreach ($users as $user) {
            echo "- ID: {$user['id']}, Email: {$user['email']}, Name: {$user['full_name']}\n";
        }
    }
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?>