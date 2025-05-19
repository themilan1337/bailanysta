<?php
declare(strict_types=1);

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../app/Core/helpers.php';

/**
 * Bailanysta - entry point
 */

// --- Initial Setup ---
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// --- Routing Setup ---
$dispatcher = FastRoute\simpleDispatcher(function(FastRoute\RouteCollector $r) {
    require __DIR__ . '/../config/routes.php';
});


// --- Request Processing ---
$httpMethod = $_SERVER['REQUEST_METHOD'];
$uri = $_SERVER['REQUEST_URI'];

if (false !== $pos = strpos($uri, '?')) {
    $uri = substr($uri, 0, $pos);
}
$uri = rawurldecode($uri);

if (empty($uri)) {
    $uri = '/';
} elseif ($uri[0] !== '/') {
     $uri = '/' . $uri;
}

//$basePath = parse_url(BASE_URL, PHP_URL_PATH);
//if ($basePath && $basePath !== '/' && strpos($uri, $basePath) === 0) {
//    $uri = substr($uri, strlen($basePath));
//    if (empty($uri) || $uri[0] !== '/') {
//         $uri = '/' . ltrim($uri, '/');
//    }
//}


// --- Dispatching and Handling ---
$routeInfo = $dispatcher->dispatch($httpMethod, $uri);

switch ($routeInfo[0]) {
    // --- 404 ---
    case FastRoute\Dispatcher::NOT_FOUND:
        http_response_code(404);
        try {
            echo view('pages.404', ['pageTitle' => 'Page Not Found']);
        } catch (\Throwable $e) {
            // Fallback if the view fails (e.g., file missing, error in view)
            error_log("Error rendering 404 view: " . $e->getMessage());
            // Output a basic HTML 404 message.
            echo "<h1>404 Not Found</h1><p>The requested page could not be found.</p>";
        }
        break;

    // --- 405 ---
    case FastRoute\Dispatcher::METHOD_NOT_ALLOWED:
        $allowedMethods = $routeInfo[1];
        http_response_code(405);
        header('Allow: ' . implode(', ', $allowedMethods));
        echo "<h1>405 Method Not Allowed</h1><p>Allowed methods for this resource: " . implode(', ', $allowedMethods) . "</p>";
        break;

        case FastRoute\Dispatcher::FOUND:
            $handler = $routeInfo[1];
            $vars = $routeInfo[2];
    
            $controllerClass = null;
            $methodName = null;
            $isCallable = false;
    
            if (is_array($handler) && count($handler) === 2 && is_string($handler[0]) && is_string($handler[1])) {
                $controllerClass = $handler[0];
                $methodName = $handler[1];
            } elseif (is_string($handler) && strpos($handler, '@') !== false) {
                list($controllerName, $methodName) = explode('@', $handler, 2);
                $controllerClass = "App\\Controllers\\" . $controllerName;
            } elseif (is_callable($handler)) {
                $isCallable = true;
            }
    
            // --- execute handler ---
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
                            $reflectionMethod = new ReflectionMethod($controllerClass, $methodName);
                            $methodParams = $reflectionMethod->getParameters();
                            $arguments = []; // hold final args in correct order
    
                            foreach ($methodParams as $param) {
                                $paramName = $param->getName();
                                $paramType = $param->getType();
                                $paramTypeName = ($paramType instanceof ReflectionNamedType) ? $paramType->getName() : null;
    
                                if (isset($vars[$paramName])) {
                                    $value = $vars[$paramName];
    
                                    if ($paramTypeName === 'int' && is_string($value) && ctype_digit($value)) {
                                        $arguments[] = (int)$value;
                                    } elseif ($paramTypeName === 'float' && is_numeric($value)) {
                                        $arguments[] = (float)$value;
                                    } elseif ($paramTypeName === 'bool' && ($value === '1' || $value === '0' || $value === 'true' || $value === 'false')) {
                                         $arguments[] = filter_var($value, FILTER_VALIDATE_BOOLEAN);
                                     }else {
                                        $arguments[] = $value;
                                    }
                                } elseif ($param->isDefaultValueAvailable()) {
                                    $arguments[] = $param->getDefaultValue();
                                } elseif (!$param->isOptional()) {
                                    throw new \InvalidArgumentException("Missing required route parameter: \${$paramName} for {$controllerClass}@{$methodName}");
                                }
                            }
                            error_log("Arguments prepared via Reflection for {$controllerClass}@{$methodName}: " . print_r($arguments, true));

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
                     http_response_code(500);
                    $errorMessage = "Error: Controller class '{$controllerClass}' not found.";
                    error_log($errorMessage);
                    echo APP_ENV === 'development' ? $errorMessage : "Server Error";
                }
            }
            elseif ($isCallable) {
                 try {
                    $arguments = array_values($vars);
                     foreach ($arguments as $key => $value) {
                         if (is_string($value) && ctype_digit($value)) { $arguments[$key] = (int)$value; }
                     }
                     call_user_func_array($handler, $arguments); // array_values for positional callables too
                 } catch (\Throwable $e) {
                      http_response_code(500);
                     error_log("Error executing callable route handler: " . $e->getMessage());
                     echo APP_ENV === 'development'
                        ? "<h1>Error</h1><p>Exception in callable route handler.</p><p>Details: " . htmlspecialchars($e->getMessage()) . "</p><pre>" . htmlspecialchars($e->getTraceAsString()) . "</pre>"
                        : "<h1>Server Error</h1><p>An unexpected error occurred.</p>";
                 }
            }
            else {
                 http_response_code(500);
                $errorMessage = "Error: Invalid route handler configuration.";
                error_log($errorMessage . " Handler received: " . print_r($handler, true));
                echo APP_ENV === 'development' ? $errorMessage . " Handler: <pre>" . htmlspecialchars(print_r($handler, true)) . "</pre>" : "Server Error";
            }
            break; // end FOUND case

}