<?php
$isLoggedIn = isset($_SESSION['logged_in']) && $_SESSION['logged_in'] === true;
$currentUser = $isLoggedIn ? ($_SESSION['user'] ?? null) : null;
?>

<div class="max-w-2xl mx-auto" id="feed-container">

    <?php if (!empty($error)): ?>
        <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative mb-6" role="alert">
            <strong class="font-bold">Error:</strong>
            <span class="block sm:inline"><?php echo htmlspecialchars($error); ?></span>
        </div>
    <?php endif; ?>

    <div class="mb-6">
        <label for="search-input" class="sr-only">Search Posts</label>
        <div class="relative">
            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                <svg class="h-5 w-5 text-muted-foreground" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                  <path fill-rule="evenodd" d="M9 3.5a5.5 5.5 0 1 0 0 11 5.5 5.5 0 0 0 0-11ZM2 9a7 7 0 1 1 12.452 4.391l3.328 3.329a.75.75 0 1 1-1.06 1.06l-3.329-3.328A7 7 0 0 1 2 9Z" clip-rule="evenodd" />
                </svg>
            </div>
            <input type="search" name="search" id="search-input"
                   class="block w-full pl-10 pr-3 py-2 border border-input bg-background rounded-md leading-5 placeholder-muted-foreground focus:outline-none focus:ring-1 focus:ring-ring focus:border-ring sm:text-sm"
                   placeholder="Search posts...">
            <div id="search-spinner" class="absolute inset-y-0 right-0 pr-3 flex items-center hidden">
                 <svg class="animate-spin h-4 w-4 text-primary" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                  </svg>
            </div>
        </div>
         <p id="search-error" class="text-xs text-destructive mt-1 hidden"></p>
    </div>

    <?php if ($isLoggedIn && $currentUser): ?>
        <div class="bg-card border rounded-lg p-4 mb-6 shadow-sm">
             <form action="<?php echo BASE_URL; ?>/profile/posts" method="POST">
                <h2 class="text-lg font-semibold mb-3 text-foreground">Create a New Post</h2>
                 <textarea name="content" rows="3"
                           class="w-full p-2 border border-input bg-background rounded-md focus:ring-1 focus:ring-ring focus:outline-none resize-none placeholder:text-muted-foreground text-sm"
                           placeholder="What's on your mind, <?php echo htmlspecialchars($currentUser['name']); ?>?"></textarea>
                 <div class="flex justify-end mt-3">
                     <button type="submit"
                             class="inline-flex items-center justify-center whitespace-nowrap rounded-md text-sm font-medium ring-offset-background transition-colors focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 disabled:pointer-events-none disabled:opacity-50 bg-primary text-primary-foreground hover:bg-primary/90 h-9 px-3">
                         Create Post
                     </button>
                 </div>
             </form>
        </div>
    <?php endif; ?>

    <div id="feed-posts-container" class="space-y-6">
        <?php if (empty($posts) && empty($error)): ?>
            <div class="text-center text-gray-500 dark:text-gray-400 mt-12 py-10">
                 <p class="text-lg">It's quiet here...</p>
                 <p>No posts have been made yet.</p>
                 <?php if ($isLoggedIn): ?><p>Why not be the first?</p>
                 <?php else: ?><p><a href="<?php echo BASE_URL; ?>/auth/google" class="text-blue-600 hover:underline dark:text-blue-400">Login</a> to start posting!</p><?php endif; ?>
             </div>
        <?php elseif(!empty($posts)): ?>
             <?php foreach ($posts as $post): ?>
                 <?php include __DIR__ . '/../../_partials/post_card.php'; // Corrected Path ?>
             <?php endforeach; ?>
        <?php endif; ?>
    </div>

    <div id="loading-indicator" class="text-center py-10" style="display: none;">
        <p class="text-gray-500 dark:text-gray-400">Loading more posts...</p>
    </div>

</div>