<?php
/**
 * Core Helper Functions
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/**
 * Escape HTML output for XSS prevention
 */
function e(?string $value): string {
    return htmlspecialchars($value ?? '', ENT_QUOTES, 'UTF-8');
}

/**
 * Get base URL / path relative to KKC root
 */
function base_url(string $path = ''): string {
    static $base = null;
    if ($base === null) {
        $cleanScript = str_replace('\\', '/', $_SERVER['SCRIPT_NAME'] ?? '');
        $dir = str_replace('\\', '/', dirname($cleanScript));
        $dir = rtrim($dir, '/');
        $base = ($dir === '' || $dir === '/' || $dir === '.') ? '' : $dir;
    }
    $trimmed = ltrim(str_replace('\\', '/', $path), '/');
    return ($base ? $base . '/' : '/') . $trimmed;
}

/**
 * Asset URL helper (e.g. assets/logo.png or public/cart.svg)
 */
function asset_url(string $path): string {
    return base_url($path);
}

/**
 * CSRF Protection
 */
function csrf_token(): string {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function verify_csrf_token(?string $token): bool {
    if (empty($token) || empty($_SESSION['csrf_token'])) {
        return false;
    }
    return hash_equals($_SESSION['csrf_token'], $token);
}

/**
 * Auth state helpers
 */
function is_logged_in(): bool {
    return !empty($_SESSION['user']);
}

function current_user(): ?array {
    return $_SESSION['user'] ?? null;
}

function is_admin(): bool {
    $u = current_user();
    return !empty($u) && ($u['role'] ?? '') === 'admin';
}

function require_login(?string $redirectUrl = null): void {
    if (!is_logged_in()) {
        $target = $redirectUrl ?? ($_SERVER['REQUEST_URI'] ?? base_url('index.php'));
        if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) || isset($_GET['ajax']) || isset($_POST['ajax'])) {
            header('Content-Type: application/json', true, 401);
            echo json_encode(['success' => false, 'error' => 'กรุณาเข้าสู่ระบบก่อนดำเนินการ', 'require_login' => true]);
            exit;
        }
        header("Location: " . base_url('login.php?redirect=' . urlencode($target)));
        exit;
    }
}

function require_admin(): void {
    if (!is_logged_in()) {
        $target = $_SERVER['REQUEST_URI'] ?? base_url('admin.php');
        header("Location: " . base_url('login.php?redirect=' . urlencode($target)));
        exit;
    }
    if (!is_admin()) {
        http_response_code(403);
        $page_title = 'KKC - 403 เข้าถึงไม่ได้';
        include __DIR__ . '/header.php';
        echo '<main style="text-align: center; padding: 100px 20px; background: #fff; min-height: 80vh;"><h1 class="title">403 เข้าถึงไม่ได้</h1><p style="font-family: var(--font-krub); margin: 16px 0;">หน้านี้สงวนสิทธิ์สำหรับผู้ดูแลระบบ (Admin) เท่านั้น</p><a href="' . base_url('index.php') . '" class="auth-btn" style="max-width: 200px; display: inline-block; text-decoration: none;">กลับหน้าแรก</a></main>';
        include __DIR__ . '/footer.php';
        exit;
    }
}

/**
 * Path Guard Middleware
 * ป้องกัน:
 * 1. คนที่ไม่ใช่ admin ต้องเข้า admin ไม่ได้ (require_admin)
 * 2. คนที่ยังไม่ login จะเข้า cart, profile และ path อื่นๆ ไม่ได้ นอกจาก home และ product
 */
function enforce_path_guard(): void {
    if (php_sapi_name() === 'cli') {
        return;
    }

    $script = basename(parse_url(str_replace('\\', '/', $_SERVER['SCRIPT_NAME'] ?? $_SERVER['PHP_SELF'] ?? ''), PHP_URL_PATH) ?? '');

    // รายการหน้าที่อนุญาตให้เข้าถึงได้โดยไม่ต้องเข้าสู่ระบบ (Home, Product, Login, Logout)
    $publicPages = [
        'index.php',
        'products.php',
        'product-detail.php',
        'login.php',
        'logout.php'
    ];

    // 1. Admin Guard: คนที่ไม่ใช่ admin เข้า admin ไม่ได้
    if ($script === 'admin.php') {
        require_admin();
        return;
    }

    // 2. Auth Guard: คนที่ยังไม่ login จะเข้า cart, profile และ path อื่นๆ ไม่ได้ นอกจาก home และ product
    if (!in_array($script, $publicPages, true)) {
        require_login();
    }
}

enforce_path_guard();

function login_user(array $user): void {
    // Security: regenerate session ID on privilege / login change
    session_regenerate_id(true);
    $_SESSION['user'] = [
        'id'       => $user['id'],
        'username' => $user['username'],
        'name'     => $user['name'] ?? $user['username'],
        'email'    => $user['email'] ?? '',
        'phone'    => $user['phone'] ?? '',
        'role'     => $user['role'] ?? 'user',
        'status'   => $user['status'] ?? 'active',
        'img'      => !empty($user['img']) ? $user['img'] : 'assets/profile-mock.png',
    ];
}

function logout_user(): void {
    $_SESSION = [];
    if (ini_get("session.use_cookies")) {
        $params = session_get_cookie_params();
        setcookie(
            session_name(),
            '',
            time() - 42000,
            $params["path"],
            $params["domain"],
            $params["secure"],
            $params["httponly"]
        );
    }
    session_destroy();
}

/**
 * Format currency
 */
function format_price(float|int $price): string {
    return number_format($price, 0) . ' บาท';
}

/**
 * Shopping Cart Helpers
 */
function get_cart(): array {
    return $_SESSION['cart'] ?? [];
}

function add_to_cart(int $productId, int $quantity = 1): bool {
    require_once __DIR__ . '/../data/products_data.php';
    $product = get_product_by_id($productId);
    if (!$product || $product['status'] === 'ขายแล้ว') {
        return false;
    }

    if (!isset($_SESSION['cart'])) {
        $_SESSION['cart'] = [];
    }

    if (isset($_SESSION['cart'][$productId])) {
        $_SESSION['cart'][$productId]['quantity'] += $quantity;
    } else {
        $_SESSION['cart'][$productId] = [
            'id'       => $product['id'],
            'name'     => $product['name'],
            'price'    => (float)$product['price'],
            'image'    => $product['image'],
            'size'     => $product['size'],
            'color'    => $product['color'],
            'quantity' => $quantity,
        ];
    }
    return true;
}

function update_cart_quantity(int $productId, int $quantity): void {
    if ($quantity <= 0) {
        remove_from_cart($productId);
    } elseif (isset($_SESSION['cart'][$productId])) {
        $_SESSION['cart'][$productId]['quantity'] = $quantity;
    }
}

function remove_from_cart(int $productId): void {
    if (isset($_SESSION['cart'][$productId])) {
        unset($_SESSION['cart'][$productId]);
    }
}

function clear_cart(): void {
    $_SESSION['cart'] = [];
}

function get_cart_count(): int {
    $cart = get_cart();
    return array_sum(array_column($cart, 'quantity'));
}

function get_cart_total(): float {
    $cart = get_cart();
    $total = 0.0;
    foreach ($cart as $item) {
        $total += $item['price'] * $item['quantity'];
    }
    return $total;
}
