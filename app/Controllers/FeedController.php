<?php
// app/Controllers/FeedController.php

namespace App\Controllers;

use PDO; // <-- Add this

class FeedController
{
    private ?PDO $db;
    private ?int $currentUserId = null; // To store logged-in user ID

    public function __construct()
    {
        $this->db = get_db_connection(); // Get DB connection
        if ($this->db === null) {
            // Handle connection failure appropriately
            error_log("FeedController: Failed to get DB connection.");
            // Optional: throw new \RuntimeException("Database connection failed.");
        }

        // Check if user is logged in and store their ID
        if (isset($_SESSION['logged_in']) && $_SESSION['logged_in'] === true && isset($_SESSION['user']['id'])) {
             $this->currentUserId = (int) $_SESSION['user']['id'];
        }
    }

    /**
     * Display the main feed page.
     */
    public function index(): void
    {
        if ($this->db === null) {
            // Render view with an error message if DB connection failed
            echo view('pages.feed.index', [
                'pageTitle' => 'Feed',
                'posts' => [],
                'error' => 'Could not connect to the database.'
            ]);
            return;
        }

        // --- Fetch Posts ---
        // TODO: Add pagination later (LIMIT/OFFSET)
        $limit = 20; // Number of posts per page/load
        $offset = 0; // Start from the beginning for now

        // Base SQL query
        $sql = "
            SELECT
                p.id AS post_id,
                p.content,
                p.image_url,
                p.created_at AS post_created_at,
                u.id AS author_id,
                u.name AS author_name,
                u.picture_url AS author_picture_url,
                (SELECT COUNT(*) FROM likes l WHERE l.post_id = p.id) AS like_count,
                (SELECT COUNT(*) FROM comments c WHERE c.post_id = p.id) AS comment_count";

        // Add check if the current user liked the post (only if logged in)
        if ($this->currentUserId !== null) {
            $sql .= ",
                (SELECT COUNT(*) FROM likes l WHERE l.post_id = p.id AND l.user_id = :current_user_id) > 0 AS user_liked";
        } else {
             $sql .= ",
                0 AS user_liked"; // Default to false if not logged in
        }

        $sql .= "
            FROM posts p
            JOIN users u ON p.user_id = u.id
            ORDER BY p.created_at DESC
            LIMIT :limit OFFSET :offset
        ";

        try {
            $stmt = $this->db->prepare($sql);

            // Bind parameters
            $stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
            $stmt->bindParam(':offset', $offset, PDO::PARAM_INT);
            if ($this->currentUserId !== null) {
                $stmt->bindParam(':current_user_id', $this->currentUserId, PDO::PARAM_INT);
            }

            $stmt->execute();
            $posts = $stmt->fetchAll(PDO::FETCH_ASSOC);

            // Optional: Add simple time formatting (e.g., "2 hours ago")
            // Consider adding a helper function for this later
            foreach ($posts as &$post) { // Use reference to modify array directly
                 $post['time_ago'] = $this->formatTimeAgo($post['post_created_at']);
                 // Ensure user_liked is boolean (PDO might return 0/1 string)
                 $post['user_liked'] = (bool)$post['user_liked'];
            }
            unset($post); // Unset reference

        } catch (\PDOException $e) {
            error_log("Error fetching feed posts: " . $e->getMessage());
            $posts = []; // Ensure posts is an empty array on error
            $viewData['error'] = APP_ENV === 'development' ? 'Error fetching posts: ' . $e->getMessage() : 'Could not retrieve posts.';
        }

        // --- Render View ---
        $viewData = [
            'pageTitle' => 'Feed',
            'posts' => $posts,
        ];
        echo view('pages.feed.index', $viewData);
    }

     /**
      * Simple helper to format time elapsed.
      * @param string $timestamp SQL timestamp string
      * @return string Formatted time string (e.g., "5 min ago", "2 hr ago", "Mar 15")
      */
     private function formatTimeAgo(string $timestamp): string
     {
         $time = strtotime($timestamp);
         $now = time();
         $diff = $now - $time;

         if ($diff < 60) {
             return $diff . ' sec ago';
         } elseif ($diff < 3600) { // 60 * 60
             return floor($diff / 60) . ' min ago';
         } elseif ($diff < 86400) { // 60 * 60 * 24
             return floor($diff / 3600) . ' hr ago';
         } elseif ($diff < 604800) { // 60 * 60 * 24 * 7
             return floor($diff / 86400) . ' day ago';
         } else {
             return date('M j', $time); // Older than a week: "Mar 15"
             // Or: return date('M j, Y', $time); // Include year
         }
     }
}