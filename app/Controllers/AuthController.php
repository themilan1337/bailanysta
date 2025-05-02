<?php

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
            $token = $this->googleClient->fetchAccessTokenWithAuthCode($_GET['code']);
            if (isset($token['error'])) { header('Location: ' . BASE_URL . '/?error=google_token_error'); exit; }
            $this->googleClient->setAccessToken($token);

            $oauth2 = new GoogleOauth2($this->googleClient);
            $googleUserInfo = null;
            try { $googleUserInfo = $oauth2->userinfo->get(); } catch (Throwable $e) { error_log("Error fetching Google user info: " . $e->getMessage());}

            if (!$googleUserInfo || !$googleUserInfo->getId() || !$googleUserInfo->getEmail()) { header('Location: ' . BASE_URL . '/?error=google_userinfo_error'); exit; }
            if ($this->db === null) { throw new \RuntimeException("DB connection unavailable."); }

            $stmt = $this->db->prepare("SELECT id, name, email, picture_url, nickname FROM users WHERE google_id = :google_id");
            $stmt->execute(['google_id' => $googleUserInfo->getId()]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);

            $userId = null;
            $localPicturePath = null;
            $googlePictureUrl = $googleUserInfo->getPicture();

            if ($googlePictureUrl) {
                $localPicturePath = $this->saveAvatarFromUrl($googlePictureUrl, $googleUserInfo->getId());
                if ($localPicturePath === false) {
                     error_log("Failed to save avatar for Google User ID: " . $googleUserInfo->getId());
                     $localPicturePath = null;
                }
            }


            if ($user) {
                $userId = $user['id'];
                $currentLocalPath = $user['picture_url'];
                $newPathToSave = $localPicturePath ?? $currentLocalPath;

                if ($user['name'] !== $googleUserInfo->getName() || $newPathToSave !== $currentLocalPath) {
                     $updateStmt = $this->db->prepare("UPDATE users SET name = :name, picture_url = :picture, updated_at = NOW() WHERE id = :id");
                     $updateStmt->execute([
                         ':name' => $googleUserInfo->getName(),
                         ':picture' => $newPathToSave,
                         ':id' => $userId
                     ]);
                     if ($newPathToSave !== $currentLocalPath && $currentLocalPath && str_starts_with($currentLocalPath, '/uploads/users/')) {
                          $this->deleteLocalAvatar($currentLocalPath);
                     }
                }

                $userData = [
                    'id' => $userId,
                    'name' => $googleUserInfo->getName(),
                    'email' => $user['email'],
                    'picture_url' => $newPathToSave,
                    'nickname' => $user['nickname']
                ];

            } else {
                $insertStmt = $this->db->prepare(
                    "INSERT INTO users (google_id, email, name, picture_url, created_at, updated_at)
                     VALUES (:google_id, :email, :name, :picture, NOW(), NOW())"
                );
                $insertStmt->execute([
                    ':google_id' => $googleUserInfo->getId(),
                    ':email' => $googleUserInfo->getEmail(),
                    ':name' => $googleUserInfo->getName(),
                    ':picture' => $localPicturePath,
                ]);
                $userId = $this->db->lastInsertId();

                $userData = [
                    'id' => $userId,
                    'name' => $googleUserInfo->getName(),
                    'email' => $googleUserInfo->getEmail(),
                    'picture_url' => $localPicturePath,
                    'nickname' => null
                ];
            }

            session_regenerate_id(true);
            $_SESSION['user'] = $userData;
            $_SESSION['logged_in'] = true;

            header('Location: ' . BASE_URL . '/');
            exit;

        } catch (\Throwable $e) {
            error_log("Google callback general error: " . $e->getMessage() . "\n" . $e->getTraceAsString());
            header('Location: ' . BASE_URL . '/?error=google_internal_error');
            exit;
        }
    }

    public function logout(): void
    {
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

    private function saveAvatarFromUrl(string $url, string $googleUserId): string|false
    {
        try {
            $ch = curl_init($url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
            curl_setopt($ch, CURLOPT_TIMEOUT, 10);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);
            $imageData = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $contentType = curl_getinfo($ch, CURLINFO_CONTENT_TYPE);
            $curlError = curl_error($ch);
            curl_close($ch);

            if ($imageData === false || $curlError || $httpCode !== 200) {
                error_log("Failed to download avatar from {$url}. HTTP: {$httpCode}, cURL Error: {$curlError}");
                return false;
            }

            $allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
            if (!$contentType || !in_array(strtolower($contentType), $allowedTypes)) {
                 error_log("Downloaded avatar from {$url} has invalid content type: {$contentType}");
                 return false;
            }

            $extension = match (strtolower($contentType)) {
                'image/jpeg' => 'jpg',
                'image/png' => 'png',
                'image/gif' => 'gif',
                'image/webp' => 'webp',
                default => 'jpg',
            };

            $uploadDir = dirname(__DIR__, 2) . '/public/uploads/users/';
            $filename = 'avatar_' . hash('sha1', $googleUserId . time()) . '.' . $extension;
            $destination = $uploadDir . $filename;
            $publicUrlPath = '/uploads/users/' . $filename;

            if (!is_dir($uploadDir)) { @mkdir($uploadDir, 0775, true); }
            if (!is_writable($uploadDir)) {
                 error_log("Avatar upload directory not writable: " . $uploadDir);
                 return false;
            }

            if (file_put_contents($destination, $imageData) !== false) {
                 error_log("Avatar saved successfully: " . $destination);
                 return $publicUrlPath;
            } else {
                 error_log("Failed to save avatar to: " . $destination);
                 return false;
            }
        } catch (Throwable $e) {
            error_log("Exception while saving avatar from URL {$url}: " . $e->getMessage());
            return false;
        }
    }

     /**
      * Deletes a local avatar file if it exists.
      */
    private function deleteLocalAvatar(?string $relativePath): void
    {
         if (!$relativePath) return;

         $basePath = dirname(__DIR__, 2) . '/public';
         $filePath = $basePath . $relativePath;

         if (file_exists($filePath) && is_file($filePath)) {
             if (unlink($filePath)) {
                  error_log("Deleted old local avatar: " . $filePath);
             } else {
                  error_log("Failed to delete old local avatar: " . $filePath);
             }
         } else {
              error_log("Old local avatar not found for deletion: " . $filePath);
         }
    }

}