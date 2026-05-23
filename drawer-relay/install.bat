@echo off
echo Setting up American Select Drawer Relay...

:: Create hidden launcher in Windows Startup folder
set STARTUP=%APPDATA%\Microsoft\Windows\Start Menu\Programs\Startup
set VBSFILE=%STARTUP%\AS-DrawerRelay.vbs

(
echo Set WshShell = CreateObject^("WScript.Shell"^)
echo WshShell.Run "node ""C:\AmericanSelect\drawer-relay\relay.cjs""", 0, False
) > "%VBSFILE%"

echo Installed to startup folder.

:: Start it right now
echo Starting relay...
cscript //nologo "%VBSFILE%"
timeout /t 3 /nobreak >nul

:: Verify
curl -s http://localhost:3099/status
echo.
echo Done! Relay is running and will auto-start on every login.
pause
