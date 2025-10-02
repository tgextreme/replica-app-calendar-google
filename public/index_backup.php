<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
session_start();

// Autoload básico
spl_autoload_register(function ($class) {
    $paths = [
        __DIR__ . '/../src/models/' . $class . '.php',
        __DIR__ . '/../src/controllers/' . $class . '.php',
        __DIR__ . '/../config/' . $class . '.php'
    ];
    
    foreach ($paths as $path) {
        if (file_exists($path)) {
            require_once $path;
            return;
        }
    }
});

try {
    // Cargar configuración
    $config = require __DIR__ . '/../config/config.php';
    
    // Obtener la ruta solicitada
    $request_uri = $_SERVER['REQUEST_URI'];
    $script_name = $_SERVER['SCRIPT_NAME'];
    $base_path = dirname($script_name);
    
    // Limpiar la ruta
    $path = str_replace($base_path, '', $request_uri);
    $path = parse_url($path, PHP_URL_PATH);
    $path = trim($path, '/');
    
    // Rutas básicas
    switch ($path) {
        case '':
        case 'index.php':
            // Página de login
            if (file_exists(__DIR__ . '/login.html')) {
                include __DIR__ . '/login.html';
            } else {
                echo "<h1>Web Calendar</h1>";
                echo "<p>Sistema de calendario web</p>";
                echo "<p><a href='test_db.php'>🔍 Test de Base de Datos</a></p>";
                echo "<p><a href='test_php.php'>🔍 Test de PHP</a></p>";
                if (file_exists(__DIR__ . '/calendar.html')) {
                    echo "<p><a href='calendar.html'>📅 Ir al Calendario</a></p>";
                }
            }
            break;
            
        case 'login.html':
            if (file_exists(__DIR__ . '/login.html')) {
                include __DIR__ . '/login.html';
            } else {
                header("Location: /");
            }
            break;
            
        case 'calendar.html':
            if (file_exists(__DIR__ . '/calendar.html')) {
                include __DIR__ . '/calendar.html';
            } else {
                echo "Archivo calendar.html no encontrado";
            }
            break;
            
        case 'register.html':
            if (file_exists(__DIR__ . '/register.html')) {
                include __DIR__ . '/register.html';
            } else {
                echo "Archivo register.html no encontrado";
            }
            break;
            
        // API Routes
        case (preg_match('/^api\/(.+)$/', $path, $matches) ? true : false):
            $api_path = $matches[1];
            
            // Configurar headers para API
            header('Content-Type: application/json');
            header('Access-Control-Allow-Origin: *');
            header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
            header('Access-Control-Allow-Headers: Content-Type, Authorization');
            
            if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
                http_response_code(200);
                exit;
            }
            
            // Ruteo de API
            $api_parts = explode('/', $api_path);
            $controller_name = ucfirst($api_parts[0]) . 'Controller';
            
            if (class_exists($controller_name)) {
                $controller = new $controller_name();
                
                // Determinar método
                $method = $_SERVER['REQUEST_METHOD'];
                $action = isset($api_parts[1]) ? $api_parts[1] : 'index';
                
                $method_name = strtolower($method) . ucfirst($action);
                
                if (method_exists($controller, $method_name)) {
                    try {
                        $result = $controller->$method_name();
                        if (is_array($result) || is_object($result)) {
                            echo json_encode($result);
                        } else {
                            echo $result;
                        }
                    } catch (Exception $e) {
                        http_response_code(500);
                        echo json_encode(['error' => $e->getMessage()]);
                    }
                } else {
                    http_response_code(404);
                    echo json_encode(['error' => 'Método no encontrado: ' . $method_name]);
                }
            } else {
                http_response_code(404);
                echo json_encode(['error' => 'Controlador no encontrado: ' . $controller_name]);
            }
            break;
            
        default:
            // Verificar si es un archivo estático
            $file_path = __DIR__ . '/' . $path;
            if (file_exists($file_path) && is_file($file_path)) {
                // Servir archivo estático
                $mime_types = [
                    'css' => 'text/css',
                    'js' => 'application/javascript',
                    'png' => 'image/png',
                    'jpg' => 'image/jpeg',
                    'jpeg' => 'image/jpeg',
                    'gif' => 'image/gif',
                    'svg' => 'image/svg+xml',
                    'ico' => 'image/x-icon'
                ];
                
                $ext = pathinfo($file_path, PATHINFO_EXTENSION);
                if (isset($mime_types[$ext])) {
                    header('Content-Type: ' . $mime_types[$ext]);
                }
                
                readfile($file_path);
            } else {
                // Página no encontrada
                http_response_code(404);
                echo "<h1>404 - Página no encontrada</h1>";
                echo "<p>La ruta solicitada no existe: " . htmlspecialchars($path) . "</p>";
                echo "<p><a href='/calendar/public/'>← Volver al inicio</a></p>";
                echo "<p><a href='/calendar/public/test_db.php'>🔍 Test de Base de Datos</a></p>";
            }
            break;
    }
    
} catch (Exception $e) {
    // Error general
    http_response_code(500);
    echo "<h1>Error del servidor</h1>";
    echo "<p>Ha ocurrido un error: " . htmlspecialchars($e->getMessage()) . "</p>";
    echo "<p><a href='/calendar/public/test_db.php'>🔍 Test de Base de Datos</a></p>";
    echo "<p><a href='/calendar/public/test_php.php'>🔍 Test de PHP</a></p>";
    
    if (isset($config) && $config['app']['debug']) {
        echo "<pre>Stack trace:\n" . $e->getTraceAsString() . "</pre>";
    }
}
?>

// Autoload básico
spl_autoload_register(function ($class) {
    $paths = [
        __DIR__ . '/../src/models/' . $class . '.php',
        __DIR__ . '/../src/controllers/' . $class . '.php',
        __DIR__ . '/../config/' . $class . '.php'
    ];
    
    foreach ($paths as $path) {
        if (file_exists($path)) {
            require_once $path;
            return;
        }
    }
});

try {
    // Cargar configuración
    $config = require __DIR__ . '/../config/config.php';
    
    // Obtener la ruta solicitada
    $request_uri = $_SERVER['REQUEST_URI'];
    $script_name = $_SERVER['SCRIPT_NAME'];
    $base_path = dirname($script_name);
    
    // Limpiar la ruta
    $path = str_replace($base_path, '', $request_uri);
    $path = parse_url($path, PHP_URL_PATH);
    $path = trim($path, '/');
    
    // Rutas básicas
    switch ($path) {
        case '':
        case 'index.php':
            // Página de login
            if (file_exists(__DIR__ . '/login.html')) {
                include __DIR__ . '/login.html';
            } else {
                echo "<h1>Web Calendar</h1>";
                echo "<p>Sistema de calendario web</p>";
                echo "<p><a href='test_db.php'>🔍 Test de Base de Datos</a></p>";
                echo "<p><a href='test_php.php'>🔍 Test de PHP</a></p>";
                if (file_exists(__DIR__ . '/calendar.html')) {
                    echo "<p><a href='calendar.html'>📅 Ir al Calendario</a></p>";
                }
            }
            break;
            
        case 'login.html':
            if (file_exists(__DIR__ . '/login.html')) {
                include __DIR__ . '/login.html';
            } else {
                header("Location: /");
            }
            break;
            
        case 'calendar.html':
            if (file_exists(__DIR__ . '/calendar.html')) {
                include __DIR__ . '/calendar.html';
            } else {
                echo "Archivo calendar.html no encontrado";
            }
            break;
            
        case 'register.html':
            if (file_exists(__DIR__ . '/register.html')) {
                include __DIR__ . '/register.html';
            } else {
                echo "Archivo register.html no encontrado";
            }
            break;
            
        // API Routes
        case (preg_match('/^api\/(.+)$/', $path, $matches) ? true : false):
            $api_path = $matches[1];
            
            // Configurar headers para API
            header('Content-Type: application/json');
            header('Access-Control-Allow-Origin: *');
            header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
            header('Access-Control-Allow-Headers: Content-Type, Authorization');
            
            if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
                http_response_code(200);
                exit;
            }
            
            // Ruteo de API
            $api_parts = explode('/', $api_path);
            $controller_name = ucfirst($api_parts[0]) . 'Controller';
            
            if (class_exists($controller_name)) {
                $controller = new $controller_name();
                
                // Determinar método
                $method = $_SERVER['REQUEST_METHOD'];
                $action = isset($api_parts[1]) ? $api_parts[1] : 'index';
                
                $method_name = strtolower($method) . ucfirst($action);
                
                if (method_exists($controller, $method_name)) {
                    try {
                        $result = $controller->$method_name();
                        if (is_array($result) || is_object($result)) {
                            echo json_encode($result);
                        } else {
                            echo $result;
                        }
                    } catch (Exception $e) {
                        http_response_code(500);
                        echo json_encode(['error' => $e->getMessage()]);
                    }
                } else {
                    http_response_code(404);
                    echo json_encode(['error' => 'Método no encontrado: ' . $method_name]);
                }
            } else {
                http_response_code(404);
                echo json_encode(['error' => 'Controlador no encontrado: ' . $controller_name]);
            }
            break;
            
        default:
            // Verificar si es un archivo estático
            $file_path = __DIR__ . '/' . $path;
            if (file_exists($file_path) && is_file($file_path)) {
                // Servir archivo estático
                $mime_types = [
                    'css' => 'text/css',
                    'js' => 'application/javascript',
                    'png' => 'image/png',
                    'jpg' => 'image/jpeg',
                    'jpeg' => 'image/jpeg',
                    'gif' => 'image/gif',
                    'svg' => 'image/svg+xml',
                    'ico' => 'image/x-icon'
                ];
                
                $ext = pathinfo($file_path, PATHINFO_EXTENSION);
                if (isset($mime_types[$ext])) {
                    header('Content-Type: ' . $mime_types[$ext]);
                }
                
                readfile($file_path);
            } else {
                // Página no encontrada
                http_response_code(404);
                echo "<h1>404 - Página no encontrada</h1>";
                echo "<p>La ruta solicitada no existe: " . htmlspecialchars($path) . "</p>";
                echo "<p><a href='/calendar/public/'>← Volver al inicio</a></p>";
                echo "<p><a href='/calendar/public/test_db.php'>🔍 Test de Base de Datos</a></p>";
            }
            break;
    }
    
} catch (Exception $e) {
    // Error general
    http_response_code(500);
    echo "<h1>Error del servidor</h1>";
    echo "<p>Ha ocurrido un error: " . htmlspecialchars($e->getMessage()) . "</p>";
    echo "<p><a href='/calendar/public/test_db.php'>🔍 Test de Base de Datos</a></p>";
    echo "<p><a href='/calendar/public/test_php.php'>🔍 Test de PHP</a></p>";
    
    if ($config['app']['debug']) {
        echo "<pre>Stack trace:\n" . $e->getTraceAsString() . "</pre>";
    }
}
?>