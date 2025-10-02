<?php

class CalendarController {
    private $calendarModel;
    private $authController;

    public function __construct() {
        $this->calendarModel = new Calendar();
        $this->authController = new AuthController();
    }

    public function index() {
        header('Content-Type: application/json');
        $this->authController->requireAuth();

        $userId = $this->authController->getCurrentUserId();

        try {
            // Union query to combine calendars and groups
            $unionQuery = "
                (SELECT 
                    CONCAT('calendar_', id) as unique_id,
                    id as original_id,
                    name,
                    description,
                    color,
                    owner_id as creator_id,
                    'calendar' as type,
                    CASE 
                        WHEN owner_id = ? THEN 'owner'
                        ELSE 'shared'
                    END as permission_level
                FROM calendars 
                WHERE is_active = 1 AND (owner_id = ? OR is_public = 1))
                
                UNION ALL
                
                (SELECT 
                    CONCAT('group_', ug.id) as unique_id,
                    ug.id as original_id,
                    ug.name,
                    ug.description,
                    ug.color,
                    ug.created_by as creator_id,
                    'group' as type,
                    CASE 
                        WHEN ug.created_by = ? THEN 'owner'
                        WHEN gm.user_id IS NOT NULL THEN 'member'
                        ELSE 'none'
                    END as permission_level
                FROM user_groups ug
                LEFT JOIN group_members gm ON ug.id = gm.group_id AND gm.user_id = ?
                WHERE ug.is_active = 1 AND (ug.created_by = ? OR gm.user_id = ?))
                
                ORDER BY type, name
            ";
            
            $db = Database::getInstance();
            $allOptions = $db->fetchAll($unionQuery, [$userId, $userId, $userId, $userId, $userId, $userId]);

            echo json_encode([
                'success' => true,
                'calendars' => $allOptions
            ]);
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => 'Error al obtener calendarios: ' . $e->getMessage()]);
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

        $requiredFields = ['name'];
        foreach ($requiredFields as $field) {
            if (!isset($input[$field]) || empty($input[$field])) {
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => "El campo $field es requerido"]);
                return;
            }
        }

        try {
            $calendarId = $this->calendarModel->create(
                $input['name'],
                $input['description'] ?? '',
                $input['color'] ?? '#007bff',
                $userId,
                $input['is_default'] ?? false,
                $input['is_public'] ?? false,
                $input['timezone'] ?? 'Europe/Madrid'
            );

            $calendar = $this->calendarModel->findById($calendarId);

            echo json_encode([
                'success' => true,
                'message' => 'Calendario creado exitosamente',
                'calendar' => $calendar
            ]);
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => 'Error al crear calendario']);
        }
    }

    public function show($id) {
        header('Content-Type: application/json');
        $this->authController->requireAuth();

        $userId = $this->authController->getCurrentUserId();

        if (!$this->calendarModel->hasPermission($id, $userId, 'read')) {
            http_response_code(403);
            echo json_encode(['success' => false, 'message' => 'No tienes permisos para ver este calendario']);
            return;
        }

        try {
            $calendar = $this->calendarModel->findById($id);

            if ($calendar) {
                $permissions = $this->calendarModel->getCalendarPermissions($id);
                $calendar['permissions'] = $permissions;

                echo json_encode([
                    'success' => true,
                    'calendar' => $calendar
                ]);
            } else {
                http_response_code(404);
                echo json_encode(['success' => false, 'message' => 'Calendario no encontrado']);
            }
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => 'Error al obtener calendario']);
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
        $input = json_decode(file_get_contents('php://input'), true);

        try {
            $calendar = $this->calendarModel->update($id, $input, $userId);

            if ($calendar) {
                echo json_encode([
                    'success' => true,
                    'message' => 'Calendario actualizado exitosamente',
                    'calendar' => $calendar
                ]);
            } else {
                http_response_code(403);
                echo json_encode(['success' => false, 'message' => 'No tienes permisos para editar este calendario']);
            }
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => 'Error al actualizar calendario']);
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

        try {
            if ($this->calendarModel->delete($id, $userId)) {
                echo json_encode(['success' => true, 'message' => 'Calendario eliminado exitosamente']);
            } else {
                http_response_code(403);
                echo json_encode(['success' => false, 'message' => 'No tienes permisos para eliminar este calendario']);
            }
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => 'Error al eliminar calendario']);
        }
    }

    public function shareWithUser($calendarId) {
        header('Content-Type: application/json');
        $this->authController->requireAuth();

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            echo json_encode(['success' => false, 'message' => 'Método no permitido']);
            return;
        }

        $input = json_decode(file_get_contents('php://input'), true);
        $userId = $this->authController->getCurrentUserId();

        $requiredFields = ['user_id', 'permission_level'];
        foreach ($requiredFields as $field) {
            if (!isset($input[$field])) {
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => "El campo $field es requerido"]);
                return;
            }
        }

        try {
            if ($this->calendarModel->shareWithUser($calendarId, $input['user_id'], $input['permission_level'], $userId)) {
                echo json_encode(['success' => true, 'message' => 'Calendario compartido exitosamente']);
            } else {
                http_response_code(403);
                echo json_encode(['success' => false, 'message' => 'No tienes permisos para compartir este calendario']);
            }
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => 'Error al compartir calendario']);
        }
    }

    public function shareWithGroup($calendarId) {
        header('Content-Type: application/json');
        $this->authController->requireAuth();

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            echo json_encode(['success' => false, 'message' => 'Método no permitido']);
            return;
        }

        $input = json_decode(file_get_contents('php://input'), true);
        $userId = $this->authController->getCurrentUserId();

        $requiredFields = ['group_id', 'permission_level'];
        foreach ($requiredFields as $field) {
            if (!isset($input[$field])) {
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => "El campo $field es requerido"]);
                return;
            }
        }

        try {
            if ($this->calendarModel->shareWithGroup($calendarId, $input['group_id'], $input['permission_level'], $userId)) {
                echo json_encode(['success' => true, 'message' => 'Calendario compartido con grupo exitosamente']);
            } else {
                http_response_code(403);
                echo json_encode(['success' => false, 'message' => 'No tienes permisos para compartir este calendario']);
            }
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => 'Error al compartir calendario con grupo']);
        }
    }
}