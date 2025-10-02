@echo off
echo ================================================
echo   Configuracion de Base de Datos - Web Calendar
echo   MySQL 9.1.0 - WAMP64
echo ================================================
echo.

set MYSQL_PATH=C:\wamp64\bin\mysql\mysql9.1.0\bin
set DB_NAME=calendar
set DB_USER=root
set PROJECT_PATH=C:\wamp64\www\calendar

echo Verificando MySQL...
"%MYSQL_PATH%\mysql" --version
if %errorlevel% neq 0 (
    echo ERROR: No se puede acceder a MySQL en la ruta especificada
    echo Verifica que WAMP este ejecutandose y la ruta sea correcta
    pause
    exit /b 1
)

echo.
echo MySQL encontrado correctamente!
echo.

echo ================================================
echo 1. Configurando la base de datos
echo ================================================

echo Creando base de datos y estructura...
"%MYSQL_PATH%\mysql" -u %DB_USER% -p < "%PROJECT_PATH%\database\mysql91_setup.sql"
if %errorlevel% neq 0 (
    echo ERROR: No se pudo configurar la base de datos inicial
    pause
    exit /b 1
)

echo Creando tablas...
"%MYSQL_PATH%\mysql" -u %DB_USER% -p %DB_NAME% < "%PROJECT_PATH%\database\schema.sql"
if %errorlevel% neq 0 (
    echo ERROR: No se pudieron crear las tablas
    pause
    exit /b 1
)

echo Insertando datos de ejemplo...
"%MYSQL_PATH%\mysql" -u %DB_USER% -p %DB_NAME% < "%PROJECT_PATH%\database\sample_data.sql"
if %errorlevel% neq 0 (
    echo ERROR: No se pudieron insertar los datos de ejemplo
    pause
    exit /b 1
)

echo.
echo ================================================
echo 2. Verificando instalacion
echo ================================================

echo Verificando tablas creadas...
"%MYSQL_PATH%\mysql" -u %DB_USER% -p %DB_NAME% -e "SHOW TABLES;"
if %errorlevel% neq 0 (
    echo ERROR: No se pudieron verificar las tablas
    pause
    exit /b 1
)

echo.
echo Verificando usuarios de ejemplo...
"%MYSQL_PATH%\mysql" -u %DB_USER% -p %DB_NAME% -e "SELECT id, username, email FROM users;"

echo.
echo ================================================
echo   INSTALACION COMPLETADA EXITOSAMENTE!
echo ================================================
echo.
echo Configuracion de la base de datos:
echo   Host: localhost
echo   Puerto: 3306
echo   Base de datos: %DB_NAME%
echo   Usuario: %DB_USER%
echo   Contrasena: (la que configuraste en WAMP)
echo.
echo URLs para acceder:
echo   Aplicacion: http://localhost/calendar/public/
echo   phpMyAdmin: http://localhost/phpmyadmin
echo.
echo Cuentas de prueba:
echo   Email: admin@calendario.local
echo   Password: password
echo.
echo   Email: tomas@calendario.local  
echo   Password: password
echo.
echo Presiona cualquier tecla para continuar...
pause > nul

echo.
echo ================================================
echo 3. Abriendo aplicacion en el navegador
echo ================================================

start http://localhost/calendar/public/

echo.
echo Instalacion completa! 
echo Si tienes problemas, revisa:
echo 1. Que WAMP este ejecutandose (icono verde)
echo 2. Que Apache y MySQL esten iniciados
echo 3. Los archivos de configuracion en config/config.php
echo.
pause