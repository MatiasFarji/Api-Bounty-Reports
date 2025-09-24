<?php
require_once __DIR__ . '/../Utils/Database.php';

class Report {
    /**
     * Get all reports with optional filters.
     *
     * Supported filters:
     *   - source_id
     *   - category_id
     *   - program_id
     *   - severity (exact match)
     *   - severity_min (>=)
     *   - severity_max (<=)
     *   - date_from (>= published_at)
     *   - date_to   (<= published_at)
     *   - limit     (max rows, default 200)
     *   - sort_by   (column name, validated whitelist)
     *   - order     (ASC or DESC)
     *
     * @param array $filters
     * @return array
     */
    public static function getAll($filters = []) {
        $db = Database::getInstance()->getConnection();
        $sql = "SELECT * FROM reports WHERE 1=1";
        $params = [];

        // Filters
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
        if (!empty($filters['severity_min'])) {
            $sql .= " AND severity >= :severity_min";
            $params[':severity_min'] = $filters['severity_min'];
        }
        if (!empty($filters['severity_max'])) {
            $sql .= " AND severity <= :severity_max";
            $params[':severity_max'] = $filters['severity_max'];
        }
        if (!empty($filters['date_from'])) {
            $sql .= " AND published_at >= :date_from";
            $params[':date_from'] = $filters['date_from'];
        }
        if (!empty($filters['date_to'])) {
            $sql .= " AND published_at <= :date_to";
            $params[':date_to'] = $filters['date_to'];
        }

        // Sorting (whitelist)
        $allowedSortColumns = ['published_at', 'severity', 'title', 'scraped_at'];
        $sortBy = in_array($filters['sort_by'] ?? '', $allowedSortColumns, true)
            ? $filters['sort_by']
            : 'published_at';

        $order = strtoupper($filters['order'] ?? 'DESC');
        if (!in_array($order, ['ASC', 'DESC'], true)) {
            $order = 'DESC';
        }

        $sql .= " ORDER BY {$sortBy} {$order}";

        // Limit (safeguarded)
        $limit = (!empty($filters['limit']) && is_numeric($filters['limit']))
            ? min((int)$filters['limit'], 1000) // cap at 1000 rows max
            : 200;

        $sql .= " LIMIT {$limit}";

        $stmt = $db->prepare($sql);
        $stmt->execute($params);

        return $stmt->fetchAll();
    }

    /**
     * Find a report by UUID.
     *
     * @param string $id
     * @return array|false
     */
    public static function findById($id) {
        $db = Database::getInstance()->getConnection();
        $stmt = $db->prepare("SELECT * FROM reports WHERE id = :id LIMIT 1");
        $stmt->execute([':id' => $id]);
        return $stmt->fetch();
    }
}
