<?php
// public/index.php
require_once __DIR__.'/../config/config.php';
require_once __DIR__.'/../vendor/autoload.php';

// Include the layout (which will include Vite assets)
// In a real app, a router would handle this
require_once __DIR__.'/../views/layouts/app.php';
?>