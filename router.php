<?php
// ~/www/root_web/router.php

// ---- Config propia ----
$allowedFiles       = ['index.php'];
$allowedDirectories = ['styles', 'js'];

// Base del proyecto (root_web). __DIR__ ya apunta aquí porque este archivo vive en root_web.
$projectBaseReal = realpath(__DIR__);

// Obtener y normalizar la ruta solicitada
$requestUri  = $_SERVER['REQUEST_URI'] ?? '/';
$requestPath = parse_url($requestUri, PHP_URL_PATH) ?: '/';

// Decodificar por si hay %20, etc.
$decodedPath = rawurldecode($requestPath);

// Seguridad básica: bloquear path traversal
if (strpos($decodedPath, '..') !== false) {
    http_response_code(400);
    echo "Bad request";
    exit;
}

// Helper: resuelve path absoluto dentro de root_web y valida límites
$abs = function (string $p) use ($projectBaseReal) {
    $full = realpath($projectBaseReal . '/' . ltrim($p, '/'));
    if ($full === false) return false;
    // asegurar que no escape de root_web
    if (strpos($full, $projectBaseReal . DIRECTORY_SEPARATOR) !== 0 && $full !== $projectBaseReal) return false;
    return $full;
};

// Helper MIME
$getMime = function (string $ext) {
    $map = [
        'html'=> 'text/html; charset=utf-8',
        'js'  => 'application/javascript',
        'css' => 'text/css',
        'png' => 'image/png',
        'jpg' => 'image/jpeg',
        'jpeg'=> 'image/jpeg',
        'gif' => 'image/gif',
        'ico' => 'image/x-icon',
        'svg' => 'image/svg+xml',
        'json'=> 'application/json; charset=utf-8',
        'mp4' => 'video/mp4',
        'zip' => 'application/zip',
        'woff'=> 'font/woff',
        'woff2'=> 'font/woff2',
        'ttf' => 'font/ttf',
    ];
    return $map[strtolower($ext)] ?? 'application/octet-stream';
};

// --- Favicon ---
if ($decodedPath === '/favicon.ico') {
    $fav = $abs('favicon.ico');
    if ($fav && is_file($fav)) {
        header('Content-Type: image/x-icon');
        readfile($fav);
        exit;
    }
    http_response_code(404);
    echo "Favicon not found.";
    exit;
}

/*
 * --- Directorios permitidos ---
 * Si se pide un archivo dentro de un directorio permitido:
 *   - Si es .php y su basename NO está en $allowedFiles → 403
 *   - Si es .php y su basename SÍ está en $allowedFiles → include (ejecutar)
 *   - Si es estático → servir con su MIME
 * Si se pide el directorio (termina en "/"):
 *   - Probar index.php (ejecutarlo) y luego index.html (servirlo)
 */
foreach ($allowedDirectories as $dir) {
    if (strpos($decodedPath, '/' . $dir . '/') === 0) {
        $file = $abs($decodedPath);
        if ($file && is_file($file)) {
            $ext = strtolower(pathinfo($file, PATHINFO_EXTENSION));

            if ($ext === 'php') {
                $base = basename($decodedPath);
                if (in_array($base, $allowedFiles, true)) {
                    include $file; // <-- solo ejecuta si el nombre está whitelisteado
                    exit;
                }
                http_response_code(403);
                echo "403 Forbidden";
                exit;
            }

            header("Content-Type: " . $getMime($ext));
            readfile($file);
            exit;
        }

        // Si pidieron el directorio, intentar index.php y luego index.html
        $dirAbs = $abs($decodedPath);
        if ($dirAbs && is_dir($dirAbs)) {
            // index.php
            $cand = $abs(trim($decodedPath, '/') . '/index.php');
            if ($cand && is_file($cand)) { include $cand; exit; }
            // index.html
            $cand = $abs(trim($decodedPath, '/') . '/index.html');
            if ($cand && is_file($cand)) { header('Content-Type: text/html; charset=utf-8'); readfile($cand); exit; }
        }

        // Si no existe, 404
        http_response_code(404);
        echo "Not found";
        exit;
    }
}

// --- Archivos exactos permitidos en la raíz del proyecto ---
foreach ($allowedFiles as $fileName) {
    if ($decodedPath === '/' . $fileName) {
        $file = $abs($fileName);
        if ($file && is_file($file)) {
            $ext  = strtolower(pathinfo($file, PATHINFO_EXTENSION));
            if ($ext === 'php') { include $file; exit; } // ejecutables solo en raíz (whitelist)
            header("Content-Type: " . $getMime($ext));
            readfile($file);
            exit;
        }
        http_response_code(404);
        echo "Not found";
        exit;
    }
}

/*
 * --- Directorios por trailing slash general ---
 * Si la ruta termina con "/", solo permitir index.php / index.html
 * en:
 *   - la raíz "/"
 *   - o subdirectorios cuyo primer segmento esté en $allowedDirectories
 */
if ($decodedPath === '/' || substr($decodedPath, -1) === '/') {
    if ($decodedPath === '/') {
        // raíz del proyecto
        $cand = $abs('index.php');
        if ($cand && is_file($cand)) { include $cand; exit; }
        $cand = $abs('index.html');
        if ($cand && is_file($cand)) { header('Content-Type: text/html; charset=utf-8'); readfile($cand); exit; }
    } else {
        // subruta: validar primer segmento contra allowedDirectories
        $first = explode('/', trim($decodedPath, '/'))[0] ?? '';
        if (in_array($first, $allowedDirectories, true)) {
            $rel = trim($decodedPath, '/');
            // index.php
            $cand = $abs($rel . '/index.php');
            if ($cand && is_file($cand)) { include $cand; exit; }
            // index.html
            $cand = $abs($rel . '/index.html');
            if ($cand && is_file($cand)) { header('Content-Type: text/html; charset=utf-8'); readfile($cand); exit; }
        }
    }
    // si no se encontró index.*, denegar o 404 (elige la política)
    http_response_code(404);
    echo "Not found";
    exit;
}

// --- Rutas PHP puntuales en raíz (si querés excepciones explícitas) ---
if ($decodedPath === '/search.php') {
    $php = $abs('search.php');
    if ($php) { include $php; exit; }
}

// --- Todo lo demás: prohibido ---
http_response_code(403);
echo "403 Forbidden";
exit;
