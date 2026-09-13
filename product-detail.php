<?php
/**
 * Product Detail Page
 */
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/data/products_data.php';

$id = (int)($_GET['id'] ?? 0);
$product = get_product_by_id($id);

$page_title = $product ? "KKC - " . $product['name'] : 'KKC - Product Not Found';
$show_back_arrow = true;
$back_url = base_url('products.php');

include __DIR__ . '/includes/header.php';

$breadcrumbs = [
    ['title' => 'Products', 'url' => 'products.php'],
    ['title' => $product ? $product['name'] : 'Detail']
];
include __DIR__ . '/includes/breadcrumb.php';
?>

<?php if (!$product): ?>
    <main class="product-container" style="flex-direction: column; text-align: center; padding: 60px 20px;">
        <h1 class="title">ไม่พบสินค้าที่ต้องการ</h1>
        <p style="font-family: var(--font-krub); margin: 16px 0; color: #666;">สินค้าชิ้นนี้อาจถูกลบหรือไม่มีอยู่ในระบบ</p>
        <a href="<?= base_url('products.php') ?>" class="auth-btn" style="max-width: 240px; text-decoration: none; display: inline-block;">
            กลับสู่หน้ารายการสินค้า
        </a>
    </main>
<?php else: ?>
    <main class="product-container">
        <div class="product-image">
            <img id="productImage" src="<?= asset_url($product['image']) ?>" alt="<?= e($product['name']) ?>" class="logo" />
            <?php if ($product['status'] === 'ขายแล้ว'): ?>
                <img id="soldOverlay" src="<?= asset_url('assets/sell.png') ?>" alt="sold" class="sold-overlay-detail" />
            <?php endif; ?>
        </div>

        <div class="product-info-main">
            <div class="colunm-flex">
                <h1 class="title" id="productName"><?= e($product['name']) ?></h1>
            </div>

            <?php if (!empty($product['gallery'])): ?>
                <div class="product-gallery-main">
                    <button class="scroll-btn scroll-btn--left" id="galleryScrollLeft" aria-label="เลื่อนซ้าย" type="button">&#8249;</button>
                    <div class="product-gallery" id="productGallery">
                        <?php foreach ($product['gallery'] as $idx => $img): ?>
                            <img
                                src="<?= asset_url($img) ?>"
                                alt="<?= e($product['name']) ?>"
                                class="product-gallery-image <?= $idx === 0 ? 'active' : '' ?>"
                                data-image="<?= asset_url($img) ?>"
                                onclick="selectGalleryImage(<?= $idx ?>)"
                            />
                        <?php endforeach; ?>
                    </div>
                    <button class="scroll-btn scroll-btn--right" id="galleryScrollRight" aria-label="เลื่อนขวา" type="button">&#8250;</button>
                </div>
            <?php endif; ?>

<style>
/* Product Detail Action Bars */
.product-mobile-bottom-bar {
    display: none;
}

@media (max-width: 768px) {
    .product-actions-desktop {
        display: none !important;
    }

    .product-mobile-bottom-bar {
        display: flex !important;
        position: fixed !important;
        bottom: 0 !important;
        top: auto !important;
        left: 0 !important;
        right: 0 !important;
        width: 100% !important;
        height: 60px !important;
        background-color: var(--color-white) !important;
        border-top: 2px solid #e0e0e0 !important;
        box-shadow: 0 -2px 10px rgba(0, 0, 0, 0.08) !important;
        z-index: 99999 !important;
        align-items: center !important;
        justify-content: space-between !important;
        padding: 8px 16px !important;
        padding-bottom: max(8px, env(safe-area-inset-bottom)) !important;
        gap: 16px !important;
    }

    .product-mobile-price-group {
        display: flex;
        flex-direction: column;
        justify-content: center;
        min-width: 80px;
    }

    .product-mobile-price-label {
        font-size: 11px;
        color: #888;
        font-family: var(--font-krub);
    }

    .product-mobile-price-val {
        font-size: 18px;
        font-weight: 700;
        color: var(--color-black);
        font-family: var(--font-krub);
        line-height: 1.2;
    }

    .product-mobile-form {
        flex: 1;
        display: flex;
        justify-content: flex-end;
        margin: 0;
    }

    .product-mobile-cart-btn {
        width: 100%;
        max-width: 220px;
        height: 44px;
        margin: 0 !important;
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 8px;
        font-size: 15px;
    }

    .product-tab-container {
        margin-bottom: 75px !important;
    }

    footer {
        margin-bottom: 60px !important;
    }
}

@media (min-width: 769px) {
    .product-mobile-bottom-bar {
        display: none !important;
    }
}
</style>

            <div class="product-meta">
                <span class="product-size" id="productSize">ขนาด: <?= e($product['size'] ?: '-') ?></span>
                <span class="product-color" id="productColor">สี: <?= e($product['color'] ?: '-') ?></span>
                <span class="product-price" id="productPrice"><?= format_price($product['price']) ?></span>
            </div>

            <div class="product-actions-desktop">
                <?php if ($product['status'] === 'พร้อมส่ง'): ?>
                    <form method="POST" action="<?= base_url('cart.php') ?>" style="display: flex; gap: 12px; align-items: center; justify-content: center; margin-top: 8px;">
                        <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
                        <input type="hidden" name="action" value="add">
                        <input type="hidden" name="product_id" value="<?= $product['id'] ?>">
                        <button type="submit" class="auth-btn" style="max-width: 240px; margin: 0; display: flex; align-items: center; justify-content: center; gap: 8px;">
                            <img src="<?= asset_url('public/cart.svg') ?>" alt="cart" style="width: 18px; filter: invert(1);">
                            <span>เพิ่มลงตะกร้า</span>
                        </button>
                    </form>
                <?php else: ?>
                    <div style="text-align: center; margin-top: 8px;">
                        <span class="order-badge badge-cancelled" style="font-size: 14px; padding: 8px 16px;">สินค้านี้ขายแล้ว</span>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </main>

    <section class="product-tab-container">
        <div class="product-tabs">
            <button type="button" class="tab-button active title" onclick="showTab('description', this)">
                Description
            </button>
            <button type="button" class="tab-button title" onclick="showTab('info', this)">
                Product Info
            </button>
        </div>

        <div id="description" class="tab-content active">
            <label class="description-sub"> - ไม่มีตำหนิ -</label>
        </div>

        <div id="info" class="tab-content">
            <table class="product-table" id="productInfoTable">
                <tr>
                    <th>รหัสสินค้า</th>
                    <td id="infoId">P<?= str_pad((string)$product['id'], 5, '0', STR_PAD_LEFT) ?></td>
                </tr>
                <tr>
                    <th>หมวดหมู่</th>
                    <td>
                        <a href="<?= base_url('products.php?product[]=' . urlencode($product['type'])) ?>" id="infoCategory" style="color: var(--color-info);">
                            <?= e(get_category_label($product['type'])) ?>
                        </a>
                    </td>
                </tr>
                <tr>
                    <th>ชื่อสินค้า</th>
                    <td id="infoName"><?= e($product['name']) ?></td>
                </tr>
                <tr>
                    <th>ขนาด</th>
                    <td id="infoSize"><?= e($product['size'] ?: '-') ?></td>
                </tr>
                <tr>
                    <th>สี</th>
                    <td id="infoColor"><?= e($product['color'] ?: '-') ?></td>
                </tr>
                <tr>
                    <th>ราคา</th>
                    <td id="infoPrice"><?= format_price($product['price']) ?></td>
                </tr>
                <tr>
                    <th>สถานะ</th>
                    <td id="infoStatus" data-status="<?= e($product['status']) ?>"><?= e($product['status']) ?></td>
                </tr>
            </table>
        </div>
    </section>

    <script>
        const productImage = document.getElementById("productImage");
        const productGallery = document.getElementById("productGallery");
        const galleryBtnL = document.getElementById("galleryScrollLeft");
        const galleryBtnR = document.getElementById("galleryScrollRight");
        const galleryImages = productGallery ? Array.from(productGallery.querySelectorAll(".product-gallery-image")) : [];
        let currentGalleryIndex = 0;

        function updateGalleryButtons() {
            if (!galleryBtnL || !galleryBtnR) return;
            if (galleryImages.length <= 1) {
                galleryBtnL.style.display = "none";
                galleryBtnR.style.display = "none";
                return;
            }
            galleryBtnL.style.display = currentGalleryIndex <= 0 ? "none" : "flex";
            galleryBtnR.style.display = currentGalleryIndex >= galleryImages.length - 1 ? "none" : "flex";
        }

        function selectGalleryImage(index) {
            if (!galleryImages.length) return;
            currentGalleryIndex = Math.max(0, Math.min(index, galleryImages.length - 1));

            const activeThumb = galleryImages[currentGalleryIndex];
            if (productImage && activeThumb) {
                productImage.src = activeThumb.dataset.image;
            }

            galleryImages.forEach((img) => img.classList.remove("active"));
            activeThumb.classList.add("active");

            if (productGallery) {
                const left = activeThumb.offsetLeft - (productGallery.clientWidth / 2) + (activeThumb.offsetWidth / 2);
                productGallery.scrollTo({
                    left: Math.max(0, left),
                    behavior: "smooth",
                });
            }

            updateGalleryButtons();
        }

        if (galleryBtnL && galleryBtnR) {
            galleryBtnL.onclick = () => selectGalleryImage(currentGalleryIndex - 1);
            galleryBtnR.onclick = () => selectGalleryImage(currentGalleryIndex + 1);
            updateGalleryButtons();
        }

        function showTab(tabId, button) {
            document.querySelectorAll(".tab-content").forEach((content) => {
                content.classList.remove("active");
            });
            document.querySelectorAll(".tab-button").forEach((btn) => {
                btn.classList.remove("active");
            });
            const target = document.getElementById(tabId);
            if (target) target.classList.add("active");
            if (button) button.classList.add("active");
        }
    </script>

    <!-- Mobile Bottom Action Bar (แทนที่ mobile-footer-tab-bar สำหรับหน้า Product Detail) -->
    <div class="product-mobile-bottom-bar" role="region" aria-label="Action Bar">
        <?php if ($product['status'] === 'พร้อมส่ง'): ?>
            <div class="product-mobile-price-group">
                <span class="product-mobile-price-label">ราคา</span>
                <span class="product-mobile-price-val"><?= format_price($product['price']) ?></span>
            </div>
            <form method="POST" action="<?= base_url('cart.php') ?>" class="product-mobile-form">
                <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
                <input type="hidden" name="action" value="add">
                <input type="hidden" name="product_id" value="<?= $product['id'] ?>">
                <button type="submit" class="auth-btn product-mobile-cart-btn">
                    <img src="<?= asset_url('public/cart.svg') ?>" alt="cart" style="width: 18px; filter: invert(1);">
                    <span>เพิ่มลงตะกร้า</span>
                </button>
            </form>
        <?php else: ?>
            <div style="width: 100%; display: flex; justify-content: center; align-items: center;">
                <span class="order-badge badge-cancelled" style="font-size: 15px; padding: 8px 24px; font-weight: 700;">สินค้านี้ขายแล้ว</span>
            </div>
        <?php endif; ?>
    </div>
<?php endif; ?>

<?php include __DIR__ . '/includes/footer.php'; ?>
