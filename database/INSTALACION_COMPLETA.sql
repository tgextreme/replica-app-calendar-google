-- ================================================
-- SCRIPT SQL COMPLETO - WEB CALENDAR
-- Base de datos: calendar
-- MySQL 9.1.0 - WAMP64
-- ================================================

-- Usar la base de datos 'calendar' existente
USE calendar;

-- Configurar modo SQL para MySQL 9.1.0
SET SESSION sql_mode = 'STRICT_TRANS_TABLES,NO_ZERO_DATE,NO_ZERO_IN_DATE,ERROR_FOR_DIVISION_BY_ZERO';

-- ================================================
-- ELIMINAR TABLAS EXISTENTES (si existen)
-- ================================================
SET FOREIGN_KEY_CHECKS = 0;

DROP TABLE IF EXISTS event_reminders;
DROP TABLE IF EXISTS event_participants;
DROP TABLE IF EXISTS events;
DROP TABLE IF EXISTS calendar_permissions;
DROP TABLE IF EXISTS calendars;
DROP TABLE IF EXISTS group_members;
DROP TABLE IF EXISTS user_groups;
DROP TABLE IF EXISTS user_sessions;
DROP TABLE IF EXISTS users;

SET FOREIGN_KEY_CHECKS = 1;

-- ================================================
-- CREAR ESTRUCTURA DE TABLAS
-- ================================================

-- Tabla de usuarios
CREATE TABLE users (
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- Tabla de grupos de usuarios
CREATE TABLE user_groups (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(100) NOT NULL,
    description TEXT,
    color VARCHAR(7) DEFAULT '#007bff',
    created_by INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    is_active BOOLEAN DEFAULT TRUE,
    FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- Tabla de membresía en grupos
CREATE TABLE group_members (
    id INT PRIMARY KEY AUTO_INCREMENT,
    group_id INT NOT NULL,
    user_id INT NOT NULL,
    role ENUM('admin', 'moderator', 'member') DEFAULT 'member',
    joined_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (group_id) REFERENCES user_groups(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    UNIQUE KEY unique_group_user (group_id, user_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- Tabla de calendarios
CREATE TABLE calendars (
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- Tabla de permisos de calendarios compartidos
CREATE TABLE calendar_permissions (
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
    CHECK ((user_id IS NOT NULL AND group_id IS NULL) OR (user_id IS NULL AND group_id IS NOT NULL))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- Tabla de eventos
CREATE TABLE events (
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- Tabla de participantes en eventos
CREATE TABLE event_participants (
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
    CHECK ((user_id IS NOT NULL AND group_id IS NULL) OR (user_id IS NULL AND group_id IS NOT NULL)),
    UNIQUE KEY unique_event_participant (event_id, user_id, group_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- Tabla de recordatorios
CREATE TABLE event_reminders (
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- Tabla de sesiones de usuario
CREATE TABLE user_sessions (
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- ================================================
-- INSERTAR DATOS DE EJEMPLO
-- ================================================

-- Usuarios de ejemplo (contraseña: password - hash MD5)
INSERT INTO users (username, email, password_hash, full_name, timezone) VALUES
('admin', 'admin@calendario.local', '5f4dcc3b5aa765d61d8327deb882cf99', 'Administrador', 'Europe/Madrid'),
('tomas', 'tomas@calendario.local', '5f4dcc3b5aa765d61d8327deb882cf99', 'Tomás González', 'Europe/Madrid'),
('jose', 'jose@calendario.local', '5f4dcc3b5aa765d61d8327deb882cf99', 'José', 'Europe/Madrid');

-- Grupos de usuarios
INSERT INTO user_groups (name, description, color, created_by) VALUES
('Cumpleaños', 'Eventos de cumpleaños y celebraciones', '#e74c3c', 1),
('Tasks', 'Tareas y recordatorios', '#f39c12', 1),
('Festivos en España', 'Días festivos y vacaciones', '#27ae60', 1),
('Tomás', 'Calendario personal de Tomás', '#3498db', 2);

-- Membresías en grupos
INSERT INTO group_members (group_id, user_id, role) VALUES
(1, 1, 'admin'),
(1, 2, 'member'),
(1, 3, 'member'),
(2, 1, 'admin'),
(2, 2, 'member'),
(3, 1, 'admin'),
(3, 2, 'member'),
(3, 3, 'member'),
(4, 2, 'admin');

-- Calendarios
INSERT INTO calendars (name, description, color, owner_id, is_default, timezone) VALUES
('Calendario Principal', 'Mi calendario personal', '#007bff', 1, TRUE, 'Europe/Madrid'),
('Cumpleaños', 'Eventos de cumpleaños', '#e74c3c', 1, FALSE, 'Europe/Madrid'),
('Tareas', 'Recordatorios y tareas', '#f39c12', 1, FALSE, 'Europe/Madrid'),
('Festivos España', 'Días festivos nacionales', '#27ae60', 1, FALSE, 'Europe/Madrid'),
('Tomás González', 'Calendario de Tomás', '#3498db', 2, TRUE, 'Europe/Madrid');

-- Permisos para compartir calendarios entre grupos
INSERT INTO calendar_permissions (calendar_id, group_id, permission_level, granted_by) VALUES
(2, 1, 'write', 1), -- Grupo Cumpleaños puede escribir en calendario Cumpleaños
(3, 2, 'write', 1), -- Grupo Tasks puede escribir en calendario Tareas
(4, 3, 'read', 1),  -- Grupo Festivos puede leer calendario Festivos
(5, 4, 'admin', 2); -- Grupo Tomás tiene acceso admin a su calendario

-- Eventos de ejemplo basados en tu imagen original
-- Eventos YT UN (11:00 - 12:00) de lunes a viernes
INSERT INTO events (calendar_id, title, description, start_datetime, end_datetime, created_by) VALUES
(1, 'YT UN', 'Reunión YouTube Universidad', '2025-09-29 11:00:00', '2025-09-29 12:00:00', 1),
(1, 'YT UN', 'Reunión YouTube Universidad', '2025-09-30 11:00:00', '2025-09-30 12:00:00', 1),
(1, 'YT UN', 'Reunión YouTube Universidad', '2025-10-01 11:00:00', '2025-10-01 12:00:00', 1),
(1, 'YT UN', 'Reunión YouTube Universidad', '2025-10-02 11:00:00', '2025-10-02 12:00:00', 1),
(1, 'YT UN', 'Reunión YouTube Universidad', '2025-10-03 11:00:00', '2025-10-03 12:00:00', 1);

-- Eventos Gym (12:30 - 13:30) de lunes a viernes
INSERT INTO events (calendar_id, title, description, start_datetime, end_datetime, created_by) VALUES
(1, 'Gym', 'Sesión de ejercicio', '2025-09-29 12:30:00', '2025-09-29 13:30:00', 1),
(1, 'Gym', 'Sesión de ejercicio', '2025-09-30 12:30:00', '2025-09-30 13:30:00', 1),
(1, 'Gym', 'Sesión de ejercicio', '2025-10-01 12:30:00', '2025-10-01 13:30:00', 1),
(1, 'Gym', 'Sesión de ejercicio', '2025-10-02 12:30:00', '2025-10-02 13:30:00', 1),
(1, 'Gym', 'Sesión de ejercicio', '2025-10-03 12:30:00', '2025-10-03 13:30:00', 1);

-- Eventos de José
INSERT INTO events (calendar_id, title, description, start_datetime, end_datetime, created_by) VALUES
(5, 'José', 'Reunión con José', '2025-09-30 15:00:00', '2025-09-30 17:00:00', 2),
(5, 'José', 'Reunión con José', '2025-10-01 15:30:00', '2025-10-01 17:00:00', 2);

-- Eventos futuros para octubre 2025
INSERT INTO events (calendar_id, title, description, start_datetime, end_datetime, created_by) VALUES
-- Semana del 6-10 octubre
(1, 'YT UN', 'Reunión YouTube Universidad', '2025-10-06 11:00:00', '2025-10-06 12:00:00', 1),
(1, 'YT UN', 'Reunión YouTube Universidad', '2025-10-07 11:00:00', '2025-10-07 12:00:00', 1),
(1, 'YT UN', 'Reunión YouTube Universidad', '2025-10-08 11:00:00', '2025-10-08 12:00:00', 1),
(1, 'YT UN', 'Reunión YouTube Universidad', '2025-10-09 11:00:00', '2025-10-09 12:00:00', 1),
(1, 'YT UN', 'Reunión YouTube Universidad', '2025-10-10 11:00:00', '2025-10-10 12:00:00', 1),

(1, 'Gym', 'Sesión de ejercicio', '2025-10-06 12:30:00', '2025-10-06 13:30:00', 1),
(1, 'Gym', 'Sesión de ejercicio', '2025-10-07 12:30:00', '2025-10-07 13:30:00', 1),
(1, 'Gym', 'Sesión de ejercicio', '2025-10-08 12:30:00', '2025-10-08 13:30:00', 1),
(1, 'Gym', 'Sesión de ejercicio', '2025-10-09 12:30:00', '2025-10-09 13:30:00', 1),
(1, 'Gym', 'Sesión de ejercicio', '2025-10-10 12:30:00', '2025-10-10 13:30:00', 1),

-- Eventos especiales
(2, 'Cumpleaños de María', 'Celebración cumpleaños', '2025-10-15 00:00:00', '2025-10-15 23:59:59', 1),
(3, 'Entrega de proyecto', 'Deadline importante', '2025-10-20 09:00:00', '2025-10-20 18:00:00', 1),
(4, 'Día de la Hispanidad', 'Fiesta nacional España', '2025-10-12 00:00:00', '2025-10-12 23:59:59', 1);

-- ================================================
-- VERIFICACIONES FINALES
-- ================================================

-- Mostrar información de instalación
SELECT 
    'INSTALACIÓN COMPLETADA EXITOSAMENTE' as status,
    COUNT(*) as total_usuarios 
FROM users;

SELECT 
    'TABLAS CREADAS:' as info,
    COUNT(*) as total_tablas
FROM information_schema.tables 
WHERE table_schema = 'calendar';

SELECT 
    'EVENTOS DE EJEMPLO:' as info,
    COUNT(*) as total_eventos
FROM events;

-- Mostrar usuarios creados
SELECT 
    'USUARIOS DISPONIBLES:' as info,
    CONCAT(full_name, ' (', email, ')') as usuario_email
FROM users;

-- ================================================
-- CONFIGURACIÓN FINAL
-- ================================================

-- Mensaje de confirmación
SELECT 
    '🎉 BASE DE DATOS CONFIGURADA CORRECTAMENTE' as mensaje,
    'Credenciales: admin@calendario.local / password' as login,
    'URL: http://localhost/calendar/public/' as aplicacion;