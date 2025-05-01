<?php
// app/Controllers/PostController.php

namespace App\Controllers;

use PDO;

class PostController
{
    private ?PDO $db;
    private ?int $currentUserId = null;

    public function __construct()
    {
        $this->db = get_db_connection();
        if ($this->db === null) {
             error_log("PostController: Failed to get DB connection.");
        }

        if (isset($_SESSION['logged_in']) && $_SESSION['logged_in'] === true && isset($_SESSION['user']['id'])) {
            $this->currentUserId = (int) $_SESSION['user']['id'];
        }
    }

    /**
     * Handle liking a post via API request.
     * Expects POST request to /api/posts/{id}/like
     * @param mixed $postId The ID of the post passed from the route {id:\d+} (Temporarily no type hint)
     */
    public function like(int $id): void // <-- Changed parameter name to $id
    {
        $this->handleLikeUnlike($id, true); // <-- Pass $id
    }

    /**
     * Handle unliking a post via API request.
     * Expects DELETE request to /api/posts/{id}/like
     * @param mixed $postId The ID of the post passed from the route {id:\d+} (Temporarily no type hint)
     */
    public function unlike(int $id): void // <-- Changed parameter name to $id
    {
        $this->handleLikeUnlike($id, false); // <-- Pass $id
    }

    /**
     * Shared logic for liking/unliking a post.
     * @param int $postId ID of the post. (Type hint can remain here)
     * @param bool $isLiking True to like, false to unlike.
     */
    private function handleLikeUnlike(int $postId, bool $isLiking): void
    {
        header('Content-Type: application/json');
        if ($this->currentUserId === null) { http_response_code(401); echo json_encode(['success' => false, 'message' => 'Auth required.']); exit; }
        if ($this->db === null) { http_response_code(500); echo json_encode(['success' => false, 'message' => 'DB error.']); exit; }

        $postOwnerId = null; // Initialize

        try {
            // Fetch post owner ID first
            $postOwnerStmt = $this->db->prepare("SELECT user_id FROM posts WHERE id = :post_id");
            $postOwnerStmt->bindParam(':post_id', $postId, PDO::PARAM_INT);
            $postOwnerStmt->execute();
            $postOwner = $postOwnerStmt->fetch(PDO::FETCH_ASSOC);

            if (!$postOwner) {
                 http_response_code(404); echo json_encode(['success' => false, 'message' => 'Post not found.']); exit;
            }
            $postOwnerId = (int)$postOwner['user_id']; // Cast to int
            error_log("[Like/Unlike] Post ID: {$postId}, Post Owner ID: {$postOwnerId}, Current User ID: {$this->currentUserId}, Action: " . ($isLiking ? 'Like' : 'Unlike'));


            $this->db->beginTransaction();

            $newLikeCount = 0;
            $userLiked = false;
            $wasInserted = false; // Track if like was new

            if ($isLiking) {
                $stmt = $this->db->prepare("INSERT IGNORE INTO likes (user_id, post_id, created_at) VALUES (:user_id, :post_id, NOW())");
                $stmt->bindParam(':user_id', $this->currentUserId, PDO::PARAM_INT);
                $stmt->bindParam(':post_id', $postId, PDO::PARAM_INT);
                $stmt->execute();
                $wasInserted = $stmt->rowCount() > 0;
                $userLiked = true;
                error_log("[Like Attempt] Post ID: {$postId}, User ID: {$this->currentUserId}, New Like Inserted: " . ($wasInserted ? 'Yes' : 'No (or already existed)'));


                // --- Check Notification Condition ---
                if ($wasInserted && $postOwnerId !== $this->currentUserId) {
                    error_log("[Notification Check - Like] Conditions met (New Like=true, Not Own Post=true). Attempting notification insert...");
                    $notifyStmt = $this->db->prepare(
                        "INSERT INTO notifications (user_id, type, actor_user_id, post_id, created_at)
                         VALUES (:user_id, 'like', :actor_user_id, :post_id, NOW())"
                    );
                    $notifySuccess = $notifyStmt->execute([ // Capture execute result
                        ':user_id' => $postOwnerId,
                        ':actor_user_id' => $this->currentUserId,
                        ':post_id' => $postId
                    ]);
                    error_log("[Notification Attempt - Like] Insert successful: " . ($notifySuccess ? 'Yes' : 'No') . ". Target User: {$postOwnerId}");

                } else {
                     error_log("[Notification Check - Like] Conditions NOT met. New Like: " . ($wasInserted?'true':'false') . ", Is Own Post: " . ($postOwnerId === $this->currentUserId?'true':'false'));
                }
            } else {
                // Unlike logic (no notification created here usually)
                 error_log("[Unlike Attempt] Post ID: {$postId}, User ID: {$this->currentUserId}");
                $stmt = $this->db->prepare("DELETE FROM likes WHERE user_id = :user_id AND post_id = :post_id");
                $stmt->bindParam(':user_id', $this->currentUserId, PDO::PARAM_INT);
                $stmt->bindParam(':post_id', $postId, PDO::PARAM_INT);
                $stmt->execute();
                $userLiked = false;
            }

            $countStmt = $this->db->prepare("SELECT COUNT(*) FROM likes WHERE post_id = :post_id");
            $countStmt->bindParam(':post_id', $postId, PDO::PARAM_INT);
            $countStmt->execute();
            $newLikeCount = (int) $countStmt->fetchColumn();

            error_log("[Like/Unlike] Committing transaction for Post ID: {$postId}");
            $this->db->commit();

            http_response_code(200);
            echo json_encode(['success' => true, 'message' => $isLiking ? 'Liked.' : 'Unliked.', 'newLikeCount' => $newLikeCount, 'userLiked' => $userLiked]);

        } catch (Throwable $e) {
            error_log("[Like/Unlike ERROR] Post ID: {$postId}, Error: " . $e->getMessage());
            if ($this->db->inTransaction()) {
                error_log("[Like/Unlike ERROR] Rolling back transaction.");
                $this->db->rollBack();
            }
            http_response_code(500); echo json_encode(['success' => false, 'message' => 'Error processing request.']);
        }
        exit;
    }
    public function update(int $id): void
    {
        header('Content-Type: application/json');

        if ($this->currentUserId === null) { http_response_code(401); echo json_encode(['success' => false, 'message' => 'Authentication required.']); exit; }
        if ($this->db === null) { http_response_code(500); echo json_encode(['success' => false, 'message' => 'Database connection error.']); exit; }

        // Get Input Data from JSON body
        $requestBody = file_get_contents('php://input');
        $data = json_decode($requestBody, true);
        if (json_last_error() !== JSON_ERROR_NONE) { if(!empty(trim($requestBody))) { error_log("Failed JSON decode for update: " . json_last_error_msg()); } http_response_code(400); echo json_encode(['success' => false, 'message' => 'Invalid request format.']); exit; }

        $newContent = trim($data['content'] ?? '');

        // Validate Input
        if (empty($newContent)) { http_response_code(400); echo json_encode(['success' => false, 'message' => 'Post content cannot be empty.']); exit; }
        if (strlen($newContent) > 65535) { http_response_code(400); echo json_encode(['success' => false, 'message' => 'Post content is too long.']); exit; }

        // Sanitize text content (Use basic sanitization if not using HTMLPurifier)
        $sanitizedContent = htmlspecialchars($newContent); // Basic escaping

        // Verify Ownership and Update Content
        try {
            $stmt = $this->db->prepare("SELECT user_id FROM posts WHERE id = :post_id");
            $stmt->bindParam(':post_id', $id, PDO::PARAM_INT);
            $stmt->execute();
            $post = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$post) { http_response_code(404); echo json_encode(['success' => false, 'message' => 'Post not found.']); exit; }
            if ($post['user_id'] !== $this->currentUserId) { http_response_code(403); echo json_encode(['success' => false, 'message' => 'You are not authorized to edit this post.']); exit; }

            // Update ONLY content and updated_at
            $updateStmt = $this->db->prepare(
                "UPDATE posts SET content = :content, updated_at = NOW()
                 WHERE id = :post_id AND user_id = :user_id"
            );
            $updateStmt->bindParam(':content', $sanitizedContent, PDO::PARAM_STR);
            $updateStmt->bindParam(':post_id', $id, PDO::PARAM_INT);
            $updateStmt->bindParam(':user_id', $this->currentUserId, PDO::PARAM_INT);
            $success = $updateStmt->execute();

            if ($success) {
                http_response_code(200);
                echo json_encode([
                    'success' => true,
                    'message' => 'Post updated successfully.',
                    // Return content formatted as plain text with line breaks
                    'newContentHtml' => nl2br(htmlspecialchars($newContent))
                ]);
            } else {
                 http_response_code(500);
                 echo json_encode(['success' => false, 'message' => 'Failed to update post in database.']);
            }

        } catch (Throwable $e) {
            error_log("Error updating post {$id}: " . $e->getMessage());
            http_response_code(500);
            $errMsg = APP_ENV === 'development' ? 'Error: ' . $e->getMessage() : 'Error updating post.';
            echo json_encode(['success' => false, 'message' => $errMsg]);
        }
        exit;
    }
    public function destroy(int $id): void
    {
        header('Content-Type: application/json');

        // 1. Check Auth
        if ($this->currentUserId === null) {
            http_response_code(401); echo json_encode(['success' => false, 'message' => 'Authentication required.']); exit;
        }
        // 2. Check DB
        if ($this->db === null) {
            http_response_code(500); echo json_encode(['success' => false, 'message' => 'Database connection error.']); exit;
        }

        // 3. Verify Ownership and Delete
        try {
            // Fetch the post first to check ownership
            $stmt = $this->db->prepare("SELECT user_id FROM posts WHERE id = :post_id");
            $stmt->bindParam(':post_id', $id, PDO::PARAM_INT);
            $stmt->execute();
            $post = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$post) {
                 http_response_code(404); echo json_encode(['success' => false, 'message' => 'Post not found.']); exit;
            }

            if ($post['user_id'] !== $this->currentUserId) {
                 http_response_code(403); echo json_encode(['success' => false, 'message' => 'You are not authorized to delete this post.']); exit;
            }

            // Proceed with delete (related likes/comments should be deleted by ON DELETE CASCADE)
            $deleteStmt = $this->db->prepare("DELETE FROM posts WHERE id = :post_id AND user_id = :user_id");
            $deleteStmt->bindParam(':post_id', $id, PDO::PARAM_INT);
            $deleteStmt->bindParam(':user_id', $this->currentUserId, PDO::PARAM_INT); // Ensure user_id matches
            $success = $deleteStmt->execute();

            if ($success && $deleteStmt->rowCount() > 0) {
                 http_response_code(200);
                 echo json_encode(['success' => true, 'message' => 'Post deleted successfully.']);
            } else {
                 // If rowCount is 0, it might mean the post was already deleted or wasn't found (though checked above)
                 http_response_code(500);
                 echo json_encode(['success' => false, 'message' => 'Failed to delete post from database or post already deleted.']);
            }

        } catch (PDOException $e) {
            error_log("PDO Error deleting post {$id}: " . $e->getMessage());
            http_response_code(500);
            $errMsg = APP_ENV === 'development' ? $e->getMessage() : 'Database error during post deletion.';
            echo json_encode(['success' => false, 'message' => $errMsg]);
        } catch (Throwable $e) {
            error_log("General Error deleting post {$id}: " . $e->getMessage());
            http_response_code(500);
            $errMsg = APP_ENV === 'development' ? $e->getMessage() : 'An unexpected error occurred.';
            echo json_encode(['success' => false, 'message' => $errMsg]);
        }
        exit;
    }
}