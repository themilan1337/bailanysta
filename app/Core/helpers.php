<?php
// app/Core/helpers.php

// --- view() function ---
function view(string $view, array $data = []): string {
    $viewPath = dirname(__DIR__) . '/Views/' . str_replace('.', '/', $view) . '.php';
     if (!file_exists($viewPath)) {
         error_log("View file not found: {$viewPath}");
         return "Error: View file '{$view}' not found.";
     }
     extract($data);
     ob_start();
     include $viewPath;
     $content = ob_get_clean();
     $layoutPath = dirname(__DIR__) . '/Views/layouts/app.php';
     if (file_exists($layoutPath)) {
          ob_start();
          // The layout file expects $content and potentially $pageTitle, etc from extract($data)
          include $layoutPath;
          return ob_get_clean();
     } else {
          return $content;
     }
}


// --- vite_assets() function ---
// Functionality to include Vite assets based on manifest.json or dev server
function vite_assets(string|array $entrypoints): string
{
    $viteDevServerBase = 'http://localhost:5173'; // Base URL of the Vite dev server
    $manifestPath = dirname(__DIR__, 2) . '/public/build/.vite/manifest.json'; // Correct path to manifest
    $isDev = APP_ENV === 'development'; // Rely on APP_ENV

    if (!is_array($entrypoints)) {
        $entrypoints = [$entrypoints];
    }

    $html = '';
    $devServerIsRunning = false;

    // Check if Vite dev server is running in development mode
    if ($isDev) {
        $ch = curl_init($viteDevServerBase . '/@vite/client');
        @curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        @curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 1);
        @curl_setopt($ch, CURLOPT_TIMEOUT, 1);
        @curl_setopt($ch, CURLOPT_NOBODY, true);
        @curl_exec($ch);
        $error = curl_errno($ch);
        curl_close($ch);
        $devServerIsRunning = ($error === 0);
         if (!$devServerIsRunning) {
             // Log only once per request potentially to avoid spamming logs
             static $loggedViteError = false;
             if (!$loggedViteError) {
                 error_log("Vite dev server not reachable at $viteDevServerBase. Did you run 'npm run dev'?");
                 $loggedViteError = true;
             }
         }
    }


    if ($isDev && $devServerIsRunning) {
        // Development mode: Link directly to Vite server
        $html .= '<script type="module" src="' . $viteDevServerBase . '/@vite/client"></script>';
        foreach ($entrypoints as $entry) {
            $path = ltrim($entry, '/');
            if (str_ends_with($path, '.css')) {
                // In dev, CSS might be injected by JS, but including link doesn't hurt
                 $html .= '<link rel="stylesheet" href="' . $viteDevServerBase . '/' . $path . '">';
            } elseif (str_ends_with($path, '.js')) {
                 $html .= '<script type="module" src="' . $viteDevServerBase . '/' . $path . '"></script>';
            }
        }
    } else {
        // Production mode: Use manifest.json
        if (!file_exists($manifestPath)) {
             $message = "manifest.json not found at {$manifestPath}. Did you run 'npm run build'?";
             error_log($message);
             return "<!-- {$message} -->"; // Return comment in HTML
        }

        $manifest = json_decode(file_get_contents($manifestPath), true);
        if ($manifest === null) {
            $message = "Failed to decode manifest.json at {$manifestPath}.";
            error_log($message);
            return "<!-- {$message} -->";
        }

        $baseUrl = rtrim(config('BASE_URL', ''), '/');
        $buildPath = '/build/'; // Matches vite config 'base' for production

        foreach ($entrypoints as $entry) {
            $entryKey = ltrim($entry, '/');
            if (!isset($manifest[$entryKey])) {
                 error_log("Entrypoint '{$entryKey}' not found in manifest.json");
                 continue;
            }

            $entryData = $manifest[$entryKey];

            // Add CSS file(s) for the entry point
             if (isset($entryData['css']) && is_array($entryData['css'])) {
                foreach ($entryData['css'] as $cssFile) {
                     $html .= '<link rel="stylesheet" href="' . $baseUrl . $buildPath . $cssFile . '">';
                 }
            }
             // Handle case where the entry point *is* the CSS file
             if (isset($entryData['file']) && str_ends_with($entryData['file'], '.css')) {
                 $html .= '<link rel="stylesheet" href="' . $baseUrl . $buildPath . $entryData['file'] . '">';
             }


            // Add the main JS file for the entry point
            if (isset($entryData['file']) && str_ends_with($entryData['file'], '.js')) {
                $html .= '<script type="module" src="' . $baseUrl . $buildPath . $entryData['file'] . '"></script>';
            }

             // Preload imported JS modules and include their CSS
            if (isset($entryData['imports']) && is_array($entryData['imports'])) {
                 foreach ($entryData['imports'] as $importKey) {
                     if (!isset($manifest[$importKey])) continue; // Skip if import key not found

                     // Preload imported JS module
                     if(isset($manifest[$importKey]['file']) && str_ends_with($manifest[$importKey]['file'], '.js')) {
                         $html .= '<link rel="modulepreload" href="' . $baseUrl . $buildPath . $manifest[$importKey]['file'] . '">';
                     }
                     // Include CSS from imported modules
                     if (isset($manifest[$importKey]['css']) && is_array($manifest[$importKey]['css'])) {
                         foreach ($manifest[$importKey]['css'] as $cssFile) {
                             $cssUrl = $baseUrl . $buildPath . $cssFile;
                             // Avoid duplicating CSS links
                             if (strpos($html, 'href="' . $cssUrl . '"') === false) {
                                 $html .= '<link rel="stylesheet" href="' . $cssUrl . '">';
                             }
                         }
                     }
                 }
             }
        }
    }

    return $html;
}

// --- NO global formatTimeAgo() function here ---