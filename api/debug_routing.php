<?php
// Debug API routing

echo "=== API ROUTING DEBUG ===\n";

// Simulate the request
$_SERVER['REQUEST_URI'] = '/calendar/api/calendars';
$_SERVER['REQUEST_METHOD'] = 'GET';

$requestUri = $_SERVER['REQUEST_URI'];
$basePath = '/calendar/api';
$path = str_replace($basePath, '', parse_url($requestUri, PHP_URL_PATH));
$method = $_SERVER['REQUEST_METHOD'];

echo "Request URI: $requestUri\n";
echo "Base path: $basePath\n";  
echo "Parsed path: '$path'\n";
echo "Method: $method\n";

echo "\n=== TESTING ROUTE MATCHING ===\n";

// Test the route matching logic
if (preg_match('/^\/calendars/', $path)) {
    echo "✓ Route /calendars MATCHED\n";
    
    if ($path === '/calendars') {
        echo "✓ Exact path /calendars matched\n";
        echo "✓ Method is GET, should call controller->index()\n";
    } else {
        echo "✗ Path '$path' is not exactly '/calendars'\n";
    }
    
} else {
    echo "✗ Route /calendars NOT MATCHED\n";
    echo "Testing other routes...\n";
    
    if (preg_match('/^\/auth\//', $path)) {
        echo "- Auth route matched\n";
    } elseif (preg_match('/^\/events/', $path)) {
        echo "- Events route matched\n";  
    } elseif (preg_match('/^\/groups/', $path)) {
        echo "- Groups route matched\n";
    } else {
        echo "- NO route matched - this is why we get 'Controlador no encontrado'\n";
    }
}

echo "\n=== URL PARSING DETAILS ===\n";
$parsedUrl = parse_url($requestUri);
echo "Full parse_url result:\n";
print_r($parsedUrl);

echo "\nTesting different path constructions:\n";
echo "1. str_replace result: '" . str_replace($basePath, '', $requestUri) . "'\n";
echo "2. Using URL path only: '" . str_replace($basePath, '', $parsedUrl['path']) . "'\n";
?>