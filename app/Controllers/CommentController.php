<?php
// app/Controllers/CommentController.php

namespace App\Controllers;

use PDO;
use PDOException;
use Throwable;

class CommentController
{
    private ?PDO $db;
    private ?int $currentUserId = null;

    public function __construct()
    {
        $this->db = get_db_connection();
        if ($this->db === null) {
             error_log("CommentController: Failed to get DB connection during construction.");
        }
        if (isset($_SESSION['logged_in']) && $_SESSION['logged_in'] === true && isset($_SESSION['user']['id'])) {
            $this->currentUserId = (int) $_SESSION['user']['id'];
        }
    }

    public function index(int $postId): void
    {
        header('Content-Type: application/json');
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
            foreach($comments as &$comment) {
                $comment['time_ago'] = $this->formatTimeAgo($comment['comment_created_at'] ?? null);
            }
            unset($comment);
            echo json_encode(['success' => true, 'comments' => $comments]);
        } catch (PDOException $e) {
            error_log("PDO Error fetching comments for post {$postId}: " . $e->getMessage());
            http_response_code(500);
            $errorMessage = APP_ENV === 'development' ? $e->getMessage() : 'Failed to fetch comments due to database error.';
            echo json_encode(['success' => false, 'message' => $errorMessage, 'comments' => []]);
        } catch (Throwable $e) {
             error_log("General Error fetching comments for post {$postId}: " . $e->getMessage() . "\n" . $e->getTraceAsString());
             http_response_code(500);
             $errorMessage = APP_ENV === 'development' ? 'Error: ' . $e->getMessage() : 'An unexpected error occurred while fetching comments.';
             echo json_encode(['success' => false, 'message' => $errorMessage, 'comments' => []]);
        }
        exit;
    }

    public function store(int $postId): void
    {
        header('Content-Type: application/json');

        // --- LOG RAW INPUT ---
        $requestBody = file_get_contents('php://input');
        error_log("[Comment Store - Raw Input] Received Body: " . $requestBody);
        // --- END LOG ---

        if ($this->currentUserId === null) {
            http_response_code(401);
            echo json_encode(['success' => false, 'message' => 'Authentication required.']);
            exit;
        }
         if ($this->db === null) {
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => 'Database connection unavailable.']);
            exit;
        }
        $data = json_decode($requestBody, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
             if(!empty(trim($requestBody))) {
                 error_log("[Comment Store - JSON Error] Failed to decode JSON body. Error: " . json_last_error_msg());
             } else {
                  error_log("[Comment Store - JSON Error] Received empty body.");
             }
             http_response_code(400);
             echo json_encode(['success' => false, 'message' => 'Invalid request format. Expected valid JSON.']);
             exit;
         }
        $content = trim($data['content'] ?? '');
        if (empty($content)) {
             error_log("[Comment Store - Validation Error] Comment content is empty after trim.");
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Comment content cannot be empty.']);
            exit;
        }
        if (strlen($content) > 1000) {
             error_log("[Comment Store - Validation Error] Comment content too long.");
             http_response_code(400);
             echo json_encode(['success' => false, 'message' => 'Comment is too long (max 1000 characters).']);
             exit;
        }
        $postOwnerId = null;
        $newCommentData = null;
        $newCommentCount = 0;
        try {
            $postOwnerStmt = $this->db->prepare("SELECT user_id FROM posts WHERE id = :post_id");
            $postOwnerStmt->bindParam(':post_id', $postId, PDO::PARAM_INT);
            $postOwnerStmt->execute();
            $postOwner = $postOwnerStmt->fetch(PDO::FETCH_ASSOC);
            if (!$postOwner) {
                 http_response_code(404); echo json_encode(['success' => false, 'message' => 'Post not found.']); exit;
            }
             $postOwnerId = (int)$postOwner['user_id'];
             error_log("[Comment Store] Post ID: {$postId}, Post Owner ID: {$postOwnerId}, Current User ID: {$this->currentUserId}");
            $this->db->beginTransaction();
            $sql = "INSERT INTO comments (user_id, post_id, content, created_at, updated_at) VALUES (:user_id, :post_id, :content, NOW(), NOW())";
            $stmt = $this->db->prepare($sql);
            $stmt->bindParam(':user_id', $this->currentUserId, PDO::PARAM_INT);
            $stmt->bindParam(':post_id', $postId, PDO::PARAM_INT);
            $stmt->bindParam(':content', $content, PDO::PARAM_STR);
            $stmt->execute();
            $newCommentId = $this->db->lastInsertId();
            error_log("[Comment Store] Comment inserted. New Comment ID: {$newCommentId}");
             if ($postOwnerId !== $this->currentUserId) {
                  error_log("[Notification Check - Comment] Conditions met (Not Own Post=true). Attempting notification insert...");
                 $notifyStmt = $this->db->prepare( "INSERT INTO notifications (user_id, type, actor_user_id, post_id, created_at) VALUES (:user_id, 'comment', :actor_user_id, :post_id, NOW())" );
                 $notifySuccess = $notifyStmt->execute([':user_id' => $postOwnerId, ':actor_user_id' => $this->currentUserId, ':post_id' => $postId ]);
                 error_log("[Notification Attempt - Comment] Insert successful: " . ($notifySuccess ? 'Yes' : 'No') . ". Target User: {$postOwnerId}");
             } else {
                 error_log("[Notification Check - Comment] Conditions NOT met. Is Own Post: true");
             }
            $countStmt = $this->db->prepare("SELECT COUNT(*) FROM comments WHERE post_id = :post_id");
            $countStmt->bindParam(':post_id', $postId, PDO::PARAM_INT); $countStmt->execute();
            $newCommentCount = (int) $countStmt->fetchColumn();
            $newCommentStmt = $this->db->prepare(" SELECT c.id AS comment_id, c.content, c.created_at AS comment_created_at, u.id AS author_id, u.name AS author_name, u.picture_url AS author_picture_url FROM comments c JOIN users u ON c.user_id = u.id WHERE c.id = :comment_id ");
            $newCommentStmt->bindParam(':comment_id', $newCommentId, PDO::PARAM_INT); $newCommentStmt->execute();
            $fetchedCommentData = $newCommentStmt->fetch(PDO::FETCH_ASSOC);
            if ($fetchedCommentData) {
                 $fetchedCommentData['time_ago'] = $this->formatTimeAgo($fetchedCommentData['comment_created_at'] ?? null);
                 $newCommentData = $fetchedCommentData;
            } else { error_log("Failed to fetch newly created comment with ID: {$newCommentId}"); $newCommentData = null; }
            error_log("[Comment Store] Committing transaction for Post ID: {$postId}");
            $this->db->commit();
            http_response_code(201);
            echo json_encode(['success' => true, 'message' => 'Comment added.', 'comment' => $newCommentData, 'newCommentCount' => $newCommentCount]);
        } catch (Throwable $e) {
             error_log("[Comment Store ERROR] Post ID: {$postId}, Error: " . $e->getMessage());
            if ($this->db->inTransaction()) { error_log("[Comment Store ERROR] Rolling back transaction."); $this->db->rollBack(); }
            http_response_code(500); $errMsg = 'Error adding comment.';
            echo json_encode(['success' => false, 'message' => $errMsg]);
        }
        exit;
    }

    private function formatTimeAgo(?string $timestamp): string
     {
         if ($timestamp === null) { return ''; }
         $time = strtotime($timestamp);
         if ($time === false) { return 'invalid date'; }
         $now = time(); $diff = $now - $time;
         if ($diff < 0) return 'in the future';
         if ($diff < 60 && $diff >= 0) { return $diff . ' sec ago'; }
         elseif ($diff < 3600 && $diff > 0) { return floor($diff / 60) . ' min ago'; }
         elseif ($diff < 86400 && $diff > 0) { return floor($diff / 3600) . ' hr ago'; }
         elseif ($diff < 604800 && $diff > 0) { return floor($diff / 86400) . ' day ago'; }
         else { return date('M j', $time); }
     }
}