<?php
// Archivo de diagnóstico para detectar problemas
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h2>Diagnóstico del Sistema - Web Calendar</h2>";

// 1. Verificar PHP
echo "<h3>1. Información de PHP</h3>";
echo "Versión PHP: " . phpversion() . "<br>";
echo "Directorio actual: " . __DIR__ . "<br>";

// 2. Verificar extensiones PHP necesarias
echo "<h3>2. Extensiones PHP</h3>";
$required_extensions = ['pdo', 'pdo_mysql', 'json', 'session'];
foreach ($required_extensions as $ext) {
    $status = extension_loaded($ext) ? "✅ OK" : "❌ FALTA";
    echo "- {$ext}: {$status}<br>";
}

// 3. Verificar archivos del proyecto
echo "<h3>3. Archivos del Proyecto</h3>";
$required_files = [
    '../config/config.php',
    '../config/Database.php',
    '../app/models/User.php',
    '../app/controllers/AuthController.php',
    '../api/index.php'
];

foreach ($required_files as $file) {
    $exists = file_exists($file) ? "✅ Existe" : "❌ No existe";
    echo "- {$file}: {$exists}<br>";
}

// 4. Verificar permisos de archivos
echo "<h3>4. Permisos de Archivos</h3>";
$check_permissions = [
    '../config/',
    '../api/',
    './'
];

foreach ($check_permissions as $dir) {
    $readable = is_readable($dir) ? "✅" : "❌";
    $writable = is_writable($dir) ? "✅" : "❌";
    echo "- {$dir}: Lectura {$readable} | Escritura {$writable}<br>";
}

// 5. Verificar conexión a base de datos
echo "<h3>5. Conexión a Base de Datos</h3>";
try {
    if (file_exists('../config/config.php')) {
        $config = require '../config/config.php';
        $dbConfig = $config['database'];
        
        $dsn = "mysql:host={$dbConfig['host']};port={$dbConfig['port']};dbname={$dbConfig['dbname']};charset={$dbConfig['charset']}";
        $pdo = new PDO($dsn, $dbConfig['username'], $dbConfig['password'], $dbConfig['options']);
        
        echo "✅ Conexión a MySQL exitosa<br>";
        echo "- Host: {$dbConfig['host']}<br>";
        echo "- Puerto: {$dbConfig['port']}<br>";
        echo "- Base de datos: {$dbConfig['dbname']}<br>";
        
        // Verificar tablas
        $stmt = $pdo->query("SHOW TABLES");
        $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);
        echo "- Tablas encontradas: " . count($tables) . "<br>";
        if (count($tables) > 0) {
            echo "- Lista: " . implode(', ', $tables) . "<br>";
        }
        
    } else {
        echo "❌ Archivo config.php no encontrado<br>";
    }
} catch (Exception $e) {
    echo "❌ Error de conexión: " . $e->getMessage() . "<br>";
}

// 6. Verificar configuración Apache
echo "<h3>6. Configuración del Servidor</h3>";
echo "- Servidor: " . $_SERVER['SERVER_SOFTWARE'] . "<br>";
echo "- Document Root: " . $_SERVER['DOCUMENT_ROOT'] . "<br>";
echo "- Request URI: " . $_SERVER['REQUEST_URI'] . "<br>";

// 7. Test de includes
echo "<h3>7. Test de Includes</h3>";
try {
    if (file_exists('../config/Database.php')) {
        require_once '../config/Database.php';
        echo "✅ Database.php cargado correctamente<br>";
    } else {
        echo "❌ Database.php no encontrado<br>";
    }
} catch (Exception $e) {
    echo "❌ Error al cargar Database.php: " . $e->getMessage() . "<br>";
}

echo "<h3>8. Información de Errores de Apache</h3>";
echo "Si sigues viendo errores, revisa el log de Apache en:<br>";
echo "C:\\wamp64\\logs\\apache_error.log<br>";

echo "<hr>";
echo "<h3>Siguientes pasos:</h3>";
echo "1. Ejecuta el SQL en phpMyAdmin: <a href='../database/setup_calendar_db.sql'>setup_calendar_db.sql</a><br>";
echo "2. Si todo está OK, accede a: <a href='index.html'>index.html</a><br>";
echo "3. Si hay errores, revisa el log de Apache<br>";
?>