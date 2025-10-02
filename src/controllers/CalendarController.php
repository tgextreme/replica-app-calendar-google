<?php

class CalendarController {
    private $db;
    
    public function __construct() {
        $this->db = Database::getInstance();
    }
    
    private function checkAuth() {
        if (!isset($_SESSION['user_id'])) {
            http_response_code(401);
            echo json_encode(['error' => 'No autenticado']);
            exit;
        }
        return $_SESSION['user_id'];
    }
    
    public function getIndex() {
        $user_id = $this->checkAuth();
        
        try {
            // Obtener calendarios propios y compartidos
            $calendars = $this->db->fetchAll(
                "SELECT c.*, 
                        CASE 
                            WHEN c.owner_id = ? THEN 'owner'
                            ELSE COALESCE(cp.permission_level, 'none')
                        END as permission_level,
                        (SELECT COUNT(*) FROM events e WHERE e.calendar_id = c.id) as event_count
                 FROM calendars c
                 LEFT JOIN calendar_permissions cp ON c.id = cp.calendar_id AND cp.user_id = ?
                 WHERE c.owner_id = ? 
                    OR (cp.user_id = ? AND cp.permission_level IN ('read', 'write', 'admin'))
                    OR c.is_public = 1
                 ORDER BY c.is_default DESC, c.name ASC",
                [$user_id, $user_id, $user_id, $user_id]
            );
            
            return json_encode(['success' => true, 'calendars' => $calendars]);
            
        } catch (Exception $e) {
            http_response_code(500);
            return json_encode(['error' => 'Error al obtener calendarios: ' . $e->getMessage()]);
        }
    }
    
    public function postIndex() {
        $user_id = $this->checkAuth();
        
        try {
            $input = json_decode(file_get_contents('php://input'), true);
            
            if (!isset($input['name'])) {
                http_response_code(400);
                return json_encode(['error' => 'Nombre del calendario requerido']);
            }
            
            $name = trim($input['name']);
            $description = $input['description'] ?? '';
            $color = $input['color'] ?? '#007bff';
            $is_public = isset($input['is_public']) ? (bool)$input['is_public'] : false;
            
            // Crear calendario
            $this->db->query(
                "INSERT INTO calendars (name, description, color, owner_id, is_public, timezone) 
                 VALUES (?, ?, ?, ?, ?, 'Europe/Madrid')",
                [$name, $description, $color, $user_id, $is_public]
            );
            
            $calendar_id = $this->db->lastInsertId();
            
            // Obtener el calendario creado
            $calendar = $this->db->fetch(
                "SELECT * FROM calendars WHERE id = ?",
                [$calendar_id]
            );
            
            return json_encode(['success' => true, 'calendar' => $calendar]);
            
        } catch (Exception $e) {
            http_response_code(500);
            return json_encode(['error' => 'Error al crear calendario: ' . $e->getMessage()]);
        }
    }
    
    public function putIndex() {
        $user_id = $this->checkAuth();
        
        try {
            $input = json_decode(file_get_contents('php://input'), true);
            
            if (!isset($input['id'])) {
                http_response_code(400);
                return json_encode(['error' => 'ID del calendario requerido']);
            }
            
            $calendar_id = $input['id'];
            
            // Verificar permisos
            $calendar = $this->db->fetch(
                "SELECT * FROM calendars WHERE id = ? AND owner_id = ?",
                [$calendar_id, $user_id]
            );
            
            if (!$calendar) {
                http_response_code(403);
                return json_encode(['error' => 'Sin permisos para editar este calendario']);
            }
            
            // Actualizar campos proporcionados
            $updates = [];
            $params = [];
            
            if (isset($input['name'])) {
                $updates[] = 'name = ?';
                $params[] = trim($input['name']);
            }
            
            if (isset($input['description'])) {
                $updates[] = 'description = ?';
                $params[] = $input['description'];
            }
            
            if (isset($input['color'])) {
                $updates[] = 'color = ?';
                $params[] = $input['color'];
            }
            
            if (isset($input['is_public'])) {
                $updates[] = 'is_public = ?';
                $params[] = (bool)$input['is_public'];
            }
            
            if (!empty($updates)) {
                $params[] = $calendar_id;
                
                $this->db->query(
                    "UPDATE calendars SET " . implode(', ', $updates) . " WHERE id = ?",
                    $params
                );
            }
            
            return json_encode(['success' => true, 'message' => 'Calendario actualizado']);
            
        } catch (Exception $e) {
            http_response_code(500);
            return json_encode(['error' => 'Error al actualizar calendario: ' . $e->getMessage()]);
        }
    }
    
    public function deleteIndex() {
        $user_id = $this->checkAuth();
        
        try {
            $input = json_decode(file_get_contents('php://input'), true);
            
            if (!isset($input['id'])) {
                http_response_code(400);
                return json_encode(['error' => 'ID del calendario requerido']);
            }
            
            $calendar_id = $input['id'];
            
            // Verificar permisos y que no sea el calendario por defecto
            $calendar = $this->db->fetch(
                "SELECT * FROM calendars WHERE id = ? AND owner_id = ?",
                [$calendar_id, $user_id]
            );
            
            if (!$calendar) {
                http_response_code(403);
                return json_encode(['error' => 'Sin permisos para eliminar este calendario']);
            }
            
            if ($calendar['is_default']) {
                http_response_code(400);
                return json_encode(['error' => 'No se puede eliminar el calendario por defecto']);
            }
            
            // Eliminar calendario (los eventos se eliminarán en cascada)
            $this->db->query("DELETE FROM calendars WHERE id = ?", [$calendar_id]);
            
            return json_encode(['success' => true, 'message' => 'Calendario eliminado']);
            
        } catch (Exception $e) {
            http_response_code(500);
            return json_encode(['error' => 'Error al eliminar calendario: ' . $e->getMessage()]);
        }
    }
}