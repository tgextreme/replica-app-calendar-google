<?php

class Event {
    private $db;
    
    public function __construct() {
        $this->db = Database::getInstance();
    }

    public function create($calendarId, $title, $description, $startDatetime, $endDatetime, $isAllDay, $location, $createdBy, $recurrenceRule = null) {
        $sql = "INSERT INTO events (calendar_id, title, description, start_datetime, end_datetime, is_all_day, location, created_by, recurrence_rule) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";
        
        $this->db->query($sql, [
            $calendarId, $title, $description, $startDatetime, $endDatetime, 
            $isAllDay ? 1 : 0, $location, $createdBy, $recurrenceRule
        ]);
        
        return $this->db->lastInsertId();
    }

    public function createWithContainer($calendarId, $groupId, $containerType, $title, $description, $startDatetime, $endDatetime, $isAllDay, $location, $createdBy, $recurrenceRule = null) {
        $sql = "INSERT INTO events (calendar_id, group_id, container_type, title, description, start_datetime, end_datetime, is_all_day, location, created_by, recurrence_rule) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
        
        $this->db->query($sql, [
            $calendarId, $groupId, $containerType, $title, $description, $startDatetime, $endDatetime, 
            $isAllDay ? 1 : 0, $location, $createdBy, $recurrenceRule
        ]);
        
        return $this->db->lastInsertId();
    }

    public function update($id, $data, $updatedBy) {
        $allowedFields = ['title', 'description', 'start_datetime', 'end_datetime', 'is_all_day', 'location', 'status', 'priority'];
        $updateFields = [];
        $params = [];
        
        foreach ($data as $field => $value) {
            if (in_array($field, $allowedFields)) {
                $updateFields[] = "$field = ?";
                $params[] = $value;
            }
        }
        
        if (!empty($updateFields)) {
            $updateFields[] = "updated_by = ?";
            $updateFields[] = "updated_at = CURRENT_TIMESTAMP";
            $params[] = $updatedBy;
            $params[] = $id;
            
            $sql = "UPDATE events SET " . implode(', ', $updateFields) . " WHERE id = ?";
            $this->db->query($sql, $params);
        }
        
        return $this->findById($id);
    }

    public function delete($id) {
        $sql = "DELETE FROM events WHERE id = ?";
        $this->db->query($sql, [$id]);
    }

    public function findById($id) {
        $sql = "SELECT e.*, 
                       CASE 
                           WHEN e.calendar_id IS NOT NULL THEN c.name 
                           WHEN e.group_id IS NOT NULL THEN g.name 
                       END as container_name,
                       CASE 
                           WHEN e.calendar_id IS NOT NULL THEN c.color 
                           WHEN e.group_id IS NOT NULL THEN '#6c757d'
                       END as container_color,
                       c.name as calendar_name, c.color as calendar_color,
                       g.name as group_name,
                       u1.full_name as created_by_name, u2.full_name as updated_by_name
                FROM events e 
                LEFT JOIN calendars c ON e.calendar_id = c.id
                LEFT JOIN user_groups g ON e.group_id = g.id
                LEFT JOIN users u1 ON e.created_by = u1.id
                LEFT JOIN users u2 ON e.updated_by = u2.id
                WHERE e.id = ?";
        return $this->db->fetch($sql, [$id]);
    }

    public function getEventsByDateRange($userId, $startDate, $endDate, $calendarIds = null) {
        $sql = "SELECT e.*, c.name as calendar_name, c.color as calendar_color,
                       u.full_name as created_by_name
                FROM events e 
                JOIN calendars c ON e.calendar_id = c.id
                LEFT JOIN users u ON e.created_by = u.id
                WHERE (
                    c.owner_id = ? 
                    OR c.id IN (
                        SELECT cp.calendar_id FROM calendar_permissions cp 
                        WHERE cp.user_id = ?
                    )
                    OR c.id IN (
                        SELECT cp.calendar_id FROM calendar_permissions cp 
                        JOIN group_members gm ON cp.group_id = gm.group_id 
                        WHERE gm.user_id = ?
                    )
                )
                AND (
                    (e.start_datetime BETWEEN ? AND ?) 
                    OR (e.end_datetime BETWEEN ? AND ?)
                    OR (e.start_datetime <= ? AND e.end_datetime >= ?)
                )";
        
        $params = [$userId, $userId, $userId, $startDate, $endDate, $startDate, $endDate, $startDate, $endDate];
        
        if ($calendarIds && !empty($calendarIds)) {
            $placeholders = str_repeat('?,', count($calendarIds) - 1) . '?';
            $sql .= " AND c.id IN ($placeholders)";
            $params = array_merge($params, $calendarIds);
        }
        
        $sql .= " ORDER BY e.start_datetime ASC";
        
        return $this->db->fetchAll($sql, $params);
    }

    public function getEventsForCalendar($calendarId, $startDate, $endDate) {
        $sql = "SELECT e.*, c.name as calendar_name, c.color as calendar_color,
                       u.full_name as created_by_name
                FROM events e 
                JOIN calendars c ON e.calendar_id = c.id
                LEFT JOIN users u ON e.created_by = u.id
                WHERE e.calendar_id = ? 
                AND (
                    (e.start_datetime BETWEEN ? AND ?) 
                    OR (e.end_datetime BETWEEN ? AND ?)
                    OR (e.start_datetime <= ? AND e.end_datetime >= ?)
                )
                ORDER BY e.start_datetime ASC";
        
        return $this->db->fetchAll($sql, [$calendarId, $startDate, $endDate, $startDate, $endDate, $startDate, $endDate]);
    }

    public function checkPermission($eventId, $userId, $requiredPermission = 'read') {
        $sql = "SELECT e.*, c.owner_id,
                       CASE 
                           WHEN c.owner_id = ? THEN 'admin'
                           WHEN cp_user.permission_level IS NOT NULL THEN cp_user.permission_level
                           WHEN cp_group.permission_level IS NOT NULL THEN cp_group.permission_level
                           ELSE 'none'
                       END as user_permission
                FROM events e
                JOIN calendars c ON e.calendar_id = c.id
                LEFT JOIN calendar_permissions cp_user ON c.id = cp_user.calendar_id AND cp_user.user_id = ?
                LEFT JOIN calendar_permissions cp_group ON c.id = cp_group.calendar_id 
                    AND cp_group.group_id IN (
                        SELECT gm.group_id FROM group_members gm WHERE gm.user_id = ?
                    )
                WHERE e.id = ?";
        
        $event = $this->db->fetch($sql, [$userId, $userId, $userId, $eventId]);
        
        if (!$event) {
            return false;
        }
        
        $permission = $event['user_permission'];
        
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
}