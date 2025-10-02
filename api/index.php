<?php
// Autoloader simple para las clases
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

// Configurar timezone
date_default_timezone_set('Europe/Madrid');

// Configurar CORS
$origin = isset($_SERVER['HTTP_ORIGIN']) ? $_SERVER['HTTP_ORIGIN'] : 'http://localhost';
header('Access-Control-Allow-Origin: ' . $origin);
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');
header('Access-Control-Allow-Credentials: true');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// Router simple
$requestUri = $_SERVER['REQUEST_URI'];
$basePath = '/calendar/api';
$path = str_replace($basePath, '', parse_url($requestUri, PHP_URL_PATH));
$method = $_SERVER['REQUEST_METHOD'];

// Rutas de autenticación
if (preg_match('/^\/auth\//', $path)) {
    $controller = new AuthController();
    switch ($path) {
        case '/auth/login':
            $controller->login();
            break;
        case '/auth/register':
            $controller->register();
            break;
        case '/auth/logout':
            $controller->logout();
            break;
        case '/auth/me':
            $controller->me();
            break;
        default:
            http_response_code(404);
            echo json_encode(['success' => false, 'message' => 'Ruta no encontrada']);
    }
}
// Rutas de eventos
elseif (preg_match('/^\/events/', $path)) {
    if ($path === '/events') {
        $controller = new EventController();
        if ($method === 'GET') {
            $controller->index();
        } elseif ($method === 'POST') {
            $controller->create();
        }
    } elseif (preg_match('/^\/events\/(\d+)$/', $path, $matches)) {
        $id = $matches[1];
        $controller = new EventController();
        
        switch ($method) {
            case 'GET':
                $controller->show($id);
                break;
            case 'PUT':
                $controller->update($id);
                break;
            case 'DELETE':
                $controller->delete($id);
                break;
            default:
                http_response_code(405);
                echo json_encode(['success' => false, 'message' => 'Método no permitido']);
        }
    } else {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'Ruta no encontrada']);
    }
}
// Rutas de calendarios
elseif (preg_match('/^\/calendars/', $path)) {
    if ($path === '/calendars') {
        $controller = new CalendarController();
        if ($method === 'GET') {
            $controller->index();
        } elseif ($method === 'POST') {
            $controller->create();
        }
    } elseif (preg_match('/^\/calendars\/(\d+)$/', $path, $matches)) {
        $id = $matches[1];
        $controller = new CalendarController();
        
        switch ($method) {
            case 'GET':
                $controller->show($id);
                break;
            case 'PUT':
                $controller->update($id);
                break;
            case 'DELETE':
                $controller->delete($id);
                break;
            default:
                http_response_code(405);
                echo json_encode(['success' => false, 'message' => 'Método no permitido']);
        }
    } elseif (preg_match('/^\/calendars\/(\d+)\/share\/user$/', $path, $matches)) {
        $id = $matches[1];
        $controller = new CalendarController();
        $controller->shareWithUser($id);
    } elseif (preg_match('/^\/calendars\/(\d+)\/share\/group$/', $path, $matches)) {
        $id = $matches[1];
        $controller = new CalendarController();
        $controller->shareWithGroup($id);
    } else {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'Ruta no encontrada']);
    }
}
// Rutas de grupos
elseif (preg_match('/^\/groups/', $path)) {
    if ($path === '/groups') {
        $controller = new GroupController();
        if ($method === 'GET') {
            $controller->index();
        } elseif ($method === 'POST') {
            $controller->create();
        }
    } elseif ($path === '/groups/search') {
        $controller = new GroupController();
        $controller->search();
    } elseif (preg_match('/^\/groups\/(\d+)$/', $path, $matches)) {
        $id = $matches[1];
        $controller = new GroupController();
        
        switch ($method) {
            case 'GET':
                $controller->show($id);
                break;
            case 'PUT':
                $controller->update($id);
                break;
            case 'DELETE':
                $controller->delete($id);
                break;
            default:
                http_response_code(405);
                echo json_encode(['success' => false, 'message' => 'Método no permitido']);
        }
    } elseif (preg_match('/^\/groups\/(\d+)\/members$/', $path, $matches)) {
        $id = $matches[1];
        $controller = new GroupController();
        if ($method === 'POST') {
            $controller->addMember($id);
        }
    } elseif (preg_match('/^\/groups\/(\d+)\/members\/(\d+)$/', $path, $matches)) {
        $groupId = $matches[1];
        $memberId = $matches[2];
        $controller = new GroupController();
        
        switch ($method) {
            case 'PUT':
                $controller->updateMemberRole($groupId, $memberId);
                break;
            case 'DELETE':
                $controller->removeMember($groupId, $memberId);
                break;
            default:
                http_response_code(405);
                echo json_encode(['success' => false, 'message' => 'Método no permitido']);
        }
    } elseif (preg_match('/^\/groups\/(\d+)\/join$/', $path, $matches)) {
        $id = $matches[1];
        $controller = new GroupController();
        $controller->join($id);
    } elseif (preg_match('/^\/groups\/(\d+)\/leave$/', $path, $matches)) {
        $id = $matches[1];
        $controller = new GroupController();
        $controller->leave($id);
    } else {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'Ruta no encontrada']);
    }
}
else {
    http_response_code(404);
    echo json_encode(['success' => false, 'message' => 'Ruta no encontrada']);
}