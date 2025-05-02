<?php

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
    public function follow(int $userId): void
    {
        $this->handleFollowUnfollow($userId, true);
    }

    /**
     * Unfollow a user.
     * @param int $userId The ID of the user to unfollow (Matches route parameter name)
     */
    public function unfollow(int $userId): void
    {
         $this->handleFollowUnfollow($userId, false);
    }

    public function getFollowers(int $userId): void
    {
        $this->fetchFollowList($userId, 'followers');
    }

    public function getFollowing(int $userId): void
    {
        $this->fetchFollowList($userId, 'following');
    }

    private function fetchFollowList(int $userId, string $type): void
    {
        header('Content-Type: application/json');
        if ($this->db === null) {
            http_response_code(500); echo json_encode(['success' => false, 'message' => 'DB error.', 'users' => []]); exit;
        }

        $joinColumn = ($type === 'followers') ? 'f.follower_id' : 'f.following_id';
        $whereColumn = ($type === 'followers') ? 'f.following_id' : 'f.follower_id';

        try {
             $sql = "SELECT
                        u.id, u.name, u.nickname, u.picture_url";

             if ($this->currentUserId !== null) {
                 $sql .= ", (SELECT COUNT(*) FROM follows f_check
                            WHERE f_check.follower_id = :current_user_id AND f_check.following_id = u.id) > 0 AS viewer_is_following";
             } else {
                 $sql .= ", 0 AS viewer_is_following";
             }

             $sql .= " FROM users u
                      JOIN follows f ON u.id = {$joinColumn}
                      WHERE {$whereColumn} = :target_user_id
                      ORDER BY f.created_at DESC";

             $stmt = $this->db->prepare($sql);
             $stmt->bindParam(':target_user_id', $userId, PDO::PARAM_INT);
              if ($this->currentUserId !== null) {
                 $stmt->bindParam(':current_user_id', $this->currentUserId, PDO::PARAM_INT);
             }
             $stmt->execute();
             $users = $stmt->fetchAll(PDO::FETCH_ASSOC);

             foreach($users as &$user) {
                 $user['viewer_is_following'] = (bool)$user['viewer_is_following'];
             }
             unset($user);

             echo json_encode(['success' => true, 'users' => $users]);

        } catch (Throwable $e) {
             error_log("Error fetching {$type} for user {$userId}: " . $e->getMessage());
             http_response_code(500);
             echo json_encode(['success' => false, 'message' => "Error fetching {$type}.", 'users' => []]);
        }
        exit;
    }


    /**
     * Shared logic for follow/unfollow actions.
     * @param int $targetUserId The ID of the user being followed/unfollowed.
     * @param bool $isFollowing True to follow, false to unfollow.
     */
    private function handleFollowUnfollow(int $targetUserId, bool $isFollowing): void
    {
        header('Content-Type: application/json');
        if ($this->currentUserId === null) { /*...*/ exit; }
        if ($this->db === null) { /*...*/ exit; }
        if ($targetUserId === $this->currentUserId) { /*...*/ exit; }
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


                 if ($wasChanged) {
                      error_log("[Notification Check - Follow] Conditions met (New Follow=true). Attempting notification insert...");
                      $notifyStmt = $this->db->prepare(
                          "INSERT INTO notifications (user_id, type, actor_user_id, post_id, created_at)
                           VALUES (:user_id, 'follow', :actor_user_id, NULL, NOW())"
                      );
                      $notifySuccess = $notifyStmt->execute([
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