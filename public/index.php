<?php

declare(strict_types=1);

// Register Composer's autoloader
require_once dirname(__DIR__) . '/vendor/autoload.php';

// Load configuration (which also loads .env and starts session)
require_once dirname(__DIR__) . '/config/config.php';

// --- Routing ---
$routeDefinitionCallback = require BASE_PATH . '/config/routes.php';
$dispatcher = FastRoute\simpleDispatcher($routeDefinitionCallback);

// Fetch method and URI from somewhere
$httpMethod = $_SERVER['REQUEST_METHOD'];
$uri = $_SERVER['REQUEST_URI'];

// Strip query string (?foo=bar) and decode URI
if (false !== $pos = strpos($uri, '?')) {
    $uri = substr($uri, 0, $pos);
}
$uri = rawurldecode($uri);

$routeInfo = $dispatcher->dispatch($httpMethod, $uri);

switch ($routeInfo[0]) {
    case FastRoute\Dispatcher::NOT_FOUND:
        // Handle 404 Not Found
        http_response_code(404);
        // You might want to render a specific 404 view here
        echo '404 Not Found';
        exit;

    case FastRoute\Dispatcher::METHOD_NOT_ALLOWED:
        $allowedMethods = $routeInfo[1];
        // Handle 405 Method Not Allowed
        http_response_code(405);
        // You might want to render a specific 405 view here
        echo '405 Method Not Allowed';
        exit;

    case FastRoute\Dispatcher::FOUND:
        $handler = $routeInfo[1];
        $vars = $routeInfo[2]; // Route parameters (e.g., /users/{id})

        // Check if the handler is in the expected format [ControllerClass, 'methodName']
        if (is_array($handler) && count($handler) === 2 && is_string($handler[0]) && is_string($handler[1])) {
            [$controllerClass, $method] = $handler;

            // Check if the controller class exists
            if (class_exists($controllerClass)) {
                // Instantiate the controller (Dependency Injection could be added later)
                $controller = new $controllerClass();

                // Check if the method exists in the controller
                if (method_exists($controller, $method)) {
                    // Call the controller method, passing route parameters
                    // Using call_user_func_array to handle parameters dynamically
                    try {
                        call_user_func_array([$controller, $method], $vars);
                    } catch (\Throwable $e) {
                        // Basic error handling
                        error_log("Error executing controller: " . $e->getMessage() . "\n" . $e->getTraceAsString());
                        http_response_code(500);
                        echo APP_DEBUG ? ('Server Error: ' . $e->getMessage()) : '500 Internal Server Error';
                        exit;
                    }
                } else {
                    // Method not found in controller
                    http_response_code(500);
                    error_log("Method '{$method}' not found in controller '{$controllerClass}'");
                    echo '500 Internal Server Error (Method not found)';
                    exit;
                }
            } else {
                // Controller class not found
                http_response_code(500);
                error_log("Controller class '{$controllerClass}' not found");
                echo '500 Internal Server Error (Controller not found)';
                exit;
            }
        } else {
            // Invalid handler format defined in routes
            http_response_code(500);
            error_log("Invalid route handler format for URI '{$uri}'");
            echo '500 Internal Server Error (Invalid route handler)';
            exit;
        }
        break;
}