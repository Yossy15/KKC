<?php
/**
 * Order Data Store & Query Helpers
 * Direct SQLite PDO
 */
require_once __DIR__ . '/db.php';

function get_orders_by_user(int $userId): array {
    $pdo = get_db_connection();
    if ($pdo && is_db_initialized($pdo)) {
        try {
            $stmt = $pdo->prepare("
                SELECT o.*
                FROM orders o
                WHERE o.user_id = ?
                ORDER BY o.id DESC
            ");
            $stmt->execute([$userId]);
            $rows = $stmt->fetchAll();
            return array_map(function($r) {
                $items = !empty($r['items']) ? json_decode($r['items'], true) : [];
                if (!is_array($items) || empty($items)) {
                    $items = [[
                        'id'       => (int)($r['product_id'] ?? 1),
                        'name'     => 'สินค้า #' . ($r['product_id'] ?? 1),
                        'image'    => 'assets/shirt.png',
                        'price'    => (float)($r['price'] ?? $r['total'] ?? 0),
                        'quantity' => (int)($r['quantity'] ?? 1),
                    ]];
                }
                $firstItem = $items[0] ?? [
                    'id'       => 0,
                    'name'     => 'สินค้า',
                    'image'    => 'assets/shirt.png',
                    'price'    => (float)($r['total'] ?? 0),
                    'quantity' => 1,
                ];
                return [
                    'id'        => (int)$r['id'],
                    'userId'    => (int)$r['user_id'],
                    'items'     => $items,
                    'productId' => (int)($firstItem['id'] ?? $r['product_id'] ?? 0),
                    'product'   => [
                        'name'  => $firstItem['name'] ?? 'สินค้า',
                        'image' => $firstItem['image'] ?? 'assets/shirt.png',
                        'price' => (float)($firstItem['price'] ?? 0),
                    ],
                    'quantity'  => (int)array_sum(array_column($items, 'quantity')),
                    'price'     => (float)($firstItem['price'] ?? $r['total']),
                    'total'     => (float)$r['total'],
                    'status'    => $r['status'] ?? 'รอดำเนินการ',
                    'createdAt' => $r['created_at'],
                ];
            }, $rows);
        } catch (Exception $e) {}
    }
    return [];
}

function get_order_by_id(int $orderId): ?array {
    $pdo = get_db_connection();
    if ($pdo && is_db_initialized($pdo)) {
        try {
            $stmt = $pdo->prepare("
                SELECT o.*, u.name as user_name, u.username, u.email as user_email, u.phone as user_phone, u.address as user_address
                FROM orders o
                LEFT JOIN users u ON o.user_id = u.id
                WHERE o.id = ?
                LIMIT 1
            ");
            $stmt->execute([$orderId]);
            $r = $stmt->fetch();
            if (!$r) return null;

            $items = !empty($r['items']) ? json_decode($r['items'], true) : [];
            if (!is_array($items) || empty($items)) {
                $items = [[
                    'id'       => (int)($r['product_id'] ?? 1),
                    'name'     => 'สินค้า #' . ($r['product_id'] ?? 1),
                    'image'    => 'assets/shirt.png',
                    'price'    => (float)($r['price'] ?? $r['total'] ?? 0),
                    'quantity' => (int)($r['quantity'] ?? 1),
                ]];
            }
            return [
                'id'          => (int)$r['id'],
                'userId'      => (int)$r['user_id'],
                'userName'    => $r['user_name'] ?? '',
                'userEmail'   => $r['user_email'] ?? '',
                'userPhone'   => $r['user_phone'] ?? '',
                'address'     => !empty($r['address']) ? $r['address'] : ($r['user_address'] ?? ''),
                'items'       => $items,
                'total'       => (float)$r['total'],
                'status'      => $r['status'] ?? 'รอดำเนินการ',
                'createdAt'   => $r['created_at'],
            ];
        } catch (Exception $e) {}
    }
    return null;
}
