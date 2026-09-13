<?php
/**
 * Shared Footer & Base JavaScript
 */
$currentScript = basename($_SERVER['PHP_SELF'] ?? '');
$isHome = in_array($currentScript, ['index.php', ''], true);
$isProduct = in_array($currentScript, ['products.php', 'product-detail.php'], true);
$isCart = ($currentScript === 'cart.php');
$isProfile = in_array($currentScript, ['profile.php', 'login.php'], true);
$footerCartCount = function_exists('get_cart_count') ? get_cart_count() : 0;
$hasInnerTabBar = in_array($currentScript, ['profile.php', 'admin.php', 'product-detail.php'], true);
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

    <script>
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
