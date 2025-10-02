# Guía de Instalación - Web Calendar

## Instalación en WAMP (Windows)

### Prerrequisitos
- WAMP Server instalado y funcionando
- PHP 7.4 o superior
- MySQL 8.0 o superior

### Pasos detallados

1. **Copiar archivos**
   - Los archivos ya están en `c:\wamp64\www\calendar\`
   - Verificar que WAMP esté ejecutándose (ícono verde)

2. **Crear base de datos**
   ```sql
   -- Abrir phpMyAdmin: http://localhost/phpmyadmin
   -- O usar línea de comandos:
   
   mysql -u root -p
   CREATE DATABASE web_calendar CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
   USE web_calendar;
   SOURCE c:\wamp64\www\calendar\database\schema.sql;
   SOURCE c:\wamp64\www\calendar\database\sample_data.sql;
   ```

3. **Configurar conexión de base de datos**
   ```php
   // Editar: c:\wamp64\www\calendar\config\config.php
   
   'database' => [
       'host' => 'localhost',
       'port' => '3306',
       'dbname' => 'web_calendar',
       'username' => 'root',
       'password' => '', // Tu contraseña de MySQL
       'charset' => 'utf8mb4',
   ]
   ```

4. **Verificar configuración de PHP**
   - Abrir WAMP Manager
   - PHP > Configuración > Verificar extensiones activas:
     - ✅ pdo_mysql
     - ✅ mysqli
     - ✅ openssl
     - ✅ json

5. **Configurar Apache (opcional)**
   ```apache
   # Agregar a httpd.conf o crear VirtualHost
   
   <Directory "c:/wamp64/www/calendar">
       Options Indexes FollowSymLinks
       AllowOverride All
       Require local
   </Directory>
   ```

6. **Acceder a la aplicación**
   - URL: `http://localhost/calendar/public/`
   - Login con cuentas de prueba:
     - Email: `admin@calendario.local`
     - Password: `password`

### Solución de problemas comunes

**Error: "No se puede conectar a la base de datos"**
- Verificar que MySQL esté ejecutándose en WAMP
- Comprobar credenciales en `config/config.php`
- Verificar que la base de datos `web_calendar` existe

**Error: "Página no encontrada" en API**
- Verificar que mod_rewrite esté habilitado
- Comprobar archivo `.htaccess` en directorio raíz

**Error: "Cannot find FullCalendar"**
- Verificar conexión a internet (CDNs)
- O descargar bibliotecas localmente

---

## Instalación en XAMPP

### Pasos para XAMPP

1. **Copiar archivos**
   ```bash
   # Copiar a:
   C:\xampp\htdocs\calendar\
   ```

2. **Configurar base de datos**
   - Abrir XAMPP Control Panel
   - Iniciar Apache y MySQL
   - Abrir phpMyAdmin: `http://localhost/phpmyadmin`
   - Importar `database/schema.sql`
   - Importar `database/sample_data.sql`

3. **Configurar PHP**
   ```php
   // Editar: C:\xampp\htdocs\calendar\config\config.php
   
   'database' => [
       'host' => 'localhost',
       'username' => 'root',
       'password' => '', // Usualmente vacía en XAMPP
   ]
   ```

4. **Acceder**
   - URL: `http://localhost/calendar/public/`

---

## Instalación en Linux (Ubuntu/Debian)

### Instalar prerrequisitos
```bash
sudo apt update
sudo apt install apache2 mysql-server php php-mysql php-json php-pdo
sudo systemctl start apache2
sudo systemctl start mysql
```

### Configurar proyecto
```bash
# Clonar/copiar archivos
sudo cp -r calendar /var/www/html/

# Configurar permisos
sudo chown -R www-data:www-data /var/www/html/calendar
sudo chmod -R 755 /var/www/html/calendar

# Configurar Apache
sudo a2enmod rewrite
sudo systemctl reload apache2
```

### Configurar base de datos
```bash
sudo mysql -u root -p
CREATE DATABASE web_calendar;
CREATE USER 'calendar_user'@'localhost' IDENTIFIED BY 'secure_password';
GRANT ALL PRIVILEGES ON web_calendar.* TO 'calendar_user'@'localhost';
FLUSH PRIVILEGES;
```

---

## Configuración para Producción

### Seguridad básica

1. **Cambiar credenciales por defecto**
   ```php
   // config/config.php
   'security' => [
       'jwt_secret' => 'CAMBIAR_POR_CLAVE_SEGURA_ALEATORIA',
       'password_cost' => 12,
   ]
   ```

2. **Configurar HTTPS**
   ```apache
   # .htaccess - descomentar líneas:
   RewriteCond %{HTTPS} off
   RewriteRule ^(.*)$ https://%{HTTP_HOST}%{REQUEST_URI} [L,R=301]
   ```

3. **Configurar permisos de archivos**
   ```bash
   # Linux
   chmod 600 config/config.php
   chmod 644 public/*.html
   chmod -R 755 public/
   
   # Windows (usar propiedades de archivos)
   # Quitar permisos de lectura para usuarios no autorizados
   ```

4. **Configurar backup automático**
   ```bash
   # Crear script de backup
   #!/bin/bash
   mysqldump -u root -p web_calendar > backup_$(date +%Y%m%d).sql
   ```

### Optimización

1. **Habilitar compresión**
   - Ya configurada en `.htaccess`

2. **Configurar cache**
   - Archivos estáticos cachecados 1 mes

3. **Optimizar MySQL**
   ```sql
   -- Agregar índices si es necesario
   ALTER TABLE events ADD INDEX idx_user_date (created_by, start_datetime);
   ```

---

## Verificación de instalación

### Checklist post-instalación

- [ ] ✅ Servidor web funcionando
- [ ] ✅ Base de datos creada y poblada
- [ ] ✅ Página de login accesible
- [ ] ✅ Login con usuario de prueba funciona
- [ ] ✅ Calendario se carga correctamente
- [ ] ✅ Se pueden crear eventos
- [ ] ✅ API endpoints responden
- [ ] ✅ Sin errores en consola del navegador

### Comandos de verificación

```bash
# Verificar servicios
sudo systemctl status apache2
sudo systemctl status mysql

# Verificar logs
sudo tail -f /var/log/apache2/error.log

# Verificar conexión DB
mysql -u root -p web_calendar -e "SELECT COUNT(*) FROM users;"
```

### URLs de prueba

- **Frontend**: `http://localhost/calendar/public/`
- **API Test**: `http://localhost/calendar/api/auth/me`
- **phpMyAdmin**: `http://localhost/phpmyadmin`

Si todo funciona correctamente, ¡tu instalación está lista! 🎉