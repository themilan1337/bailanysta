<?php
// app/Controllers/CommentController.php

namespace App\Controllers;

use PDO;
use PDOException; // Explicitly use PDOException
use Throwable;    // Catch generic throwables

class CommentController
{
    private ?PDO $db;
    private ?int $currentUserId = null;

    public function __construct()
    {
        $this->db = get_db_connection(); // Assuming this handles its own errors/returns null
        if ($this->db === null) {
             error_log("CommentController: Failed to get DB connection during construction.");
             // Don't echo/exit here, let methods handle the response
        }

        if (isset($_SESSION['logged_in']) && $_SESSION['logged_in'] === true && isset($_SESSION['user']['id'])) {
            $this->currentUserId = (int) $_SESSION['user']['id'];
        }
    }

    /**
     * Fetch comments for a specific post.
     * GET /api/posts/{postId}/comments
     * @param int $postId (Type hint restored)
     */
    public function index(int $postId): void
    {
        header('Content-Type: application/json');

        // Re-check DB connection inside the method
        if ($this->db === null) {
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => 'Database connection unavailable.', 'comments' => []]);
            exit;
        }

        try {
            $sql = "SELECT
                        c.id AS comment_id,
                        c.content,
                        c.created_at AS comment_created_at,
                        u.id AS author_id,
                        u.name AS author_name,
                        u.picture_url AS author_picture_url
                    FROM comments c
                    JOIN users u ON c.user_id = u.id
                    WHERE c.post_id = :post_id
                    ORDER BY c.created_at ASC";

            $stmt = $this->db->prepare($sql);
            $stmt->bindParam(':post_id', $postId, PDO::PARAM_INT);
            $stmt->execute();
            $comments = $stmt->fetchAll(PDO::FETCH_ASSOC);

            // Format time after fetching
            foreach($comments as &$comment) {
                // Pass the raw timestamp to the robust helper
                $comment['time_ago'] = $this->formatTimeAgo($comment['comment_created_at'] ?? null);
            }
            unset($comment); // Break reference

            echo json_encode(['success' => true, 'comments' => $comments]);

        } catch (PDOException $e) { // Catch specific PDO errors
            error_log("PDO Error fetching comments for post {$postId}: " . $e->getMessage());
            http_response_code(500);
            $errorMessage = APP_ENV === 'development' ? $e->getMessage() : 'Failed to fetch comments due to database error.';
            echo json_encode(['success' => false, 'message' => $errorMessage, 'comments' => []]);
        } catch (Throwable $e) { // Catch other potential errors during processing
             error_log("General Error fetching comments for post {$postId}: " . $e->getMessage() . "\n" . $e->getTraceAsString()); // Log trace
             http_response_code(500);
             $errorMessage = APP_ENV === 'development' ? 'Error: ' . $e->getMessage() : 'An unexpected error occurred while fetching comments.';
             echo json_encode(['success' => false, 'message' => $errorMessage, 'comments' => []]);
        }
        exit;
    }

    /**
     * Store a new comment for a specific post.
     * POST /api/posts/{postId}/comments
     * @param int $postId (Type hint restored)
     */
    public function store(int $postId): void
    {
        header('Content-Type: application/json');

        // 1. Check Auth
        if ($this->currentUserId === null) {
            http_response_code(401);
            echo json_encode(['success' => false, 'message' => 'Authentication required.']);
            exit;
        }

        // 2. Re-check DB
         if ($this->db === null) {
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => 'Database connection unavailable.']);
            exit;
        }

        // 3. Get Input Data
        $requestBody = file_get_contents('php://input');
        $data = json_decode($requestBody, true);

        // Check JSON decoding carefully
        if (json_last_error() !== JSON_ERROR_NONE) {
             if(!empty(trim($requestBody))) {
                 error_log("Failed to decode JSON body for adding comment. Error: " . json_last_error_msg() . " Body: " . $requestBody);
             }
             http_response_code(400);
             echo json_encode(['success' => false, 'message' => 'Invalid request format. Expected valid JSON.']);
             exit;
         }

        $content = trim($data['content'] ?? '');

        // 4. Validate Input
        if (empty($content)) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Comment content cannot be empty.']);
            exit;
        }
        if (strlen($content) > 1000) { // Example length limit
             http_response_code(400);
             echo json_encode(['success' => false, 'message' => 'Comment is too long (max 1000 characters).']);
             exit;
        }

        // Variables needed inside and outside try block
        $newCommentData = null;
        $newCommentCount = 0;

        // 5. Check Post Exists & Insert Comment (within transaction)
        try {
            // Check Post Existence first
            $checkStmt = $this->db->prepare("SELECT id FROM posts WHERE id = :post_id");
            $checkStmt->bindParam(':post_id', $postId, PDO::PARAM_INT);
            $checkStmt->execute();
            if ($checkStmt->fetch() === false) {
                 http_response_code(404);
                 echo json_encode(['success' => false, 'message' => 'Post not found.']);
                 exit; // Exit before starting transaction
            }

            // Start transaction ONLY if post exists
            $this->db->beginTransaction();

            // Insert the comment
            $sql = "INSERT INTO comments (user_id, post_id, content, created_at, updated_at)
                    VALUES (:user_id, :post_id, :content, NOW(), NOW())";
            $stmt = $this->db->prepare($sql);
            $stmt->bindParam(':user_id', $this->currentUserId, PDO::PARAM_INT);
            $stmt->bindParam(':post_id', $postId, PDO::PARAM_INT);
            $stmt->bindParam(':content', $content, PDO::PARAM_STR); // Consider sanitization if content allows HTML
            $stmt->execute();
            $newCommentId = $this->db->lastInsertId();

            // Get Updated Comment Count
            $countStmt = $this->db->prepare("SELECT COUNT(*) FROM comments WHERE post_id = :post_id");
            $countStmt->bindParam(':post_id', $postId, PDO::PARAM_INT);
            $countStmt->execute();
            $newCommentCount = (int) $countStmt->fetchColumn();

            // Fetch the newly created comment data
            $newCommentStmt = $this->db->prepare("
                SELECT
                    c.id AS comment_id, c.content, c.created_at AS comment_created_at,
                    u.id AS author_id, u.name AS author_name, u.picture_url AS author_picture_url
                FROM comments c JOIN users u ON c.user_id = u.id
                WHERE c.id = :comment_id
            ");
            $newCommentStmt->bindParam(':comment_id', $newCommentId, PDO::PARAM_INT);
            $newCommentStmt->execute();
            $fetchedCommentData = $newCommentStmt->fetch(PDO::FETCH_ASSOC);

             // Format time_ago for the newly fetched comment data
            if ($fetchedCommentData) {
                 $fetchedCommentData['time_ago'] = $this->formatTimeAgo($fetchedCommentData['comment_created_at'] ?? null);
                 $newCommentData = $fetchedCommentData; // Assign to the variable used in the response
            } else {
                 // Handle case where fetching the new comment failed unexpectedly
                 error_log("Failed to fetch newly created comment with ID: {$newCommentId}");
                 // You might still commit but the returned comment data will be null
                 $newCommentData = null;
            }


            $this->db->commit(); // Commit transaction

            http_response_code(201); // Created
            echo json_encode([
                'success' => true,
                'message' => 'Comment added.',
                'comment' => $newCommentData, // Send back the full new comment object (or null if fetch failed)
                'newCommentCount' => $newCommentCount
            ]);

        } catch (PDOException $e) { // Catch specific PDO errors
             if ($this->db->inTransaction()) { // Check if transaction was started before rolling back
                $this->db->rollBack();
             }
             error_log("PDO Error adding comment for post {$postId}, user {$this->currentUserId}: " . $e->getMessage());
             http_response_code(500);
             $errorMessage = APP_ENV === 'development' ? $e->getMessage() : 'Failed to add comment due to database error.';
             echo json_encode(['success' => false, 'message' => $errorMessage]);
        } catch (Throwable $e) { // Catch other potential errors
             if ($this->db->inTransaction()) {
                $this->db->rollBack();
             }
             error_log("General Error adding comment for post {$postId}, user {$this->currentUserId}: " . $e->getMessage() . "\n" . $e->getTraceAsString());
             http_response_code(500);
             $errorMessage = APP_ENV === 'development' ? 'Error: ' . $e->getMessage() : 'An unexpected error occurred while adding the comment.';
             echo json_encode(['success' => false, 'message' => $errorMessage]);
        }
        exit;
    }

    // Optional: destroy method for deleting comments
    // public function destroy(int $commentId): void { ... }


    /**
      * Simple helper to format time elapsed.
      * Makes sure strtotime doesn't fail on invalid input.
      * @param ?string $timestamp SQL timestamp string or null
      * @return string Formatted time string (e.g., "5 min ago") or empty string
      */
     private function formatTimeAgo(?string $timestamp): string
     {
         if ($timestamp === null) {
             return ''; // Return empty if timestamp is null
         }
         $time = strtotime($timestamp);
         // Check if strtotime failed
         if ($time === false) {
             error_log("formatTimeAgo failed to parse timestamp: " . $timestamp);
             return 'invalid date'; // Or return the original string, or empty
         }

         $now = time();
         $diff = $now - $time;

         if ($diff < 0) return 'in the future'; // Handle edge case
         if ($diff < 60) return $diff . ' sec ago';
         if ($diff < 3600) return floor($diff / 60) . ' min ago';
         if ($diff < 86400) return floor($diff / 3600) . ' hr ago';
         if ($diff < 604800) return floor($diff / 86400) . ' day ago';
         // Older than a week: "Mar 15" (example format)
         return date('M j', $time);
         // Or include year: return date('M j, Y', $time);
     }
}