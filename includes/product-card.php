<?php
/**
 * Shared Product Card Component
 * 
 * @param array $product
 */
if (!isset($product)) return;
?>
<div class="merch-container-card">
    <img src="<?= asset_url($product['image']) ?>" alt="<?= e($product['type']) ?>" class="merch-img">
    <?php if ($product['status'] === 'ขายแล้ว'): ?>
        <img src="<?= asset_url('assets/sell.png') ?>" alt="sold" class="sold-overlay">
    <?php endif; ?>
    <div class="merch-overlay-search">
        <a href="<?= base_url('product-detail.php?id=' . $product['id']) ?>" class="circle">
            <img src="<?= asset_url('public/search.svg') ?>" alt="search" class="merch-img-search">
        </a>
    </div>
    <div class="merch-overlay">
        <span class="merch-overlay-title"><?= e($product['name']) ?></span>
        <span class="merch-overlay-sub">ขนาด: <?= e($product['size']) ?></span>
        <div class="merch-overlay-sub-detail">
            <span class="merch-overlay-sub">สี: <?= e($product['color']) ?></span>
            <span class="merch-overlay-sub"><?= e($product['price']) ?> บาท</span>
        </div>
        <a href="<?= base_url('product-detail.php?id=' . $product['id']) ?>" class="merch-overlay-sub">ดูรายละเอียด</a>
    </div>
</div>
