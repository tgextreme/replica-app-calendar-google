<?php
require_once '../config/database.php';

$db = Database::getInstance();
$conn = $db->getConnection();

try {
    echo "Modificando tabla events para permitir calendar_id NULL...\n";
    
    // Modificar la columna calendar_id para permitir NULL
    $sql = "ALTER TABLE events MODIFY COLUMN calendar_id INT NULL";
    $conn->exec($sql);
    
    echo "✅ Tabla events modificada exitosamente\n";
    
    echo "\n=== NUEVA ESTRUCTURA DE CALENDAR_ID ===\n";
    $stmt = $conn->query("SHOW COLUMNS FROM events LIKE 'calendar_id'");
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    print_r($row);
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}
?>