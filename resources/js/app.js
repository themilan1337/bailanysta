// resources/js/app.js

const APP_BASE_URL = typeof BASE_URL !== 'undefined' ? BASE_URL : '';
const sessionUserPictureUrl = typeof SESSION_USER_PICTURE !== 'undefined' ? SESSION_USER_PICTURE : null;

function getCookie(name) {
    const value = `; ${document.cookie}`;
    const parts = value.split(`; ${name}=`);
    if (parts.length === 2) return parts.pop().split(';').shift();
    return null;
}

function setCookie(name, value, days) {
    let expires = "";
    if (days) {
        const date = new Date();
        date.setTime(date.getTime() + (days * 24 * 60 * 60 * 1000));
        expires = "; expires=" + date.toUTCString();
    }
    document.cookie = name + "=" + (value || "") + expires + "; path=/; SameSite=Lax";
}

function escapeHtml(unsafe) {
    if (typeof unsafe !== 'string') return '';
    return unsafe
        .replace(/&/g, "&amp;")
        .replace(/</g, "&lt;")
        .replace(/>/g, "&gt;")
        .replace(/"/g, "&quot;")
        .replace(/'/g, "&#039;");
}

function nl2br(str) {
    if (typeof str === 'undefined' || str === null) {
        return '';
    }
    return (str + '').replace(/([^>\r\n]?)(\r\n|\n\r|\r|\n)/g, '$1<br>$2');
}

            const applyTheme = (theme) => {
                const htmlElement = document.documentElement;
                if (!htmlElement) return;
                if (theme === 'dark') {
                    htmlElement.classList.add('dark');
                } else {
                    htmlElement.classList.remove('dark');
                }
                const sunIcon = document.getElementById('theme-toggle-sun-icon');
                const moonIcon = document.getElementById('theme-toggle-moon-icon');
                if (sunIcon && moonIcon) {
                    sunIcon.style.display = theme === 'dark' ? 'none' : 'block';
                    moonIcon.style.display = theme === 'dark' ? 'block' : 'none';
                }
                const sunIconLoggedOut = document.getElementById('theme-toggle-sun-icon-logged-out');
                const moonIconLoggedOut = document.getElementById('theme-toggle-moon-icon-logged-out');
                if (sunIconLoggedOut && moonIconLoggedOut) {
                    sunIconLoggedOut.style.display = theme === 'dark' ? 'none' : 'block';
                    moonIconLoggedOut.style.display = theme === 'dark' ? 'block' : 'none';
                }
            };
            const toggleTheme = () => {
                const currentTheme = document.documentElement.classList.contains('dark') ? 'dark' : 'light';
                const newTheme = currentTheme === 'dark' ? 'light' : 'dark';
                setCookie('ui-theme', newTheme, 365);
                applyTheme(newTheme);
            };
            const initializeTheme = () => {
                console.log("Attempting to initialize theme...");
                const savedTheme = getCookie('ui-theme');
                const initialTheme = (savedTheme === 'dark' || savedTheme === 'light') ? savedTheme : 'light';
                if (!savedTheme || (savedTheme !== 'dark' && savedTheme !== 'light')) {
                    setCookie('ui-theme', initialTheme, 365);
                }
                applyTheme(initialTheme);
                const toggleButton = document.getElementById('theme-toggle');
                const toggleButtonLoggedOut = document.getElementById('theme-toggle-logged-out');
                if (toggleButton) {
                    toggleButton.removeEventListener('click', toggleTheme);
                    toggleButton.addEventListener('click', toggleTheme);
                }
                if (toggleButtonLoggedOut) {
                    toggleButtonLoggedOut.removeEventListener('click', toggleTheme);
                    toggleButtonLoggedOut.addEventListener('click', toggleTheme);
                }
                console.log("Theme initialized to:", initialTheme);
            };

            const updateLikeButton = (button, liked, count) => {
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
            const handleLikeClick = async (event) => {
                const button = event.currentTarget;
                const postId = button.dataset.postId;
                if (!postId || button.disabled) return;
                const currentlyLiked = button.getAttribute('aria-pressed') === 'true';
                const method = currentlyLiked ? 'DELETE' : 'POST';
                const url = `/api/posts/${postId}/like`;
                button.disabled = true;
                button.classList.add('opacity-70');
                try {
                    const response = await fetch(url, {
                        method: method,
                        headers: {
                            'Accept': 'application/json'
                        }
                    });
                    const data = await response.json();
                    if (!response.ok || !data.success) {
                        console.error(`Failed to ${method === 'POST' ? 'like' : 'unlike'} post:`, data.message || `HTTP ${response.status}`);
                    } else {
                        updateLikeButton(button, data.userLiked, data.newLikeCount);
                        button.setAttribute('aria-label', data.userLiked ? 'Unlike post' : 'Like post');
                    }
                } catch (error) {
                    console.error('Network error during like/unlike:', error);
                } finally {
                    button.disabled = false;
                    button.classList.remove('opacity-70');
                }
            };
            const initializeLikeButtons = () => {
                console.log("Attempting to initialize like buttons...");
                const likeButtons = document.querySelectorAll('.like-button');
                likeButtons.forEach(button => {
                    const initiallyLiked = button.classList.contains('text-red-500');
                    button.setAttribute('aria-pressed', initiallyLiked.toString());
                    button.setAttribute('aria-label', initiallyLiked ? 'Unlike post' : 'Like post');
                    if (!button.disabled) {
                        button.removeEventListener('click', handleLikeClick);
                        button.addEventListener('click', handleLikeClick);
                    }
                });
                console.log(`Initialized ${likeButtons.length} like buttons.`);
            };

            const createCommentElement = (comment) => {
                const div = document.createElement('div');
                div.classList.add('comment-item', 'flex', 'items-start', 'space-x-2');
                div.dataset.commentId = comment.comment_id;
                const authorPic = comment.author_picture_url || 'https://upload.wikimedia.org/wikipedia/commons/7/7c/Profile_avatar_placeholder_large.png?20150327203541';
                const authorName = comment.author_name || 'Unknown User';
                const timeAgo = comment.time_ago || '';
                const content = comment.content || '';
                div.innerHTML = ` <img src="${escapeHtml(authorPic)}" alt="${escapeHtml(authorName)}'s profile picture" class="w-8 h-8 rounded-full border bg-muted flex-shrink-0"> <div class="flex-grow bg-muted/50 dark:bg-muted/20 rounded-md px-3 py-1.5"> <div class="flex items-baseline space-x-2"> <span class="font-semibold text-foreground text-xs">${escapeHtml(authorName)}</span> <span class="text-muted-foreground text-xs">${escapeHtml(timeAgo)}</span> </div> <p class="text-foreground leading-snug">${escapeHtml(content).replace(/\n/g, '<br>')}</p> </div> `;
                return div;
            };
            const loadComments = async (postId, commentSection) => {
                const commentList = commentSection.querySelector('.comment-list');
                const loadingIndicator = commentSection.querySelector('.loading-comments'); // Now targets the div
            
                if (!commentList || !loadingIndicator) return;
            
                loadingIndicator.style.display = 'flex'; // Show spinner div
                // Clear previous comments *except* the loading indicator itself
                commentList.querySelectorAll('.comment-item, .no-comments, .text-destructive').forEach(el => el.remove());
            
                try {
                    const response = await fetch(`/api/posts/${postId}/comments`);
                    const data = await response.json();
            
                    if (!response.ok || !data.success) {
                        console.error('Failed load comments:', data.message || `HTTP ${response.status}`);
                        const errorP = document.createElement('p');
                        errorP.className = 'text-destructive text-xs text-center py-3';
                        errorP.textContent = 'Could not load comments.';
                        commentList.appendChild(errorP);
                    } else if (data.comments && data.comments.length > 0) {
                        data.comments.forEach(comment => {
                            commentList.appendChild(createCommentElement(comment));
                        });
                    } else {
                         const noCommentsP = document.createElement('p');
                         noCommentsP.className = 'text-muted-foreground text-xs text-center py-3 no-comments';
                         noCommentsP.textContent = 'No comments yet.';
                         commentList.appendChild(noCommentsP);
                    }
                } catch (error) {
                    console.error('Network error loading comments:', error);
                     const errorP = document.createElement('p');
                     errorP.className = 'text-destructive text-xs text-center py-3';
                     errorP.textContent = 'Error loading comments.';
                     commentList.appendChild(errorP);
                } finally {
                     loadingIndicator.style.display = 'none'; // Hide spinner div when done
                }
            };
            const handleCommentToggle = (event) => {
                const button = event.currentTarget;
                const postId = button.dataset.postId;
                const commentSectionId = `comment-section-${postId}`;
                const commentSection = document.getElementById(commentSectionId);
                if (!commentSection) return;
                const isExpanded = button.getAttribute('aria-expanded') === 'true';
                if (isExpanded) {
                    commentSection.classList.add('hidden');
                    button.setAttribute('aria-expanded', 'false');
                } else {
                    commentSection.classList.remove('hidden');
                    button.setAttribute('aria-expanded', 'true');
                    const listContent = commentSection.querySelector('.comment-list')?.innerHTML.trim() || '';
                    const isLoading = !!commentSection.querySelector('.loading-comments');
                    if (isLoading || listContent === '' || listContent.includes('no-comments') || listContent.includes('Could not load')) {
                        loadComments(postId, commentSection);
                    }
                }
            };
            const handleAddComment = async (event) => {
                event.preventDefault();
                const form = event.currentTarget;
                const postId = form.dataset.postId;
                const textarea = form.querySelector('textarea[name="content"]');
                const submitButton = form.querySelector('button[type="submit"]');
                const commentSection = document.getElementById(`comment-section-${postId}`);
                const _commentListElement = commentSection ? commentSection.querySelector('.comment-list') : null;
                const postContainer = document.querySelector(`[data-post-container-id="${postId}"]`);
                const _commentCountDisplayElement = postContainer ? postContainer.querySelector('.comment-count-display') : null;
                console.log("handleAddComment Debug Info:");
                console.log("  - Post ID:", postId);
                console.log("  - Textarea found:", !!textarea);
                console.log("  - Submit Button found:", !!submitButton);
                console.log("  - Comment Section found:", !!commentSection);
                console.log("  - Comment List found:", !!_commentListElement);
                console.log("  - Post Container found:", !!postContainer);
                console.log("  - Comment Count Display found:", !!_commentCountDisplayElement);
                if (!postId || !textarea || !submitButton || !_commentListElement || !_commentCountDisplayElement || !commentSection || !postContainer) {
                    console.error('Could not find necessary elements for adding comment. Check specific logs above.');
                    return;
                }
                const commentList = _commentListElement;
                const commentCountDisplay = _commentCountDisplayElement;
                const content = textarea.value.trim();
                if (content === '') {
                    textarea.focus();
                    textarea.classList.add('ring-1', 'ring-destructive');
                    setTimeout(() => textarea.classList.remove('ring-1', 'ring-destructive'), 1500);
                    return;
                }
                submitButton.disabled = true;
                submitButton.textContent = 'Posting...';
                const payload = {
                    content: content
                };
                console.log("[Comment Store - JS Sending]", JSON.stringify(payload));
                try {
                    const response = await fetch(`/api/posts/${postId}/comments`, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'Accept': 'application/json',
                        },
                        body: JSON.stringify(payload)
                    });
                    const data = await response.json();
                    if (!response.ok || !data.success) {
                        console.error('Failed to add comment:', data.message || `HTTP ${response.status}`);
                        alert(`Error: ${data.message || 'Could not post comment.'}`);
                    } else {
                        textarea.value = '';
                        const noCommentsMsg = commentList.querySelector('.no-comments');
                        if (noCommentsMsg) noCommentsMsg.remove();
                        if (data.comment) {
                            commentList.appendChild(createCommentElement(data.comment));
                        }
                        const count = data.newCommentCount;
                        commentCountDisplay.textContent = `${count} ${count === 1 ? 'Comment' : 'Comments'}`;
                    }
                } catch (error) {
                    console.error('Network error adding comment:', error);
                    alert('A network error occurred. Please try again.');
                } finally {
                    submitButton.disabled = false;
                    submitButton.textContent = 'Post';
                }
            };
            const initializeComments = () => {
                console.log("Attempting to initialize comments...");
                document.querySelectorAll('.comment-toggle-button').forEach(button => {
                    button.removeEventListener('click', handleCommentToggle);
                    if (!button.disabled) {
                        button.addEventListener('click', handleCommentToggle);
                    }
                });
                document.querySelectorAll('.add-comment-form').forEach(form => {
                    form.removeEventListener('submit', handleAddComment);
                    form.addEventListener('submit', handleAddComment);
                });
                console.log("Comment listeners attached.");
            };

            const updateFollowButton = (button, isFollowing) => {
                const followText = button.querySelector('.follow-text');
                if (!followText) return;
                followText.textContent = isFollowing ? 'Following' : 'Follow';
                button.classList.toggle('border', isFollowing);
                button.classList.toggle('border-input', isFollowing);
                button.classList.toggle('bg-background', isFollowing);
                button.classList.toggle('hover:bg-accent', isFollowing);
                button.classList.toggle('hover:text-accent-foreground', isFollowing);
                button.classList.toggle('bg-primary', !isFollowing);
                button.classList.toggle('text-primary-foreground', !isFollowing);
                button.classList.toggle('hover:bg-primary/90', !isFollowing);
                button.setAttribute('aria-pressed', isFollowing.toString());
            };
            const handleFollowToggle = async (event) => {
                const button = event.currentTarget;
                const userId = button.dataset.userId;
                const spinner = button.querySelector('.loading-spinner');
                if (!userId || button.disabled) return;
                const currentlyFollowing = button.getAttribute('aria-pressed') === 'true';
                const method = currentlyFollowing ? 'DELETE' : 'POST';
                const url = `/api/users/${userId}/follow`;
                button.disabled = true;
                if (spinner) spinner.classList.remove('hidden');
                try {
                    const response = await fetch(url, {
                        method: method,
                        headers: {
                            'Accept': 'application/json',
                        }
                    });
                    const data = await response.json();
                    if (!response.ok || !data.success) {
                        console.error(`Failed to ${method === 'POST' ? 'follow' : 'unfollow'} user:`, data.message || `HTTP ${response.status}`);
                        alert(`Error: ${data.message || 'Could not perform follow action.'}`);
                    } else {
                        updateFollowButton(button, data.isFollowingNow);
                        console.log(data.message);
                    }
                } catch (error) {
                    console.error('Network error during follow/unfollow:', error);
                    alert('A network error occurred. Please try again.');
                } finally {
                    button.disabled = false;
                    if (spinner) spinner.classList.add('hidden');
                }
            };
            const initializeFollowButtons = () => {
                console.log("Attempting to initialize follow buttons...");
                document.querySelectorAll('.follow-toggle-button').forEach(button => {
                    button.removeEventListener('click', handleFollowToggle);
                    if (!button.disabled) {
                        button.addEventListener('click', handleFollowToggle);
                    }
                });
                console.log("Follow button listeners attached.");
            };

            const toggleEditState = (postContainer, showEditForm) => {
                const displayContent = postContainer.querySelector('.post-display-content');
                const editForm = postContainer.querySelector('.post-edit-form');
                const optionsMenu = postContainer.querySelector('.post-options-menu');
                const statusSpan = editForm?.querySelector('.edit-status');
                if (!displayContent || !editForm) return;
                displayContent.classList.toggle('hidden', showEditForm);
                editForm.classList.toggle('hidden', !showEditForm);
                if (statusSpan) statusSpan.textContent = '';
                if (showEditForm && optionsMenu) {
                    optionsMenu.classList.add('hidden');
                }
                if (showEditForm) {
                    editForm.querySelector('textarea')?.focus();
                } else {
                    const currentDisplayHtml = displayContent.innerHTML;
                    const currentDisplayText = displayContent.textContent || '';
                    editForm.querySelector('textarea').value = currentDisplayText;
                }
            };
            const handleEditButtonClick = (event) => {
                const button = event.currentTarget;
                const postContainer = button.closest('article[data-post-container-id]');
                if (postContainer) {
                    toggleEditState(postContainer, true);
                }
            };
            const handleEditCancel = (event) => {
                const button = event.currentTarget;
                const postContainer = button.closest('article[data-post-container-id]');
                if (postContainer) {
                    toggleEditState(postContainer, false);
                }
            };
            const handleEditSave = async (event) => {
                event.preventDefault();
                const form = event.currentTarget;
                const postId = form.dataset.postId;
                const textarea = form.querySelector('textarea[name="content"]');
                // No imageInput or removeCheckbox needed here anymore
                const saveButton = form.querySelector('.edit-save-button');
                const cancelButton = form.querySelector('.edit-cancel-button');
                const statusSpan = form.querySelector('.edit-status');
                const postContainer = form.closest('article[data-post-container-id]');
                const displayContent = postContainer?.querySelector('.post-display-content');
            
                if (!postId || !textarea || !saveButton || !cancelButton || !postContainer || !displayContent || !statusSpan) {
                    console.error("Missing elements for edit save.");
                    return;
                }
            
                const newContent = textarea.value.trim();
                // Validation only needs to check text content now
                if (newContent === '') {
                    // Using alert as we reverted toast notifications
                    alert('Post content cannot be empty.');
                    textarea.focus();
                    return;
                }
            
                saveButton.disabled = true; cancelButton.disabled = true; textarea.disabled = true;
                statusSpan.textContent = 'Saving...'; statusSpan.classList.remove('text-destructive');
            
                // --- Send JSON, not FormData ---
                const payload = { content: newContent };
            
                try {
                    const response = await fetch(`/api/posts/${postId}/update`, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json', // Set Content-Type for JSON
                            'Accept': 'application/json',
                        },
                        body: JSON.stringify(payload) // Send JSON string
                    });
                    const data = await response.json(); // Expect JSON response
            
                    if (!response.ok || !data.success) {
                         console.error('Update post error:', data.message || `HTTP ${response.status}`);
                         statusSpan.textContent = `Error: ${data.message || 'Could not save.'}`;
                         statusSpan.classList.add('text-destructive');
                         // Re-enable form on error
                         saveButton.disabled = false; cancelButton.disabled = false; textarea.disabled = false;
                    } else {
                        // --- Update UI (Text Only) ---
                        displayContent.innerHTML = data.newContentHtml || nl2br(escapeHtml(newContent)); // Use response HTML (nl2br for plain text)
                        statusSpan.textContent = '';
                        toggleEditState(postContainer, false); // Hide edit form
                         // Re-enable buttons after hiding
                         saveButton.disabled = false; cancelButton.disabled = false; textarea.disabled = false;
                         // alert('Post updated successfully!'); // Reverted alert
                    }
            
                } catch(error) {
                    console.error('Network error updating post:', error);
                    statusSpan.textContent = 'Network error.';
                    statusSpan.classList.add('text-destructive');
                     alert('Network error saving post.'); // Reverted alert
                    // Re-enable form on error
                    saveButton.disabled = false; cancelButton.disabled = false; textarea.disabled = false;
                }
                // No finally block needed for this simplified version
            };
            const handleDeleteButtonClick = async (event) => {
                const button = event.currentTarget;
                const postContainer = button.closest('article[data-post-container-id]');
                const postId = postContainer?.dataset.postContainerId;
                const optionsMenu = button.closest('.post-options-menu');
                if (!postId || !postContainer) return;
                if (!confirm('Are you sure you want to delete this post? This cannot be undone.')) {
                    if (optionsMenu) optionsMenu.classList.add('hidden');
                    return;
                }
                button.disabled = true;
                if (optionsMenu) optionsMenu.classList.add('hidden');
                try {
                    const response = await fetch(`/api/posts/${postId}`, {
                        method: 'DELETE',
                        headers: {
                            'Accept': 'application/json',
                        }
                    });
                    const data = await response.json();
                    if (!response.ok || !data.success) {
                        console.error('Failed to delete post:', data.message || `HTTP ${response.status}`);
                        alert(`Error: ${data.message || 'Could not delete post.'}`);
                        button.disabled = false;
                    } else {
                        console.log(data.message);
                        postContainer.style.transition = 'opacity 0.5s ease-out';
                        postContainer.style.opacity = '0';
                        setTimeout(() => {
                            postContainer.remove();
                        }, 500);
                    }
                } catch (error) {
                    console.error('Network error deleting post:', error);
                    alert('A network error occurred while deleting the post.');
                    button.disabled = false;
                }
            };
            const initializePostOptions = () => {
                console.log("Attempting to initialize post options...");
                document.querySelectorAll('.post-options-dropdown').forEach(dropdown => {
                    const button = dropdown.querySelector('.post-options-button');
                    const menu = dropdown.querySelector('.post-options-menu');
                    if (!button || !menu) return;
                    button.removeEventListener('click', toggleOptionsMenu);
                    button.addEventListener('click', toggleOptionsMenu);
                    const editButton = menu.querySelector('.post-edit-button');
                    if (editButton) {
                        editButton.removeEventListener('click', handleEditButtonClick);
                        editButton.addEventListener('click', handleEditButtonClick);
                    }
                    const deleteButton = menu.querySelector('.post-delete-button');
                    if (deleteButton) {
                        deleteButton.removeEventListener('click', handleDeleteButtonClick);
                        deleteButton.addEventListener('click', handleDeleteButtonClick);
                    }
                });
                document.removeEventListener('click', hideOpenOptionMenus);
                document.addEventListener('click', hideOpenOptionMenus);
                document.querySelectorAll('.post-edit-form').forEach(form => {
                    const cancelButton = form.querySelector('.edit-cancel-button');
                    if (cancelButton) {
                        cancelButton.removeEventListener('click', handleEditCancel);
                        cancelButton.addEventListener('click', handleEditCancel);
                    }
                    form.removeEventListener('submit', handleEditSave);
                    form.addEventListener('submit', handleEditSave);
                });
                console.log("Post options listeners attached.");
            };
            const toggleOptionsMenu = (e) => {
                e.stopPropagation();
                const button = e.currentTarget;
                const menu = button.closest('.post-options-dropdown')?.querySelector('.post-options-menu');
                if (!menu) return;
                const isHidden = menu.classList.contains('hidden');
                hideOpenOptionMenus(e, menu);
                if (isHidden) {
                    menu.classList.remove('hidden');
                } else {
                    menu.classList.add('hidden');
                }
            };
            const hideOpenOptionMenus = (e, menuToKeepOpen = null) => {
                document.querySelectorAll('.post-options-menu').forEach(menu => {
                    if (menu !== menuToKeepOpen && !menu.closest('.post-options-dropdown').contains(e.target)) {
                        menu.classList.add('hidden');
                    }
                });
            };

            const notificationToggleButton = document.getElementById('notification-toggle-button');
            const notificationCountBadge = document.getElementById('notification-count-badge');
            const notificationList = document.getElementById('notification-list');
            const notificationItemsContainer = document.getElementById('notification-items-container');
            const markAllReadButton = document.getElementById('mark-all-read-button');
            
            let unreadNotifications = []; // Store unread notifications fetched
            
            /**
             * Formats a notification item into an HTML string.
             */
            const formatNotificationItem = (notification) => {
                let message = '';
                let link = '#'; // Default link
            
                const actorName = escapeHtml(notification.actor_name || 'Someone');
                const actorPic = escapeHtml(notification.actor_picture || 'https://upload.wikimedia.org/wikipedia/commons/7/7c/Profile_avatar_placeholder_large.png?20150327203541');
                const timeAgo = escapeHtml(notification.time_ago || '');
            
                // Customize message and link based on notification type
                switch (notification.type) {
                    case 'like':
                        message = `liked your post.`;
                        if (notification.post_id) {
                             link = `/post/${notification.post_id}`; // TODO: Need a route/view for single posts later
                             // For now, maybe link to the actor's profile?
                             // link = `/profile/${notification.actor_id}`;
                             link = '#'; // Placeholder link
                        }
                        break;
                    case 'comment':
                        message = `commented on your post.`;
                         if (notification.post_id) {
                             link = `/post/${notification.post_id}`; // TODO: Link to post later
                              link = '#'; // Placeholder link
                         }
                        break;
                    case 'follow':
                        message = `started following you.`;
                        if (notification.actor_id) {
                            link = `/profile/${notification.actor_id}`; // Link to follower's profile
                        }
                        break;
                    default:
                        message = `sent you a notification.`;
                }
            
                return `
                    <a href="${link}" class="notification-item flex items-start px-3 py-2 hover:bg-accent text-foreground" data-notification-id="${notification.id}">
                        <img src="${actorPic}" alt="${actorName}'s picture" class="w-8 h-8 rounded-full border bg-muted mr-2 flex-shrink-0">
                        <div class="flex-grow">
                            <p class="text-xs leading-snug">
                                <strong class="font-medium">${actorName}</strong> ${message}
                            </p>
                            <p class="text-xs text-muted-foreground mt-0.5">${timeAgo}</p>
                        </div>
                        ${!notification.is_read ? '<span class="ml-2 mt-1 w-2 h-2 bg-primary rounded-full flex-shrink-0" title="Unread"></span>' : ''}
                    </a>
                `;
            };
            
            /**
             * Updates the notification UI (badge and dropdown list).
             */
            const updateNotificationUI = (count, notifications) => {
                // Update badge
                if (notificationCountBadge) {
                    if (count > 0) {
                        notificationCountBadge.textContent = count > 9 ? '9+' : count;
                        notificationCountBadge.classList.remove('hidden');
                    } else {
                        notificationCountBadge.classList.add('hidden');
                    }
                }
            
                // Update dropdown list
                if (notificationItemsContainer) {
                    if (notifications.length > 0) {
                        notificationItemsContainer.innerHTML = notifications.map(formatNotificationItem).join('');
                    } else {
                        notificationItemsContainer.innerHTML = '<p class="p-4 text-muted-foreground text-center text-xs">No unread notifications.</p>';
                    }
                }
            
                 // Enable/disable mark all read button
                 if (markAllReadButton) {
                      markAllReadButton.disabled = count === 0;
                 }
            };
            
            /**
             * Fetches notifications from the API.
             */
            const fetchNotifications = async () => {
                // Only fetch if elements exist (i.e., user is likely logged in)
                if (!notificationToggleButton) return;
            
                console.log("Fetching notifications...");
                try {
                    const response = await fetch('/api/notifications'); // Fetches unread by default
                    if (!response.ok) {
                        throw new Error(`HTTP error! status: ${response.status}`);
                    }
                    const data = await response.json();
            
                    if (data.success) {
                        unreadNotifications = data.notifications || []; // Store fetched notifications
                        updateNotificationUI(data.unread_count || 0, unreadNotifications);
                    } else {
                         console.error("Failed to fetch notifications:", data.message);
                         if (notificationItemsContainer) notificationItemsContainer.innerHTML = '<p class="p-4 text-destructive text-center text-xs">Could not load notifications.</p>';
                    }
                } catch (error) {
                    console.error("Network error fetching notifications:", error);
                    if (notificationItemsContainer) notificationItemsContainer.innerHTML = '<p class="p-4 text-destructive text-center text-xs">Error loading notifications.</p>';
                    // Optionally disable badge/button on error
                    if (notificationCountBadge) notificationCountBadge.classList.add('hidden');
                    if (markAllReadButton) markAllReadButton.disabled = true;
                }
            };
            
            /**
             * Marks notifications as read via API.
             * @param {Array<number>|null} ids - Array of specific IDs, or null/empty to mark all currently fetched unread.
             */
            const markNotificationsRead = async (ids = null) => {
                if (!ids && unreadNotifications.length === 0) return; // Nothing to mark
            
                const idsToMark = ids || unreadNotifications.map(n => n.id); // Use specific IDs or all fetched unread IDs
                if (idsToMark.length === 0) return;
            
                console.log("Marking notifications read:", idsToMark);
            
                try {
                    const response = await fetch('/api/notifications/mark-read', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json', 'Accept': 'application/json' },
                        body: JSON.stringify({ ids: idsToMark })
                    });
                    const data = await response.json();
            
                    if (!response.ok || !data.success) {
                        console.error("Failed to mark notifications read:", data.message);
                    } else {
                        console.log("Notifications marked read.");
                        // Refetch notifications to update the UI state (simplest way)
                        // Or manually update the UI for faster feedback
                        unreadNotifications = unreadNotifications.filter(n => !idsToMark.includes(n.id));
                        updateNotificationUI(unreadNotifications.length, unreadNotifications); // Update immediately
                         // Optionally refetch later for consistency: setTimeout(fetchNotifications, 1000);
                    }
                } catch (error) {
                    console.error("Network error marking notifications read:", error);
                }
            };
            
            
            /**
             * Initializes Notification functionality.
             */
            const initializeNotifications = () => {
                if (!notificationToggleButton) return; // Don't run if user isn't logged in / elements missing
            
                console.log("Initializing notifications...");
            
                // Toggle dropdown visibility
                notificationToggleButton.addEventListener('click', (e) => {
                    e.stopPropagation();
                    const isHidden = notificationList.classList.contains('hidden');
                    hideOpenOptionMenus(e); // Hide post options if open
                    notificationList.classList.toggle('hidden');
            
                    // If opening and there are unread notifications, mark them read after a short delay
                    if (isHidden && unreadNotifications.length > 0) {
                         setTimeout(() => {
                             markNotificationsRead(); // Mark all currently shown (fetched) unread ones
                         }, 5000); // Delay marking slightly
                    }
                });
            
                 // Mark all read button
                 if (markAllReadButton) {
                     markAllReadButton.addEventListener('click', () => {
                         markNotificationsRead();
                     });
                 }
            
                // Hide dropdown if clicked outside
                document.addEventListener('click', (e) => {
                    if (!notificationList.classList.contains('hidden') && !notificationDropdownContainer.contains(e.target)) {
                         notificationList.classList.add('hidden');
                     }
                });
            
                // Initial fetch
                fetchNotifications();
            
                // Optional: Poll for new notifications periodically (e.g., every minute)
                // setInterval(fetchNotifications, 60000);
            
                 console.log("Notification listeners attached.");
            };
            
            const searchInput = document.getElementById('search-input');
            const postsContainer = document.getElementById('feed-posts-container'); // Target for results
            const searchSpinner = document.getElementById('search-spinner');
            const searchError = document.getElementById('search-error');
            const initialFeedContent = postsContainer ? postsContainer.innerHTML : ''; // Store initial feed content
            let searchTimeoutId = null;
            let currentSearchController = null; // To abort previous requests
            
            /**
             * Renders posts received from search API into the container.
             */
            const renderSearchResults = (posts) => {
                if (!postsContainer) return;
            
                searchError.textContent = '';
                searchError.classList.add('hidden');
            
                const isLoggedIn = !!document.querySelector('a[href$="/logout"]');
                const currentUserId = typeof CURRENT_USER_ID !== 'undefined' ? CURRENT_USER_ID : null;
                // Make sure sessionUserPictureUrl is accessible (defined globally in the module scope)
                // const sessionUserPictureUrl = typeof SESSION_USER_PICTURE !== 'undefined' ? SESSION_USER_PICTURE : null; // Already defined globally
            
                if (posts.length === 0) {
                    postsContainer.innerHTML = '<p class="text-center text-muted-foreground py-10">No posts found matching your search.</p>';
                } else {
                    postsContainer.innerHTML = posts.map(post => {
                        const isAuthor = currentUserId !== null && currentUserId === post.author_id;
            
                        // Prepare button states (same as before)
                        const likeButtonClasses = `like-button flex items-center justify-center space-x-1.5 py-1.5 px-3 rounded-md hover:bg-accent transition-colors ${post.user_liked ? 'text-red-500 font-medium' : 'text-muted-foreground'} ${!isLoggedIn ? 'cursor-not-allowed opacity-60' : ''}`;
                        const likeButtonDisabled = !isLoggedIn ? 'disabled title="Login to like posts"' : '';
                        const likeButtonAriaLabel = post.user_liked ? 'Unlike' : 'Like';
                        const likeButtonAriaPressed = post.user_liked ? 'true' : 'false';
                        const outlineIconStyle = `display: ${post.user_liked ? 'none' : 'block'};`;
                        const filledIconStyle = `display: ${post.user_liked ? 'block' : 'none'};`;
                        const commentButtonClasses = `comment-toggle-button flex items-center justify-center space-x-1.5 py-1.5 px-3 rounded-md text-muted-foreground hover:bg-accent transition-colors ${!isLoggedIn ? 'cursor-not-allowed opacity-60' : ''}`;
                        const commentButtonDisabled = !isLoggedIn ? 'disabled title="Login to comment"' : '';
            
                        // Prepare author picture URL with fallback
                        const authorPicSrc = escapeHtml(post.author_picture_url || 'https://upload.wikimedia.org/wikipedia/commons/7/7c/Profile_avatar_placeholder_large.png?20150327203541');
                        // Prepare current user picture URL with fallback for comment form
                        const currentUserPicSrc = escapeHtml(sessionUserPictureUrl || 'https://upload.wikimedia.org/wikipedia/commons/7/7c/Profile_avatar_placeholder_large.png?20150327203541');
            
            
                        // Generate the HTML string with correct avatars
                        const postHtml = `
                             <article class="bg-card border rounded-lg shadow-sm overflow-hidden flex flex-col" data-post-container-id="${post.post_id}">
                                 <div class="p-4 flex items-start space-x-3">
                                     <a href="${APP_BASE_URL}/profile/${post.author_id}">
                                         <img src="${authorPicSrc}" alt="${escapeHtml(post.author_name)}'s picture" class="w-10 h-10 rounded-full border bg-muted hover:opacity-80 transition-opacity">
                                     </a>
                                     <div class="flex-grow">
                                         <a href="${APP_BASE_URL}/profile/${post.author_id}" class="font-semibold text-foreground hover:underline">${escapeHtml(post.author_name)}</a>
                                         <p class="text-xs text-muted-foreground">${escapeHtml(post.time_ago)}</p>
                                     </div>
                                     ${ isAuthor ? `
                                          <div class="relative post-options-dropdown">
                                             <button type="button" aria-label="Post options" class="post-options-button p-1 rounded-full text-muted-foreground hover:bg-accent hover:text-foreground focus:outline-none focus:ring-1 focus:ring-ring"><svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" class="w-5 h-5"><path d="M10 3a1.5 1.5 0 1 1 0 3 1.5 1.5 0 0 1 0-3ZM10 8.5a1.5 1.5 0 1 1 0 3 1.5 1.5 0 0 1 0-3ZM11.5 15.5a1.5 1.5 0 1 0-3 0 1.5 1.5 0 0 0 3 0Z" /></svg></button>
                                             <div class="post-options-menu hidden absolute right-0 mt-1 w-36 bg-popover border rounded-md shadow-lg z-10 py-1 text-sm">
                                                <button type="button" class="post-edit-button block w-full text-left px-3 py-1.5 text-foreground hover:bg-accent">Edit Post</button>
                                                <button type="button" class="post-delete-button block w-full text-left px-3 py-1.5 text-destructive hover:bg-destructive/10">Delete Post</button>
                                             </div>
                                          </div>
                                     ` : '' }
                                 </div>
            
                                 <div class="post-content-area px-4 ${ post.content ? 'pb-4' : 'pb-1'}">
                                    ${ post.content ? `<div class="post-display-content max-w-none dark:text-gray-200">${post.content}</div>` : ''}
                                    ${ isAuthor ? `
                                        <form class="post-edit-form hidden mt-2" data-post-id="${post.post_id}">
                                            <textarea name="content" rows="5" class="w-full p-2 border border-input bg-background rounded-md focus:ring-1 focus:ring-ring focus:outline-none resize-y placeholder:text-muted-foreground text-sm" required>${escapeHtml(post.content || '')}</textarea>
                                            <div class="flex justify-end items-center space-x-2 mt-2">
                                                <span class="edit-status text-xs text-muted-foreground"></span>
                                                <button type="button" class="edit-cancel-button inline-flex items-center justify-center whitespace-nowrap rounded-md text-sm font-medium ring-offset-background transition-colors focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 disabled:pointer-events-none disabled:opacity-50 border border-input bg-background hover:bg-accent hover:text-accent-foreground h-8 px-3">Cancel</button>
                                                <button type="submit" class="edit-save-button inline-flex items-center justify-center whitespace-nowrap rounded-md text-sm font-medium ring-offset-background transition-colors focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 disabled:pointer-events-none disabled:opacity-50 bg-primary text-primary-foreground hover:bg-primary/90 h-8 px-3">Save Changes</button>
                                            </div>
                                        </form>
                                        ` : '' }
                                 </div>
            
                                 ${ post.image_url ? `
                                     <div class="bg-muted border-t dark:border-gray-700 max-h-[60vh] overflow-hidden">
                                          <a href="${APP_BASE_URL}${escapeHtml(post.image_url)}" target="_blank" rel="noopener noreferrer" title="View full image">
                                               <img src="${APP_BASE_URL}${escapeHtml(post.image_url)}" alt="Post image" class="w-full h-auto object-contain display-block" loading="lazy">
                                          </a>
                                     </div>
                                 ` : ''}
            
                                 <div class="px-4 pt-3 pb-1 border-t flex items-center justify-between text-sm text-muted-foreground">
                                    <div class="flex space-x-4">
                                       <span class="like-count-display">${post.like_count} ${post.like_count == 1 ? 'Like' : 'Likes'}</span>
                                       <span class="comment-count-display">${post.comment_count} ${post.comment_count == 1 ? 'Comment' : 'Comments'}</span>
                                    </div>
                                 </div>
            
                                  <div class="p-2 border-t grid grid-cols-2 gap-1">
                                        <button data-post-id="${post.post_id}" aria-label="${likeButtonAriaLabel} post" aria-pressed="${likeButtonAriaPressed}" class="${likeButtonClasses}" ${likeButtonDisabled}>
                                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="like-icon-outline w-5 h-5" style="${outlineIconStyle}"><path stroke-linecap="round" stroke-linejoin="round" d="M21 8.25c0-2.485-2.099-4.5-4.688-4.5-1.935 0-3.597 1.126-4.312 2.733-.715-1.607-2.377-2.733-4.313-2.733C5.1 3.75 3 5.765 3 8.25c0 7.22 9 12 9 12s9-4.78 9-12z" /></svg>
                                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" class="like-icon-filled w-5 h-5" style="${filledIconStyle}"><path d="M9.653 16.915l-.005-.003-.019-.01a20.759 20.759 0 01-1.162-.682 22.045 22.045 0 01-2.582-1.9C4.045 12.733 2 10.352 2 7.5a4.5 4.5 0 018-2.828A4.5 4.5 0 0118 7.5c0 2.852-2.044 5.233-3.885 6.82a22.049 22.049 0 01-3.744 2.582l-.019.01-.005.003h-.002a.739.739 0 01-.69.001l-.002-.001z" /></svg>
                                            <span>Like <span class="like-count font-normal">${post.like_count}</span></span>
                                        </button>
                                        <button data-post-id="${post.post_id}" aria-expanded="false" aria-controls="comment-section-${post.post_id}" class="${commentButtonClasses}" ${commentButtonDisabled}>
                                           <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-5 h-5"><path stroke-linecap="round" stroke-linejoin="round" d="M2.25 12.76c0 1.6 1.123 2.994 2.707 3.227 1.087.16 2.185.283 3.293.369V21l4.076-4.076a1.526 1.526 0 011.037-.443 48.282 48.282 0 005.68-.494c1.584-.233 2.707-1.626 2.707-3.228V6.741c0-1.602-1.123-2.995-2.707-3.228A48.394 48.394 0 0012 3c-2.392 0-4.744.175-7.043.513C3.373 3.746 2.25 5.14 2.25 6.741v6.018z" /></svg>
                                           <span>Comment</span>
                                       </button>
                                  </div>
                                  <div id="comment-section-${post.post_id}" class="comment-section border-t px-4 py-3 space-y-3 hidden">
                                        <div class="comment-list space-y-3 text-sm">
                                            <p class="text-muted-foreground text-xs loading-comments">Loading comments...</p>
                                        </div>
                                        ${isLoggedIn ? `
                                        <form class="add-comment-form flex items-start space-x-2 pt-3" data-post-id="${post.post_id}">
                                            <img src="${currentUserPicSrc}" alt="Your profile picture" class="w-8 h-8 rounded-full border bg-muted flex-shrink-0 mt-1">
                                            <div class="flex-grow">
                                                <textarea name="content" rows="1" class="w-full p-2 border border-input bg-background rounded-md focus:ring-1 focus:ring-ring focus:outline-none resize-none placeholder:text-muted-foreground text-sm" placeholder="Add a comment..."></textarea>
                                                <button type="submit" class="inline-flex items-center justify-center whitespace-nowrap rounded-md text-sm font-medium ring-offset-background transition-colors focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 disabled:pointer-events-none disabled:opacity-50 bg-primary text-primary-foreground hover:bg-primary/90 h-7 px-3 mt-1 float-right">Post</button>
                                            </div>
                                        </form>
                                        ` : ''}
                                  </div>
                             </article>
                         `;
                         return postHtml;
                    }).join('');
            
                     // Re-initialize listeners after rendering new content
                     initializeLikeButtons();
                     initializeComments();
                     initializePostOptions();
                }
            };
            
            /**
             * Performs the search request.
             */
            const performSearch = async (searchTerm) => {
                if (!postsContainer || !searchSpinner || !searchError) return;
            
                // Abort previous pending search request
                if (currentSearchController) {
                    currentSearchController.abort();
                }
                currentSearchController = new AbortController(); // Create a new controller for this request
                const signal = currentSearchController.signal;
            
                searchSpinner.classList.remove('hidden'); // Show spinner
                searchError.textContent = '';
                searchError.classList.add('hidden');
            
                // If search term is empty, restore initial feed content
                if (searchTerm.trim() === '') {
                    postsContainer.innerHTML = initialFeedContent;
                    searchSpinner.classList.add('hidden');
                     // Re-initialize listeners for the original content
                     initializeLikeButtons();
                     initializeComments();
                     initializePostOptions();
                     // You might need to re-fetch original posts if state is lost,
                     // or store initial state more robustly.
                    return;
                }
            
                try {
                    const response = await fetch(`/api/posts/search?q=${encodeURIComponent(searchTerm)}`, { signal }); // Pass the signal
            
                    if (!response.ok) {
                        throw new Error(`HTTP error! status: ${response.status}`);
                    }
            
                    const data = await response.json();
            
                    if (data.success) {
                        renderSearchResults(data.posts);
                    } else {
                        searchError.textContent = data.message || 'Search failed.';
                        searchError.classList.remove('hidden');
                        postsContainer.innerHTML = ''; // Clear posts on error
                    }
            
                } catch (error) {
                    if (error.name === 'AbortError') {
                        console.log('Search request aborted.'); // Don't show error if intentionally aborted
                    } else {
                        console.error('Search network error:', error);
                        searchError.textContent = 'Error performing search. Check connection.';
                        searchError.classList.remove('hidden');
                        postsContainer.innerHTML = ''; // Clear posts on error
                    }
                } finally {
                    searchSpinner.classList.add('hidden'); // Hide spinner
                    currentSearchController = null; // Clear controller
                }
            };
            
            /**
             * Initializes Search functionality.
             */
            const initializeSearch = () => {
                if (!searchInput) return;
                console.log("Initializing search...");
            
                searchInput.addEventListener('input', (event) => {
                    const searchTerm = event.target.value;
            
                    // Clear previous timeout if user is still typing
                    clearTimeout(searchTimeoutId);
            
                    // Set a new timeout to perform search after user stops typing (e.g., 500ms)
                    searchTimeoutId = setTimeout(() => {
                        performSearch(searchTerm);
                    }, 500); // Debounce time in milliseconds
                });
                 console.log("Search listener attached.");
            };
            
            const handleGenerateIdeaClick = async (event) => {
                const button = event.currentTarget;
                // Find the form this button belongs to (using the form attribute or closest)
                const formId = button.getAttribute('form');
                const form = formId ? document.getElementById(formId) : button.closest('form');
            
                if (!form) {
                     console.error("Could not find associated form for generate idea button.");
                     alert("Error: Cannot find form context.");
                     return;
                }
            
                // Find elements within the identified form
                const textarea = form.querySelector('textarea[name="content"]');
                const promptInput = form.querySelector('input[name="ai_prompt"]'); // Find the prompt input
            
                if (!textarea) {
                    console.error("Could not find associated textarea within the form.");
                    alert("Error: Cannot find where to put the generated idea.");
                    return;
                }
            
                // Get custom prompt context from the input field
                const customPromptText = promptInput ? promptInput.value.trim() : '';
            
                button.disabled = true;
                const originalButtonHtml = button.innerHTML; // Store original content
                button.innerHTML = `
                    <svg class="animate-spin h-4 w-4 text-current" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                       <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                       <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                    </svg>
                    <span>Generating...</span>`;
            
                try {
                    const payload = {}; // Prepare payload
                    if (customPromptText !== '') {
                        payload.prompt = customPromptText; // Add prompt only if not empty
                    }
            
                    const response = await fetch('/api/ai/generate-post-idea', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'Accept': 'application/json',
                        },
                        body: JSON.stringify(payload)
                    });
                    const data = await response.json();
            
                    if (!response.ok || !data.success) {
                         console.error("Failed to generate content:", data.message || `HTTP ${response.status}`);
                         alert(`Error generating idea: ${data.message || 'Request failed.'}`);
                    } else if (data.idea) {
                         textarea.value = data.idea; // Replace content in the main textarea
                         textarea.focus();
                    } else {
                        alert("Received an empty idea from the AI service.");
                    }
            
                } catch(error) {
                    console.error("Network error generating idea:", error);
                    alert("A network error occurred while generating the idea.");
                } finally {
                     button.disabled = false;
                     button.innerHTML = originalButtonHtml; // Restore original button content
                }
            };
            
            const initializeAiFeatures = () => {
                console.log("Initializing AI features...");
                document.querySelectorAll('.generate-idea-button').forEach(button => {
                    button.removeEventListener('click', handleGenerateIdeaClick); // Prevent duplicates
                    button.addEventListener('click', handleGenerateIdeaClick);
                });
                 console.log("AI feature listeners attached.");
            };
            
            
            const followListModal = document.getElementById('follow-list-modal');
const followListTitle = document.getElementById('follow-list-modal-title');
const followListContent = document.getElementById('follow-list-content');
const closeFollowModalButton = document.getElementById('close-follow-modal-button');
// const notificationDropdownContainer = document.getElementById('notification-dropdown-container'); // Already defined if needed

/**
 * Renders the list of users inside the modal.
 */
const renderFollowList = (users, listType) => {
    if (!followListContent || !followListTitle) return;
    followListTitle.textContent = listType.charAt(0).toUpperCase() + listType.slice(1); // Capitalize type

    if (!users || users.length === 0) {
        followListContent.innerHTML = `<p class="text-center text-muted-foreground py-4">No users found.</p>`;
        return;
    }

    const currentUserId = typeof CURRENT_USER_ID !== 'undefined' ? CURRENT_USER_ID : null;

    followListContent.innerHTML = users.map(user => {
        const isFollowingThisUser = user.viewer_is_following;
        const isSelf = currentUserId === user.id;
        let followButtonHtml = '';

        if (currentUserId !== null && !isSelf) {
             const buttonClasses = `follow-toggle-button inline-flex items-center justify-center whitespace-nowrap rounded-md text-xs font-medium ring-offset-background transition-colors focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 disabled:pointer-events-none disabled:opacity-50 h-7 px-2 ${isFollowingThisUser ? 'border border-input bg-background hover:bg-accent hover:text-accent-foreground' : 'bg-primary text-primary-foreground hover:bg-primary/90'}`;
             const buttonText = isFollowingThisUser ? 'Following' : 'Follow';
             const ariaPressed = isFollowingThisUser ? 'true' : 'false';
             followButtonHtml = `<button type="button" data-user-id="${user.id}" class="${buttonClasses}" aria-pressed="${ariaPressed}"><span class="follow-text">${buttonText}</span></button>`;
        }

        return `
            <div class="flex items-center justify-between space-x-3 py-1.5">
                <a href="${APP_BASE_URL}/profile/${user.id}" class="flex items-center space-x-2 min-w-0 group">
                    <img src="${escapeHtml(user.picture_url || 'https://upload.wikimedia.org/wikipedia/commons/7/7c/Profile_avatar_placeholder_large.png?20150327203541')}" alt="${escapeHtml(user.name)}'s picture" class="w-9 h-9 rounded-full border bg-muted flex-shrink-0 group-hover:opacity-80">
                    <div class="min-w-0 flex-1">
                        <p class="text-sm font-medium text-foreground truncate group-hover:underline">${escapeHtml(user.name)}</p>
                        ${user.nickname ? `<p class="text-xs text-muted-foreground truncate">@${escapeHtml(user.nickname)}</p>` : ''}
                    </div>
                </a>
                <div class="flex-shrink-0 ml-auto">
                    ${followButtonHtml}
                </div>
            </div>
        `;
    }).join('');

    initializeFollowButtonsInModal(followListContent); // Re-attach listeners for buttons inside modal
};

/**
 * Fetch and display the follower or following list.
 */
const showFollowList = async (event) => {
    const button = event.currentTarget;
    const userId = button.dataset.userId;
    const listType = button.dataset.listType;

    if (!userId || !listType || !followListModal || !followListContent || !followListTitle) return;

    followListModal.classList.remove('hidden');
    followListModal.classList.add('flex');
    followListTitle.textContent = `Loading ${listType}...`;
    followListContent.innerHTML = `
        <div class="loading-follow-list flex justify-center items-center py-6 text-muted-foreground">
              <svg class="animate-spin h-6 w-6 mr-3" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
              </svg>
              <span>Loading Users...</span>
        </div>`;

    try {
        const apiUrl = `/api/users/${userId}/${listType}`;
        const response = await fetch(apiUrl);
        const data = await response.json();

        if (!response.ok || !data.success) {
            throw new Error(data.message || `Failed to load ${listType}`);
        }
        renderFollowList(data.users, listType);

    } catch (error) {
         console.error(`Error fetching ${listType}:`, error);
         followListContent.innerHTML = `<p class="text-center text-destructive py-4">Error loading list.</p>`;
         followListTitle.textContent = 'Error';
    }
};

/**
 * Close the follow list modal.
 */
const closeFollowListModal = () => {
    if (followListModal) {
        followListModal.classList.add('hidden');
        followListModal.classList.remove('flex');
    }
};

/**
 * Initialize listeners specifically for follow buttons inside the modal.
 * Important because modal content is dynamic.
 */
const initializeFollowButtonsInModal = (container) => {
    if (!container) return;
    container.querySelectorAll('.follow-toggle-button').forEach(button => {
         button.removeEventListener('click', handleFollowToggle); // Prevent duplicates
         if (!button.disabled) { // Check if not disabled (shouldn't be in modal unless logged out state is wrong)
             button.addEventListener('click', handleFollowToggle);
         }
     });
      console.log("Follow buttons within modal initialized.");
};


/**
 * Initialize listeners for showing the follow list modal.
 */
const initializeFollowListTriggers = () => {
     if(!followListModal) return; // Don't initialize if modal isn't present

     console.log("Initializing follow list triggers...");
     document.querySelectorAll('.show-follow-list-button').forEach(button => {
         button.removeEventListener('click', showFollowList);
         button.addEventListener('click', showFollowList);
     });

     if (closeFollowModalButton) {
          closeFollowModalButton.removeEventListener('click', closeFollowListModal);
          closeFollowModalButton.addEventListener('click', closeFollowListModal);
     }

     document.addEventListener('keydown', (event) => {
         if (event.key === "Escape" && !followListModal.classList.contains('hidden')) {
             closeFollowListModal();
         }
     });

    // Optional: Close on backdrop click
    // followListModal.addEventListener('click', (event) => {
    //      if (event.target === followListModal) { closeFollowListModal(); }
    //  });


      console.log("Follow list trigger listeners attached.");
};


// resources/js/app.js
// ... (Existing code: Globals, Helpers, Theme, Like, Comment, Follow, Edit, Delete, Search, Notification logic) ...

// --- Infinite Scroll Functionality ---

const feedPostsContainer = document.getElementById('feed-posts-container'); // Container for posts
const loadingIndicator = document.getElementById('loading-indicator'); // Loading indicator element
let isLoadingPosts = false; // Flag to prevent multiple loads
let noMorePosts = false; // Flag to stop loading
let currentPostOffset = 0; // Start with offset 0
const postsPerLoad = 5; // Should match FeedController::DEFAULT_LIMIT

/**
 * Renders new posts fetched via infinite scroll.
 * Uses the simplified JS rendering approach. Re-initializes listeners.
 */
const renderMorePosts = (posts) => {
    if (!feedPostsContainer || !posts || posts.length === 0) return;

    const isLoggedIn = !!document.querySelector('a[href$="/logout"]');
    const currentUserId = typeof CURRENT_USER_ID !== 'undefined' ? CURRENT_USER_ID : null;

    posts.forEach(post => {
        const isAuthor = currentUserId !== null && currentUserId === post.author_id;

        const likeButtonClasses = `like-button flex items-center justify-center space-x-1.5 py-1.5 px-3 rounded-md hover:bg-accent transition-colors ${post.user_liked ? 'text-red-500 font-medium' : 'text-muted-foreground'} ${!isLoggedIn ? 'cursor-not-allowed opacity-60' : ''}`;
        const likeButtonDisabled = !isLoggedIn ? 'disabled title="Login to like posts"' : '';
        const likeButtonAriaLabel = post.user_liked ? 'Unlike' : 'Like';
        const likeButtonAriaPressed = post.user_liked ? 'true' : 'false';
        const outlineIconStyle = `display: ${post.user_liked ? 'none' : 'block'};`;
        const filledIconStyle = `display: ${post.user_liked ? 'block' : 'none'};`;

        const commentButtonClasses = `comment-toggle-button flex items-center justify-center space-x-1.5 py-1.5 px-3 rounded-md text-muted-foreground hover:bg-accent transition-colors ${!isLoggedIn ? 'cursor-not-allowed opacity-60' : ''}`;
        const commentButtonDisabled = !isLoggedIn ? 'disabled title="Login to comment"' : '';

        let authorPicSrc = post.author_picture_url || 'https://upload.wikimedia.org/wikipedia/commons/7/7c/Profile_avatar_placeholder_large.png?20150327203541';
        if (authorPicSrc && authorPicSrc.startsWith('/')) { authorPicSrc = APP_BASE_URL + authorPicSrc; }
        authorPicSrc = escapeHtml(authorPicSrc);
        const currentUserPicSrc = escapeHtml(sessionUserPictureUrl || 'https://upload.wikimedia.org/wikipedia/commons/7/7c/Profile_avatar_placeholder_large.png?20150327203541');
        let postImageSrc = post.image_url ? APP_BASE_URL + escapeHtml(post.image_url) : null;

        const postElement = document.createElement('article');
        postElement.className = 'bg-card border rounded-lg shadow-sm overflow-hidden flex flex-col opacity-0 transition-opacity duration-500';
        postElement.dataset.postContainerId = post.post_id;

        postElement.innerHTML = `
            <div class="p-4 flex items-start space-x-3">
                <a href="${APP_BASE_URL}/profile/${post.author_id}">
                    <img src="${authorPicSrc}" alt="${escapeHtml(post.author_name)}'s picture" class="w-10 h-10 rounded-full border bg-muted hover:opacity-80 transition-opacity">
                </a>
                <div class="flex-grow">
                    <a href="${APP_BASE_URL}/profile/${post.author_id}" class="font-semibold text-foreground hover:underline">${escapeHtml(post.author_name)}</a>
                    <p class="text-xs text-muted-foreground">${escapeHtml(post.time_ago)}</p>
                </div>
                ${ isAuthor ? `
                     <div class="relative post-options-dropdown">
                        <button type="button" aria-label="Post options" class="post-options-button p-1 rounded-full text-muted-foreground hover:bg-accent hover:text-foreground focus:outline-none focus:ring-1 focus:ring-ring"><svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" class="w-5 h-5"><path d="M10 3a1.5 1.5 0 1 1 0 3 1.5 1.5 0 0 1 0-3ZM10 8.5a1.5 1.5 0 1 1 0 3 1.5 1.5 0 0 1 0-3ZM11.5 15.5a1.5 1.5 0 1 0-3 0 1.5 1.5 0 0 0 3 0Z" /></svg></button>
                        <div class="post-options-menu hidden absolute right-0 mt-1 w-36 bg-popover border rounded-md shadow-lg z-10 py-1 text-sm">
                           <button type="button" class="post-edit-button block w-full text-left px-3 py-1.5 text-foreground hover:bg-accent">Edit Post</button>
                           <button type="button" class="post-delete-button block w-full text-left px-3 py-1.5 text-destructive hover:bg-destructive/10">Delete Post</button>
                        </div>
                     </div>
                ` : '' }
            </div>

            <div class="post-content-area px-4 ${ post.content ? 'pb-4' : 'pb-1'}">
               ${ post.content ? `<div class="post-display-content max-w-none dark:text-gray-200">${nl2br(escapeHtml(post.content))}</div>` : ''}
               ${ isAuthor ? `
                   <form class="post-edit-form hidden mt-2" data-post-id="${post.post_id}">
                       <textarea name="content" rows="5" class="w-full p-2 border border-input bg-background rounded-md focus:ring-1 focus:ring-ring focus:outline-none resize-y placeholder:text-muted-foreground text-sm" required>${escapeHtml(post.content || '')}</textarea>
                       <div class="flex justify-end items-center space-x-2 mt-2">
                           <span class="edit-status text-xs text-muted-foreground"></span>
                           <button type="button" class="edit-cancel-button inline-flex items-center justify-center whitespace-nowrap rounded-md text-sm font-medium ring-offset-background transition-colors focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 disabled:pointer-events-none disabled:opacity-50 border border-input bg-background hover:bg-accent hover:text-accent-foreground h-8 px-3">Cancel</button>
                           <button type="submit" class="edit-save-button inline-flex items-center justify-center whitespace-nowrap rounded-md text-sm font-medium ring-offset-background transition-colors focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 disabled:pointer-events-none disabled:opacity-50 bg-primary text-primary-foreground hover:bg-primary/90 h-8 px-3">Save Changes</button>
                       </div>
                   </form>
                   ` : '' }
            </div>

            ${ post.image_url ? `
                <div class="post-display-image bg-muted border-t dark:border-gray-700 max-h-[60vh] overflow-hidden">
                     <a href="${postImageSrc}" target="_blank" rel="noopener noreferrer" title="View full image">
                          <img src="${postImageSrc}" alt="Post image" class="w-full h-auto object-contain display-block" loading="lazy">
                     </a>
                </div>
            ` : ''}

            <div class="px-4 pt-3 pb-1 border-t flex items-center justify-between text-sm text-muted-foreground">
               <div class="flex space-x-4">
                  <span class="like-count-display">${post.like_count} ${post.like_count == 1 ? 'Like' : 'Likes'}</span>
                  <span class="comment-count-display">${post.comment_count} ${post.comment_count == 1 ? 'Comment' : 'Comments'}</span>
               </div>
            </div>

             <div class="p-2 border-t grid grid-cols-2 gap-1">
                   <button data-post-id="${post.post_id}" aria-label="${likeButtonAriaLabel} post" aria-pressed="${likeButtonAriaPressed}" class="${likeButtonClasses}" ${likeButtonDisabled}>
                       <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="like-icon-outline w-5 h-5" style="${outlineIconStyle}"><path stroke-linecap="round" stroke-linejoin="round" d="M21 8.25c0-2.485-2.099-4.5-4.688-4.5-1.935 0-3.597 1.126-4.312 2.733-.715-1.607-2.377-2.733-4.313-2.733C5.1 3.75 3 5.765 3 8.25c0 7.22 9 12 9 12s9-4.78 9-12z" /></svg>
                       <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" class="like-icon-filled w-5 h-5" style="${filledIconStyle}"><path d="M9.653 16.915l-.005-.003-.019-.01a20.759 20.759 0 01-1.162-.682 22.045 22.045 0 01-2.582-1.9C4.045 12.733 2 10.352 2 7.5a4.5 4.5 0 018-2.828A4.5 4.5 0 0118 7.5c0 2.852-2.044 5.233-3.885 6.82a22.049 22.049 0 01-3.744 2.582l-.019.01-.005.003h-.002a.739.739 0 01-.69.001l-.002-.001z" /></svg>
                       <span>Like <span class="like-count font-normal">${post.like_count}</span></span>
                   </button>
                   <button data-post-id="${post.post_id}" aria-expanded="false" aria-controls="comment-section-${post.post_id}" class="${commentButtonClasses}" ${commentButtonDisabled}>
                      <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-5 h-5"><path stroke-linecap="round" stroke-linejoin="round" d="M2.25 12.76c0 1.6 1.123 2.994 2.707 3.227 1.087.16 2.185.283 3.293.369V21l4.076-4.076a1.526 1.526 0 011.037-.443 48.282 48.282 0 005.68-.494c1.584-.233 2.707-1.626 2.707-3.228V6.741c0-1.602-1.123-2.995-2.707-3.228A48.394 48.394 0 0012 3c-2.392 0-4.744.175-7.043.513C3.373 3.746 2.25 5.14 2.25 6.741v6.018z" /></svg>
                      <span>Comment</span>
                  </button>
             </div>
             <div id="comment-section-${post.post_id}" class="comment-section border-t px-4 py-3 space-y-3 hidden">
                   <div class="comment-list space-y-3 text-sm">
                       <p class="text-muted-foreground text-xs loading-comments">Loading comments...</p>
                   </div>
                   ${isLoggedIn ? `
                   <form class="add-comment-form flex items-start space-x-2 pt-3" data-post-id="${post.post_id}">
                       <img src="${currentUserPicSrc}" alt="Your profile picture" class="w-8 h-8 rounded-full border bg-muted flex-shrink-0 mt-1">
                       <div class="flex-grow">
                           <textarea name="content" rows="1" class="w-full p-2 border border-input bg-background rounded-md focus:ring-1 focus:ring-ring focus:outline-none resize-none placeholder:text-muted-foreground text-sm" placeholder="Add a comment..."></textarea>
                           <button type="submit" class="inline-flex items-center justify-center whitespace-nowrap rounded-md text-sm font-medium ring-offset-background transition-colors focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 disabled:pointer-events-none disabled:opacity-50 bg-primary text-primary-foreground hover:bg-primary/90 h-7 px-3 mt-1 float-right">Post</button>
                       </div>
                   </form>
                   ` : ''}
             </div>
        `;

        feedPostsContainer.appendChild(postElement);
        setTimeout(() => { postElement.classList.remove('opacity-0'); }, 50);
    });

    initializeLikeButtons();
    initializeComments();
    initializePostOptions();
};


/**
 * Fetches and appends more posts to the feed.
 */
const loadMorePosts = async () => {
    // Exit conditions: already loading, no more posts, elements missing
    if (isLoadingPosts || noMorePosts || !feedPostsContainer || !loadingIndicator) {
        // console.log("Load More Posts: Skipped (Loading:", isLoadingPosts, "No More:", noMorePosts, ")");
        return;
    }

    isLoadingPosts = true;
    loadingIndicator.style.display = 'block'; // Show loading indicator
    loadingIndicator.innerHTML = '<p class="text-gray-500 dark:text-gray-400">Loading more posts...</p>'; // Reset text

    // Calculate the offset based on the *current* number of posts displayed
    const currentPostCount = feedPostsContainer.querySelectorAll('article[data-post-container-id]').length;
    const offsetToLoad = currentPostCount; // Next offset is the current count

    console.log(`Loading more posts... Offset: ${offsetToLoad}, Limit: ${postsPerLoad}`);

    try {
        // Use APP_BASE_URL just in case, although '/' should work
        const response = await fetch(`${APP_BASE_URL}/?ajax=1&offset=${offsetToLoad}&limit=${postsPerLoad}`);
        const data = await response.json();

        if (!response.ok || !data.success) {
            throw new Error(data.message || 'Failed to load more posts');
        }

        if (data.posts && data.posts.length > 0) {
            renderMorePosts(data.posts);
            // If fewer posts were returned than requested, assume we reached the end
            if (data.posts.length < postsPerLoad) {
                noMorePosts = true;
                loadingIndicator.innerHTML = '<p class="text-muted-foreground text-sm py-4">End of feed.</p>';
                 console.log("Reached end of feed (loaded < limit).");
            } else {
                 // Still more posts potentially available, hide indicator until next scroll trigger
                 loadingIndicator.style.display = 'none';
            }
        } else {
            // No more posts returned from the server
            console.log("No more posts to load (API returned empty).");
            noMorePosts = true;
            loadingIndicator.innerHTML = '<p class="text-muted-foreground text-sm py-4">No more posts.</p>';
        }

    } catch (error) {
        console.error("Error loading more posts:", error);
        loadingIndicator.innerHTML = '<p class="text-destructive text-sm py-4">Error loading posts.</p>';
        // Decide whether to stop trying after an error
        // noMorePosts = true;
    } finally {
        isLoadingPosts = false;
        // Keep indicator visible only if noMorePosts is true
         if (!noMorePosts) {
            loadingIndicator.style.display = 'none';
        }
    }
};

/**
 * Throttled scroll handler to check if user is near the bottom.
 */
let scrollTimeout; // Define timeout variable outside handler
const throttledScrollHandler = () => {
     if (scrollTimeout) return; // Don't run if already waiting for timeout

     scrollTimeout = setTimeout(() => {
        // Reset timeout ID so it can run again
         scrollTimeout = null;

         // Check if user is near the bottom
         const scrollThreshold = 350; // Pixels from bottom to trigger load (increase slightly?)
         if (!isLoadingPosts && !noMorePosts && (window.innerHeight + window.scrollY) >= (document.documentElement.scrollHeight - scrollThreshold)) {
            console.log("Scroll threshold met, calling loadMorePosts.");
            loadMorePosts();
         }
     }, 250); // Check scroll position at most every 250ms
 };


/**
 * Initializes Infinite Scroll functionality.
 */
const initializeInfiniteScroll = () => {
    // Check if the necessary elements exist (only run on feed page)
    if (!feedPostsContainer || !loadingIndicator) {
        console.log("Infinite scroll elements not found, skipping initialization.");
        return;
    }

     // Determine initial state based on PHP render
     const initialPostCount = feedPostsContainer.querySelectorAll('article[data-post-container-id]').length;
     console.log("Initial post count:", initialPostCount);

     // If initial load is less than limit, assume no more posts
     if (initialPostCount < postsPerLoad) {
        noMorePosts = true;
         loadingIndicator.innerHTML = '<p class="text-muted-foreground text-sm py-4">End of feed.</p>';
         loadingIndicator.style.display = 'block';
         console.log("Initial load has less posts than limit, infinite scroll inactive.");
     } else {
        // Only add scroll listener if there are potentially more posts
         console.log("Initializing infinite scroll listener.");
         noMorePosts = false; // Ensure it's false initially
         isLoadingPosts = false; // Ensure it's false initially
         window.addEventListener('scroll', throttledScrollHandler);
          // Initial check in case the first page doesn't fill the screen
          handleScroll(); // Call handleScroll once to check initial state
     }
};

// --- Update DOMContentLoaded Listener ---
document.addEventListener('DOMContentLoaded', () => {
    console.log("DOMContentLoaded event fired.");
    initializeTheme();
    initializeLikeButtons();
    initializeComments();
    initializeFollowButtons();
    initializePostOptions();
    initializeNotifications();
    initializeSearch();
    initializeAiFeatures();
    initializeInfiniteScroll(); // Make sure this is called
});

// ... (rest of console log) ...