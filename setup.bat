@echo off
echo ================================
echo Office AI Agent - Setup Script
echo ================================
echo.

echo [1/5] Installing Composer dependencies...
call composer install
if errorlevel 1 (
    echo ERROR: Composer install failed!
    pause
    exit /b 1
)
echo.

echo [2/5] Generating application key...
php artisan key:generate
echo.

echo [3/5] Running database migrations...
php artisan migrate
if errorlevel 1 (
    echo ERROR: Migration failed! Make sure your database is configured in .env
    pause
    exit /b 1
)
echo.

echo [4/5] Seeding initial data...
php artisan db:seed
if errorlevel 1 (
    echo ERROR: Seeding failed!
    pause
    exit /b 1
)
echo.

echo [5/5] Creating storage link...
php artisan storage:link
echo.

echo ================================
echo Setup completed successfully!
echo ================================
echo.
echo Next steps:
echo 1. Configure your AI API key in .env (AI_API_KEY)
echo 2. Run: php artisan serve
echo 3. Open: http://localhost:8000
echo.
echo Happy coding! 🚀
echo.
pause
