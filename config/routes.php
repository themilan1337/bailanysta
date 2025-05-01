<?php
// config/routes.php

use App\Controllers\AuthController;
use App\Controllers\FeedController;
use App\Controllers\ProfileController;
use App\Controllers\PostController;
use App\Controllers\CommentController;
use App\Controllers\UserController;
use App\Controllers\NotificationController;
use App\Controllers\AiController;
/**
 * Defines the application routes.
 * The $r variable is an instance of FastRoute\RouteCollector provided by public/index.php
 *
 * @var FastRoute\RouteCollector $r
 */

// --- Page Routes ---
$r->addRoute('GET', '/', [FeedController::class, 'index']); // Main feed/homepage
$r->addRoute('GET', '/profile', [ProfileController::class, 'show']); // View own profile
$r->addRoute('GET', '/profile/{userId:\d+}', [ProfileController::class, 'showById']); // View other users' profiles by ID

// --- Authentication Routes ---
$r->addRoute('GET', '/auth/google', [AuthController::class, 'redirectToGoogle']); // Redirect to Google for login
$r->addRoute('GET', '/auth/google/callback', [AuthController::class, 'handleGoogleCallback']); // Handle callback from Google
$r->addRoute('GET', '/logout', [AuthController::class, 'logout']); // Logout user

// --- Profile Action Routes (Form Submissions) ---
$r->addRoute('POST', '/profile/update', [ProfileController::class, 'update']); // Handle nickname update form
$r->addRoute('POST', '/profile/delete', [ProfileController::class, 'destroy']); // Handle account deletion form

// --- Post Action Routes (Form Submissions) ---
$r->addRoute('POST', '/profile/posts', [ProfileController::class, 'storePost']); // Handle create post form submission

// --- API Routes ---

// Likes API
$r->addRoute('POST', '/api/posts/{id:\d+}/like', [PostController::class, 'like']); // Like a post
$r->addRoute('DELETE', '/api/posts/{id:\d+}/like', [PostController::class, 'unlike']); // Unlike a post

// Comments API
$r->addRoute('GET', '/api/posts/{postId:\d+}/comments', [CommentController::class, 'index']); // Get comments for a post
$r->addRoute('POST', '/api/posts/{postId:\d+}/comments', [CommentController::class, 'store']); // Add a comment to a post
// $r->addRoute('DELETE', '/api/comments/{id:\d+}', [CommentController::class, 'destroy']); // Optional: Delete comment route

// Posts API (Update)
$r->addRoute('POST', '/api/posts/{id:\d+}/update', [PostController::class, 'update']);
$r->addRoute('DELETE', '/api/posts/{id:\d+}', [PostController::class, 'destroy']); // <-- Add Delete Route

// Follows API
$r->addRoute('POST', '/api/users/{userId:\d+}/follow', [UserController::class, 'follow']); // Follow a user
$r->addRoute('DELETE', '/api/users/{userId:\d+}/follow', [UserController::class, 'unfollow']); // Unfollow a user

// --- NOTIFICATION API Routes (New) ---
$r->addRoute('GET', '/api/notifications', [NotificationController::class, 'index']); // Get unread notifications (or all recent)
$r->addRoute('POST', '/api/notifications/mark-read', [NotificationController::class, 'markRead']); // Mark notifications as read

$r->addRoute('GET', '/api/posts/search', [FeedController::class, 'search']);

$r->addRoute('POST', '/api/ai/generate-post-idea', [AiController::class, 'generatePostIdea']);