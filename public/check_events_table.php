<?php
require_once '../config/database.php';

$db = Database::getInstance();
$conn = $db->getConnection();

echo "=== ESTRUCTURA DE LA TABLA EVENTS ===\n";
$stmt = $conn->query('DESCRIBE events');
while($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    echo $row['Field'] . " - " . $row['Type'] . " - " . $row['Null'] . " - " . $row['Default'] . "\n";
}
?>