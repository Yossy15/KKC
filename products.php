<?php
/**
 * Products Listing & Filter Page
 */
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/data/products_data.php';

// Normalize GET array parameters (supports both product[]=shirt and product=shirt)
function normalize_param($val): array {
    if (empty($val)) return [];
    if (is_array($val)) return $val;
    return [$val];
}

$filterProduct = normalize_param($_GET['product'] ?? []);
$filterSize    = normalize_param($_GET['size'] ?? []);
$filterPrice   = normalize_param($_GET['price'] ?? []);
$filterStatus  = normalize_param($_GET['status'] ?? []);

// Default status: พร้อมส่ง if not explicitly specified
if (empty($filterStatus) && !isset($_GET['filter_applied'])) {
    $filterStatus = ['พร้อมส่ง'];
}

$page = max(1, (int)($_GET['page'] ?? 1));
$perPage = 15;

// Mobile Filter View Check
$isMobileFilterView = ($_GET['view'] ?? '') === 'filters';

$filteredProducts = filter_products([
    'product' => $filterProduct,
    'size'    => $filterSize,
    'price'   => $filterPrice,
    'status'  => $filterStatus,
]);

$totalItems = count($filteredProducts);
$totalPages = (int)ceil($totalItems / $perPage);
if ($page > $totalPages && $totalPages > 0) {
    $page = $totalPages;
}
$startIndex = ($page - 1) * $perPage;
$paginatedProducts = array_slice($filteredProducts, $startIndex, $perPage);

// Helper to check if a checkbox should be checked
function is_checked(string $field, string $val, array $current): string {
    return in_array($val, $current, true) ? 'checked' : '';
}

// Build URL for pagination while keeping filter query params
function pagination_url(int $p): string {
    $params = $_GET;
    $params['page'] = $p;
    unset($params['view']);
    return '?' . http_build_query($params);
}

$page_title = 'KKC - Filter Products';
$show_back_arrow = true;
$back_url = base_url('index.php');

include __DIR__ . '/includes/header.php';
?>

<?php if ($isMobileFilterView): ?>
    <!-- MOBILE FILTER VIEW -->
    <?php
    $breadcrumbs = [
        ['title' => 'Filter']
    ];
    include __DIR__ . '/includes/breadcrumb.php';
    ?>

    <section class="filter-wrapper">
        <div class="filter-container">
            <div class="divider-head">
                <h1 class="title">FILTER</h1>
                <?php include __DIR__ . '/includes/size-chart.php'; ?>
            </div>

            <form method="GET" action="<?= base_url('products.php') ?>" id="filterForm">
                <input type="hidden" name="filter_applied" value="1">
                
                <div class="filter-section">
                    <h3>สินค้า</h3>
                    <div class="checkbox-group">
                        <label><input type="checkbox" name="product[]" value="shirt" <?= is_checked('product', 'shirt', $filterProduct) ?> /> เสื้อ</label>
                        <label><input type="checkbox" name="product[]" value="pants" <?= is_checked('product', 'pants', $filterProduct) ?> /> กางเกง</label>
                        <label><input type="checkbox" name="product[]" value="skirt" <?= is_checked('product', 'skirt', $filterProduct) ?> /> กระโปรง</label>
                        <label><input type="checkbox" name="product[]" value="cap" <?= is_checked('product', 'cap', $filterProduct) ?> /> หมวก</label>
                    </div>
                </div>

                <div class="filter-section">
                    <h3>สถานะ</h3>
                    <div class="checkbox-group">
                        <label><input type="checkbox" name="status[]" value="ขายแล้ว" <?= is_checked('status', 'ขายแล้ว', $filterStatus) ?> /> ขายแล้ว</label>
                        <label><input type="checkbox" name="status[]" value="พร้อมส่ง" <?= is_checked('status', 'พร้อมส่ง', $filterStatus) ?> /> พร้อมส่ง</label>
                    </div>
                </div>

                <div class="filter-section">
                    <h3>ขนาด</h3>
                    <div class="checkbox-group">
                        <label><input type="checkbox" name="size[]" value="S" <?= is_checked('size', 'S', $filterSize) ?> /> S</label>
                        <label><input type="checkbox" name="size[]" value="M" <?= is_checked('size', 'M', $filterSize) ?> /> M</label>
                        <label><input type="checkbox" name="size[]" value="L" <?= is_checked('size', 'L', $filterSize) ?> /> L</label>
                    </div>
                </div>

                <div class="filter-section">
                    <h3>ราคา</h3>
                    <div class="checkbox-group">
                        <label><input type="checkbox" name="price[]" value="20" <?= is_checked('price', '20', $filterPrice) ?> /> 20 บาท</label>
                        <label><input type="checkbox" name="price[]" value="25" <?= is_checked('price', '25', $filterPrice) ?> /> 25 บาท</label>
                        <label><input type="checkbox" name="price[]" value="30" <?= is_checked('price', '30', $filterPrice) ?> /> 30 บาท</label>
                        <label><input type="checkbox" name="price[]" value="35" <?= is_checked('price', '35', $filterPrice) ?> /> 35 บาท</label>
                    </div>
                </div>

                <button type="submit" class="filter-btn">ค้นหา</button>
            </form>
        </div>
    </section>

<?php else: ?>
    <!-- DESKTOP / MAIN PRODUCTS VIEW -->
    <?php
    $breadcrumbs = [
        ['title' => 'Filter', 'url' => 'products.php'],
        ['title' => 'Products']
    ];
    include __DIR__ . '/includes/breadcrumb.php';
    ?>

    <section>
        <div class="fitter-row">
            <!-- Sidebar Filters -->
            <section class="fitter">
                <div class="divider-head">
                    <h1 class="title">FILTER</h1>
                    <?php include __DIR__ . '/includes/size-chart.php'; ?>
                </div>

                <form id="filterForm" method="GET" action="<?= base_url('products.php') ?>">
                    <input type="hidden" name="filter_applied" value="1">
                    
                    <div class="filter-section">
                        <h3>สินค้า</h3>
                        <div class="checkbox-form">
                            <label><input type="checkbox" name="product[]" value="shirt" onchange="this.form.submit()" <?= is_checked('product', 'shirt', $filterProduct) ?> /> เสื้อ</label>
                            <label><input type="checkbox" name="product[]" value="pants" onchange="this.form.submit()" <?= is_checked('product', 'pants', $filterProduct) ?> /> กางเกง</label>
                            <label><input type="checkbox" name="product[]" value="skirt" onchange="this.form.submit()" <?= is_checked('product', 'skirt', $filterProduct) ?> /> กระโปรง</label>
                            <label><input type="checkbox" name="product[]" value="cap" onchange="this.form.submit()" <?= is_checked('product', 'cap', $filterProduct) ?> /> หมวก</label>
                        </div>
                    </div>

                    <div class="filter-section">
                        <h3>สถานะ</h3>
                        <div class="checkbox-form">
                            <label><input type="checkbox" name="status[]" value="ขายแล้ว" onchange="this.form.submit()" <?= is_checked('status', 'ขายแล้ว', $filterStatus) ?> /> ขายแล้ว</label>
                            <label><input type="checkbox" name="status[]" value="พร้อมส่ง" onchange="this.form.submit()" <?= is_checked('status', 'พร้อมส่ง', $filterStatus) ?> /> พร้อมส่ง</label>
                        </div>
                    </div>

                    <div class="filter-section">
                        <h3>ขนาด</h3>
                        <div class="checkbox-form">
                            <label><input type="checkbox" name="size[]" value="S" onchange="this.form.submit()" <?= is_checked('size', 'S', $filterSize) ?> /> S</label>
                            <label><input type="checkbox" name="size[]" value="M" onchange="this.form.submit()" <?= is_checked('size', 'M', $filterSize) ?> /> M</label>
                            <label><input type="checkbox" name="size[]" value="L" onchange="this.form.submit()" <?= is_checked('size', 'L', $filterSize) ?> /> L</label>
                        </div>
                    </div>

                    <div class="filter-section">
                        <h3>ราคา</h3>
                        <div class="checkbox-form">
                            <label><input type="checkbox" name="price[]" value="20" onchange="this.form.submit()" <?= is_checked('price', '20', $filterPrice) ?> /> 20 บาท</label>
                            <label><input type="checkbox" name="price[]" value="25" onchange="this.form.submit()" <?= is_checked('price', '25', $filterPrice) ?> /> 25 บาท</label>
                            <label><input type="checkbox" name="price[]" value="30" onchange="this.form.submit()" <?= is_checked('price', '30', $filterPrice) ?> /> 30 บาท</label>
                            <label><input type="checkbox" name="price[]" value="35" onchange="this.form.submit()" <?= is_checked('price', '35', $filterPrice) ?> /> 35 บาท</label>
                        </div>
                    </div>
                </form>
            </section>

            <!-- Products Grid Data -->
            <section class="fitter-data">
                <div class="fitter-container-data-header divider-head">
                    <h1 class="title">PRODUCTS</h1>
                    <div class="filter-mobile">
                        <?php 
                        $filterMobileQuery = $_GET;
                        $filterMobileQuery['view'] = 'filters';
                        ?>
                        <a href="<?= base_url('products.php?' . http_build_query($filterMobileQuery)) ?>">
                            <img src="<?= asset_url('public/sort.svg') ?>" alt="filter" class="filter-icon" />
                        </a>
                    </div>
                </div>

                <div class="fitter-container-data" id="productsGrid">
                    <?php if (empty($paginatedProducts)): ?>
                        <p class="no-results">ไม่พบสินค้าที่ตรงกับเงื่อนไข</p>
                    <?php else: ?>
                        <?php foreach ($paginatedProducts as $product): ?>
                            <div class="product-card">
                                <?php include __DIR__ . '/includes/product-card.php'; ?>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>

                <!-- Pagination -->
                <?php if ($totalPages > 1): ?>
                    <div class="page-number">
                        <div class="page-number-container">
                            <!-- Prev Button -->
                            <?php if ($page > 1): ?>
                                <a href="<?= pagination_url($page - 1) ?>" class="page-number-button" aria-label="หน้าก่อนหน้า">
                                    <img src="<?= asset_url('public/arrow.svg') ?>" alt="arrow" class="arrow-left" />
                                </a>
                            <?php else: ?>
                                <button type="button" class="page-number-button" disabled aria-label="หน้าก่อนหน้า">
                                    <img src="<?= asset_url('public/arrow.svg') ?>" alt="arrow" class="arrow-left" />
                                </button>
                            <?php endif; ?>

                            <!-- Page Numbers -->
                            <?php
                            $maxVisible = 5;
                            $start = max(1, $page - (int)floor($maxVisible / 2));
                            $end = min($totalPages, $start + $maxVisible - 1);
                            if ($end - $start < $maxVisible - 1) {
                                $start = max(1, $end - $maxVisible + 1);
                            }
                            for ($i = $start; $i <= $end; $i++):
                            ?>
                                <a href="<?= pagination_url($i) ?>" class="page-number-button <?= ($i === $page) ? 'active' : '' ?>">
                                    <?= $i ?>
                                </a>
                            <?php endfor; ?>

                            <!-- Next Button -->
                            <?php if ($page < $totalPages): ?>
                                <a href="<?= pagination_url($page + 1) ?>" class="page-number-button" aria-label="หน้าถัดไป">
                                    <img src="<?= asset_url('public/arrow.svg') ?>" alt="arrow" class="arrow-right" />
                                </a>
                            <?php else: ?>
                                <button type="button" class="page-number-button" disabled aria-label="หน้าถัดไป">
                                    <img src="<?= asset_url('public/arrow.svg') ?>" alt="arrow" class="arrow-right" />
                                </button>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endif; ?>
            </section>
        </div>
    </section>
<?php endif; ?>

<?php include __DIR__ . '/includes/footer.php'; ?>
