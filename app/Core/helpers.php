<?php
// app/Core/helpers.php

// ... (view function remains the same) ...

/**
 * Generates HTML tags for Vite assets (CSS & JS).
 * Reads manifest.json for production builds.
 * Connects to Vite dev server for development.
 *
 * @param string|string[] $entrypoints Entry point file(s) relative to the project root (e.g., 'resources/js/app.js').
 * @return string HTML tags for the assets.
 */
function vite_assets(string|array $entrypoints): string
{
    $viteDevServerBase = 'http://localhost:5173'; // Base URL of the Vite dev server
    $manifestPath = dirname(__DIR__, 2) . '/public/build/.vite/manifest.json'; // Correct path to manifest
    $isDev = APP_ENV === 'development'; // Rely on APP_ENV

    if (!is_array($entrypoints)) {
        $entrypoints = [$entrypoints];
    }

    $html = '';

    // Try to detect Vite dev server more reliably in dev mode
    $devServerIsRunning = false;
    if ($isDev) {
        $ch = curl_init($viteDevServerBase . '/@vite/client');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 1); // Short timeout
        curl_setopt($ch, CURLOPT_TIMEOUT, 1);
        curl_setopt($ch, CURLOPT_NOBODY, true); // We only need headers/connection status
        curl_exec($ch);
        $error = curl_errno($ch);
        curl_close($ch);
        $devServerIsRunning = ($error === 0); // CURLE_OK means connection succeeded
         if (!$devServerIsRunning) {
             error_log("Vite dev server not reachable at $viteDevServerBase. Did you run 'npm run dev'?");
         }
    }


    if ($isDev && $devServerIsRunning) {
        // Development mode: Include Vite client and entry points directly from Vite server
        $html .= '<script type="module" src="' . $viteDevServerBase . '/@vite/client"></script>';
        foreach ($entrypoints as $entry) {
            $html .= '<script type="module" src="' . $viteDevServerBase . '/' . ltrim($entry, '/') . '"></script>';
        }
    } else {
        // Production mode (or dev server not running): Use manifest.json
        if (!file_exists($manifestPath)) {
             $message = "manifest.json not found at {$manifestPath}. Did you run 'npm run build'?";
             error_log($message);
             // Optionally return an HTML comment or throw an exception in production
             return "<!-- {$message} -->";
        }

        $manifest = json_decode(file_get_contents($manifestPath), true);
        if ($manifest === null) {
            $message = "Failed to decode manifest.json at {$manifestPath}.";
            error_log($message);
            return "<!-- {$message} -->";
        }


        foreach ($entrypoints as $entry) {
            $entryKey = ltrim($entry, '/'); // Ensure key matches manifest format
            if (!isset($manifest[$entryKey])) {
                 error_log("Entrypoint '{$entryKey}' not found in manifest.json");
                 continue;
            }

            $entryData = $manifest[$entryKey];
            $baseUrl = rtrim(config('BASE_URL', ''), '/'); // Get base URL from config
            $buildPath = '/build/'; // Matches vite config 'base' for production

            // Add the main JS file for the entry point
            if (isset($entryData['file'])) {
                $html .= '<script type="module" src="' . $baseUrl . $buildPath . $entryData['file'] . '"></script>';
            }

            // Add CSS files associated with the entry point
            if (isset($entryData['css']) && is_array($entryData['css'])) {
                foreach ($entryData['css'] as $cssFile) {
                    $html .= '<link rel="stylesheet" href="' . $baseUrl . $buildPath . $cssFile . '">';
                }
            }

             // Preload imported JS modules and their CSS (optional but good for performance)
            if (isset($entryData['imports']) && is_array($entryData['imports'])) {
                 foreach ($entryData['imports'] as $importKey) {
                     if(isset($manifest[$importKey]['file'])) {
                         $html .= '<link rel="modulepreload" href="' . $baseUrl . $buildPath . $manifest[$importKey]['file'] . '">';
                     }
                     // Handle CSS from imported modules
                     if (isset($manifest[$importKey]['css']) && is_array($manifest[$importKey]['css'])) {
                         foreach ($manifest[$importKey]['css'] as $cssFile) {
                             // Avoid duplicating CSS links if already added by main entry
                             if (strpos($html, 'href="' . $baseUrl . $buildPath . $cssFile . '"') === false) {
                                 $html .= '<link rel="stylesheet" href="' . $baseUrl . $buildPath . $cssFile . '">';
                             }
                         }
                     }
                 }
             }
        }
    }

    return $html;
}

// Ensure view function is also here
function view(string $view, array $data = []): string {
    // ... view function code from previous answer ...
     // Construct the full path to the view file
     $viewPath = dirname(__DIR__) . '/Views/' . str_replace('.', '/', $view) . '.php'; // Adjusted path

     if (!file_exists($viewPath)) {
         error_log("View file not found: {$viewPath}");
         // In production, you might want a more user-friendly error
         return "Error: View file '{$view}' not found.";
         // throw new \InvalidArgumentException("View file not found: {$viewPath}");
     }

     // Extract data into the local scope
     extract($data);

     // Start output buffering for the view content
     ob_start();
     include $viewPath;
     $content = ob_get_clean(); // This is the content of the specific view file

     // Check for a layout file
     $layoutPath = dirname(__DIR__) . '/Views/layouts/app.php'; // Adjusted path
     if (file_exists($layoutPath)) {
          // If a layout exists, buffer its output
          ob_start();
          // Include the layout. The layout should use the $content variable.
          include $layoutPath;
          // Return the complete layout content
          return ob_get_clean();
     } else {
          // If no layout, just return the view content
          return $content;
     }
}

