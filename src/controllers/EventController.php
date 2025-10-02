<?php

class EventController {
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
            // Obtener eventos del usuario y calendarios compartidos
            $events = $this->db->fetchAll(
                "SELECT e.*, c.name as calendar_name, c.color as calendar_color 
                 FROM events e
                 JOIN calendars c ON e.calendar_id = c.id
                 WHERE c.owner_id = ? 
                    OR c.id IN (
                        SELECT cp.calendar_id FROM calendar_permissions cp 
                        WHERE cp.user_id = ? AND cp.permission_level IN ('read', 'write', 'admin')
                    )
                 ORDER BY e.start_datetime ASC",
                [$user_id, $user_id]
            );
            
            // Formatear eventos para FullCalendar
            $formatted_events = array_map(function($event) {
                return [
                    'id' => $event['id'],
                    'title' => $event['title'],
                    'start' => $event['start_datetime'],
                    'end' => $event['end_datetime'],
                    'allDay' => (bool)$event['is_all_day'],
                    'backgroundColor' => $event['calendar_color'] ?? '#007bff',
                    'borderColor' => $event['calendar_color'] ?? '#007bff',
                    'extendedProps' => [
                        'description' => $event['description'],
                        'location' => $event['location'],
                        'calendar_id' => $event['calendar_id'],
                        'calendar_name' => $event['calendar_name'],
                        'status' => $event['status'],
                        'priority' => $event['priority'],
                        'visibility' => $event['visibility']
                    ]
                ];
            }, $events);
            
            return json_encode(['success' => true, 'events' => $formatted_events]);
            
        } catch (Exception $e) {
            http_response_code(500);
            return json_encode(['error' => 'Error al obtener eventos: ' . $e->getMessage()]);
        }
    }
    
    public function postIndex() {
        $user_id = $this->checkAuth();
        
        try {
            $input = json_decode(file_get_contents('php://input'), true);
            
            if (!isset($input['title']) || !isset($input['start'])) {
                http_response_code(400);
                return json_encode(['error' => 'Título y fecha de inicio requeridos']);
            }
            
            // Obtener calendario por defecto del usuario
            $calendar = $this->db->fetch(
                "SELECT id FROM calendars WHERE owner_id = ? AND is_default = 1 LIMIT 1",
                [$user_id]
            );
            
            if (!$calendar) {
                // Crear calendario por defecto si no existe
                $this->db->query(
                    "INSERT INTO calendars (name, owner_id, is_default, color, timezone) 
                     VALUES ('Mi Calendario', ?, 1, '#007bff', 'Europe/Madrid')",
                    [$user_id]
                );
                $calendar_id = $this->db->lastInsertId();
            } else {
                $calendar_id = $calendar['id'];
            }
            
            $title = $input['title'];
            $start = $input['start'];
            $end = $input['end'] ?? $start;
            $description = $input['description'] ?? '';
            $location = $input['location'] ?? '';
            $all_day = isset($input['allDay']) ? (bool)$input['allDay'] : false;
            
            // Crear evento
            $this->db->query(
                "INSERT INTO events (calendar_id, title, description, start_datetime, end_datetime, 
                 is_all_day, location, created_by, status, priority, visibility) 
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'confirmed', 'normal', 'public')",
                [$calendar_id, $title, $description, $start, $end, $all_day, $location, $user_id]
            );
            
            $event_id = $this->db->lastInsertId();
            
            // Obtener el evento creado
            $event = $this->db->fetch(
                "SELECT e.*, c.color as calendar_color FROM events e 
                 JOIN calendars c ON e.calendar_id = c.id 
                 WHERE e.id = ?",
                [$event_id]
            );
            
            return json_encode([
                'success' => true,
                'event' => [
                    'id' => $event['id'],
                    'title' => $event['title'],
                    'start' => $event['start_datetime'],
                    'end' => $event['end_datetime'],
                    'allDay' => (bool)$event['is_all_day'],
                    'backgroundColor' => $event['calendar_color'],
                    'borderColor' => $event['calendar_color']
                ]
            ]);
            
        } catch (Exception $e) {
            http_response_code(500);
            return json_encode(['error' => 'Error al crear evento: ' . $e->getMessage()]);
        }
    }
    
    public function putIndex() {
        $user_id = $this->checkAuth();
        
        try {
            $input = json_decode(file_get_contents('php://input'), true);
            
            if (!isset($input['id'])) {
                http_response_code(400);
                return json_encode(['error' => 'ID del evento requerido']);
            }
            
            $event_id = $input['id'];
            
            // Verificar permisos
            $event = $this->db->fetch(
                "SELECT e.*, c.owner_id FROM events e 
                 JOIN calendars c ON e.calendar_id = c.id 
                 WHERE e.id = ?",
                [$event_id]
            );
            
            if (!$event || ($event['owner_id'] != $user_id && $event['created_by'] != $user_id)) {
                http_response_code(403);
                return json_encode(['error' => 'Sin permisos para editar este evento']);
            }
            
            // Actualizar campos proporcionados
            $updates = [];
            $params = [];
            
            if (isset($input['title'])) {
                $updates[] = 'title = ?';
                $params[] = $input['title'];
            }
            
            if (isset($input['start'])) {
                $updates[] = 'start_datetime = ?';
                $params[] = $input['start'];
            }
            
            if (isset($input['end'])) {
                $updates[] = 'end_datetime = ?';
                $params[] = $input['end'];
            }
            
            if (isset($input['description'])) {
                $updates[] = 'description = ?';
                $params[] = $input['description'];
            }
            
            if (isset($input['location'])) {
                $updates[] = 'location = ?';
                $params[] = $input['location'];
            }
            
            if (isset($input['allDay'])) {
                $updates[] = 'is_all_day = ?';
                $params[] = (bool)$input['allDay'];
            }
            
            if (!empty($updates)) {
                $updates[] = 'updated_by = ?';
                $params[] = $user_id;
                $params[] = $event_id;
                
                $this->db->query(
                    "UPDATE events SET " . implode(', ', $updates) . " WHERE id = ?",
                    $params
                );
            }
            
            return json_encode(['success' => true, 'message' => 'Evento actualizado']);
            
        } catch (Exception $e) {
            http_response_code(500);
            return json_encode(['error' => 'Error al actualizar evento: ' . $e->getMessage()]);
        }
    }
    
    public function deleteIndex() {
        $user_id = $this->checkAuth();
        
        try {
            $input = json_decode(file_get_contents('php://input'), true);
            
            if (!isset($input['id'])) {
                http_response_code(400);
                return json_encode(['error' => 'ID del evento requerido']);
            }
            
            $event_id = $input['id'];
            
            // Verificar permisos
            $event = $this->db->fetch(
                "SELECT e.*, c.owner_id FROM events e 
                 JOIN calendars c ON e.calendar_id = c.id 
                 WHERE e.id = ?",
                [$event_id]
            );
            
            if (!$event || ($event['owner_id'] != $user_id && $event['created_by'] != $user_id)) {
                http_response_code(403);
                return json_encode(['error' => 'Sin permisos para eliminar este evento']);
            }
            
            // Eliminar evento
            $this->db->query("DELETE FROM events WHERE id = ?", [$event_id]);
            
            return json_encode(['success' => true, 'message' => 'Evento eliminado']);
            
        } catch (Exception $e) {
            http_response_code(500);
            return json_encode(['error' => 'Error al eliminar evento: ' . $e->getMessage()]);
        }
    }
}