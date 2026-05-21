@echo off
echo ============================================
echo  American Select - Cash Drawer Relay Setup
echo ============================================
echo.
echo Step 1: Installing dependencies...
npm install
if %errorlevel% neq 0 (
    echo ERROR: npm install failed. Make sure Node.js is installed.
    echo Download Node.js from: https://nodejs.org
    pause
    exit /b 1
)

echo.
echo Step 2: Finding Node.js path...
for /f "delims=" %%i in ('where node') do set NODE_PATH=%%i
echo Node.js found at: %NODE_PATH%

echo.
echo Step 3: Adding to Windows startup...
set SCRIPT_DIR=%~dp0
set STARTUP=%APPDATA%\Microsoft\Windows\Start Menu\Programs\Startup
powershell -Command "$s=(New-Object -COM WScript.Shell).CreateShortcut('%STARTUP%\DrawerRelay.lnk');$s.TargetPath='%NODE_PATH%';$s.Arguments='"%SCRIPT_DIR%relay.cjs"';$s.WorkingDirectory='%SCRIPT_DIR%';$s.WindowStyle=7;$s.Save()"
echo Startup shortcut created.

echo.
echo ============================================
echo  Setup complete!
echo  The relay will now start automatically
echo  every time Windows starts.
echo.
echo  Starting relay now...
echo ============================================
start "Cash Drawer Relay" /MIN "%NODE_PATH%" "%SCRIPT_DIR%relay.cjs"
echo Done. The relay is running in the background.
pause
