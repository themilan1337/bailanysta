<?php
$isLoggedIn = $isLoggedIn ?? (isset($_SESSION['logged_in']) && $_SESSION['logged_in'] === true);
$currentUserId = $isLoggedIn ? ($_SESSION['user']['id'] ?? null) : null;
$isAuthor = ($currentUserId !== null && isset($post['author_id']) && $currentUserId === $post['author_id']);

// Determine Author Avatar Source
$authorAvatarSrc = 'https://via.placeholder.com/40/cccccc/969696?text='; // Default
if (!empty($post['author_picture_url'])) {
    if (str_starts_with($post['author_picture_url'], '/uploads/users/')) {
         $authorAvatarSrc = BASE_URL . $post['author_picture_url']; // Local path
    }
    // elseif (str_starts_with($post['author_picture_url'], 'http')) { $authorAvatarSrc = $post['author_picture_url']; } // Optional fallback
}
?>
<article class="bg-card border rounded-lg overflow-hidden flex flex-col" data-post-container-id="<?php echo $post['post_id']; ?>" id="post-<?php echo $post['post_id']; ?>">
     <div class="p-4 flex items-start space-x-3">
         <a href="<?php echo BASE_URL . '/profile/' . $post['author_id']; ?>">
             <img src="<?php echo htmlspecialchars($authorAvatarSrc); ?>"
                  alt="<?php echo htmlspecialchars($post['author_name']); ?>'s profile picture"
                  class="w-10 h-10 rounded-full border bg-muted hover:opacity-80 transition-opacity">
         </a>
         <div class="flex-grow">
             <a href="<?php echo BASE_URL . '/profile/' . $post['author_id']; ?>" class="font-semibold text-foreground hover:underline">
                 <?php echo htmlspecialchars($post['author_name']); ?>
             </a>
             <p class="text-xs text-muted-foreground">
                 <?php echo htmlspecialchars($post['time_ago']); ?>
             </p>
         </div>
          <?php if ($isAuthor): ?>
              <div class="relative post-options-dropdown">
                 <button type="button" aria-label="Post options" class="post-options-button p-1 rounded-full text-muted-foreground hover:bg-accent hover:text-foreground focus:outline-none focus:ring-1 focus:ring-ring">
                     <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" class="w-5 h-5"><path d="M10 3a1.5 1.5 0 1 1 0 3 1.5 1.5 0 0 1 0-3ZM10 8.5a1.5 1.5 0 1 1 0 3 1.5 1.5 0 0 1 0-3ZM11.5 15.5a1.5 1.5 0 1 0-3 0 1.5 1.5 0 0 0 3 0Z" /></svg>
                 </button>
                 <div class="post-options-menu hidden absolute right-0 mt-1 w-36 bg-popover border rounded-md shadow-lg z-10 py-1 text-sm">
                    <button type="button" class="post-edit-button block w-full text-left px-3 py-1.5 text-foreground hover:bg-accent">Edit Post</button>
                    <button type="button" class="post-delete-button block w-full text-left px-3 py-1.5 text-destructive hover:bg-destructive/10">Delete Post</button>
                 </div>
              </div>
          <?php endif; ?>
     </div>

    <div class="post-content-area px-4 <?php echo !empty($post['content']) || !empty($post['image_url']) ? 'pb-4' : 'pb-1'; ?>">
        <?php if (!empty($post['content'])): ?>
        <div class="post-display-content prose prose-sm dark:prose-invert max-w-none">
             <?php echo nl2br(htmlspecialchars($post['content'])); ?>
        </div>
         <?php endif; ?>
        <?php if ($isAuthor): ?>
            <form class="post-edit-form hidden mt-2" data-post-id="<?php echo $post['post_id']; ?>">
                 <textarea name="content" rows="5" class="w-full p-2 border border-input bg-background rounded-md focus:ring-1 focus:ring-ring focus:outline-none resize-y placeholder:text-muted-foreground text-sm" required><?php echo htmlspecialchars($post['content'] ?? ''); ?></textarea>
                 <div class="flex justify-end items-center space-x-2 mt-3">
                      <span class="edit-status text-xs text-muted-foreground"></span>
                      <button type="button" class="edit-cancel-button inline-flex items-center justify-center whitespace-nowrap rounded-md text-sm font-medium ring-offset-background transition-colors focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 disabled:pointer-events-none disabled:opacity-50 border border-input bg-background hover:bg-accent hover:text-accent-foreground h-8 px-3">Cancel</button>
                     <button type="submit" class="edit-save-button inline-flex items-center justify-center whitespace-nowrap rounded-md text-sm font-medium ring-offset-background transition-colors focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 disabled:pointer-events-none disabled:opacity-50 bg-primary text-primary-foreground hover:bg-primary/90 h-8 px-3">Save Changes</button>
                 </div>
            </form>
        <?php endif; ?>
    </div>

     <?php if (!empty($post['image_url'])): ?>
         <div class="post-display-image bg-muted border-t dark:border-gray-700 max-h-[60vh] overflow-hidden">
              <a href="<?php echo BASE_URL . htmlspecialchars($post['image_url']); ?>" target="_blank" rel="noopener noreferrer" title="View full image">
                   <img src="<?php echo BASE_URL . htmlspecialchars($post['image_url']); ?>" alt="Post image" class="w-full h-auto object-contain display-block" loading="lazy">
              </a>
         </div>
     <?php endif; ?>

     <div class="px-4 pt-3 pb-1 border-t flex items-center justify-between text-sm text-muted-foreground">
          <div class="flex space-x-4">
               <span class="like-count-display"><?php echo $post['like_count']; ?> <?php echo ($post['like_count'] == 1) ? 'Like' : 'Likes'; ?></span>
               <span class="comment-count-display"><?php echo $post['comment_count']; ?> <?php echo ($post['comment_count'] == 1) ? 'Comment' : 'Comments'; ?></span>
          </div>
     </div>
     <div class="p-2 border-t grid grid-cols-2 gap-1">
         <button data-post-id="<?php echo $post['post_id']; ?>" aria-label="<?php echo $post['user_liked'] ? 'Unlike' : 'Like'; ?> post" aria-pressed="<?php echo $post['user_liked'] ? 'true' : 'false'; ?>" class="like-button flex items-center text-sm cursor-pointer  justify-center space-x-1.5 py-1.5 px-3 rounded-md hover:bg-accent transition-colors <?php echo $post['user_liked'] ? 'text-red-500 font-medium' : 'text-muted-foreground'; ?> <?php echo !$isLoggedIn ? 'cursor-not-allowed opacity-60' : ''; ?>" <?php echo !$isLoggedIn ? 'disabled title="Login to like posts"' : ''; ?>>
             <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="like-icon-outline w-5 h-5" style="display: <?php echo $post['user_liked'] ? 'none' : 'block'; ?>;"><path stroke-linecap="round" stroke-linejoin="round" d="M21 8.25c0-2.485-2.099-4.5-4.688-4.5-1.935 0-3.597 1.126-4.312 2.733-.715-1.607-2.377-2.733-4.313-2.733C5.1 3.75 3 5.765 3 8.25c0 7.22 9 12 9 12s9-4.78 9-12z" /></svg>
             <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" class="like-icon-filled w-5 h-5" style="display: <?php echo $post['user_liked'] ? 'block' : 'none'; ?>;"><path d="M9.653 16.915l-.005-.003-.019-.01a20.759 20.759 0 01-1.162-.682 22.045 22.045 0 01-2.582-1.9C4.045 12.733 2 10.352 2 7.5a4.5 4.5 0 018-2.828A4.5 4.5 0 0118 7.5c0 2.852-2.044 5.233-3.885 6.82a22.049 22.049 0 01-3.744 2.582l-.019.01-.005.003h-.002a.739.739 0 01-.69.001l-.002-.001z" /></svg>
             <span>Like <span class="like-count font-normal"><?php echo $post['like_count']; ?></span></span>
         </button>
         <button data-post-id="<?php echo $post['post_id']; ?>" aria-expanded="false" aria-controls="comment-section-<?php echo $post['post_id']; ?>" class="comment-toggle-button flex items-center text-sm cursor-pointer justify-center space-x-1.5 py-1.5 px-3 rounded-md text-muted-foreground hover:bg-accent transition-colors <?php echo !$isLoggedIn ? 'cursor-not-allowed opacity-60' : ''; ?>" <?php echo !$isLoggedIn ? 'disabled title="Login to comment"' : ''; ?>>
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-5 h-5"><path stroke-linecap="round" stroke-linejoin="round" d="M2.25 12.76c0 1.6 1.123 2.994 2.707 3.227 1.087.16 2.185.283 3.293.369V21l4.076-4.076a1.526 1.526 0 011.037-.443 48.282 48.282 0 005.68-.494c1.584-.233 2.707-1.626 2.707-3.228V6.741c0-1.602-1.123-2.995-2.707-3.228A48.394 48.394 0 0012 3c-2.392 0-4.744.175-7.043.513C3.373 3.746 2.25 5.14 2.25 6.741v6.018z" /></svg>
            <span>Comment</span>
        </button>
     </div>
     <div id="comment-section-<?php echo $post['post_id']; ?>" class="comment-section border-t px-4 py-3 space-y-3 hidden">
        <div class="comment-list space-y-3 text-sm">
             <div class="loading-comments flex justify-center items-center py-3 text-muted-foreground">
                 <svg class="animate-spin h-5 w-5 mr-2" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                 <span>Loading...</span>
             </div>
        </div>
         <?php if ($isLoggedIn): ?>
             <?php
                // Determine Current User Avatar Source for comment form
                $currentUserCommentAvatarSrc = 'https://via.placeholder.com/32/cccccc/969696?text='; // Default
                if (!empty($_SESSION['user']['picture_url']) && str_starts_with($_SESSION['user']['picture_url'], '/uploads/users/')) {
                    $currentUserCommentAvatarSrc = BASE_URL . $_SESSION['user']['picture_url'];
                }
             ?>
             <form class="add-comment-form flex items-start space-x-2 pt-3" data-post-id="<?php echo $post['post_id']; ?>">
                 <img src="<?php echo htmlspecialchars($currentUserCommentAvatarSrc); ?>" alt="Your profile picture" class="w-8 h-8 rounded-full border bg-muted flex-shrink-0 mt-1">
                 <div class="flex-grow">
                     <textarea name="content" rows="1" class="w-full p-2 border border-input bg-background rounded-md focus:ring-1 focus:ring-ring focus:outline-none resize-none placeholder:text-muted-foreground text-sm" placeholder="Add a comment..."></textarea>
                     <button type="submit" class="cursor-pointer inline-flex items-center justify-center whitespace-nowrap rounded-md text-sm font-medium ring-offset-background transition-colors focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 disabled:pointer-events-none disabled:opacity-50 bg-primary text-primary-foreground hover:bg-primary/90 h-7 px-3 mt-1 float-right">Post</button>
                 </div>
             </form>
         <?php endif; ?>
     </div>
 </article>