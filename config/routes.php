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
 *
 * @var FastRoute\RouteCollector $r
 */

// --- pages ---
$r->addRoute('GET', '/', [FeedController::class, 'index']);
$r->addRoute('GET', '/profile', [ProfileController::class, 'show']);
$r->addRoute('GET', '/profile/{userId:\d+}', [ProfileController::class, 'showById']);

// --- auth ---
$r->addRoute('GET', '/auth/google', [AuthController::class, 'redirectToGoogle']);
$r->addRoute('GET', '/auth/google/callback', [AuthController::class, 'handleGoogleCallback']);
$r->addRoute('GET', '/logout', [AuthController::class, 'logout']);

// --- profile actions ---
$r->addRoute('POST', '/profile/update', [ProfileController::class, 'update']);
$r->addRoute('POST', '/profile/delete', [ProfileController::class, 'destroy']);

// --- from submissions ---
$r->addRoute('POST', '/profile/posts', [ProfileController::class, 'storePost']);

// --- API Routes ---

// Likes API
$r->addRoute('POST', '/api/posts/{id:\d+}/like', [PostController::class, 'like']);
$r->addRoute('DELETE', '/api/posts/{id:\d+}/like', [PostController::class, 'unlike']);

// Comments API
$r->addRoute('GET', '/api/posts/{postId:\d+}/comments', [CommentController::class, 'index']);
$r->addRoute('POST', '/api/posts/{postId:\d+}/comments', [CommentController::class, 'store']);
// $r->addRoute('DELETE', '/api/comments/{id:\d+}', [CommentController::class, 'destroy']); todo: implement frontend delete comment

// Posts API (Update)
$r->addRoute('POST', '/api/posts/{id:\d+}/update', [PostController::class, 'update']);
$r->addRoute('DELETE', '/api/posts/{id:\d+}', [PostController::class, 'destroy']);

// --- follows API ---
$r->addRoute('POST', '/api/users/{userId:\d+}/follow', [UserController::class, 'follow']);
$r->addRoute('DELETE', '/api/users/{userId:\d+}/follow', [UserController::class, 'unfollow']);

// --- notifications API ---
$r->addRoute('GET', '/api/notifications', [NotificationController::class, 'index']);
$r->addRoute('POST', '/api/notifications/mark-read', [NotificationController::class, 'markRead']);

// --- Search API Routes ---
$r->addRoute('GET', '/api/posts/search', [FeedController::class, 'search']);

// --- AI API Routes ---
$r->addRoute('POST', '/api/ai/generate-post-idea', [AiController::class, 'generatePostIdea']);

// --- User API Routes ---
$r->addRoute('GET', '/api/users/{userId:\d+}/followers', [UserController::class, 'getFollowers']);
$r->addRoute('GET', '/api/users/{userId:\d+}/following', [UserController::class, 'getFollowing']);