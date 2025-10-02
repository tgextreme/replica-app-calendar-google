<?php
require_once '../config/Database.php';

echo "=== ADDING GROUP SUPPORT TO EVENTS TABLE ===\n";

try {
    $db = Database::getInstance();
    
    // Check if group_id column already exists
    $columns = $db->fetchAll("SHOW COLUMNS FROM events LIKE 'group_id'");
    
    if (empty($columns)) {
        echo "Adding group_id column to events table...\n";
        $db->query("ALTER TABLE events ADD COLUMN group_id INT NULL AFTER calendar_id");
        
        echo "Adding container_type column to events table...\n";
        $db->query("ALTER TABLE events ADD COLUMN container_type ENUM('calendar', 'group') DEFAULT 'calendar' AFTER group_id");
        
        echo "Adding index for group_id...\n";
        $db->query("ALTER TABLE events ADD INDEX idx_group_id (group_id)");
        
        echo "Adding foreign key constraint for group_id...\n";
        $db->query("ALTER TABLE events ADD CONSTRAINT fk_events_group_id FOREIGN KEY (group_id) REFERENCES user_groups(id) ON DELETE CASCADE");
        
        echo "Successfully added group support to events table!\n";
    } else {
        echo "Group support already exists in events table.\n";
    }
    
    // Show updated structure
    echo "\n=== UPDATED EVENTS TABLE STRUCTURE ===\n";
    $eventsStructure = $db->fetchAll('DESCRIBE events');
    foreach($eventsStructure as $column) {
        echo "{$column['Field']}: {$column['Type']} {$column['Null']} {$column['Key']}\n";
    }
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?>