<?php
// config/routes.php

use App\Controllers\AuthController;
use App\Controllers\FeedController;
use App\Controllers\ProfileController; // Keep this
use App\Controllers\PostController;
use App\Controllers\CommentController;

/** @var FastRoute\RouteCollector $r */

// --- Define Routes ---

// Pages
$r->addRoute('GET', '/', [FeedController::class, 'index']);
$r->addRoute('GET', '/profile', [ProfileController::class, 'show']); // Route for viewing own profile

// Authentication
$r->addRoute('GET', '/auth/google', [AuthController::class, 'redirectToGoogle']);
$r->addRoute('GET', '/auth/google/callback', [AuthController::class, 'handleGoogleCallback']);
$r->addRoute('GET', '/logout', [AuthController::class, 'logout']);

// Profile Actions (New)
$r->addRoute('POST', '/profile/update', [ProfileController::class, 'update']); // Update profile (nickname)
$r->addRoute('POST', '/profile/delete', [ProfileController::class, 'destroy']); // Delete account (using POST)

// Posts Actions
$r->addRoute('POST', '/profile/posts', [ProfileController::class, 'storePost']); // Create post

// API Routes (Likes, Comments)
$r->addRoute('POST', '/api/posts/{id:\d+}/like', [PostController::class, 'like']);
$r->addRoute('DELETE', '/api/posts/{id:\d+}/like', [PostController::class, 'unlike']);
$r->addRoute('GET', '/api/posts/{postId:\d+}/comments', [CommentController::class, 'index']);
$r->addRoute('POST', '/api/posts/{postId:\d+}/comments', [CommentController::class, 'store']);