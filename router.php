<?php
/**
 * router.php
 * Global router for PHP built-in server
 * Serves static files from /public or delegates to /public/index.php
 */

$uri = urldecode(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH));
$publicDir = realpath(__DIR__ . '/public');

// 1. Block directory traversal attempts
if (strpos($uri, '..') !== false) {
    http_response_code(404);
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Not Found']);
    exit;
}

// 2. Resolve full path safely
$publicPath = realpath($publicDir . $uri);

// If file exists and is inside /public, serve it
if ($publicPath !== false && strpos($publicPath, $publicDir) === 0 && is_file($publicPath)) {
    return false; // let PHP’s built-in server serve the file
}

// 3. Otherwise, forward to API router (index.php)
require_once __DIR__ . '/public/index.php';
