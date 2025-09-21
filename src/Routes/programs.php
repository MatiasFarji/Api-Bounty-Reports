<?php
require_once __DIR__ . '/../Controllers/ProgramController.php';

$router->add('GET', '/api/v1/programs', function() {
    ProgramController::index();
});

$router->add('GET', '/api/v1/programs/{id}', function($params) {
    ProgramController::show($params['id']);
});
