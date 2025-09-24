<?php
require_once __DIR__ . '/../Utils/Database.php';

class Subcategory {
    public static function getAll() {
        $db = Database::getInstance()->getConnection();
        $stmt = $db->query("SELECT * FROM subcategories ORDER BY id ASC");
        return $stmt->fetchAll();
    }

    public static function findByNameAndCategory($name, $categoryId) {
        $db = Database::getInstance()->getConnection();
        $stmt = $db->prepare("SELECT * FROM subcategories WHERE name = :name AND category_id = :category_id LIMIT 1");
        $stmt->execute([':name' => $name, ':category_id' => $categoryId]);
        return $stmt->fetch();
    }

    public static function create($categoryId, $name, $cwe = null) {
    $db = Database::getInstance()->getConnection();
        $stmt = $db->prepare("
            INSERT INTO subcategories (category_id, name, created_at)
            VALUES (:category_id, :name, now())
            ON CONFLICT (category_id, name) DO UPDATE SET name = EXCLUDED.name
            RETURNING id
        ");
        $stmt->execute([
            ':category_id' => $categoryId,
            ':name' => $name
        ]);
        return $stmt->fetchColumn();
    }
}
