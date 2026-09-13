<?php
/**
 * Migration Self-Check (Minimal assertions)
 */
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/data/products_data.php';
require_once __DIR__ . '/data/user_data.php';
require_once __DIR__ . '/data/order_data.php';

echo "Running Migration Self-Check...\n";

// 1. Products test
$allProducts = get_all_products();
assert(count($allProducts) === 100, "Should have 100 products");

$shirt = get_product_by_id(1);
assert($shirt !== null && isset($shirt['name'], $shirt['price'], $shirt['gallery']), "Product 1 should be valid");

$filteredShirts = filter_products(['product' => ['shirt']]);
assert(count($filteredShirts) > 0, "Should filter shirts");
foreach ($filteredShirts as $p) {
    assert($p['type'] === 'shirt', "Each filtered item must be shirt");
}

// 2. User test
$user = authenticate_user('kkc1', '1234');
assert($user !== null && $user['username'] === 'kkc1', "User kkc1 should authenticate");

$invalid = authenticate_user('kkc1', 'wrongpass');
assert($invalid === null, "Wrong password should fail");

// 3. Orders test
$orders = get_orders_by_user(1);
assert(count($orders) > 0, "Should get orders for user");

// 4. Output escaping & URL normalization test
assert(e('<script>alert(1)</script>') === '&lt;script&gt;alert(1)&lt;/script&gt;', "Output escaping must prevent XSS");
$styleUrl = asset_url('style/style.css');
assert(!str_contains($styleUrl, '\\'), "Asset URL must not contain backslash: {$styleUrl}");
assert(str_ends_with($styleUrl, '/style/style.css'), "Asset URL must properly end with /style/style.css: {$styleUrl}");


// 5. CSRF Protection test
$token = csrf_token();
assert(!empty($token) && strlen($token) === 64, "CSRF token should be 64-char hex");
assert(verify_csrf_token($token) === true, "Valid CSRF token should verify");
assert(verify_csrf_token('fake_token') === false, "Fake CSRF token should be rejected");

// 6. Registration with password hashing & DB persistence test
$testUsername = 'testuser_' . time();
$reg = register_new_user([
    'username' => $testUsername,
    'name'     => 'Test User',
    'email'    => 'test_' . time() . '@example.com',
    'phone'    => '0812345678',
    'password' => 'secret123'
]);
assert($reg['success'] === true, "User registration should succeed");
assert(password_verify('secret123', $reg['user']['password']), "Password must be securely hashed");

// 7. Verify persistent retrieval of newly registered user
$retrieved = find_user_by_login($testUsername);
assert($retrieved !== null && $retrieved['username'] === $testUsername, "User must be retrievable from persistent storage");

// 8. Shopping Cart test
clear_cart();
assert(get_cart_count() === 0, "Cart should initially be empty");
$added = add_to_cart(1, 2);
assert($added === true, "Should add available product to cart");
assert(get_cart_count() === 2, "Cart count should be 2");
assert(get_cart_total() > 0, "Cart total should be positive");
remove_from_cart(1);
assert(get_cart_count() === 0, "Cart should be empty after removal");

// 9. Admin role check test
$adminUser = find_user_by_login('kkc1');
assert($adminUser !== null && $adminUser['role'] === 'admin', "kkc1 should have admin role");

echo "All checks passed successfully! (100% OK)\n";



