# Instalación Específica - MySQL 9.1.0 en WAMP64

## Tu configuración detectada:
- **MySQL**: Versión 9.1.0
- **Ruta**: `C:\wamp64\bin\mysql\mysql9.1.0\bin`
- **WAMP**: 64 bits
- **Sistema**: Windows

## 🚀 Instalación Rápida (Método Automatizado)

### Opción 1: Script Automatizado
1. **Ejecutar el script de instalación:**
   ```cmd
   cd C:\wamp64\www\calendar
   setup_mysql91.bat
   ```
   
2. **Seguir las instrucciones** en pantalla
3. **Acceder a la aplicación:** `http://localhost/calendar/public/`

---

## 🛠️ Instalación Manual (Método Paso a Paso)

### Paso 1: Verificar WAMP
- Asegúrate de que WAMP esté ejecutándose (ícono verde en la bandeja del sistema)
- Servicios activos: Apache y MySQL

### Paso 2: Configurar Base de Datos

#### Opción A: Usando phpMyAdmin
1. **Abrir phpMyAdmin:** `http://localhost/phpmyadmin`
2. **Importar archivos SQL en este orden:**
   - `database/mysql91_setup.sql` (configuración inicial)
   - `database/schema.sql` (estructura de tablas)
   - `database/sample_data.sql` (datos de ejemplo)

#### Opción B: Línea de comandos
```cmd
# Abrir Command Prompt como Administrador
cd C:\wamp64\www\calendar

# Configuración inicial
C:\wamp64\bin\mysql\mysql9.1.0\bin\mysql -u root -p < database\mysql91_setup.sql

# Crear estructura
C:\wamp64\bin\mysql\mysql9.1.0\bin\mysql -u root -p web_calendar < database\schema.sql

# Insertar datos de ejemplo
C:\wamp64\bin\mysql\mysql9.1.0\bin\mysql -u root -p web_calendar < database\sample_data.sql
```

### Paso 3: Verificar Configuración
La configuración en `config/config.php` ya está optimizada para tu setup:

```php
'database' => [
    'host' => 'localhost',
    'port' => '3306',
    'dbname' => 'web_calendar',
    'username' => 'root',
    'password' => '', // Cambiar si tienes contraseña configurada
    'charset' => 'utf8mb4',
]
```

### Paso 4: Verificar Instalación

#### Verificar servicios:
- **WAMP Manager**: Ícono verde en bandeja del sistema
- **Apache**: Puerto 80 libre
- **MySQL**: Puerto 3306 activo

#### Verificar base de datos:
```sql
-- En phpMyAdmin, ejecutar:
USE web_calendar;
SHOW TABLES;
SELECT COUNT(*) FROM users;
```

#### Verificar aplicación:
- **Frontend**: `http://localhost/calendar/public/`
- **API Test**: `http://localhost/calendar/api/auth/me`

---

## 🔧 Configuraciones Específicas para MySQL 9.1.0

### Características aprovechadas:
- **Collation mejorada**: `utf8mb4_0900_ai_ci`
- **Modo SQL optimizado**: Compatible con funciones modernas
- **Soporte JSON**: Para eventos complejos (futuras mejoras)

### Configuraciones de rendimiento:
```sql
-- Configuraciones recomendadas para MySQL 9.1.0
SET GLOBAL innodb_buffer_pool_size = 128M;
SET GLOBAL max_connections = 100;
SET GLOBAL query_cache_size = 16M;
```

---

## 🚨 Solución de Problemas Comunes

### Error: "Access denied for user 'root'"
```cmd
# Resetear contraseña de MySQL en WAMP:
1. Parar MySQL en WAMP Manager
2. Hacer clic derecho en ícono WAMP > MySQL > Resetear contraseña root
3. Reiniciar MySQL
```

### Error: "Can't connect to MySQL server"
```cmd
# Verificar que MySQL esté corriendo:
netstat -an | findstr :3306

# Si no aparece, iniciar MySQL desde WAMP Manager
```

### Error: "Table doesn't exist"
```cmd
# Verificar que las tablas se crearon:
C:\wamp64\bin\mysql\mysql9.1.0\bin\mysql -u root -p web_calendar -e "SHOW TABLES;"
```

### Error: "Permission denied" en archivos
```cmd
# Verificar permisos de carpeta:
# Clic derecho en C:\wamp64\www\calendar
# Propiedades > Seguridad > Asegurar permisos de lectura/escritura
```

---

## ✅ Verificación Final

### Checklist de instalación:
- [ ] WAMP ejecutándose (ícono verde)
- [ ] MySQL 9.1.0 activo en puerto 3306
- [ ] Base de datos `web_calendar` creada
- [ ] 8 tablas creadas correctamente
- [ ] 3 usuarios de ejemplo insertados
- [ ] Página de login carga sin errores
- [ ] Login con `admin@calendario.local` / `password` funciona
- [ ] Calendario principal se muestra correctamente

### Comandos de verificación:
```cmd
# Verificar versión MySQL
C:\wamp64\bin\mysql\mysql9.1.0\bin\mysql --version

# Verificar conexión
C:\wamp64\bin\mysql\mysql9.1.0\bin\mysql -u root -p -e "SELECT 'Conexión exitosa' as status;"

# Verificar datos
C:\wamp64\bin\mysql\mysql9.1.0\bin\mysql -u root -p web_calendar -e "SELECT username, email FROM users;"
```

---

## 🎯 Próximos Pasos

Una vez instalado:

1. **Cambiar contraseñas por defecto**
2. **Personalizar colores y nombres de grupos**
3. **Agregar más usuarios** desde la página de registro
4. **Crear eventos de prueba**
5. **Explorar funcionalidades de compartir calendarios**

### Cuentas de prueba incluidas:
```
Admin: admin@calendario.local / password
Tomás: tomas@calendario.local / password  
José: jose@calendario.local / password
```

---

¡Tu calendario web está listo para usar con MySQL 9.1.0! 🎉