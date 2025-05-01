<?php

if (file_exists(__DIR__.'/../.env')) {
    $lines = file(__DIR__.'/../.env', FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        if (strpos(trim($line), '#') === 0) {
            continue;
        }
        list($name, $value) = explode('=', $line, 2);
        $name = trim($name);
        $value = trim($value);
        if (!array_key_exists($name, $_ENV)) {
            putenv(sprintf('%s=%s', $name, $value));
            $_ENV[$name] = $value;
        }
    }
}

define('BASE_URL', $_ENV['APP_URL'] ?? 'http://localhost:5173');

define('DB_HOST', $_ENV['DB_HOST'] ?? 'localhost');
define('DB_PORT', $_ENV['DB_PORT'] ?? '3306');
define('DB_DATABASE', $_ENV['DB_DATABASE'] ?? 'bailanysta');
define('DB_USERNAME', $_ENV['DB_USERNAME'] ?? 'root');
define('DB_PASSWORD', $_ENV['DB_PASSWORD'] ?? '');

define('GOOGLE_CLIENT_ID', $_ENV['GOOGLE_CLIENT_ID'] ?? 'YOUR_GOOGLE_CLIENT_ID');
define('GOOGLE_CLIENT_SECRET', $_ENV['GOOGLE_CLIENT_SECRET'] ?? 'YOUR_GOOGLE_CLIENT_SECRET');
define('GOOGLE_REDIRECT_URI', ($_ENV['APP_URL'] ?? 'http://localhost:5173') . '/auth/google/callback');

define('APP_NAME', $_ENV['APP_NAME'] ?? 'Bailanysta');
define('DEBUG_MODE', filter_var($_ENV['DEBUG_MODE'] ?? true, FILTER_VALIDATE_BOOLEAN));

define('VITE_MANIFEST_PATH', __DIR__ . '/../public/dist/.vite/manifest.json');
define('VITE_DEV_SERVER', 'http://localhost:5173');
define('VITE_PUBLIC_PATH', '/dist/');

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

?>