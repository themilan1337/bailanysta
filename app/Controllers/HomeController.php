<?php

declare(strict_types=1);

namespace App\Controllers;

class HomeController
{
    public function index(): void
    {
        // Data to pass to the view
        $data = [
            'pageTitle' => 'Home Feed',
            'message' => 'Welcome to Bailanysta!'
        ];

        // Render the view using the helper
        // It assumes a layout file 'layouts.app' will include 'pages.home'
        view('layouts.app', [ // Pass data needed by the layout
            'pageTitle' => $data['pageTitle'],
            'contentView' => 'pages.home', // Tell layout which page content to load
            'viewData' => $data // Pass page-specific data to the layout
        ]);
    }
}