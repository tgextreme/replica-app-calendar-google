<?php
require_once '../config/database.php';
require_once '../app/models/Group.php';

$db = Database::getInstance();

echo "=== TEST GROUP MODEL ===\n\n";

// Test con Maria (ID: 4)
$userId = 4;
echo "Testing getUserGroups for Maria (ID: $userId):\n";

try {
    $groupModel = new Group();
    $groups = $groupModel->getUserGroups($userId);
    
    echo "SUCCESS: Found " . count($groups) . " groups\n";
    
    foreach($groups as $group) {
        echo "- Group #{$group['id']}: {$group['name']} (Role: {$group['role']})\n";
    }
    
} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
}

echo "\n=== DIRECT DB QUERY ===\n";
try {
    $conn = $db->getConnection();
    $stmt = $conn->prepare("
        SELECT g.*, gm.role, gm.joined_at,
               COUNT(DISTINCT gm2.user_id) as member_count
        FROM user_groups g 
        JOIN group_members gm ON g.id = gm.group_id 
        LEFT JOIN group_members gm2 ON g.id = gm2.group_id
        WHERE gm.user_id = ? AND g.is_active = 1
        GROUP BY g.id, gm.role, gm.joined_at
        ORDER BY g.name
    ");
    
    $stmt->execute([$userId]);
    $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "Direct query found " . count($results) . " results:\n";
    foreach($results as $row) {
        print_r($row);
    }
    
} catch (Exception $e) {
    echo "DB ERROR: " . $e->getMessage() . "\n";
}
?>