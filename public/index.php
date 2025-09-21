<?php
/**
 * index.php
 * Entry point for the API
 */

// Autoload manually since we don’t have Composer
require_once __DIR__ . '/../src/Utils/Router.php';

// Initialize router
$router = new Router();

// Load route files
require_once __DIR__ . '/../src/Routes/reports.php';
require_once __DIR__ . '/../src/Routes/sources.php';
require_once __DIR__ . '/../src/Routes/categories.php';
require_once __DIR__ . '/../src/Routes/programs.php';

// Parse request
$method = $_SERVER['REQUEST_METHOD'];
$uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);

// Dispatch
$router->dispatch($method, $uri);
