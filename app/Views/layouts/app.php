<?php
// app/Views/layouts/app.php

// Determine theme based on cookie, default to 'light'
$theme = $_COOKIE['ui-theme'] ?? 'light';
// Basic validation in case cookie is tampered with
if ($theme !== 'light' && $theme !== 'dark') {
    $theme = 'light';
}

// Check login status for conditional rendering
$isLoggedIn = isset($_SESSION['logged_in']) && $_SESSION['logged_in'] === true;

?>
<!doctype html>
<html lang="en" class="<?php echo $theme; // Apply theme class directly ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo isset($pageTitle) ? htmlspecialchars($pageTitle) . ' - Bailanysta' : 'Bailanysta'; // Append site name ?></title>
    <meta name="description" content="Bailanysta - Connect and Share">
    <?php
    // Include Vite assets (CSS and JS)
    // Make sure the paths match your vite.config.mjs input entries
    echo vite_assets(['resources/css/app.css', 'resources/js/app.js']);
    ?>
</head>
<body class="bg-background text-foreground font-sans antialiased">

    <div class="min-h-screen flex flex-col">

        <header class="sticky top-0 z-50 w-full border-b border-border/40 bg-background/95 backdrop-blur supports-[backdrop-filter]:bg-background/60">
            <nav class="container mx-auto h-14 flex items-center justify-between px-4">
                <a href="<?php echo BASE_URL; ?>/" class="mr-6 flex items-center space-x-2">
                    <span class="font-bold text-lg">Bailanysta</span>
                </a>

                <div class="flex flex-1 items-center justify-end space-x-2 sm:space-x-4">
                    <a href="<?php echo BASE_URL; ?>/" class="text-sm font-medium text-muted-foreground transition-colors hover:text-primary px-2 sm:px-3 py-2 rounded-md">
                        Feed
                    </a>

                        <?php if ($isLoggedIn): ?>
                        <a href="<?php echo BASE_URL; ?>/profile" class="text-sm font-medium text-muted-foreground transition-colors hover:text-primary px-2 sm:px-3 py-2 rounded-md">
                            Profile
                        </a>

                        <?php // --- Notification Dropdown --- ?>
                        <div class="relative" id="notification-dropdown-container">
                            <button id="notification-toggle-button" type="button" aria-label="View notifications" class="relative inline-flex items-center justify-center whitespace-nowrap rounded-md text-sm font-medium ring-offset-background transition-colors focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 disabled:pointer-events-none disabled:opacity-50 hover:bg-accent hover:text-accent-foreground h-10 w-10">
                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="h-5 w-5">
                                  <path stroke-linecap="round" stroke-linejoin="round" d="M14.857 17.082a23.848 23.848 0 0 0 5.454-1.31A8.967 8.967 0 0 1 18 9.75V9A6 6 0 0 0 6 9v.75a8.967 8.967 0 0 1-2.312 6.022c1.733.64 3.56 1.017 5.454 1.31m5.714 0a24.255 24.255 0 0 1-5.714 0m5.714 0a3 3 0 1 1-5.714 0" />
                                </svg>
                                <span id="notification-count-badge" class="absolute top-1 right-1 inline-flex items-center justify-center px-1.5 py-0.5 text-xs font-bold leading-none text-red-100 transform translate-x-1/2 -translate-y-1/2 bg-red-600 rounded-full hidden">0</span>
                            </button>
                            <div id="notification-list" class="hidden absolute right-0 mt-2 w-80 max-h-[70vh] overflow-y-auto bg-popover border rounded-md shadow-lg z-20 py-1 text-sm">
                                <div class="px-3 py-2 font-semibold text-foreground border-b">Notifications</div>
                                <div id="notification-items-container">
                                     <p class="p-4 text-muted-foreground text-center text-xs">Loading...</p>
                                     <?php /* Notification items will be added here by JS */ ?>
                                </div>
                                 <div class="p-2 border-t text-center">
                                     <button id="mark-all-read-button" class="text-xs text-primary hover:underline disabled:opacity-50" disabled>Mark all as read</button>
                                 </div>
                            </div>
                        </div>
                        <?php // --- End Notification Dropdown --- ?>

                        <button id="theme-toggle" type="button" class="inline-flex items-center justify-center whitespace-nowrap rounded-md text-sm font-medium ring-offset-background transition-colors focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 disabled:pointer-events-none disabled:opacity-50 hover:bg-accent hover:text-accent-foreground h-10 w-10">
                            <svg id="theme-toggle-sun-icon" xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="h-5 w-5"><circle cx="12" cy="12" r="4"/><path d="M12 2v2"/><path d="M12 20v2"/><path d="m4.93 4.93 1.41 1.41"/><path d="m17.66 17.66 1.41 1.41"/><path d="M2 12h2"/><path d="M20 12h2"/><path d="m6.34 17.66-1.41 1.41"/><path d="m19.07 4.93-1.41 1.41"/></svg>
                            <svg id="theme-toggle-moon-icon" xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="hidden h-5 w-5"><path d="M12 3a6 6 0 0 0 9 9 9 9 0 1 1-9-9Z"/></svg>
                            <span class="sr-only">Toggle theme</span>
                        </button>

                         <div class="flex items-center space-x-2">
                             <?php if (!empty($_SESSION['user']['picture_url'])): ?>
                                <a href="<?php echo BASE_URL; ?>/profile" title="Your Profile">
                                     <img src="<?php echo htmlspecialchars($_SESSION['user']['picture_url']); ?>" alt="Profile Picture" class="w-8 h-8 rounded-full border hover:opacity-80 transition-opacity">
                                </a>
                             <?php endif; ?>
                             <a href="<?php echo BASE_URL; ?>/logout" class="inline-flex items-center justify-center whitespace-nowrap rounded-md text-sm font-medium ring-offset-background transition-colors focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 disabled:pointer-events-none disabled:opacity-50 border border-input bg-background hover:bg-accent hover:text-accent-foreground h-9 px-3">
                                Logout
                             </a>
                        </div>


                    <?php else: ?>
                        <button id="theme-toggle-logged-out" type="button" class="inline-flex items-center justify-center whitespace-nowrap rounded-md text-sm font-medium ring-offset-background transition-colors focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 disabled:pointer-events-none disabled:opacity-50 hover:bg-accent hover:text-accent-foreground h-10 w-10">
                             <svg id="theme-toggle-sun-icon-logged-out" xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="h-5 w-5"><circle cx="12" cy="12" r="4"/><path d="M12 2v2"/><path d="M12 20v2"/><path d="m4.93 4.93 1.41 1.41"/><path d="m17.66 17.66 1.41 1.41"/><path d="M2 12h2"/><path d="M20 12h2"/><path d="m6.34 17.66-1.41 1.41"/><path d="m19.07 4.93-1.41 1.41"/></svg>
                             <svg id="theme-toggle-moon-icon-logged-out" xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="hidden h-5 w-5"><path d="M12 3a6 6 0 0 0 9 9 9 9 0 1 1-9-9Z"/></svg>
                            <span class="sr-only">Toggle theme</span>
                        </button>

                        <a href="<?php echo BASE_URL; ?>/auth/google" class="inline-flex items-center justify-center whitespace-nowrap rounded-md text-sm font-medium ring-offset-background transition-colors focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 disabled:pointer-events-none disabled:opacity-50 bg-primary text-primary-foreground hover:bg-primary/90 h-9 px-3">
                            Login with Google
                        </a>
                    <?php endif; ?>
                </div>
            </nav>
        </header>

        <main class="flex-1 container mx-auto p-4 md:p-6 lg:p-8">
            <?php if (isset($_SESSION['flash_message'])): ?>
                <?php
                    $message = $_SESSION['flash_message'];
                    unset($_SESSION['flash_message']); // Clear message after displaying
                    $isSuccess = ($message['type'] === 'success');
                    $messageTypeClass = $isSuccess
                        ? 'bg-green-100 border-green-400 text-green-700 dark:bg-green-900/30 dark:border-green-600/50 dark:text-green-300' // Success styles
                        : 'bg-red-100 border-red-400 text-red-700 dark:bg-red-900/30 dark:border-red-600/50 dark:text-red-300';      // Error styles
                    $iconPath = $isSuccess
                        ? 'M9 12.75 11.25 15 15 9.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z' // Check circle
                        : 'M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126ZM12 15.75h.007v.008H12v-.008Z'; // Exclamation triangle
                ?>
                <div class="border <?php echo $messageTypeClass; ?> px-4 py-3 rounded-md relative mb-4 flex items-start" role="alert">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-5 h-5 mr-2 mt-0.5 flex-shrink-0">
                      <path stroke-linecap="round" stroke-linejoin="round" d="<?php echo $iconPath; ?>" />
                    </svg>
                    <div>
                        <strong class="font-semibold"><?php echo ucfirst($message['type']); ?>!</strong>
                        <span class="block sm:inline ml-1"><?php echo htmlspecialchars($message['text']); ?></span>
                    </div>
                    <button type="button" class="ml-auto -mx-1.5 -my-1.5 p-1.5 inline-flex items-center justify-center h-8 w-8 <?php echo $isSuccess ? 'hover:bg-green-200 dark:hover:bg-green-900/50' : 'hover:bg-red-200 dark:hover:bg-red-900/50'; ?> rounded-lg focus:ring-2 focus:ring-offset-2 <?php echo $isSuccess ? 'focus:ring-green-400' : 'focus:ring-red-400'; ?>" onclick="this.parentElement.remove();" aria-label="Close">
                        <span class="sr-only">Close</span>
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" class="w-5 h-5"><path d="M6.28 5.22a.75.75 0 0 0-1.06 1.06L8.94 10l-3.72 3.72a.75.75 0 1 0 1.06 1.06L10 11.06l3.72 3.72a.75.75 0 1 0 1.06-1.06L11.06 10l3.72-3.72a.75.75 0 0 0-1.06-1.06L10 8.94 6.28 5.22Z" /></svg>
                   </button>
                </div>
            <?php endif; ?>

            <?php echo $content; ?>
        </main>

        <footer class="py-6 md:px-8 md:py-0 border-t border-border/40 bg-background">
            <div class="container mx-auto flex flex-col items-center justify-between gap-4 md:h-24 md:flex-row px-4">
                <p class="text-balance text-center text-sm leading-loose text-muted-foreground md:text-left">
                    Bailanysta © <?php echo date('Y'); ?>. Built with PHP & Tailwind CSS.
                </p>
            </div>
        </footer>

    </div>

</body>
</html>