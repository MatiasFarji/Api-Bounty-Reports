<?php
require_once __DIR__ . '/../Controllers/SubcategoryController.php';

$router->add('GET', '/api/v1/subcategories', function() {
    SubcategoryController::index();
});
