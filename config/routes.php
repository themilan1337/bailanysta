<?php

declare(strict_types=1);

// Define application routes.
// The callback function receives the FastRoute Dispatcher instance.
return function(FastRoute\RouteCollector $r) {
    // Home page
    $r->addRoute('GET', '/', ['App\Controllers\HomeController', 'index']);

    // Example Login Route (we'll implement this later)
    $r->addRoute('GET', '/login', ['App\Controllers\AuthController', 'showLogin']);
    $r->addRoute('GET', '/auth/google', ['App\Controllers\AuthController', 'redirectToGoogle']);
    $r->addRoute('GET', '/auth/google/callback', ['App\Controllers\AuthController', 'handleGoogleCallback']);
    $r->addRoute('GET', '/logout', ['App\Controllers\AuthController', 'logout']);

    // Example Profile Route (Level 1)
    $r->addRoute('GET', '/profile', ['App\Controllers\ProfileController', 'index']);

    // Example Feed Route (Level 1)
    $r->addRoute('GET', '/feed', ['App\Controllers\FeedController', 'index']);

    // Add more routes here for posts, etc. later
};