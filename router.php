<?php
/**
 * router.php
 * Global router for PHP built-in server
 */

$uri = urldecode(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH));
$publicDir = __DIR__ . '/public';

// Normalizar el path
$normalized = '/' . ltrim($uri, '/');
$publicPath = realpath($publicDir . $normalized);

// Evitar path traversal
if (strpos($normalized, '..') !== false) {
    http_response_code(404);
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Not Found']);
    exit;
}

// 1. Si el archivo existe dentro de /public → servirlo manualmente
if ($publicPath && is_file($publicPath) && strpos($publicPath, $publicDir) === 0) {
    $ext = pathinfo($publicPath, PATHINFO_EXTENSION);

    // Tipos MIME básicos
    $mimeTypes = [
        'ico' => 'image/x-icon',
        'png' => 'image/png',
        'jpg' => 'image/jpeg',
        'jpeg'=> 'image/jpeg',
        'gif' => 'image/gif',
        'css' => 'text/css',
        'js'  => 'application/javascript',
        'html'=> 'text/html'
    ];

    $mime = $mimeTypes[$ext] ?? 'application/octet-stream';
    header("Content-Type: $mime");
    readfile($publicPath);
    exit;
}

// 2. Si el archivo no existe, devolver 404 JSON
if ($normalized !== '/' && pathinfo($normalized, PATHINFO_EXTENSION)) {
    http_response_code(404);
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Not Found']);
    exit;
}

// 3. Todo lo demás → pasa a index.php
require_once $publicDir . '/index.php';
