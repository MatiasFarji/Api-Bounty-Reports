<?php
require_once __DIR__ . '/../Models/Program.php';
require_once __DIR__ . '/../Utils/Response.php';

class ProgramController {
    public static function index() {
        try {
            $programs = Program::getAll();
            Response::json($programs);
        } catch (Exception $e) {
            Response::error("Failed to fetch programs: " . $e->getMessage(), 500);
        }
    }

    public static function show($id) {
        try {
            $program = Program::findById($id);
            if ($program) {
                Response::json($program);
            } else {
                Response::error("Program not found", 404);
            }
        } catch (Exception $e) {
            Response::error("Failed to fetch program: " . $e->getMessage(), 500);
        }
    }
}
