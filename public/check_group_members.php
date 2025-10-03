<?php
require_once '../config/database.php';

$db = Database::getInstance();
$conn = $db->getConnection();

echo "=== GRUPOS Y SUS MIEMBROS ===\n\n";

$stmt = $conn->query("
    SELECT 
        g.id, g.name, g.description, g.created_by,
        u_creator.full_name as creator_name,
        gm.user_id, gm.role, gm.joined_at,
        u_member.full_name as member_name
    FROM user_groups g
    LEFT JOIN users u_creator ON g.created_by = u_creator.id
    LEFT JOIN group_members gm ON g.id = gm.group_id
    LEFT JOIN users u_member ON gm.user_id = u_member.id
    WHERE g.is_active = 1
    ORDER BY g.id, gm.role DESC
");

$currentGroupId = null;
while($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    if ($currentGroupId !== $row['id']) {
        if ($currentGroupId !== null) echo "\n";
        echo "GRUPO #{$row['id']}: {$row['name']}\n";
        echo "Descripción: {$row['description']}\n";
        echo "Creador: {$row['creator_name']} (ID: {$row['created_by']})\n";
        echo "Miembros:\n";
        $currentGroupId = $row['id'];
    }
    
    if ($row['user_id']) {
        echo "  - {$row['member_name']} ({$row['role']}) - Unido: {$row['joined_at']}\n";
    }
}
?>