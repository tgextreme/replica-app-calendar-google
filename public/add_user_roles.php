<?php
require_once '../config/database.php';

$db = Database::getInstance();
$conn = $db->getConnection();

try {
    echo "Agregando campo role a la tabla users...\n";
    
    // Agregar campo role
    $sql = "ALTER TABLE users ADD COLUMN role ENUM('admin', 'user') DEFAULT 'user'";
    $conn->exec($sql);
    
    echo "✅ Campo role agregado exitosamente\n";
    
    // Establecer el usuario admin como administrador
    echo "Estableciendo usuario admin como administrador...\n";
    $stmt = $conn->prepare('UPDATE users SET role = ? WHERE username = ?');
    $stmt->execute(['admin', 'admin']);
    
    echo "✅ Usuario admin configurado como administrador\n";
    
    echo "\n=== VERIFICAR USUARIOS ===\n";
    $stmt = $conn->query('SELECT username, full_name, role FROM users ORDER BY id');
    while($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        echo "{$row['username']} - {$row['full_name']} - {$row['role']}\n";
    }
    
} catch (Exception $e) {
    if (strpos($e->getMessage(), 'Duplicate column name') !== false) {
        echo "ℹ️ El campo role ya existe\n";
        
        // Solo mostrar los usuarios actuales
        echo "\n=== USUARIOS ACTUALES ===\n";
        $stmt = $conn->query('SELECT username, full_name, role FROM users ORDER BY id');
        while($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            echo "{$row['username']} - {$row['full_name']} - {$row['role']}\n";
        }
    } else {
        echo "❌ Error: " . $e->getMessage() . "\n";
    }
}
?>