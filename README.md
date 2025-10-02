# Web Calendar - Aplicación de Calendario Web

Una aplicación web completa de calendario similar a Google Calendar, desarrollada con PHP, MySQL y JavaScript.

## Características

- 📅 **Interfaz moderna** similar a Google Calendar
- 👥 **Calendarios compartidos** entre usuarios y grupos
- 🔐 **Sistema de autenticación** y permisos granulares
- 📱 **Responsive design** compatible con móviles
- 🎨 **Personalización** de colores por calendario/grupo
- 📊 **Vistas múltiples** (mes, semana, día)
- ✏️ **Drag & Drop** para mover eventos
- 🔔 **Eventos recurrentes** y recordatorios
- 🌍 **Soporte de timezone**

## Tecnologías

### Backend
- **PHP 7.4+** - Lógica del servidor
- **MySQL 8.0+** - Base de datos
- **PDO** - Acceso a datos
- **REST API** - Comunicación frontend/backend

### Frontend
- **FullCalendar.js 6.1** - Componente principal del calendario
- **Bootstrap 5.3** - Framework CSS
- **Axios** - Cliente HTTP
- **Vanilla JavaScript** - Lógica del cliente

## Instalación

### Requisitos
- PHP 7.4 o superior
- MySQL 8.0 o superior
- Apache/Nginx con mod_rewrite
- Composer (opcional)

### Pasos de instalación

1. **Clonar el proyecto**
   ```bash
   git clone <repository-url>
   cd calendar
   ```

2. **Configurar la base de datos**
   ```bash
   # Crear la base de datos
   mysql -u root -p < database/schema.sql
   
   # Insertar datos de ejemplo (opcional)
   mysql -u root -p < database/sample_data.sql
   ```

3. **Configurar conexión a base de datos**
   ```php
   // Editar config/config.php
   'database' => [
       'host' => 'localhost',
       'dbname' => 'web_calendar',
       'username' => 'tu_usuario',
       'password' => 'tu_contraseña'
   ]
   ```

4. **Configurar servidor web**
   
   **Apache:**
   ```apache
   <VirtualHost *:80>
       ServerName calendar.local
       DocumentRoot /ruta/al/proyecto/public
       DirectoryIndex index.html
       
       <Directory "/ruta/al/proyecto/public">
           AllowOverride All
           Require all granted
       </Directory>
   </VirtualHost>
   ```
   
   **Nginx:**
   ```nginx
   server {
       listen 80;
       server_name calendar.local;
       root /ruta/al/proyecto/public;
       index index.html;
       
       location / {
           try_files $uri $uri/ /index.html;
       }
       
       location /api/ {
           try_files $uri /api/index.php?$query_string;
       }
       
       location ~ \.php$ {
           fastcgi_pass 127.0.0.1:9000;
           fastcgi_index index.php;
           include fastcgi_params;
       }
   }
   ```

5. **Instalar dependencias (opcional)**
   ```bash
   composer install
   npm install
   ```

## Uso

### Acceder a la aplicación
- Abrir navegador en `http://localhost/calendar` (WAMP/XAMPP)
- O `http://calendar.local` (configuración personalizada)

### Cuentas de prueba
```
Email: admin@calendario.local
Contraseña: password

Email: tomas@calendario.local  
Contraseña: password

Email: jose@calendario.local
Contraseña: password
```

### Funcionalidades principales

1. **Crear eventos**
   - Click en "+ Crear" o en una fecha del calendario
   - Completar formulario (título, fechas, descripción, etc.)
   - Seleccionar calendario destino

2. **Compartir calendarios**
   - Acceder a configuración del calendario
   - Agregar usuarios o grupos con permisos específicos
   - Niveles: lectura, escritura, administrador

3. **Gestionar grupos**
   - Crear grupos de usuarios
   - Invitar miembros por email
   - Asignar roles (admin, moderador, miembro)

4. **Vistas del calendario**
   - **Mes**: Vista general mensual
   - **Semana**: Detalle semanal con horas
   - **Día**: Vista detallada diaria

## Estructura del Proyecto

```
calendar/
├── api/                    # API REST endpoints
│   └── index.php          # Router principal
├── app/                   # Lógica de aplicación
│   ├── controllers/       # Controladores MVC
│   ├── models/           # Modelos de datos
│   └── views/            # Vistas (no usado en esta implementación)
├── config/               # Configuración
│   ├── config.php        # Configuración principal
│   └── Database.php      # Clase de conexión DB
├── database/             # Scripts de base de datos
│   ├── schema.sql        # Estructura de tablas
│   └── sample_data.sql   # Datos de ejemplo
├── public/               # Archivos públicos
│   ├── css/             # Estilos CSS
│   ├── js/              # JavaScript
│   ├── index.html       # Página de login
│   ├── calendar.html    # Aplicación principal
│   └── register.html    # Página de registro
├── .htaccess            # Configuración Apache
├── composer.json        # Dependencias PHP
├── package.json         # Dependencias JS
└── README.md           # Este archivo
```

## API Endpoints

### Autenticación
- `POST /api/auth/login` - Iniciar sesión
- `POST /api/auth/register` - Registrar usuario
- `POST /api/auth/logout` - Cerrar sesión
- `GET /api/auth/me` - Información del usuario actual

### Eventos
- `GET /api/events` - Listar eventos
- `POST /api/events` - Crear evento
- `GET /api/events/{id}` - Obtener evento
- `PUT /api/events/{id}` - Actualizar evento
- `DELETE /api/events/{id}` - Eliminar evento

### Calendarios
- `GET /api/calendars` - Listar calendarios
- `POST /api/calendars` - Crear calendario
- `GET /api/calendars/{id}` - Obtener calendario
- `PUT /api/calendars/{id}` - Actualizar calendario
- `DELETE /api/calendars/{id}` - Eliminar calendario
- `POST /api/calendars/{id}/share/user` - Compartir con usuario
- `POST /api/calendars/{id}/share/group` - Compartir con grupo

### Grupos
- `GET /api/groups` - Listar grupos del usuario
- `POST /api/groups` - Crear grupo
- `GET /api/groups/search` - Buscar grupos
- `GET /api/groups/{id}` - Obtener grupo
- `PUT /api/groups/{id}` - Actualizar grupo
- `DELETE /api/groups/{id}` - Eliminar grupo
- `POST /api/groups/{id}/members` - Agregar miembro
- `DELETE /api/groups/{id}/members/{userId}` - Remover miembro

## Personalización

### Cambiar colores de tema
```css
/* En public/css/calendar.css */
.btn-primary {
    background-color: #tu-color;
}
```

### Agregar nuevos campos a eventos
1. Modificar tabla `events` en la base de datos
2. Actualizar modelo `Event.php`
3. Agregar campos al formulario en `calendar.html`

### Configurar notificaciones por email
1. Instalar librería de email (ej: PHPMailer)
2. Configurar SMTP en `config/config.php`
3. Implementar sistema de recordatorios

## Seguridad

### En producción
- Cambiar claves secretas en `config/config.php`
- Configurar HTTPS
- Restringir acceso a archivos de configuración
- Validar y sanitizar todas las entradas
- Implementar rate limiting

### Permisos de archivos
```bash
chmod 755 public/
chmod 644 public/*.html
chmod 644 public/css/*.css
chmod 644 public/js/*.js
chmod 600 config/config.php
```

## Desarrollo

### Agregar nuevas funcionalidades
1. Crear modelo en `app/models/`
2. Crear controlador en `app/controllers/`
3. Agregar rutas en `api/index.php`
4. Implementar frontend en JavaScript

### Debugging
- Activar modo debug en `config/config.php`
- Revisar logs de Apache/Nginx
- Usar herramientas de desarrollo del navegador

## Licencia

MIT License - Ver archivo LICENSE para más detalles.

## Soporte

Para reportar bugs o solicitar funcionalidades:
1. Crear issue en el repositorio
2. Incluir pasos para reproducir el problema
3. Especificar versión de PHP/MySQL/navegador

## Contribuir

1. Fork del proyecto
2. Crear rama para nueva funcionalidad
3. Commit de cambios
4. Push a la rama
5. Abrir Pull Request#   r e p l i c a - a p p - c a l e n d a r - g o o g l e  
 