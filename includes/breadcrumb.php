<?php
/**
 * Shared Breadcrumb Component
 * 
 * @param array $breadcrumbs e.g. [
 *   ['title' => 'Filter', 'url' => 'products.php'],
 *   ['title' => 'Products']
 * ]
 */
$breadcrumbs = $breadcrumbs ?? [];
?>
<section>
    <div class="path-home-container desktop-only">
        <a href="<?= base_url('index.php') ?>"><img src="<?= asset_url('public/home.svg') ?>" alt="home" /></a>
        <?php foreach ($breadcrumbs as $crumb): ?>
            <img src="<?= asset_url('public/arrow.svg') ?>" alt="arrow" class="arrow" />
            <?php if (!empty($crumb['url'])): ?>
                <a href="<?= base_url($crumb['url']) ?>" class="path-text"><?= e($crumb['title']) ?></a>
            <?php else: ?>
                <span class="path-text"><?= e($crumb['title']) ?></span>
            <?php endif; ?>
        <?php endforeach; ?>
    </div>
</section>
