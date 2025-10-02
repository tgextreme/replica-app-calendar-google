<?php
// Debug API routing and autoloader

echo "=== API DEBUG TEST ===\n";
echo "Testing from: " . __DIR__ . "\n";

// Simulate the API autoloader
spl_autoload_register(function ($class) {
    $paths = [
        __DIR__ . '/../config/',
        __DIR__ . '/../app/models/',
        __DIR__ . '/../app/controllers/'
    ];
    
    echo "Looking for class: $class\n";
    
    foreach ($paths as $path) {
        $file = $path . $class . '.php';
        echo "  Checking: $file -> ";
        if (file_exists($file)) {
            echo "FOUND!\n";
            require_once $file;
            return;
        } else {
            echo "not found\n";
        }
    }
    
    echo "  Class $class NOT FOUND in any path!\n";
});

echo "\n=== TESTING CALENDAR CONTROLLER ===\n";

try {
    // Try to simulate the exact API call
    $_SERVER['REQUEST_METHOD'] = 'GET';
    $_SERVER['REQUEST_URI'] = '/calendar/api/calendars';
    
    echo "Creating CalendarController...\n";
    $controller = new CalendarController();
    echo "SUCCESS: CalendarController created!\n";
    
    echo "Testing index method...\n";
    // Don't actually call it because it will output headers
    echo "CalendarController has index method: " . (method_exists($controller, 'index') ? 'YES' : 'NO') . "\n";
    
} catch (Exception $e) {
    echo "EXCEPTION: " . $e->getMessage() . "\n";
} catch (Error $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
    echo "File: " . $e->getFile() . " Line: " . $e->getLine() . "\n";
}

echo "\n=== LISTING FILES ===\n";
echo "Controllers directory contents:\n";
$controllersPath = __DIR__ . '/../app/controllers/';
if (is_dir($controllersPath)) {
    $files = scandir($controllersPath);
    foreach ($files as $file) {
        if ($file != '.' && $file != '..') {
            echo "  - $file\n";
        }
    }
} else {
    echo "Controllers directory not found at: $controllersPath\n";
}
?>