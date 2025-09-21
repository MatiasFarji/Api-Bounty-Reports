<?php
require_once __DIR__ . '/../Controllers/SourceController.php';

$router->add('GET', '/api/v1/sources', function() {
    SourceController::index();
});

$router->add('GET', '/api/v1/sources/{id}', function($params) {
    SourceController::show($params['id']);
});
