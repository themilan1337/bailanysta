<?php
declare(strict_types=1);
require_once BASE_PATH . '/app/Core/helpers.php';

if (!function_exists('vite_assets')) {
    /**
     * Generates script and link tags for Vite assets.
     * Needs access to constants defined in config.php.
     *
     * @return string HTML tags for Vite assets.
     */
    function vite_assets(): string
    {
        $viteServer = VITE_SERVER; // e.g., http://localhost:5173
        $manifestPath = VITE_MANIFEST_PATH; // e.g., BASE_PATH . '/public/assets/.vite/manifest.json'
        $basePath = rtrim(str_replace($_SERVER['DOCUMENT_ROOT'], '', BASE_PATH . '/public'), '/'); // Relative web path to public dir

        if (VITE_DEVELOPMENT) {
            // Development mode: Include Vite client and entry point script
            return <<<HTML
                <script type="module" src="{$viteServer}/@vite/client"></script>
                <script type="module" src="{$viteServer}/resources/js/app.js"></script>
            HTML;
        } else {
            // Production mode: Read manifest.json
            if (!file_exists($manifestPath)) {
                error_log('Vite manifest not found at: ' . $manifestPath);
                return '<!-- Vite Manifest Not Found -->';
            }

            $manifest = json_decode(file_get_contents($manifestPath), true);
            if (!$manifest) {
                error_log('Error decoding Vite manifest: ' . $manifestPath);
                 return '<!-- Error reading Vite Manifest -->';
            }

            // Find the entry point script (adjust key if your input name in vite.config.js is different)
            $jsEntryKey = 'resources/js/app.js'; // Matches input key in vite.config.js
            $html = '';

            if (isset($manifest[$jsEntryKey])) {
                $entry = $manifest[$jsEntryKey];
                $jsFile = $basePath . '/assets/' . $entry['file'];
                $html .= "<script type=\"module\" src=\"{$jsFile}\"></script>\n";

                // Include CSS file(s) associated with the entry point
                if (isset($entry['css']) && is_array($entry['css'])) {
                    foreach ($entry['css'] as $cssFile) {
                        $cssPath = $basePath . '/assets/' . $cssFile;
                        $html .= "<link rel=\"stylesheet\" href=\"{$cssPath}\">\n";
                    }
                }
                 // Include CSS file directly associated with the entry if present (Tailwind 4 pattern?)
                if (isset($entry['isEntry']) && $entry['isEntry'] && isset($entry['css'])) {
                     // Vite/Tailwind might put CSS here in some configs
                     // Let's re-check if the above loop didn't catch it
                     // This might be redundant depending on exact Vite/Tailwind v4 interaction
                }

            } else {
                 error_log("Entry point '{$jsEntryKey}' not found in Vite manifest: " . $manifestPath);
                 return "<!-- Entry point {$jsEntryKey} not found in manifest -->";
            }

            // Preload imports (optional but good for performance)
             if (isset($entry['imports']) && is_array($entry['imports'])) {
                 foreach ($entry['imports'] as $import) {
                     if(isset($manifest[$import]['file'])) {
                         $importFile = $basePath . '/assets/' . $manifest[$import]['file'];
                        // Check if it's a CSS or JS file based on extension
                         if (str_ends_with($importFile, '.css')) {
                             $html .= "<link rel=\"modulepreload\" href=\"{$importFile}\" as=\"style\">\n";
                         } else if (str_ends_with($importFile, '.js')) {
                             $html .= "<link rel=\"modulepreload\" href=\"{$importFile}\" as=\"script\">\n";
                         } else {
                              $html .= "<link rel=\"modulepreload\" href=\"{$importFile}\">\n"; // Generic preload
                         }
                     }
                 }
             }

            return rtrim($html);
        }
    }
}

if (!function_exists('view')) {
    /**
     * Simple view rendering helper.
     *
     * @param string $viewName The name of the view file (e.g., 'pages.home' or 'layouts.app')
     * @param array $data Data to extract into the view's scope
     * @return void
     */
    function view(string $viewName, array $data = []): void
    {
        // Convert dot notation to directory separators
        $viewPath = str_replace('.', '/', $viewName);
        $fullPath = BASE_PATH . '/app/Views/' . $viewPath . '.php';

        if (file_exists($fullPath)) {
            // Extract data variables into the current symbol table
            extract($data);

            // Include the view file
            include $fullPath;
        } else {
            error_log("View file not found: " . $fullPath);
            echo "Error: View '{$viewName}' not found.";
            // Optionally throw an exception or show a more user-friendly error
        }
    }
}

// Include this helper file in config.php or index.php early on
// Add this line near the end of config/config.php:
// require_once BASE_PATH . '/app/Core/helpers.php';