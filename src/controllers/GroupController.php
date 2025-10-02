<?php

class GroupController {
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
    
    // Obtener todos los grupos del usuario
    public function getIndex() {
        $user_id = $this->checkAuth();
        
        try {
            // Grupos donde el usuario es miembro o administrador
            $groups = $this->db->fetchAll(
                "SELECT g.*, gm.role,
                        (SELECT COUNT(*) FROM group_members gm2 WHERE gm2.group_id = g.id) as member_count,
                        u.full_name as created_by_name
                 FROM user_groups g
                 JOIN group_members gm ON g.id = gm.group_id
                 JOIN users u ON g.created_by = u.id
                 WHERE gm.user_id = ? AND g.is_active = 1
                 ORDER BY g.name ASC",
                [$user_id]
            );
            
            return json_encode(['success' => true, 'groups' => $groups]);
            
        } catch (Exception $e) {
            http_response_code(500);
            return json_encode(['error' => 'Error al obtener grupos: ' . $e->getMessage()]);
        }
    }
    
    // Crear nuevo grupo
    public function postIndex() {
        $user_id = $this->checkAuth();
        
        try {
            $input = json_decode(file_get_contents('php://input'), true);
            
            if (!isset($input['name']) || empty(trim($input['name']))) {
                http_response_code(400);
                return json_encode(['error' => 'Nombre del grupo requerido']);
            }
            
            $name = trim($input['name']);
            $description = $input['description'] ?? '';
            $color = $input['color'] ?? '#007bff';
            
            // Crear el grupo
            $this->db->query(
                "INSERT INTO user_groups (name, description, color, created_by) 
                 VALUES (?, ?, ?, ?)",
                [$name, $description, $color, $user_id]
            );
            
            $group_id = $this->db->lastInsertId();
            
            // Añadir al creador como administrador del grupo
            $this->db->query(
                "INSERT INTO group_members (group_id, user_id, role) 
                 VALUES (?, ?, 'admin')",
                [$group_id, $user_id]
            );
            
            // Obtener el grupo creado con información completa
            $group = $this->db->fetch(
                "SELECT g.*, 'admin' as role, 1 as member_count, u.full_name as created_by_name
                 FROM user_groups g
                 JOIN users u ON g.created_by = u.id
                 WHERE g.id = ?",
                [$group_id]
            );
            
            return json_encode(['success' => true, 'group' => $group]);
            
        } catch (Exception $e) {
            http_response_code(500);
            return json_encode(['error' => 'Error al crear grupo: ' . $e->getMessage()]);
        }
    }
    
    // Obtener miembros de un grupo
    public function getMembers() {
        $user_id = $this->checkAuth();
        
        try {
            $group_id = $_GET['group_id'] ?? null;
            if (!$group_id) {
                http_response_code(400);
                return json_encode(['error' => 'ID del grupo requerido']);
            }
            
            // Verificar que el usuario pertenece al grupo
            $membership = $this->db->fetch(
                "SELECT role FROM group_members WHERE group_id = ? AND user_id = ?",
                [$group_id, $user_id]
            );
            
            if (!$membership) {
                http_response_code(403);
                return json_encode(['error' => 'No tienes permisos para ver este grupo']);
            }
            
            // Obtener miembros del grupo
            $members = $this->db->fetchAll(
                "SELECT u.id, u.username, u.full_name, u.email, u.avatar_url,
                        gm.role, gm.joined_at
                 FROM group_members gm
                 JOIN users u ON gm.user_id = u.id
                 WHERE gm.group_id = ?
                 ORDER BY gm.role DESC, u.full_name ASC",
                [$group_id]
            );
            
            return json_encode(['success' => true, 'members' => $members]);
            
        } catch (Exception $e) {
            http_response_code(500);
            return json_encode(['error' => 'Error al obtener miembros: ' . $e->getMessage()]);
        }
    }
    
    // Añadir miembro al grupo
    public function postMembers() {
        $user_id = $this->checkAuth();
        
        try {
            $input = json_decode(file_get_contents('php://input'), true);
            
            $group_id = $input['group_id'] ?? null;
            $member_email = $input['member_email'] ?? null;
            $new_member_id = $input['user_id'] ?? null;
            $role = $input['role'] ?? 'member';
            
            if (!$group_id || (!$member_email && !$new_member_id)) {
                http_response_code(400);
                return json_encode(['error' => 'ID del grupo y email o ID del usuario requeridos']);
            }
            
            // Verificar que el usuario actual es admin del grupo
            $membership = $this->db->fetch(
                "SELECT role FROM group_members WHERE group_id = ? AND user_id = ?",
                [$group_id, $user_id]
            );
            
            if (!$membership || !in_array($membership['role'], ['admin', 'moderator'])) {
                http_response_code(403);
                return json_encode(['error' => 'No tienes permisos para añadir miembros']);
            }
            
            // Buscar usuario por ID o email
            if ($new_member_id) {
                $new_member = $this->db->fetch(
                    "SELECT id, username, full_name, email FROM users WHERE id = ? AND is_active = 1",
                    [$new_member_id]
                );
            } else {
                $new_member = $this->db->fetch(
                    "SELECT id, username, full_name, email FROM users WHERE email = ? AND is_active = 1",
                    [$member_email]
                );
            }
            
            if (!$new_member) {
                http_response_code(404);
                return json_encode(['error' => 'Usuario no encontrado']);
            }
            
            // Verificar si ya es miembro
            $existing = $this->db->fetch(
                "SELECT id FROM group_members WHERE group_id = ? AND user_id = ?",
                [$group_id, $new_member['id']]
            );
            
            if ($existing) {
                http_response_code(409);
                return json_encode(['error' => 'El usuario ya es miembro del grupo']);
            }
            
            // Añadir miembro
            $this->db->query(
                "INSERT INTO group_members (group_id, user_id, role) VALUES (?, ?, ?)",
                [$group_id, $new_member['id'], $role]
            );
            
            // Devolver información del miembro añadido
            $member_info = array_merge($new_member, [
                'role' => $role,
                'joined_at' => date('Y-m-d H:i:s')
            ]);
            
            return json_encode(['success' => true, 'member' => $member_info]);
            
        } catch (Exception $e) {
            http_response_code(500);
            return json_encode(['error' => 'Error al añadir miembro: ' . $e->getMessage()]);
        }
    }
    
    // Actualizar rol de miembro
    public function putMembers() {
        $user_id = $this->checkAuth();
        
        try {
            $input = json_decode(file_get_contents('php://input'), true);
            
            $group_id = $input['group_id'] ?? null;
            $member_id = $input['member_id'] ?? null;
            $new_role = $input['role'] ?? null;
            
            if (!$group_id || !$member_id || !$new_role) {
                http_response_code(400);
                return json_encode(['error' => 'Datos incompletos']);
            }
            
            // Verificar permisos del usuario actual
            $user_membership = $this->db->fetch(
                "SELECT role FROM group_members WHERE group_id = ? AND user_id = ?",
                [$group_id, $user_id]
            );
            
            if (!$user_membership || $user_membership['role'] !== 'admin') {
                http_response_code(403);
                return json_encode(['error' => 'Solo los administradores pueden cambiar roles']);
            }
            
            // Actualizar rol
            $this->db->query(
                "UPDATE group_members SET role = ? WHERE group_id = ? AND user_id = ?",
                [$new_role, $group_id, $member_id]
            );
            
            return json_encode(['success' => true, 'message' => 'Rol actualizado']);
            
        } catch (Exception $e) {
            http_response_code(500);
            return json_encode(['error' => 'Error al actualizar rol: ' . $e->getMessage()]);
        }
    }
    
    // Eliminar miembro del grupo
    public function deleteMembers() {
        $user_id = $this->checkAuth();
        
        try {
            $input = json_decode(file_get_contents('php://input'), true);
            
            $group_id = $input['group_id'] ?? null;
            $member_id = $input['member_id'] ?? null;
            
            if (!$group_id || !$member_id) {
                http_response_code(400);
                return json_encode(['error' => 'Datos incompletos']);
            }
            
            // Verificar permisos
            $user_membership = $this->db->fetch(
                "SELECT role FROM group_members WHERE group_id = ? AND user_id = ?",
                [$group_id, $user_id]
            );
            
            // Solo admins pueden eliminar otros miembros, o el propio usuario puede salirse
            if ($user_id != $member_id && (!$user_membership || $user_membership['role'] !== 'admin')) {
                http_response_code(403);
                return json_encode(['error' => 'Sin permisos para eliminar este miembro']);
            }
            
            // No permitir que el creador del grupo se elimine a sí mismo
            $group = $this->db->fetch(
                "SELECT created_by FROM user_groups WHERE id = ?",
                [$group_id]
            );
            
            if ($member_id == $group['created_by']) {
                http_response_code(400);
                return json_encode(['error' => 'El creador del grupo no puede eliminarse']);
            }
            
            // Eliminar miembro
            $this->db->query(
                "DELETE FROM group_members WHERE group_id = ? AND user_id = ?",
                [$group_id, $member_id]
            );
            
            return json_encode(['success' => true, 'message' => 'Miembro eliminado del grupo']);
            
        } catch (Exception $e) {
            http_response_code(500);
            return json_encode(['error' => 'Error al eliminar miembro: ' . $e->getMessage()]);
        }
    }
    
    // Buscar usuarios para añadir al grupo
    public function getSearch() {
        $user_id = $this->checkAuth();
        
        try {
            $query = $_GET['q'] ?? '';
            $group_id = $_GET['group_id'] ?? null;
            
            if (strlen($query) < 2) {
                return json_encode(['success' => true, 'users' => []]);
            }
            
            // Buscar usuarios que NO estén en el grupo
            $users = $this->db->fetchAll(
                "SELECT u.id, u.username, u.full_name, u.email
                 FROM users u
                 WHERE (u.username LIKE ? OR u.full_name LIKE ? OR u.email LIKE ?)
                   AND u.is_active = 1
                   AND u.id != ?
                   AND u.id NOT IN (
                       SELECT gm.user_id FROM group_members gm WHERE gm.group_id = ?
                   )
                 LIMIT 10",
                ["%$query%", "%$query%", "%$query%", $user_id, $group_id]
            );
            
            return json_encode(['success' => true, 'users' => $users]);
            
        } catch (Exception $e) {
            http_response_code(500);
            return json_encode(['error' => 'Error en búsqueda: ' . $e->getMessage()]);
        }
    }
}