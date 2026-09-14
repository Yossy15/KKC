<?php
/**
 * Shared Footer & Base JavaScript
 */
$currentScript = basename($_SERVER['PHP_SELF'] ?? '');
$isHome = in_array($currentScript, ['index.php', ''], true);
$isProduct = in_array($currentScript, ['products.php', 'product-detail.php'], true);
$isCart = ($currentScript === 'cart.php');
$isProfile = ($currentScript === 'profile.php');
$footerCartCount = function_exists('get_cart_count') ? get_cart_count() : 0;
$hasInnerTabBar = in_array($currentScript, ['profile.php', 'admin.php', 'product-detail.php', 'login.php', 'cart.php', 'checkout.php'], true);
?>
    <!-- Desktop Footer (แสดงเป็น © 2026 KAI KHONG CHAN เหมือนเดิม) -->
    <section>
        <footer>
            <div class="footer-container">
                <span class="footer-text"> © 2026 KAI KHONG CHAN </span>
            </div>
        </footer>
    </section>

    <?php if (!$hasInnerTabBar): ?>
    <!-- Mobile Bottom Tab Navigation Bar (แสดงเฉพาะบน Mobile สไตล์เหมือน profile-mobile-tab-bar) -->
    <div class="mobile-footer-tab-bar" role="navigation" aria-label="Mobile Navigation">
        <a href="<?= base_url('index.php') ?>" class="mobile-footer-tab-item <?= $isHome ? 'active' : '' ?>">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="margin-bottom: 2px;">
                <path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"></path>
                <polyline points="9 22 9 12 15 12 15 22"></polyline>
            </svg>
            <span>Home</span>
        </a>
        <a href="<?= base_url('products.php') ?>" class="mobile-footer-tab-item <?= $isProduct ? 'active' : '' ?>">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="margin-bottom: 2px;">
                <path d="M6 2L3 6v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V6l-3-4z"></path>
                <line x1="3" y1="6" x2="21" y2="6"></line>
                <path d="M16 10a4 4 0 0 1-8 0"></path>
            </svg>
            <span>Product</span>
        </a>
        <a href="<?= base_url('cart.php') ?>" class="mobile-footer-tab-item <?= $isCart ? 'active' : '' ?>" style="position: relative;">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="margin-bottom: 2px;">
                <circle cx="9" cy="21" r="1"></circle>
                <circle cx="20" cy="21" r="1"></circle>
                <path d="M1 1h4l2.68 13.39a2 2 0 0 0 2 1.61h9.72a2 2 0 0 0 2-1.61L23 6H6"></path>
            </svg>
            <?php if ($footerCartCount > 0): ?>
                <span class="mobile-footer-cart-badge"><?= $footerCartCount ?></span>
            <?php endif; ?>
            <span>Cart</span>
        </a>
        <a href="<?= base_url('profile.php') ?>" class="mobile-footer-tab-item <?= $isProfile ? 'active' : '' ?>">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="margin-bottom: 2px;">
                <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path>
                <circle cx="12" cy="7" r="4"></circle>
            </svg>
            <span>Profile</span>
        </a>
    </div>
    <?php endif; ?>

    <!-- Global Alert / Confirm Dialog (แทนที่ browser alert / confirm) -->
    <dialog id="appAlertDialog" class="app-alert-dialog">
        <div class="app-alert-card">
            <button commandfor="appAlertDialog" command="close" type="button" class="alert-close" onclick="handleAppAlertCancel()" style="position: absolute; top: 12px; right: 12px; background: #fff; border: none; border-radius: 50%; width: 30px; height: 30px; display: flex; align-items: center; justify-content: center; cursor: pointer; box-shadow: 0 1px 4px rgba(0,0,0,0.15);">
                <img src="<?= asset_url('public/close.svg') ?>" alt="close" style="width: 14px; height: 14px;" />
            </button>
            <div class="app-alert-icon-box" id="appAlertIconBox">
                <svg id="appAlertIconSvg" width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <circle cx="12" cy="12" r="10"></circle>
                    <line x1="12" y1="8" x2="12" y2="12"></line>
                    <line x1="12" y1="16" x2="12.01" y2="16"></line>
                </svg>
            </div>
            <h3 class="app-alert-title" id="appAlertTitle">แจ้งเตือน</h3>
            <div class="app-alert-message" id="appAlertMessage">ข้อความแจ้งเตือน</div>
            <div class="app-alert-actions" id="appAlertActions">
                <button type="button" class="auth-btn app-alert-btn-confirm" id="appAlertConfirmBtn" onclick="handleAppAlertConfirm()">
                    ตกลง
                </button>
                <button type="button" class="app-alert-btn-cancel" id="appAlertCancelBtn" style="display: none;" onclick="handleAppAlertCancel()">
                    ยกเลิก
                </button>
            </div>
        </div>
    </dialog>

    <script>
        // Global Custom Dialog (แทนที่ window.alert และ window.confirm)
        let _appDialogResolve = null;

        function showAppAlert(message, title = 'แจ้งเตือน', type = 'info') {
            return new Promise((resolve) => {
                _appDialogResolve = resolve;
                const dialog = document.getElementById('appAlertDialog');
                if (!dialog) {
                    if (window.nativeAlert) window.nativeAlert(message);
                    else console.log(message);
                    resolve(true);
                    return;
                }

                const titleEl = document.getElementById('appAlertTitle');
                const msgEl = document.getElementById('appAlertMessage');
                const cancelBtn = document.getElementById('appAlertCancelBtn');
                const confirmBtn = document.getElementById('appAlertConfirmBtn');
                const iconBox = document.getElementById('appAlertIconBox');

                if (titleEl) titleEl.textContent = title;
                if (msgEl) msgEl.textContent = message;
                if (cancelBtn) cancelBtn.style.display = 'none';
                if (confirmBtn) confirmBtn.textContent = 'ตกลง';

                if (iconBox) {
                    iconBox.className = 'app-alert-icon-box ' + type;
                }

                if (dialog.showModal) dialog.showModal();
                else dialog.setAttribute('open', '');
            });
        }

        function showAppConfirm(message, title = 'ยืนยันการทำรายการ', options = {}) {
            return new Promise((resolve) => {
                _appDialogResolve = resolve;
                const dialog = document.getElementById('appAlertDialog');
                if (!dialog) {
                    const res = window.nativeConfirm ? window.nativeConfirm(message) : true;
                    resolve(res);
                    return;
                }

                const titleEl = document.getElementById('appAlertTitle');
                const msgEl = document.getElementById('appAlertMessage');
                const cancelBtn = document.getElementById('appAlertCancelBtn');
                const confirmBtn = document.getElementById('appAlertConfirmBtn');
                const iconBox = document.getElementById('appAlertIconBox');

                if (titleEl) titleEl.textContent = title;
                if (msgEl) msgEl.textContent = message;
                if (cancelBtn) {
                    cancelBtn.style.display = 'inline-flex';
                    cancelBtn.textContent = options.cancelText || 'ยกเลิก';
                }
                if (confirmBtn) {
                    confirmBtn.textContent = options.confirmText || 'ยืนยัน';
                }

                if (iconBox) {
                    iconBox.className = 'app-alert-icon-box warning';
                }

                if (dialog.showModal) dialog.showModal();
                else dialog.setAttribute('open', '');
            });
        }

        function handleAppAlertConfirm() {
            const dialog = document.getElementById('appAlertDialog');
            if (dialog) {
                if (dialog.close) dialog.close();
                else dialog.removeAttribute('open');
            }
            if (_appDialogResolve) {
                _appDialogResolve(true);
                _appDialogResolve = null;
            }
        }

        function handleAppAlertCancel() {
            const dialog = document.getElementById('appAlertDialog');
            if (dialog) {
                if (dialog.close) dialog.close();
                else dialog.removeAttribute('open');
            }
            if (_appDialogResolve) {
                _appDialogResolve(false);
                _appDialogResolve = null;
            }
        }

        const appAlertDialogEl = document.getElementById('appAlertDialog');
        if (appAlertDialogEl) {
            appAlertDialogEl.addEventListener('click', (e) => {
                if (e.target === appAlertDialogEl) handleAppAlertCancel();
            });
        }

        // แทนที่ native window.alert
        if (!window.nativeAlert) window.nativeAlert = window.alert;
        window.alert = function(msg) {
            showAppAlert(msg);
        };
        window.appAlert = showAppAlert;
        window.appConfirm = showAppConfirm;
        const loginDialog = document.getElementById("loginDialog");
        if (loginDialog) {
            loginDialog.addEventListener("click", (e) => {
                if (e.target === loginDialog) loginDialog.close();
            });
        }

        function openLoginModal() {
            if (window.innerWidth > 768) {
                if (loginDialog) loginDialog.showModal();
            } else {
                window.location.href = "<?= base_url('login.php') ?>";
            }
        }

        function switchModalAuthTab(tab) {
            const loginForm = document.getElementById("modalLoginForm");
            const regForm = document.getElementById("modalRegisterForm");
            const tabLogin = document.getElementById("modalTabLoginBtn");
            const tabReg = document.getElementById("modalTabRegisterBtn");
            const errLogin = document.getElementById("modalLoginError");
            const errReg = document.getElementById("modalRegisterError");
            if (errLogin) errLogin.style.display = "none";
            if (errReg) errReg.style.display = "none";

            if (tab === "register") {
                if (loginForm) loginForm.style.display = "none";
                if (regForm) regForm.style.display = "flex";
                if (tabLogin) tabLogin.classList.remove("active");
                if (tabReg) tabReg.classList.add("active");
            } else {
                if (loginForm) loginForm.style.display = "flex";
                if (regForm) regForm.style.display = "none";
                if (tabLogin) tabLogin.classList.add("active");
                if (tabReg) tabReg.classList.remove("active");
            }
        }

        async function handleAuthAjax(e, form) {
            e.preventDefault();
            const action = form.getAttribute("action");
            const formData = new FormData(form);
            const isReg = form.id === "modalRegisterForm";
            const errEl = document.getElementById(isReg ? "modalRegisterError" : "modalLoginError");
            
            if (errEl) errEl.style.display = "none";

            // Validate pass on register
            if (isReg) {
                const p1 = form.querySelector('[name="password"]').value;
                const p2 = form.querySelector('[name="confirm_password"]').value;
                if (p1 !== p2) {
                    if (errEl) {
                        errEl.textContent = "รหัสผ่านและยืนยันรหัสผ่านไม่ตรงกัน";
                        errEl.style.display = "block";
                    }
                    return;
                }
            }

            try {
                const res = await fetch(action + "&ajax=1", {
                    method: "POST",
                    body: formData
                });
                const data = await res.json();
                if (data.success) {
                    window.location.reload();
                } else {
                    if (errEl) {
                        errEl.textContent = data.error || "เกิดข้อผิดพลาดในการตรวจสอบข้อมูล";
                        errEl.style.display = "block";
                    }
                }
            } catch (err) {
                // Fallback to normal form submit if fetch fails
                form.submit();
            }
        }
    </script>
</body>
</html>
