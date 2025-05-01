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
    public function like($postId): void // <-- Temporarily removed :int type hint
    {
        // Ensure it's treated as int internally
        $this->handleLikeUnlike((int)$postId, true);
    }

    /**
     * Handle unliking a post via API request.
     * Expects DELETE request to /api/posts/{id}/like
     * @param mixed $postId The ID of the post passed from the route {id:\d+} (Temporarily no type hint)
     */
    public function unlike($postId): void // <-- Temporarily removed :int type hint
    {
        // Ensure it's treated as int internally
        $this->handleLikeUnlike((int)$postId, false);
    }

    /**
     * Shared logic for liking/unliking a post.
     * @param int $postId ID of the post. (Type hint can remain here)
     * @param bool $isLiking True to like, false to unlike.
     */
    private function handleLikeUnlike(int $postId, bool $isLiking): void
    {
        header('Content-Type: application/json'); // Always return JSON

        // 1. Check Authentication
        if ($this->currentUserId === null) {
            http_response_code(401); // Unauthorized
            echo json_encode(['success' => false, 'message' => 'Authentication required.']);
            exit;
        }

        // 2. Check Database Connection
        if ($this->db === null) {
            http_response_code(500); // Internal Server Error
            echo json_encode(['success' => false, 'message' => 'Database connection error.']);
            exit;
        }

        // 3. Check if Post Exists
        $checkStmt = $this->db->prepare("SELECT id FROM posts WHERE id = :post_id");
        $checkStmt->bindParam(':post_id', $postId, PDO::PARAM_INT);
        $checkStmt->execute();
        if ($checkStmt->fetch() === false) {
             http_response_code(404); // Not Found
             echo json_encode(['success' => false, 'message' => 'Post not found.']);
             exit;
        }


        try {
            $this->db->beginTransaction(); // Start transaction

            $newLikeCount = 0;
            $userLiked = false;

            if ($isLiking) {
                // --- Attempt to LIKE ---
                $stmt = $this->db->prepare(
                    "INSERT IGNORE INTO likes (user_id, post_id, created_at) VALUES (:user_id, :post_id, NOW())"
                );
                $stmt->bindParam(':user_id', $this->currentUserId, PDO::PARAM_INT);
                $stmt->bindParam(':post_id', $postId, PDO::PARAM_INT);
                $stmt->execute();
                $userLiked = true; // Assume success if insert happens or already exists

            } else {
                // --- Attempt to UNLIKE ---
                $stmt = $this->db->prepare(
                    "DELETE FROM likes WHERE user_id = :user_id AND post_id = :post_id"
                );
                 $stmt->bindParam(':user_id', $this->currentUserId, PDO::PARAM_INT);
                $stmt->bindParam(':post_id', $postId, PDO::PARAM_INT);
                $stmt->execute();
                $userLiked = false; // Set to false after unliking
            }

            // --- Get Updated Like Count ---
            $countStmt = $this->db->prepare("SELECT COUNT(*) FROM likes WHERE post_id = :post_id");
            $countStmt->bindParam(':post_id', $postId, PDO::PARAM_INT);
            $countStmt->execute();
            $newLikeCount = (int) $countStmt->fetchColumn();

            $this->db->commit(); // Commit transaction

            // --- Success Response ---
            http_response_code(200);
            echo json_encode([
                'success' => true,
                'message' => $isLiking ? 'Post liked successfully.' : 'Post unliked successfully.',
                'newLikeCount' => $newLikeCount,
                'userLiked' => $userLiked // Send back the new state
            ]);

        } catch (\PDOException $e) {
            $this->db->rollBack(); // Roll back changes on error
            error_log("Error during like/unlike for post {$postId}, user {$this->currentUserId}: " . $e->getMessage());
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => 'An error occurred while processing the request.']);
        }
        exit; // Important to stop script execution after sending JSON
    }
}