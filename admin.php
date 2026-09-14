<?php
/**
 * Admin Management Dashboard
 * Fullscreen layout with Desktop Sidebar ("Slice ข้าง") & Mobile Bottom Navigation Bar (No Emojis)
 */
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/data/db.php';
require_once __DIR__ . '/data/products_data.php';
require_once __DIR__ . '/data/order_data.php';
require_once __DIR__ . '/includes/thai_date.php';

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

            $typeImages = [
                'shirt' => 'assets/shirt.png',
                'pants' => 'assets/pants.png',
                'skirt' => 'assets/skirt.png',
                'cap'   => 'assets/cap.png',
            ];
            $mainImage = $typeImages[$type] ?? 'assets/shirt.png';
            $galleryList = [];

            // Handle ordered uploaded product images (Files only)
            $uploadedFiles = !empty($_FILES['product_images']['name'][0]) ? $_FILES['product_images'] : (!empty($_FILES['gallery_files']['name'][0]) ? $_FILES['gallery_files'] : []);
            if (!empty($uploadedFiles['name'][0])) {
                $allowed = ['jpg', 'jpeg', 'png', 'webp', 'svg'];
                foreach ($uploadedFiles['tmp_name'] as $idx => $tmp) {
                    if (!empty($tmp) && is_uploaded_file($tmp)) {
                        $fName = $uploadedFiles['name'][$idx];
                        $ext = strtolower(pathinfo($fName, PATHINFO_EXTENSION));
                        if (in_array($ext, $allowed, true)) {
                            $targetName = 'prod_' . time() . "_{$idx}_" . bin2hex(random_bytes(4)) . '.' . $ext;
                            $targetPath = __DIR__ . '/assets/uploads/' . $targetName;
                            if (move_uploaded_file($tmp, $targetPath)) {
                                $savedPath = 'assets/uploads/' . $targetName;
                                if (empty($galleryList)) {
                                    $mainImage = $savedPath; // First image in sequence is main image
                                }
                                $galleryList[] = $savedPath;
                            }
                        }
                    }
                }
            }


            // If gallery list is empty, default to repeating main image
            if (empty($galleryList)) {
                $galleryList = [$mainImage, $mainImage];
            }

            if (!empty($name) && $price > 0) {
                $stmt = $pdo->prepare("
                    INSERT INTO products (name, type, size, price, color, status, image)
                    VALUES (?, ?, ?, ?, ?, 'พร้อมส่ง', ?)
                ");
                $stmt->execute([$name, $type, $size, $price, $color, $mainImage]);
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
                $items = [[
                    'id'       => $prod['id'],
                    'name'     => $prod['name'],
                    'price'    => (float)$prod['price'],
                    'image'    => $prod['image'],
                    'size'     => $prod['size'] ?? 'M',
                    'color'    => $prod['color'] ?? '',
                    'quantity' => $qty,
                ]];
                $stmt = $pdo->prepare("
                    INSERT INTO orders (user_id, items, total, status, created_at, product_id, quantity, price)
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?)
                ");
                $stmt->execute([
                    $userId,
                    json_encode($items, JSON_UNESCAPED_UNICODE),
                    $total,
                    $status,
                    date('Y-m-d H:i:s'),
                    $productId,
                    $qty,
                    $prod['price']
                ]);
                $newOrderId = $pdo->lastInsertId();
                $notice = "สร้างคำสั่งซื้อ #ORD-{$newOrderId} เรียบร้อยแล้ว";
            } else {
                $error = "ไม่พบสินค้าหรือผู้ใช้งานที่ระบุ";
            }
        }

        // 5. Edit Product with Gallery & Image Reordering
        elseif ($adminAction === 'edit_product' && $pdo) {
            $productId = (int)($_POST['product_id'] ?? 0);
            $name   = trim($_POST['name'] ?? '');
            $type   = trim($_POST['type'] ?? 'shirt');
            $size   = trim($_POST['size'] ?? 'M');
            $price  = (float)($_POST['price'] ?? 0);
            $color  = trim($_POST['color'] ?? 'ดำ');
            $status = trim($_POST['status'] ?? 'พร้อมส่ง');

            $sequence = $_POST['image_sequence'] ?? [];

            // Handle newly uploaded product images and map by original file index
            $uploadedFiles = !empty($_FILES['product_images']['name'][0]) ? $_FILES['product_images'] : [];
            $savedNewFiles = [];
            if (!empty($uploadedFiles['name'][0])) {
                $allowed = ['jpg', 'jpeg', 'png', 'webp', 'svg'];
                foreach ($uploadedFiles['tmp_name'] as $idx => $tmp) {
                    if (!empty($tmp) && is_uploaded_file($tmp)) {
                        $fName = $uploadedFiles['name'][$idx];
                        $ext = strtolower(pathinfo($fName, PATHINFO_EXTENSION));
                        if (in_array($ext, $allowed, true)) {
                            $targetName = 'prod_' . time() . "_{$idx}_" . bin2hex(random_bytes(4)) . '.' . $ext;
                            $targetPath = __DIR__ . '/assets/uploads/' . $targetName;
                            if (move_uploaded_file($tmp, $targetPath)) {
                                $savedNewFiles[$idx] = 'assets/uploads/' . $targetName;
                            }
                        }
                    }
                }
            }

            // Assemble final gallery in the exact order specified by the user in the UI
            $finalGallery = [];
            if (!empty($sequence) && is_array($sequence)) {
                foreach ($sequence as $seqItem) {
                    if (strpos($seqItem, 'existing:') === 0) {
                        $finalGallery[] = substr($seqItem, 9);
                    } elseif (strpos($seqItem, 'new:') === 0) {
                        $nIdx = (int)substr($seqItem, 4);
                        if (isset($savedNewFiles[$nIdx])) {
                            $finalGallery[] = $savedNewFiles[$nIdx];
                        }
                    }
                }
            } else {
                $existingImages = isset($_POST['existing_gallery']) && is_array($_POST['existing_gallery']) ? array_values(array_filter($_POST['existing_gallery'])) : [];
                $finalGallery = array_merge($existingImages, array_values($savedNewFiles));
            }

            if ($productId > 0 && !empty($name) && $price > 0) {
                $curProd = get_product_by_id($productId);
                $mainImage = !empty($finalGallery) ? $finalGallery[0] : ($curProd['image'] ?? 'assets/shirt.png');

                $stmt = $pdo->prepare("
                    UPDATE products
                    SET name = ?, type = ?, size = ?, price = ?, color = ?, status = ?, image = ?
                    WHERE id = ?
                ");
                $stmt->execute([$name, $type, $size, $price, $color, $status, $mainImage, $productId]);

                // Re-populate product_gallery if images are present
                if (!empty($finalGallery)) {
                    $delG = $pdo->prepare("DELETE FROM product_gallery WHERE product_id = ?");
                    $delG->execute([$productId]);

                    $insG = $pdo->prepare("INSERT INTO product_gallery (product_id, image_url, sort_order) VALUES (?, ?, ?)");
                    foreach ($finalGallery as $idx => $gUrl) {
                        $insG->execute([$productId, $gUrl, $idx]);
                    }
                }

                $notice = "แก้ไขข้อมูลสินค้า '{$name}' (ID: {$productId}) เรียบร้อยแล้ว";
            } else {
                $error = "กรุณากรอกชื่อสินค้าและราคาให้ถูกต้อง";
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
                WHERE CAST(o.id AS TEXT) LIKE ? OR u.name LIKE ? OR u.username LIKE ? OR p.name LIKE ? OR o.items LIKE ?
            ";
            $searchParam = '%' . $orderSearch . '%';
            $cStmt = $pdo->prepare($orderCountSql);
            $cStmt->execute([$searchParam, $searchParam, $searchParam, $searchParam, $searchParam]);
            $totalFilteredOrders = (int)$cStmt->fetchColumn();

            $totalOrderPages = max(1, (int)ceil($totalFilteredOrders / $ordersPerPage));
            if ($orderPage > $totalOrderPages) $orderPage = $totalOrderPages;
            $orderOffset = ($orderPage - 1) * $ordersPerPage;

            $oStmt = $pdo->prepare("
                SELECT o.*, u.username, u.name as user_name, p.name as product_name, p.image as product_image
                FROM orders o
                LEFT JOIN users u ON o.user_id = u.id
                LEFT JOIN products p ON o.product_id = p.id
                WHERE CAST(o.id AS TEXT) LIKE ? OR u.name LIKE ? OR u.username LIKE ? OR p.name LIKE ? OR o.items LIKE ?
                ORDER BY o.id DESC
                LIMIT ? OFFSET ?
            ");
            $oStmt->bindValue(1, $searchParam, PDO::PARAM_STR);
            $oStmt->bindValue(2, $searchParam, PDO::PARAM_STR);
            $oStmt->bindValue(3, $searchParam, PDO::PARAM_STR);
            $oStmt->bindValue(4, $searchParam, PDO::PARAM_STR);
            $oStmt->bindValue(5, $searchParam, PDO::PARAM_STR);
            $oStmt->bindValue(6, $ordersPerPage, PDO::PARAM_INT);
            $oStmt->bindValue(7, $orderOffset, PDO::PARAM_INT);
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

        // 3. Order detail query for tab=order_detail
        $detailOrder = null;
        if ($tab === 'order_detail') {
            $detailId = (int)($_GET['id'] ?? 0);
            if ($detailId > 0) {
                $dStmt = $pdo->prepare("
                    SELECT o.*, u.username, u.name as user_name, u.email as user_email, u.phone as user_phone, u.address as user_address, u.img as user_img
                    FROM orders o
                    LEFT JOIN users u ON o.user_id = u.id
                    WHERE o.id = ?
                    LIMIT 1
                ");
                $dStmt->execute([$detailId]);
                $detailOrder = $dStmt->fetch();
                if ($detailOrder) {
                    $dItems = !empty($detailOrder['items']) ? json_decode($detailOrder['items'], true) : [];
                    if (!is_array($dItems) || empty($dItems)) {
                        $dItems = [[
                            'id'       => $detailOrder['product_id'] ?? 0,
                            'name'     => $detailOrder['product_name'] ?? ('สินค้า #' . ($detailOrder['product_id'] ?? '')),
                            'image'    => $detailOrder['product_image'] ?? 'assets/shirt.png',
                            'price'    => (float)($detailOrder['price'] ?? $detailOrder['total']),
                            'quantity' => (int)($detailOrder['quantity'] ?? 1),
                        ]];
                    }
                    $detailOrder['parsed_items'] = $dItems;
                }
            }
        }

        // 4. Product edit query for tab=edit_product
        $editProduct = null;
        $editGallery = [];
        if ($tab === 'edit_product') {
            $editId = (int)($_GET['id'] ?? 0);
            if ($editId > 0) {
                $epStmt = $pdo->prepare("SELECT * FROM products WHERE id = ? LIMIT 1");
                $epStmt->execute([$editId]);
                $editProduct = $epStmt->fetch();
                if ($editProduct) {
                    $egStmt = $pdo->prepare("SELECT * FROM product_gallery WHERE product_id = ? ORDER BY sort_order ASC");
                    $egStmt->execute([$editId]);
                    $editGallery = $egStmt->fetchAll();
                }
            }
        }
    } catch (Exception $e) {}
}

$page_title = 'KKC - แผงควบคุมผู้ดูแลระบบ (Admin Panel)';
if (in_array($tab, ['order_detail', 'add_product', 'orders', 'products', 'add_order', 'edit_product'], true)) {
    $show_back_arrow = false;
    $back_url = base_url('index.php');
    $hide_nav_admin_btn = true;
    $hide_nav_cart = true;
} else {
    $show_back_arrow = true;
    $back_url = base_url('profile.php');
}

include __DIR__ . '/includes/header.php';
?>



<div class="admin-fullscreen-layout">
    <!-- Desktop Sidebar ("Slice ข้าง") -->
    <aside class="admin-sidebar">
        <div class="admin-sidebar-header">
            <h2 class="title" style="font-size: 18px; margin: 0;">ADMIN PANEL</h2>
            <span style="font-size: 12px; color: #888;">ผู้ดูแลระบบ: <?= e(current_user()['name']) ?></span>
        </div>

        <a href="?tab=orders" class="admin-nav-item <?= in_array($tab, ['orders', 'order_detail'], true) ? 'active' : '' ?>">
            <span class="admin-nav-icon">
                <img src="<?= asset_url('public/box.svg') ?>" alt="orders" style="width: 18px; height: 18px;" />
            </span>
            <span>คำสั่งซื้อทั้งหมด (<?= $totalOrders ?>)</span>
        </a>

        <a href="?tab=products" class="admin-nav-item <?= in_array($tab, ['products', 'edit_product'], true) ? 'active' : '' ?>">
            <span class="admin-nav-icon">
                <img src="<?= asset_url('public/sort.svg') ?>" alt="products" style="width: 18px; height: 18px;" />
            </span>
            <span>จัดการสินค้า (<?= $totalProducts ?>)</span>
        </a>

        <a href="?tab=add_product" class="admin-nav-item <?= $tab === 'add_product' ? 'active' : '' ?>">
            <span class="admin-nav-icon">
                <img src="<?= asset_url('public/add-cart.svg') ?>" alt="add" style="width: 18px; height: 18px;" />
            </span>
            <span>เพิ่มสินค้าใหม่</span>
        </a>

        <a href="?tab=add_order" class="admin-nav-item <?= $tab === 'add_order' ? 'active' : '' ?>">
            <span class="admin-nav-icon">
                <img src="<?= asset_url('public/cart.svg') ?>" alt="add-order" style="width: 18px; height: 18px;" />
            </span>
            <span>เพิ่มคำสั่งซื้อใหม่</span>
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
                        <h1 style="font-size: 22px; font-family: var(--font-krub); font-weight: bold;">คำสั่งซื้อทั้งหมด</h1>
                        <!-- <span style="font-size: 13px; color: #888;">
                            แสดงหน้า <?= $orderPage ?> จากทั้งหมด <?= $totalOrderPages ?> หน้า (พบ <?= $totalFilteredOrders ?> รายการ จากทั้งหมด <?= $totalOrders ?>)
                        </span> -->
                    </div>
                    <!-- <span class="order-badge badge-pending" style="font-size: 12px;">10 รายการ / หน้า</span> -->
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
                                <th>จำนวน</th>
                                <th>ยอดรวม</th>
                                <th>วันที่สั่งซื้อ</th>
                                <th>สถานะ & บันทึก</th>
                                <!-- <th style="text-align: center;">รายละเอียด</th> -->
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
                                    <?php
                                    $oItems = !empty($o['items']) ? json_decode($o['items'], true) : [];
                                    if (!is_array($oItems) || empty($oItems)) {
                                        $oItems = [[
                                            'id'       => $o['product_id'] ?? 0,
                                            'name'     => $o['product_name'] ?? ('สินค้า #' . ($o['product_id'] ?? '')),
                                            'image'    => $o['product_image'] ?? 'assets/shirt.png',
                                            'quantity' => (int)($o['quantity'] ?? 1),
                                            'price'    => (float)($o['price'] ?? $o['total']),
                                        ]];
                                    }
                                    $totalQty = array_sum(array_column($oItems, 'quantity'));
                                    ?>
                                    <tr onclick="window.location='<?= base_url('admin.php?tab=order_detail&id=' . $o['id']) ?>'" class="clickable-order-row" style="cursor: pointer;" title="คลิกเพื่อดูรายละเอียดคำสั่งซื้อ #ORD-<?= str_pad((string)$o['id'], 5, '0', STR_PAD_LEFT) ?>">
                                        <td style="font-weight: 700;">
                                            <a href="<?= base_url('admin.php?tab=order_detail&id=' . $o['id']) ?>" style="color: var(--color-primary); font-family: var(--font-krub); text-decoration: underline;">
                                                #ORD-<?= str_pad((string)$o['id'], 5, '0', STR_PAD_LEFT) ?>
                                            </a>
                                        </td>
                                        <td>
                                            <strong><?= e($o['user_name'] ?: $o['username'] ?: 'User #' . $o['user_id']) ?></strong>
                                            <div style="font-size: 12px; color: #888;">@<?= e($o['username']) ?></div>
                                        </td>
                                        <td style="text-align: start; font-size: 13px;">
                                            <strong><?= $totalQty ?></strong> ชิ้น<br>
                                            <span style="color: #888; font-size: 11px;">(<?= count($oItems) ?> รายการ)</span>
                                        </td>
                                        <td style="font-weight: 700; color: var(--color-black);"><?= format_price($o['total']) ?></td>
                                        <td style="font-size: 13px; color: #666;"><?= date('d/m/Y H:i', strtotime($o['created_at'])) ?></td>
                                        <td onclick="event.stopPropagation();">
                                            <form method="POST" action="<?= base_url('admin.php?tab=orders&order_page=' . $orderPage . ($orderSearch !== '' ? '&order_search=' . urlencode($orderSearch) : '')) ?>" style="display: flex;gap: 6px;flex-wrap: nowrap;flex-direction: column;align-items: stretch;">
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
                                        <!-- <td style="text-align: center;" onclick="event.stopPropagation();">
                                            <a href="<?= base_url('admin.php?tab=order_detail&id=' . $o['id']) ?>" class="page-number-button" style="text-decoration: none; padding: 4px 10px; font-size: 12px; height: auto;">
                                                ดูข้อมูล →
                                            </a>
                                        </td> -->
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

            <?php elseif ($tab === 'order_detail'): ?>
                <!-- ======================================================= -->
                <!-- Tab: Order Detail View                                  -->
                <!-- ======================================================= -->
                <div style="margin-bottom: 20px;">
                    <a href="<?= base_url('admin.php?tab=orders') ?>" class="page-number-button" style="text-decoration: none; padding: 8px 16px; height: auto; display: inline-flex; align-items: center; gap: 8px; font-size: 14px;">
                        ← กลับไปรายการคำสั่งซื้อทั้งหมด
                    </a>
                </div>

                <?php if (!$detailOrder): ?>
                    <div style="text-align: center; padding: 60px 20px; color: #888;">
                        <h3>ไม่พบข้อมูลคำสั่งซื้อที่ระบุ</h3>
                        <p style="font-size: 14px; margin-top: 8px;">คำสั่งซื้อนี้อาจถูกลบหรือไม่มีอยู่ในระบบ</p>
                        <a href="<?= base_url('admin.php?tab=orders') ?>" class="auth-btn" style="max-width: 200px; margin: 16px auto; text-decoration: none;">กลับหน้ารายการ</a>
                    </div>
                <?php else: ?>
                    <?php
                    $detailBadge = $badgeClassMap[$detailOrder['status']] ?? 'badge-pending';
                    $parsedItems = $detailOrder['parsed_items'] ?? [];
                    ?>
                    <div class="divider-head" style="margin-bottom: 24px; padding-bottom: 16px; border-bottom: 2px solid var(--color-gray-light);">
                        <div>
                            <div style="display: flex; align-items: center; gap: 12px; flex-wrap: wrap;">
                                <h1 style="font-size: 24px; margin: 0; font-family: var(--font-krub); font-weight: bold;">
                                    คำสั่งซื้อ #ORD-<?= str_pad((string)$detailOrder['id'], 5, '0', STR_PAD_LEFT) ?>
                                </h1>
                                <span class="order-badge <?= $detailBadge ?>" style="font-size: 13px; padding: 4px 12px;">
                                    <?= e($detailOrder['status']) ?>
                                </span>
                            </div>
                            <span style="font-size: 13px; color: #888; display: block; margin-top: 6px;">
                                วันที่สั่งซื้อ: <?= thaiDateTime($detailOrder['created_at']) ?> (<?= date('d/m/Y H:i', strtotime($detailOrder['created_at'])) ?>)
                            </span>
                        </div>
                    </div>

                    <!-- 2 Column Cards Grid (User Info & Status Management) -->
                    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(320px, 1fr)); gap: 20px; margin-bottom: 28px;">
                        <!-- Card 1: User Info -->
                        <div style="background: #fafafa; border: 1px solid #e5e5e5; border-radius: 8px; padding: 20px;">
                            <div style="display: flex; align-items: center; gap: 8px; margin-bottom: 16px; padding-bottom: 10px; border-bottom: 1px solid #eee;">
                                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="color: var(--color-primary);"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path><circle cx="12" cy="7" r="4"></circle></svg>
                                <h3 style="margin: 0; font-size: 16px; font-family: var(--font-krub); font-weight: 700;">ข้อมูลลูกค้า / ผู้สั่งซื้อ</h3>
                            </div>

                            <div style="display: flex; gap: 16px; align-items: flex-start; margin-bottom: 16px;">
                                <img src="<?= asset_url($detailOrder['user_img'] ?: 'assets/profile-mock.png') ?>" alt="avatar" style="width: 52px; height: 52px; border-radius: 50%; object-fit: cover; border: 1px solid #ddd;" />
                                <div>
                                    <div style="font-weight: 700; font-size: 16px; font-family: var(--font-krub);">
                                        <?= e($detailOrder['user_name'] ?: $detailOrder['username']) ?>
                                    </div>
                                    <div style="font-size: 13px; color: #888;">
                                        @<?= e($detailOrder['username']) ?> (User ID: #<?= $detailOrder['user_id'] ?>)
                                    </div>
                                </div>
                            </div>

                            <div style="display: flex; flex-direction: column; gap: 8px; font-size: 14px; font-family: var(--font-krub);">
                                <div>
                                    <strong style="color: #666; font-size: 13px;">อีเมล:</strong>
                                    <span style="margin-left: 6px;"><?= e($detailOrder['user_email'] ?: '-') ?></span>
                                </div>
                                <div>
                                    <strong style="color: #666; font-size: 13px;">เบอร์โทรศัพท์:</strong>
                                    <span style="margin-left: 6px;"><?= e($detailOrder['user_phone'] ?: '-') ?></span>
                                </div>
                                <div style="margin-top: 4px; padding-top: 8px; border-top: 1px dashed #eee;">
                                    <strong style="color: #666; font-size: 13px; display: block; margin-bottom: 4px;">ที่อยู่สำหรับจัดส่งพัสดุ:</strong>
                                    <div style="background: #fff; padding: 10px 12px; border: 1px solid #e0e0e0; border-radius: 6px; line-height: 1.5; color: #333;">
                                        <?= nl2br(e(!empty($detailOrder['address']) ? $detailOrder['address'] : (!empty($detailOrder['user_address']) ? $detailOrder['user_address'] : 'ไม่ได้ระบุที่อยู่จัดส่ง'))) ?>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Card 2: Status Management -->
                        <div style="background: #fafafa; border: 1px solid #e5e5e5; border-radius: 8px; padding: 20px; display: flex; flex-direction: column; justify-content: space-between;">
                            <div>
                                <div style="display: flex; align-items: center; gap: 8px; margin-bottom: 16px; padding-bottom: 10px; border-bottom: 1px solid #eee;">
                                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="color: var(--color-primary);"><circle cx="12" cy="12" r="10"></circle><polyline points="12 6 12 12 16 14"></polyline></svg>
                                    <h3 style="margin: 0; font-size: 16px; font-family: var(--font-krub); font-weight: 700;">สถานะคำสั่งซื้อ</h3>
                                </div>

                                <div style="margin-bottom: 16px;">
                                    <span style="font-size: 13px; color: #666; display: block; margin-bottom: 4px;">สถานะปัจจุบัน:</span>
                                    <span class="order-badge <?= $detailBadge ?>" style="font-size: 15px; padding: 6px 16px; display: inline-block;">
                                        <?= e($detailOrder['status']) ?>
                                    </span>
                                </div>

                                <form method="POST" action="<?= base_url('admin.php?tab=order_detail&id=' . $detailOrder['id']) ?>" style="background: #fff; padding: 16px; border: 1px solid #e0e0e0; border-radius: 6px;">
                                    <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
                                    <input type="hidden" name="admin_action" value="update_order_status">
                                    <input type="hidden" name="order_id" value="<?= $detailOrder['id'] ?>">
                                    
                                    <label for="detailOrderStatus" style="font-weight: 600; font-size: 13px; display: block; margin-bottom: 8px; font-family: var(--font-krub);">
                                        อัปเดตสถานะคำสั่งซื้อ:
                                    </label>
                                    <div style="display: flex; gap: 8px; align-items: center;">
                                        <select name="status" id="detailOrderStatus" class="auth-input" style="flex: 1; padding: 8px 12px; font-size: 14px;">
                                            <option value="รอดำเนินการ" <?= $detailOrder['status'] === 'รอดำเนินการ' ? 'selected' : '' ?>>รอดำเนินการ</option>
                                            <option value="กำลังจัดส่ง" <?= $detailOrder['status'] === 'กำลังจัดส่ง' ? 'selected' : '' ?>>กำลังจัดส่ง</option>
                                            <option value="จัดส่งแล้ว" <?= $detailOrder['status'] === 'จัดส่งแล้ว' ? 'selected' : '' ?>>จัดส่งแล้ว</option>
                                            <option value="ยกเลิก" <?= $detailOrder['status'] === 'ยกเลิก' ? 'selected' : '' ?>>ยกเลิก</option>
                                        </select>
                                        <button type="submit" class="auth-btn" style="margin: 0; padding: 8px 18px; font-size: 14px; width: auto; white-space: nowrap;">
                                            บันทึก
                                        </button>
                                    </div>
                                </form>
                            </div>

                            <div style="font-size: 12px; color: #888; margin-top: 14px; font-family: var(--font-krub);">
                                * เมื่อเปลี่ยนสถานะ ระบบจะแสดงสถานะใหม่ให้ลูกค้าเห็นในหน้าโปรไฟล์ทันที
                            </div>
                        </div>
                    </div>

                    <!-- Card 3: Products Table -->
                    <div style="margin-bottom: 24px;">
                        <h3 style="font-size: 16px; margin: 0 0 12px 0; font-family: var(--font-krub); font-weight: 700;">
                            รายการสินค้าในคำสั่งซื้อ (<?= count($parsedItems) ?> รายการ)
                        </h3>
                        <div style="overflow-x: auto; border: 1px solid #eee; border-radius: 8px;">
                            <table class="product-table" style="margin: 0;">
                                <thead>
                                    <tr style="background-color: var(--color-gray-light);">
                                        <th style="width: 60px;">รูปภาพ</th>
                                        <th>ชื่อสินค้า</th>
                                        <th style="text-align: center;">ตัวเลือก (ขนาด / สี)</th>
                                        <th style="text-align: right;">ราคาต่อชิ้น</th>
                                        <th style="text-align: center;">จำนวน</th>
                                        <th style="text-align: right;">ยอดรวม</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($parsedItems as $it): ?>
                                        <?php 
                                        $itId = (int)($it['id'] ?? 0);
                                        $itQty = (int)($it['quantity'] ?? 1);
                                        $itPrice = (float)($it['price'] ?? 0);
                                        $itSubtotal = $itPrice * $itQty;
                                        ?>
                                        <tr>
                                            <td>
                                                <img src="<?= asset_url($it['image'] ?? 'assets/shirt.png') ?>" alt="" style="width: 48px; height: 48px; object-fit: cover; border-radius: 6px; border: 1px solid #eee;" />
                                            </td>
                                            <td>
                                                <a href="<?= base_url('product-detail.php?id=' . ($itId ?: 1)) ?>" target="_blank" style="font-weight: 600; font-size: 14px; color: var(--color-black); text-decoration: none;">
                                                    <?= e($it['name'] ?? 'สินค้า') ?>
                                                </a>
                                                <div style="font-size: 12px; color: #888;">Product ID: #<?= $itId ?></div>
                                            </td>
                                            <td style="text-align: center; font-size: 13px; color: #666;">
                                                <?php if (!empty($it['size']) || !empty($it['color'])): ?>
                                                    <span><?= e($it['size'] ?? '-') ?></span> / <span><?= e($it['color'] ?? '-') ?></span>
                                                <?php else: ?>
                                                    -
                                                <?php endif; ?>
                                            </td>
                                            <td style="text-align: right; font-size: 14px;"><?= format_price($itPrice) ?></td>
                                            <td style="text-align: center; font-weight: 600; font-size: 14px;"><?= $itQty ?></td>
                                            <td style="text-align: right; font-weight: 700; font-size: 14px; color: var(--color-black);"><?= format_price($itSubtotal) ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <!-- Card 4: Financial Totals -->
                    <div style="display: flex; justify-content: flex-end; margin-top: 16px;">
                        <div style="width: 100vw; background: #fafafa; border: 1px solid #e5e5e5; border-radius: 8px; padding: 18px; font-family: var(--font-krub); font-size: 14px;">
                            <div style="display: flex; justify-content: space-between; margin-bottom: 8px; color: #666;">
                                <span>ยอดรวมราคาสินค้า:</span>
                                <span style="font-weight: 600; color: #333;"><?= format_price($detailOrder['total']) ?></span>
                            </div>
                            <div style="display: flex; justify-content: space-between; margin-bottom: 8px; color: #666;">
                                <span>ค่าจัดส่ง:</span>
                                <span style="color: var(--color-success); font-weight: 600;">ฟรี (0 บาท)</span>
                            </div>
                            <div style="display: flex; justify-content: space-between; align-items: center; border-top: 2px solid #ddd; padding-top: 10px; margin-top: 6px;">
                                <span style="font-size: 16px; font-weight: 700;">ยอดสุทธิทั้งสิ้น:</span>
                                <span style="font-size: 22px; font-weight: 700; color: var(--color-black); font-family: var(--font-black-ops-one);">
                                    <?= format_price($detailOrder['total']) ?>
                                </span>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>

            <?php elseif ($tab === 'products'): ?>
                <!-- ======================================================= -->
                <!-- Tab 2: Products Management (10 per page + Search)       -->
                <!-- ======================================================= -->
                <div class="divider-head">
                    <div>
                        <h1 style="font-size: 22px; font-family: var(--font-krub); font-weight: bold;">จัดการสินค้า</h1>
                        <!-- <span style="font-size: 13px; color: #888;">
                            แสดงหน้า <?= $productPage ?> จากทั้งหมด <?= $totalProductPages ?> หน้า (พบ <?= $totalFilteredProducts ?> รายการ จากทั้งหมด <?= $totalProducts ?>)
                        </span> -->
                    </div>
                    <div style="display: flex; gap: 8px; align-items: center;">
                        <!-- <span class="order-badge badge-pending" style="font-size: 12px;">10 รายการ / หน้า</span> -->
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
                                <th>การดำเนินการ</th>
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
                                            <div style="display: flex;gap: 6px;flex-wrap: nowrap;flex-direction: column;align-items: stretch;">
                                                <a href="?tab=edit_product&id=<?= $p['id'] ?>" class="auth-btn" style="justify-content: center; margin: 0; padding: 4px 10px; font-size: 12px; width: auto; background-color: var(--color-black); text-decoration: none; display: inline-flex; align-items: center; gap: 4px;">
                                                    แก้ไข
                                                </a>
                                                <form method="POST" action="<?= base_url('admin.php?tab=products&product_page=' . $productPage . ($productSearch !== '' ? '&product_search=' . urlencode($productSearch) : '')) ?>" style="display: inline; margin: 0;">
                                                    <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
                                                    <input type="hidden" name="admin_action" value="toggle_product_status">
                                                    <input type="hidden" name="product_id" value="<?= $p['id'] ?>">
                                                    <input type="hidden" name="current_status" value="<?= e($p['status']) ?>">
                                                    <button type="submit" class="auth-btn" style="margin: 0; padding: 4px 10px; font-size: 12px; width: stretch; background-color: <?= $p['status'] === 'ขายแล้ว' ? 'var(--color-gray-dark)' : 'var(--color-error)' ?>;">
                                                        <?= $p['status'] === 'ขายแล้ว' ? 'ขายใหม่' : 'ขายแล้ว' ?>
                                                    </button>
                                                </form>
                                            </div>
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
                <!-- Tab 3: Add Product (Multi-Image & Gallery)              -->
                <!-- ======================================================= -->
                <div>
                    <div class="divider-head">
                        <div>
                            <h1 style="font-size: 22px; margin: 0 0 4px 0;font-family: var(--font-krub); font-weight: bold;">เพิ่มสินค้าใหม่</h1>
                            <!-- <span style="font-size: 13px; color: #888;">กรอกข้อมูลสินค้าและเลือกรูปภาพสินค้า</span> -->
                        </div>
                        <a href="?tab=products" class="login-btn" style="text-decoration: none;">ดูสินค้าทั้งหมด</a>
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
                            <div class="auth-field desktop-only" style="flex: 1;">
                                <label for="prodPrice">ราคา (บาท) *</label>
                                <input type="number" name="price" id="prodPrice" class="auth-input" placeholder="เช่น 35" min="1" step="1" required />
                            </div>
                        </div>

                        <div class="auth-field mobile-only" style="flex: 1;">
                            <label for="prodPrice">ราคา (บาท) *</label>
                            <input type="number" name="price" id="prodPrice" class="auth-input" placeholder="เช่น 35" min="1" step="1" required />
                        </div>

                        <div class="auth-field">
                            <label for="prodColor">สี</label>
                            <input type="text" name="color" id="prodColor" class="auth-input" placeholder="เช่น ดำ, ขาว, น้ำเงิน" value="ดำ" required />
                        </div>

                        <!-- Product Images (File upload only, square frame with +, reorderable) -->
                        <div class="auth-field">
                            <label style="font-weight: 700;">รูปภาพสินค้า (เลือกไฟล์รูปภาพเท่านั้น)</label>
                            <!-- <span style="font-size: 12px; color: #666; margin-bottom: 8px; display: block;">
                                คลิกที่กรอบ <b>+</b> เพื่อเลือกไฟล์รูปภาพ (สามารถเลือกได้หลายรูป, รูปแรกจะเป็นรูปหลัก, ลากหรือกดลูกศร ◀ ▶ เพื่อขยับสลับตำแหน่งได้)
                            </span> -->

                            <!-- Hidden container for ordered image sequence -->
                            <div id="sequenceInputsContainer"></div>

                            <!-- Hidden actual input for form submission -->
                            <input type="file" id="productImagesFinal" name="product_images[]" multiple accept="image/*" style="display: none;" />

                            <!-- Hidden input for file picker dialog -->
                            <input type="file" id="productImagesPicker" multiple accept="image/*" style="display: none;" onchange="handleProductFilesAdded(this.files); this.value='';" />

                            <!-- Image Grid & Square Add Button -->
                            <div class="img-upload-grid" id="productImagesGrid">
                                <div class="img-upload-square-btn" id="btnAddProductImage" onclick="document.getElementById('productImagesPicker').click()" title="คลิกเพื่อเลือกไฟล์รูปภาพ">
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                        <line x1="12" y1="5" x2="12" y2="19"></line>
                                        <line x1="5" y1="12" x2="19" y2="12"></line>
                                    </svg>
                                    <span>เพิ่มรูปภาพ</span>
                                </div>
                            </div>
                        </div>

                        <button type="submit" class="auth-btn">
                            บันทึกสินค้าใหม่
                        </button>
                    </form>
                </div>

            <?php elseif ($tab === 'add_order'): ?>
                <!-- ======================================================= -->
                <!-- Tab 4: Add Order (สร้างออร์เดอร์ใหม่)                     -->
                <!-- ======================================================= -->
                <div>
                    <div class="divider-head">
                        <div>
                            <h1 style="font-size: 22px; margin: 0 0 4px 0; font-family: var(--font-krub); font-weight: bold;">เพิ่มคำสั่งซื้อใหม่ (สร้างออร์เดอร์)</h1>
                            <!-- <span style="font-size: 13px; color: #888;">สร้างคำสั่งซื้อให้กับผู้ใช้งานในระบบ</span> -->
                        </div>
                        <a href="?tab=orders" class="login-btn" style="text-decoration: none;">ดูคำสั่งซื้อทั้งหมด</a>
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

                        <button type="submit" class="auth-btn">
                            สร้างคำสั่งซื้อนี้
                        </button>
                    </form>
                </div>

            <?php elseif ($tab === 'edit_product'): ?>
                <!-- ======================================================= -->
                <!-- Tab 5: Edit Product (แก้ไขสินค้า)                       -->
                <!-- ======================================================= -->
                <?php if (!$editProduct): ?>
                    <div style="text-align: center; padding: 40px;">
                        <h2 class="title" style="font-size: 20px; color: var(--color-error);">ไม่พบสินค้าที่ต้องการแก้ไข</h2>
                        <p style="color: #666; margin: 12px 0;">สินค้ารหัสนี้อาจถูกลบหรือไม่มีอยู่ในระบบ</p>
                        <a href="?tab=products" class="auth-btn" style="display: inline-block; width: auto; text-decoration: none;">
                            [←] กลับหน้ารายการสินค้า
                        </a>
                    </div>
                <?php else: ?>
                    <div>
                        <div class="divider-head">
                            <div>
                                <h1 style="font-size: 22px; margin: 0 0 4px 0; font-family: var(--font-krub); font-weight: bold;">แก้ไขสินค้า</h1>
                                <span style="font-size: 13px; color: #888;">รหัสสินค้า: P<?= str_pad((string)$editProduct['id'], 5, '0', STR_PAD_LEFT) ?></span>
                            </div>
                            <a href="?tab=products" class="login-btn" style="text-decoration: none;">กลับหน้ารายการสินค้า
                            </a>
                        </div>

                        <form method="POST" action="<?= base_url('admin.php?tab=products') ?>" class="auth-form" enctype="multipart/form-data">
                            <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
                            <input type="hidden" name="admin_action" value="edit_product">
                            <input type="hidden" name="product_id" value="<?= $editProduct['id'] ?>">

                            <div class="auth-field">
                                <label for="editProdName">ชื่อสินค้า *</label>
                                <input type="text" name="name" id="editProdName" class="auth-input" value="<?= e($editProduct['name']) ?>" required />
                            </div>

                            <div class="auth-field">
                                <label for="editProdType">หมวดหมู่ *</label>
                                <select name="type" id="editProdType" class="auth-input" required>
                                    <option value="shirt" <?= $editProduct['type'] === 'shirt' ? 'selected' : '' ?>>เสื้อ (Shirt)</option>
                                    <option value="pants" <?= $editProduct['type'] === 'pants' ? 'selected' : '' ?>>กางเกง (Pants)</option>
                                    <option value="skirt" <?= $editProduct['type'] === 'skirt' ? 'selected' : '' ?>>กระโปรง (Skirt)</option>
                                    <option value="cap" <?= $editProduct['type'] === 'cap' ? 'selected' : '' ?>>หมวก (Cap)</option>
                                </select>
                            </div>

                            <div style="display: flex; gap: 12px;">
                                <div class="auth-field" style="flex: 1;">
                                    <label for="editProdSize">ขนาด (Size)</label>
                                    <select name="size" id="editProdSize" class="auth-input" required>
                                        <option value="S" <?= $editProduct['size'] === 'S' ? 'selected' : '' ?>>S</option>
                                        <option value="M" <?= $editProduct['size'] === 'M' ? 'selected' : '' ?>>M</option>
                                        <option value="L" <?= $editProduct['size'] === 'L' ? 'selected' : '' ?>>L</option>
                                    </select>
                                </div>
                                <div class="auth-field" style="flex: 1;">
                                    <label for="editProdPrice">ราคา (บาท) *</label>
                                    <input type="number" name="price" id="editProdPrice" class="auth-input" value="<?= (float)$editProduct['price'] ?>" min="1" step="1" required />
                                </div>
                            </div>

                            <div class="auth-field">
                                <label for="editProdColor">สี</label>
                                <input type="text" name="color" id="editProdColor" class="auth-input" value="<?= e($editProduct['color']) ?>" required />
                            </div>

                            <div class="auth-field">
                                <label for="editProdStatus">สถานะสินค้า</label>
                                <select name="status" id="editProdStatus" class="auth-input">
                                    <option value="พร้อมส่ง" <?= $editProduct['status'] === 'พร้อมส่ง' ? 'selected' : '' ?>>พร้อมส่ง</option>
                                    <option value="ขายแล้ว" <?= $editProduct['status'] === 'ขายแล้ว' ? 'selected' : '' ?>>ขายแล้ว</option>
                                </select>
                            </div>

                            <!-- รูปภาพสินค้าทั้งหมด (รวมรูปเดิมและรูปใหม่ใน Grid เดียวกัน ขยับตำแหน่งได้ทั้งหมด) -->
                            <div class="auth-field">
                                <label style="font-weight: 700;">รูปภาพสินค้า (รูปหลัก & แกลเลอรี)</label>
                                <!-- <span style="font-size: 12px; color: #666; margin-bottom: 8px; display: block;">
                                    คลิกกรอบ <b>+</b> เพื่อเลือกไฟล์รูปภาพใหม่เพิ่ม, ลากหรือกดลูกศร ◀ ▶ เพื่อขยับสลับตำแหน่งได้ทุกรูป (รูปแรกสุดจะเป็นรูปหลัก) และกด ✕ เพื่อลบรูป
                                </span> -->

                                <?php 
                                $currentDisplayGallery = !empty($editGallery) ? array_column($editGallery, 'image_url') : (!empty($editProduct['image']) ? [$editProduct['image']] : []);
                                ?>
                                <script>
                                window.initialExistingImages = <?= json_encode(array_map(function($img) {
                                    return [
                                        'path' => $img,
                                        'display' => asset_url($img),
                                    ];
                                }, $currentDisplayGallery)) ?>;
                                </script>

                                <!-- Hidden container for ordered image sequence -->
                                <div id="sequenceInputsContainer"></div>

                                <!-- Hidden actual input for form submission -->
                                <input type="file" id="productImagesFinal" name="product_images[]" multiple accept="image/*" style="display: none;" />

                                <!-- Hidden input for file picker dialog -->
                                <input type="file" id="productImagesPicker" multiple accept="image/*" style="display: none;" onchange="handleProductFilesAdded(this.files); this.value='';" />

                                <!-- Unified Grid (Both existing & new images + Square Add Button in the same grid) -->
                                <div class="img-upload-grid" id="productImagesGrid">
                                    <div class="img-upload-square-btn" id="btnAddProductImage" onclick="document.getElementById('productImagesPicker').click()" title="คลิกเพื่อเลือกไฟล์รูปภาพเพิ่ม">
                                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                            <line x1="12" y1="5" x2="12" y2="19"></line>
                                            <line x1="5" y1="12" x2="19" y2="12"></line>
                                        </svg>
                                        <span>เพิ่มรูปภาพ</span>
                                    </div>
                                </div>
                            </div>

                            <div style="display: flex; gap: 12px; margin-top: 24px;">
                                <button type="submit" class="auth-btn" style="flex: 1;">
                                    บันทึกการแก้ไขสินค้า
                                </button>
                                <a href="?tab=products" class="auth-btn" style="flex: 0.5; background-color: var(--color-gray-dark); text-align: center; text-decoration: none;">
                                    ยกเลิก
                                </a>
                            </div>
                        </form>
                    </div>
                <?php endif; ?>
            <?php endif; ?>
        </div>
    </main>
</div>

<?php if (!in_array($tab, ['order_detail'], true)): ?>
<!-- Mobile Bottom Tab Navigation Bar (เมนูแท็บด้านล่างขอบจอสำหรับมือถือ) -->
<div class="admin-mobile-tab-bar" role="navigation">
    <a href="?tab=orders" class="admin-mobile-tab-item <?= in_array($tab, ['orders', 'order_detail'], true) ? 'active' : '' ?>">
        <img src="<?= asset_url('public/box.svg') ?>" alt="orders" style="width: 20px; height: 20px;" />
        <span>คำสั่งซื้อ</span>
    </a>
    <a href="?tab=products" class="admin-mobile-tab-item <?= in_array($tab, ['products', 'edit_product'], true) ? 'active' : '' ?>">
        <img src="<?= asset_url('public/sort.svg') ?>" alt="products" style="width: 20px; height: 20px;" />
        <span>จัดการสินค้า</span>
    </a>
    <a href="?tab=add_product" class="admin-mobile-tab-item <?= $tab === 'add_product' ? 'active' : '' ?>">
        <img src="<?= asset_url('public/add-cart.svg') ?>" alt="add" style="width: 20px; height: 20px;" />
        <span>เพิ่มสินค้า</span>
    </a>
    <a href="?tab=add_order" class="admin-mobile-tab-item <?= $tab === 'add_order' ? 'active' : '' ?>">
        <img src="<?= asset_url('public/cart.svg') ?>" alt="add-order" style="width: 20px; height: 20px;" />
        <span>เพิ่มออร์เดอร์</span>
    </a>
</div>
<?php endif; ?>

<script>
let allImageItems = [];
let dragSrcIndex = null;
let activeBlobUrls = [];

// Initialize existing images on page load (e.g. on edit product tab)
document.addEventListener('DOMContentLoaded', function() {
    if (window.initialExistingImages && Array.isArray(window.initialExistingImages)) {
        allImageItems = window.initialExistingImages.map(function(item) {
            return {
                type: 'existing',
                path: item.path,
                displayUrl: item.display
            };
        });
        renderProductImagePreviews();
    }
});

function handleProductFilesAdded(files) {
    if (!files || files.length === 0) return;
    for (let i = 0; i < files.length; i++) {
        const file = files[i];
        const blobUrl = URL.createObjectURL(file);
        activeBlobUrls.push(blobUrl);
        allImageItems.push({
            type: 'new',
            file: file,
            displayUrl: blobUrl
        });
    }
    renderProductImagePreviews();
}

function removeProductFile(index) {
    allImageItems.splice(index, 1);
    renderProductImagePreviews();
}

function moveProductFile(index, direction) {
    const target = index + direction;
    if (target < 0 || target >= allImageItems.length) return;
    const temp = allImageItems[index];
    allImageItems[index] = allImageItems[target];
    allImageItems[target] = temp;
    renderProductImagePreviews();
}

function syncFinalFilesAndSequence() {
    const finalInput = document.getElementById('productImagesFinal');
    const seqContainer = document.getElementById('sequenceInputsContainer');
    
    // 1. Sync new File objects to productImagesFinal using DataTransfer
    if (finalInput) {
        try {
            const dt = new DataTransfer();
            allImageItems.forEach(item => {
                if (item.type === 'new' && item.file) {
                    dt.items.add(item.file);
                }
            });
            finalInput.files = dt.files;
        } catch (err) {
            console.warn('DataTransfer sync warning:', err);
        }
    }

    // 2. Build image_sequence[] hidden inputs to preserve unified order
    if (seqContainer) {
        seqContainer.innerHTML = '';
        let newFileCounter = 0;
        allImageItems.forEach(item => {
            const input = document.createElement('input');
            input.type = 'hidden';
            input.name = 'image_sequence[]';
            if (item.type === 'existing') {
                input.value = 'existing:' + item.path;
            } else if (item.type === 'new') {
                input.value = 'new:' + (newFileCounter++);
            }
            seqContainer.appendChild(input);
        });
    }
}

function renderProductImagePreviews() {
    const grid = document.getElementById('productImagesGrid');
    const addBtn = document.getElementById('btnAddProductImage');
    if (!grid || !addBtn) return;

    grid.querySelectorAll('.img-preview-card').forEach(el => el.remove());

    allImageItems.forEach((item, idx) => {
        const card = document.createElement('div');
        card.className = 'img-preview-card';
        card.draggable = true;
        card.dataset.index = idx;
        card.title = "ลากเพื่อสลับตำแหน่ง หรือกดลูกศร (รูปที่ " + (idx + 1) + (idx === 0 ? " - รูปหลัก" : "") + ")";

        const isMain = idx === 0;
        const mainBadgeHtml = isMain ? '<span class="img-card-main-badge">รูปหลัก</span>' : '';
        const prevDisabled = idx === 0 ? 'disabled' : '';
        const nextDisabled = idx === allImageItems.length - 1 ? 'disabled' : '';

        card.innerHTML = `
            ${mainBadgeHtml}
            <button type="button" class="img-card-delete-btn" title="ลบรูปนี้" onclick="removeProductFile(${idx})">&#10005;</button>
            <img src="${item.displayUrl}" alt="preview ${idx}" />
            <div class="img-card-actions">
                <button type="button" class="img-card-btn-move" title="ขยับไปทางซ้าย" ${prevDisabled} onclick="moveProductFile(${idx}, -1)">&#9664;</button>
                <button type="button" class="img-card-btn-move" title="ขยับไปทางขวา" ${nextDisabled} onclick="moveProductFile(${idx}, 1)">&#9654;</button>
            </div>
        `;

        card.addEventListener('dragstart', (e) => {
            dragSrcIndex = idx;
            e.dataTransfer.effectAllowed = 'move';
            card.classList.add('dragging');
        });

        card.addEventListener('dragend', () => {
            card.classList.remove('dragging');
            grid.querySelectorAll('.img-preview-card').forEach(c => c.classList.remove('drag-over'));
            dragSrcIndex = null;
        });

        card.addEventListener('dragover', (e) => {
            e.preventDefault();
            e.dataTransfer.dropEffect = 'move';
            card.classList.add('drag-over');
        });

        card.addEventListener('dragleave', () => {
            card.classList.remove('drag-over');
        });

        card.addEventListener('drop', (e) => {
            e.preventDefault();
            card.classList.remove('drag-over');
            const targetIndex = idx;
            if (dragSrcIndex !== null && dragSrcIndex !== targetIndex) {
                const movedItem = allImageItems.splice(dragSrcIndex, 1)[0];
                allImageItems.splice(targetIndex, 0, movedItem);
                renderProductImagePreviews();
            }
        });

        grid.insertBefore(card, addBtn);
    });

    syncFinalFilesAndSequence();
}
</script>

<?php include __DIR__ . '/includes/footer.php'; ?>
