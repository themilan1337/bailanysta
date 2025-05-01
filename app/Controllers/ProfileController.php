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
    private ?array $currentUser = null;

    public function __construct()
    {
        $this->db = get_db_connection();
        if ($this->db === null) {
            error_log("ProfileController: Failed to get DB connection.");
        }
        if (isset($_SESSION['logged_in']) && $_SESSION['logged_in'] === true && isset($_SESSION['user']['id'])) {
            $this->currentUserId = (int) $_SESSION['user']['id'];
            $this->currentUser = $_SESSION['user'];
        }
    }

    public function show(): void
    {
        if ($this->currentUserId === null) {
            header('Location: ' . BASE_URL . '/auth/google');
            exit;
        }
        $this->showById($this->currentUserId);
    }

    public function showById(int $userId): void
    {
         if ($this->db === null) {
             $_SESSION['flash_message'] = ['type' => 'error', 'text' => 'Database connection error.'];
             header('Location: ' . BASE_URL . '/'); exit;
         }
         $isOwnProfile = ($this->currentUserId !== null && $this->currentUserId === $userId);
        try {
            $sql = "
                SELECT u.id, u.google_id, u.email, u.name, u.nickname, u.picture_url, u.created_at,
                       (SELECT COUNT(*) FROM follows WHERE following_id = u.id) AS follower_count,
                       (SELECT COUNT(*) FROM follows WHERE follower_id = u.id) AS following_count";
            if ($this->currentUserId !== null) {
                 $sql .= ", (SELECT COUNT(*) FROM follows WHERE follower_id = :current_user_id AND following_id = u.id) > 0 AS viewer_is_following";
            } else {
                 $sql .= ", 0 AS viewer_is_following";
            }
            $sql .= " FROM users u WHERE u.id = :profile_user_id";
            $stmt = $this->db->prepare($sql);
            $stmt->bindParam(':profile_user_id', $userId, PDO::PARAM_INT);
             if ($this->currentUserId !== null) {
                 $stmt->bindParam(':current_user_id', $this->currentUserId, PDO::PARAM_INT);
             }
            $stmt->execute();
            $profileUser = $stmt->fetch(PDO::FETCH_ASSOC);
            if (!$profileUser) {
                http_response_code(404);
                echo view('pages.404', ['pageTitle' => 'User Not Found']);
                exit;
            }
            $profileUser['viewer_is_following'] = (bool)($profileUser['viewer_is_following'] ?? false);
            if ($isOwnProfile) {
                 $_SESSION['user'] = $profileUser;
                 $this->currentUser = $profileUser;
            }
            $posts = $this->getUserPosts($userId);
            echo view('pages.profile.show', [
                'pageTitle' => $profileUser['name'] . ($profileUser['nickname'] ? ' (@' . $profileUser['nickname'] . ')' : '') . ' - Profile',
                'user' => $profileUser,
                'posts' => $posts,
                'isOwnProfile' => $isOwnProfile
            ]);
        } catch (Throwable $e) {
             error_log("Error loading profile for user {$userId}: " . $e->getMessage() . "\n" . $e->getTraceAsString());
             $_SESSION['flash_message'] = ['type' => 'error', 'text' => 'Could not load profile.'];
             header('Location: ' . BASE_URL . '/');
             exit;
        }
    }

     public function update(): void
     {
        if ($this->currentUserId === null) { $_SESSION['flash_message'] = ['type' => 'error', 'text' => 'Authentication required.']; header('Location: ' . BASE_URL . '/auth/google'); exit; }
        if ($this->db === null) { $_SESSION['flash_message'] = ['type' => 'error', 'text' => 'Database error. Cannot update profile.']; header('Location: ' . BASE_URL . '/profile'); exit; }
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') { header('Allow: POST', true, 405); $_SESSION['flash_message'] = ['type' => 'error', 'text' => 'Invalid request method.']; header('Location: ' . BASE_URL . '/profile'); exit; }
        $nicknameInput = trim($_POST['nickname'] ?? '');
        $newNickname = $nicknameInput === '' ? null : $nicknameInput;
        if ($newNickname !== null) {
            if (!preg_match('/^[a-zA-Z0-9_]{3,20}$/', $newNickname)) { $_SESSION['flash_message'] = ['type' => 'error', 'text' => 'Nickname must be 3-20 characters long and contain only letters, numbers, and underscores.']; header('Location: ' . BASE_URL . '/profile'); exit; }
            $currentNickname = $this->currentUser['nickname'] ?? null;
            if ($newNickname !== $currentNickname) {
                try {
                    $stmt = $this->db->prepare("SELECT id FROM users WHERE nickname = :nickname AND id != :user_id");
                    $stmt->execute([':nickname' => $newNickname, ':user_id' => $this->currentUserId]);
                    if ($stmt->fetch()) { $_SESSION['flash_message'] = ['type' => 'error', 'text' => 'Nickname already taken. Please choose another.']; header('Location: ' . BASE_URL . '/profile'); exit; }
                } catch (PDOException $e) { error_log("PDO Error checking nickname uniqueness for user {$this->currentUserId}: " . $e->getMessage()); $_SESSION['flash_message'] = ['type' => 'error', 'text' => 'Error checking nickname availability.']; header('Location: ' . BASE_URL . '/profile'); exit; }
            }
        }
        try {
            $stmt = $this->db->prepare("UPDATE users SET nickname = :nickname, updated_at = NOW() WHERE id = :user_id");
            $stmt->bindParam(':nickname', $newNickname, $newNickname === null ? PDO::PARAM_NULL : PDO::PARAM_STR);
            $stmt->bindParam(':user_id', $this->currentUserId, PDO::PARAM_INT);
            $stmt->execute();
            $_SESSION['user']['nickname'] = $newNickname;
            $_SESSION['flash_message'] = ['type' => 'success', 'text' => 'Profile updated successfully!'];
        } catch (PDOException $e) {
            error_log("PDO Error updating profile for user {$this->currentUserId}: " . $e->getMessage());
            $_SESSION['flash_message'] = ['type' => 'error', 'text' => 'Failed to update profile due to a database error.'];
        }
        header('Location: ' . BASE_URL . '/profile');
        exit;
     }

    public function destroy(): void
    {
        if ($this->currentUserId === null) { $_SESSION['flash_message'] = ['type' => 'error', 'text' => 'Authentication required.']; header('Location: ' . BASE_URL . '/auth/google'); exit; }
        if ($this->db === null) { $_SESSION['flash_message'] = ['type' => 'error', 'text' => 'Database error. Cannot delete account.']; header('Location: ' . BASE_URL . '/profile'); exit; }
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') { header('Allow: POST', true, 405); $_SESSION['flash_message'] = ['type' => 'error', 'text' => 'Invalid request method for deletion.']; header('Location: ' . BASE_URL . '/profile'); exit; }
        try {
            $this->db->beginTransaction();
            $stmt = $this->db->prepare("DELETE FROM users WHERE id = :user_id");
            $stmt->bindParam(':user_id', $this->currentUserId, PDO::PARAM_INT);
            $stmt->execute();
            $rowCount = $stmt->rowCount();
            $this->db->commit();
            if ($rowCount > 0) {
                $_SESSION = [];
                if (ini_get("session.use_cookies")) { $params = session_get_cookie_params(); setcookie(session_name(), '', time() - 42000, $params["path"], $params["domain"], $params["secure"], $params["httponly"]); }
                session_destroy();
                header('Location: ' . BASE_URL . '/?deleted=true');
                exit;
            } else { throw new \Exception("User deletion failed, row count was zero for ID: {$this->currentUserId}"); }
        } catch (Throwable $e) {
            if ($this->db->inTransaction()) { $this->db->rollBack(); }
            error_log("Error deleting account for user {$this->currentUserId}: " . $e->getMessage() . "\n" . $e->getTraceAsString());
            $_SESSION['flash_message'] = ['type' => 'error', 'text' => 'Failed to delete account. Please try again.'];
            header('Location: ' . BASE_URL . '/profile');
            exit;
        }
    }

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
                // Use the private method from THIS class (Original problematic time format)
                $post['time_ago'] = $this->formatTimeAgo($post['post_created_at'] ?? 'now');
                $post['user_liked'] = (bool)($post['user_liked'] ?? false);
            }
            unset($post);
            return $userPosts;
        } catch (\PDOException $e) {
            error_log("Error fetching posts for user {$userId}: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Simple helper to format time elapsed (Original version).
     */
    private function formatTimeAgo(string $timestamp): string
    {
        $time = strtotime($timestamp);
        if ($time === false) { return 'invalid date'; }
        $now = time();
        $diff = $now - $time;
        if ($diff < 60 && $diff >= 0) { return $diff . ' sec ago'; }
        elseif ($diff < 3600 && $diff > 0) { return floor($diff / 60) . ' min ago'; }
        elseif ($diff < 86400 && $diff > 0) { return floor($diff / 3600) . ' hr ago'; }
        elseif ($diff < 604800 && $diff > 0) { return floor($diff / 86400) . ' day ago'; }
        else if ($diff < 0) { return 'in the future'; }
        else { return date('M j', $time); }
    }


    public function storePost(): void {
        if ($this->currentUserId === null) {
            $_SESSION['flash_message'] = ['type' => 'error', 'text' => 'You must be logged in to post.'];
            header('Location: ' . BASE_URL . '/auth/google'); exit;
        }
        if ($this->db === null) {
            $_SESSION['flash_message'] = ['type' => 'error', 'text' => 'Database error. Cannot create post.'];
            header('Location: ' . BASE_URL . '/profile'); exit;
        }
        $content = trim($_POST['content'] ?? '');
        $uploadedImage = $_FILES['post_image'] ?? null; // Check for uploaded file
        $imageUrl = null; // Initialize image URL

        if (empty($content) && (empty($uploadedImage) || $uploadedImage['error'] == UPLOAD_ERR_NO_FILE)) {
            $_SESSION['flash_message'] = ['type' => 'error', 'text' => 'Post content or image is required.'];
            header('Location: ' . ($_SERVER['HTTP_REFERER'] ?? BASE_URL . '/profile')); exit;
        }

        // --- Handle Image Upload ---
        if ($uploadedImage && $uploadedImage['error'] === UPLOAD_ERR_OK) {
            $allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
            $maxSize = 2 * 1024 * 1024; // 2 MB

            // Basic Validation
            if (!in_array($uploadedImage['type'], $allowedTypes)) {
                $_SESSION['flash_message'] = ['type' => 'error', 'text' => 'Invalid image type. Allowed: JPG, PNG, GIF, WEBP.'];
                header('Location: ' . ($_SERVER['HTTP_REFERER'] ?? BASE_URL . '/profile')); exit;
            }
            if ($uploadedImage['size'] > $maxSize) {
                $_SESSION['flash_message'] = ['type' => 'error', 'text' => 'Image file size exceeds the 2MB limit.'];
                header('Location: ' . ($_SERVER['HTTP_REFERER'] ?? BASE_URL . '/profile')); exit;
            }

            // Generate unique filename
            $extension = pathinfo($uploadedImage['name'], PATHINFO_EXTENSION);
            $safeExtension = strtolower(preg_replace('/[^a-zA-Z0-9]/', '', $extension)); // Sanitize extension
            if (!in_array('image/'.$safeExtension, $allowedTypes) && !($safeExtension === 'jpg' && in_array('image/jpeg', $allowedTypes))) { // Extra check on extension
                $safeExtension = 'jpg'; // Default to jpg if unsure/unsafe
            }
            $filename = uniqid('post_', true) . '.' . $safeExtension;
            $uploadDir = dirname(__DIR__, 2) . '/public/uploads/posts/'; // Absolute path to storage
            $destination = $uploadDir . $filename;
            $publicUrlPath = '/uploads/posts/' . $filename; // Relative URL path for DB/HTML src

            // Ensure directory exists and is writable (basic check)
            if (!is_dir($uploadDir)) { @mkdir($uploadDir, 0775, true); } // Attempt to create if missing
            if (!is_writable($uploadDir)) {
                error_log("Upload directory not writable: " . $uploadDir);
                $_SESSION['flash_message'] = ['type' => 'error', 'text' => 'Server error: Cannot save uploaded image.'];
                header('Location: ' . ($_SERVER['HTTP_REFERER'] ?? BASE_URL . '/profile')); exit;
            }

            // Move the uploaded file
            if (move_uploaded_file($uploadedImage['tmp_name'], $destination)) {
                $imageUrl = $publicUrlPath; // Set the URL to be saved in DB
                 error_log("Image uploaded successfully: " . $destination);
            } else {
                error_log("Failed to move uploaded file to: " . $destination);
                 $_SESSION['flash_message'] = ['type' => 'error', 'text' => 'Failed to process uploaded image.'];
                 header('Location: ' . ($_SERVER['HTTP_REFERER'] ?? BASE_URL . '/profile')); exit;
            }
        } elseif ($uploadedImage && $uploadedImage['error'] !== UPLOAD_ERR_NO_FILE) {
             // Handle other upload errors (permissions, partial upload, etc.)
             error_log("File upload error code: " . $uploadedImage['error']);
             $_SESSION['flash_message'] = ['type' => 'error', 'text' => 'An error occurred during file upload. Code: ' . $uploadedImage['error']];
             header('Location: ' . ($_SERVER['HTTP_REFERER'] ?? BASE_URL . '/profile')); exit;
        }
        // --- End Image Upload Handling ---


        try {
            $stmt = $this->db->prepare(
                "INSERT INTO posts (user_id, content, image_url, created_at, updated_at)
                 VALUES (:user_id, :content, :image_url, NOW(), NOW())"
            );
            $stmt->execute([
                ':user_id' => $this->currentUserId,
                ':content' => $content, // Still use plain text content
                ':image_url' => $imageUrl // Save the image URL (or NULL if no image)
            ]);

            $_SESSION['flash_message'] = ['type' => 'success', 'text' => 'Post created successfully!'];
            header('Location: ' . BASE_URL . (str_contains($_SERVER['HTTP_REFERER'] ?? '', '/profile') ? '/profile' : '/'));
            exit;

        } catch (\PDOException $e) {
            error_log("Error creating post for user {$this->currentUserId}: " . $e->getMessage());
             // Attempt to delete uploaded image if DB insert failed
             if ($imageUrl && isset($destination) && file_exists($destination)) { @unlink($destination); }
            $_SESSION['flash_message'] = ['type' => 'error', 'text' => 'Failed to save post to database.'];
            header('Location: ' . ($_SERVER['HTTP_REFERER'] ?? BASE_URL . '/profile')); exit;
        }
    }
}