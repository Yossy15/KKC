<?php
/**
 * Shopping Cart & Checkout Page
 */
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/data/products_data.php';
require_once __DIR__ . '/data/order_data.php';
require_once __DIR__ . '/data/db.php';

$action = $_GET['action'] ?? $_POST['action'] ?? '';
$error = '';
$success = '';

// Handle Cart Actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
        $error = 'โทเค็นความปลอดภัยไม่ถูกต้อง กรุณาลองใหม่อีกครั้ง';
    } elseif ($action === 'add') {
        $prodId = (int)($_POST['product_id'] ?? 0);
        $qty = max(1, (int)($_POST['quantity'] ?? 1));
        if (add_to_cart($prodId, $qty)) {
            if (isset($_GET['ajax']) || isset($_POST['ajax'])) {
                header('Content-Type: application/json');
                echo json_encode(['success' => true, 'cart_count' => get_cart_count()]);
                exit;
            }
            header("Location: " . base_url('cart.php'));
            exit;
        } else {
            if (isset($_GET['ajax']) || isset($_POST['ajax'])) {
                header('Content-Type: application/json');
                echo json_encode(['success' => false, 'error' => 'ไม่สามารถเพิ่มสินค้านี้ลงตะกร้าได้']);
                exit;
            }
            $error = 'ไม่สามารถเพิ่มสินค้านี้ลงตะกร้าได้ (สินค้าอาจหมดหรือไม่มีจำหน่าย)';
        }
    } elseif ($action === 'update') {
        $prodId = (int)($_POST['product_id'] ?? 0);
        $qty = (int)($_POST['quantity'] ?? 1);
        update_cart_quantity($prodId, $qty);
        header("Location: " . base_url('cart.php'));
        exit;
    } elseif ($action === 'remove') {
        $prodId = (int)($_POST['product_id'] ?? 0);
        remove_from_cart($prodId);
        header("Location: " . base_url('cart.php'));
        exit;
    } elseif ($action === 'checkout') {
        if (!is_logged_in()) {
            header("Location: " . base_url('login.php?redirect=' . urlencode(base_url('cart.php'))));
            exit;
        }

        $cart = get_cart();
        if (empty($cart)) {
            $error = 'ตะกร้าสินค้าว่างเปล่า ไม่สามารถสั่งซื้อได้';
        } else {
            $currentUser = current_user();
            $pdo = get_db_connection();

            if ($pdo && is_db_initialized($pdo)) {
                $stmt = $pdo->prepare("
                    INSERT INTO orders (user_id, product_id, quantity, price, total, status, created_at)
                    VALUES (?, ?, ?, ?, ?, 'รอดำเนินการ', ?)
                ");
                $now = date('Y-m-d H:i:s');
                $pdo->beginTransaction();
                foreach ($cart as $item) {
                    $itemTotal = $item['price'] * $item['quantity'];
                    $stmt->execute([
                        $currentUser['id'],
                        $item['id'],
                        $item['quantity'],
                        $item['price'],
                        $itemTotal,
                        $now
                    ]);
                }
                $pdo->commit();
            }

            clear_cart();
            header("Location: " . base_url('profile.php?view=orders&ordered=1'));
            exit;
        }
    }
}

$cart = get_cart();
$cartTotal = get_cart_total();
$page_title = 'KKC - ตะกร้าสินค้า';
$show_back_arrow = true;
$back_url = base_url('products.php');

include __DIR__ . '/includes/header.php';

$breadcrumbs = [
    ['title' => 'Products', 'url' => 'products.php'],
    ['title' => 'Cart']
];
include __DIR__ . '/includes/breadcrumb.php';
?>

<main class="profile-wrapper" style="width: 100%; max-width: 100%; margin: 0; padding: 20px; min-height: calc(100vh - 70px); background-color: var(--color-gray-light);">
    <div style="background-color: var(--color-white); border-radius: 8px; padding: 32px; box-shadow: 0 1px 3px rgba(0,0,0,0.06); width: 100%; min-height: calc(100vh - 110px);">
        <div class="divider-head" style="margin-bottom: 24px;">
            <h1 class="title" style="font-size: 24px;">ตะกร้าสินค้าของคุณ</h1>
            <span style="color: #666; font-size: 14px;"><?= get_cart_count() ?> ชิ้น</span>
        </div>

        <?php if (!empty($error)): ?>
            <div class="auth-error" style="display: block; margin-bottom: 16px;"><?= e($error) ?></div>
        <?php endif; ?>

        <?php if (empty($cart)): ?>
            <div style="text-align: center; padding: 60px 20px;">
                <img src="<?= asset_url('public/cart.svg') ?>" alt="empty cart" style="width: 64px; height: 64px; opacity: 0.3; margin-bottom: 16px;" />
                <h3 style="margin-bottom: 8px;">ยังไม่มีสินค้าในตะกร้า</h3>
                <p style="color: #888; font-size: 14px; margin-bottom: 24px;">เลือกดูสินค้าที่ถูกใจและสั่งซื้อได้เลย</p>
                <a href="<?= base_url('products.php') ?>" class="auth-btn" style="max-width: 220px; text-decoration: none; display: inline-block;">
                    เลือกซื้อสินค้า
                </a>
            </div>
        <?php else: ?>
            <div style="display: flex; flex-direction: column; gap: 16px;">
                <?php foreach ($cart as $item): ?>
                    <div style="display: flex; align-items: center; justify-content: space-between; border-bottom: 1px solid #eee; padding-bottom: 16px; flex-wrap: wrap; gap: 12px;">
                        <div style="display: flex; align-items: center; gap: 16px; flex: 1; min-width: 200px;">
                            <img src="<?= asset_url($item['image']) ?>" alt="<?= e($item['name']) ?>" style="width: 70px; height: 70px; object-fit: cover; border-radius: 6px; border: 1px solid #eee;" />
                            <div>
                                <a href="<?= base_url('product-detail.php?id=' . $item['id']) ?>" style="font-weight: 600; font-size: 16px; color: var(--color-black); font-family: var(--font-krub);">
                                    <?= e($item['name']) ?>
                                </a>
                                <div style="font-size: 13px; color: #666; margin-top: 4px;">
                                    <span>ขนาด: <?= e($item['size']) ?></span> | <span>สี: <?= e($item['color']) ?></span>
                                </div>
                                <div style="font-weight: 600; font-size: 14px; color: var(--color-gray-dark); margin-top: 4px;">
                                    <?= format_price($item['price']) ?>
                                </div>
                            </div>
                        </div>

                        <!-- Quantity & Actions -->
                        <div style="display: flex; align-items: center; gap: 16px;">
                            <form method="POST" action="<?= base_url('cart.php') ?>" style="display: flex; align-items: center; gap: 6px;">
                                <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
                                <input type="hidden" name="action" value="update">
                                <input type="hidden" name="product_id" value="<?= $item['id'] ?>">
                                <button type="submit" name="quantity" value="<?= $item['quantity'] - 1 ?>" style="width: 28px; height: 28px; border: 1px solid #ccc; background: #fff; cursor: pointer; border-radius: 4px;">-</button>
                                <span style="min-width: 24px; text-align: center; font-weight: 600; font-size: 14px;"><?= $item['quantity'] ?></span>
                                <button type="submit" name="quantity" value="<?= $item['quantity'] + 1 ?>" style="width: 28px; height: 28px; border: 1px solid #ccc; background: #fff; cursor: pointer; border-radius: 4px;">+</button>
                            </form>

                            <div style="min-width: 90px; text-align: right; font-weight: 700; font-size: 16px; font-family: var(--font-krub);">
                                <?= format_price($item['price'] * $item['quantity']) ?>
                            </div>

                            <form method="POST" action="<?= base_url('cart.php') ?>">
                                <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
                                <input type="hidden" name="action" value="remove">
                                <input type="hidden" name="product_id" value="<?= $item['id'] ?>">
                                <button type="submit" onclick="event.preventDefault(); const f = this.form; showAppConfirm('คุณต้องการลบสินค้านี้ออกจากตะกร้าหรือไม่?', 'ยืนยันการลบสินค้า').then(ok => { if (ok) f.submit(); });" style="background: none; border: none; cursor: pointer; padding: 4px;" title="ลบสินค้านี้">
                                    <img src="<?= asset_url('public/close.svg') ?>" alt="remove" style="width: 16px; height: 16px; opacity: 0.5;" />
                                </button>
                            </form>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>

            <!-- Summary & Checkout Section -->
            <div style="margin-top: 24px; padding-top: 16px; border-top: 2px solid var(--color-gray-light); display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 16px;">
                <div>
                    <span style="font-size: 14px; color: #666;">ยอดรวมทั้งสิ้น</span>
                    <div style="font-size: 24px; font-weight: 700; color: var(--color-black); font-family: var(--font-black-ops-one);">
                        <?= format_price($cartTotal) ?>
                    </div>
                </div>

                <div style="display: flex; gap: 12px;">
                    <a href="<?= base_url('products.php') ?>" class="page-number-button" style="text-decoration: none; padding: 10px 16px; height: auto;">
                        เลือกซื้อต่อ
                    </a>

                    <form method="POST" action="<?= base_url('cart.php') ?>">
                        <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
                        <input type="hidden" name="action" value="checkout">
                        <button type="submit" class="auth-btn" style="margin: 0; padding: 10px 24px; font-size: 16px;">
                            สั่งซื้อสินค้าทันที
                        </button>
                    </form>
                </div>
            </div>
        <?php endif; ?>
    </div>
</main>

<?php include __DIR__ . '/includes/footer.php'; ?>
