<?php
require_once __DIR__ . '/../Models/Category.php';
require_once __DIR__ . '/../Utils/Response.php';

class CategoryController {
    public static function index() {
        try {
            $categories = Category::getAll();
            Response::json($categories);
        } catch (Exception $e) {
            Response::error("Failed to fetch categories: " . $e->getMessage(), 500);
        }
    }

    public static function show($id) {
        try {
            $category = Category::findById($id);
            if ($category) {
                Response::json($category);
            } else {
                Response::error("Category not found", 404);
            }
        } catch (Exception $e) {
            Response::error("Failed to fetch category: " . $e->getMessage(), 500);
        }
    }
}
