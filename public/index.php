<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Configuración de sesión más robusta
ini_set('session.cookie_httponly', 1);
ini_set('session.cookie_secure', 0); // 0 para HTTP, 1 para HTTPS
ini_set('session.cookie_samesite', 'Lax');
ini_set('session.use_strict_mode', 1);

session_start();

// Autoload básico
spl_autoload_register(function ($class) {
    $paths = [
        __DIR__ . '/../src/models/' . $class . '.php',
        __DIR__ . '/../src/controllers/' . $class . '.php',
        __DIR__ . '/../app/controllers/' . $class . '.php',  
        __DIR__ . '/../config/' . $class . '.php'
    ];
    
    foreach ($paths as $path) {
        if (file_exists($path)) {
            require_once $path;
            return;
        }
    }
});

// Cargar configuración
$config = require __DIR__ . '/../config/config.php';

// Obtener la ruta
$request_uri = $_SERVER['REQUEST_URI'];
$path = parse_url($request_uri, PHP_URL_PATH);

// Remover el prefijo /calendar
if (strpos($path, '/calendar/') === 0) {
    $path = substr($path, 10); // Quitar '/calendar/'
}

$path = trim($path, '/');

// Ruteo simple
if (empty($path) || $path == 'index.php') {
    // Mostrar login
    if (file_exists(__DIR__ . '/index.html')) {
        readfile(__DIR__ . '/index.html');
    } else {
        echo "Error: archivo index.html no encontrado";
    }
} elseif ($path == 'login.html') {
    if (file_exists(__DIR__ . '/login.html')) {
        readfile(__DIR__ . '/login.html');
    } else {
        echo "Error: archivo login.html no encontrado";
    }
} elseif ($path == 'calendar.html') {
    if (file_exists(__DIR__ . '/calendar.html')) {
        readfile(__DIR__ . '/calendar.html');
    } else {
        echo "Error: archivo calendar.html no encontrado";  
    }
} elseif ($path == 'register.html') {
    if (file_exists(__DIR__ . '/register.html')) {
        readfile(__DIR__ . '/register.html');
    } else {
        echo "Error: archivo register.html no encontrado";
    }
} elseif (strpos($path, 'api/') === 0) {
    // API Routes
    header('Content-Type: application/json');
    header('Access-Control-Allow-Origin: ' . ($_SERVER['HTTP_ORIGIN'] ?? 'http://localhost'));
    header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
    header('Access-Control-Allow-Headers: Content-Type, Authorization');
    header('Access-Control-Allow-Credentials: true');
    
    if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
        http_response_code(200);
        exit;
    }
    
    $api_path = substr($path, 4); // Quitar 'api/'
    $api_parts = explode('/', $api_path);
    $controller_name = ucfirst($api_parts[0]) . 'Controller';
    
    if (class_exists($controller_name)) {
        $controller = new $controller_name();
        $method = strtolower($_SERVER['REQUEST_METHOD']);
        $action = isset($api_parts[1]) ? $api_parts[1] : 'index';
        $method_name = $method . ucfirst($action);
        
        if (method_exists($controller, $method_name)) {
            try {
                $result = $controller->$method_name();
                echo $result;
            } catch (Exception $e) {
                http_response_code(500);
                echo json_encode(['error' => $e->getMessage()]);
            }
        } else {
            http_response_code(404);
            echo json_encode(['error' => 'Método no encontrado']);
        }
    } else {
        http_response_code(404);
        echo json_encode(['error' => 'Controlador no encontrado']);
    }
} else {
    // Archivos estáticos
    $file_path = __DIR__ . '/' . $path;
    if (file_exists($file_path) && is_file($file_path)) {
        $mime_types = [
            'css' => 'text/css',
            'js' => 'application/javascript',
            'png' => 'image/png',
            'jpg' => 'image/jpeg',
            'gif' => 'image/gif',
            'svg' => 'image/svg+xml'
        ];
        
        $ext = pathinfo($file_path, PATHINFO_EXTENSION);
        if (isset($mime_types[$ext])) {
            header('Content-Type: ' . $mime_types[$ext]);
        }
        
        readfile($file_path);
    } else {
        http_response_code(404);
        echo "Página no encontrada";
    }
}
?>