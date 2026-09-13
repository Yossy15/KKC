<?php
/**
 * Order Data Store & Query Helpers
 */

require_once __DIR__ . '/products_data.php';

function get_all_orders(): array {
    static $orders = null;
    if ($orders !== null) {
        return $orders;
    }

    $orderStatuses = [
        "รอดำเนินการ",
        "กำลังจัดส่ง",
        "จัดส่งแล้ว",
        "ยกเลิก",
    ];

    $orders = [];
    for ($i = 1; $i <= 100; $i++) {
        $userId = ($i % 30) + 1;
        $productId = ($i % 100) + 1;
        $product = get_product_by_id($productId) ?? [
            'name' => "สินค้า #{$productId}",
            'price' => 30,
            'image' => 'assets/shirt.png'
        ];
        $quantity = ($i % 3) + 1;
        $status = $orderStatuses[$i % count($orderStatuses)];
        $daysAgo = ($i * 3) % 30;
        $timestamp = time() - ($daysAgo * 86400) - ($i * 3600);

        $orders[] = [
            'id'         => $i,
            'userId'     => $userId,
            'productId'  => $productId,
            'product'    => $product,
            'quantity'   => $quantity,
            'price'      => $product['price'],
            'total'      => $product['price'] * $quantity,
            'status'     => $status,
            'createdAt'  => date('Y-m-d H:i:s', $timestamp),
        ];
    }

    return $orders;
}

require_once __DIR__ . '/db.php';

function get_orders_by_user(int $userId): array {
    $pdo = get_db_connection();
    if ($pdo && is_db_initialized($pdo)) {
        try {
            $stmt = $pdo->prepare("
                SELECT o.*, p.name as product_name, p.image as product_image, p.price as product_price
                FROM orders o
                JOIN products p ON o.product_id = p.id
                WHERE o.user_id = ?
                ORDER BY o.id DESC
            ");
            $stmt->execute([$userId]);
            $rows = $stmt->fetchAll();
            if (!empty($rows)) {
                return array_map(function($r) {
                    return [
                        'id'        => (int)$r['id'],
                        'userId'    => (int)$r['user_id'],
                        'productId' => (int)$r['product_id'],
                        'product'   => [
                            'name'  => $r['product_name'],
                            'image' => $r['product_image'],
                            'price' => (float)$r['product_price'],
                        ],
                        'quantity'  => (int)$r['quantity'],
                        'price'     => (float)$r['price'],
                        'total'     => (float)$r['total'],
                        'status'    => $r['status'],
                        'createdAt' => $r['created_at'],
                    ];
                }, $rows);
            }
        } catch (Exception $e) {}
    }

    $orders = get_all_orders();
    $userOrders = array_values(array_filter($orders, fn($o) => $o['userId'] === $userId));

    // If user has no orders (e.g. newly registered), provide a few sample orders
    if (empty($userOrders)) {
        $userOrders = array_slice($orders, 0, 4);
    }

    return $userOrders;
}
