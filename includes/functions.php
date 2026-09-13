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
