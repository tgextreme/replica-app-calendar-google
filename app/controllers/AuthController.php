<?php

class AuthController {
    private $userModel;
    private $config;

    public function __construct() {
        $this->userModel = new User();
        $this->config = require __DIR__ . '/../../config/config.php';
        
        if (session_status() == PHP_SESSION_NONE) {
            session_name($this->config['security']['session_name']);
            session_start();
        }
    }

    public function login() {
        header('Content-Type: application/json');
        
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            echo json_encode(['success' => false, 'message' => 'Método no permitido']);
            return;
        }

        $input = json_decode(file_get_contents('php://input'), true);
        
        if (!$input || !isset($input['email']) || !isset($input['password'])) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Email y contraseña son requeridos']);
            return;
        }

        $user = $this->userModel->authenticate($input['email'], $input['password']);
        
        if ($user) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['user_email'] = $user['email'];
            $_SESSION['user_name'] = $user['full_name'];
            $_SESSION['user_role'] = $user['role'];
            $_SESSION['login_time'] = time();
            
            $this->userModel->updateLastActivity($user['id']);
            
            echo json_encode([
                'success' => true, 
                'message' => 'Login exitoso',
                'user' => [
                    'id' => $user['id'],
                    'username' => $user['username'],
                    'email' => $user['email'],
                    'full_name' => $user['full_name'],
                    'timezone' => $user['timezone'],
                    'role' => $user['role']
                ]
            ]);
        } else {
            http_response_code(401);
            echo json_encode(['success' => false, 'message' => 'Credenciales inválidas']);
        }
    }

    public function register() {
        header('Content-Type: application/json');
        
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            echo json_encode(['success' => false, 'message' => 'Método no permitido']);
            return;
        }

        $input = json_decode(file_get_contents('php://input'), true);
        
        $requiredFields = ['username', 'email', 'password', 'full_name'];
        foreach ($requiredFields as $field) {
            if (!isset($input[$field]) || empty($input[$field])) {
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => "El campo $field es requerido"]);
                return;
            }
        }

        // Verificar si el usuario ya existe
        if ($this->userModel->findByEmail($input['email'])) {
            http_response_code(409);
            echo json_encode(['success' => false, 'message' => 'El email ya está registrado']);
            return;
        }

        if ($this->userModel->findByUsername($input['username'])) {
            http_response_code(409);
            echo json_encode(['success' => false, 'message' => 'El nombre de usuario ya está en uso']);
            return;
        }

        try {
            $userId = $this->userModel->create(
                $input['username'],
                $input['email'],
                $input['password'],
                $input['full_name']
            );

            $user = $this->userModel->findById($userId);
            
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['user_email'] = $user['email'];
            $_SESSION['user_name'] = $user['full_name'];
            $_SESSION['login_time'] = time();

            echo json_encode([
                'success' => true, 
                'message' => 'Usuario registrado exitosamente',
                'user' => [
                    'id' => $user['id'],
                    'username' => $user['username'],
                    'email' => $user['email'],
                    'full_name' => $user['full_name']
                ]
            ]);
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => 'Error al registrar usuario']);
        }
    }

    public function logout() {
        header('Content-Type: application/json');
        
        session_destroy();
        echo json_encode(['success' => true, 'message' => 'Logout exitoso']);
    }

    public function me() {
        header('Content-Type: application/json');
        
        if (!$this->isAuthenticated()) {
            http_response_code(401);
            echo json_encode(['success' => false, 'message' => 'No autenticado']);
            return;
        }

        $user = $this->userModel->findById($_SESSION['user_id']);
        
        if ($user) {
            echo json_encode([
                'success' => true,
                'user' => [
                    'id' => $user['id'],
                    'username' => $user['username'],
                    'email' => $user['email'],
                    'full_name' => $user['full_name'],
                    'timezone' => $user['timezone'],
                    'role' => $user['role']
                ]
            ]);
        } else {
            http_response_code(404);
            echo json_encode(['success' => false, 'message' => 'Usuario no encontrado']);
        }
    }

    public function isAuthenticated() {
        return isset($_SESSION['user_id']) && 
               isset($_SESSION['login_time']) && 
               (time() - $_SESSION['login_time']) < $this->config['app']['session_lifetime'];
    }

    public function requireAuth() {
        if (!$this->isAuthenticated()) {
            http_response_code(401);
            echo json_encode(['success' => false, 'message' => 'Autenticación requerida']);
            exit;
        }
    }

    public function getCurrentUserId() {
        return $_SESSION['user_id'] ?? null;
    }

    public function getCurrentUserRole() {
        return $_SESSION['user_role'] ?? 'user';
    }

    public function isAdmin() {
        return $this->getCurrentUserRole() === 'admin';
    }

    public function requireAdmin() {
        $this->requireAuth();
        if (!$this->isAdmin()) {
            http_response_code(403);
            echo json_encode(['success' => false, 'message' => 'Permisos de administrador requeridos']);
            exit;
        }
    }
}