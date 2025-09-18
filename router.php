<?php

// Simular un .htaccess con rutas permitidas
$allowedFiles = ['index.php'];
$allowedDirectories = ['styles', 'js'];

// Obtener la URI solicitada
$requestUri = $_SERVER['REQUEST_URI'];
$requestPath = parse_url($requestUri, PHP_URL_PATH);

// Manejar la solicitud del favicon
if ($requestPath === '/favicon.ico') {
    $faviconPath = __DIR__ . '/favicon.ico'; // Ruta al favicon en el directorio raíz
    if (file_exists($faviconPath)) {
        header('Content-Type: image/x-icon');
        readfile($faviconPath);
        exit;
    } else {
        http_response_code(404);
        echo "Favicon not found.";
        exit;
    }
}

// Si la solicitud no tiene path o termina con "/", redirigir a index.html o index.php
if ($requestPath === '/' || substr($requestPath, -1) === '/') {
    $indexFiles = ['index.html', 'index.php'];
    foreach ($indexFiles as $index) {
        if (file_exists(__DIR__ . $requestPath . $index)) {
            include __DIR__ . $requestPath . $index;
            exit;
        }
    }
}

// Si es un archivo permitido, deja que el servidor lo maneje
foreach ($allowedFiles as $file) {
    if ($requestPath === '/' . $file) {
        return false; // Deja que el servidor maneje el archivo
    }
}

// Si es un directorio permitido, deja que el servidor lo maneje
foreach ($allowedDirectories as $directory) {
    if (strpos($requestPath, '/' . $directory . '/') === 0) {
        return false; // Deja que el servidor maneje el archivo
    }
}

// Bloquear el acceso a todo lo demás
http_response_code(403);
echo "403 Forbidden";
exit;
