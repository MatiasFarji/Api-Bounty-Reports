<?php
require_once __DIR__ . '/../Utils/Database.php';

class Category
{
    public static function getAll()
    {
        $db = Database::getInstance()->getConnection();
        $stmt = $db->query("SELECT * FROM categories ORDER BY id ASC");
        return $stmt->fetchAll();
    }

    public static function findById($id)
    {
        $db = Database::getInstance()->getConnection();
        $stmt = $db->prepare("SELECT * FROM categories WHERE id = :id LIMIT 1");
        $stmt->execute([':id' => $id]);
        return $stmt->fetch();
    }

    public static function findByName($name)
    {
        $db = Database::getInstance()->getConnection();
        $stmt = $db->prepare("SELECT * FROM categories WHERE name = :name LIMIT 1");
        $stmt->execute([':name' => $name]);
        return $stmt->fetch();
    }

    public static function create($name)
    {
        $db = Database::getInstance()->getConnection();
        $stmt = $db->prepare("
            INSERT INTO categories (name)
            VALUES (:name)
            ON CONFLICT (name) DO UPDATE SET name = EXCLUDED.name
            RETURNING id
        ");
        $stmt->execute([':name' => $name]);
        return $stmt->fetchColumn();
    }
}
