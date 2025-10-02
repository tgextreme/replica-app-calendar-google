<?php
require_once '../config/Database.php';

$db = Database::getInstance();
echo "=== USERS TABLE STRUCTURE ===\n";

$cols = $db->fetchAll('DESCRIBE users');
foreach($cols as $col) {
    echo $col['Field'] . ': ' . $col['Type'] . "\n";
}

echo "\n=== ADMIN USER DATA ===\n";
$admin = $db->fetch("SELECT * FROM users WHERE email = ?", ['admin@calendario.local']);
if ($admin) {
    foreach ($admin as $key => $value) {
        echo "$key: $value\n";
    }
} else {
    echo "Admin not found\n";
}
?>