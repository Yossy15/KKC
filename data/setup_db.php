<?php
/**
 * Database Setup & Seeder Script
 * Run from terminal: php data/setup_db.php
 */
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/products_data.php';
require_once __DIR__ . '/user_data.php';
require_once __DIR__ . '/order_data.php';

$pdo = get_db_connection();
if (!$pdo) {
    die("Database connection failed. Please check configuration.\n");
}

$driver = $pdo->getAttribute(PDO::ATTR_DRIVER_NAME);
echo "Setting up KKC database using [{$driver}] driver...\n";

// 1. Create Tables
if ($driver === 'sqlite') {
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS users (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            username TEXT NOT NULL UNIQUE,
            password TEXT NOT NULL,
            name TEXT NOT NULL,
            email TEXT NOT NULL UNIQUE,
            phone TEXT DEFAULT NULL,
            role TEXT DEFAULT 'user',
            status TEXT DEFAULT 'active',
            img TEXT DEFAULT 'assets/profile-mock.png',
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP
        );

        CREATE TABLE IF NOT EXISTS products (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            name TEXT NOT NULL,
            type TEXT NOT NULL,
            size TEXT NOT NULL,
            price REAL NOT NULL,
            color TEXT NOT NULL,
            status TEXT DEFAULT 'พร้อมส่ง',
            image TEXT NOT NULL,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP
        );

        CREATE TABLE IF NOT EXISTS product_gallery (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            product_id INTEGER NOT NULL,
            image_url TEXT NOT NULL,
            sort_order INTEGER DEFAULT 0,
            FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE
        );

        CREATE TABLE IF NOT EXISTS orders (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            user_id INTEGER NOT NULL,
            product_id INTEGER NOT NULL,
            quantity INTEGER NOT NULL DEFAULT 1,
            price REAL NOT NULL,
            total REAL NOT NULL,
            status TEXT DEFAULT 'รอดำเนินการ',
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
            FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE
        );
    ");
} else {
    // MySQL schema from schema.sql
    $sql = file_get_contents(__DIR__ . '/schema.sql');
    $pdo->exec($sql);
}

echo "Tables created successfully.\n";

// 2. Seed Products
$countProducts = (int)$pdo->query("SELECT COUNT(*) FROM products")->fetchColumn();
if ($countProducts === 0) {
    echo "Seeding 100 products...\n";
    $rawProducts = get_raw_products();
    $stmtProd = $pdo->prepare("
        INSERT INTO products (id, name, type, size, price, color, status, image)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?)
    ");
    $stmtGal = $pdo->prepare("
        INSERT INTO product_gallery (product_id, image_url, sort_order)
        VALUES (?, ?, ?)
    ");

    $pdo->beginTransaction();
    foreach ($rawProducts as $p) {
        $stmtProd->execute([
            $p['id'],
            $p['name'],
            $p['type'],
            $p['size'],
            $p['price'],
            $p['color'],
            $p['status'],
            $p['image'],
        ]);

        if (!empty($p['gallery'])) {
            foreach ($p['gallery'] as $idx => $gUrl) {
                $stmtGal->execute([$p['id'], $gUrl, $idx]);
            }
        }
    }
    $pdo->commit();
    echo "Products seeded.\n";
} else {
    echo "Products table already has {$countProducts} records. Skipping seed.\n";
}

// 3. Seed Users
$countUsers = (int)$pdo->query("SELECT COUNT(*) FROM users")->fetchColumn();
if ($countUsers === 0) {
    echo "Seeding users...\n";
    $rawUsers = get_mock_users();
    $stmtUser = $pdo->prepare("
        INSERT INTO users (id, username, password, name, email, phone, role, status, img)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
    ");

    $defaultHash = password_hash('1234', PASSWORD_DEFAULT);

    $pdo->beginTransaction();
    foreach ($rawUsers as $u) {
        $stmtUser->execute([
            $u['id'],
            $u['username'],
            $defaultHash,
            $u['name'],
            $u['email'],
            $u['phone'],
            $u['role'],
            $u['status'],
            $u['img'],
        ]);
    }
    $pdo->commit();
    echo "Users seeded.\n";
} else {
    echo "Users table already has {$countUsers} records. Skipping seed.\n";
}

// 4. Seed Orders
$countOrders = (int)$pdo->query("SELECT COUNT(*) FROM orders")->fetchColumn();
if ($countOrders === 0) {
    echo "Seeding orders...\n";
    $rawOrders = get_all_orders();
    $stmtOrder = $pdo->prepare("
        INSERT INTO orders (id, user_id, product_id, quantity, price, total, status, created_at)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?)
    ");

    $pdo->beginTransaction();
    foreach ($rawOrders as $o) {
        $stmtOrder->execute([
            $o['id'],
            $o['userId'],
            $o['productId'],
            $o['quantity'],
            $o['price'],
            $o['total'],
            $o['status'],
            $o['createdAt'],
        ]);
    }
    $pdo->commit();
    echo "Orders seeded.\n";
} else {
    echo "Orders table already has {$countOrders} records. Skipping seed.\n";
}

echo "Database setup completed successfully! All data is persistent and ready.\n";
