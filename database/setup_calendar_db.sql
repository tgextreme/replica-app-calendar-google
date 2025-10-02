-- Script para usar la base de datos 'calendar' existente
-- Ejecutar en phpMyAdmin

USE calendar;

-- Configurar modo SQL para MySQL 9.1.0
SET SESSION sql_mode = 'STRICT_TRANS_TABLES,NO_ZERO_DATE,NO_ZERO_IN_DATE,ERROR_FOR_DIVISION_BY_ZERO';

-- Tabla de usuarios
CREATE TABLE IF NOT EXISTS users (
    id INT PRIMARY KEY AUTO_INCREMENT,
    username VARCHAR(50) UNIQUE NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    password_hash VARCHAR(255) NOT NULL,
    full_name VARCHAR(100) NOT NULL,
    avatar_url VARCHAR(255),
    timezone VARCHAR(50) DEFAULT 'UTC',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    is_active BOOLEAN DEFAULT TRUE
);

-- Tabla de grupos de usuarios
CREATE TABLE IF NOT EXISTS user_groups (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(100) NOT NULL,
    description TEXT,
    color VARCHAR(7) DEFAULT '#007bff',
    created_by INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    is_active BOOLEAN DEFAULT TRUE,
    FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE CASCADE
);

-- Tabla de membresía en grupos
CREATE TABLE IF NOT EXISTS group_members (
    id INT PRIMARY KEY AUTO_INCREMENT,
    group_id INT NOT NULL,
    user_id INT NOT NULL,
    role ENUM('admin', 'moderator', 'member') DEFAULT 'member',
    joined_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (group_id) REFERENCES user_groups(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    UNIQUE KEY unique_group_user (group_id, user_id)
);

-- Tabla de calendarios
CREATE TABLE IF NOT EXISTS calendars (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(100) NOT NULL,
    description TEXT,
    color VARCHAR(7) DEFAULT '#007bff',
    owner_id INT NOT NULL,
    is_default BOOLEAN DEFAULT FALSE,
    is_public BOOLEAN DEFAULT FALSE,
    timezone VARCHAR(50) DEFAULT 'UTC',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    is_active BOOLEAN DEFAULT TRUE,
    FOREIGN KEY (owner_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Tabla de permisos de calendarios compartidos
CREATE TABLE IF NOT EXISTS calendar_permissions (
    id INT PRIMARY KEY AUTO_INCREMENT,
    calendar_id INT NOT NULL,
    user_id INT,
    group_id INT,
    permission_level ENUM('read', 'write', 'admin') DEFAULT 'read',
    granted_by INT NOT NULL,
    granted_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (calendar_id) REFERENCES calendars(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (group_id) REFERENCES user_groups(id) ON DELETE CASCADE,
    FOREIGN KEY (granted_by) REFERENCES users(id) ON DELETE CASCADE,
    CONSTRAINT check_user_or_group CHECK (
        (user_id IS NOT NULL AND group_id IS NULL) OR
        (user_id IS NULL AND group_id IS NOT NULL)
    )
);

-- Tabla de eventos
CREATE TABLE IF NOT EXISTS events (
    id INT PRIMARY KEY AUTO_INCREMENT,
    calendar_id INT NOT NULL,
    title VARCHAR(200) NOT NULL,
    description TEXT,
    start_datetime DATETIME NOT NULL,
    end_datetime DATETIME,
    is_all_day BOOLEAN DEFAULT FALSE,
    location VARCHAR(255),
    created_by INT NOT NULL,
    updated_by INT,
    recurrence_rule VARCHAR(500),
    parent_event_id INT,
    status ENUM('confirmed', 'tentative', 'cancelled') DEFAULT 'confirmed',
    priority ENUM('low', 'normal', 'high') DEFAULT 'normal',
    visibility ENUM('public', 'private', 'confidential') DEFAULT 'public',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (calendar_id) REFERENCES calendars(id) ON DELETE CASCADE,
    FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (updated_by) REFERENCES users(id) ON DELETE SET NULL,
    FOREIGN KEY (parent_event_id) REFERENCES events(id) ON DELETE CASCADE,
    INDEX idx_calendar_datetime (calendar_id, start_datetime),
    INDEX idx_datetime_range (start_datetime, end_datetime)
);

-- Tabla de participantes en eventos
CREATE TABLE IF NOT EXISTS event_participants (
    id INT PRIMARY KEY AUTO_INCREMENT,
    event_id INT NOT NULL,
    user_id INT,
    group_id INT,
    status ENUM('pending', 'accepted', 'declined', 'tentative') DEFAULT 'pending',
    response_datetime TIMESTAMP NULL,
    added_by INT NOT NULL,
    added_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (event_id) REFERENCES events(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (group_id) REFERENCES user_groups(id) ON DELETE CASCADE,
    FOREIGN KEY (added_by) REFERENCES users(id) ON DELETE CASCADE,
    CONSTRAINT check_participant_user_or_group CHECK (
        (user_id IS NOT NULL AND group_id IS NULL) OR
        (user_id IS NULL AND group_id IS NOT NULL)
    ),
    UNIQUE KEY unique_event_participant (event_id, user_id, group_id)
);

-- Tabla de recordatorios
CREATE TABLE IF NOT EXISTS event_reminders (
    id INT PRIMARY KEY AUTO_INCREMENT,
    event_id INT NOT NULL,
    user_id INT NOT NULL,
    remind_before_minutes INT NOT NULL,
    method ENUM('email', 'notification', 'popup') DEFAULT 'notification',
    is_sent BOOLEAN DEFAULT FALSE,
    sent_at TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (event_id) REFERENCES events(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Tabla de sesiones de usuario
CREATE TABLE IF NOT EXISTS user_sessions (
    id VARCHAR(128) PRIMARY KEY,
    user_id INT NOT NULL,
    ip_address VARCHAR(45),
    user_agent TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    last_activity TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    expires_at TIMESTAMP NOT NULL,
    is_active BOOLEAN DEFAULT TRUE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_user_active (user_id, is_active),
    INDEX idx_expires (expires_at)
);

-- Insertar datos de ejemplo
INSERT IGNORE INTO users (username, email, password_hash, full_name) VALUES
('admin', 'admin@calendario.local', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Administrador'),
('tomas', 'tomas@calendario.local', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Tomás González'),
('jose', 'jose@calendario.local', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'José');

INSERT IGNORE INTO user_groups (name, description, color, created_by) VALUES
('Cumpleaños', 'Eventos de cumpleaños y celebraciones', '#e74c3c', 1),
('Tasks', 'Tareas y recordatorios', '#f39c12', 1),
('Festivos en España', 'Días festivos y vacaciones', '#27ae60', 1),
('Tomás', 'Calendario personal de Tomás', '#3498db', 2);

INSERT IGNORE INTO group_members (group_id, user_id, role) VALUES
(1, 1, 'admin'),
(1, 2, 'member'),
(1, 3, 'member'),
(2, 1, 'admin'),
(2, 2, 'member'),
(3, 1, 'admin'),
(3, 2, 'member'),
(3, 3, 'member'),
(4, 2, 'admin');

INSERT IGNORE INTO calendars (name, description, color, owner_id, is_default) VALUES
('Calendario Principal', 'Mi calendario personal', '#007bff', 1, TRUE),
('Cumpleaños', 'Eventos de cumpleaños', '#e74c3c', 1, FALSE),
('Tareas', 'Recordatorios y tareas', '#f39c12', 1, FALSE),
('Festivos España', 'Días festivos nacionales', '#27ae60', 1, FALSE),
('Tomás González', 'Calendario de Tomás', '#3498db', 2, TRUE);