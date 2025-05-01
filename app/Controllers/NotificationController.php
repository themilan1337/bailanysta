<?php
// app/Controllers/NotificationController.php

namespace App\Controllers;

use PDO;
use PDOException;
use Throwable;

class NotificationController
{
    private ?PDO $db;
    private ?int $currentUserId = null;

    public function __construct()
    {
        $this->db = get_db_connection();
        if ($this->db === null) { error_log("NotificationController: DB connection failed."); }
        if (isset($_SESSION['logged_in']) && $_SESSION['logged_in'] === true && isset($_SESSION['user']['id'])) {
            $this->currentUserId = (int) $_SESSION['user']['id'];
        }
    }

    /**
     * Fetch notifications for the logged-in user.
     * GET /api/notifications
     * Optional query params: ?status=unread (default) or ?status=all, ?limit=10
     */
    public function index(): void
    {
        header('Content-Type: application/json');
        if ($this->currentUserId === null) { http_response_code(401); echo json_encode(['success' => false, 'message' => 'Auth required.']); exit; }
        if ($this->db === null) { http_response_code(500); echo json_encode(['success' => false, 'message' => 'DB error.']); exit; }

        // Simple implementation: fetch latest ~15 unread notifications + unread count
        $limit = 15;

        try {
            // Get unread count
            $countStmt = $this->db->prepare("SELECT COUNT(*) FROM notifications WHERE user_id = :user_id AND is_read = 0");
            $countStmt->execute([':user_id' => $this->currentUserId]);
            $unreadCount = (int) $countStmt->fetchColumn();

            // Fetch recent unread notifications with actor info
            $sql = "SELECT
                        n.id, n.type, n.post_id, n.is_read, n.created_at,
                        a.id AS actor_id, a.name AS actor_name, a.picture_url AS actor_picture
                    FROM notifications n
                    JOIN users a ON n.actor_user_id = a.id
                    WHERE n.user_id = :user_id AND n.is_read = 0
                    ORDER BY n.created_at DESC
                    LIMIT :limit";

            $stmt = $this->db->prepare($sql);
            $stmt->bindParam(':user_id', $this->currentUserId, PDO::PARAM_INT);
            $stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
            $stmt->execute();
            $notifications = $stmt->fetchAll(PDO::FETCH_ASSOC);

            // Add time ago formatting
            foreach($notifications as &$n) {
                $n['time_ago'] = $this->formatTimeAgo($n['created_at'] ?? null); // Use internal helper or global one
            }
            unset($n);

            echo json_encode([
                'success' => true,
                'unread_count' => $unreadCount,
                'notifications' => $notifications
            ]);

        } catch (Throwable $e) {
            error_log("Error fetching notifications for user {$this->currentUserId}: " . $e->getMessage());
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => 'Error fetching notifications.']);
        }
        exit;
    }

    /**
     * Mark notifications as read.
     * POST /api/notifications/mark-read
     * Expects JSON body: {"ids": [1, 2, 3]} (array of notification IDs) OR empty body to mark all unread.
     */
    public function markRead(): void
    {
        header('Content-Type: application/json');
        if ($this->currentUserId === null) { http_response_code(401); echo json_encode(['success' => false, 'message' => 'Auth required.']); exit; }
        if ($this->db === null) { http_response_code(500); echo json_encode(['success' => false, 'message' => 'DB error.']); exit; }

        $requestBody = file_get_contents('php://input');
        $data = json_decode($requestBody, true);
        $idsToProcess = $data['ids'] ?? null; // Get specific IDs if provided

        try {
            // Start Transaction for atomicity (Mark then Delete)
            $this->db->beginTransaction();

            // --- Step 1: Identify which notifications WILL be processed ---
            $fetchSql = "SELECT id FROM notifications WHERE user_id = :user_id AND is_read = 0";
            $fetchParams = [':user_id' => $this->currentUserId];
            $actualIdsToDelete = [];

            if (is_array($idsToProcess) && !empty($idsToProcess)) {
                // Filter specific IDs provided by the client
                $idsToProcess = array_filter($idsToProcess, 'is_int');
                if (!empty($idsToProcess)) {
                    $idPlaceholders = [];
                    foreach ($idsToProcess as $key => $id) {
                         $placeholder = ':id_' . $key;
                         $idPlaceholders[] = $placeholder;
                         $fetchParams[$placeholder] = $id;
                    }
                    $fetchSql .= " AND id IN (" . implode(',', $idPlaceholders) . ")";
                 } else {
                     // Array was provided but empty after filtering, default to processing all unread
                     $idsToProcess = null; // Fall back to fetching all unread
                 }
            }
            // If $idsToProcess is null or empty array after filtering, the original $fetchSql gets all unread

            $fetchStmt = $this->db->prepare($fetchSql);
            $fetchStmt->execute($fetchParams);
            $actualIdsToDelete = $fetchStmt->fetchAll(PDO::FETCH_COLUMN); // Get just the IDs

             error_log("Notifications to delete for user {$this->currentUserId}: " . implode(', ', $actualIdsToDelete));


             // --- Step 2: Delete the identified notifications ---
             $deletedCount = 0;
             if (!empty($actualIdsToDelete)) {
                 // Prepare DELETE statement using the fetched IDs
                 $deletePlaceholders = implode(',', array_fill(0, count($actualIdsToDelete), '?'));
                 $deleteSql = "DELETE FROM notifications WHERE user_id = ? AND id IN ({$deletePlaceholders})";

                 // Prepare parameters for DELETE (user ID first, then the notification IDs)
                 $deleteParams = array_merge([$this->currentUserId], $actualIdsToDelete);

                 $deleteStmt = $this->db->prepare($deleteSql);
                 $deleteSuccess = $deleteStmt->execute($deleteParams); // Execute with positional params
                 $deletedCount = $deleteStmt->rowCount();
                 error_log("Attempted deletion for user {$this->currentUserId}. Success: " . ($deleteSuccess?'Yes':'No') . ". Rows Deleted: {$deletedCount}");

             } else {
                  error_log("No valid notifications found to delete for user {$this->currentUserId}.");
             }

             $this->db->commit(); // Commit transaction

             echo json_encode(['success' => true, 'message' => "{$deletedCount} notification(s) processed and deleted."]);

        } catch (Throwable $e) {
             if ($this->db->inTransaction()) {
                 $this->db->rollBack();
             }
            error_log("Error processing/deleting notifications for user {$this->currentUserId}: " . $e->getMessage());
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => 'Error updating notifications.']);
        }
         exit;
    }

     // Using the internal, original time ago function for this controller
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