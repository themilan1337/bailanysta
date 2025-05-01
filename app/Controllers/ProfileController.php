<?php
// app/Controllers/ProfileController.php

namespace App\Controllers;

use PDO;
use PDOException;
use Throwable;

class ProfileController
{
    private ?PDO $db;
    private ?int $currentUserId = null;
    private ?array $currentUser = null; // Store full user data

    public function __construct()
    {
        $this->db = get_db_connection();
        if ($this->db === null) {
            error_log("ProfileController: Failed to get DB connection.");
        }

        if (isset($_SESSION['logged_in']) && $_SESSION['logged_in'] === true && isset($_SESSION['user']['id'])) {
            $this->currentUserId = (int) $_SESSION['user']['id'];
            $this->currentUser = $_SESSION['user']; // Store session user data
        }
    }

    /**
     * Display the profile page for the currently logged-in user.
     */
    public function show(): void
    {
        // Authentication Check
        if ($this->currentUserId === null) {
            header('Location: ' . BASE_URL . '/auth/google');
            exit;
        }
        if ($this->db === null) {
             $_SESSION['flash_message'] = ['type' => 'error', 'text' => 'Database connection error. Cannot load profile.'];
             header('Location: ' . BASE_URL . '/');
             exit;
         }

        // --- Fetch Fresh User Data (including nickname) ---
        // It's good practice to fetch fresh data here in case it changed (like nickname)
        try {
            $stmt = $this->db->prepare("SELECT id, google_id, email, name, nickname, picture_url, created_at FROM users WHERE id = :id");
            $stmt->bindParam(':id', $this->currentUserId, PDO::PARAM_INT);
            $stmt->execute();
            $user = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$user) {
                // Should not happen if session is valid, but handle defensively
                 error_log("Profile Error: User ID {$this->currentUserId} found in session but not in database.");
                 // Log user out as session is inconsistent
                 unset($_SESSION['logged_in'], $_SESSION['user']);
                 session_regenerate_id(true); // Prevent session fixation
                 $_SESSION['flash_message'] = ['type' => 'error', 'text' => 'Your session was invalid. Please log in again.'];
                 header('Location: ' . BASE_URL . '/auth/google');
                 exit;
            }
             // Update session with potentially new data (like nickname)
             $_SESSION['user'] = $user;
             $this->currentUser = $user; // Update local property too


        } catch (PDOException $e) {
             error_log("PDO Error fetching user profile {$this->currentUserId}: " . $e->getMessage());
             $_SESSION['flash_message'] = ['type' => 'error', 'text' => 'Could not load profile data.'];
             // Render view with error? For now, redirect home might be okay.
             header('Location: ' . BASE_URL . '/');
             exit;
        }


        // Fetch User's Posts
        $posts = $this->getUserPosts($this->currentUserId);

        // Render View
        echo view('pages.profile.show', [
            'pageTitle' => 'Your Profile',
            'user' => $user, // Pass the freshly fetched user data
            'posts' => $posts,
            'isOwnProfile' => true // Always true for this specific route /profile
        ]);
    }

    /**
     * Update the user's profile (e.g., nickname).
     * Handles POST request from the profile edit form.
     */
     public function update(): void
     {
        // 1. Auth Check
        if ($this->currentUserId === null) {
            $_SESSION['flash_message'] = ['type' => 'error', 'text' => 'Authentication required.'];
            header('Location: ' . BASE_URL . '/auth/google');
            exit;
        }
        // 2. DB Check
        if ($this->db === null) {
            $_SESSION['flash_message'] = ['type' => 'error', 'text' => 'Database error. Cannot update profile.'];
            header('Location: ' . BASE_URL . '/profile');
            exit;
        }
        // 3. Method Check (Should be POST)
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
             header('Allow: POST', true, 405);
             $_SESSION['flash_message'] = ['type' => 'error', 'text' => 'Invalid request method.'];
             header('Location: ' . BASE_URL . '/profile');
             exit;
         }

         // 4. Get & Validate Nickname Input
         $nicknameInput = trim($_POST['nickname'] ?? '');
         $newNickname = $nicknameInput === '' ? null : $nicknameInput; // Store NULL if empty string

         // Basic validation rules for nickname
         if ($newNickname !== null) {
             if (!preg_match('/^[a-zA-Z0-9_]{3,20}$/', $newNickname)) {
                  $_SESSION['flash_message'] = ['type' => 'error', 'text' => 'Nickname must be 3-20 characters long and contain only letters, numbers, and underscores.'];
                  header('Location: ' . BASE_URL . '/profile');
                  exit;
             }
             // Check for uniqueness (only if it's different from the current one or setting it from null)
              $currentNickname = $this->currentUser['nickname'] ?? null;
             if ($newNickname !== $currentNickname) {
                  try {
                      $stmt = $this->db->prepare("SELECT id FROM users WHERE nickname = :nickname AND id != :user_id");
                      $stmt->execute([':nickname' => $newNickname, ':user_id' => $this->currentUserId]);
                      if ($stmt->fetch()) {
                           $_SESSION['flash_message'] = ['type' => 'error', 'text' => 'Nickname already taken. Please choose another.'];
                           header('Location: ' . BASE_URL . '/profile');
                           exit;
                      }
                  } catch (PDOException $e) {
                       error_log("PDO Error checking nickname uniqueness for user {$this->currentUserId}: " . $e->getMessage());
                       $_SESSION['flash_message'] = ['type' => 'error', 'text' => 'Error checking nickname availability.'];
                       header('Location: ' . BASE_URL . '/profile');
                       exit;
                  }
              }

         }

         // 5. Update Database
         try {
            $stmt = $this->db->prepare("UPDATE users SET nickname = :nickname, updated_at = NOW() WHERE id = :user_id");
            $stmt->bindParam(':nickname', $newNickname, $newNickname === null ? PDO::PARAM_NULL : PDO::PARAM_STR);
            $stmt->bindParam(':user_id', $this->currentUserId, PDO::PARAM_INT);
            $stmt->execute();

             // Update session immediately
             $_SESSION['user']['nickname'] = $newNickname;

            $_SESSION['flash_message'] = ['type' => 'success', 'text' => 'Profile updated successfully!'];

         } catch (PDOException $e) {
             error_log("PDO Error updating profile for user {$this->currentUserId}: " . $e->getMessage());
             $_SESSION['flash_message'] = ['type' => 'error', 'text' => 'Failed to update profile due to a database error.'];
         }

         // Redirect back to profile page
         header('Location: ' . BASE_URL . '/profile');
         exit;
     }

     /**
     * Handle deleting the user's account.
     * Handles POST request (recommended over DELETE for simple forms)
     */
    public function destroy(): void
    {
        // 1. Auth Check
        if ($this->currentUserId === null) {
            $_SESSION['flash_message'] = ['type' => 'error', 'text' => 'Authentication required.'];
            header('Location: ' . BASE_URL . '/auth/google');
            exit;
        }
        // 2. DB Check
        if ($this->db === null) {
            $_SESSION['flash_message'] = ['type' => 'error', 'text' => 'Database error. Cannot delete account.'];
            header('Location: ' . BASE_URL . '/profile');
            exit;
        }
         // 3. Method Check (Using POST for simplicity with form/JS confirm)
         if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
              header('Allow: POST', true, 405);
              $_SESSION['flash_message'] = ['type' => 'error', 'text' => 'Invalid request method for deletion.'];
              header('Location: ' . BASE_URL . '/profile');
              exit;
          }

          // Optional: Add confirmation check (e.g., hidden input or specific value)
          // if (!isset($_POST['confirm_delete']) || $_POST['confirm_delete'] !== 'DELETE_MY_ACCOUNT') { ... }


        // 4. Delete User (Transaction recommended)
        try {
            $this->db->beginTransaction();

            // The ON DELETE CASCADE constraints on posts, likes, comments, follows, notifications
            // should handle deleting related data automatically when the user is deleted.
            $stmt = $this->db->prepare("DELETE FROM users WHERE id = :user_id");
            $stmt->bindParam(':user_id', $this->currentUserId, PDO::PARAM_INT);
            $stmt->execute();

            $rowCount = $stmt->rowCount();
            $this->db->commit();

            if ($rowCount > 0) {
                // 5. Logout User (Destroy session)
                $_SESSION = [];
                if (ini_get("session.use_cookies")) {
                    $params = session_get_cookie_params();
                    setcookie(session_name(), '', time() - 42000, $params["path"], $params["domain"], $params["secure"], $params["httponly"]);
                }
                session_destroy();

                // 6. Redirect to Homepage with success message (via query param as session is gone)
                header('Location: ' . BASE_URL . '/?deleted=true');
                exit;
            } else {
                 // Should not happen if user was authenticated
                 throw new \Exception("User deletion failed, row count was zero for ID: {$this->currentUserId}");
            }

        } catch (Throwable $e) { // Catch PDO or other exceptions
            if ($this->db->inTransaction()) {
                 $this->db->rollBack();
            }
            error_log("Error deleting account for user {$this->currentUserId}: " . $e->getMessage() . "\n" . $e->getTraceAsString());
            $_SESSION['flash_message'] = ['type' => 'error', 'text' => 'Failed to delete account. Please try again.'];
            header('Location: ' . BASE_URL . '/profile');
            exit;
        }
    }


    // --- Helper methods (getUserPosts, formatTimeAgo) ---

    private function getUserPosts(int $userId, int $limit = 10, int $offset = 0): array {
        if ($this->db === null) return [];
        $sql = "
           SELECT
               p.id AS post_id, p.content, p.image_url, p.created_at AS post_created_at,
               u.id AS author_id, u.name AS author_name, u.picture_url AS author_picture_url,
               (SELECT COUNT(*) FROM likes l WHERE l.post_id = p.id) AS like_count,
               (SELECT COUNT(*) FROM comments c WHERE c.post_id = p.id) AS comment_count";
        if ($this->currentUserId !== null) {
            $sql .= ", (SELECT COUNT(*) FROM likes l WHERE l.post_id = p.id AND l.user_id = :current_user_id) > 0 AS user_liked";
        } else { $sql .= ", 0 AS user_liked"; }
        $sql .= "
           FROM posts p JOIN users u ON p.user_id = u.id
           WHERE p.user_id = :profile_user_id
           ORDER BY p.created_at DESC LIMIT :limit OFFSET :offset";
        try {
            $stmt = $this->db->prepare($sql);
            $stmt->bindParam(':profile_user_id', $userId, PDO::PARAM_INT);
            $stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
            $stmt->bindParam(':offset', $offset, PDO::PARAM_INT);
            if ($this->currentUserId !== null) {
                $stmt->bindParam(':current_user_id', $this->currentUserId, PDO::PARAM_INT);
            }
            $stmt->execute();
            $userPosts = $stmt->fetchAll(PDO::FETCH_ASSOC);
            foreach ($userPosts as &$post) {
                $post['time_ago'] = $this->formatTimeAgo($post['post_created_at'] ?? null);
                $post['user_liked'] = (bool)($post['user_liked'] ?? false);
            }
            unset($post);
            return $userPosts;
        } catch (\PDOException $e) {
            error_log("Error fetching posts for user {$userId}: " . $e->getMessage());
            return [];
        }
    }

    private function formatTimeAgo(?string $timestamp): string {
         if ($timestamp === null) return '';
         $time = strtotime($timestamp);
         if ($time === false) { error_log("formatTimeAgo failed to parse timestamp: " . $timestamp); return 'invalid date'; }
         $now = time(); $diff = $now - $time;
         if ($diff < 0) return 'in the future'; if ($diff < 60) return $diff . ' sec ago';
         if ($diff < 3600) return floor($diff / 60) . ' min ago'; if ($diff < 86400) return floor($diff / 3600) . ' hr ago';
         if ($diff < 604800) return floor($diff / 86400) . ' day ago'; return date('M j', $time);
    }

     // --- storePost Method (Keep existing for post creation form) ---
    public function storePost(): void {
         // Authentication check
        if ($this->currentUserId === null) {
            $_SESSION['flash_message'] = ['type' => 'error', 'text' => 'You must be logged in to post.'];
            header('Location: ' . BASE_URL . '/auth/google'); exit;
        }
        if ($this->db === null) {
            $_SESSION['flash_message'] = ['type' => 'error', 'text' => 'Database error. Cannot create post.'];
            header('Location: ' . ($_SERVER['HTTP_REFERER'] ?? BASE_URL . '/profile')); exit;
         }
        $content = trim($_POST['content'] ?? ''); // <-- Changed name to 'content' to match form
        if (empty($content)) {
            $_SESSION['flash_message'] = ['type' => 'error', 'text' => 'Post content cannot be empty.'];
            header('Location: ' . ($_SERVER['HTTP_REFERER'] ?? BASE_URL . '/profile')); exit;
        }
        try {
            $stmt = $this->db->prepare( "INSERT INTO posts (user_id, content, created_at, updated_at) VALUES (:user_id, :content, NOW(), NOW())" );
            $stmt->execute([':user_id' => $this->currentUserId, ':content' => $content ]);
            $_SESSION['flash_message'] = ['type' => 'success', 'text' => 'Post created successfully!'];
            // Redirect to PROFILE after posting from profile/feed
            header('Location: ' . BASE_URL . (str_contains($_SERVER['HTTP_REFERER'] ?? '', '/profile') ? '/profile' : '/'));
            exit;
        } catch (\PDOException $e) {
            error_log("Error creating post for user {$this->currentUserId}: " . $e->getMessage());
            $_SESSION['flash_message'] = ['type' => 'error', 'text' => 'Failed to create post.'];
            header('Location: ' . ($_SERVER['HTTP_REFERER'] ?? BASE_URL . '/profile')); exit;
        }
    }


} // End class