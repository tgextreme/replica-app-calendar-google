@echo off
echo ================================================
echo   Reiniciando servicios WAMP
echo ================================================
echo.

echo Deteniendo Apache...
net stop wampapache64
timeout /t 3 /nobreak > nul

echo Iniciando Apache...
net start wampapache64
timeout /t 3 /nobreak > nul

echo.
echo ================================================
echo Servicios reiniciados!
echo ================================================
echo.
echo Probando la aplicacion...
start http://localhost/calendar/public/

echo.
echo Si aun hay errores, prueba:
echo http://localhost/calendar/public/simple_test.php
echo.
pause