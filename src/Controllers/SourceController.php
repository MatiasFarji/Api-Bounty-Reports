<?php
require_once __DIR__ . '/../Models/Source.php';
require_once __DIR__ . '/../Utils/Response.php';

class SourceController {
    public static function index() {
        try {
            $sources = Source::getAll();
            Response::json($sources);
        } catch (Exception $e) {
            Response::error("Failed to fetch sources: " . $e->getMessage(), 500);
        }
    }

    public static function show($id) {
        try {
            $source = Source::findById($id);
            if ($source) {
                Response::json($source);
            } else {
                Response::error("Source not found", 404);
            }
        } catch (Exception $e) {
            Response::error("Failed to fetch source: " . $e->getMessage(), 500);
        }
    }
}
