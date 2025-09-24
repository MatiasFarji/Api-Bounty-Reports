<?php
require_once __DIR__ . '/../Controllers/ReportController.php';

$router->add('GET', '/api/v1/reports', function($params, $queryParams) {
    ReportController::index($queryParams);
});

$router->add('GET', '/api/v1/reports/{id}', function($params, $queryParams) {
    ReportController::show($params['id']);
});
