<?php
require_once '../config/database.php';

$db = Database::getInstance();
$conn = $db->getConnection();

echo "=== ESTRUCTURA TABLA USERS ===\n";
$stmt = $conn->query('DESCRIBE users');
while($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    echo $row['Field'] . " - " . $row['Type'] . " - " . $row['Null'] . " - " . $row['Default'] . "\n";
}

echo "\n=== VERIFICAR ROL DE ADMIN ===\n";
// Asumir que el usuario con username 'admin' es administrador
$stmt = $conn->prepare('SELECT id, username, full_name FROM users WHERE username = ?');
$stmt->execute(['admin']);
$admin = $stmt->fetch(PDO::FETCH_ASSOC);
if ($admin) {
    echo "Admin user found: ID {$admin['id']}, {$admin['full_name']}\n";
} else {
    echo "No admin user found\n";
}
?>