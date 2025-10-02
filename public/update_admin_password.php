<?php
require_once '../config/Database.php';

echo "=== UPDATING ADMIN PASSWORD ===\n";

$db = Database::getInstance();
$newPassword = 'passwd12345@';
$newHash = md5($newPassword);

echo "New password: $newPassword\n";
echo "New hash: $newHash\n";

$db->query('UPDATE users SET password_hash = ? WHERE email = ?', [$newHash, 'admin@calendario.local']);

echo "Admin password updated successfully!\n";

// Verify
$admin = $db->fetch("SELECT email, password_hash FROM users WHERE email = ?", ['admin@calendario.local']);
echo "Verification - Admin hash now: {$admin['password_hash']}\n";
?>