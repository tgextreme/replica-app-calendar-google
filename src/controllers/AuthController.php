<?php

class AuthController {
    private $db;
    
    public function __construct() {
        $this->db = Database::getInstance();
    }
    
    public function postLogin() {
        try {
            $input = json_decode(file_get_contents('php://input'), true);
            
            if (!isset($input['email']) || !isset($input['password'])) {
                http_response_code(400);
                return json_encode(['error' => 'Email y contraseña requeridos']);
            }
            
            $email = $input['email'];
            $password = $input['password'];
            
            // Buscar usuario
            $user = $this->db->fetch(
                "SELECT * FROM users WHERE email = ? AND is_active = 1",
                [$email]
            );
            
            // Login successful - debug removed
            
            if (!$user || md5($password) !== $user['password_hash']) {
                http_response_code(401);
                return json_encode(['error' => 'Credenciales incorrectas']);
            }
            
            // Crear sesión
            session_regenerate_id(true);
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['email'] = $user['email'];
            $_SESSION['full_name'] = $user['full_name'];
            
            // Registrar sesión en la base de datos
            $session_id = session_id();
            $this->db->query(
                "INSERT INTO user_sessions (id, user_id, ip_address, user_agent, expires_at) 
                 VALUES (?, ?, ?, ?, DATE_ADD(NOW(), INTERVAL 1 HOUR))
                 ON DUPLICATE KEY UPDATE 
                 last_activity = CURRENT_TIMESTAMP, 
                 expires_at = DATE_ADD(NOW(), INTERVAL 1 HOUR)",
                [$session_id, $user['id'], $_SERVER['REMOTE_ADDR'] ?? '', $_SERVER['HTTP_USER_AGENT'] ?? '']
            );
            
            return json_encode([
                'success' => true,
                'user' => [
                    'id' => $user['id'],
                    'username' => $user['username'],
                    'email' => $user['email'],
                    'full_name' => $user['full_name']
                ],
                'redirect' => 'calendar.html'
            ]);
            
        } catch (Exception $e) {
            http_response_code(500);
            return json_encode(['error' => 'Error interno del servidor: ' . $e->getMessage()]);
        }
    }
    
    public function postRegister() {
        try {
            $input = json_decode(file_get_contents('php://input'), true);
            
            if (!isset($input['username']) || !isset($input['email']) || 
                !isset($input['password']) || !isset($input['full_name'])) {
                http_response_code(400);
                return json_encode(['error' => 'Todos los campos son requeridos']);
            }
            
            $username = trim($input['username']);
            $email = trim($input['email']);
            $password = $input['password'];
            $full_name = trim($input['full_name']);
            
            // Validaciones
            if (strlen($password) < 6) {
                http_response_code(400);
                return json_encode(['error' => 'La contraseña debe tener al menos 6 caracteres']);
            }
            
            if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                http_response_code(400);
                return json_encode(['error' => 'Email inválido']);
            }
            
            // Verificar si ya existe
            $existing = $this->db->fetch(
                "SELECT id FROM users WHERE email = ? OR username = ?",
                [$email, $username]
            );
            
            if ($existing) {
                http_response_code(409);
                return json_encode(['error' => 'El usuario o email ya existe']);
            }
            
            // Crear usuario
            $password_hash = md5($password);
            
            $this->db->query(
                "INSERT INTO users (username, email, password_hash, full_name, timezone) 
                 VALUES (?, ?, ?, ?, 'Europe/Madrid')",
                [$username, $email, $password_hash, $full_name]
            );
            
            $user_id = $this->db->lastInsertId();
            
            // Crear calendario por defecto
            $this->db->query(
                "INSERT INTO calendars (name, description, color, owner_id, is_default, timezone) 
                 VALUES (?, 'Mi calendario personal', '#007bff', ?, TRUE, 'Europe/Madrid')",
                [$full_name . ' - Personal', $user_id]
            );
            
            return json_encode([
                'success' => true,
                'message' => 'Usuario registrado correctamente',
                'redirect' => '/'
            ]);
            
        } catch (Exception $e) {
            http_response_code(500);
            return json_encode(['error' => 'Error interno del servidor: ' . $e->getMessage()]);
        }
    }
    
    public function postLogout() {
        $session_id = session_id();
        
        // Marcar sesión como inactiva en BD
        if (!empty($session_id)) {
            $this->db->query(
                "UPDATE user_sessions SET is_active = FALSE WHERE id = ?",
                [$session_id]
            );
        }
        
        // Destruir sesión
        session_destroy();
        
        return json_encode(['success' => true, 'redirect' => '/']);
    }
    
    public function getMe() {
        if (!isset($_SESSION['user_id'])) {
            http_response_code(401);
            return json_encode(['error' => 'No autenticado']);
        }
        
        $user = $this->db->fetch(
            "SELECT id, username, email, full_name, avatar_url, timezone FROM users WHERE id = ?",
            [$_SESSION['user_id']]
        );
        
        if (!$user) {
            http_response_code(404);
            return json_encode(['error' => 'Usuario no encontrado']);
        }
        
        return json_encode(['success' => true, 'user' => $user]);
    }
}