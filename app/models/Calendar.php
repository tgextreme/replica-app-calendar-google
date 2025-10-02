<?php

class Calendar {
    private $db;
    
    public function __construct() {
        $this->db = Database::getInstance();
    }

    public function create($name, $description, $color, $ownerId, $isDefault = false, $isPublic = false, $timezone = 'UTC') {
        $sql = "INSERT INTO calendars (name, description, color, owner_id, is_default, is_public, timezone) 
                VALUES (?, ?, ?, ?, ?, ?, ?)";
        
        $this->db->query($sql, [
            $name, $description, $color, $ownerId, 
            $isDefault ? 1 : 0, $isPublic ? 1 : 0, $timezone
        ]);
        
        return $this->db->lastInsertId();
    }

    public function findById($id) {
        $sql = "SELECT c.*, u.full_name as owner_name 
                FROM calendars c 
                LEFT JOIN users u ON c.owner_id = u.id 
                WHERE c.id = ? AND c.is_active = 1";
        return $this->db->fetch($sql, [$id]);
    }

    public function getUserCalendars($userId) {
        $sql = "SELECT DISTINCT c.*, 
                       CASE 
                           WHEN c.owner_id = ? THEN 'owner'
                           WHEN cp_user.permission_level IS NOT NULL THEN cp_user.permission_level
                           WHEN cp_group.permission_level IS NOT NULL THEN cp_group.permission_level
                           ELSE 'none'
                       END as user_permission
                FROM calendars c 
                LEFT JOIN calendar_permissions cp_user ON c.id = cp_user.calendar_id AND cp_user.user_id = ?
                LEFT JOIN calendar_permissions cp_group ON c.id = cp_group.calendar_id 
                    AND cp_group.group_id IN (
                        SELECT gm.group_id FROM group_members gm WHERE gm.user_id = ?
                    )
                WHERE (
                    c.owner_id = ? 
                    OR cp_user.user_id = ?
                    OR cp_group.group_id IN (
                        SELECT gm.group_id FROM group_members gm WHERE gm.user_id = ?
                    )
                    OR c.is_public = 1
                ) AND c.is_active = 1
                ORDER BY c.is_default DESC, c.name ASC";
                
        return $this->db->fetchAll($sql, [$userId, $userId, $userId, $userId, $userId, $userId]);
    }

    public function getOwnedCalendars($userId) {
        $sql = "SELECT * FROM calendars 
                WHERE owner_id = ? AND is_active = 1 
                ORDER BY is_default DESC, name ASC";
        return $this->db->fetchAll($sql, [$userId]);
    }

    public function getSharedCalendars($userId) {
        $sql = "SELECT DISTINCT c.*, u.full_name as owner_name,
                       CASE 
                           WHEN cp_user.permission_level IS NOT NULL THEN cp_user.permission_level
                           WHEN cp_group.permission_level IS NOT NULL THEN cp_group.permission_level
                           ELSE 'read'
                       END as permission_level
                FROM calendars c 
                JOIN users u ON c.owner_id = u.id
                LEFT JOIN calendar_permissions cp_user ON c.id = cp_user.calendar_id AND cp_user.user_id = ?
                LEFT JOIN calendar_permissions cp_group ON c.id = cp_group.calendar_id 
                    AND cp_group.group_id IN (
                        SELECT gm.group_id FROM group_members gm WHERE gm.user_id = ?
                    )
                WHERE c.owner_id != ? 
                AND (cp_user.user_id = ? OR cp_group.group_id IN (
                    SELECT gm.group_id FROM group_members gm WHERE gm.user_id = ?
                ))
                AND c.is_active = 1
                ORDER BY c.name ASC";
                
        return $this->db->fetchAll($sql, [$userId, $userId, $userId, $userId, $userId]);
    }

    public function shareWithUser($calendarId, $userId, $permissionLevel, $grantedBy) {
        // Verificar que el usuario que otorga el permiso sea el propietario o tenga permisos de admin
        if (!$this->hasPermission($calendarId, $grantedBy, 'admin')) {
            return false;
        }

        $sql = "INSERT INTO calendar_permissions (calendar_id, user_id, permission_level, granted_by) 
                VALUES (?, ?, ?, ?) 
                ON DUPLICATE KEY UPDATE 
                permission_level = VALUES(permission_level), 
                granted_by = VALUES(granted_by),
                granted_at = CURRENT_TIMESTAMP";
                
        $this->db->query($sql, [$calendarId, $userId, $permissionLevel, $grantedBy]);
        return true;
    }

    public function shareWithGroup($calendarId, $groupId, $permissionLevel, $grantedBy) {
        // Verificar permisos
        if (!$this->hasPermission($calendarId, $grantedBy, 'admin')) {
            return false;
        }

        $sql = "INSERT INTO calendar_permissions (calendar_id, group_id, permission_level, granted_by) 
                VALUES (?, ?, ?, ?) 
                ON DUPLICATE KEY UPDATE 
                permission_level = VALUES(permission_level), 
                granted_by = VALUES(granted_by),
                granted_at = CURRENT_TIMESTAMP";
                
        $this->db->query($sql, [$calendarId, $groupId, $permissionLevel, $grantedBy]);
        return true;
    }

    public function hasPermission($calendarId, $userId, $requiredPermission = 'read') {
        $sql = "SELECT c.owner_id,
                       CASE 
                           WHEN c.owner_id = ? THEN 'admin'
                           WHEN cp_user.permission_level IS NOT NULL THEN cp_user.permission_level
                           WHEN cp_group.permission_level IS NOT NULL THEN cp_group.permission_level
                           WHEN c.is_public = 1 THEN 'read'
                           ELSE 'none'
                       END as user_permission
                FROM calendars c
                LEFT JOIN calendar_permissions cp_user ON c.id = cp_user.calendar_id AND cp_user.user_id = ?
                LEFT JOIN calendar_permissions cp_group ON c.id = cp_group.calendar_id 
                    AND cp_group.group_id IN (
                        SELECT gm.group_id FROM group_members gm WHERE gm.user_id = ?
                    )
                WHERE c.id = ?";
        
        $result = $this->db->fetch($sql, [$userId, $userId, $userId, $calendarId]);
        
        if (!$result) {
            return false;
        }
        
        $permission = $result['user_permission'];
        
        // Verificar permisos según el nivel requerido
        switch ($requiredPermission) {
            case 'admin':
                return $permission === 'admin';
            case 'write':
                return in_array($permission, ['admin', 'write']);
            case 'read':
                return in_array($permission, ['admin', 'write', 'read']);
            default:
                return false;
        }
    }

    public function update($id, $data, $userId) {
        // Verificar permisos de administrador
        if (!$this->hasPermission($id, $userId, 'admin')) {
            return false;
        }

        $allowedFields = ['name', 'description', 'color', 'is_public', 'timezone'];
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
            
            $sql = "UPDATE calendars SET " . implode(', ', $updateFields) . " WHERE id = ?";
            $this->db->query($sql, $params);
            
            return $this->findById($id);
        }
        
        return false;
    }

    public function delete($id, $userId) {
        // Solo el propietario puede eliminar el calendario
        $calendar = $this->findById($id);
        if (!$calendar || $calendar['owner_id'] != $userId) {
            return false;
        }

        $sql = "UPDATE calendars SET is_active = 0 WHERE id = ?";
        $this->db->query($sql, [$id]);
        return true;
    }

    public function getCalendarPermissions($calendarId) {
        $sql = "SELECT cp.*, 
                       CASE 
                           WHEN cp.user_id IS NOT NULL THEN u.full_name
                           WHEN cp.group_id IS NOT NULL THEN g.name
                       END as grantee_name,
                       CASE 
                           WHEN cp.user_id IS NOT NULL THEN 'user'
                           WHEN cp.group_id IS NOT NULL THEN 'group'
                       END as grantee_type
                FROM calendar_permissions cp
                LEFT JOIN users u ON cp.user_id = u.id
                LEFT JOIN user_groups g ON cp.group_id = g.id
                WHERE cp.calendar_id = ?
                ORDER BY grantee_type, grantee_name";
                
        return $this->db->fetchAll($sql, [$calendarId]);
    }

    public function createDefaultCalendar($userId, $userName) {
        return $this->create(
            "Calendario de $userName",
            "Calendario personal predeterminado",
            "#007bff",
            $userId,
            true,  // is_default
            false, // is_public
            'Europe/Madrid'
        );
    }
}