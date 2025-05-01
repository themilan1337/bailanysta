<?php

namespace App\Controllers;

use PDO;
use PDOException;

class FeedController
{
    private ?PDO $db;
    private ?int $currentUserId = null;
    private const DEFAULT_LIMIT = 5; // <-- Define the constant here

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
        $isAjaxRequest = isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest'
                      || (isset($_GET['ajax']) && $_GET['ajax'] == '1')
                      || (strpos($_SERVER['HTTP_ACCEPT'] ?? '', 'application/json') !== false);

        $limit = isset($_GET['limit']) && is_numeric($_GET['limit']) ? max(1, (int)$_GET['limit']) : self::DEFAULT_LIMIT; // Now uses the defined constant
        $offset = isset($_GET['offset']) && is_numeric($_GET['offset']) ? max(0, (int)$_GET['offset']) : 0;

        if ($this->db === null) {
            if ($isAjaxRequest) {
                header('Content-Type: application/json'); http_response_code(500);
                echo json_encode(['success' => false, 'message' => 'Database unavailable.', 'posts' => []]);
            } else {
                echo view('pages.feed.index', ['pageTitle' => 'Feed', 'posts' => [], 'error' => 'Could not connect to the database.']);
            }
            exit;
        }

        $posts = [];
        $error = null;

        $sql = " SELECT p.id AS post_id, p.content, p.image_url, p.created_at AS post_created_at, u.id AS author_id, u.name AS author_name, u.picture_url AS author_picture_url, (SELECT COUNT(*) FROM likes l WHERE l.post_id = p.id) AS like_count, (SELECT COUNT(*) FROM comments c WHERE c.post_id = p.id) AS comment_count";
        if ($this->currentUserId !== null) { $sql .= ", (SELECT COUNT(*) FROM likes l WHERE l.post_id = p.id AND l.user_id = :current_user_id) > 0 AS user_liked"; }
        else { $sql .= ", 0 AS user_liked"; }
        $sql .= " FROM posts p JOIN users u ON p.user_id = u.id ORDER BY p.created_at DESC LIMIT :limit OFFSET :offset";

        try {
            $stmt = $this->db->prepare($sql);
            $stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
            $stmt->bindParam(':offset', $offset, PDO::PARAM_INT);
            if ($this->currentUserId !== null) { $stmt->bindParam(':current_user_id', $this->currentUserId, PDO::PARAM_INT); }
            $stmt->execute();
            $posts = $stmt->fetchAll(PDO::FETCH_ASSOC);
            foreach ($posts as &$post) {
                 $post['time_ago'] = $this->formatTimeAgo($post['post_created_at'] ?? null);
                 $post['user_liked'] = (bool)($post['user_liked'] ?? false);
            }
            unset($post);
        } catch (PDOException $e) {
            error_log("Error fetching feed posts (offset:{$offset}, limit:{$limit}): " . $e->getMessage());
            $error = APP_ENV === 'development' ? 'Error fetching posts: ' . $e->getMessage() : 'Could not retrieve posts.';
        }

        if ($isAjaxRequest) {
            header('Content-Type: application/json');
            if ($error !== null) { http_response_code(500); echo json_encode(['success' => false, 'message' => $error, 'posts' => []]); }
            else { echo json_encode(['success' => true, 'posts' => $posts]); }
        } else {
            echo view('pages.feed.index', ['pageTitle' => 'Feed', 'posts' => $posts, 'error' => $error ]);
        }
        exit;
    }

    public function search(): void
    {
        header('Content-Type: application/json');
        $searchTerm = trim($_GET['q'] ?? '');
        if (strlen($searchTerm) < 2 && $searchTerm !== '') { echo json_encode(['success' => true, 'posts' => [], 'message' => 'Search term too short.']); exit; }
        if ($this->db === null) { http_response_code(500); echo json_encode(['success' => false, 'message' => 'Database connection error.']); exit; }

        $sql = " SELECT p.id AS post_id, p.content, p.image_url, p.created_at AS post_created_at, u.id AS author_id, u.name AS author_name, u.picture_url AS author_picture_url, (SELECT COUNT(*) FROM likes l WHERE l.post_id = p.id) AS like_count, (SELECT COUNT(*) FROM comments c WHERE c.post_id = p.id) AS comment_count";
        if ($this->currentUserId !== null) { $sql .= ", (SELECT COUNT(*) FROM likes l WHERE l.post_id = p.id AND l.user_id = :current_user_id) > 0 AS user_liked"; }
        else { $sql .= ", 0 AS user_liked"; }
        $sql .= " FROM posts p JOIN users u ON p.user_id = u.id ";
        $params = [];
        if ($searchTerm !== '') { $sql .= " WHERE p.content LIKE :search_term "; $params[':search_term'] = '%' . $searchTerm . '%'; }
        $sql .= " ORDER BY p.created_at DESC LIMIT 50";
        if ($this->currentUserId !== null) { $params[':current_user_id'] = $this->currentUserId; }

        try {
            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);
            $posts = $stmt->fetchAll(PDO::FETCH_ASSOC);
            foreach ($posts as &$post) {
                 $post['time_ago'] = $this->formatTimeAgo($post['post_created_at'] ?? null);
                 $post['user_liked'] = (bool)($post['user_liked'] ?? false);
            }
            unset($post);
            echo json_encode(['success' => true, 'posts' => $posts]);
        } catch (PDOException $e) {
            error_log("Error searching posts (term: '{$searchTerm}'): " . $e->getMessage());
            http_response_code(500); echo json_encode(['success' => false, 'message' => 'Error performing search.']);
        }
        exit;
    }

    private function formatTimeAgo(?string $timestamp): string {
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