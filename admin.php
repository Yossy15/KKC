<?php
/**
 * Admin Management Dashboard
 * Fullscreen layout with Desktop Sidebar ("Slice ข้าง") & Mobile Bottom Navigation Bar (No Emojis)
 */
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/data/db.php';
require_once __DIR__ . '/data/products_data.php';
require_once __DIR__ . '/data/order_data.php';

// Authorization Check: Admin only
if (!is_logged_in() || current_user()['role'] !== 'admin') {
    http_response_code(403);
    $page_title = 'KKC - 403 Forbidden';
    include __DIR__ . '/includes/header.php';
    echo '<main style="text-align: center; padding: 100px 20px; background: #fff; min-height: 80vh;"><h1 class="title">403 เข้าถึงไม่ได้</h1><p style="font-family: var(--font-krub); margin: 16px 0;">หน้านี้สงวนสิทธิ์สำหรับผู้ดูแลระบบ (Admin) เท่านั้น</p><a href="' . base_url('index.php') . '" class="auth-btn" style="max-width: 200px; display: inline-block; text-decoration: none;">กลับหน้าแรก</a></main>';
    include __DIR__ . '/includes/footer.php';
    exit;
}

$pdo = get_db_connection();
$notice = '';
$error = '';
$tab = $_GET['tab'] ?? 'orders';

// Handle Admin POST Actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
        $error = 'โทเค็นความปลอดภัยไม่ถูกต้อง';
    } else {
        $adminAction = $_POST['admin_action'] ?? '';

        // 1. Update Order Status
        if ($adminAction === 'update_order_status' && $pdo) {
            $orderId = (int)($_POST['order_id'] ?? 0);
            $newStatus = trim($_POST['status'] ?? '');
            $allowed = ["รอดำเนินการ", "กำลังจัดส่ง", "จัดส่งแล้ว", "ยกเลิก"];
            if ($orderId > 0 && in_array($newStatus, $allowed, true)) {
                $stmt = $pdo->prepare("UPDATE orders SET status = ? WHERE id = ?");
                $stmt->execute([$newStatus, $orderId]);
                $notice = "อัปเดตสถานะออร์เดอร์ #{$orderId} เป็น '{$newStatus}' เรียบร้อยแล้ว";
            }
        }

        // 2. Toggle Product Status
        elseif ($adminAction === 'toggle_product_status' && $pdo) {
            $productId = (int)($_POST['product_id'] ?? 0);
            $currentStatus = $_POST['current_status'] ?? 'พร้อมส่ง';
            $newStatus = ($currentStatus === 'พร้อมส่ง') ? 'ขายแล้ว' : 'พร้อมส่ง';
            $stmt = $pdo->prepare("UPDATE products SET status = ? WHERE id = ?");
            $stmt->execute([$newStatus, $productId]);
            $notice = "เปลี่ยนสถานะสินค้า ID {$productId} เป็น '{$newStatus}' เรียบร้อยแล้ว";
        }

        // 3. Add Product with Image & Multiple Gallery Images
        elseif ($adminAction === 'add_product' && $pdo) {
            $name  = trim($_POST['name'] ?? '');
            $type  = trim($_POST['type'] ?? 'shirt');
            $size  = trim($_POST['size'] ?? 'M');
            $price = (float)($_POST['price'] ?? 0);
            $color = trim($_POST['color'] ?? 'ดำ');

            // Handle Main Image (Upload or URL/preset)
            $typeImages = [
                'shirt' => 'assets/shirt.png',
                'pants' => 'assets/pants.png',
                'skirt' => 'assets/skirt.png',
                'cap'   => 'assets/cap.png',
            ];
            $image = trim($_POST['image_url'] ?? '') ?: ($typeImages[$type] ?? 'assets/shirt.png');

            if (!empty($_FILES['main_image_file']['name'])) {
                $fName = $_FILES['main_image_file']['name'];
                $ext = strtolower(pathinfo($fName, PATHINFO_EXTENSION));
                $allowed = ['jpg', 'jpeg', 'png', 'webp', 'svg'];
                if (in_array($ext, $allowed, true)) {
                    $targetName = 'prod_' . time() . '_' . bin2hex(random_bytes(4)) . '.' . $ext;
                    $targetPath = __DIR__ . '/assets/uploads/' . $targetName;
                    if (move_uploaded_file($_FILES['main_image_file']['tmp_name'], $targetPath)) {
                        $image = 'assets/uploads/' . $targetName;
                    }
                }
            }

            // Handle Gallery Images (Multiple Uploads & Textarea URLs)
            $galleryList = [];
            if (!empty($_FILES['gallery_files']['name'][0])) {
                foreach ($_FILES['gallery_files']['tmp_name'] as $idx => $tmp) {
                    if (!empty($tmp)) {
                        $fName = $_FILES['gallery_files']['name'][$idx];
                        $ext = strtolower(pathinfo($fName, PATHINFO_EXTENSION));
                        $allowed = ['jpg', 'jpeg', 'png', 'webp', 'svg'];
                        if (in_array($ext, $allowed, true)) {
                            $gName = 'gal_' . time() . "_{$idx}_" . bin2hex(random_bytes(4)) . '.' . $ext;
                            $gPath = __DIR__ . '/assets/uploads/' . $gName;
                            if (move_uploaded_file($tmp, $gPath)) {
                                $galleryList[] = 'assets/uploads/' . $gName;
                            }
                        }
                    }
                }
            }

            if (!empty($_POST['gallery_urls'])) {
                $lines = explode("\n", $_POST['gallery_urls']);
                foreach ($lines as $line) {
                    $trimmed = trim($line);
                    if (!empty($trimmed)) {
                        $galleryList[] = $trimmed;
                    }
                }
            }

            // If gallery list is empty, default to repeating main image
            if (empty($galleryList)) {
                $galleryList = [$image, $image];
            }

            if (!empty($name) && $price > 0) {
                $stmt = $pdo->prepare("
                    INSERT INTO products (name, type, size, price, color, status, image)
                    VALUES (?, ?, ?, ?, ?, 'พร้อมส่ง', ?)
                ");
                $stmt->execute([$name, $type, $size, $price, $color, $image]);
                $newId = $pdo->lastInsertId();

                $gStmt = $pdo->prepare("
                    INSERT INTO product_gallery (product_id, image_url, sort_order)
                    VALUES (?, ?, ?)
                ");
                foreach ($galleryList as $idx => $gUrl) {
                    $gStmt->execute([$newId, $gUrl, $idx]);
                }
                $notice = "เพิ่มสินค้าใหม่ '{$name}' (ID: {$newId}) พร้อมแกลเลอรี " . count($galleryList) . " รูปเรียบร้อยแล้ว";
            } else {
                $error = "กรุณากรอกชื่อสินค้าและราคาให้ถูกต้อง";
            }
        }

        // 4. Create Order Manually
        elseif ($adminAction === 'create_order' && $pdo) {
            $userId = (int)($_POST['user_id'] ?? 0);
            $productId = (int)($_POST['product_id'] ?? 0);
            $qty = max(1, (int)($_POST['quantity'] ?? 1));
            $status = trim($_POST['status'] ?? 'รอดำเนินการ');

            $prod = get_product_by_id($productId);
            if ($prod && $userId > 0) {
                $total = $prod['price'] * $qty;
                $stmt = $pdo->prepare("
                    INSERT INTO orders (user_id, product_id, quantity, price, total, status, created_at)
                    VALUES (?, ?, ?, ?, ?, ?, ?)
                ");
                $stmt->execute([$userId, $productId, $qty, $prod['price'], $total, $status, date('Y-m-d H:i:s')]);
                $newOrderId = $pdo->lastInsertId();
                $notice = "สร้างคำสั่งซื้อ #ORD-{$newOrderId} เรียบร้อยแล้ว";
            } else {
                $error = "ไม่พบสินค้าหรือผู้ใช้งานที่ระบุ";
            }
        }
    }
}

// -------------------------------------------------------------
// Pagination & Data Retrieval
// -------------------------------------------------------------

// Search terms
$orderSearch = trim($_GET['order_search'] ?? '');
$productSearch = trim($_GET['product_search'] ?? '');

// 1. Pagination for Orders (10 orders per page)
$ordersPerPage = 10;
$orderPage = max(1, (int)($_GET['order_page'] ?? 1));
$totalOrders = 0;
$totalFilteredOrders = 0;
$allOrders = [];

// 2. Pagination for Products (10 products per page)
$productsPerPage = 10;
$productPage = max(1, (int)($_GET['product_page'] ?? 1));
$totalProducts = 0;
$totalFilteredProducts = 0;
$allProducts = [];

// Users list for manual order form
$allUsersList = [];
$selectableProductsList = [];

if ($pdo && is_db_initialized($pdo)) {
    try {
        // Overall totals
        $totalOrders = (int)$pdo->query("SELECT COUNT(*) FROM orders")->fetchColumn();
        $totalProducts = (int)$pdo->query("SELECT COUNT(*) FROM products")->fetchColumn();

        // Query filtered orders
        if ($orderSearch !== '') {
            $orderCountSql = "
                SELECT COUNT(*) FROM orders o
                LEFT JOIN users u ON o.user_id = u.id
                LEFT JOIN products p ON o.product_id = p.id
                WHERE CAST(o.id AS TEXT) LIKE ? OR u.name LIKE ? OR u.username LIKE ? OR p.name LIKE ?
            ";
            $searchParam = '%' . $orderSearch . '%';
            $cStmt = $pdo->prepare($orderCountSql);
            $cStmt->execute([$searchParam, $searchParam, $searchParam, $searchParam]);
            $totalFilteredOrders = (int)$cStmt->fetchColumn();

            $totalOrderPages = max(1, (int)ceil($totalFilteredOrders / $ordersPerPage));
            if ($orderPage > $totalOrderPages) $orderPage = $totalOrderPages;
            $orderOffset = ($orderPage - 1) * $ordersPerPage;

            $oStmt = $pdo->prepare("
                SELECT o.*, u.username, u.name as user_name, p.name as product_name, p.image as product_image
                FROM orders o
                LEFT JOIN users u ON o.user_id = u.id
                LEFT JOIN products p ON o.product_id = p.id
                WHERE CAST(o.id AS TEXT) LIKE ? OR u.name LIKE ? OR u.username LIKE ? OR p.name LIKE ?
                ORDER BY o.id DESC
                LIMIT ? OFFSET ?
            ");
            $oStmt->bindValue(1, $searchParam, PDO::PARAM_STR);
            $oStmt->bindValue(2, $searchParam, PDO::PARAM_STR);
            $oStmt->bindValue(3, $searchParam, PDO::PARAM_STR);
            $oStmt->bindValue(4, $searchParam, PDO::PARAM_STR);
            $oStmt->bindValue(5, $ordersPerPage, PDO::PARAM_INT);
            $oStmt->bindValue(6, $orderOffset, PDO::PARAM_INT);
            $oStmt->execute();
            $allOrders = $oStmt->fetchAll();
        } else {
            $totalFilteredOrders = $totalOrders;
            $totalOrderPages = max(1, (int)ceil($totalFilteredOrders / $ordersPerPage));
            if ($orderPage > $totalOrderPages) $orderPage = $totalOrderPages;
            $orderOffset = ($orderPage - 1) * $ordersPerPage;

            $oStmt = $pdo->prepare("
                SELECT o.*, u.username, u.name as user_name, p.name as product_name, p.image as product_image
                FROM orders o
                LEFT JOIN users u ON o.user_id = u.id
                LEFT JOIN products p ON o.product_id = p.id
                ORDER BY o.id DESC
                LIMIT ? OFFSET ?
            ");
            $oStmt->bindValue(1, $ordersPerPage, PDO::PARAM_INT);
            $oStmt->bindValue(2, $orderOffset, PDO::PARAM_INT);
            $oStmt->execute();
            $allOrders = $oStmt->fetchAll();
        }

        // Query filtered products (10 per page)
        if ($productSearch !== '') {
            $productCountSql = "
                SELECT COUNT(*) FROM products
                WHERE CAST(id AS TEXT) LIKE ? OR name LIKE ? OR type LIKE ? OR color LIKE ?
            ";
            $pSearchParam = '%' . $productSearch . '%';
            $cpStmt = $pdo->prepare($productCountSql);
            $cpStmt->execute([$pSearchParam, $pSearchParam, $pSearchParam, $pSearchParam]);
            $totalFilteredProducts = (int)$cpStmt->fetchColumn();

            $totalProductPages = max(1, (int)ceil($totalFilteredProducts / $productsPerPage));
            if ($productPage > $totalProductPages) $productPage = $totalProductPages;
            $productOffset = ($productPage - 1) * $productsPerPage;

            $pStmt = $pdo->prepare("
                SELECT * FROM products
                WHERE CAST(id AS TEXT) LIKE ? OR name LIKE ? OR type LIKE ? OR color LIKE ?
                ORDER BY id DESC
                LIMIT ? OFFSET ?
            ");
            $pStmt->bindValue(1, $pSearchParam, PDO::PARAM_STR);
            $pStmt->bindValue(2, $pSearchParam, PDO::PARAM_STR);
            $pStmt->bindValue(3, $pSearchParam, PDO::PARAM_STR);
            $pStmt->bindValue(4, $pSearchParam, PDO::PARAM_STR);
            $pStmt->bindValue(5, $productsPerPage, PDO::PARAM_INT);
            $pStmt->bindValue(6, $productOffset, PDO::PARAM_INT);
            $pStmt->execute();
            $allProducts = $pStmt->fetchAll();
        } else {
            $totalFilteredProducts = $totalProducts;
            $totalProductPages = max(1, (int)ceil($totalFilteredProducts / $productsPerPage));
            if ($productPage > $totalProductPages) $productPage = $totalProductPages;
            $productOffset = ($productPage - 1) * $productsPerPage;

            $pStmt = $pdo->prepare("
                SELECT * FROM products
                ORDER BY id DESC
                LIMIT ? OFFSET ?
            ");
            $pStmt->bindValue(1, $productsPerPage, PDO::PARAM_INT);
            $pStmt->bindValue(2, $productOffset, PDO::PARAM_INT);
            $pStmt->execute();
            $allProducts = $pStmt->fetchAll();
        }

        // Selectable list for manual order form
        $selectableProductsList = $pdo->query("SELECT id, name, price FROM products ORDER BY id DESC LIMIT 100")->fetchAll();

        // Users list
        $uStmt = $pdo->query("SELECT id, username, name FROM users ORDER BY id ASC LIMIT 50");
        $allUsersList = $uStmt->fetchAll();
    } catch (Exception $e) {}
}

$page_title = 'KKC - แผงควบคุมผู้ดูแลระบบ (Admin Panel)';
$show_back_arrow = true;
$back_url = base_url('profile.php');

include __DIR__ . '/includes/header.php';
?>

<style>
/* Fullscreen Admin Layout & Responsive Navigation */
.admin-fullscreen-layout {
    display: flex;
    min-height: calc(100vh - 70px);
    width: 100%;
    margin: 0;
    padding: 0;
    background-color: var(--color-gray-light);
}

.admin-sidebar {
    width: 260px;
    min-width: 260px;
    background-color: var(--color-white);
    border-right: 2px solid #e5e5e5;
    display: flex;
    flex-direction: column;
    padding: 24px 16px;
    gap: 8px;
}

.admin-sidebar-header {
    padding-bottom: 16px;
    margin-bottom: 12px;
    border-bottom: 2px solid var(--color-gray-light);
}

.admin-nav-item {
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 12px 16px;
    border-radius: 6px;
    font-family: var(--font-krub);
    font-size: 15px;
    font-weight: 600;
    color: var(--color-gray-dark);
    text-decoration: none;
    transition: all 0.2s ease;
}

.admin-nav-item:hover {
    background-color: var(--color-gray-light);
    color: var(--color-black);
}

.admin-nav-item.active {
    background-color: var(--color-black);
    color: var(--color-white);
}

.admin-nav-icon {
    width: 20px;
    height: 20px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
}

.admin-content-area {
    flex: 1;
    padding: 24px 32px;
    overflow-x: auto;
}

.admin-panel-card {
    background-color: var(--color-white);
    border-radius: 8px;
    padding: 24px;
    box-shadow: 0 1px 4px rgba(0,0,0,0.06);
    min-height: 100%;
}

/* Admin Search Bar */
.admin-search-wrapper {
    display: flex;
    gap: 8px;
    align-items: center;
    margin: 16px 0 20px;
    max-width: 500px;
}

.admin-search-input {
    flex: 1;
    padding: 9px 14px;
    border: 1px solid #ccc;
    border-radius: 6px;
    font-family: var(--font-krub);
    font-size: 14px;
    outline: none;
}

.admin-search-input:focus {
    border-color: var(--color-black);
}

.admin-search-btn {
    padding: 9px 18px;
    background-color: var(--color-black);
    color: var(--color-white);
    border: none;
    border-radius: 6px;
    font-family: var(--font-krub);
    font-size: 14px;
    font-weight: 600;
    cursor: pointer;
    white-space: nowrap;
}

.admin-clear-btn {
    padding: 9px 14px;
    background-color: #e5e5e5;
    color: #333;
    border-radius: 6px;
    text-decoration: none;
    font-family: var(--font-krub);
    font-size: 14px;
    font-weight: 500;
    white-space: nowrap;
}

/* Mobile Bottom Tab Menu */
.admin-mobile-tab-bar {
    display: none;
}

@media (max-width: 768px) {
    .admin-fullscreen-layout {
        flex-direction: column;
    }
    .admin-sidebar {
        display: none !important; /* Replaced by bottom tab bar */
    }
    .admin-content-area {
        padding: 16px;
        padding-bottom: 85px; /* Space for bottom tab bar */
        width: 100%;
    }
    .admin-mobile-tab-bar {
        display: flex !important;
        position: fixed !important;
        bottom: 0 !important;
        top: auto !important;
        left: 0 !important;
        right: 0 !important;
        width: 100% !important;
        height: 60px !important;
        margin: 0 !important;
        padding: 0 !important;
        background-color: var(--color-white) !important;
        border-top: 2px solid #e0e0e0 !important;
        z-index: 99999 !important;
        box-shadow: 0 -2px 10px rgba(0, 0, 0, 0.08) !important;
        padding-bottom: max(0px, env(safe-area-inset-bottom)) !important;
    }
    .admin-mobile-tab-item {
        flex: 1;
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        gap: 2px;
        text-decoration: none;
        color: #777;
        font-family: var(--font-krub);
        font-size: 12px;
        font-weight: 600;
        transition: color 0.15s;
    }
    .admin-mobile-tab-item.active {
        color: var(--color-black);
        background-color: #f7f7f7;
        border-top: 3px solid var(--color-black);
    }
    footer {
        margin-bottom: 60px;
    }
}

@media (min-width: 769px) {
    .admin-mobile-tab-bar {
        display: none !important;
    }
}
</style>

<div class="admin-fullscreen-layout">
    <!-- Desktop Sidebar ("Slice ข้าง") -->
    <aside class="admin-sidebar">
        <div class="admin-sidebar-header">
            <h2 class="title" style="font-size: 18px; margin: 0;">ADMIN PANEL</h2>
            <span style="font-size: 12px; color: #888;">ผู้ดูแลระบบ: <?= e(current_user()['name']) ?></span>
        </div>

        <a href="?tab=orders" class="admin-nav-item <?= $tab === 'orders' ? 'active' : '' ?>">
            <span class="admin-nav-icon">
                <img src="<?= asset_url('public/box.svg') ?>" alt="orders" style="width: 18px; height: 18px;" />
            </span>
            <span>คำสั่งซื้อทั้งหมด (<?= $totalOrders ?>)</span>
        </a>

        <a href="?tab=products" class="admin-nav-item <?= $tab === 'products' ? 'active' : '' ?>">
            <span class="admin-nav-icon">
                <img src="<?= asset_url('public/sort.svg') ?>" alt="products" style="width: 18px; height: 18px;" />
            </span>
            <span>จัดการสินค้า (<?= $totalProducts ?>)</span>
        </a>

        <a href="?tab=add_product" class="admin-nav-item <?= $tab === 'add_product' ? 'active' : '' ?>">
            <span class="admin-nav-icon">
                <img src="<?= asset_url('public/add-cart.svg') ?>" alt="add" style="width: 18px; height: 18px;" />
            </span>
            <span>เพิ่มสินค้า / คำสั่งซื้อ</span>
        </a>

        <div style="margin-top: auto; padding-top: 16px; border-top: 1px solid #eee;">
            <a href="<?= base_url('profile.php') ?>" class="admin-nav-item" style="color: #666;">
                <span>[←] กลับหน้าโปรไฟล์</span>
            </a>
        </div>
    </aside>

    <!-- Main Content Area (Full Screen) -->
    <main class="admin-content-area">
        <div class="admin-panel-card">
            <?php if (!empty($notice)): ?>
                <div style="margin-bottom: 16px; padding: 12px 16px; background-color: #d4edda; color: #155724; border-radius: 6px; font-family: var(--font-krub);">
                    [เรียบร้อย] <?= e($notice) ?>
                </div>
            <?php endif; ?>

            <?php if (!empty($error)): ?>
                <div class="auth-error" style="display: block; margin-bottom: 16px;"><?= e($error) ?></div>
            <?php endif; ?>

            <?php if ($tab === 'orders'): ?>
                <!-- ======================================================= -->
                <!-- Tab 1: Orders (10 orders per page + Search + Pagination)-->
                <!-- ======================================================= -->
                <div class="divider-head">
                    <div>
                        <h1 class="title" style="font-size: 22px;">คำสั่งซื้อทั้งหมด</h1>
                        <span style="font-size: 13px; color: #888;">
                            แสดงหน้า <?= $orderPage ?> จากทั้งหมด <?= $totalOrderPages ?> หน้า (พบ <?= $totalFilteredOrders ?> รายการ จากทั้งหมด <?= $totalOrders ?>)
                        </span>
                    </div>
                    <span class="order-badge badge-pending" style="font-size: 12px;">10 รายการ / หน้า</span>
                </div>

                <!-- Search Bar for Orders -->
                <form method="GET" action="<?= base_url('admin.php') ?>" class="admin-search-wrapper">
                    <input type="hidden" name="tab" value="orders">
                    <input type="text" name="order_search" value="<?= e($orderSearch) ?>" class="admin-search-input" placeholder="ค้นหาเลขออร์เดอร์, ชื่อลูกค้า, หรือชื่อสินค้า..." />
                    <button type="submit" class="admin-search-btn">ค้นหา</button>
                    <?php if ($orderSearch !== ''): ?>
                        <a href="?tab=orders" class="admin-clear-btn">ล้างค้นหา</a>
                    <?php endif; ?>
                </form>

                <div style="overflow-x: auto;">
                    <table class="product-table">
                        <thead>
                            <tr style="background-color: var(--color-gray-light);">
                                <th>เลขออร์เดอร์</th>
                                <th>ผู้สั่งซื้อ</th>
                                <th>สินค้า</th>
                                <th>จำนวน</th>
                                <th>ยอดรวม</th>
                                <th>วันที่สั่งซื้อ</th>
                                <th>สถานะ & บันทึก</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($allOrders)): ?>
                                <tr>
                                    <td colspan="7" style="text-align: center; padding: 40px; color: #888;">
                                        <?= $orderSearch !== '' ? 'ไม่พบคำสั่งซื้อที่ตรงกับคำค้นหา "' . e($orderSearch) . '"' : 'ไม่พบรายการคำสั่งซื้อ' ?>
                                    </td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($allOrders as $o): ?>
                                    <tr>
                                        <td style="font-weight: 700;">#ORD-<?= str_pad((string)$o['id'], 5, '0', STR_PAD_LEFT) ?></td>
                                        <td>
                                            <strong><?= e($o['user_name'] ?: $o['username'] ?: 'User #' . $o['user_id']) ?></strong>
                                            <div style="font-size: 12px; color: #888;">@<?= e($o['username']) ?></div>
                                        </td>
                                        <td>
                                            <div style="display: flex; align-items: center; gap: 8px;">
                                                <img src="<?= asset_url($o['product_image'] ?: 'assets/shirt.png') ?>" alt="" style="width: 36px; height: 36px; object-fit: cover; border-radius: 4px;" />
                                                <a href="<?= base_url('product-detail.php?id=' . $o['product_id']) ?>" target="_blank" style="color: var(--color-info); font-weight: 500;">
                                                    <?= e($o['product_name'] ?: 'สินค้า #' . $o['product_id']) ?>
                                                </a>
                                            </div>
                                        </td>
                                        <td style="text-align: center;"><?= $o['quantity'] ?></td>
                                        <td style="font-weight: 700; color: var(--color-black);"><?= format_price($o['total']) ?></td>
                                        <td style="font-size: 13px; color: #666;"><?= date('d/m/Y H:i', strtotime($o['created_at'])) ?></td>
                                        <td>
                                            <form method="POST" action="<?= base_url('admin.php?tab=orders&order_page=' . $orderPage . ($orderSearch !== '' ? '&order_search=' . urlencode($orderSearch) : '')) ?>" style="display: flex; gap: 6px; align-items: center;">
                                                <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
                                                <input type="hidden" name="admin_action" value="update_order_status">
                                                <input type="hidden" name="order_id" value="<?= $o['id'] ?>">
                                                <select name="status" class="auth-input" style="padding: 4px 8px; font-size: 13px; width: auto;">
                                                    <option value="รอดำเนินการ" <?= $o['status'] === 'รอดำเนินการ' ? 'selected' : '' ?>>รอดำเนินการ</option>
                                                    <option value="กำลังจัดส่ง" <?= $o['status'] === 'กำลังจัดส่ง' ? 'selected' : '' ?>>กำลังจัดส่ง</option>
                                                    <option value="จัดส่งแล้ว" <?= $o['status'] === 'จัดส่งแล้ว' ? 'selected' : '' ?>>จัดส่งแล้ว</option>
                                                    <option value="ยกเลิก" <?= $o['status'] === 'ยกเลิก' ? 'selected' : '' ?>>ยกเลิก</option>
                                                </select>
                                                <button type="submit" class="auth-btn" style="margin: 0; padding: 5px 12px; font-size: 12px; width: auto;">บันทึก</button>
                                            </form>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>

                <!-- Page Number Pagination (KKC Standard Component) -->
                <?php if ($totalOrderPages > 1): ?>
                    <div class="page-number" style="margin-top: 24px;">
                        <div class="page-number-container">
                            <!-- Prev Button -->
                            <?php 
                            $orderSearchParam = $orderSearch !== '' ? '&order_search=' . urlencode($orderSearch) : '';
                            ?>
                            <?php if ($orderPage > 1): ?>
                                <a href="?tab=orders&order_page=<?= $orderPage - 1 ?><?= $orderSearchParam ?>" class="page-number-button" aria-label="ก่อนหน้า">
                                    <img src="<?= asset_url('public/arrow.svg') ?>" alt="arrow" class="arrow-left" />
                                </a>
                            <?php else: ?>
                                <button type="button" class="page-number-button" disabled aria-label="ก่อนหน้า">
                                    <img src="<?= asset_url('public/arrow.svg') ?>" alt="arrow" class="arrow-left" />
                                </button>
                            <?php endif; ?>

                            <!-- Numbered Buttons -->
                            <?php
                            $maxVisible = 5;
                            $start = max(1, $orderPage - (int)floor($maxVisible / 2));
                            $end = min($totalOrderPages, $start + $maxVisible - 1);
                            if ($end - $start < $maxVisible - 1) {
                                $start = max(1, $end - $maxVisible + 1);
                            }
                            for ($p = $start; $p <= $end; $p++):
                            ?>
                                <a href="?tab=orders&order_page=<?= $p ?><?= $orderSearchParam ?>" class="page-number-button <?= ($p === $orderPage) ? 'active' : '' ?>">
                                    <?= $p ?>
                                </a>
                            <?php endfor; ?>

                            <!-- Next Button -->
                            <?php if ($orderPage < $totalOrderPages): ?>
                                <a href="?tab=orders&order_page=<?= $orderPage + 1 ?><?= $orderSearchParam ?>" class="page-number-button" aria-label="ถัดไป">
                                    <img src="<?= asset_url('public/arrow.svg') ?>" alt="arrow" class="arrow-right" />
                                </a>
                            <?php else: ?>
                                <button type="button" class="page-number-button" disabled aria-label="ถัดไป">
                                    <img src="<?= asset_url('public/arrow.svg') ?>" alt="arrow" class="arrow-right" />
                                </button>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endif; ?>

            <?php elseif ($tab === 'products'): ?>
                <!-- ======================================================= -->
                <!-- Tab 2: Products Management (10 per page + Search)       -->
                <!-- ======================================================= -->
                <div class="divider-head">
                    <div>
                        <h1 class="title" style="font-size: 22px;">จัดการสินค้า</h1>
                        <span style="font-size: 13px; color: #888;">
                            แสดงหน้า <?= $productPage ?> จากทั้งหมด <?= $totalProductPages ?> หน้า (พบ <?= $totalFilteredProducts ?> รายการ จากทั้งหมด <?= $totalProducts ?>)
                        </span>
                    </div>
                    <div style="display: flex; gap: 8px; align-items: center;">
                        <span class="order-badge badge-pending" style="font-size: 12px;">10 รายการ / หน้า</span>
                        <a href="?tab=add_product" class="login-btn" style="text-decoration: none; padding: 6px 14px;">+ เพิ่มสินค้าใหม่</a>
                    </div>
                </div>

                <!-- Search Bar for Products -->
                <form method="GET" action="<?= base_url('admin.php') ?>" class="admin-search-wrapper">
                    <input type="hidden" name="tab" value="products">
                    <input type="text" name="product_search" value="<?= e($productSearch) ?>" class="admin-search-input" placeholder="ค้นหารหัสสินค้า, ชื่อสินค้า, หมวดหมู่, หรือสี..." />
                    <button type="submit" class="admin-search-btn">ค้นหา</button>
                    <?php if ($productSearch !== ''): ?>
                        <a href="?tab=products" class="admin-clear-btn">ล้างค้นหา</a>
                    <?php endif; ?>
                </form>

                <div style="overflow-x: auto;">
                    <table class="product-table">
                        <thead>
                            <tr style="background-color: var(--color-gray-light);">
                                <th>รหัสสินค้า</th>
                                <th>รูปภาพ</th>
                                <th>ชื่อสินค้า</th>
                                <th>หมวดหมู่</th>
                                <th>ขนาด</th>
                                <th>ราคา</th>
                                <th>สถานะ</th>
                                <th>สลับสถานะ</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($allProducts)): ?>
                                <tr>
                                    <td colspan="8" style="text-align: center; padding: 40px; color: #888;">
                                        <?= $productSearch !== '' ? 'ไม่พบสินค้าที่ตรงกับคำค้นหา "' . e($productSearch) . '"' : 'ไม่พบรายการสินค้า' ?>
                                    </td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($allProducts as $p): ?>
                                    <tr>
                                        <td style="font-weight: 700;">P<?= str_pad((string)$p['id'], 5, '0', STR_PAD_LEFT) ?></td>
                                        <td>
                                            <img src="<?= asset_url($p['image']) ?>" alt="<?= e($p['name']) ?>" style="width: 44px; height: 44px; object-fit: cover; border-radius: 4px; border: 1px solid #eee;" />
                                        </td>
                                        <td>
                                            <a href="<?= base_url('product-detail.php?id=' . $p['id']) ?>" target="_blank" style="font-weight: 600; color: var(--color-black);">
                                                <?= e($p['name']) ?>
                                            </a>
                                        </td>
                                        <td><?= e(get_category_label($p['type'])) ?></td>
                                        <td><?= e($p['size']) ?></td>
                                        <td style="font-weight: 600;"><?= format_price($p['price']) ?></td>
                                        <td>
                                            <span class="order-badge <?= $p['status'] === 'ขายแล้ว' ? 'badge-cancelled' : 'badge-delivered' ?>">
                                                <?= e($p['status']) ?>
                                            </span>
                                        </td>
                                        <td>
                                            <form method="POST" action="<?= base_url('admin.php?tab=products&product_page=' . $productPage . ($productSearch !== '' ? '&product_search=' . urlencode($productSearch) : '')) ?>" style="display: inline;">
                                                <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
                                                <input type="hidden" name="admin_action" value="toggle_product_status">
                                                <input type="hidden" name="product_id" value="<?= $p['id'] ?>">
                                                <input type="hidden" name="current_status" value="<?= e($p['status']) ?>">
                                                <button type="submit" class="auth-btn" style="margin: 0; padding: 4px 10px; font-size: 12px; width: auto; background-color: <?= $p['status'] === 'ขายแล้ว' ? 'var(--color-gray-dark)' : 'var(--color-error)' ?>;">
                                                    <?= $p['status'] === 'ขายแล้ว' ? 'เปิดขายใหม่' : 'ปรับเป็นขายแล้ว' ?>
                                                </button>
                                            </form>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>

                <!-- Page Number Pagination for Products (10 items / page) -->
                <?php if ($totalProductPages > 1): ?>
                    <div class="page-number" style="margin-top: 24px;">
                        <div class="page-number-container">
                            <!-- Prev Button -->
                            <?php 
                            $prodSearchParam = $productSearch !== '' ? '&product_search=' . urlencode($productSearch) : '';
                            ?>
                            <?php if ($productPage > 1): ?>
                                <a href="?tab=products&product_page=<?= $productPage - 1 ?><?= $prodSearchParam ?>" class="page-number-button" aria-label="ก่อนหน้า">
                                    <img src="<?= asset_url('public/arrow.svg') ?>" alt="arrow" class="arrow-left" />
                                </a>
                            <?php else: ?>
                                <button type="button" class="page-number-button" disabled aria-label="ก่อนหน้า">
                                    <img src="<?= asset_url('public/arrow.svg') ?>" alt="arrow" class="arrow-left" />
                                </button>
                            <?php endif; ?>

                            <!-- Numbered Buttons -->
                            <?php
                            $maxVisible = 5;
                            $start = max(1, $productPage - (int)floor($maxVisible / 2));
                            $end = min($totalProductPages, $start + $maxVisible - 1);
                            if ($end - $start < $maxVisible - 1) {
                                $start = max(1, $end - $maxVisible + 1);
                            }
                            for ($p = $start; $p <= $end; $p++):
                            ?>
                                <a href="?tab=products&product_page=<?= $p ?><?= $prodSearchParam ?>" class="page-number-button <?= ($p === $productPage) ? 'active' : '' ?>">
                                    <?= $p ?>
                                </a>
                            <?php endfor; ?>

                            <!-- Next Button -->
                            <?php if ($productPage < $totalProductPages): ?>
                                <a href="?tab=products&product_page=<?= $productPage + 1 ?><?= $prodSearchParam ?>" class="page-number-button" aria-label="ถัดไป">
                                    <img src="<?= asset_url('public/arrow.svg') ?>" alt="arrow" class="arrow-right" />
                                </a>
                            <?php else: ?>
                                <button type="button" class="page-number-button" disabled aria-label="ถัดไป">
                                    <img src="<?= asset_url('public/arrow.svg') ?>" alt="arrow" class="arrow-right" />
                                </button>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endif; ?>

            <?php elseif ($tab === 'add_product'): ?>
                <!-- ======================================================= -->
                <!-- Tab 3: Add Product (Multi-Image & Gallery) & Add Order  -->
                <!-- ======================================================= -->
                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(320px, 1fr)); gap: 32px;">
                    <!-- Section A: Add Product -->
                    <div>
                        <div class="divider-head">
                            <h2 class="title" style="font-size: 20px;">เพิ่มสินค้าใหม่ (พร้อม Gallery หลายรูป)</h2>
                        </div>

                        <form method="POST" action="<?= base_url('admin.php?tab=products') ?>" class="auth-form" enctype="multipart/form-data">
                            <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
                            <input type="hidden" name="admin_action" value="add_product">

                            <div class="auth-field">
                                <label for="prodName">ชื่อสินค้า *</label>
                                <input type="text" name="name" id="prodName" class="auth-input" placeholder="เช่น Vintage Graphic T-Shirt #101" required />
                            </div>

                            <div class="auth-field">
                                <label for="prodType">หมวดหมู่ *</label>
                                <select name="type" id="prodType" class="auth-input" required>
                                    <option value="shirt">เสื้อ (Shirt)</option>
                                    <option value="pants">กางเกง (Pants)</option>
                                    <option value="skirt">กระโปรง (Skirt)</option>
                                    <option value="cap">หมวก (Cap)</option>
                                </select>
                            </div>

                            <div style="display: flex; gap: 12px;">
                                <div class="auth-field" style="flex: 1;">
                                    <label for="prodSize">ขนาด (Size)</label>
                                    <select name="size" id="prodSize" class="auth-input" required>
                                        <option value="S">S</option>
                                        <option value="M" selected>M</option>
                                        <option value="L">L</option>
                                    </select>
                                </div>
                                <div class="auth-field" style="flex: 1;">
                                    <label for="prodPrice">ราคา (บาท) *</label>
                                    <input type="number" name="price" id="prodPrice" class="auth-input" placeholder="เช่น 35" min="1" step="1" required />
                                </div>
                            </div>

                            <div class="auth-field">
                                <label for="prodColor">สี</label>
                                <input type="text" name="color" id="prodColor" class="auth-input" placeholder="เช่น ดำ, ขาว, น้ำเงิน" value="ดำ" required />
                            </div>

                            <!-- Main Image Input -->
                            <div class="auth-field" style="background: #f9f9f9; padding: 12px; border-radius: 6px; border: 1px dashed #ccc;">
                                <label style="font-weight: 700;">รูปภาพหลัก (Main Image)</label>
                                <span style="font-size: 12px; color: #666; margin-bottom: 6px;">อัปโหลดไฟล์รูป หรือระบุ URL/Path</span>
                                <input type="file" name="main_image_file" accept="image/*" class="auth-input" style="background: #fff; margin-bottom: 6px;" />
                                <input type="text" name="image_url" class="auth-input" placeholder="หรือพิมพ์ URL เช่น assets/shirt.png" />
                            </div>

                            <!-- Multiple Gallery Images Input -->
                            <div class="auth-field" style="background: #f9f9f9; padding: 12px; border-radius: 6px; border: 1px dashed #ccc;">
                                <label style="font-weight: 700;">รูปแกลเลอรีเพิ่มเติม (Gallery - หลายรูป)</label>
                                <span style="font-size: 12px; color: #666; margin-bottom: 6px;">เลือกหลายไฟล์พร้อมกัน หรือกรอก URL ทีละบรรทัด</span>
                                <input type="file" name="gallery_files[]" multiple accept="image/*" class="auth-input" style="background: #fff; margin-bottom: 6px;" />
                                <textarea name="gallery_urls" rows="3" class="auth-input" placeholder="หรือกรอก URL รูปแกลเลอรี (คั่นแต่ละรูปด้วยการขึ้นบรรทัดใหม่)"></textarea>
                            </div>

                            <button type="submit" class="auth-btn">
                                บันทึกสินค้าใหม่พร้อม Gallery
                            </button>
                        </form>
                    </div>

                    <!-- Section B: Manual Create Order -->
                    <div>
                        <div class="divider-head">
                            <h2 class="title" style="font-size: 20px;">เพิ่มคำสั่งซื้อใหม่ (สร้างออร์เดอร์)</h2>
                        </div>

                        <form method="POST" action="<?= base_url('admin.php?tab=orders') ?>" class="auth-form">
                            <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
                            <input type="hidden" name="admin_action" value="create_order">

                            <div class="auth-field">
                                <label for="orderUser">เลือกลูกค้า / ผู้สั่งซื้อ *</label>
                                <select name="user_id" id="orderUser" class="auth-input" required>
                                    <option value="">-- เลือกลูกค้า --</option>
                                    <?php foreach ($allUsersList as $u): ?>
                                        <option value="<?= $u['id'] ?>">#<?= $u['id'] ?> - <?= e($u['name']) ?> (@<?= e($u['username']) ?>)</option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <div class="auth-field">
                                <label for="orderProduct">เลือกสินค้า *</label>
                                <select name="product_id" id="orderProduct" class="auth-input" required>
                                    <option value="">-- เลือกสินค้า --</option>
                                    <?php foreach ($selectableProductsList as $p): ?>
                                        <option value="<?= $p['id'] ?>">#<?= $p['id'] ?> - <?= e($p['name']) ?> (<?= format_price($p['price']) ?>)</option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <div class="auth-field">
                                <label for="orderQty">จำนวนชิ้น *</label>
                                <input type="number" name="quantity" id="orderQty" class="auth-input" value="1" min="1" required />
                            </div>

                            <div class="auth-field">
                                <label for="orderStatus">สถานะเริ่มต้น</label>
                                <select name="status" id="orderStatus" class="auth-input">
                                    <option value="รอดำเนินการ">รอดำเนินการ</option>
                                    <option value="กำลังจัดส่ง">กำลังจัดส่ง</option>
                                    <option value="จัดส่งแล้ว">จัดส่งแล้ว</option>
                                </select>
                            </div>

                            <button type="submit" class="auth-btn" style="background-color: var(--color-gray-dark);">
                                สร้างคำสั่งซื้อนี้
                            </button>
                        </form>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </main>
</div>

<!-- Mobile Bottom Tab Navigation Bar (เมนูแท็บด้านล่างขอบจอสำหรับมือถือ) -->
<div class="admin-mobile-tab-bar" role="navigation">
    <a href="?tab=orders" class="admin-mobile-tab-item <?= $tab === 'orders' ? 'active' : '' ?>">
        <img src="<?= asset_url('public/box.svg') ?>" alt="orders" style="width: 20px; height: 20px;" />
        <span>คำสั่งซื้อ</span>
    </a>
    <a href="?tab=products" class="admin-mobile-tab-item <?= $tab === 'products' ? 'active' : '' ?>">
        <img src="<?= asset_url('public/sort.svg') ?>" alt="products" style="width: 20px; height: 20px;" />
        <span>จัดการสินค้า</span>
    </a>
    <a href="?tab=add_product" class="admin-mobile-tab-item <?= $tab === 'add_product' ? 'active' : '' ?>">
        <img src="<?= asset_url('public/add-cart.svg') ?>" alt="add" style="width: 20px; height: 20px;" />
        <span>เพิ่มสินค้า</span>
    </a>
</div>

<?php include __DIR__ . '/includes/footer.php'; ?>
