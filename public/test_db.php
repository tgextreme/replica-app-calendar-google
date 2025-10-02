<?php
// Test de conexión a base de datos
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h2>🔍 Test de Base de Datos - Web Calendar</h2>";
echo "<hr>";

// Test 1: Verificar archivos de configuración
echo "<h3>1. Verificación de archivos</h3>";
$configFile = __DIR__ . '/../config/config.php';
$dbFile = __DIR__ . '/../config/Database.php';

if (file_exists($configFile)) {
    echo "✅ config.php existe<br>";
} else {
    echo "❌ config.php NO existe en: " . $configFile . "<br>";
}

if (file_exists($dbFile)) {
    echo "✅ Database.php existe<br>";
} else {
    echo "❌ Database.php NO existe en: " . $dbFile . "<br>";
}

// Test 2: Cargar configuración
echo "<h3>2. Configuración</h3>";
try {
    $config = require $configFile;
    echo "✅ Configuración cargada correctamente<br>";
    echo "Host: " . $config['database']['host'] . "<br>";
    echo "Puerto: " . $config['database']['port'] . "<br>";
    echo "Base de datos: " . $config['database']['dbname'] . "<br>";
    echo "Usuario: " . $config['database']['username'] . "<br>";
} catch (Exception $e) {
    echo "❌ Error al cargar configuración: " . $e->getMessage() . "<br>";
    exit;
}

// Test 3: Extensiones PHP
echo "<h3>3. Extensiones PHP</h3>";
if (extension_loaded('pdo')) {
    echo "✅ PDO está disponible<br>";
} else {
    echo "❌ PDO NO está disponible<br>";
}

if (extension_loaded('pdo_mysql')) {
    echo "✅ PDO MySQL está disponible<br>";
} else {
    echo "❌ PDO MySQL NO está disponible<br>";
}

// Test 4: Conexión directa
echo "<h3>4. Test de conexión directa</h3>";
try {
    $dsn = sprintf(
        "mysql:host=%s;port=%s;charset=utf8mb4",
        $config['database']['host'],
        $config['database']['port']
    );
    
    $pdo = new PDO(
        $dsn,
        $config['database']['username'],
        $config['database']['password'],
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );
    
    echo "✅ Conexión a MySQL exitosa<br>";
    
    // Verificar si existe la base de datos
    $stmt = $pdo->query("SHOW DATABASES LIKE 'calendar'");
    if ($stmt->rowCount() > 0) {
        echo "✅ Base de datos 'calendar' existe<br>";
        
        // Conectar a la base de datos específica
        $dsn_with_db = sprintf(
            "mysql:host=%s;port=%s;dbname=%s;charset=utf8mb4",
            $config['database']['host'],
            $config['database']['port'],
            $config['database']['dbname']
        );
        
        $pdo_db = new PDO(
            $dsn_with_db,
            $config['database']['username'],
            $config['database']['password'],
            [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
        );
        
        echo "✅ Conexión a base de datos 'calendar' exitosa<br>";
        
        // Verificar tablas
        $stmt = $pdo_db->query("SHOW TABLES");
        $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);
        
        if (count($tables) > 0) {
            echo "✅ Tablas encontradas (" . count($tables) . "): " . implode(', ', $tables) . "<br>";
            
            // Verificar usuarios
            if (in_array('users', $tables)) {
                $stmt = $pdo_db->query("SELECT COUNT(*) as total FROM users");
                $result = $stmt->fetch();
                echo "✅ Usuarios en la base de datos: " . $result['total'] . "<br>";
                
                if ($result['total'] > 0) {
                    $stmt = $pdo_db->query("SELECT username, email FROM users LIMIT 3");
                    $users = $stmt->fetchAll();
                    echo "Usuarios disponibles:<br>";
                    foreach ($users as $user) {
                        echo "  - " . $user['username'] . " (" . $user['email'] . ")<br>";
                    }
                }
            }
        } else {
            echo "❌ No se encontraron tablas en la base de datos 'calendar'<br>";
            echo "<strong>ACCIÓN REQUERIDA:</strong> Ejecuta el archivo INSTALACION_COMPLETA.sql en phpMyAdmin<br>";
        }
        
    } else {
        echo "❌ Base de datos 'calendar' NO existe<br>";
        echo "<strong>ACCIÓN REQUERIDA:</strong> Crea la base de datos 'calendar' y ejecuta INSTALACION_COMPLETA.sql<br>";
    }
    
} catch (Exception $e) {
    echo "❌ Error de conexión: " . $e->getMessage() . "<br>";
    echo "<strong>Posibles causas:</strong><br>";
    echo "- WAMP no está ejecutándose<br>";
    echo "- MySQL no está iniciado<br>";
    echo "- Credenciales incorrectas<br>";
    echo "- Puerto incorrecto<br>";
}

// Test 5: Test de clase Database
echo "<h3>5. Test de clase Database</h3>";
try {
    require_once $dbFile;
    $db = Database::getInstance();
    echo "✅ Clase Database cargada correctamente<br>";
    
    $connection = $db->getConnection();
    if ($connection) {
        echo "✅ Conexión mediante clase Database exitosa<br>";
    }
} catch (Exception $e) {
    echo "❌ Error con clase Database: " . $e->getMessage() . "<br>";
}

echo "<hr>";
echo "<h3>📋 Resumen</h3>";
echo "Si ves errores arriba, sigue estos pasos:<br>";
echo "1. Asegúrate de que WAMP esté ejecutándose (icono verde)<br>";
echo "2. Ve a phpMyAdmin: <a href='http://localhost/phpmyadmin' target='_blank'>http://localhost/phpmyadmin</a><br>";
echo "3. Ejecuta el archivo INSTALACION_COMPLETA.sql que creé anteriormente<br>";
echo "4. Vuelve a probar: <a href='http://localhost/calendar/public/test_db.php'>Refresh esta página</a><br>";
echo "<br>";
echo "<strong>Una vez que todo esté ✅, ve a:</strong><br>";
echo "<a href='http://localhost/calendar/public/' target='_blank'>🚀 Aplicación Calendar</a>";
?>