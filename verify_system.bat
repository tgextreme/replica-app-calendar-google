@echo off
echo ================================================
echo   Verificacion del Sistema - Web Calendar
echo   MySQL 9.1.0 - WAMP64
echo ================================================
echo.

set MYSQL_PATH=C:\wamp64\bin\mysql\mysql9.1.0\bin
set DB_NAME=web_calendar
set DB_USER=root

echo Realizando verificaciones del sistema...
echo.

echo ================================================
echo 1. Verificando MySQL 9.1.0
echo ================================================

"%MYSQL_PATH%\mysql" --version
if %errorlevel% neq 0 (
    echo [ERROR] MySQL no accesible en la ruta especificada
    echo Verifica que WAMP este ejecutandose
    goto :error
) else (
    echo [OK] MySQL 9.1.0 accesible
)

echo.
echo ================================================
echo 2. Verificando conexion a base de datos
echo ================================================

"%MYSQL_PATH%\mysql" -u %DB_USER% -p -e "SELECT 'Conexion exitosa' as status;" 2>nul
if %errorlevel% neq 0 (
    echo [ERROR] No se puede conectar a MySQL
    echo Verifica usuario y contrasena
    goto :error
) else (
    echo [OK] Conexion a MySQL exitosa
)

echo.
echo ================================================
echo 3. Verificando base de datos web_calendar
echo ================================================

"%MYSQL_PATH%\mysql" -u %DB_USER% -p -e "USE %DB_NAME%; SELECT 'Base de datos OK' as status;" 2>nul
if %errorlevel% neq 0 (
    echo [ERROR] Base de datos web_calendar no existe
    echo Ejecuta primero setup_mysql91.bat
    goto :error
) else (
    echo [OK] Base de datos web_calendar existe
)

echo.
echo ================================================
echo 4. Verificando estructura de tablas
echo ================================================

for %%t in (users user_groups calendars events calendar_permissions group_members event_participants user_sessions) do (
    "%MYSQL_PATH%\mysql" -u %DB_USER% -p %DB_NAME% -e "DESCRIBE %%t;" >nul 2>nul
    if !errorlevel! neq 0 (
        echo [ERROR] Tabla %%t no existe
        set table_error=1
    ) else (
        echo [OK] Tabla %%t verificada
    )
)

if defined table_error goto :error

echo.
echo ================================================
echo 5. Verificando datos de ejemplo
echo ================================================

"%MYSQL_PATH%\mysql" -u %DB_USER% -p %DB_NAME% -e "SELECT COUNT(*) as total_users FROM users;" 2>nul | findstr /c:"3" >nul
if %errorlevel% neq 0 (
    echo [WARNING] Datos de ejemplo pueden no estar cargados correctamente
) else (
    echo [OK] Usuarios de ejemplo encontrados
)

echo.
echo ================================================
echo 6. Verificando archivos del proyecto
echo ================================================

set files_ok=1
if not exist "C:\wamp64\www\calendar\config\config.php" (
    echo [ERROR] config.php no encontrado
    set files_ok=0
)
if not exist "C:\wamp64\www\calendar\public\index.html" (
    echo [ERROR] index.html no encontrado
    set files_ok=0
)
if not exist "C:\wamp64\www\calendar\api\index.php" (
    echo [ERROR] API index.php no encontrado
    set files_ok=0
)

if %files_ok%==1 (
    echo [OK] Archivos del proyecto verificados
) else (
    goto :error
)

echo.
echo ================================================
echo 7. Verificando servicios WAMP
echo ================================================

netstat -an | findstr ":80 " >nul
if %errorlevel% neq 0 (
    echo [ERROR] Apache no esta ejecutandose en puerto 80
    set services_error=1
) else (
    echo [OK] Apache ejecutandose en puerto 80
)

netstat -an | findstr ":3306 " >nul
if %errorlevel% neq 0 (
    echo [ERROR] MySQL no esta ejecutandose en puerto 3306
    set services_error=1
) else (
    echo [OK] MySQL ejecutandose en puerto 3306
)

if defined services_error goto :error

echo.
echo ================================================
echo 8. Test de conectividad HTTP
echo ================================================

echo Probando acceso a la aplicacion...
curl -s -o nul -w "%%{http_code}" http://localhost/calendar/public/ | findstr "200" >nul
if %errorlevel% neq 0 (
    echo [WARNING] La aplicacion puede no ser accesible via HTTP
    echo Verifica que WAMP este ejecutandose correctamente
) else (
    echo [OK] Aplicacion accesible via HTTP
)

echo.
echo ================================================
echo   VERIFICACION COMPLETADA EXITOSAMENTE!
echo ================================================
echo.
echo Tu sistema esta configurado correctamente para Web Calendar
echo.
echo Informacion del sistema:
echo   MySQL: 9.1.0
echo   Ruta MySQL: %MYSQL_PATH%
echo   Base de datos: %DB_NAME%
echo   Usuario DB: %DB_USER%
echo.
echo URLs de acceso:
echo   Aplicacion: http://localhost/calendar/public/
echo   API: http://localhost/calendar/api/
echo   phpMyAdmin: http://localhost/phpmyadmin
echo.
echo Cuentas de prueba:
"%MYSQL_PATH%\mysql" -u %DB_USER% -p %DB_NAME% -e "SELECT CONCAT('   Email: ', email, ' / Password: password') as 'Cuentas disponibles' FROM users;"
echo.
echo Presiona cualquier tecla para abrir la aplicacion...
pause >nul

start http://localhost/calendar/public/
goto :end

:error
echo.
echo ================================================
echo   ERROR EN LA VERIFICACION
echo ================================================
echo.
echo Se encontraron errores en la configuracion.
echo.
echo Soluciones recomendadas:
echo 1. Verificar que WAMP este ejecutandose (icono verde)
echo 2. Ejecutar setup_mysql91.bat para configurar la DB
echo 3. Verificar permisos de archivos y carpetas
echo 4. Consultar INSTALL_MYSQL91.md para mas detalles
echo.
echo Presiona cualquier tecla para salir...
pause >nul
exit /b 1

:end
echo.
echo Sistema verificado y funcional!
exit /b 0