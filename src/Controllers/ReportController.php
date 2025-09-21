<?php
require_once __DIR__ . '/../Models/Report.php';
require_once __DIR__ . '/../Utils/Response.php';

class ReportController {
    public static function index($queryParams) {
        try {
            $reports = Report::getAll($queryParams);
            Response::json($reports);
        } catch (Exception $e) {
            Response::error("Failed to fetch reports: " . $e->getMessage(), 500);
        }
    }

    public static function show($id) {
        try {
            $report = Report::findById($id);
            if ($report) {
                Response::json($report);
            } else {
                Response::error("Report not found", 404);
            }
        } catch (Exception $e) {
            Response::error("Failed to fetch report: " . $e->getMessage(), 500);
        }
    }
}
