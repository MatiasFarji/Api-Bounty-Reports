<?php
require_once __DIR__ . '/../Utils/Database.php';

class Subcategory
{
    public static function getAll()
    {
        $db = Database::getInstance()->getConnection();
        $stmt = $db->query("SELECT * FROM subcategories ORDER BY id ASC");
        return $stmt->fetchAll();
    }

    public static function findByNameAndCategory($name, $categoryId)
    {
        $db = Database::getInstance()->getConnection();
        $stmt = $db->prepare("SELECT * FROM subcategories WHERE name = :name AND category_id = :category_id LIMIT 1");
        $stmt->execute([':name' => $name, ':category_id' => $categoryId]);
        return $stmt->fetch();
    }

    /**
     * Find subcategory by name only (ignores category_id).
     * Useful for classification when category is unknown.
     *
     * @param string $name Subcategory name
     * @return array|false Returns row as associative array or false if not found
     */
    public static function findByName($name)
    {
        $db = Database::getInstance()->getConnection();
        $stmt = $db->prepare("SELECT * FROM subcategories WHERE name = :name LIMIT 1");
        $stmt->execute([':name' => $name]);
        return $stmt->fetch();
    }

     /**
     * Find subcategory IDs by severities (e.g. ['P1','P2']).
     *
     * @param array $severities
     * @return array List of subcategory IDs
     */
    public static function findIdsBySeverities(array $severities) {
        if (empty($severities)) {
            return [];
        }

        $db = Database::getInstance()->getConnection();
        $placeholders = [];
        $params = [];
        foreach ($severities as $i => $sev) {
            $key = ":sev{$i}";
            $placeholders[] = $key;
            $params[$key] = $sev;
        }

        $sql = "SELECT id FROM subcategories WHERE severity IN (" . implode(',', $placeholders) . ")";
        $stmt = $db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(\PDO::FETCH_COLUMN);
    }


    public static function create($categoryId, $name, $cwe = null)
    {
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

    public static function updateSeverity($id, $severity) {
        $db = Database::getInstance()->getConnection();
        $stmt = $db->prepare("UPDATE subcategories SET severity = :severity WHERE id = :id");
        $stmt->execute([
            ':severity' => $severity,
            ':id' => $id
        ]);
    }
}
