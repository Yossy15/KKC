<?php
/**
 * User Profile, Orders & Support Page
 * Desktop: Sidebar Slice / Mobile: Fixed Bottom Tab Bar
 */
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/data/order_data.php';

if (!is_logged_in()) {
    header("Location: " . base_url('login.php?redirect=' . urlencode($_SERVER['REQUEST_URI'])));
    exit;
}

$currentUser = current_user();
$userOrders = get_orders_by_user((int)$currentUser['id']);
$activeTab = $_GET['tab'] ?? 'info';
$supportSuccess = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['support_submit'])) {
    if (verify_csrf_token($_POST['csrf_token'] ?? '')) {
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

<style>
/* Desktop Fullscreen Profile Layout */
.profile-wrapper {
    width: 100% !important;
    max-width: 100% !important;
    margin: 0 !important;
    padding: 0 !important;
    min-height: calc(100vh - 70px) !important;
    background-color: var(--color-gray-light);
}

.profile-layout {
    display: flex !important;
    width: 100% !important;
    min-height: calc(100vh - 70px) !important;
    gap: 0 !important;
    align-items: stretch !important;
}

.profile-sidebar {
    width: 260px !important;
    min-width: 260px !important;
    background-color: var(--color-white) !important;
    border-right: 2px solid #e5e5e5 !important;
    border-radius: 0 !important;
    padding: 24px 16px !important;
    display: flex !important;
    flex-direction: column !important;
    gap: 8px !important;
    min-height: calc(100vh - 70px) !important;
}

.profile-content {
    flex: 1 !important;
    min-width: 0 !important;
    padding: 28px 36px !important;
    background-color: var(--color-gray-light) !important;
    border-radius: 0 !important;
    min-height: calc(100vh - 70px) !important;
}

.profile-panel {
    display: none;
    background-color: var(--color-white);
    border-radius: 8px;
    padding: 28px 32px;
    box-shadow: 0 1px 4px rgba(0, 0, 0, 0.06);
}

.profile-panel.active {
    display: block !important;
}

/* Mobile Bottom Tab Bar */
.profile-mobile-tab-bar {
    display: none;
}

@media (max-width: 768px) {
    .profile-layout {
        flex-direction: column !important;
    }
    .profile-sidebar {
        display: none !important;
    }
    .profile-content {
        display: block !important;
        padding: 16px !important;
        padding-bottom: 85px !important;
        width: 100% !important;
        background-color: transparent !important;
    }
    .profile-panel {
        padding: 20px 16px !important;
    }
    .profile-mobile-tab-bar {
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
    .profile-mobile-tab-item {
        flex: 1;
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        text-decoration: none;
        color: #777;
        font-family: var(--font-krub);
        font-size: 12px;
        font-weight: 600;
        cursor: pointer;
        border: none;
        background: transparent;
        transition: all 0.15s;
    }
    .profile-mobile-tab-item.active {
        color: var(--color-black);
        background-color: #f7f7f7;
        border-top: 3px solid var(--color-black);
    }
    footer {
        margin-bottom: 60px;
    }
}

@media (min-width: 769px) {
    .profile-mobile-tab-bar {
        display: none !important;
    }
}
</style>

<main class="profile-wrapper">
    <div class="profile-layout">
        <!-- Desktop Sidebar ("Slice ข้าง") -->
        <aside class="profile-sidebar">
            <div class="profile-user-card">
                <img src="<?= asset_url($currentUser['img']) ?>" alt="avatar" class="profile-avatar-lg" />
                <div class="profile-user-name"><?= e($currentUser['name'] ?: $currentUser['username']) ?></div>
                <div class="profile-user-role"><?= e($currentUser['role']) ?></div>
            </div>

            <button type="button" class="profile-nav-btn <?= $activeTab === 'info' ? 'active' : '' ?>" id="desktopBtn-info" onclick="switchProfileTab('info')">
                <span>ข้อมูลส่วนตัว</span>
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
        <section class="profile-content">
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
                            $img = $order['product']['image'] ?? 'assets/shirt.png';
                            ?>
                            <div class="order-card">
                                <div class="order-header">
                                    <div>
                                        <span class="order-id">คำสั่งซื้อ #ORD-<?= str_pad((string)$order['id'], 5, '0', STR_PAD_LEFT) ?></span>
                                        <span style="color: #888; margin-left: 10px;"><?= date('d M Y, H:i', strtotime($order['createdAt'])) ?></span>
                                    </div>
                                    <span class="order-badge <?= $badge ?>"><?= e($order['status']) ?></span>
                                </div>
                                <div class="order-body">
                                    <img src="<?= asset_url($img) ?>" alt="<?= e($order['product']['name']) ?>" class="order-img" />
                                    <div class="order-details">
                                        <span class="order-product-name"><?= e($order['product']['name']) ?></span>
                                        <span class="order-qty-price">จำนวน: <?= $order['quantity'] ?> ชิ้น | ราคาชิ้นละ <?= $order['price'] ?> บาท</span>
                                    </div>
                                    <div class="order-total">
                                        ยอดรวม: <?= $order['total'] ?> บาท
                                    </div>
                                </div>
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
        <span>ข้อมูลส่วนตัว</span>
    </button>
    <button type="button" class="profile-mobile-tab-item <?= $activeTab === 'orders' ? 'active' : '' ?>" id="mobileBtn-orders" onclick="switchProfileTab('orders')">
        <img src="<?= asset_url('public/box.svg') ?>" alt="orders" style="width: 20px; height: 20px; margin-bottom: 2px;" />
        <span>ออร์เดอร์ของฉัน</span>
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
</script>

<?php include __DIR__ . '/includes/footer.php'; ?>
