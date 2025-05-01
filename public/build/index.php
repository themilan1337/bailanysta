<?php
declare(strict_types=1);

/**
 * Bailanysta - Public Entry Point
 *
 * All web requests are handled by this file.
 */

// --- Initial Setup ---

// Start session if needed (e.g., for auth later)
// Doing this early is generally good practice.
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// 1. Load Configuration & Dependencies
// This should be the first include as it sets up constants, autoloading, etc.
// It's assumed config.php requires the Composer autoloader.
require_once __DIR__ . '/../config/config.php';

// 2. Load Core Helper Functions (required explicitly if not autoloaded)
// Contains essential functions like view() and vite_assets().
require_once __DIR__ . '/../app/Core/helpers.php';


// --- Routing Setup ---

// 3. Define Routes using FastRoute
// Routes are defined in a separate file for organization.
// FastRoute\simpleDispatcher creates a dispatcher based on the defined routes.
$dispatcher = FastRoute\simpleDispatcher(function(FastRoute\RouteCollector $r) {
    // The callback function receives a RouteCollector instance ($r).
    // The required file uses $r->addRoute(...) to define application routes.
    require __DIR__ . '/../config/routes.php';
});


// --- Request Processing ---

// 4. Fetch HTTP Method and URI from the request
$httpMethod = $_SERVER['REQUEST_METHOD'];
$uri = $_SERVER['REQUEST_URI'];

// Clean the URI:
// - Strip query string (?foo=bar) from the URI for routing purposes.
if (false !== $pos = strpos($uri, '?')) {
    $uri = substr($uri, 0, $pos);
}
// - Decode URL-encoded characters (e.g., %20 becomes space).
$uri = rawurldecode($uri);

// Ensure URI starts with a '/' for consistent matching by FastRoute.
// Handles cases where the URI might be empty or missing the leading slash.
if (empty($uri)) {
    $uri = '/';
} elseif ($uri[0] !== '/') {
     $uri = '/' . $uri;
}

// Optional: Base path stripping (usually not needed with `php -S -t public`)
// Uncomment and adjust if your app runs in a subdirectory and routes don't match.
/*
$basePath = parse_url(BASE_URL, PHP_URL_PATH); // Get path component from BASE_URL
if ($basePath && $basePath !== '/' && strpos($uri, $basePath) === 0) {
    $uri = substr($uri, strlen($basePath));
    if (empty($uri) || $uri[0] !== '/') {
         $uri = '/' . ltrim($uri, '/'); // Ensure it starts with '/' after stripping
    }
}
*/


// --- Dispatching and Handling ---

// 5. Dispatch the Request using FastRoute
// The dispatcher matches the $httpMethod and $uri against the defined routes.
$routeInfo = $dispatcher->dispatch($httpMethod, $uri);

// 6. Handle the Route Result based on the dispatcher's response
switch ($routeInfo[0]) {

    // --- Case 1: Route Not Found (404) ---
    case FastRoute\Dispatcher::NOT_FOUND:
        http_response_code(404);
        try {
            // Attempt to render a user-friendly 404 view using the helper function.
            echo view('pages.404', ['pageTitle' => 'Page Not Found']);
        } catch (\Throwable $e) {
            // Fallback if the view fails (e.g., file missing, error in view)
            error_log("Error rendering 404 view: " . $e->getMessage());
            // Output a basic HTML 404 message.
            echo "<h1>404 Not Found</h1><p>The requested page could not be found.</p>";
        }
        break;

    // --- Case 2: Method Not Allowed (405) ---
    case FastRoute\Dispatcher::METHOD_NOT_ALLOWED:
        $allowedMethods = $routeInfo[1]; // Get the list of allowed methods
        http_response_code(405);
        header('Allow: ' . implode(', ', $allowedMethods)); // Set the 'Allow' HTTP header
        // Output a basic HTML 405 message.
        echo "<h1>405 Method Not Allowed</h1><p>Allowed methods for this resource: " . implode(', ', $allowedMethods) . "</p>";
        break;

        case FastRoute\Dispatcher::FOUND:
            $handler = $routeInfo[1];
            $vars = $routeInfo[2]; // Associative array ['postId' => '1']
    
            $controllerClass = null;
            $methodName = null;
            $isCallable = false;
    
            // Determine Handler Type (same as before)
            if (is_array($handler) && count($handler) === 2 && is_string($handler[0]) && is_string($handler[1])) {
                $controllerClass = $handler[0];
                $methodName = $handler[1];
            } elseif (is_string($handler) && strpos($handler, '@') !== false) {
                list($controllerName, $methodName) = explode('@', $handler, 2);
                $controllerClass = "App\\Controllers\\" . $controllerName;
            } elseif (is_callable($handler)) {
                $isCallable = true;
            }
    
            // --- Execute Handler ---
            if ($controllerClass !== null && $methodName !== null) {
                if (class_exists($controllerClass)) {
                    try {
                        $controller = new $controllerClass();
                    } catch (\Throwable $e) {
                         http_response_code(500);
                         error_log("Error instantiating controller {$controllerClass}: " . $e->getMessage());
                         echo APP_ENV === 'development' ? "Error: Could not instantiate controller {$controllerClass}. <br>Details: " . htmlspecialchars($e->getMessage()) : "Server Error";
                         break;
                    }
    
                    if (method_exists($controller, $methodName)) {
                        try {
                            // --- Reflection-Based Argument Preparation ---
                            $reflectionMethod = new ReflectionMethod($controllerClass, $methodName);
                            $methodParams = $reflectionMethod->getParameters();
                            $arguments = []; // Array to hold final arguments in correct order
    
                            // Match route variables ($vars) to method parameters by name and type
                            foreach ($methodParams as $param) {
                                $paramName = $param->getName();
                                $paramType = $param->getType(); // ReflectionType object or null
                                $paramTypeName = ($paramType instanceof ReflectionNamedType) ? $paramType->getName() : null; // e.g., 'int', 'string'
    
                                if (isset($vars[$paramName])) {
                                    $value = $vars[$paramName]; // Get value from route vars
    
                                    // Attempt type casting based on parameter type hint
                                    if ($paramTypeName === 'int' && is_string($value) && ctype_digit($value)) {
                                        $arguments[] = (int)$value;
                                    } elseif ($paramTypeName === 'float' && is_numeric($value)) {
                                        $arguments[] = (float)$value;
                                    } elseif ($paramTypeName === 'bool' && ($value === '1' || $value === '0' || $value === 'true' || $value === 'false')) {
                                         $arguments[] = filter_var($value, FILTER_VALIDATE_BOOLEAN);
                                     }else {
                                        // Pass as string or original type if no specific cast needed/possible
                                        $arguments[] = $value;
                                    }
                                } elseif ($param->isDefaultValueAvailable()) {
                                    // If route variable not set, use parameter's default value
                                    $arguments[] = $param->getDefaultValue();
                                } elseif (!$param->isOptional()) {
                                    // If required parameter is missing in route vars and has no default
                                    throw new \InvalidArgumentException("Missing required route parameter: \${$paramName} for {$controllerClass}@{$methodName}");
                                }
                                // Optional parameters without a value in $vars will simply not be added
                            }
                            error_log("Arguments prepared via Reflection for {$controllerClass}@{$methodName}: " . print_r($arguments, true));
                            // --- End Argument Preparation ---
    
                            // Call the method using the prepared arguments array
                            call_user_func_array([$controller, $methodName], $arguments);
    
                        } catch (\Throwable $e) {
                             http_response_code(500);
                             $errorMessage = "Error executing controller method {$controllerClass}@{$methodName}: " . $e->getMessage();
                             error_log($errorMessage . "\n" . $e->getTraceAsString());
                             echo APP_ENV === 'development'
                                 ? "<h1>Error</h1><p>Exception in controller method {$controllerClass}@{$methodName}.</p><p>Details: " . htmlspecialchars($e->getMessage()) . "</p><pre>" . htmlspecialchars($e->getTraceAsString()) . "</pre>"
                                 : "<h1>Server Error</h1><p>An unexpected error occurred. Please try again later.</p>";
                        }
                    } else {
                        // Method not found... (same as before)
                        http_response_code(500);
                        $errorMessage = "Error: Method '{$methodName}' not found in controller '{$controllerClass}'.";
                        error_log($errorMessage);
                        echo APP_ENV === 'development' ? $errorMessage : "Server Error";
                    }
                } else {
                    // Controller class not found... (same as before)
                     http_response_code(500);
                    $errorMessage = "Error: Controller class '{$controllerClass}' not found.";
                    error_log($errorMessage);
                    echo APP_ENV === 'development' ? $errorMessage : "Server Error";
                }
            }
            elseif ($isCallable) {
                // Handle callables (same as before, needs casting if type hinted)
                 try {
                    // Note: Casting for callable handlers would need similar logic if they use type hints
                    $arguments = array_values($vars);
                     foreach ($arguments as $key => $value) {
                         if (is_string($value) && ctype_digit($value)) { $arguments[$key] = (int)$value; }
                     }
                     call_user_func_array($handler, $arguments); // Use array_values for positional callables too
                 } catch (\Throwable $e) {
                      http_response_code(500);
                     error_log("Error executing callable route handler: " . $e->getMessage());
                     echo APP_ENV === 'development'
                        ? "<h1>Error</h1><p>Exception in callable route handler.</p><p>Details: " . htmlspecialchars($e->getMessage()) . "</p><pre>" . htmlspecialchars($e->getTraceAsString()) . "</pre>"
                        : "<h1>Server Error</h1><p>An unexpected error occurred.</p>";
                 }
            }
            else {
                // Invalid handler format... (same as before)
                 http_response_code(500);
                $errorMessage = "Error: Invalid route handler configuration.";
                error_log($errorMessage . " Handler received: " . print_r($handler, true));
                echo APP_ENV === 'development' ? $errorMessage . " Handler: <pre>" . htmlspecialchars(print_r($handler, true)) . "</pre>" : "Server Error";
            }
            break; // End FOUND case

} // End switch ($routeInfo[0])