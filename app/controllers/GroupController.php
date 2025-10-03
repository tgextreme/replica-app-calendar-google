<?php

class GroupController {
    private $groupModel;
    private $authController;

    public function __construct() {
        $this->groupModel = new Group();
        $this->authController = new AuthController();
    }

    public function index() {
        header('Content-Type: application/json');
        $this->authController->requireAuth();

        $userId = $this->authController->getCurrentUserId();
        $isAdmin = $this->authController->isAdmin();

        try {
            $groups = $this->groupModel->getUserGroups($userId);

            echo json_encode([
                'success' => true,
                'groups' => $groups,
                'isAdmin' => $isAdmin
            ]);
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => 'Error al obtener grupos']);
        }
    }

    public function create() {
        header('Content-Type: application/json');
        $this->authController->requireAdmin(); // Solo admins pueden crear grupos

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
            $groupId = $this->groupModel->create(
                $input['name'],
                $input['description'] ?? '',
                $input['color'] ?? '#007bff',
                $userId
            );

            $group = $this->groupModel->findById($groupId);

            echo json_encode([
                'success' => true,
                'message' => 'Grupo creado exitosamente',
                'group' => $group
            ]);
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => 'Error al crear grupo']);
        }
    }

    public function show($id) {
        header('Content-Type: application/json');
        $this->authController->requireAuth();

        $userId = $this->authController->getCurrentUserId();

        if (!$this->groupModel->isMember($id, $userId)) {
            http_response_code(403);
            echo json_encode(['success' => false, 'message' => 'No eres miembro de este grupo']);
            return;
        }

        try {
            $group = $this->groupModel->findById($id);

            if ($group) {
                $isAdmin = $this->authController->isAdmin();
                
                // Solo los admins pueden ver miembros y calendarios
                if ($isAdmin) {
                    $members = $this->groupModel->getGroupMembers($id);
                    $calendars = $this->groupModel->getGroupCalendars($id);
                    $group['members'] = $members;
                    $group['calendars'] = $calendars;
                } else {
                    // Los usuarios normales solo ven información básica del grupo
                    $group['members'] = [];
                    $group['calendars'] = [];
                }
                
                $group['isAdmin'] = $isAdmin;

                echo json_encode([
                    'success' => true,
                    'group' => $group
                ]);
            } else {
                http_response_code(404);
                echo json_encode(['success' => false, 'message' => 'Grupo no encontrado']);
            }
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => 'Error al obtener grupo']);
        }
    }

    public function addMember($id) {
        header('Content-Type: application/json');
        $this->authController->requireAuth();

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            echo json_encode(['success' => false, 'message' => 'Método no permitido']);
            return;
        }

        $input = json_decode(file_get_contents('php://input'), true);
        $userId = $this->authController->getCurrentUserId();

        // Verificar que el usuario actual es admin o moderador del grupo
        $userRole = $this->groupModel->getMemberRole($id, $userId);
        if (!in_array($userRole, ['admin', 'moderator'])) {
            http_response_code(403);
            echo json_encode(['success' => false, 'message' => 'No tienes permisos para agregar miembros']);
            return;
        }

        if (isset($input['email'])) {
            // Invitar por email
            try {
                if ($this->groupModel->inviteUser($id, $input['email'], $userId, $input['role'] ?? 'member')) {
                    echo json_encode(['success' => true, 'message' => 'Usuario invitado exitosamente']);
                } else {
                    echo json_encode(['success' => false, 'message' => 'No se pudo invitar al usuario']);
                }
            } catch (Exception $e) {
                http_response_code(500);
                echo json_encode(['success' => false, 'message' => 'Error al invitar usuario']);
            }
        } elseif (isset($input['user_id'])) {
            // Agregar usuario directamente
            try {
                if ($this->groupModel->addMember($id, $input['user_id'], $input['role'] ?? 'member')) {
                    echo json_encode(['success' => true, 'message' => 'Miembro agregado exitosamente']);
                } else {
                    echo json_encode(['success' => false, 'message' => 'No se pudo agregar el miembro']);
                }
            } catch (Exception $e) {
                http_response_code(500);
                echo json_encode(['success' => false, 'message' => 'Error al agregar miembro']);
            }
        } else {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Se requiere email o user_id']);
        }
    }

    public function removeMember($id, $memberId) {
        header('Content-Type: application/json');
        $this->authController->requireAuth();

        if ($_SERVER['REQUEST_METHOD'] !== 'DELETE') {
            http_response_code(405);
            echo json_encode(['success' => false, 'message' => 'Método no permitido']);
            return;
        }

        $userId = $this->authController->getCurrentUserId();

        try {
            if ($this->groupModel->removeMember($id, $memberId, $userId)) {
                echo json_encode(['success' => true, 'message' => 'Miembro removido exitosamente']);
            } else {
                http_response_code(403);
                echo json_encode(['success' => false, 'message' => 'No tienes permisos para remover este miembro']);
            }
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => 'Error al remover miembro']);
        }
    }

    public function updateMemberRole($id, $memberId) {
        header('Content-Type: application/json');
        $this->authController->requireAuth();

        if ($_SERVER['REQUEST_METHOD'] !== 'PUT') {
            http_response_code(405);
            echo json_encode(['success' => false, 'message' => 'Método no permitido']);
            return;
        }

        $input = json_decode(file_get_contents('php://input'), true);
        $userId = $this->authController->getCurrentUserId();

        if (!isset($input['role'])) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'El campo role es requerido']);
            return;
        }

        try {
            if ($this->groupModel->updateMemberRole($id, $memberId, $input['role'], $userId)) {
                echo json_encode(['success' => true, 'message' => 'Rol actualizado exitosamente']);
            } else {
                http_response_code(403);
                echo json_encode(['success' => false, 'message' => 'No tienes permisos para cambiar roles']);
            }
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => 'Error al actualizar rol']);
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
            $group = $this->groupModel->update($id, $input, $userId);

            if ($group) {
                echo json_encode([
                    'success' => true,
                    'message' => 'Grupo actualizado exitosamente',
                    'group' => $group
                ]);
            } else {
                http_response_code(403);
                echo json_encode(['success' => false, 'message' => 'No tienes permisos para editar este grupo']);
            }
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => 'Error al actualizar grupo']);
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
            if ($this->groupModel->delete($id, $userId)) {
                echo json_encode(['success' => true, 'message' => 'Grupo eliminado exitosamente']);
            } else {
                http_response_code(403);
                echo json_encode(['success' => false, 'message' => 'No tienes permisos para eliminar este grupo']);
            }
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => 'Error al eliminar grupo']);
        }
    }

    public function search() {
        header('Content-Type: application/json');
        $this->authController->requireAdmin(); // Solo admins pueden buscar usuarios

        $searchTerm = $_GET['q'] ?? '';
        $groupId = intval($_GET['group_id'] ?? 0);
        $limit = intval($_GET['limit'] ?? 10);

        try {
            if (!empty($searchTerm) && $groupId > 0) {
                // Buscar usuarios que NO son miembros del grupo
                $users = $this->groupModel->searchAvailableUsers($searchTerm, $groupId, $limit);
            } else {
                $users = [];
            }

            echo json_encode([
                'success' => true,
                'users' => $users
            ]);
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => 'Error al buscar usuarios']);
        }
    }

    public function join($id) {
        header('Content-Type: application/json');
        $this->authController->requireAuth();

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            echo json_encode(['success' => false, 'message' => 'Método no permitido']);
            return;
        }

        $userId = $this->authController->getCurrentUserId();

        // Verificar que el usuario no sea ya miembro
        if ($this->groupModel->isMember($id, $userId)) {
            echo json_encode(['success' => false, 'message' => 'Ya eres miembro de este grupo']);
            return;
        }

        try {
            if ($this->groupModel->addMember($id, $userId, 'member')) {
                echo json_encode(['success' => true, 'message' => 'Te has unido al grupo exitosamente']);
            } else {
                echo json_encode(['success' => false, 'message' => 'No se pudo unir al grupo']);
            }
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => 'Error al unirse al grupo']);
        }
    }

    public function leave($id) {
        header('Content-Type: application/json');
        $this->authController->requireAuth();

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            echo json_encode(['success' => false, 'message' => 'Método no permitido']);
            return;
        }

        $userId = $this->authController->getCurrentUserId();

        try {
            if ($this->groupModel->removeMember($id, $userId, $userId)) {
                echo json_encode(['success' => true, 'message' => 'Has salido del grupo exitosamente']);
            } else {
                echo json_encode(['success' => false, 'message' => 'No se pudo salir del grupo']);
            }
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => 'Error al salir del grupo']);
        }
    }
}