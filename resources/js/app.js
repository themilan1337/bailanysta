// resources/js/app.js

/**
 * Gets a cookie value by name.
 * @param {string} name - The name of the cookie.
 * @returns {string|null} - The cookie value or null if not found.
 */
function getCookie(name) {
    const value = `; ${document.cookie}`;
    const parts = value.split(`; ${name}=`);
    if (parts.length === 2) return parts.pop().split(';').shift();
    return null;
}

/**
 * Sets a cookie.
 * @param {string} name - The name of the cookie.
 * @param {string} value - The value of the cookie.
 * @param {number} days - Number of days until the cookie expires.
 */
function setCookie(name, value, days) {
    let expires = "";
    if (days) {
        const date = new Date();
        date.setTime(date.getTime() + (days * 24 * 60 * 60 * 1000));
        expires = "; expires=" + date.toUTCString();
    }
    document.cookie = name + "=" + (value || "") + expires + "; path=/; SameSite=Lax";
}


/**
 * Applies the theme (light/dark) to the HTML element and updates toggle icons.
 * @param {string} theme - 'light' or 'dark'
 */
const applyTheme = (theme) => {
    const htmlElement = document.documentElement;

    if (!htmlElement) return; // Guard against running too early

    if (theme === 'dark') {
        htmlElement.classList.add('dark');
    } else {
        htmlElement.classList.remove('dark');
    }

    // Update icons for logged-in button
    const sunIcon = document.getElementById('theme-toggle-sun-icon');
    const moonIcon = document.getElementById('theme-toggle-moon-icon');
    if (sunIcon && moonIcon) {
        sunIcon.style.display = theme === 'dark' ? 'none' : 'block';
        moonIcon.style.display = theme === 'dark' ? 'block' : 'none';
    }

    // Update icons for logged-out button
    const sunIconLoggedOut = document.getElementById('theme-toggle-sun-icon-logged-out');
    const moonIconLoggedOut = document.getElementById('theme-toggle-moon-icon-logged-out');
     if (sunIconLoggedOut && moonIconLoggedOut) {
        sunIconLoggedOut.style.display = theme === 'dark' ? 'none' : 'block';
        moonIconLoggedOut.style.display = theme === 'dark' ? 'block' : 'none';
    }
};

/**
 * Toggles the theme, saves to cookie, and applies it visually.
 */
const toggleTheme = () => {
    const currentTheme = document.documentElement.classList.contains('dark') ? 'dark' : 'light';
    const newTheme = currentTheme === 'dark' ? 'light' : 'dark';
    setCookie('ui-theme', newTheme, 365);
    applyTheme(newTheme);
};

/**
 * Initializes the theme based on the cookie or defaults to 'light'.
 */
const initializeTheme = () => {
    console.log("Attempting to initialize theme..."); // Debug log
    const savedTheme = getCookie('ui-theme');
    const initialTheme = (savedTheme === 'dark' || savedTheme === 'light') ? savedTheme : 'light';

    if (!savedTheme || (savedTheme !== 'dark' && savedTheme !== 'light')) {
         setCookie('ui-theme', initialTheme, 365);
    }

    applyTheme(initialTheme);

    // Add event listeners to toggle buttons (safer inside init or DOMContentLoaded)
    const toggleButton = document.getElementById('theme-toggle');
    const toggleButtonLoggedOut = document.getElementById('theme-toggle-logged-out');

    if (toggleButton) {
        toggleButton.removeEventListener('click', toggleTheme); // Remove potential duplicates
        toggleButton.addEventListener('click', toggleTheme);
    }
    if (toggleButtonLoggedOut) {
        toggleButtonLoggedOut.removeEventListener('click', toggleTheme); // Remove potential duplicates
        toggleButtonLoggedOut.addEventListener('click', toggleTheme);
    }
     console.log("Theme initialized to:", initialTheme); // Debug log
};


// --- Like Button Functionality ---

/**
 * Updates the appearance and count of a like button.
 * @param {HTMLButtonElement} button - The like button element.
 * @param {boolean} liked - Whether the user currently likes the post.
 * @param {number} count - The new like count.
 */
const updateLikeButton = (button, liked, count) => {
    // ... (implementation remains the same) ...
    const likeCountSpan = button.querySelector('.like-count');
    const iconContainer = button.querySelector('svg')?.parentElement;

    if (!likeCountSpan || !iconContainer) {
        console.error('Could not find like count span or icon container for button:', button);
        return;
    }
    likeCountSpan.textContent = count;
    button.classList.toggle('text-red-500', liked);
    button.classList.toggle('font-medium', liked);
    button.classList.toggle('text-muted-foreground', !liked);
    const filledIcon = button.querySelector('.like-icon-filled');
    const outlineIcon = button.querySelector('.like-icon-outline');
    if (filledIcon && outlineIcon) {
        filledIcon.style.display = liked ? 'block' : 'none';
        outlineIcon.style.display = liked ? 'none' : 'block';
    } else {
         console.warn('Could not find like icons within button:', button);
    }
    button.setAttribute('aria-pressed', liked.toString());
};

/**
 * Handles the click event on a like button.
 * @param {Event} event - The click event object.
 */
const handleLikeClick = async (event) => {
    // ... (implementation remains the same) ...
     const button = event.currentTarget;
    const postId = button.dataset.postId;
    if (!postId || button.disabled) return;
    const currentlyLiked = button.getAttribute('aria-pressed') === 'true'; // Use ARIA state
    const method = currentlyLiked ? 'DELETE' : 'POST';
    const url = `/api/posts/${postId}/like`;
    button.disabled = true;
    button.classList.add('opacity-70');
    try {
        const response = await fetch(url, { method: method, headers: { 'Accept': 'application/json' } });
        const data = await response.json();
        if (!response.ok || !data.success) {
             console.error(`Failed to ${method === 'POST' ? 'like' : 'unlike'} post:`, data.message || `HTTP ${response.status}`);
        } else {
            updateLikeButton(button, data.userLiked, data.newLikeCount);
            // Update aria-label for screen readers
            button.setAttribute('aria-label', data.userLiked ? 'Unlike post' : 'Like post');
        }
    } catch (error) {
        console.error('Network error during like/unlike:', error);
    } finally {
         button.disabled = false;
         button.classList.remove('opacity-70');
    }
};

/**
  * Initializes Like Buttons Adds listeners etc.
  */
const initializeLikeButtons = () => {
    console.log("Attempting to initialize like buttons..."); // Debug log
    const likeButtons = document.querySelectorAll('.like-button');
    likeButtons.forEach(button => {
        // Ensure initial ARIA state is set correctly (already done in PHP, but good fallback)
        const initiallyLiked = button.classList.contains('text-red-500');
        button.setAttribute('aria-pressed', initiallyLiked.toString());
        button.setAttribute('aria-label', initiallyLiked ? 'Unlike post' : 'Like post');


        if (!button.disabled) {
            // Remove listener first to prevent duplicates if this runs multiple times
            button.removeEventListener('click', handleLikeClick);
            button.addEventListener('click', handleLikeClick);
        }
    });
     console.log(`Initialized ${likeButtons.length} like buttons.`); // Debug log
};


const createCommentElement = (comment) => {
    const div = document.createElement('div');
    div.classList.add('comment-item', 'flex', 'items-start', 'space-x-2');
    div.dataset.commentId = comment.comment_id;

    // Basic check for necessary data
    const authorPic = comment.author_picture_url || 'https://via.placeholder.com/32/cccccc/969696?text=';
    const authorName = comment.author_name || 'Unknown User';
    const timeAgo = comment.time_ago || '';
    const content = comment.content || '';

    div.innerHTML = `
        <img src="${escapeHtml(authorPic)}" alt="${escapeHtml(authorName)}'s profile picture" class="w-8 h-8 rounded-full border bg-muted flex-shrink-0">
        <div class="flex-grow bg-muted/50 dark:bg-muted/20 rounded-md px-3 py-1.5">
            <div class="flex items-baseline space-x-2">
                <span class="font-semibold text-foreground text-xs">${escapeHtml(authorName)}</span>
                <span class="text-muted-foreground text-xs">${escapeHtml(timeAgo)}</span>
                ${'' /* TODO: Add Delete button if comment.is_own_comment */}
            </div>
            <p class="text-foreground leading-snug">${escapeHtml(content).replace(/\n/g, '<br>')}</p>
        </div>
    `;
    return div;
};

/**
 * Simple HTML escaping function
 */
function escapeHtml(unsafe) {
    if (typeof unsafe !== 'string') return '';
    return unsafe
        .replace(/&/g, "&amp;")
        .replace(/</g, "&lt;")
        .replace(/>/g, "&gt;")
        .replace(/"/g, "&quot;")
        .replace(/'/g, "&#39;");
}


/**
 * Loads and displays comments for a post.
 * @param {number} postId - The ID of the post.
 * @param {HTMLElement} commentSection - The container element for comments.
 */
const loadComments = async (postId, commentSection) => {
    const commentList = commentSection.querySelector('.comment-list');
    const loadingIndicator = commentSection.querySelector('.loading-comments');
    // const noCommentsIndicator = commentSection.querySelector('.no-comments'); // If using

    if (!commentList || !loadingIndicator) return;

    loadingIndicator.style.display = 'block';
    // if (noCommentsIndicator) noCommentsIndicator.style.display = 'none';
    commentList.innerHTML = ''; // Clear previous comments (except loading indicator)
    commentList.appendChild(loadingIndicator); // Keep loading indicator

    try {
        const response = await fetch(`/api/posts/${postId}/comments`);
        const data = await response.json();

        loadingIndicator.style.display = 'none'; // Hide loading indicator

        if (!response.ok || !data.success) {
            console.error('Failed to load comments:', data.message || `HTTP ${response.status}`);
            commentList.innerHTML = '<p class="text-destructive text-xs">Could not load comments.</p>';
        } else if (data.comments && data.comments.length > 0) {
            data.comments.forEach(comment => {
                commentList.appendChild(createCommentElement(comment));
            });
        } else {
            // commentList.innerHTML = ''; // Clear loading indicator
            // if (noCommentsIndicator) noCommentsIndicator.style.display = 'block';
            commentList.innerHTML = '<p class="text-muted-foreground text-xs no-comments">No comments yet.</p>';
        }
    } catch (error) {
        console.error('Network error loading comments:', error);
        loadingIndicator.style.display = 'none';
        commentList.innerHTML = '<p class="text-destructive text-xs">Error loading comments.</p>';
    }
};


/**
 * Handles toggling the visibility of the comment section.
 * @param {Event} event - The click event object.
 */
const handleCommentToggle = (event) => {
    const button = event.currentTarget;
    const postId = button.dataset.postId;
    const commentSectionId = `comment-section-${postId}`;
    const commentSection = document.getElementById(commentSectionId);

    if (!commentSection) return;

    const isExpanded = button.getAttribute('aria-expanded') === 'true';

    if (isExpanded) {
        // Collapse
        commentSection.classList.add('hidden');
        button.setAttribute('aria-expanded', 'false');
    } else {
        // Expand
        commentSection.classList.remove('hidden');
        button.setAttribute('aria-expanded', 'true');
        // Load comments only if the list is currently empty or just has the loading/no comments message
        const listContent = commentSection.querySelector('.comment-list')?.innerHTML.trim() || '';
        const isLoading = !!commentSection.querySelector('.loading-comments'); // Check if loading indicator is present

        // Only load if it hasn't been loaded successfully before
        if (isLoading || listContent === '' || listContent.includes('no-comments') || listContent.includes('Could not load')) {
             loadComments(postId, commentSection);
        }
    }
};


/**
 * Handles submission of the add comment form.
 * @param {Event} event - The submit event object.
 */
const handleAddComment = async (event) => {
    event.preventDefault(); // Prevent default form submission
    const form = event.currentTarget;
    const postId = form.dataset.postId;
    const textarea = form.querySelector('textarea[name="content"]');
    const submitButton = form.querySelector('button[type="submit"]');
    const commentSection = document.getElementById(`comment-section-${postId}`);
    // Use temporary variables to log clearly before the nullish coalescing/optional chaining
    const _commentListElement = commentSection ? commentSection.querySelector('.comment-list') : null;
    const postContainer = document.querySelector(`[data-post-container-id="${postId}"]`);
    const _commentCountDisplayElement = postContainer ? postContainer.querySelector('.comment-count-display') : null;

    // --- DETAILED LOGGING ---
    console.log("handleAddComment Debug Info:");
    console.log("  - Post ID:", postId);
    console.log("  - Textarea found:", !!textarea); // Log true/false
    console.log("  - Submit Button found:", !!submitButton); // Log true/false
    console.log("  - Comment Section found:", !!commentSection); // Log true/false
    console.log("  - Comment List found:", !!_commentListElement); // Log true/false
    console.log("  - Post Container found:", !!postContainer); // Log true/false
    console.log("  - Comment Count Display found:", !!_commentCountDisplayElement); // Log true/false
    // --- END DETAILED LOGGING ---


    // Check using the temporary vars
    if (!postId || !textarea || !submitButton || !_commentListElement || !_commentCountDisplayElement || !commentSection || !postContainer) {
        console.error('Could not find necessary elements for adding comment. Check specific logs above.');
        return;
    }
    // Assign to original variables if checks pass
    const commentList = _commentListElement;
    const commentCountDisplay = _commentCountDisplayElement;

    const content = textarea.value.trim();
    if (content === '') {
        // Optional: Add visual feedback for empty comment
        textarea.focus();
        textarea.classList.add('ring-1', 'ring-destructive');
        setTimeout(() => textarea.classList.remove('ring-1', 'ring-destructive'), 1500);
        return;
    }

    submitButton.disabled = true;
    submitButton.textContent = 'Posting...'; // Feedback

    try {
        const response = await fetch(`/api/posts/${postId}/comments`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                // Add CSRF token if needed
            },
            body: JSON.stringify({ content: content })
        });

        const data = await response.json();

        if (!response.ok || !data.success) {
            console.error('Failed to add comment:', data.message || `HTTP ${response.status}`);
            // Optional: Show error message near the form
             alert(`Error: ${data.message || 'Could not post comment.'}`); // Simple alert for now
        } else {
            // Clear textarea
            textarea.value = '';
            // Remove "No comments yet" message if present
            const noCommentsMsg = commentList.querySelector('.no-comments');
            if (noCommentsMsg) noCommentsMsg.remove();
            // Prepend the new comment
            if (data.comment) {
                 commentList.appendChild(createCommentElement(data.comment)); // Append new comment
                 // Scroll to the new comment? Optional.
                 // commentList.lastElementChild?.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
            }
             // Update comment count display
            const count = data.newCommentCount;
            commentCountDisplay.textContent = `${count} ${count === 1 ? 'Comment' : 'Comments'}`;

        }

    } catch (error) {
        console.error('Network error adding comment:', error);
         alert('A network error occurred. Please try again.'); // Simple alert
    } finally {
        submitButton.disabled = false;
        submitButton.textContent = 'Post';
    }
};

// --- Initialize Comment Functionality ---
const initializeComments = () => {
     console.log("Attempting to initialize comments...");
     document.querySelectorAll('.comment-toggle-button').forEach(button => {
         button.removeEventListener('click', handleCommentToggle); // Prevent duplicates
         if (!button.disabled) {
             button.addEventListener('click', handleCommentToggle);
         }
     });

     document.querySelectorAll('.add-comment-form').forEach(form => {
         form.removeEventListener('submit', handleAddComment); // Prevent duplicates
         form.addEventListener('submit', handleAddComment);
     });
      console.log("Comment listeners attached.");
};


// --- Update DOMContentLoaded Listener ---
document.addEventListener('DOMContentLoaded', () => {
    console.log("DOMContentLoaded event fired.");
    initializeTheme();
    initializeLikeButtons();
    initializeComments(); // <-- Add this call
});

console.log('Bailanysta app.js script parsed (Includes Comment AJAX).');