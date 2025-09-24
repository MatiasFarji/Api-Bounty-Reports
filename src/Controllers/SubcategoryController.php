<?php
require_once __DIR__ . '/../Models/Subcategory.php';

class SubcategoryController {
    public static function index() {
        $subcategories = Subcategory::getAll();
        header('Content-Type: application/json');
        echo json_encode($subcategories);
    }
}
