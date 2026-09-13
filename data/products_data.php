<?php
/**
 * Product Data Store and Query Helpers
 * (Easily swappable with MySQL / PDO in the future)
 */

function get_raw_products(): array {
    static $products = null;
    if ($products !== null) {
        return $products;
    }

    $productTypes = [
        ['type' => 'shirt', 'image' => 'assets/shirt.png', 'name' => 'Classic Tee'],
        ['type' => 'pants', 'image' => 'assets/pants.png', 'name' => 'Classic Pants'],
        ['type' => 'skirt', 'image' => 'assets/skirt.png', 'name' => 'Classic Skirt'],
        ['type' => 'cap',   'image' => 'assets/cap.png',   'name' => 'Classic Cap'],
    ];

    $sizes = ['S', 'M', 'L'];
    $prices = [20, 25, 30, 35];
    $colors = ['ดำ', 'ขาว', 'เทา', 'น้ำเงิน', 'แดง'];
    $statuses = ['พร้อมส่ง', 'ขายแล้ว'];
    $galleries = ['assets/shirt.png', 'assets/pants.png', 'assets/skirt.png', 'assets/cap.png'];

    $products = [];
    for ($i = 1; $i <= 100; $i++) {
        $ptIndex = $i % count($productTypes);
        $pt = $productTypes[$ptIndex];

        // Deterministic pseudo-random generation based on item ID for stability
        $size    = $sizes[($i * 7) % count($sizes)];
        $price   = $prices[($i * 11) % count($prices)];
        $color   = $colors[($i * 13) % count($colors)];
        $status  = ($i % 5 === 0) ? 'ขายแล้ว' : 'พร้อมส่ง'; // Every 5th product sold out
        $extraG  = $galleries[($i + 1) % count($galleries)];

        $products[$i] = [
            'id'      => $i,
            'type'    => $pt['type'],
            'name'    => "{$pt['name']} #{$i}",
            'image'   => $pt['image'],
            'gallery' => [
                $pt['image'],
                $extraG,
                $pt['image'],
                $extraG
            ],
            'size'    => $size,
            'price'   => $price,
            'color'   => $color,
            'status'  => $status,
        ];
    }

    return $products;
}

require_once __DIR__ . '/db.php';

function get_all_products(): array {
    $pdo = get_db_connection();
    if ($pdo && is_db_initialized($pdo)) {
        try {
            $stmt = $pdo->query("SELECT * FROM products ORDER BY id ASC");
            return $stmt->fetchAll();
        } catch (Exception $e) {}
    }
    return array_values(get_raw_products());
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
            return null;
        } catch (Exception $e) {}
    }
    $products = get_raw_products();
    return $products[$id] ?? null;
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
 * Filter products by criteria (PDO query with array fallback)
 */
function filter_products(array $filters): array {
    $types    = !empty($filters['product']) ? (array)$filters['product'] : [];
    $sizes    = !empty($filters['size']) ? (array)$filters['size'] : [];
    $prices   = !empty($filters['price']) ? array_map('intval', (array)$filters['price']) : [];
    $statuses = !empty($filters['status']) ? (array)$filters['status'] : [];

    $pdo = get_db_connection();
    if ($pdo && is_db_initialized($pdo)) {
        try {
            $where = [];
            $params = [];

            if (!empty($types)) {
                $in = implode(',', array_fill(0, count($types), '?'));
                $where[] = "type IN ($in)";
                $params = array_merge($params, $types);
            }
            if (!empty($sizes)) {
                $in = implode(',', array_fill(0, count($sizes), '?'));
                $where[] = "size IN ($in)";
                $params = array_merge($params, $sizes);
            }
            if (!empty($prices)) {
                $in = implode(',', array_fill(0, count($prices), '?'));
                $where[] = "CAST(price AS SIGNED) IN ($in)";
                $params = array_merge($params, $prices);
            }
            if (!empty($statuses)) {
                $in = implode(',', array_fill(0, count($statuses), '?'));
                $where[] = "status IN ($in)";
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
        } catch (Exception $e) {}
    }

    // Fallback to in-memory filter
    $all = get_all_products();
    return array_values(array_filter($all, function ($p) use ($types, $sizes, $prices, $statuses) {
        if (!empty($types) && !in_array($p['type'], $types, true)) {
            return false;
        }
        if (!empty($sizes) && !in_array($p['size'], $sizes, true)) {
            return false;
        }
        if (!empty($prices) && !in_array((int)$p['price'], $prices, true)) {
            return false;
        }
        if (!empty($statuses) && !in_array($p['status'], $statuses, true)) {
            return false;
        }
        return true;
    }));
}
