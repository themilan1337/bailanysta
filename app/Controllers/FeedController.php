<?php

namespace App\Controllers;

use PDO;
use PDOException;

class FeedController
{
    private ?PDO $db;
    private ?int $currentUserId = null;

    public function __construct()
    {
        $this->db = get_db_connection();
        if ($this->db === null) {
            error_log("FeedController: Failed to get DB connection.");
        }
        if (isset($_SESSION['logged_in']) && $_SESSION['logged_in'] === true && isset($_SESSION['user']['id'])) {
             $this->currentUserId = (int) $_SESSION['user']['id'];
        }
    }

    public function index(): void
    {
        $viewData = ['pageTitle' => 'Feed', 'posts' => []];
        if ($this->db === null) {
            $viewData['error'] = 'Could not connect to the database.';
            echo view('pages.feed.index', $viewData);
            return;
        }
        $limit = 20;
        $offset = 0;
        $sql = "
            SELECT
                p.id AS post_id, p.content, p.image_url, p.created_at AS post_created_at,
                u.id AS author_id, u.name AS author_name, u.picture_url AS author_picture_url,
                (SELECT COUNT(*) FROM likes l WHERE l.post_id = p.id) AS like_count,
                (SELECT COUNT(*) FROM comments c WHERE c.post_id = p.id) AS comment_count";
        if ($this->currentUserId !== null) {
            $sql .= ", (SELECT COUNT(*) FROM likes l WHERE l.post_id = p.id AND l.user_id = :current_user_id) > 0 AS user_liked";
        } else {
             $sql .= ", 0 AS user_liked";
        }
        $sql .= "
            FROM posts p JOIN users u ON p.user_id = u.id
            ORDER BY p.created_at DESC LIMIT :limit OFFSET :offset";
        try {
            $stmt = $this->db->prepare($sql);
            $stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
            $stmt->bindParam(':offset', $offset, PDO::PARAM_INT);
            if ($this->currentUserId !== null) {
                $stmt->bindParam(':current_user_id', $this->currentUserId, PDO::PARAM_INT);
            }
            $stmt->execute();
            $posts = $stmt->fetchAll(PDO::FETCH_ASSOC);
            foreach ($posts as &$post) {
                 // Use the private method from THIS class
                 $post['time_ago'] = $this->formatTimeAgo($post['post_created_at'] ?? 'now'); // Pass default on null
                 $post['user_liked'] = (bool)($post['user_liked'] ?? false);
            }
            unset($post);
            $viewData['posts'] = $posts;
        } catch (PDOException $e) {
            error_log("Error fetching feed posts: " . $e->getMessage());
            $viewData['error'] = APP_ENV === 'development' ? 'Error fetching posts: ' . $e->getMessage() : 'Could not retrieve posts.';
            $viewData['posts'] = [];
        }
        echo view('pages.feed.index', $viewData);
    }

    public function search(): void
    {
        header('Content-Type: application/json');

        // Get search term from query parameter
        $searchTerm = trim($_GET['q'] ?? '');

        // Basic validation: Don't search for very short terms if desired
        if (strlen($searchTerm) < 2 && $searchTerm !== '') { // Allow empty search to maybe show all? Or require min length.
             echo json_encode(['success' => true, 'posts' => [], 'message' => 'Search term too short.']);
             exit;
        }

        if ($this->db === null) {
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => 'Database connection error.']);
            exit;
        }

        // --- Build Search Query ---
        // Simple search using LIKE on post content.
        // For hashtags, we'd need to parse/store them separately or use more complex LIKE/REGEX.
        // Add FULLTEXT index on `content` column for better performance on large datasets.
        $sql = "
            SELECT
                p.id AS post_id, p.content, p.image_url, p.created_at AS post_created_at,
                u.id AS author_id, u.name AS author_name, u.picture_url AS author_picture_url,
                (SELECT COUNT(*) FROM likes l WHERE l.post_id = p.id) AS like_count,
                (SELECT COUNT(*) FROM comments c WHERE c.post_id = p.id) AS comment_count";

        if ($this->currentUserId !== null) {
            $sql .= ", (SELECT COUNT(*) FROM likes l WHERE l.post_id = p.id AND l.user_id = :current_user_id) > 0 AS user_liked";
        } else {
             $sql .= ", 0 AS user_liked";
        }

        $sql .= "
            FROM posts p JOIN users u ON p.user_id = u.id ";

        $params = [];
        if ($searchTerm !== '') {
            // Use LIKE for simple keyword search
            // Add % wildcards for partial matches
            $sql .= " WHERE p.content LIKE :search_term ";
            $params[':search_term'] = '%' . $searchTerm . '%';
        }
        // Add other conditions if needed (e.g., search by user, date range)

        $sql .= " ORDER BY p.created_at DESC "; // Or ORDER BY relevance if using FULLTEXT
        $sql .= " LIMIT 50"; // Limit search results


        if ($this->currentUserId !== null) {
            $params[':current_user_id'] = $this->currentUserId;
        }

        try {
            $stmt = $this->db->prepare($sql);
            $stmt->execute($params); // Execute with parameters
            $posts = $stmt->fetchAll(PDO::FETCH_ASSOC);

            // Format time ago and like status
            foreach ($posts as &$post) {
                 $post['time_ago'] = $this->formatTimeAgo($post['post_created_at'] ?? null);
                 $post['user_liked'] = (bool)($post['user_liked'] ?? false);
            }
            unset($post);

            echo json_encode(['success' => true, 'posts' => $posts]);

        } catch (PDOException $e) {
            error_log("Error searching posts (term: '{$searchTerm}'): " . $e->getMessage());
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => 'Error performing search.']);
        }
        exit;
    }

     /**
      * Simple helper to format time elapsed (Original version).
      * @param string $timestamp SQL timestamp string
      * @return string Formatted time string
      */
    private function formatTimeAgo(string $timestamp): string
     {
         $time = strtotime($timestamp);
         // Basic check if strtotime failed, although less likely with DB format
         if ($time === false) { return 'invalid date'; }
         $now = time();
         $diff = $now - $time; // Could be negative if clocks are off

         if ($diff < 60 && $diff >= 0) { // Only show positive seconds ago
             return $diff . ' sec ago';
         } elseif ($diff < 3600 && $diff > 0) {
             return floor($diff / 60) . ' min ago';
         } elseif ($diff < 86400 && $diff > 0) {
             return floor($diff / 3600) . ' hr ago';
         } elseif ($diff < 604800 && $diff > 0) {
             return floor($diff / 86400) . ' day ago';
         } else if ($diff < 0) {
             // Handle potential future dates or clock sync issues
             return 'in the future'; // Or return formatted date
         }
         else {
             return date('M j', $time); // Older than a week
         }
     }
}