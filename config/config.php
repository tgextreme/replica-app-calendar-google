<?php
return [
    'database' => [
        'host' => 'localhost',
        'port' => '3306',
        'dbname' => 'calendar',
        'username' => 'root', // Usuario por defecto de WAMP
        'password' => '',     // Contraseña por defecto de WAMP (vacía)
        'charset' => 'utf8mb4',
        'options' => [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
            PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4",
            PDO::ATTR_TIMEOUT => 30,
        ]
    ],
    'app' => [
        'name' => 'Web Calendar',
        'version' => '1.0.0',
        'timezone' => 'Europe/Madrid',
        'debug' => true, // Cambiar a false en producción
        'base_url' => 'http://localhost/calendar',
        'session_lifetime' => 3600, // 1 hora
    ],
    'security' => [
        'jwt_secret' => 'tu_clave_secreta_muy_segura_aqui_cambiar_en_produccion',
        'session_name' => 'calendar_session',
    ]
];