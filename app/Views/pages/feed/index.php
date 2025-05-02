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
            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none"><svg class="h-5 w-5 text-muted-foreground" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true"><path fill-rule="evenodd" d="M9 3.5a5.5 5.5 0 1 0 0 11 5.5 5.5 0 0 0 0-11ZM2 9a7 7 0 1 1 12.452 4.391l3.328 3.329a.75.75 0 1 1-1.06 1.06l-3.329-3.328A7 7 0 0 1 2 9Z" clip-rule="evenodd" /></svg></div>
            <input type="search" name="search" id="search-input" class="block w-full pl-10 pr-3 py-2 border border-input bg-background rounded-xl leading-5 placeholder-muted-foreground focus:outline-none focus:ring-1 focus:ring-ring focus:border-ring sm:text-sm" placeholder="Search posts...">
            <div id="search-spinner" class="absolute inset-y-0 right-0 pr-3 flex items-center hidden"><svg class="animate-spin h-4 w-4 text-primary" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg></div>
        </div>
         <p id="search-error" class="text-xs text-destructive mt-1 hidden"></p>
    </div>

    <?php if ($isLoggedIn && $currentUser): ?>
        <div class="bg-card border rounded-lg p-4 mb-6">
             <form action="<?php echo BASE_URL; ?>/profile/posts" method="POST" enctype="multipart/form-data" id="create-post-form-feed">
                <h2 class="text-lg font-semibold mb-3 text-foreground">Create a New Post</h2>
                
                 <textarea name="content" rows="3" class="w-full p-2 border border-input bg-background rounded-xl focus:ring-1 focus:ring-ring focus:outline-none resize-none placeholder:text-muted-foreground text-sm" placeholder="What's on your mind, <?php echo htmlspecialchars($currentUser['name']); ?>? Or generate an idea!"></textarea>
                <div class="flex items-end space-x-2 mt-3">
                    <div class="flex-grow">
                        <label for="ai_prompt_feed" class="block text-xs font-medium text-muted-foreground mb-1">Optional AI Prompt Context:</label>
                        <input type="text" id="ai_prompt_feed" name="ai_prompt" class="w-full px-4 py-2 border border-input bg-background rounded-md focus:ring-1 focus:ring-ring focus:outline-none placeholder:text-muted-foreground text-xs" placeholder="e.g., my new puppy">
                    </div>
                    <button type="button" form="create-post-form-feed" class="generate-idea-button inline-flex items-center text-xs font-medium rounded-md ring-offset-background transition-colors focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 cursor-pointer disabled:pointer-events-none disabled:opacity-50 border border-input bg-background hover:bg-accent hover:text-accent-foreground py-2 px-4 space-x-1 flex-shrink-0"><svg class="size-4 mr-2" xmlns="http://www.w3.org/2000/svg" width="32" height="32" viewBox="0 0 24 24"><path fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m10 7l-.516 1.394c-.676 1.828-1.014 2.742-1.681 3.409s-1.581 1.005-3.409 1.681L3 14l1.394.516c1.828.676 2.742 1.015 3.409 1.681s1.005 1.581 1.681 3.409L10 21l.516-1.394c.676-1.828 1.015-2.742 1.681-3.409s1.581-1.005 3.409-1.681L17 14l-1.394-.516c-1.828-.676-2.742-1.014-3.409-1.681s-1.005-1.581-1.681-3.409zm8-4l-.221.597c-.29.784-.435 1.176-.72 1.461c-.286.286-.678.431-1.462.72L15 6l.598.221c.783.29 1.175.435 1.46.72c.286.286.431.678.72 1.462L18 9l.221-.597c.29-.784.435-1.176.72-1.461c.286-.286.678-.431 1.462-.72L21 6l-.598-.221c-.783-.29-1.175-.435-1.46-.72c-.286-.286-.431-.678-.72-1.462z" color="currentColor"/></svg><span>Generate</span></button>
                </div>
                 <div class="mt-4"><label for="post_image_feed" class="block text-sm font-medium text-muted-foreground mb-1">Attach Image (Optional)</label><input type="file" name="post_image" id="post_image_feed" accept="image/jpeg, image/png, image/gif, image/webp" class="block w-full text-sm text-muted-foreground border border-input rounded-xl cursor-pointer bg-background focus:outline-none file:mr-4 file:py-2 file:px-4 file:rounded-xl file:border-0 file:text-sm file:font-semibold file:bg-primary file:text-primary-foreground hover:file:bg-primary/90"></div>
                 <div class="flex justify-end mt-3"><button type="submit" class="cursor-pointer inline-flex items-center justify-center whitespace-nowrap rounded-xl text-sm font-medium ring-offset-background transition-colors focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 disabled:pointer-events-none disabled:opacity-50 bg-primary text-primary-foreground hover:bg-primary/90 h-9 px-3">Create Post</button></div>
             </form>
        </div>
    <?php endif; ?>

    <div id="feed-posts-container" class="space-y-6">
        <?php // Loop through posts passed from controller (initial load) ?>
        <?php if (!empty($posts)): ?>
             <?php foreach ($posts as $post): ?>
                 <?php include __DIR__ . '/../../_partials/post_card.php'; ?>
             <?php endforeach; ?>
        <?php elseif(empty($error)): // Only show 'quiet' if no posts AND no error ?>
             <div class="text-center text-gray-500 dark:text-gray-400 mt-12 py-10">
                 <p class="text-lg">It's quiet here...</p>
                 <p>No posts have been made yet.</p>
                 <?php if ($isLoggedIn): ?><p>Why not be the first?</p>
                 <?php else: ?><p><a href="<?php echo BASE_URL; ?>/auth/google" class="text-blue-600 hover:underline dark:text-blue-400">Login</a> to start posting!</p><?php endif; ?>
             </div>
        <?php endif; ?>
    </div>

    <div id="loading-indicator" class="text-center py-10" style="display: none;">
        <p class="text-gray-500 dark:text-gray-400">Loading more posts...</p>
    </div>

</div>