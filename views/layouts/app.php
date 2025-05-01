<?php
// views/layouts/app.php

// Function to include Vite assets (handles dev vs. prod)
function vite(string $entry): string
{
    $devServer = defined('VITE_DEV_SERVER') ? VITE_DEV_SERVER : 'http://localhost:5173';
    $publicPath = defined('VITE_PUBLIC_PATH') ? VITE_PUBLIC_PATH : '/dist/';
    $manifestPath = defined('VITE_MANIFEST_PATH') ? VITE_MANIFEST_PATH : __DIR__.'/../../public/dist/.vite/manifest.json';

    // Check if Vite dev server is running by trying to connect
    $isDev = false;
    $handle = @fopen($devServer, 'r');
    if ($handle !== false) {
        $isDev = true;
        fclose($handle);
    }

    if ($isDev) {
        // Development mode: load directly from Vite dev server
        return '<script type="module" src="' . $devServer . '/@vite/client"></script>' .
               '<script type="module" src="' . $devServer . '/' . ltrim($entry, '/') . '"></script>';
    } else {
        // Production mode: load from manifest
        if (!file_exists($manifestPath)) {
            error_log("Vite manifest not found at: " . $manifestPath);
            return ''; // Or throw an exception
        }

        $manifest = json_decode(file_get_contents($manifestPath), true);

        if (!isset($manifest[$entry])) {
             error_log("Vite entrypoint not found in manifest: " . $entry);
            return ''; // Or throw an exception
        }

        $asset = $manifest[$entry];
        $html = '';

        // Include CSS files associated with the entry
         if (!empty($asset['css'])) {
             foreach ($asset['css'] as $cssFile) {
                 $html .= '<link rel="stylesheet" href="' . $publicPath . $cssFile . '">';
             }
         }

         // Include the main JS module script
         $html .= '<script type="module" src="' . $publicPath . $asset['file'] . '"></script>';


        return $html;
    }
}
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= defined('APP_NAME') ? APP_NAME : 'Bailanysta' ?></title>

    <!-- Include Vite Assets (JS and CSS) -->
    <?= vite('src/js/app.js') // Main JS entry point ?>
    <?= vite('src/css/main.css') // Main CSS entry point ?>

</head>
<body class="bg-gradient-to-r from-sky-100 to-indigo-100 min-h-screen">

    <div class="container mx-auto p-4">
        <h1 class="text-4xl font-bold text-center text-blue-700 mb-6 underline decoration-wavy">
            Welcome to <?= defined('APP_NAME') ? APP_NAME : 'Bailanysta' ?>! (Tailwind Test)
        </h1>

        <div class="bg-white p-6 rounded-lg shadow-md">
            <p class="text-gray-800">If Tailwind CSS is working, the heading should be large, bold, blue, underlined, and centered. The body should have a light gradient background.</p>
            <p class="mt-4 text-green-600 font-semibold">Vite setup seems okay if you see this page without errors in the console.</p>
            <button class="mt-4 px-4 py-2 bg-purple-600 text-white rounded hover:bg-purple-700 transition duration-200">Test Button</button>
        </div>

        <!-- Content from specific page views will go here later -->
        <?php /* include __DIR__ . '/../pages/home.php'; */ ?>

    </div>

</body>
</html>