<?php

// +5gmt
date_default_timezone_set("Atlantic/Reykjavik");

require_once __DIR__ . '/../vendor/autoload.php';

// --- app cfg ---
define('BASE_URL', 'http://localhost:8000'); // NO trailing slash
define('APP_ENV', 'development'); // 'development' or 'production'

// --- db ---
define('DB_HOST', '127.0.0.1');
define('DB_PORT', '3306');
define('DB_DATABASE', 'bailanysta');
define('DB_USERNAME', 'root');
define('DB_PASSWORD', '');
define('DB_CHARSET', 'utf8mb4');

// --- google sign in ---
define('GOOGLE_CLIENT_ID', '2547332709-jmtah33q6j6eu7copud1c6s8356vml4v.apps.googleusercontent.com');
define('GOOGLE_CLIENT_SECRET', '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');
define('GOOGLE_REDIRECT_URI', BASE_URL . '/auth/google/callback');

define('DEEPINFRA_API_KEY', ''); //deepinfra.com

// --- helpers ---
function config(string $key, $default = null) {
    $constant_name = strtoupper($key);
    return defined($constant_name) ? constant($constant_name) : $default;
}

// --- misc ---
ini_set('session.cookie_httponly', '1');
ini_set('session.use_only_cookies', '1');
if (APP_ENV === 'production') {
    ini_set('session.cookie_secure', '1');
    ini_set('display_errors', 0); error_reporting(0);
} else {
    ini_set('display_errors', 1); error_reporting(E_ALL);
}
ini_set('session.cookie_samesite', 'Lax');

/**
 * @return PDO|null Returns a pdo instance on success, null on failure.
 */
function get_db_connection(): ?PDO {
    static $pdo = null;

    if ($pdo === null) {
        $dsn = "mysql:host=" . DB_HOST . ";port=" . DB_PORT . ";dbname=" . DB_DATABASE . ";charset=" . DB_CHARSET;
        $options = [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ];

        try {
            $pdo = new PDO($dsn, DB_USERNAME, DB_PASSWORD, $options);
        } catch (\PDOException $e) {
            error_log("Database Connection Error: " . $e->getMessage());
            if (APP_ENV === 'development') {
                die("Database Connection Error: " . $e->getMessage());
            } else {
                die("Database connection failed. Please try again later.");
            }
        }
    }
    return $pdo;
}
