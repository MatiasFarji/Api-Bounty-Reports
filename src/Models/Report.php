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
     *   - severity (Allowed string divided by comma P1,P2,P3,P4,P5)
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

         // Filter by severities (via subcategories)
        if (!empty($filters['severity'])) {
            $severityStr = strtoupper($filters['severity']);
            if (!preg_match('/^(P[1-5])(,P[1-5])*$/', $severityStr)) {
                throw new InvalidArgumentException("Invalid severity filter format. Allowed: P1..P5, comma separated.");
            }

            $severityList = explode(',', $severityStr);

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
