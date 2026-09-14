<?php
/**
 * Order Confirmation & Checkout Page
 */
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/data/products_data.php';
require_once __DIR__ . '/data/order_data.php';
require_once __DIR__ . '/data/user_data.php';
require_once __DIR__ . '/data/db.php';

require_login();

$currentUser = current_user();
$cart = get_cart();
$cartTotal = get_cart_total();
$error = '';

// Check if cart is empty
if (empty($cart)) {
    header("Location: " . base_url('cart.php'));
    exit;
}

$userAddress = trim($currentUser['address'] ?? '');
$hasAddress = !empty($userAddress);

// Handle Order Confirmation POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
        $error = 'โทเค็นความปลอดภัยไม่ถูกต้อง กรุณาลองใหม่อีกครั้ง';
    } else {
        // If user submitted an address inline
        $submittedAddress = trim($_POST['inline_address'] ?? '');
        if (!empty($submittedAddress)) {
            update_user_address((int)$currentUser['id'], $submittedAddress);
            $userAddress = $submittedAddress;
            $currentUser['address'] = $submittedAddress;
            $hasAddress = true;
        }

        if (empty($userAddress)) {
            $error = 'กรุณาระบุที่อยู่สำหรับจัดส่งสินค้าก่อนทำการยืนยันคำสั่งซื้อ';
        } else {
            $pdo = get_db_connection();
            if ($pdo && is_db_initialized($pdo)) {
                $items = array_values($cart);
                $itemsJson = json_encode($items, JSON_UNESCAPED_UNICODE);
                $firstItem = $items[0] ?? null;
                $now = date('Y-m-d H:i:s');

                $stmt = $pdo->prepare("
                    INSERT INTO orders (user_id, items, total, status, created_at, address, product_id, quantity, price)
                    VALUES (?, ?, ?, 'รอดำเนินการ', ?, ?, ?, ?, ?)
                ");
                $stmt->execute([
                    $currentUser['id'],
                    $itemsJson,
                    $cartTotal,
                    $now,
                    $userAddress,
                    $firstItem['id'] ?? null,
                    count($items),
                    $cartTotal
                ]);

                clear_cart();
                header("Location: " . base_url('profile.php?tab=orders&ordered=1'));
                exit;
            } else {
                $error = 'ระบบฐานข้อมูลขัดข้อง กรุณาลองใหม่อีกครั้ง';
            }
        }
    }
}

$page_title = 'KKC - ยืนยันคำสั่งซื้อ';
$show_back_arrow = true;
$back_url = base_url('cart.php');

include __DIR__ . '/includes/header.php';

$breadcrumbs = [
    ['title' => 'Cart', 'url' => 'cart.php'],
    ['title' => 'ยืนยันคำสั่งซื้อ']
];
include __DIR__ . '/includes/breadcrumb.php';
?>

<main class="page-wrapper">
    <div class="page-container-narrow">
        <div class="card">
            <div class="card-header" style="display: flex; justify-content: space-between; align-items: center; border-bottom: 1px solid var(--color-gray-light); padding-bottom: 16px;">
                <div>
                    <h1 class="card-title" style="font-size: 24px; margin: 0;">ยืนยันคำสั่งซื้อ</h1>
                    <span style="color: #666; font-size: 14px;">กรุณาตรวจสอบที่อยู่และรายการสินค้าก่อนทำการสั่งซื้อ</span>
                </div>
                <span class="badge badge-pending" style="font-size: 13px;">สินค้ารวม <?= count($cart) ?> รายการ</span>
            </div>

            <div class="card-body" style="padding-top: 20px;">
                <?php if (!empty($error)): ?>
                    <div class="form-error" style="display: block; margin-bottom: 20px; font-size: 14px;"><?= e($error) ?></div>
                <?php endif; ?>

                <!-- 1. ที่อยู่จัดส่ง (Delivery Address Section) -->
                <div style="margin-bottom: 28px; border: 1px solid #e5e5e5; border-radius: 8px; padding: 20px; background-color: #fafafa;">
                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 12px; flex-wrap: wrap; gap: 8px;">
                        <div style="display: flex; align-items: center; gap: 8px;">
                            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="color: var(--color-primary);"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"></path><circle cx="12" cy="10" r="3"></circle></svg>
                            <h3 style="font-size: 16px; margin: 0; font-family: var(--font-krub); font-weight: 700;">ที่อยู่สำหรับจัดส่งสินค้า</h3>
                        </div>

                        <?php if ($hasAddress): ?>
                            <a href="<?= base_url('profile.php?tab=address&redirect=' . urlencode(base_url('checkout.php'))) ?>" class="btn btn-outline btn-sm" style="text-decoration: none;">
                                เปลี่ยน / แก้ไขที่อยู่
                            </a>
                        <?php endif; ?>
                    </div>

                    <?php if ($hasAddress): ?>
                        <div style="background: #fff; border: 1px solid #e0e0e0; border-radius: 6px; padding: 14px 16px;">
                            <div style="font-weight: 600; font-size: 15px; margin-bottom: 4px; font-family: var(--font-krub);">
                                ผู้รับ: <?= e($currentUser['name'] ?: $currentUser['username']) ?>
                                <?php if (!empty($currentUser['phone'])): ?>
                                    <span style="color: #666; font-weight: 400; margin-left: 10px;">(เบอร์โทรศัพท์: <?= e($currentUser['phone']) ?>)</span>
                                <?php endif; ?>
                            </div>
                            <div style="font-size: 14px; color: #444; line-height: 1.6; font-family: var(--font-krub);">
                                <?= nl2br(e($userAddress)) ?>
                            </div>
                        </div>
                    <?php else: ?>
                        <!-- Notice when user does not have an address yet -->
                        <div style="border: 1px solid #e5e5e5; border-radius: 6px; padding: 16px; font-family: var(--font-krub); background: #fff;">
                            <div style="font-weight: 700; font-size: 15px; margin-bottom: 6px;">
                                [!] คุณยังไม่ได้เพิ่มที่อยู่สำหรับจัดส่งสินค้า
                            </div>
                            <p style="font-size: 13px; margin: 0 0 14px 0; color: #666;">
                                กรุณาเพิ่มที่อยู่จัดส่งเพื่อให้ทางร้านดำเนินการจัดส่งพัสดุได้อย่างถูกต้อง
                            </p>

                            <div style="display: flex; gap: 12px; align-items: center; flex-wrap: wrap;">
                                <a href="<?= base_url('profile.php?tab=address&redirect=' . urlencode(base_url('checkout.php'))) ?>" class="btn btn-primary btn-sm" style="text-decoration: none; display: inline-flex; align-items: center; gap: 6px;">
                                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 5v14M5 12h14"></path></svg>
                                    เพิ่มที่อยู่จัดส่ง
                                </a>
                                <button type="button" onclick="document.getElementById('quickAddressBox').style.display = document.getElementById('quickAddressBox').style.display === 'none' ? 'block' : 'none';" class="btn btn-outline btn-sm">
                                    หรือกรอกที่อยู่ตรงนี้ด่วน
                                </button>
                            </div>

                            <!-- Quick Inline Address Form -->
                            <div id="quickAddressBox" style="display: none; margin-top: 14px; padding-top: 14px; border-top: 1px dashed #e0d0a0;">
                                <label for="quickInlineAddress" class="form-label" style="font-size: 13px; font-weight: 600; display: block; margin-bottom: 6px;">กรอกที่อยู่จัดส่งตรงนี้:</label>
                                <textarea id="quickInlineAddress" form="confirmOrderForm" name="inline_address" class="form-input" rows="3" placeholder="บ้านเลขที่, ถนน, แขวง/ตำบล, เขต/อำเภอ, จังหวัด, รหัสไปรษณีย์" style="background: #fff; font-size: 13px; resize: vertical; line-height: 1.5;"></textarea>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>

                <!-- 2. รายการสินค้าในออร์เดอร์ (Order Items Section) -->
                <div style="margin-bottom: 28px;">
                    <h3 style="font-size: 16px; margin: 0 0 14px 0; font-family: var(--font-krub); font-weight: 700;">
                        รายการสินค้าในคำสั่งซื้อ (<?= count($cart) ?> รายการ)
                    </h3>
                    <div style="border: 1px solid #eee; border-radius: 8px; overflow: hidden;">
                        <?php foreach ($cart as $item): ?>
                            <div style="display: flex; align-items: center; justify-content: space-between; padding: 14px 16px; border-bottom: 1px solid #eee; background: #fff; flex-wrap: wrap; gap: 12px;">
                                <div style="display: flex; align-items: center; gap: 14px; flex: 1; min-width: 240px;">
                                    <img src="<?= asset_url($item['image']) ?>" alt="<?= e($item['name']) ?>" style="width: 56px; height: 56px; object-fit: cover; border-radius: 6px; border: 1px solid #eee;" />
                                    <div>
                                        <a href="<?= base_url('product-detail.php?id=' . $item['id']) ?>" target="_blank" style="font-weight: 600; font-size: 15px; color: var(--color-black); text-decoration: none; font-family: var(--font-krub);">
                                            <?= e($item['name']) ?>
                                        </a>
                                        <div style="font-size: 12px; color: #777; margin-top: 3px;">
                                            <span>ขนาด: <?= e($item['size'] ?? 'M') ?></span> | <span>สี: <?= e($item['color'] ?? '-') ?></span>
                                        </div>
                                        <div style="font-size: 13px; color: #555; margin-top: 2px;">
                                            ราคาชิ้นละ <?= format_price($item['price']) ?> x <?= $item['quantity'] ?> ชิ้น
                                        </div>
                                    </div>
                                </div>

                                <div style="font-weight: 700; font-size: 15px; color: var(--color-black); font-family: var(--font-krub); min-width: 90px; text-align: right;">
                                    <?= format_price($item['price'] * $item['quantity']) ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>

                <!-- 3. สรุปยอดเงินคำสั่งซื้อ (Summary Section) -->
                <div style="border-top: 2px solid var(--color-gray-light); padding-top: 20px; margin-top: 20px;">
                    <div style="display: flex;flex-direction: column;gap: 8px;font-family: var(--font-krub);font-size: 14px;padding: 12px;">
                        <div style="display: flex; justify-content: space-between; color: #666;">
                            <span>ยอดรวมสินค้า:</span>
                            <span style="font-weight: 600; color: #333;"><?= format_price($cartTotal) ?></span>
                        </div>
                        <div style="display: flex; justify-content: space-between; color: #666;">
                            <span>ค่าจัดส่ง:</span>
                            <span style="color: var(--color-success); font-weight: 600;">ฟรี (0 บาท)</span>
                        </div>
                        <div style="display: flex; justify-content: space-between; align-items: center; border-top: 1px solid #eee; padding-top: 10px; margin-top: 4px;">
                            <span style="font-size: 16px; font-weight: 700;">ยอดรวมที่ต้องชำระ:</span>
                            <span style="font-size: 22px; font-weight: 700; color: var(--color-black); font-family: var(--font-black-ops-one);">
                                <?= format_price($cartTotal) ?>
                            </span>
                        </div>
                    </div>

                    <!-- Action Buttons (Desktop) -->
                    <div class="checkout-actions-desktop" style="display: flex; justify-content: space-between; align-items: center; margin-top: 28px; flex-wrap: wrap; gap: 12px;">
                        <a href="<?= base_url('cart.php') ?>" class="btn btn-outline" style="text-decoration: none;">
                            ← กลับไปยังตะกร้าสินค้า
                        </a>

                        <form id="confirmOrderForm" method="POST" action="<?= base_url('checkout.php') ?>">
                            <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
                            <button type="submit" class="btn btn-primary" style="padding: 12px 32px; font-size: 16px;" onclick="
                                const hasAddr = <?= $hasAddress ? 'true' : 'false' ?>;
                                const inlineAddr = (document.getElementById('quickInlineAddress') ? document.getElementById('quickInlineAddress').value.trim() : '');
                                if (!hasAddr && !inlineAddr) {
                                    event.preventDefault();
                                    showAppAlert('กรุณาเพิ่มที่อยู่สำหรับจัดส่งสินค้าก่อนทำการยืนยันคำสั่งซื้อ', 'ยังไม่มีที่อยู่จัดส่ง').then(() => {
                                        const box = document.getElementById('quickAddressBox');
                                        if (box) { box.style.display = 'block'; document.getElementById('quickInlineAddress').focus(); }
                                    });
                                }
                            ">
                                ยืนยันการสั่งซื้อ
                            </button>
                        </form>
                    </div>

                    <!-- Mobile Back to Cart Button -->
                    <div class="mobile-only" style="margin-top: 20px;">
                        <a href="<?= base_url('cart.php') ?>" class="btn btn-outline" style="width: 100%; text-decoration: none; justify-content: center; font-size: 13px;">
                            ← กลับไปยังตะกร้าสินค้า
                        </a>
                    </div>
                </div>

            </div>
        </div>
    </div>
</main>

<!-- Mobile Bottom Action Bar (แทนที่ mobile-footer-tab-bar สำหรับหน้า Checkout) -->
<div class="checkout-mobile-bottom-bar" role="region" aria-label="Checkout Action Bar">
    <div class="checkout-mobile-price-group">
        <span class="checkout-mobile-price-label">ยอดที่ต้องชำระ</span>
        <span class="checkout-mobile-price-val"><?= format_price($cartTotal) ?></span>
    </div>
    <div class="checkout-mobile-actions">
        <button type="submit" form="confirmOrderForm" class="btn btn-primary checkout-mobile-submit-btn" onclick="
            const hasAddr = <?= $hasAddress ? 'true' : 'false' ?>;
            const inlineAddr = (document.getElementById('quickInlineAddress') ? document.getElementById('quickInlineAddress').value.trim() : '');
            if (!hasAddr && !inlineAddr) {
                event.preventDefault();
                showAppAlert('กรุณาเพิ่มที่อยู่สำหรับจัดส่งสินค้าก่อนทำการยืนยันคำสั่งซื้อ', 'ยังไม่มีที่อยู่จัดส่ง').then(() => {
                    const box = document.getElementById('quickAddressBox');
                    if (box) { box.style.display = 'block'; document.getElementById('quickInlineAddress').focus(); }
                });
            }
        ">
            ยืนยันการสั่งซื้อ
        </button>
    </div>
</div>

<?php include __DIR__ . '/includes/footer.php'; ?>
