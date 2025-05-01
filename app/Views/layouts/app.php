<!doctype html>
<html lang="en" class="">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <?= vite_assets() ?>

    <title><?= htmlspecialchars($pageTitle ?? 'Bailanysta') ?></title>

</head>
<body class="bg-gray-100 dark:bg-slate-900 text-gray-900 dark:text-gray-100 min-h-screen font-sans antialiased transition-colors duration-300">

    <nav class="bg-white dark:bg-slate-800 shadow-md mb-8">
        <div class="container mx-auto px-4 py-3 flex justify-between items-center">
            <a href="/" class="text-xl font-bold text-blue-600 dark:text-blue-400">Bailanysta</a>
            <div>
                <a href="/feed" class="text-gray-700 dark:text-gray-300 hover:text-blue-600 dark:hover:text-blue-400 px-3 py-2 rounded">Feed</a>
                <a href="/profile" class="text-gray-700 dark:text-gray-300 hover:text-blue-600 dark:hover:text-blue-400 px-3 py-2 rounded">Profile</a>
                 <a href="/login" class="text-gray-700 dark:text-gray-300 hover:text-blue-600 dark:hover:text-blue-400 px-3 py-2 rounded">Login</a>
            </div>
        </div>
    </nav>

    <main class="container mx-auto px-4 py-4">
        <?php
        // Include the specific page content view passed from the controller
        if (isset($contentView)) {
            view($contentView, $viewData ?? []); // Pass page-specific data to the content view
        } else {
            echo '<p class="text-red-500">Error: Content view not specified.</p>';
        }
        ?>
    </main>

    <footer class="text-center text-gray-600 dark:text-gray-400 mt-12 py-4 border-t dark:border-gray-700">
        © <?= date('Y') ?> Bailanysta. All rights reserved.
    </footer>

</body>
</html>