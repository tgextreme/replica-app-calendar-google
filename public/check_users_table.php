<?php
require_once '../config/database.php';

$db = Database::getInstance();
$conn = $db->getConnection();

echo "=== ESTRUCTURA DE LA TABLA USERS ===\n";
$stmt = $conn->query('DESCRIBE users');
while($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    echo $row['Field'] . " - " . $row['Type'] . " - " . $row['Null'] . " - " . $row['Default'] . "\n";
}

echo "\n=== TODOS LOS USUARIOS ===\n";
$stmt = $conn->query('SELECT * FROM users');
while($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    print_r($row);
}
?>