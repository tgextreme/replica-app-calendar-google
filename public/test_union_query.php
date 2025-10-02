<?php
require_once '../config/Database.php';

echo "=== TESTING UNION QUERY ===\n";

try {
    $db = Database::getInstance();
    $userId = 1; // admin user
    
    // Union query to combine calendars and groups as selectable options
    $unionQuery = "
        (SELECT 
            CONCAT('calendar_', id) as unique_id,
            id as original_id,
            name,
            description,
            color,
            owner_id as creator_id,
            'calendar' as type,
            CASE 
                WHEN owner_id = ? THEN 'owner'
                ELSE 'shared'
            END as permission_level
        FROM calendars 
        WHERE is_active = 1 AND (owner_id = ? OR is_public = 1))
        
        UNION ALL
        
        (SELECT 
            CONCAT('group_', ug.id) as unique_id,
            ug.id as original_id,
            ug.name,
            ug.description,
            ug.color,
            ug.created_by as creator_id,
            'group' as type,
            CASE 
                WHEN ug.created_by = ? THEN 'owner'
                WHEN gm.user_id IS NOT NULL THEN 'member'
                ELSE 'none'
            END as permission_level
        FROM user_groups ug
        LEFT JOIN group_members gm ON ug.id = gm.group_id AND gm.user_id = ?
        WHERE ug.is_active = 1 AND (ug.created_by = ? OR gm.user_id = ?))
        
        ORDER BY type, name
    ";
    
    $results = $db->fetchAll($unionQuery, [$userId, $userId, $userId, $userId, $userId, $userId]);
    
    echo "Combined calendars and groups for user $userId:\n";
    foreach($results as $item) {
        echo "- {$item['type']}: {$item['name']} (ID: {$item['unique_id']}, Permission: {$item['permission_level']})\n";
    }
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?>