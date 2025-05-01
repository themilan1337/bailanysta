<?php
// config/config.php

date_default_timezone_set('Asia/Almaty');

// Autoload dependencies
require_once __DIR__ . '/../vendor/autoload.php';

// --- Application Configuration ---
define('BASE_URL', 'http://localhost:8000'); // NO trailing slash
define('APP_ENV', 'development'); // 'development' or 'production'

// --- Database Configuration ---
define('DB_HOST', '127.0.0.1');       // Or your DB host (e.g., 'localhost')
define('DB_PORT', '3306');            // Default MySQL port
define('DB_DATABASE', 'bailanysta');  // Your database name
define('DB_USERNAME', 'root');        // Your database username
define('DB_PASSWORD', '');            // Your database password (use quotes even if empty)
define('DB_CHARSET', 'utf8mb4');

// --- Google API Credentials (Placeholder) ---
define('GOOGLE_CLIENT_ID', '2547332709-jmtah33q6j6eu7copud1c6s8356vml4v.apps.googleusercontent.com');
define('GOOGLE_CLIENT_SECRET', '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');
define('GOOGLE_REDIRECT_URI', BASE_URL . '/auth/google/callback');

define('GEMINI_API_KEY', 'AIzaSyDGsts9l3_8LWKzeYCiC_UCREVlDyJ9cnI');

// --- Helper Function & Error Reporting (Keep as before) ---
function config(string $key, $default = null) {
    $constant_name = strtoupper($key);
    return defined($constant_name) ? constant($constant_name) : $default;
}

ini_set('session.cookie_httponly', '1'); // Prevent JS access to session cookie
ini_set('session.use_only_cookies', '1'); // Prevent session ID in URL
if (APP_ENV === 'production') { // Only force secure cookies over HTTPS in production
    ini_set('session.cookie_secure', '1'); // Send cookie only over HTTPS
    ini_set('display_errors', 0); error_reporting(0);
} else {
    ini_set('display_errors', 1); error_reporting(E_ALL); // Show all errors in development
}
ini_set('session.cookie_samesite', 'Lax'); // Mitigate CSRF with SameSite attribute

// --- Simple PDO Database Connection Function ---
/**
 * Establishes a PDO database connection.
 * @return PDO|null Returns a PDO instance on success, null on failure.
 */
function get_db_connection(): ?PDO {
    static $pdo = null; // Static variable to hold the connection (singleton pattern)

    if ($pdo === null) {
        $dsn = "mysql:host=" . DB_HOST . ";port=" . DB_PORT . ";dbname=" . DB_DATABASE . ";charset=" . DB_CHARSET;
        $options = [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION, // Throw exceptions on error
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,       // Fetch results as associative arrays
            PDO::ATTR_EMULATE_PREPARES   => false,                  // Use native prepared statements
        ];

        try {
            $pdo = new PDO($dsn, DB_USERNAME, DB_PASSWORD, $options);
        } catch (\PDOException $e) {
            // Log the error in production, show details in development
            error_log("Database Connection Error: " . $e->getMessage());
            if (APP_ENV === 'development') {
                // Display error details only in development for security
                die("Database Connection Error: " . $e->getMessage());
            } else {
                // Provide a generic error message in production
                die("Database connection failed. Please try again later.");
            }
            // Optionally return null or throw the exception depending on how you want to handle failures globally
            // return null;
        }
    }
    return $pdo;
}