<?php

declare(strict_types=1);

use Dotenv\Dotenv;

// Define project root directory
define('BASE_PATH', dirname(__DIR__));

// Load environment variables
$dotenv = Dotenv::createImmutable(BASE_PATH);
$dotenv->load(); // Load .env file

// --- Application Configuration ---
define('APP_ENV', $_ENV['APP_ENV'] ?? 'production');
define('APP_DEBUG', filter_var($_ENV['APP_DEBUG'] ?? false, FILTER_VALIDATE_BOOLEAN));
define('APP_URL', rtrim($_ENV['APP_URL'] ?? 'http://localhost', '/'));

// --- Google API Configuration ---
define('GOOGLE_CLIENT_ID', $_ENV['GOOGLE_CLIENT_ID'] ?? '');
define('GOOGLE_CLIENT_SECRET', $_ENV['GOOGLE_CLIENT_SECRET'] ?? '');
define('GOOGLE_REDIRECT_URI', $_ENV['GOOGLE_REDIRECT_URI'] ?? '');

// --- Database Configuration (Placeholder) ---
define('DB_CONNECTION', $_ENV['DB_CONNECTION'] ?? 'mysql');
define('DB_HOST', $_ENV['DB_HOST'] ?? '127.0.0.1');
define('DB_PORT', $_ENV['DB_PORT'] ?? '3306');
define('DB_DATABASE', $_ENV['DB_DATABASE'] ?? '');
define('DB_USERNAME', $_ENV['DB_USERNAME'] ?? 'root');
define('DB_PASSWORD', $_ENV['DB_PASSWORD'] ?? '');

// --- Vite Asset Configuration ---
// Function to check if Vite dev server is running
function isViteDevelopment(): bool
{
    // Check for environment variable or if manifest doesn't exist
    if (APP_ENV !== 'local') {
        return false;
    }
    // Try to connect to Vite HMR port
    $viteServer = 'http://localhost:5173'; // Use Vite dev server URL
    $handle = @fopen($viteServer, 'r');
    if ($handle !== false) {
        fclose($handle);
        return true;
    }
    return false;
}

define('VITE_DEVELOPMENT', isViteDevelopment());
define('VITE_MANIFEST_PATH', BASE_PATH . '/public/assets/.vite/manifest.json');
define('VITE_SERVER', 'http://localhost:5173'); // Vite dev server URL

// Error reporting based on environment
if (APP_DEBUG) {
    ini_set('display_errors', '1');
    ini_set('display_startup_errors', '1');
    error_reporting(E_ALL);
} else {
    ini_set('display_errors', '0');
    ini_set('display_startup_errors', '0');
    error_reporting(0);
    // Consider setting up proper logging for production here
}

// Start session if not already started (important for auth)
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Include helper functions if you create them later
// require_once BASE_PATH . '/app/Core/helpers.php';