<?php
// app/Controllers/UserController.php

namespace App\Controllers;

use PDO;
use PDOException;
use Throwable;

class UserController
{
    private ?PDO $db;
    private ?int $currentUserId = null;

    public function __construct()
    {
        $this->db = get_db_connection();
        if ($this->db === null) {
             error_log("UserController: Failed to get DB connection.");
        }
        if (isset($_SESSION['logged_in']) && $_SESSION['logged_in'] === true && isset($_SESSION['user']['id'])) {
            $this->currentUserId = (int) $_SESSION['user']['id'];
        }
    }

    /**
     * Follow a user.
     * POST /api/users/{userId}/follow
     * @param int $userId The ID of the user to follow (Matches route parameter name)
     */
    public function follow(int $userId): void // <-- CHANGED Parameter name
    {
        $this->handleFollowUnfollow($userId, true); // <-- Pass $userId
    }

    /**
     * Unfollow a user.
     * DELETE /api/users/{userId}/follow
     * @param int $userId The ID of the user to unfollow (Matches route parameter name)
     */
    public function unfollow(int $userId): void // <-- CHANGED Parameter name
    {
         $this->handleFollowUnfollow($userId, false); // <-- Pass $userId
    }


    /**
     * Shared logic for follow/unfollow actions.
     * @param int $targetUserId The ID of the user being followed/unfollowed.
     * @param bool $isFollowing True to follow, false to unfollow.
     */
    private function handleFollowUnfollow(int $targetUserId, bool $isFollowing): void
    {
        header('Content-Type: application/json');
        // Auth, DB, self-follow, target exists checks... (same as before)
        if ($this->currentUserId === null) { /*...*/ exit; }
        if ($this->db === null) { /*...*/ exit; }
        if ($targetUserId === $this->currentUserId) { /*...*/ exit; }
        // ... check if target user exists ...
         error_log("[Follow/Unfollow] Target User ID: {$targetUserId}, Current User ID: {$this->currentUserId}, Action: " . ($isFollowing ? 'Follow' : 'Unfollow'));


         try {
            $this->db->beginTransaction();
            $wasChanged = false;

            if ($isFollowing) {
                 $sql = "INSERT IGNORE INTO follows (follower_id, following_id, created_at) VALUES (:follower_id, :following_id, NOW())";
                 $stmt = $this->db->prepare($sql);
                 $stmt->bindParam(':follower_id', $this->currentUserId, PDO::PARAM_INT);
                 $stmt->bindParam(':following_id', $targetUserId, PDO::PARAM_INT);
                 $stmt->execute();
                 $wasChanged = $stmt->rowCount() > 0;
                 error_log("[Follow Attempt] Target User ID: {$targetUserId}, Follower ID: {$this->currentUserId}, New Follow Inserted: " . ($wasChanged ? 'Yes' : 'No (or already existed)'));


                 // --- Check Notification Condition ---
                 if ($wasChanged) { // Only notify if it's a new follow
                      error_log("[Notification Check - Follow] Conditions met (New Follow=true). Attempting notification insert...");
                      $notifyStmt = $this->db->prepare(
                          "INSERT INTO notifications (user_id, type, actor_user_id, post_id, created_at)
                           VALUES (:user_id, 'follow', :actor_user_id, NULL, NOW())"
                      );
                      $notifySuccess = $notifyStmt->execute([ // Capture result
                          ':user_id' => $targetUserId,
                          ':actor_user_id' => $this->currentUserId
                      ]);
                      error_log("[Notification Attempt - Follow] Insert successful: " . ($notifySuccess ? 'Yes' : 'No') . ". Target User: {$targetUserId}");

                 } else {
                      error_log("[Notification Check - Follow] Conditions NOT met. New Follow: false");
                 }
            } else {
                 error_log("[Unfollow Attempt] Target User ID: {$targetUserId}, Follower ID: {$this->currentUserId}");
                 $sql = "DELETE FROM follows WHERE follower_id = :follower_id AND following_id = :following_id";
                 $stmt = $this->db->prepare($sql);
                 $stmt->bindParam(':follower_id', $this->currentUserId, PDO::PARAM_INT);
                 $stmt->bindParam(':following_id', $targetUserId, PDO::PARAM_INT);
                 $stmt->execute();
            }

            error_log("[Follow/Unfollow] Committing transaction for Target User ID: {$targetUserId}");
            $this->db->commit();

            http_response_code(200);
            echo json_encode(['success' => true, 'message' => $isFollowing ? 'Followed.' : 'Unfollowed.', 'isFollowingNow' => $isFollowing]);

         } catch (Throwable $e) {
             error_log("[Follow/Unfollow ERROR] Target User ID: {$targetUserId}, Error: " . $e->getMessage());
              if ($this->db->inTransaction()) {
                   error_log("[Follow/Unfollow ERROR] Rolling back transaction.");
                   $this->db->rollBack();
              }
              http_response_code(500); $errMsg = 'Error processing follow request.';
              echo json_encode(['success' => false, 'message' => $errMsg]);
         }
          exit;
    }
}