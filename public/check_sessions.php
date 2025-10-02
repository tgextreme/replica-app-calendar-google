<?php
require_once '../config/Database.php';

$db = Database::getInstance();
echo "=== USER_SESSIONS TABLE STRUCTURE ===\n";

$cols = $db->fetchAll('DESCRIBE user_sessions');
foreach($cols as $col) {
    echo $col['Field'] . ': ' . $col['Type'] . "\n";
}

echo "\n=== SAMPLE USER_SESSIONS DATA ===\n";
$sessions = $db->fetchAll('SELECT * FROM user_sessions LIMIT 3');
foreach($sessions as $session) {
    print_r($session);
    echo "---\n";
}
?>