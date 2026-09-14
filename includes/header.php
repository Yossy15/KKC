<?php
/**
 * Shared Header & Navigation
 */
require_once __DIR__ . '/functions.php';

$page_title = $page_title ?? 'KKC - Kai Khong Chan';
$show_back_arrow = $show_back_arrow ?? false;
$back_url = $back_url ?? base_url('index.php');
$hide_nav_admin_btn = $hide_nav_admin_btn ?? false;
$hide_nav_cart = $hide_nav_cart ?? false;
$currentUser = current_user();
?>
<!doctype html>
<html lang="th">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title><?= e($page_title) ?></title>
    <link rel="stylesheet" href="<?= asset_url('style/style.css') ?>" />
    <link rel="icon" type="image/png" sizes="32x32" href="<?= asset_url('assets/logo-nrm.jpg') ?>" />
</head>
<body>
    <nav>
        <a href="<?= $show_back_arrow ? e($back_url) : base_url('index.php') ?>" class="nav-container">
            <?php if ($show_back_arrow): ?>
                <img src="<?= asset_url('public/arrow.svg') ?>" alt="arrow" class="back-arrow arrow-left mobile-only" />
                <img src="<?= asset_url('assets/logo.png') ?>" alt="logo" class="logo desktop-only" />
            <?php else: ?>
                <img src="<?= asset_url('assets/logo.png') ?>" alt="logo" class="logo" />
            <?php endif; ?>
        </a>
        <div class="nav-icon-group" style="display: flex; align-items: center; gap: 16px;">
            <?php if (!$hide_nav_admin_btn && $currentUser && $currentUser['role'] === 'admin'): ?>
                <a href="<?= base_url('admin.php') ?>" class="login-btn" style="background-color: var(--color-error); font-size: 12px; padding: 4px 10px; text-decoration: none;">
                    ADMIN PANEL
                </a>
            <?php endif; ?>
            <?php if (!$hide_nav_cart): ?>
            <a href="<?= base_url('cart.php') ?>" class="nav-cart" aria-label="ตะกร้าสินค้า" style="position: relative; display: flex; align-items: center;">
                <img src="<?= asset_url('public/cart.svg') ?>" alt="cart">
                <?php $cartCount = get_cart_count(); if ($cartCount > 0): ?>
                    <span style="position: absolute; top: -6px; right: -8px; background-color: var(--color-error); color: #fff; font-size: 11px; font-weight: bold; border-radius: 50%; width: 18px; height: 18px; display: flex; align-items: center; justify-content: center; line-height: 1; font-family: var(--font-krub);">
                        <?= $cartCount ?>
                    </span>
                <?php endif; ?>
            </a>
            <?php endif; ?>
            <div id="profile-container">
                <?php if ($currentUser): ?>
                    <a href="<?= base_url('profile.php') ?>">
                        <img src="<?= asset_url($currentUser['img']) ?>" alt="profile" class="nav-profile" title="<?= e($currentUser['name'] ?? $currentUser['username']) ?>" />
                    </a>
                <?php else: ?>
                    <button type="button" class="login-btn" onclick="openLoginModal()">Login</button>
                <?php endif; ?>
            </div>
        </div>
    </nav>

    <!-- Desktop Login / Register Dialog -->
    <dialog id="loginDialog">
        <button commandfor="loginDialog" command="close" type="button" onclick="document.getElementById('loginDialog').close()">
            <img src="<?= asset_url('public/close.svg') ?>" alt="close" />
        </button>
        <div class="auth-container" style="max-width: 420px; box-shadow: none;">
            <div class="auth-tabs">
                <button type="button" class="auth-tab active" id="modalTabLoginBtn" onclick="switchModalAuthTab('login')">
                    เข้าสู่ระบบ
                </button>
                <button type="button" class="auth-tab" id="modalTabRegisterBtn" onclick="switchModalAuthTab('register')">
                    สมัครสมาชิก
                </button>
            </div>

            <!-- Modal Login Form -->
            <form id="modalLoginForm" class="auth-form" method="POST" action="<?= base_url('login.php?action=login') ?>" onsubmit="handleAuthAjax(event, this)">
                <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
                <input type="hidden" name="redirect" value="<?= e($_SERVER['REQUEST_URI'] ?? '') ?>">
                <div class="auth-field">
                    <label for="modalLoginUser">ชื่อผู้ใช้ หรือ อีเมล</label>
                    <input type="text" name="username" id="modalLoginUser" class="auth-input" placeholder="เช่น kkc1 หรือ kkc1@g.co" required />
                </div>
                <div class="auth-field">
                    <label for="modalLoginPass">รหัสผ่าน</label>
                    <input type="password" name="password" id="modalLoginPass" class="auth-input" placeholder="รหัสผ่าน (mock: 1234)" required />
                </div>
                <div id="modalLoginError" class="auth-error" style="display: none;"></div>
                <button type="submit" class="auth-btn">เข้าสู่ระบบ</button>
                <div class="auth-switch-text">
                    ยังไม่มีบัญชี? <a href="javascript:void(0)" onclick="switchModalAuthTab('register')">สมัครสมาชิก</a>
                </div>
            </form>

            <!-- Modal Register Form -->
            <form id="modalRegisterForm" class="auth-form" style="display: none;" method="POST" action="<?= base_url('login.php?action=register') ?>" onsubmit="handleAuthAjax(event, this)">
                <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
                <input type="hidden" name="redirect" value="<?= e($_SERVER['REQUEST_URI'] ?? '') ?>">
                <div class="auth-field">
                    <label for="modalRegName">ชื่อ - นามสกุล</label>
                    <input type="text" name="name" id="modalRegName" class="auth-input" placeholder="เช่น สมชาย ใจดี" required />
                </div>
                <div class="auth-field">
                    <label for="modalRegUsername">ชื่อผู้ใช้</label>
                    <input type="text" name="username" id="modalRegUsername" class="auth-input" placeholder="เช่น somchai99" required />
                </div>
                <div class="auth-field">
                    <label for="modalRegEmail">อีเมล</label>
                    <input type="email" name="email" id="modalRegEmail" class="auth-input" placeholder="เช่น somchai@example.com" required />
                </div>
                <div class="auth-field">
                    <label for="modalRegPhone">เบอร์โทรศัพท์</label>
                    <input type="tel" name="phone" id="modalRegPhone" class="auth-input" placeholder="เช่น 0812345678" required />
                </div>
                <div class="auth-field">
                    <label for="modalRegPass">รหัสผ่าน</label>
                    <input type="password" name="password" id="modalRegPass" class="auth-input" placeholder="อย่างน้อย 4 ตัวอักษร" minlength="4" required />
                </div>
                <div class="auth-field">
                    <label for="modalRegConfirmPass">ยืนยันรหัสผ่าน</label>
                    <input type="password" name="confirm_password" id="modalRegConfirmPass" class="auth-input" placeholder="กรอกรหัสผ่านอีกครั้ง" required />
                </div>
                <div id="modalRegisterError" class="auth-error" style="display: none;"></div>
                <button type="submit" class="auth-btn">สมัครสมาชิก</button>
                <div class="auth-switch-text">
                    มีบัญชีอยู่แล้ว? <a href="javascript:void(0)" onclick="switchModalAuthTab('login')">เข้าสู่ระบบ</a>
                </div>
            </form>
        </div>
    </dialog>
