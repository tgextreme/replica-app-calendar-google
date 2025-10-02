<?php

class User {
    private $db;
    
    public function __construct() {
        $this->db = Database::getInstance();
    }

    public function create($username, $email, $password, $fullName) {
        $passwordHash = password_hash($password, PASSWORD_DEFAULT);
        
        $sql = "INSERT INTO users (username, email, password_hash, full_name) VALUES (?, ?, ?, ?)";
        $this->db->query($sql, [$username, $email, $passwordHash, $fullName]);
        
        return $this->db->lastInsertId();
    }

    public function findByEmail($email) {
        $sql = "SELECT * FROM users WHERE email = ? AND is_active = 1";
        return $this->db->fetch($sql, [$email]);
    }

    public function findByUsername($username) {
        $sql = "SELECT * FROM users WHERE username = ? AND is_active = 1";
        return $this->db->fetch($sql, [$username]);
    }

    public function findById($id) {
        $sql = "SELECT * FROM users WHERE id = ? AND is_active = 1";
        return $this->db->fetch($sql, [$id]);
    }

    public function authenticate($email, $password) {
        $user = $this->findByEmail($email);
        
        // Using MD5 for password verification (as per your current setup)
        if ($user && md5($password) === $user['password_hash']) {
            return $user;
        }
        
        return false;
    }

    public function updateLastActivity($userId) {
        $sql = "UPDATE users SET updated_at = CURRENT_TIMESTAMP WHERE id = ?";
        $this->db->query($sql, [$userId]);
    }

    public function getUserGroups($userId) {
        $sql = "SELECT g.*, gm.role 
                FROM user_groups g 
                JOIN group_members gm ON g.id = gm.group_id 
                WHERE gm.user_id = ? AND g.is_active = 1
                ORDER BY g.name";
        return $this->db->fetchAll($sql, [$userId]);
    }

    public function getUserCalendars($userId) {
        $sql = "SELECT c.* FROM calendars c 
                WHERE (c.owner_id = ? 
                   OR c.id IN (
                       SELECT cp.calendar_id FROM calendar_permissions cp 
                       WHERE cp.user_id = ?
                   )
                   OR c.id IN (
                       SELECT cp.calendar_id FROM calendar_permissions cp 
                       JOIN group_members gm ON cp.group_id = gm.group_id 
                       WHERE gm.user_id = ?
                   ))
                   AND c.is_active = 1
                ORDER BY c.is_default DESC, c.name";
        return $this->db->fetchAll($sql, [$userId, $userId, $userId]);
    }
}