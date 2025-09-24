<?php
require_once __DIR__ . '/../Utils/Database.php';
require_once __DIR__ . '/Subcategory.php';

class Report {
    /**
     * Get all reports with optional filters.
     *
     * Supported filters:
     *   - source_id (comma-separated numbers)
     *   - subcategory_id (comma-separated numbers)
     *   - program_id (comma-separated numbers)
     *   - severity (comma-separated P1..P5)
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

        // Multi-ID filters
        foreach (['source_id', 'subcategory_id', 'program_id'] as $field) {
            if (!empty($filters[$field])) {
                $ids = array_filter(array_map('intval', explode(',', $filters[$field])));
                if (!empty($ids)) {
                    $sql .= " AND $field IN (" . implode(',', $ids) . ")";
                }
            }
        }

        // Filter by severities (via subcategories)
        if (!empty($filters['severity'])) {
            $severityList = explode(',', strtoupper($filters['severity']));
            $subIds = Subcategory::findIdsBySeverities($severityList);
            if (!empty($subIds)) {
                $sql .= " AND subcategory_id IN (" . implode(',', array_map('intval', $subIds)) . ")";
            } else {
                return []; // no matches
            }
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
        $allowedSortColumns = ['published_at', 'title', 'scraped_at'];
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
            ? (int)$filters['limit']
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
