<?php
// Real API test with debugging

// Autoloader
spl_autoload_register(function ($class) {
    $paths = [
        __DIR__ . '/../config/',
        __DIR__ . '/../app/models/',
        __DIR__ . '/../app/controllers/'
    ];
    
    foreach ($paths as $path) {
        $file = $path . $class . '.php';
        if (file_exists($file)) {
            require_once $file;
            return;
        }
    }
});

// Set timezone
date_default_timezone_set('Europe/Madrid');

// Headers - but let's comment out for debugging
echo "=== CALENDAR API DEBUG TEST ===\n";

// Router logic (copied from api/index.php)
$requestUri = '/calendar/api/calendars';
$basePath = '/calendar/api';
$path = str_replace($basePath, '', parse_url($requestUri, PHP_URL_PATH));
$method = 'GET';

echo "Parsed path: '$path'\n";
echo "Method: $method\n";

echo "\n=== EXECUTING ROUTING LOGIC ===\n";

// Copy exact routing logic from api/index.php
if (preg_match('/^\/auth\//', $path)) {
    echo "Auth route matched\n";
}
elseif (preg_match('/^\/events/', $path)) {
    echo "Events route matched\n";
}
elseif (preg_match('/^\/calendars/', $path)) {
    echo "✓ Calendars route matched!\n";
    
    if ($path === '/calendars') {
        echo "✓ Exact path match for /calendars\n";
        
        if ($method === 'GET') {
            echo "✓ GET method - calling controller->index()\n";
            
            try {
                $controller = new CalendarController();
                echo "✓ CalendarController created\n";
                echo "About to call index() method...\n";
                
                // Don't actually call it because of headers, but simulate
                echo "✓ Would call: \$controller->index()\n";
                echo "SUCCESS: All routing logic works!\n";
                
            } catch (Exception $e) {
                echo "✗ Exception creating controller: " . $e->getMessage() . "\n";
            }
        } else {
            echo "✗ Method is not GET\n";
        }
    } else {
        echo "✗ Path is not exactly '/calendars'\n";
    }
}
elseif (preg_match('/^\/groups/', $path)) {
    echo "Groups route matched\n";
}
else {
    echo "✗ NO ROUTE MATCHED - This would return 'Controlador no encontrado'\n";
}
?>