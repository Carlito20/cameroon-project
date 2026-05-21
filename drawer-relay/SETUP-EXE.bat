@echo off
echo ============================================
echo  American Select - Cash Drawer Relay Setup
echo  (Standalone - no Node.js required)
echo ============================================
echo.
echo Adding DrawerRelay to Windows startup...
set EXE_PATH=%~dp0dist\DrawerRelay.exe
set STARTUP=%APPDATA%\Microsoft\Windows\Start Menu\Programs\Startup
powershell -Command "$s=(New-Object -COM WScript.Shell).CreateShortcut('%STARTUP%\DrawerRelay.lnk');$s.TargetPath='%EXE_PATH%';$s.WindowStyle=7;$s.Save()"
echo.
echo Starting relay now...
start "Cash Drawer Relay" /MIN "%EXE_PATH%"
echo.
echo ============================================
echo  Done! DrawerRelay will now auto-start
echo  every time Windows boots.
echo ============================================
pause
