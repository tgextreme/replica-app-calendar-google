<?php

class Group {
    private $db;
    
    public function __construct() {
        $this->db = Database::getInstance();
    }

    public function create($name, $description, $color, $createdBy) {
        $sql = "INSERT INTO user_groups (name, description, color, created_by) VALUES (?, ?, ?, ?)";
        $this->db->query($sql, [$name, $description, $color, $createdBy]);
        
        $groupId = $this->db->lastInsertId();
        
        // Agregar al creador como administrador del grupo
        $this->addMember($groupId, $createdBy, 'admin');
        
        return $groupId;
    }

    public function findById($id) {
        $sql = "SELECT g.*, u.full_name as created_by_name,
                       COUNT(gm.user_id) as member_count
                FROM user_groups g 
                LEFT JOIN users u ON g.created_by = u.id
                LEFT JOIN group_members gm ON g.id = gm.group_id
                WHERE g.id = ? AND g.is_active = 1
                GROUP BY g.id";
        return $this->db->fetch($sql, [$id]);
    }

    public function getUserGroups($userId) {
        $sql = "SELECT g.*, gm.role, gm.joined_at,
                       COUNT(DISTINCT gm2.user_id) as member_count
                FROM user_groups g 
                JOIN group_members gm ON g.id = gm.group_id 
                LEFT JOIN group_members gm2 ON g.id = gm2.group_id
                WHERE gm.user_id = ? AND g.is_active = 1
                GROUP BY g.id, gm.role, gm.joined_at
                ORDER BY g.name";
        return $this->db->fetchAll($sql, [$userId]);
    }

    public function getGroupMembers($groupId) {
        $sql = "SELECT u.id, u.username, u.full_name, u.email, u.avatar_url,
                       gm.role, gm.joined_at
                FROM group_members gm
                JOIN users u ON gm.user_id = u.id
                WHERE gm.group_id = ? AND u.is_active = 1
                ORDER BY 
                    CASE gm.role 
                        WHEN 'admin' THEN 1 
                        WHEN 'moderator' THEN 2 
                        ELSE 3 
                    END, 
                    u.full_name";
        return $this->db->fetchAll($sql, [$groupId]);
    }

    public function addMember($groupId, $userId, $role = 'member') {
        // Verificar que el grupo existe
        if (!$this->findById($groupId)) {
            return false;
        }

        $sql = "INSERT INTO group_members (group_id, user_id, role) VALUES (?, ?, ?)
                ON DUPLICATE KEY UPDATE role = VALUES(role)";
        
        try {
            $this->db->query($sql, [$groupId, $userId, $role]);
            return true;
        } catch (Exception $e) {
            return false;
        }
    }

    public function removeMember($groupId, $userId, $removedBy) {
        // Verificar permisos: admin puede remover a cualquiera, moderador puede remover members
        $removerRole = $this->getMemberRole($groupId, $removedBy);
        $targetRole = $this->getMemberRole($groupId, $userId);
        
        if (!$removerRole || $removerRole === 'member') {
            return false; // Sin permisos
        }
        
        if ($removerRole === 'moderator' && in_array($targetRole, ['admin', 'moderator'])) {
            return false; // Moderador no puede remover admin o moderador
        }

        $sql = "DELETE FROM group_members WHERE group_id = ? AND user_id = ?";
        $this->db->query($sql, [$groupId, $userId]);
        return true;
    }

    public function updateMemberRole($groupId, $userId, $newRole, $updatedBy) {
        // Verificar permisos: solo admin puede cambiar roles
        $updaterRole = $this->getMemberRole($groupId, $updatedBy);
        
        if ($updaterRole !== 'admin') {
            return false;
        }

        $sql = "UPDATE group_members SET role = ? WHERE group_id = ? AND user_id = ?";
        $this->db->query($sql, [$newRole, $groupId, $userId]);
        return true;
    }

    public function getMemberRole($groupId, $userId) {
        $sql = "SELECT role FROM group_members WHERE group_id = ? AND user_id = ?";
        $result = $this->db->fetch($sql, [$groupId, $userId]);
        return $result ? $result['role'] : null;
    }

    public function isMember($groupId, $userId) {
        return $this->getMemberRole($groupId, $userId) !== null;
    }

    public function isAdmin($groupId, $userId) {
        return $this->getMemberRole($groupId, $userId) === 'admin';
    }

    public function update($id, $data, $userId) {
        // Verificar que el usuario es admin del grupo
        if (!$this->isAdmin($id, $userId)) {
            return false;
        }

        $allowedFields = ['name', 'description', 'color'];
        $updateFields = [];
        $params = [];
        
        foreach ($data as $field => $value) {
            if (in_array($field, $allowedFields)) {
                $updateFields[] = "$field = ?";
                $params[] = $value;
            }
        }
        
        if (!empty($updateFields)) {
            $updateFields[] = "updated_at = CURRENT_TIMESTAMP";
            $params[] = $id;
            
            $sql = "UPDATE user_groups SET " . implode(', ', $updateFields) . " WHERE id = ?";
            $this->db->query($sql, $params);
            
            return $this->findById($id);
        }
        
        return false;
    }

    public function delete($id, $userId) {
        // Solo el creador o admin puede eliminar el grupo
        $group = $this->findById($id);
        if (!$group) {
            return false;
        }

        $isCreator = $group['created_by'] == $userId;
        $isAdmin = $this->isAdmin($id, $userId);
        
        if (!$isCreator && !$isAdmin) {
            return false;
        }

        $this->db->beginTransaction();
        
        try {
            // Eliminar miembros del grupo
            $sql = "DELETE FROM group_members WHERE group_id = ?";
            $this->db->query($sql, [$id]);
            
            // Marcar grupo como inactivo
            $sql = "UPDATE user_groups SET is_active = 0 WHERE id = ?";
            $this->db->query($sql, [$id]);
            
            $this->db->commit();
            return true;
        } catch (Exception $e) {
            $this->db->rollBack();
            return false;
        }
    }

    public function searchGroups($searchTerm, $limit = 10) {
        $sql = "SELECT g.id, g.name, g.description, g.color,
                       COUNT(gm.user_id) as member_count
                FROM user_groups g
                LEFT JOIN group_members gm ON g.id = gm.group_id
                WHERE g.is_active = 1 
                AND (g.name LIKE ? OR g.description LIKE ?)
                GROUP BY g.id
                ORDER BY member_count DESC, g.name ASC
                LIMIT ?";
                
        $searchPattern = "%$searchTerm%";
        return $this->db->fetchAll($sql, [$searchPattern, $searchPattern, $limit]);
    }

    public function getPublicGroups($limit = 20) {
        $sql = "SELECT g.id, g.name, g.description, g.color,
                       COUNT(gm.user_id) as member_count,
                       u.full_name as created_by_name
                FROM user_groups g
                LEFT JOIN group_members gm ON g.id = gm.group_id
                LEFT JOIN users u ON g.created_by = u.id
                WHERE g.is_active = 1
                GROUP BY g.id
                ORDER BY member_count DESC, g.created_at DESC
                LIMIT ?";
                
        return $this->db->fetchAll($sql, [$limit]);
    }

    public function inviteUser($groupId, $userEmail, $invitedBy, $role = 'member') {
        // Verificar permisos del invitador
        $inviterRole = $this->getMemberRole($groupId, $invitedBy);
        if (!in_array($inviterRole, ['admin', 'moderator'])) {
            return false;
        }

        // Buscar usuario por email
        $userModel = new User();
        $user = $userModel->findByEmail($userEmail);
        
        if (!$user) {
            return false;
        }

        // Verificar que no sea ya miembro
        if ($this->isMember($groupId, $user['id'])) {
            return false;
        }

        return $this->addMember($groupId, $user['id'], $role);
    }

    public function getGroupCalendars($groupId) {
        $sql = "SELECT DISTINCT c.*, cp.permission_level
                FROM calendars c
                JOIN calendar_permissions cp ON c.id = cp.calendar_id
                WHERE cp.group_id = ? AND c.is_active = 1
                ORDER BY c.name";
                
        return $this->db->fetchAll($sql, [$groupId]);
    }
}