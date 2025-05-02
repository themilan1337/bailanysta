<?php
$isLoggedIn = isset($_SESSION['logged_in']) && $_SESSION['logged_in'] === true;
$viewerIsFollowing = $user['viewer_is_following'] ?? false;
$currentUserId = $isLoggedIn ? ($_SESSION['user']['id'] ?? null) : null;

// Determine Profile User Avatar Source
$profileAvatarSrc = 'https://upload.wikimedia.org/wikipedia/commons/7/7c/Profile_avatar_placeholder_large.png?20150327203541'; // Default
if (!empty($user['picture_url'])) {
    if (str_starts_with($user['picture_url'], '/uploads/users/')) {
         $profileAvatarSrc = BASE_URL . $user['picture_url']; // Local path
    }
     // Optional: Fallback to original URL if needed, otherwise default is used
    // elseif (str_starts_with($user['picture_url'], 'http')) { $profileAvatarSrc = $user['picture_url']; }
}
?>

<div class="max-w-3xl mx-auto space-y-8">

    <div class="bg-card border rounded-lg shadow-sm p-6 flex flex-col sm:flex-row items-center sm:items-start space-y-4 sm:space-y-0 sm:space-x-6">
        <img src="<?php echo htmlspecialchars($profileAvatarSrc); ?>"
             alt="<?php echo htmlspecialchars($user['name']); ?>'s profile picture"
             class="w-24 h-24 rounded-full border-4 border-background shadow-md flex-shrink-0">
        <div class="text-center sm:text-left flex-grow">
            <div class="flex flex-col sm:flex-row items-center justify-center sm:justify-between gap-2 mb-1">
                <h1 class="text-2xl font-bold text-foreground"><?php echo htmlspecialchars($user['name']); ?></h1>

                <?php if (!$isOwnProfile && $isLoggedIn): ?>
                     <button type="button"
                             data-user-id="<?php echo $user['id']; ?>"
                             class="follow-toggle-button cursor-pointer inline-flex items-center justify-center whitespace-nowrap rounded-md text-sm font-medium ring-offset-background transition-colors focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 disabled:pointer-events-none disabled:opacity-50 h-9 px-4 py-2 w-full sm:w-auto <?php echo $viewerIsFollowing ? 'border border-input bg-background hover:bg-accent hover:text-accent-foreground' : 'bg-primary text-primary-foreground hover:bg-primary/90'; ?>"
                             aria-pressed="<?php echo $viewerIsFollowing ? 'true' : 'false'; ?>">
                         <span class="follow-text"><?php echo $viewerIsFollowing ? 'Following' : 'Follow'; ?></span>
                         <span class="unfollow-text hidden">Unfollow</span>
                         <span class="loading-spinner hidden ml-2">
                             <svg class="animate-spin h-4 w-4 text-current" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                               <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                               <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                             </svg>
                         </span>
                     </button>
                <?php elseif (!$isLoggedIn && !$isOwnProfile): ?>
                     <a href="<?php echo BASE_URL; ?>/auth/google" class="inline-flex items-center justify-center whitespace-nowrap rounded-md text-sm font-medium ring-offset-background transition-colors focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 disabled:pointer-events-none disabled:opacity-50 bg-primary text-primary-foreground hover:bg-primary/90 h-9 px-4 py-2 w-full sm:w-auto opacity-70" title="Login to follow">
                           Follow
                     </a>
                <?php endif; ?>
            </div>

            <?php if (!empty($user['nickname'])): ?>
                 <p class="text-lg text-muted-foreground text-center sm:text-left">@<?php echo htmlspecialchars($user['nickname']); ?></p>
            <?php elseif ($isOwnProfile): ?>
                <p class="text-sm text-muted-foreground italic text-center sm:text-left">No nickname set</p>
            <?php endif; ?>

            <div class="flex items-center justify-center sm:justify-start space-x-4 mt-2 text-sm">
                <button type="button" data-list-type="followers" data-user-id="<?php echo $user['id']; ?>" class="show-follow-list-button cursor-pointer text-muted-foreground hover:text-primary hover:underline focus:outline-none">
                    <strong class="text-foreground"><?php echo $user['follower_count'] ?? 0; ?></strong> Followers
                </button>
                 <button type="button" data-list-type="following" data-user-id="<?php echo $user['id']; ?>" class="show-follow-list-button cursor-pointer text-muted-foreground hover:text-primary hover:underline focus:outline-none">
                     <strong class="text-foreground"><?php echo $user['following_count'] ?? 0; ?></strong> Following
                 </button>
            </div>

            <p class="text-muted-foreground text-xs mt-1 text-center sm:text-left">Joined: <?php echo date('M j, Y', strtotime($user['created_at'])); ?></p>
        </div>
    </div>

    <?php if ($isOwnProfile): ?>
        <div class="bg-card border rounded-lg shadow-sm p-6">
           <h2 class="text-xl font-semibold mb-4 text-foreground">Edit Profile</h2>
           <form action="<?php echo BASE_URL; ?>/profile/update" method="POST">
               <div class="mb-4">
                   <label for="nickname" class="block text-sm font-medium text-muted-foreground mb-1">Nickname</label>
                   <input type="text" id="nickname" name="nickname"
                          value="<?php echo htmlspecialchars($user['nickname'] ?? ''); ?>"
                          placeholder="e.g., CoolCoder_99"
                          maxlength="50"
                          pattern="^[a-zA-Z0-9_]{3,20}$"
                          title="3-20 characters, letters, numbers, underscores only."
                          class="w-full max-w-sm p-2 border border-input bg-background rounded-md focus:ring-1 focus:ring-ring focus:outline-none placeholder:text-muted-foreground text-sm">
                    <p class="text-xs text-muted-foreground mt-1">Unique nickname (3-20 chars, a-z, 0-9, _). Leave empty to remove.</p>
               </div>
               <div class="flex justify-start">
                    <button type="submit"
                            class="inline-flex items-center justify-center whitespace-nowrap rounded-md text-sm font-medium ring-offset-background transition-colors focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 disabled:pointer-events-none disabled:opacity-50 bg-primary text-primary-foreground hover:bg-primary/90 h-9 px-4">
                        Save Changes
                    </button>
               </div>
           </form>
        </div>

        <div class="bg-card border rounded-lg p-4 shadow-sm">
             <form action="<?php echo BASE_URL; ?>/profile/posts" method="POST" enctype="multipart/form-data" id="create-post-form-profile">
                 <div class="flex justify-between items-center mb-3">
                     <h2 class="text-lg font-semibold text-foreground">Create a New Post</h2>
                     <button type="button" form="create-post-form-profile" class="generate-idea-button cursor-pointer inline-flex items-center text-xs font-medium rounded-md ring-offset-background transition-colors focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 disabled:pointer-events-none disabled:opacity-50 border border-input bg-background hover:bg-accent hover:text-accent-foreground h-7 px-2 space-x-1 flex-shrink-0">
                        <svg class="size-4 mr-2" xmlns="http://www.w3.org/2000/svg" width="32" height="32" viewBox="0 0 24 24"><path fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m10 7l-.516 1.394c-.676 1.828-1.014 2.742-1.681 3.409s-1.581 1.005-3.409 1.681L3 14l1.394.516c1.828.676 2.742 1.015 3.409 1.681s1.005 1.581 1.681 3.409L10 21l.516-1.394c.676-1.828 1.015-2.742 1.681-3.409s1.581-1.005 3.409-1.681L17 14l-1.394-.516c-1.828-.676-2.742-1.014-3.409-1.681s-1.005-1.581-1.681-3.409zm8-4l-.221.597c-.29.784-.435 1.176-.72 1.461c-.286.286-.678.431-1.462.72L15 6l.598.221c.783.29 1.175.435 1.46.72c.286.286.431.678.72 1.462L18 9l.221-.597c.29-.784.435-1.176.72-1.461c.286-.286.678-.431 1.462-.72L21 6l-.598-.221c-.783-.29-1.175-.435-1.46-.72c-.286-.286-.431-.678-.72-1.462z" color="currentColor"/></svg>
                         <span>Generate an idea</span>
                     </button>
                 </div>
                 <textarea name="content" rows="4"
                           class="w-full p-2 border border-input bg-background rounded-md focus:ring-1 focus:ring-ring focus:outline-none resize-none placeholder:text-muted-foreground text-sm"
                           placeholder="Share something... You can also add context below and generate an idea!"></textarea>
                <div class="flex items-end space-x-2 mt-3">
                    <div class="flex-grow">
                        <label for="ai_prompt_feed" class="block text-xs font-medium text-muted-foreground mb-1">Optional AI Prompt Context:</label>
                        <input type="text" id="ai_prompt_feed" name="ai_prompt" class="w-full px-4 py-2 border border-input bg-background rounded-md focus:ring-1 focus:ring-ring focus:outline-none placeholder:text-muted-foreground text-xs" placeholder="e.g., my new puppy">
                    </div>
                    <button type="button" form="create-post-form-profile" class="generate-idea-button inline-flex items-center text-xs font-medium rounded-md ring-offset-background transition-colors focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 disabled:pointer-events-none disabled:opacity-50 border border-input bg-background hover:bg-accent hover:text-accent-foreground py-2 px-4 cursor-pointer space-x-1 flex-shrink-0">
                    <svg class="size-4 mr-2" xmlns="http://www.w3.org/2000/svg" width="32" height="32" viewBox="0 0 24 24"><path fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m10 7l-.516 1.394c-.676 1.828-1.014 2.742-1.681 3.409s-1.581 1.005-3.409 1.681L3 14l1.394.516c1.828.676 2.742 1.015 3.409 1.681s1.005 1.581 1.681 3.409L10 21l.516-1.394c.676-1.828 1.015-2.742 1.681-3.409s1.581-1.005 3.409-1.681L17 14l-1.394-.516c-1.828-.676-2.742-1.014-3.409-1.681s-1.005-1.581-1.681-3.409zm8-4l-.221.597c-.29.784-.435 1.176-.72 1.461c-.286.286-.678.431-1.462.72L15 6l.598.221c.783.29 1.175.435 1.46.72c.286.286.431.678.72 1.462L18 9l.221-.597c.29-.784.435-1.176.72-1.461c.286-.286.678-.431 1.462-.72L21 6l-.598-.221c-.783-.29-1.175-.435-1.46-.72c-.286-.286-.431-.678-.72-1.462z" color="currentColor"/></svg>
                        <span>Generate</span>
                    </button>
                </div>

                 <!-- <div class="mt-4">
                     <label for="post_image_profile" class="block text-sm font-medium text-muted-foreground mb-1">Attach Image (Optional)</label>
                     <input type="file" name="post_image" id="post_image_profile" accept="image/jpeg, image/png, image/gif, image/webp" class="block w-full text-sm text-muted-foreground border border-input rounded-md cursor-pointer bg-background focus:outline-none file:mr-4 file:py-2 file:px-4 file:rounded-md file:border-0 file:text-sm file:font-semibold file:bg-primary file:text-primary-foreground hover:file:bg-primary/90">
                     <p class="text-xs text-muted-foreground mt-1">Max file size: 2MB.</p>
                    </div> -->
                 <div class="flex justify-end mt-3">
                     <button type="submit"
                             class="inline-flex items-center justify-center whitespace-nowrap rounded-md text-sm font-medium ring-offset-background transition-colors focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 disabled:pointer-events-none disabled:opacity-50 bg-primary text-primary-foreground hover:bg-primary/90 h-9 px-3">
                         Create Post
                     </button>
                 </div>
             </form>
        </div>

        <div class="bg-card border border-destructive/50 rounded-lg shadow-sm p-6">
        <h2 class="text-xl font-semibold mb-3 text-destructive">Danger Zone</h2>
        <p class="text-sm text-muted-foreground mb-4">Deleting your account is permanent and cannot be undone. All your posts, comments, likes, and follower information will be lost.</p>
        <form action="<?php echo BASE_URL; ?>/profile/delete" method="POST" onsubmit="return confirm('Are you absolutely sure you want to delete your account? This action cannot be undone.');">
                <button type="submit"
                        class="inline-flex items-center justify-center whitespace-nowrap rounded-md text-sm font-medium ring-offset-background transition-colors focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 disabled:pointer-events-none disabled:opacity-50 bg-destructive text-destructive-foreground hover:bg-destructive/90 h-9 px-4">
                    Delete My Account Permanently
                </button>
        </form>
        </div>

        <div>
           <h2 class="text-xl font-semibold mb-4 text-foreground">Your Posts</h2>
           <?php if (empty($posts)): ?>
                <div class="text-center text-muted-foreground mt-8 bg-card border rounded-lg p-6">
                   <p class="text-lg">You haven't posted anything yet.</p>
               </div>
           <?php else: ?>
               <div class="space-y-6">
                    <?php foreach ($posts as $post): ?>
                         <?php include __DIR__ . '/../../_partials/post_card.php'; ?>
                    <?php endforeach; ?>
               </div>
           <?php endif; ?>
        </div>

    <?php else: ?>
        <div>
            <h2 class="text-xl font-semibold mb-4 text-foreground">
                <?php echo htmlspecialchars($user['name']); ?>'s Posts
            </h2>
              <?php if (empty($posts)): ?>
                  <div class="text-center text-muted-foreground mt-8 bg-card border rounded-lg p-6">
                     <p class="text-lg">@<?php echo htmlspecialchars($user['nickname'] ?? $user['name']); ?> hasn't posted yet.</p>
                 </div>
              <?php else: ?>
                  <div class="space-y-6">
                      <?php foreach ($posts as $post): ?>
                            <?php include __DIR__ . '/../../_partials/post_card.php'; ?>
                      <?php endforeach; ?>
                  </div>
              <?php endif; ?>
        </div>
    <?php endif; ?>

</div>

<div id="follow-list-modal" class="fixed inset-0 z-50 hidden items-center justify-center bg-black/50 backdrop-blur-sm" aria-labelledby="follow-list-modal-title" role="dialog" aria-modal="true">
  <div class="relative w-full max-w-md transform overflow-hidden rounded-lg bg-card text-left shadow-xl transition-all sm:my-8">
     <div class="border-b p-4">
        <div class="flex items-center justify-between">
            <h3 class="text-lg font-semibold leading-6 text-foreground" id="follow-list-modal-title">List Title</h3>
            <button type="button" id="close-follow-modal-button" class="rounded-md p-1 text-muted-foreground hover:bg-accent focus:outline-none focus:ring-2 focus:ring-ring focus:ring-offset-2">
                <span class="sr-only">Close</span>
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="h-6 w-6"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" /></svg>
            </button>
        </div>
     </div>
     <div class="max-h-[60vh] overflow-y-auto p-4">
     <div id="follow-list-content" class="space-y-3">
        <div class="loading-follow-list flex justify-center items-center py-6 text-muted-foreground">
              <svg class="animate-spin h-6 w-6 mr-3" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
              </svg>
              <span>Loading Users...</span>
        </div>
    </div>
     </div>
  </div>
</div>