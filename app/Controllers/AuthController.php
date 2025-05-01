<?php
// app/Controllers/AuthController.php

namespace App\Controllers;

use Google\Client as GoogleClient;
use Google\Service\Oauth2 as GoogleOauth2;
use PDO;

class AuthController
{
    private GoogleClient $googleClient;
    private ?PDO $db;

    public function __construct()
    {
        $this->googleClient = new GoogleClient();
        $this->googleClient->setClientId(config('GOOGLE_CLIENT_ID'));
        $this->googleClient->setClientSecret(config('GOOGLE_CLIENT_SECRET'));
        $this->googleClient->setRedirectUri(config('GOOGLE_REDIRECT_URI'));
        $this->googleClient->addScope('email');
        $this->googleClient->addScope('profile');
        // openid scope is often implicitly included or sometimes needed explicitly
        $this->googleClient->addScope('openid');

        $this->db = get_db_connection();
        if ($this->db === null) {
             error_log("AuthController: Failed to get DB connection.");
        }
    }

    public function redirectToGoogle(): void
    {
        $authUrl = $this->googleClient->createAuthUrl();
        header('Location: ' . filter_var($authUrl, FILTER_SANITIZE_URL));
        exit;
    }

    public function handleGoogleCallback(): void
    {
        if (!isset($_GET['code'])) {
            error_log("Google callback missing 'code' parameter.");
            header('Location: ' . BASE_URL . '/?error=google_auth_failed');
            exit;
        }

        try {
            // Exchange code for token
            $token = $this->googleClient->fetchAccessTokenWithAuthCode($_GET['code']);

            // Check for token error BEFORE setting token
            if (isset($token['error'])) {
                error_log("Google token fetch error: " . ($token['error_description'] ?? $token['error']));
                header('Location: ' . BASE_URL . '/?error=google_token_error');
                exit;
            }

            // Set access token
            $this->googleClient->setAccessToken($token);

            // --- Fetch User Info ---
            $oauth2 = new GoogleOauth2($this->googleClient);
            $googleUserInfo = null; // Initialize to null
             try {
                 // Explicitly try to get user info
                 $googleUserInfo = $oauth2->userinfo->get();
             } catch (\Throwable $userInfoError) {
                  // Catch error specifically during userinfo fetch
                  error_log("Error fetching Google user info: " . $userInfoError->getMessage());
                  // Optionally log $userInfoError->getTraceAsString()
                  // Fall through to the check below, $googleUserInfo will be null
             }


            // --- !! VERIFY USER INFO !! ---
            // Check if we successfully got the object AND it has the necessary data
            if (!$googleUserInfo || !$googleUserInfo->getId() || !$googleUserInfo->getEmail()) {
                error_log("Failed to retrieve valid/complete user info from Google API.");
                // Log what we received if anything (helps debugging scope/permission issues)
                if ($googleUserInfo) {
                    error_log("Partial Google User Info received: " . print_r($googleUserInfo->toPrimitive(), true));
                } else {
                    error_log("Google User Info was null.");
                }
                header('Location: ' . BASE_URL . '/?error=google_userinfo_error');
                exit; // Exit immediately if user info is invalid/missing
            }
            // --- End Verification ---

            // --- Proceed only if $googleUserInfo is valid ---
            if ($this->db === null) {
                 throw new \RuntimeException("Database connection not available for Google callback.");
            }

            $stmt = $this->db->prepare("SELECT id, name, email, picture_url FROM users WHERE google_id = :google_id");
            $stmt->execute(['google_id' => $googleUserInfo->getId()]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);

            $userId = null;
            $userData = [];

            if ($user) {
                // User exists
                $userId = $user['id'];
                $updateStmt = $this->db->prepare("UPDATE users SET name = :name, picture_url = :picture, updated_at = NOW() WHERE id = :id");
                $updateStmt->execute([
                    'name' => $googleUserInfo->getName(),
                    'picture' => $googleUserInfo->getPicture(),
                    'id' => $userId
                ]);
                $userData = [
                    'id' => $userId,
                    'name' => $googleUserInfo->getName(),
                    'email' => $user['email'], // Use existing email
                    'picture_url' => $googleUserInfo->getPicture()
                ];
            } else {
                // Create new user
                $insertStmt = $this->db->prepare(
                    "INSERT INTO users (google_id, email, name, picture_url, created_at, updated_at)
                     VALUES (:google_id, :email, :name, :picture, NOW(), NOW())"
                );
                $insertStmt->execute([
                    'google_id' => $googleUserInfo->getId(),
                    'email' => $googleUserInfo->getEmail(),
                    'name' => $googleUserInfo->getName(),
                    'picture' => $googleUserInfo->getPicture(),
                ]);
                $userId = $this->db->lastInsertId();
                $userData = [
                    'id' => $userId,
                    'name' => $googleUserInfo->getName(),
                    'email' => $googleUserInfo->getEmail(),
                    'picture_url' => $googleUserInfo->getPicture()
                ];
            }

            session_regenerate_id(true);
            $_SESSION['user'] = $userData;
            $_SESSION['logged_in'] = true;

            header('Location: ' . BASE_URL . '/'); // Redirect to feed on success
            exit;

        } catch (\Throwable $e) { // Catch other exceptions (e.g., token exchange failure)
            error_log("Google callback general error: " . $e->getMessage() . "\n" . $e->getTraceAsString());
            header('Location: ' . BASE_URL . '/?error=google_internal_error');
            exit;
        }
    }

    public function logout(): void
    {
        // ... (logout code remains the same) ...
        $_SESSION = [];
        if (ini_get("session.use_cookies")) {
            $params = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000,
                $params["path"], $params["domain"],
                $params["secure"], $params["httponly"]
            );
        }
        session_destroy();
        header('Location: ' . BASE_URL . '/');
        exit;
    }
}