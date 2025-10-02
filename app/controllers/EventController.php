<?php

class EventController {
    private $eventModel;
    private $authController;

    public function __construct() {
        $this->eventModel = new Event();
        $this->authController = new AuthController();
    }

    public function index() {
        header('Content-Type: application/json');
        $this->authController->requireAuth();

        $userId = $this->authController->getCurrentUserId();
        
        // Obtener parámetros de fecha
        $start = $_GET['start'] ?? date('Y-m-01'); // Primer día del mes actual
        $end = $_GET['end'] ?? date('Y-m-t');     // Último día del mes actual
        
        // Obtener calendarios específicos si se proporcionan
        $calendarIds = isset($_GET['calendar_ids']) ? explode(',', $_GET['calendar_ids']) : null;

        try {
            $events = $this->eventModel->getEventsByDateRange($userId, $start, $end, $calendarIds);
            
            // Formatear eventos para FullCalendar
            $formattedEvents = [];
            foreach ($events as $event) {
                $formattedEvents[] = [
                    'id' => $event['id'],
                    'title' => $event['title'],
                    'start' => $event['start_datetime'],
                    'end' => $event['end_datetime'],
                    'allDay' => (bool)$event['is_all_day'],
                    'backgroundColor' => $event['calendar_color'],
                    'borderColor' => $event['calendar_color'],
                    'description' => $event['description'],
                    'location' => $event['location'],
                    'calendar' => [
                        'id' => $event['calendar_id'],
                        'name' => $event['calendar_name']
                    ],
                    'creator' => $event['created_by_name'],
                    'status' => $event['status'],
                    'priority' => $event['priority']
                ];
            }

            echo json_encode(['success' => true, 'events' => $formattedEvents]);
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => 'Error al obtener eventos']);
        }
    }

    public function create() {
        header('Content-Type: application/json');
        $this->authController->requireAuth();

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            echo json_encode(['success' => false, 'message' => 'Método no permitido']);
            return;
        }

        $input = json_decode(file_get_contents('php://input'), true);
        $userId = $this->authController->getCurrentUserId();

        // Validate that either calendar_id or group_id is provided
        $requiredFields = ['title', 'start_datetime'];
        foreach ($requiredFields as $field) {
            if (!isset($input[$field]) || empty($input[$field])) {
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => "El campo $field es requerido"]);
                return;
            }
        }

        // Validate container (calendar or group)
        if (empty($input['calendar_id']) && empty($input['group_id'])) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Debe especificar un calendario o grupo']);
            return;
        }

        try {
            $eventId = $this->eventModel->createWithContainer(
                $input['calendar_id'] ?? null,
                $input['group_id'] ?? null,
                $input['container_type'] ?? 'calendar',
                $input['title'],
                $input['description'] ?? '',
                $input['start_datetime'],
                $input['end_datetime'] ?? null,
                $input['is_all_day'] ?? false,
                $input['location'] ?? '',
                $userId,
                $input['recurrence_rule'] ?? null
            );

            $event = $this->eventModel->findById($eventId);
            
            echo json_encode([
                'success' => true, 
                'message' => 'Evento creado exitosamente',
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
            echo json_encode(['success' => false, 'message' => 'Error al crear evento']);
        }
    }

    public function update($id) {
        header('Content-Type: application/json');
        $this->authController->requireAuth();

        if ($_SERVER['REQUEST_METHOD'] !== 'PUT') {
            http_response_code(405);
            echo json_encode(['success' => false, 'message' => 'Método no permitido']);
            return;
        }

        $userId = $this->authController->getCurrentUserId();
        
        // Verificar permisos
        if (!$this->eventModel->checkPermission($id, $userId, 'write')) {
            http_response_code(403);
            echo json_encode(['success' => false, 'message' => 'No tienes permisos para editar este evento']);
            return;
        }

        $input = json_decode(file_get_contents('php://input'), true);

        try {
            $event = $this->eventModel->update($id, $input, $userId);
            
            if ($event) {
                echo json_encode([
                    'success' => true, 
                    'message' => 'Evento actualizado exitosamente',
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
            } else {
                http_response_code(404);
                echo json_encode(['success' => false, 'message' => 'Evento no encontrado']);
            }
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => 'Error al actualizar evento']);
        }
    }

    public function delete($id) {
        header('Content-Type: application/json');
        $this->authController->requireAuth();

        if ($_SERVER['REQUEST_METHOD'] !== 'DELETE') {
            http_response_code(405);
            echo json_encode(['success' => false, 'message' => 'Método no permitido']);
            return;
        }

        $userId = $this->authController->getCurrentUserId();
        
        // Verificar permisos
        if (!$this->eventModel->checkPermission($id, $userId, 'write')) {
            http_response_code(403);
            echo json_encode(['success' => false, 'message' => 'No tienes permisos para eliminar este evento']);
            return;
        }

        try {
            $this->eventModel->delete($id);
            echo json_encode(['success' => true, 'message' => 'Evento eliminado exitosamente']);
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => 'Error al eliminar evento']);
        }
    }

    public function show($id) {
        header('Content-Type: application/json');
        $this->authController->requireAuth();

        $userId = $this->authController->getCurrentUserId();
        
        // Verificar permisos
        if (!$this->eventModel->checkPermission($id, $userId, 'read')) {
            http_response_code(403);
            echo json_encode(['success' => false, 'message' => 'No tienes permisos para ver este evento']);
            return;
        }

        try {
            $event = $this->eventModel->findById($id);
            
            if ($event) {
                echo json_encode([
                    'success' => true, 
                    'event' => $event
                ]);
            } else {
                http_response_code(404);
                echo json_encode(['success' => false, 'message' => 'Evento no encontrado']);
            }
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => 'Error al obtener evento']);
        }
    }
}