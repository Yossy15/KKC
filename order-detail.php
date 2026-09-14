<?php
/**
 * Customer Order Detail Page
 * Opens in new window/tab to show all items in an order
 */
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/data/db.php';
require_once __DIR__ . '/data/order_data.php';
require_once __DIR__ . '/includes/thai_date.php';

require_login();
$currentUser = current_user();

$orderId = (int)($_GET['id'] ?? 0);
$order = $orderId > 0 ? get_order_by_id($orderId) : null;

$isAllowed = $order && ((int)$order['userId'] === (int)$currentUser['id'] || is_admin());

$page_title = $order ? 'KKC - คำสั่งซื้อ #ORD-' . str_pad((string)$order['id'], 5, '0', STR_PAD_LEFT) : 'KKC - ไม่พบคำสั่งซื้อ';
$show_back_arrow = true;
$back_url = base_url('profile.php?tab=orders');

$badgeClassMap = [
    'รอดำเนินการ' => 'badge-pending',
    'กำลังจัดส่ง' => 'badge-shipping',
    'จัดส่งแล้ว'  => 'badge-delivered',
    'ยกเลิก'     => 'badge-cancelled',
];

include __DIR__ . '/includes/header.php';

$breadcrumbs = [
    ['title' => 'หน้าแรก', 'url' => 'index.php'],
    ['title' => 'โปรไฟล์ของฉัน', 'url' => 'profile.php?tab=orders'],
    ['title' => $order ? 'คำสั่งซื้อ #' . str_pad((string)$order['id'], 5, '0', STR_PAD_LEFT) : 'รายละเอียดคำสั่งซื้อ']
];
include __DIR__ . '/includes/breadcrumb.php';
?>

<main class="page-wrapper">
    <div class="page-container-narrow">
    <?php if (!$isAllowed): ?>
        <div class="card" style="text-align: center; padding: 60px 20px;">
            <svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="color: var(--color-error); margin-bottom: 16px;"><circle cx="12" cy="12" r="10"></circle><line x1="12" y1="8" x2="12" y2="12"></line><line x1="12" y1="16" x2="12.01" y2="16"></line></svg>
            <h1 class="card-title" style="font-size: 22px; margin-bottom: 8px;">ไม่พบคำสั่งซื้อ</h1>
            <p style="color: #666; font-family: var(--font-krub); margin-bottom: 24px;">ไม่พบข้อมูลคำสั่งซื้อที่ระบุ หรือคุณไม่มีสิทธิ์เข้าถึงคำสั่งซื้อนี้</p>
            <a href="<?= base_url('profile.php?tab=orders') ?>" class="btn btn-primary" style="display: inline-block; width: auto; padding: 10px 24px; text-decoration: none;">
                ← กลับไปหน้ารายการคำสั่งซื้อ
            </a>
        </div>
    <?php else: ?>
        <?php 
        $badge = $badgeClassMap[$order['status']] ?? 'badge-pending';
        $items = $order['items'] ?? [];
        $totalQty = array_sum(array_column($items, 'quantity'));
        ?>

        <!-- Header Bar -->
        <div style="display: flex; flex-wrap: wrap; justify-content: space-between; align-items: center; gap: 16px; margin-bottom: 24px; padding-bottom: 16px; border-bottom: 2px solid var(--color-gray-light);">
            <div>
                <div style="display: flex; align-items: center; gap: 12px; margin-bottom: 4px;">
                    <h1 class="card-title" style="font-size: 24px; margin: 0;">
                        คำสั่งซื้อ #ORD-<?= str_pad((string)$order['id'], 5, '0', STR_PAD_LEFT) ?>
                    </h1>
                    <span class="badge <?= $badge ?>" style="font-size: 14px; padding: 4px 14px;">
                        <?= e($order['status']) ?>
                    </span>
                </div>
                <div style="font-size: 13px; color: #777; font-family: var(--font-krub);">
                    สั่งซื้อเมื่อ: <?= thaiDateTime($order['createdAt']) ?>
                </div>
            </div>

            <div style="display: flex; gap: 10px;">
                <button type="button" onclick="window.print()" class="btn btn-outline btn-sm" style="display: inline-flex; align-items: center; gap: 6px;">
                    <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="6 9 6 2 18 2 18 9"></polyline><path d="M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2"></path><rect x="6" y="14" width="12" height="8"></rect></svg>
                    พิมพ์ใบเสร็จ
                </button>
                <a href="<?= base_url('profile.php?tab=orders') ?>" class="btn btn-primary btn-sm" style="text-decoration: none;">
                    ← กลับหน้ารวมออร์เดอร์
                </a>
            </div>
        </div>

        <div style="display: grid; grid-template-columns: 1fr; gap: 20px;">
            <!-- Shipping Address Card -->
            <div class="card">
                <div style="display: flex; align-items: center; gap: 8px; margin-bottom: 12px; padding-bottom: 8px; border-bottom: 1px solid #eee;">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="color: var(--color-primary);"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"></path><circle cx="12" cy="10" r="3"></circle></svg>
                    <h2 style="font-size: 16px; margin: 0; font-family: var(--font-krub); font-weight: 700;">ข้อมูลการจัดส่งสินค้า</h2>
                </div>
                <div style="font-family: var(--font-krub); font-size: 14px; line-height: 1.6; color: #444;">
                    <div><b>ผู้รับ:</b> <?= e($order['userName']) ?></div>
                    <?php if (!empty($order['userPhone'])): ?>
                        <div><b>เบอร์โทรศัพท์:</b> <?= e($order['userPhone']) ?></div>
                    <?php endif; ?>
                    <div style="margin-top: 4px;">
                        <b>ที่อยู่จัดส่ง:</b> <?= !empty($order['address']) ? nl2br(e($order['address'])) : '<span style="color: #999;">(ไม่ได้ระบุที่อยู่)</span>' ?>
                    </div>
                </div>
            </div>

            <!-- Ordered Products Card -->
            <div class="card">
                <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 16px; padding-bottom: 8px; border-bottom: 1px solid #eee;">
                    <div style="display: flex; align-items: center; gap: 8px;">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="color: var(--color-primary);"><rect x="2" y="7" width="20" height="14" rx="2" ry="2"></rect><path d="M16 21V5a2 2 0 0 0-2-2h-4a2 2 0 0 0-2 2v16"></path></svg>
                        <h2 style="font-size: 16px; margin: 0; font-family: var(--font-krub); font-weight: 700;">รายการสินค้าในออร์เดอร์ (<?= count($items) ?> รายการ)</h2>
                    </div>
                    <span style="font-size: 13px; color: #666; font-family: var(--font-krub);">รวมทั้งหมด <?= $totalQty ?> ชิ้น</span>
                </div>

                <div style="display: flex; flex-direction: column; gap: 14px;">
                    <?php foreach ($items as $idx => $it): ?>
                        <?php 
                        $itId = (int)($it['id'] ?? 0);
                        $itName = $it['name'] ?? ('สินค้า #' . $itId);
                        $itImage = !empty($it['image']) ? $it['image'] : 'assets/shirt.png';
                        $itQty = max(1, (int)($it['quantity'] ?? 1));
                        $itPrice = (float)($it['price'] ?? 0);
                        $itSubtotal = $itPrice * $itQty;
                        ?>
                        <div style="display: flex; align-items: center; justify-content: space-between; gap: 16px; padding-bottom: 14px; border-bottom: <?= $idx === count($items) - 1 ? 'none' : '1px dashed #eee' ?>;">
                            <div style="display: flex; align-items: center; gap: 14px; flex: 1;">
                                <img src="<?= asset_url($itImage) ?>" alt="<?= e($itName) ?>" style="width: 68px; height: 68px; object-fit: cover; border-radius: 6px; border: 1px solid #eee; background: #f9f9f9; flex-shrink: 0;" />
                                <div>
                                    <?php if ($itId > 0): ?>
                                        <a href="<?= base_url('product-detail.php?id=' . $itId) ?>" target="_blank" style="font-weight: 600; font-size: 15px; color: var(--color-black); text-decoration: none; font-family: var(--font-krub); display: inline-block; margin-bottom: 4px;">
                                            <?= e($itName) ?>
                                        </a>
                                    <?php else: ?>
                                        <span style="font-weight: 600; font-size: 15px; color: var(--color-black); font-family: var(--font-krub); display: inline-block; margin-bottom: 4px;">
                                            <?= e($itName) ?>
                                        </span>
                                    <?php endif; ?>

                                    <div style="font-size: 13px; color: #666; font-family: var(--font-krub);">
                                        จำนวน: <?= $itQty ?> ชิ้น | ราคาต่อชิ้น: ฿<?= number_format($itPrice, 0) ?> บาท
                                    </div>
                                </div>
                            </div>

                            <div style="font-weight: 700; font-size: 16px; color: var(--color-black); font-family: var(--font-black-ops-one); text-align: right; white-space: nowrap;">
                                ฿<?= number_format($itSubtotal, 0) ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>

                <!-- Financial Summary Box -->
                <div style="margin-top: 20px; padding-top: 16px; border-top: 2px solid #eee; display: flex; flex-direction: column; gap: 8px; font-family: var(--font-krub);">
                    <div style="display: flex; justify-content: space-between; font-size: 14px; color: #666;">
                        <span>ยอดรวมค่าสินค้า (<?= $totalQty ?> ชิ้น)</span>
                        <span>฿<?= number_format($order['total'], 0) ?> บาท</span>
                    </div>
                    <div style="display: flex; justify-content: space-between; font-size: 14px; color: #666;">
                        <span>ค่าจัดส่ง</span>
                        <span style="color: #2e7d32; font-weight: 600;">ส่งฟรี</span>
                    </div>
                    <div style="display: flex; justify-content: space-between; font-size: 18px; font-weight: 700; color: var(--color-black); margin-top: 8px; padding-top: 8px; border-top: 1px solid #f0f0f0;">
                        <span>ยอดชำระสุทธิ</span>
                        <span style="color: var(--color-primary); font-family: var(--font-black-ops-one); font-size: 22px;">
                            ฿<?= number_format($order['total'], 0) ?> บาท
                        </span>
                    </div>
                </div>
            </div>
        </div>

        <div style="margin-top: 24px; text-align: center;">
            <a href="<?= base_url('profile.php?tab=orders') ?>" style="color: #666; font-family: var(--font-krub); font-size: 14px; text-decoration: underline;">
                ← กลับไปยังหน้ารวมคำสั่งซื้อในโปรไฟล์
            </a>
        </div>
    <?php endif; ?>
    </div>
</main>

<?php include __DIR__ . '/includes/footer.php'; ?>
