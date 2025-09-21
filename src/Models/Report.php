<?php
require_once __DIR__ . '/../Utils/Database.php';

class Report {
    public static function getAll($filters = []) {
        $db = Database::getInstance()->getConnection();
        $sql = "SELECT * FROM reports WHERE 1=1";
        $params = [];

        if (!empty($filters['source_id'])) {
            $sql .= " AND source_id = :source_id";
            $params[':source_id'] = $filters['source_id'];
        }
        if (!empty($filters['category_id'])) {
            $sql .= " AND category_id = :category_id";
            $params[':category_id'] = $filters['category_id'];
        }
        if (!empty($filters['program_id'])) {
            $sql .= " AND program_id = :program_id";
            $params[':program_id'] = $filters['program_id'];
        }
        if (!empty($filters['severity'])) {
            $sql .= " AND severity = :severity";
            $params[':severity'] = $filters['severity'];
        }
        if (!empty($filters['date_from'])) {
            $sql .= " AND published_at >= :date_from";
            $params[':date_from'] = $filters['date_from'];
        }
        if (!empty($filters['date_to'])) {
            $sql .= " AND published_at <= :date_to";
            $params[':date_to'] = $filters['date_to'];
        }

        $sql .= " ORDER BY published_at DESC LIMIT 100";

        $stmt = $db->prepare($sql);
        $stmt->execute($params);

        return $stmt->fetchAll();
    }

    public static function findById($id) {
        $db = Database::getInstance()->getConnection();
        $stmt = $db->prepare("SELECT * FROM reports WHERE id = :id LIMIT 1");
        $stmt->execute([':id' => $id]);
        return $stmt->fetch();
    }
}
