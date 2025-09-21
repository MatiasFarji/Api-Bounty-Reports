<?php
require_once __DIR__ . '/../Controllers/CategoryController.php';

$router->add('GET', '/api/v1/categories', function() {
    CategoryController::index();
});

$router->add('GET', '/api/v1/categories/{id}', function($params) {
    CategoryController::show($params['id']);
});
