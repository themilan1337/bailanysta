<?php
// This view assumes it's rendered via the view() helper,
// potentially wrapped by the layouts/app.php layout.
// $pageTitle should be passed from the controller/router.
?>

<div class="flex flex-col items-center justify-center min-h-[60vh] text-center">
    <h1 class="text-6xl font-bold text-red-500 dark:text-red-400 mb-4">404</h1>
    <h2 class="text-2xl font-semibold text-gray-700 dark:text-gray-300 mb-2">Page Not Found</h2>
    <p class="text-gray-500 dark:text-gray-400 mb-6">
        Sorry, the page you are looking for could not be found.
    </p>
    <a href="<?php echo BASE_URL; ?>/" class="px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700 transition duration-200">
        Go Back Home
    </a>
</div>