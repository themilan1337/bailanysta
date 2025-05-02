<?php
   $theme = $_COOKIE['ui-theme'] ?? 'light';
   if ($theme !== 'light' && $theme !== 'dark') { $theme = 'light'; }
   $isLoggedIn = isset($_SESSION['logged_in']) && $_SESSION['logged_in'] === true;
   
   // Determine Header Avatar Source
   $headerAvatarSrc = 'https://via.placeholder.com/32/cccccc/969696?text='; // Default placeholder
   if ($isLoggedIn && !empty($_SESSION['user']['picture_url'])) {
       if (str_starts_with($_SESSION['user']['picture_url'], '/uploads/users/')) {
            $headerAvatarSrc = BASE_URL . $_SESSION['user']['picture_url']; // Local path
       }
        // Optional fallback if needed
        // elseif (str_starts_with($_SESSION['user']['picture_url'], 'http')) { $headerAvatarSrc = $_SESSION['user']['picture_url']; }
   }
   ?>
<!doctype html>
<html lang="en" class="<?php echo $theme; ?>">
   <head>
      <meta charset="UTF-8">
      <meta name="viewport" content="width=device-width, initial-scale=1.0">
      <title><?php echo isset($pageTitle) ? htmlspecialchars($pageTitle) . ' - Bailanysta' : 'Bailanysta'; ?></title>
      <meta name="description" content="Bailanysta - Connect and Share">
      <script>
         const BASE_URL = '<?php echo rtrim(BASE_URL, '/'); ?>';
         <?php if ($isLoggedIn && isset($_SESSION['user']['id'])): ?>
             const CURRENT_USER_ID = <?php echo (int)$_SESSION['user']['id']; ?>;
             const SESSION_USER_PICTURE = '<?php echo htmlspecialchars($_SESSION['user']['picture_url'] ?? '', ENT_QUOTES, 'UTF-8'); ?>';
         <?php else: ?>
             const CURRENT_USER_ID = null;
             const SESSION_USER_PICTURE = null;
         <?php endif; ?>
      </script>
      <?php echo vite_assets(['resources/css/app.css', 'resources/js/app.js']); ?>
   </head>
   <body class="bg-background text-foreground font-sans antialiased">
      <div class="min-h-screen flex flex-col">
         <header class="sticky top-0 z-50 w-full border-b border-border/40 bg-background/95 backdrop-blur supports-[backdrop-filter]:bg-background/60">
            <nav class="container mx-auto h-14 flex items-center justify-between px-4">
               <a href="<?php echo BASE_URL; ?>/" class="mr-4 flex items-center space-x-2 flex-shrink-0">
                <img src="https://em-content.zobj.net/source/apple/96/rocket_1f680.png" alt="" class="size-5">
                <span class="font-bold text-lg">
                Bailanysta
                </span>
               </a>
               <div class="flex items-center space-x-1 md:space-x-2">
                  <div class="hidden md:flex items-center space-x-1">
                     <a href="<?php echo BASE_URL; ?>/" class="text-sm font-medium text-muted-foreground transition-colors hover:text-primary px-3 py-2 rounded-md">Feed</a>
                     <?php if ($isLoggedIn): ?>
                     <a href="<?php echo BASE_URL; ?>/profile" class="text-sm font-medium text-muted-foreground transition-colors hover:text-primary px-3 py-2 rounded-md">Profile</a>
                     <?php endif; ?>
                  </div>
                  <div class="flex items-center space-x-1 md:space-x-2">
                     <?php if ($isLoggedIn): ?>
                     <div class="relative" id="notification-dropdown-container">
                        <button id="notification-toggle-button" type="button" aria-label="View notifications" class="cursor-pointer relative inline-flex items-center justify-center whitespace-nowrap rounded-md text-sm font-medium ring-offset-background transition-colors focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 disabled:pointer-events-none disabled:opacity-50 hover:bg-accent hover:text-accent-foreground h-9 w-9 md:h-10 md:w-10">
                           <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="h-5 w-5">
                              <path stroke-linecap="round" stroke-linejoin="round" d="M14.857 17.082a23.848 23.848 0 0 0 5.454-1.31A8.967 8.967 0 0 1 18 9.75V9A6 6 0 0 0 6 9v.75a8.967 8.967 0 0 1-2.312 6.022c1.733.64 3.56 1.017 5.454 1.31m5.714 0a24.255 24.255 0 0 1-5.714 0m5.714 0a3 3 0 1 1-5.714 0" />
                           </svg>
                           <span id="notification-count-badge" class="absolute top-1 right-1 inline-flex items-center justify-center px-1.5 py-0.5 text-xs font-bold leading-none text-red-100 transform translate-x-1/2 -translate-y-1/2 bg-red-600 rounded-full hidden">0</span>
                        </button>
                        <div id="notification-list" class="hidden absolute right-0 mt-2 w-80 max-h-[70vh] overflow-y-auto bg-popover border rounded-md shadow-lg z-20 py-1 text-sm">
                           <div class="px-3 py-2 font-semibold text-foreground border-b">Notifications</div>
                           <div id="notification-items-container">
                              <p class="p-4 text-muted-foreground text-center text-xs">Loading...</p>
                           </div>
                           <div class="p-2 border-t text-center"><button id="mark-all-read-button" class="text-xs cursor-pointer text-primary hover:underline disabled:opacity-50" disabled>Mark all as read</button></div>
                        </div>
                     </div>
                     <button id="theme-toggle" type="button" class="inline-flex items-center justify-center whitespace-nowrap rounded-md text-sm font-medium ring-offset-background transition-colors focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 disabled:pointer-events-none disabled:opacity-50 hover:bg-accent hover:text-accent-foreground h-9 w-9 md:h-10 md:w-10">
                        <svg id="theme-toggle-sun-icon" xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="cursor-pointer h-5 w-5">
                           <circle cx="12" cy="12" r="4"/>
                           <path d="M12 2v2"/>
                           <path d="M12 20v2"/>
                           <path d="m4.93 4.93 1.41 1.41"/>
                           <path d="m17.66 17.66 1.41 1.41"/>
                           <path d="M2 12h2"/>
                           <path d="M20 12h2"/>
                           <path d="m6.34 17.66-1.41 1.41"/>
                           <path d="m19.07 4.93-1.41 1.41"/>
                        </svg>
                        <svg id="theme-toggle-moon-icon" xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="cursor-pointer hidden h-5 w-5">
                           <path d="M12 3a6 6 0 0 0 9 9 9 9 0 1 1-9-9Z"/>
                        </svg>
                        <span class="sr-only">Toggle theme</span>
                     </button>
                     <a href="<?php echo BASE_URL; ?>/profile" title="Your Profile" class="hidden md:block">
                     <img src="<?php echo htmlspecialchars($headerAvatarSrc); ?>" alt="Profile Picture" class="w-8 h-8 rounded-full border hover:opacity-80 transition-opacity">
                     </a>
                     <a href="<?php echo BASE_URL; ?>/logout" class="hidden md:inline-flex items-center justify-center whitespace-nowrap rounded-md text-sm font-medium ring-offset-background transition-colors focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 disabled:pointer-events-none disabled:opacity-50 border border-input bg-background hover:bg-accent hover:text-accent-foreground h-9 px-3">Logout</a>
                     <?php else: ?>
                     <button id="theme-toggle-logged-out" type="button" class="inline-flex items-center justify-center whitespace-nowrap rounded-md text-sm font-medium ring-offset-background transition-colors focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 disabled:pointer-events-none disabled:opacity-50 hover:bg-accent hover:text-accent-foreground h-9 w-9 md:h-10 md:w-10">
                        <svg id="theme-toggle-sun-icon-logged-out" xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="h-5 w-5">
                           <circle cx="12" cy="12" r="4"/>
                           <path d="M12 2v2"/>
                           <path d="M12 20v2"/>
                           <path d="m4.93 4.93 1.41 1.41"/>
                           <path d="m17.66 17.66 1.41 1.41"/>
                           <path d="M2 12h2"/>
                           <path d="M20 12h2"/>
                           <path d="m6.34 17.66-1.41 1.41"/>
                           <path d="m19.07 4.93-1.41 1.41"/>
                        </svg>
                        <svg id="theme-toggle-moon-icon-logged-out" xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="hidden h-5 w-5">
                           <path d="M12 3a6 6 0 0 0 9 9 9 9 0 1 1-9-9Z"/>
                        </svg>
                        <span class="sr-only">Toggle theme</span>
                     </button>
                     <a href="<?php echo BASE_URL; ?>/auth/google" class="inline-flex items-center justify-center whitespace-nowrap rounded-full text-sm font-medium ring-offset-background transition-colors focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 disabled:pointer-events-none disabled:opacity-50 bg-primary text-primary-foreground hover:bg-primary/90 h-9 px-3">Login with Google</a>
                     <?php endif; ?>
                  </div>
                  <button id="mobile-menu-button" type="button" class="md:hidden inline-flex items-center justify-center whitespace-nowrap rounded-md text-sm font-medium ring-offset-background transition-colors focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 disabled:pointer-events-none disabled:opacity-50 hover:bg-accent hover:text-accent-foreground h-9 w-9 p-1.5">
                     <span class="sr-only">Open main menu</span>
                     <svg id="menu-icon-open" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="block h-6 w-6">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6.75h16.5M3.75 12h16.5m-16.5 5.25h16.5" />
                     </svg>
                     <svg id="menu-icon-close" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="hidden h-6 w-6">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" />
                     </svg>
                  </button>
               </div>
            </nav>
            <div id="mobile-menu" class="hidden md:hidden absolute top-14 left-0 right-0 z-40 bg-background border-b border-border shadow-md">
               <div class="space-y-1 px-2 pb-3 pt-2">
                  <a href="<?php echo BASE_URL; ?>/" class="block rounded-md px-3 py-2 text-base font-medium text-muted-foreground hover:bg-accent hover:text-accent-foreground">Feed</a>
                  <?php if ($isLoggedIn): ?>
                  <a href="<?php echo BASE_URL; ?>/profile" class="block rounded-md px-3 py-2 text-base font-medium text-muted-foreground hover:bg-accent hover:text-accent-foreground">Profile</a>
                  <a href="<?php echo BASE_URL; ?>/logout" class="block rounded-md px-3 py-2 text-base font-medium text-muted-foreground hover:bg-accent hover:text-accent-foreground">Logout</a>
                  <?php endif; ?>
               </div>
            </div>
         </header>
         <main class="flex-1 container mx-auto p-4 md:p-6 lg:p-8">
            <!--<?php if (isset($_SESSION['flash_message'])): ?>
               <?php $message = $_SESSION['flash_message']; unset($_SESSION['flash_message']); $isSuccess = ($message['type'] === 'success'); $messageTypeClass = $isSuccess ? 'bg-green-100 border-green-400 text-green-700 dark:bg-green-900/30 dark:border-green-600/50 dark:text-green-300' : 'bg-red-100 border-red-400 text-red-700 dark:bg-red-900/30 dark:border-red-600/50 dark:text-red-300'; $iconPath = $isSuccess ? 'M9 12.75 11.25 15 15 9.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z' : 'M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126ZM12 15.75h.007v.008H12v-.008Z'; ?>
               <div class="border <?php echo $messageTypeClass; ?> px-4 py-3 rounded-md relative mb-4 flex items-start" role="alert">
                   <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-5 h-5 mr-2 mt-0.5 flex-shrink-0"><path stroke-linecap="round" stroke-linejoin="round" d="<?php echo $iconPath; ?>" /></svg>
                   <div><strong class="font-semibold"><?php echo ucfirst($message['type']); ?>!</strong> <span class="block sm:inline ml-1"><?php echo htmlspecialchars($message['text']); ?></span></div>
                   <button type="button" class="ml-auto -mx-1.5 -my-1.5 p-1.5 inline-flex items-center justify-center h-8 w-8 <?php echo $isSuccess ? 'hover:bg-green-200 dark:hover:bg-green-900/50' : 'hover:bg-red-200 dark:hover:bg-red-900/50'; ?> rounded-lg focus:ring-2 focus:ring-offset-2 <?php echo $isSuccess ? 'focus:ring-green-400' : 'focus:ring-red-400'; ?>" onclick="this.parentElement.remove();" aria-label="Close"><span class="sr-only">Close</span><svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" class="w-5 h-5"><path d="M6.28 5.22a.75.75 0 0 0-1.06 1.06L8.94 10l-3.72 3.72a.75.75 0 1 0 1.06 1.06L10 11.06l3.72 3.72a.75.75 0 1 0 1.06-1.06L11.06 10l3.72-3.72a.75.75 0 0 0-1.06-1.06L10 8.94 6.28 5.22Z" /></svg></button>
               </div>
               <?php endif; ?> -->
            <?php if (isset($_GET['deleted']) && $_GET['deleted'] === 'true'): ?>
            <div class="border bg-green-50 border-green-500/40 text-green-800 dark:bg-green-950/30 dark:border-green-600/60 dark:text-green-200 px-4 py-3 rounded-md relative mb-4 flex items-start shadow-sm" role="alert">
               <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-5 h-5 mr-2 mt-0.5 flex-shrink-0 text-green-600 dark:text-green-400">
                  <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
               </svg>
               <div><strong class="font-semibold">Success!</strong><span class="block sm:inline ml-1">Your account has been deleted.</span></div>
               <button type="button" class="ml-auto -mx-1.5 -my-1.5 p-1.5 inline-flex items-center justify-center h-8 w-8 hover:bg-green-100 dark:hover:bg-green-900/50 rounded-lg focus:outline-none focus:ring-2 focus:ring-ring focus:ring-offset-2" onclick="this.parentElement.remove();" aria-label="Close">
                  <span class="sr-only">Close</span>
                  <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" class="w-5 h-5">
                     <path d="M6.28 5.22a.75.75 0 0 0-1.06 1.06L8.94 10l-3.72 3.72a.75.75 0 1 0 1.06 1.06L10 11.06l3.72 3.72a.75.75 0 1 0 1.06-1.06L11.06 10l3.72-3.72a.75.75 0 0 0-1.06-1.06L10 8.94 6.28 5.22Z" />
                  </svg>
               </button>
            </div>
            <?php endif; ?>
            <?php echo $content; ?>
         </main>
         <footer class="py-6 md:px-8 md:py-0 border-t border-border/40 bg-background">
            <div class="container mx-auto flex flex-col items-center justify-between gap-4 md:h-24 md:flex-row px-4">
               <p class="text-balance text-center text-sm leading-loose text-muted-foreground md:text-left"> Bailanysta © <?php echo date('Y'); ?>. Built with PHP MVC & Tailwind CSS. By Milan Gorislavets</p>
            </div>
         </footer>
      </div>
   </body>
</html>