<?php
/**
 * User Profile, Orders & Support Page
 * Desktop: Sidebar Slice / Mobile: Fixed Bottom Tab Bar
 */
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/data/order_data.php';
require_once __DIR__ . '/includes/thai_date.php';

$currentUser = current_user();
$userOrders = get_orders_by_user((int)$currentUser['id']);
$activeTab = $_GET['tab'] ?? ($_GET['view'] ?? 'info');
$supportSuccess = false;
$addressNotice = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['address_submit']) && verify_csrf_token($_POST['csrf_token'] ?? '')) {
        require_once __DIR__ . '/data/user_data.php';
        $newAddr = trim($_POST['address'] ?? '');
        if (update_user_address((int)$currentUser['id'], $newAddr)) {
            $currentUser['address'] = $newAddr;
            $addressNotice = 'บันทึกที่อยู่จัดส่งเรียบร้อยแล้ว';
            if (!empty($_GET['redirect'])) {
                header("Location: " . $_GET['redirect']);
                exit;
            }
        }
        $activeTab = 'address';
    } elseif (isset($_POST['support_submit']) && verify_csrf_token($_POST['csrf_token'] ?? '')) {
        $supportSuccess = true;
        $activeTab = 'support';
    }
}

$page_title = 'KKC - ข้อมูลโปรไฟล์';
$show_back_arrow = true;
$back_url = base_url('index.php');

include __DIR__ . '/includes/header.php';

$breadcrumbs = [
    ['title' => 'Profile']
];
include __DIR__ . '/includes/breadcrumb.php';

$badgeClassMap = [
    "รอดำเนินการ" => "badge-pending",
    "กำลังจัดส่ง" => "badge-shipping",
    "จัดส่งแล้ว"   => "badge-delivered",
    "ยกเลิก"      => "badge-cancelled"
];
?>



<main class="profile-wrapper">
    <div class="profile-layout">
        <!-- Desktop Sidebar ("Slice ข้าง") -->
        <aside class="profile-sidebar" style="margin-left: 24px;">
            <div class="profile-user-card">
                <img src="<?= asset_url($currentUser['img']) ?>" alt="avatar" class="profile-avatar-lg" />
                <div class="profile-user-name"><?= e($currentUser['name'] ?: $currentUser['username']) ?></div>
                <div class="profile-user-role"><?= e($currentUser['role']) ?></div>
            </div>

            <button type="button" class="profile-nav-btn <?= $activeTab === 'info' ? 'active' : '' ?>" id="desktopBtn-info" onclick="switchProfileTab('info')">
                <span>ข้อมูลส่วนตัว</span>
            </button>
            <button type="button" class="profile-nav-btn <?= $activeTab === 'address' ? 'active' : '' ?>" id="desktopBtn-address" onclick="switchProfileTab('address')">
                <span>ที่อยู่จัดส่ง</span>
            </button>
            <button type="button" class="profile-nav-btn <?= $activeTab === 'orders' ? 'active' : '' ?>" id="desktopBtn-orders" onclick="switchProfileTab('orders')">
                <span>ออร์เดอร์ของฉัน</span>
            </button>
            <button type="button" class="profile-nav-btn <?= $activeTab === 'support' ? 'active' : '' ?>" id="desktopBtn-support" onclick="switchProfileTab('support')">
                <span>แจ้งปัญหา</span>
            </button>
            <?php if ($currentUser['role'] === 'admin'): ?>
                <a href="<?= base_url('admin.php') ?>" class="profile-nav-btn" style="text-decoration: none; color: var(--color-error); font-weight: 700;">
                    <span>แผงควบคุมผู้ดูแลระบบ</span>
                </a>
            <?php endif; ?>
            <a href="<?= base_url('logout.php') ?>" class="profile-nav-btn logout" onclick="event.preventDefault(); showAppConfirm('คุณต้องการออกจากระบบหรือไม่?', 'ยืนยันการออกจากระบบ').then(ok => { if (ok) window.location.href = this.href; });" style="text-decoration: none;">
                <span>ออกจากระบบ</span>
            </a>
        </aside>

        <!-- Main Content Area -->
        <section class="profile-content" style="padding-top: unset;">
            <!-- Mobile User Top Bar (Mobile Only) -->
            <div class="mobile-only" style="align-items: center; justify-content: space-between; background: var(--color-white); padding: 12px 16px; border-radius: 8px; margin-bottom: 16px;">
                <div style="display: flex; align-items: center; gap: 12px;">
                    <img src="<?= asset_url($currentUser['img']) ?>" alt="avatar" style="width: 44px; height: 44px; border-radius: 50%; object-fit: cover;" />
                    <div>
                        <div style="font-weight: 700; font-size: 15px; font-family: var(--font-krub);"><?= e($currentUser['name'] ?: $currentUser['username']) ?></div>
                        <span style="font-size: 12px; color: #888;"><?= e($currentUser['role']) ?></span>
                    </div>
                </div>
                <div style="display: flex; gap: 8px; align-items: center;">
                    <?php if ($currentUser['role'] === 'admin'): ?>
                        <a href="<?= base_url('admin.php') ?>" class="login-btn" style="padding: 4px 8px; font-size: 11px; background-color: var(--color-error); text-decoration: none;">
                            ADMIN
                        </a>
                    <?php endif; ?>
                    <a href="<?= base_url('logout.php') ?>" onclick="event.preventDefault(); showAppConfirm('คุณต้องการออกจากระบบหรือไม่?', 'ยืนยันการออกจากระบบ').then(ok => { if (ok) window.location.href = this.href; });" style="font-size: 12px; color: #888; font-family: var(--font-krub); text-decoration: underline;">
                        ออกจากระบบ
                    </a>
                </div>
            </div>

            <!-- Panel 1: Personal Info -->
            <div class="profile-panel <?= $activeTab === 'info' ? 'active' : '' ?>" id="panel-info">
                <h2 class="title profile-panel-title">ข้อมูลส่วนตัว</h2>
                <div class="info-grid">
                    <div class="info-item">
                        <span class="info-label">ชื่อ - นามสกุล</span>
                        <span class="info-value"><?= e($currentUser['name'] ?: '-') ?></span>
                    </div>
                    <div class="info-item">
                        <span class="info-label">ชื่อผู้ใช้ (Username)</span>
                        <span class="info-value"><?= e($currentUser['username'] ?: '-') ?></span>
                    </div>
                    <div class="info-item">
                        <span class="info-label">อีเมล</span>
                        <span class="info-value"><?= e($currentUser['email'] ?: '-') ?></span>
                    </div>
                    <div class="info-item">
                        <span class="info-label">เบอร์โทรศัพท์</span>
                        <span class="info-value"><?= e($currentUser['phone'] ?: '-') ?></span>
                    </div>
                    <div class="info-item" style="grid-column: 1 / -1;">
                        <span class="info-label">ที่อยู่จัดส่ง</span>
                        <span class="info-value">
                            <?= e($currentUser['address'] ?: '-') ?>
                            <!-- <a href="javascript:void(0)" onclick="switchProfileTab('address')" style="font-size: 13px; color: var(--color-primary); margin-left: 10px; text-decoration: underline;">
                                <?= empty($currentUser['address']) ? '+ เพิ่มที่อยู่' : 'แก้ไขที่อยู่' ?>
                            </a> -->
                        </span>
                    </div>
                    <div class="info-item">
                        <span class="info-label">สถานะบัญชี</span>
                        <span class="info-value"><?= e($currentUser['status'] ?: 'Active') ?></span>
                    </div>
                    <div class="info-item">
                        <span class="info-label">ระดับผู้ใช้</span>
                        <span class="info-value"><?= e($currentUser['role'] ?: 'User') ?></span>
                    </div>
                </div>
            </div>

            <!-- Panel: Address -->
            <div class="profile-panel <?= $activeTab === 'address' ? 'active' : '' ?>" id="panel-address">
                <h2 class="title profile-panel-title">ที่อยู่สำหรับการจัดส่ง</h2>
                <?php if (!empty($addressNotice)): ?>
                    <div style="margin-bottom: 16px; padding: 12px 16px; background-color: #d4edda; color: #155724; border-radius: 6px; font-family: var(--font-krub);">
                        <?= e($addressNotice) ?>
                    </div>
                <?php endif; ?>
                <form method="POST" action="<?= base_url('profile.php?tab=address' . (!empty($_GET['redirect']) ? '&redirect=' . urlencode($_GET['redirect']) : '')) ?>" class="auth-form" style="max-width: 600px;">
                    <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
                    <input type="hidden" name="address_submit" value="1">
                    <div class="auth-field">
                        <label for="userAddress">ที่อยู่จัดส่งสินค้า (บ้านเลขที่, ถนน, แขวง/ตำบล, เขต/อำเภอ, จังหวัด, รหัสไปรษณีย์)</label>
                        <textarea name="address" id="userAddress" class="auth-input" rows="4" placeholder="กรุณาระบุที่อยู่จัดส่ง เช่น 123/45 หมู่ 2 ถ.มิตรภาพ ต.ในเมือง อ.เมือง จ.ขอนแก่น 40000" style="resize: vertical; line-height: 1.6;" required><?= e($currentUser['address'] ?? '') ?></textarea>
                    </div>
                    <div style="display: flex; gap: 12px; align-items: center; margin-top: 8px;">
                        <button type="submit" class="auth-btn" style="margin: 0; padding: 10px 24px; width: auto;">
                            <?= empty($currentUser['address']) ? 'บันทึกที่อยู่' : 'อัปเดตที่อยู่' ?>
                        </button>
                        <?php if (!empty($_GET['redirect'])): ?>
                            <a href="<?= e($_GET['redirect']) ?>" class="page-number-button" style="text-decoration: none; padding: 10px 16px; height: auto;">
                                กลับไปยังหน้ารายการสั่งซื้อ
                            </a>
                        <?php endif; ?>
                    </div>
                </form>
            </div>

            <!-- Panel 2: Orders -->
            <div class="profile-panel <?= $activeTab === 'orders' ? 'active' : '' ?>" id="panel-orders">
                <h2 class="title profile-panel-title">รายการคำสั่งซื้อและสถานะจัดส่ง</h2>
                <?php if (isset($_GET['ordered'])): ?>
                    <div style="margin-bottom: 16px; padding: 12px 16px; background-color: #d4edda; color: #155724; border-radius: 6px; font-family: var(--font-krub);">
                        ทำรายการสั่งซื้อเรียบร้อยแล้ว ทีมงานกำลังดำเนินการจัดเตรียมสินค้า
                    </div>
                <?php endif; ?>

                <div class="order-list">
                    <?php if (empty($userOrders)): ?>
                        <div style="text-align: center; color: #888; padding: 40px 0; font-family: var(--font-krub);">ยังไม่มีรายการคำสั่งซื้อ</div>
                    <?php else: ?>
                        <?php foreach ($userOrders as $order): ?>
                            <?php 
                            $badge = $badgeClassMap[$order['status']] ?? 'badge-pending';
                            $items = !empty($order['items']) ? $order['items'] : [$order['product']];
                            ?>
                            <div class="order-card">
                                <div class="order-header">
                                    <div>
                                        <span class="order-id">คำสั่งซื้อ <?= str_pad((string)$order['id'], 5, '0', STR_PAD_LEFT) ?></span>
                                        <span style="color: #888; margin-left: 10px; font-size: 12px;"><?= thaiDateTime($order['createdAt']) ?></span>
                                    </div>
                                    <span class="order-badge <?= $badge ?>"><?= e($order['status']) ?></span>
                                </div>
                                <div class="order-body" style="display: flex; flex-direction: column; gap: 12px; width: 100%;">
                                    <?php 
                                    $firstItem = $items[0] ?? null;
                                    $remainingItems = array_slice($items, 1);
                                    ?>

                                    <?php if ($firstItem): ?>
                                        <div style="display: flex; align-items: center; justify-content: space-between; gap: 16px; width: 100%; border-bottom: 1px dashed #eee; padding-bottom: 12px;">
                                            <div style="display: flex; align-items: center; gap: 14px; flex: 1;">
                                                <img src="<?= asset_url($firstItem['image'] ?? 'assets/shirt.png') ?>" alt="<?= e($firstItem['name'] ?? 'product') ?>" class="order-img" style="width: 60px; height: 60px; object-fit: cover; border-radius: 6px; border: 1px solid #eee;" />
                                                <div class="order-details">
                                                    <span class="order-product-name"><?= e($firstItem['name'] ?? 'สินค้า') ?></span>
                                                    <span class="order-qty-price">จำนวน: <?= (int)($firstItem['quantity'] ?? 1) ?> ชิ้น | ราคาชิ้นละ <?= number_format($firstItem['price'] ?? 0, 0) ?> บาท</span>
                                                </div>
                                            </div>
                                            <div style="font-weight: 600; font-size: 14px; color: var(--color-gray-dark); text-align: right; white-space: nowrap;">
                                                ฿<?= number_format(($firstItem['price'] ?? 0) * ($firstItem['quantity'] ?? 1), 0) ?>
                                            </div>
                                        </div>
                                    <?php endif; ?>

                                    <?php if (!empty($remainingItems)): ?>
                                        <!-- Collapsible remaining items -->
                                        <div id="order-more-<?= $order['id'] ?>" style="display: none; flex-direction: column; gap: 12px; width: 100%;">
                                            <?php foreach ($remainingItems as $item): ?>
                                                <div style="display: flex; align-items: center; justify-content: space-between; gap: 16px; width: 100%; border-bottom: 1px dashed #eee; padding-bottom: 12px;">
                                                    <div style="display: flex; align-items: center; gap: 14px; flex: 1;">
                                                        <img src="<?= asset_url($item['image'] ?? 'assets/shirt.png') ?>" alt="<?= e($item['name'] ?? 'product') ?>" class="order-img" style="width: 60px; height: 60px; object-fit: cover; border-radius: 6px; border: 1px solid #eee;" />
                                                        <div class="order-details">
                                                            <span class="order-product-name"><?= e($item['name'] ?? 'สินค้า') ?></span>
                                                            <span class="order-qty-price">จำนวน: <?= (int)($item['quantity'] ?? 1) ?> ชิ้น | ราคาชิ้นละ <?= number_format($item['price'] ?? 0, 0) ?> บาท</span>
                                                        </div>
                                                    </div>
                                                    <div style="font-weight: 600; font-size: 14px; color: var(--color-gray-dark); text-align: right; white-space: nowrap;">
                                                        ฿<?= number_format(($item['price'] ?? 0) * ($item['quantity'] ?? 1), 0) ?>
                                                    </div>
                                                </div>
                                            <?php endforeach; ?>
                                        </div>

                                        <button type="button" 
                                                onclick="toggleOrderItems(<?= $order['id'] ?>)" 
                                                id="btn-more-<?= $order['id'] ?>"
                                                data-more-count="<?= count($remainingItems) ?>"
                                                style="align-self: flex-start; background: none; border: none; color: var(--color-primary); font-family: var(--font-krub); font-size: 13px; font-weight: 600; cursor: pointer; padding: 2px 0 6px; display: inline-flex; align-items: center; gap: 4px;">
                                            <span>แสดงเพิ่มเติม (+<?= count($remainingItems) ?> รายการ)</span>
                                            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="6 9 12 15 18 9"></polyline></svg>
                                        </button>
                                    <?php endif; ?>
                                </div>
                                <!-- <div class="order-footer">
                                    <span style="font-size: 15px; font-weight: 600;">สินค้ารวม <?= count($items) ?> รายการ: ฿<?= number_format($order['total'], 0) ?> บาท</span>
                                    <div style="display: flex; gap: 8px; align-items: center;">
                                        <a href="<?= base_url('order-detail.php?id=' . $order['id']) ?>" target="_blank" style="font-size: 13px; color: var(--color-primary); font-family: var(--font-krub); font-weight: 600; text-decoration: underline; display: inline-flex; align-items: center; gap: 4px;">
                                            ดูรายละเอียด
                                            <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M18 13v6a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h6"></path><polyline points="15 3 21 3 21 9"></polyline><line x1="10" y1="14" x2="21" y2="3"></line></svg>
                                        </a>
                                    </div>
                                </div> -->
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Panel 3: Support -->
            <div class="profile-panel <?= $activeTab === 'support' ? 'active' : '' ?>" id="panel-support">
                <h2 class="title profile-panel-title">แจ้งปัญหาการใช้งาน</h2>
                <form id="supportForm" class="auth-form" method="POST" action="<?= base_url('profile.php?tab=support') ?>">
                    <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
                    <input type="hidden" name="support_submit" value="1">
                    <div class="auth-field">
                        <label for="issueTopic">หัวข้อปัญหา</label>
                        <select name="topic" id="issueTopic" class="auth-input" required>
                            <option value="">-- เลือกหัวข้อปัญหา --</option>
                            <option value="product">สินค้าชำรุด / ไม่ตรงตามคำสั่งซื้อ</option>
                            <option value="delivery">การจัดส่งล่าช้า / ไม่ได้รับสินค้า</option>
                            <option value="payment">ปัญหาการชำระเงิน</option>
                            <option value="account">ปัญหาเกี่ยวกับบัญชีผู้ใช้</option>
                            <option value="other">เรื่องอื่นๆ</option>
                        </select>
                    </div>
                    <div class="auth-field">
                        <label for="orderRef">เลขออร์เดอร์ที่เกี่ยวข้อง (ถ้ามี)</label>
                        <input type="text" name="order_ref" id="orderRef" class="auth-input" placeholder="เช่น #ORD-00123" />
                    </div>
                    <div class="auth-field">
                        <label for="issueDetail">รายละเอียดปัญหา</label>
                        <textarea name="detail" id="issueDetail" class="auth-input" rows="5" placeholder="กรุณาระบุรายละเอียดปัญหาที่ท่านพบให้ชัดเจน..." style="resize: vertical;" required></textarea>
                    </div>
                    <button type="submit" class="auth-btn">ส่งเรื่องแจ้งปัญหา</button>
                </form>

                <?php if ($supportSuccess): ?>
                    <div id="supportSuccess" style="margin-top: 16px; padding: 16px; background-color: #d4edda; color: #155724; border-radius: 6px; font-family: var(--font-krub);">
                        ส่งเรื่องแจ้งปัญหาเรียบร้อยแล้ว ทีมงานจะตรวจสอบและติดต่อกลับโดยเร็วที่สุด
                    </div>
                <?php endif; ?>
            </div>
        </section>
    </div>
</main>

<!-- Mobile Bottom Tab Navigation Bar (เมนูแท็บด้านล่างขอบจอสำหรับมือถือ) -->
<div class="profile-mobile-tab-bar" role="navigation">
    <button type="button" class="profile-mobile-tab-item <?= $activeTab === 'info' ? 'active' : '' ?>" id="mobileBtn-info" onclick="switchProfileTab('info')">
        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="margin-bottom: 2px;"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path><circle cx="12" cy="7" r="4"></circle></svg>
        <span>ข้อมูล</span>
    </button>
    <button type="button" class="profile-mobile-tab-item <?= $activeTab === 'address' ? 'active' : '' ?>" id="mobileBtn-address" onclick="switchProfileTab('address')">
        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="margin-bottom: 2px;"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"></path><circle cx="12" cy="10" r="3"></circle></svg>
        <span>ที่อยู่</span>
    </button>
    <button type="button" class="profile-mobile-tab-item <?= $activeTab === 'orders' ? 'active' : '' ?>" id="mobileBtn-orders" onclick="switchProfileTab('orders')">
        <img src="<?= asset_url('public/box.svg') ?>" alt="orders" style="width: 20px; height: 20px; margin-bottom: 2px;" />
        <span>ออร์เดอร์</span>
    </button>
    <button type="button" class="profile-mobile-tab-item <?= $activeTab === 'support' ? 'active' : '' ?>" id="mobileBtn-support" onclick="switchProfileTab('support')">
        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="margin-bottom: 2px;"><circle cx="12" cy="12" r="10"></circle><line x1="12" y1="8" x2="12" y2="12"></line><line x1="12" y1="16" x2="12.01" y2="16"></line></svg>
        <span>แจ้งปัญหา</span>
    </button>
</div>

<script>
    function switchProfileTab(tabId) {
        document.querySelectorAll(".profile-panel").forEach((p) => p.classList.remove("active"));
        document.querySelectorAll(".profile-nav-btn").forEach((b) => b.classList.remove("active"));
        document.querySelectorAll(".profile-mobile-tab-item").forEach((b) => b.classList.remove("active"));

        const targetPanel = document.getElementById("panel-" + tabId);
        if (targetPanel) targetPanel.classList.add("active");

        const dBtn = document.getElementById("desktopBtn-" + tabId);
        if (dBtn) dBtn.classList.add("active");

        const mBtn = document.getElementById("mobileBtn-" + tabId);
        if (mBtn) mBtn.classList.add("active");

        window.scrollTo({ top: 0, behavior: "smooth" });
    }

    function toggleOrderItems(orderId) {
        const moreContainer = document.getElementById('order-more-' + orderId);
        const btn = document.getElementById('btn-more-' + orderId);
        if (!moreContainer || !btn) return;
        const count = btn.getAttribute('data-more-count');
        const isHidden = moreContainer.style.display === 'none' || moreContainer.style.display === '';
        if (isHidden) {
            moreContainer.style.display = 'flex';
            btn.innerHTML = `<span>ย่อรายการ</span> <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="18 15 12 9 6 15"></polyline></svg>`;
        } else {
            moreContainer.style.display = 'none';
            btn.innerHTML = `<span>แสดงเพิ่มเติม (+${count} รายการ)</span> <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="6 9 12 15 18 9"></polyline></svg>`;
        }
    }
</script>

<?php include __DIR__ . '/includes/footer.php'; ?>
