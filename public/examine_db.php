<?php
require_once '../config/Database.php';

echo "=== EXAMINING DATABASE STRUCTURE ===\n";

try {
    $db = Database::getInstance();
    
    // Show all tables
    $tables = $db->fetchAll('SHOW TABLES');
    echo "Tables in database:\n";
    foreach($tables as $table) {
        $tableName = $table[array_keys($table)[0]];
        echo "- $tableName\n";
    }
    
    echo "\n=== CALENDARS TABLE ===\n";
    $calendarsStructure = $db->fetchAll('DESCRIBE calendars');
    foreach($calendarsStructure as $column) {
        echo "{$column['Field']}: {$column['Type']} {$column['Null']} {$column['Key']}\n";
    }
    
    echo "\n=== USER_GROUPS TABLE ===\n";
    $groupsStructure = $db->fetchAll('DESCRIBE user_groups');
    foreach($groupsStructure as $column) {
        echo "{$column['Field']}: {$column['Type']} {$column['Null']} {$column['Key']}\n";
    }
    
    echo "\n=== SAMPLE CALENDARS DATA ===\n";
    $calendars = $db->fetchAll('SELECT id, name, description, owner_id FROM calendars LIMIT 5');
    foreach($calendars as $cal) {
        echo "ID: {$cal['id']}, Name: {$cal['name']}, Owner: {$cal['owner_id']}\n";
    }
    
    echo "\n=== SAMPLE GROUPS DATA ===\n";
    $groups = $db->fetchAll('SELECT id, name, description, created_by FROM user_groups LIMIT 5');
    foreach($groups as $group) {
        echo "ID: {$group['id']}, Name: {$group['name']}, Created by: {$group['created_by']}\n";
    }
    
    echo "\n=== EVENTS TABLE STRUCTURE ===\n";
    $eventsStructure = $db->fetchAll('DESCRIBE events');
    foreach($eventsStructure as $column) {
        echo "{$column['Field']}: {$column['Type']} {$column['Null']} {$column['Key']}\n";
    }
    
    echo "\n=== SAMPLE EVENTS ===\n";
    $events = $db->fetchAll('SELECT id, title, calendar_id, start_datetime, end_datetime FROM events LIMIT 3');
    foreach($events as $event) {
        echo "Event ID: {$event['id']}, Title: {$event['title']}, Calendar ID: {$event['calendar_id']}\n";
    }
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?>