<?php
/**
 * router.php
 * Global router for PHP built-in server
 * Serves static files from /public or delegates to /public/index.php
 */

$uri = urldecode(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH));
$publicPath = __DIR__ . '/public' . $uri;

// 1. Serve static files if they exist (JS, CSS, images, etc.)
if ($uri !== '/' && file_exists($publicPath) && !is_dir($publicPath)) {
    return false; // let PHP built-in server handle it directly
}

// 2. Otherwise, forward to API router (public/index.php)
require_once __DIR__ . '/public/index.php';
