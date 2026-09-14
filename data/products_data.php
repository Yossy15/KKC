<?php
/**
 * Product Data Store and Query Helpers
 * Direct SQLite PDO
 */
require_once __DIR__ . '/db.php';

function get_all_products(): array {
    $pdo = get_db_connection();
    if ($pdo && is_db_initialized($pdo)) {
        try {
            return $pdo->query("SELECT * FROM products ORDER BY id ASC")->fetchAll();
        } catch (Exception $e) {}
    }
    return [];
}

function get_product_by_id(int $id): ?array {
    $pdo = get_db_connection();
    if ($pdo && is_db_initialized($pdo)) {
        try {
            $stmt = $pdo->prepare("SELECT * FROM products WHERE id = ?");
            $stmt->execute([$id]);
            $p = $stmt->fetch();
            if ($p) {
                $gStmt = $pdo->prepare("SELECT image_url FROM product_gallery WHERE product_id = ? ORDER BY sort_order ASC");
                $gStmt->execute([$id]);
                $p['gallery'] = $gStmt->fetchAll(PDO::FETCH_COLUMN) ?: [$p['image']];
                return $p;
            }
        } catch (Exception $e) {}
    }
    return null;
}

function get_category_label(string $type): string {
    $labels = [
        'shirt' => 'เสื้อ',
        'pants' => 'กางเกง',
        'skirt' => 'กระโปรง',
        'cap'   => 'หมวก',
    ];
    return $labels[$type] ?? $type;
}

/**
 * Filter products by criteria
 */
function filter_products(array $filters): array {
    $types    = !empty($filters['product']) ? (array)$filters['product'] : [];
    $sizes    = !empty($filters['size']) ? (array)$filters['size'] : [];
    $prices   = !empty($filters['price']) ? array_map('intval', (array)$filters['price']) : [];
    $statuses = !empty($filters['status']) ? (array)$filters['status'] : [];

    $pdo = get_db_connection();
    if (!$pdo || !is_db_initialized($pdo)) {
        return [];
    }

    try {
        $where = [];
        $params = [];

        if (!empty($types)) {
            $where[] = "type IN (" . implode(',', array_fill(0, count($types), '?')) . ")";
            $params = array_merge($params, $types);
        }
        if (!empty($sizes)) {
            $where[] = "size IN (" . implode(',', array_fill(0, count($sizes), '?')) . ")";
            $params = array_merge($params, $sizes);
        }
        if (!empty($prices)) {
            $where[] = "CAST(price AS INTEGER) IN (" . implode(',', array_fill(0, count($prices), '?')) . ")";
            $params = array_merge($params, $prices);
        }
        if (!empty($statuses)) {
            $where[] = "status IN (" . implode(',', array_fill(0, count($statuses), '?')) . ")";
            $params = array_merge($params, $statuses);
        }

        $sql = "SELECT * FROM products";
        if (!empty($where)) {
            $sql .= " WHERE " . implode(" AND ", $where);
        }
        $sql .= " ORDER BY id ASC";
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    } catch (Exception $e) {
        return [];
    }
}
