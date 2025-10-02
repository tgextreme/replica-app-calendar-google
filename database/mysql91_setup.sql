-- Script de configuración para MySQL 9.1.0
-- Ejecutar desde línea de comandos o phpMyAdmin

-- 1. Crear la base de datos con configuración optimizada para MySQL 9.1.0
CREATE DATABASE IF NOT EXISTS web_calendar 
CHARACTER SET utf8mb4 
COLLATE utf8mb4_0900_ai_ci;

USE web_calendar;

-- 2. Configurar el modo SQL para compatibilidad
SET SESSION sql_mode = 'STRICT_TRANS_TABLES,NO_ZERO_DATE,NO_ZERO_IN_DATE,ERROR_FOR_DIVISION_BY_ZERO';

-- 3. Crear usuario específico para la aplicación (recomendado para producción)
-- NOTA: Descomenta estas líneas si quieres crear un usuario dedicado
-- CREATE USER IF NOT EXISTS 'calendar_user'@'localhost' IDENTIFIED BY 'calendar_pass_2025';
-- GRANT ALL PRIVILEGES ON web_calendar.* TO 'calendar_user'@'localhost';
-- FLUSH PRIVILEGES;

-- 4. Verificar configuración
SELECT @@version as mysql_version;
SELECT @@character_set_database, @@collation_database;

-- 5. Mostrar información de conexión
SELECT 
    'CONFIGURACIÓN PARA config.php:' as info,
    'host: localhost' as host,
    'port: 3306' as port,
    'username: root (o calendar_user si creaste usuario)' as username,
    'password: (vacía por defecto en WAMP)' as password,
    'dbname: web_calendar' as database_name;