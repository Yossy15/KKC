<?php
/**
 * User Data Store & Auth Logic
 */

function get_mock_users(): array {
    static $baseUsers = null;
    if ($baseUsers === null) {
        $firstNames = ["สมชาย", "สมหญิง", "กิตติ", "อนันต์", "ธนภัทร", "ณัฐพล", "พิมพ์ชนก", "สุภาวดี", "ชลธิชา", "นภัสสร"];
        $lastNames  = ["ใจดี", "สุขใจ", "วงศ์ดี", "ศรีสุข", "ทองดี", "เจริญสุข", "บุญมี", "แสงทอง", "คำดี", "วัฒนา"];

        $baseUsers = [];
        for ($i = 1; $i <= 50; $i++) {
            $fn = $firstNames[($i * 3) % count($firstNames)];
            $ln = $lastNames[($i * 5) % count($lastNames)];
            $baseUsers["kkc{$i}"] = [
                'id'       => $i,
                'img'      => 'assets/profile-mock.png',
                'username' => "kkc{$i}",
                'name'     => "{$fn} {$ln}",
                'email'    => "kkc{$i}@g.co",
                'phone'    => '08' . str_pad((string)(10000000 + ($i * 1234567) % 90000000), 8, '0'),
                'role'     => $i <= 5 ? 'admin' : 'user',
                'status'   => 'active',
                'password' => '1234', // Default password for mock accounts
            ];
        }
    }

    // Merge with any users registered during this browser session
    $registered = $_SESSION['custom_users'] ?? [];
    return array_merge($baseUsers, $registered);
}

require_once __DIR__ . '/db.php';

function find_user_by_login(string $identifier): ?array {
    $clean = strtolower(trim($identifier));

    $pdo = get_db_connection();
    if ($pdo && is_db_initialized($pdo)) {
        try {
            $stmt = $pdo->prepare("SELECT * FROM users WHERE LOWER(username) = ? OR LOWER(email) = ? LIMIT 1");
            $stmt->execute([$clean, $clean]);
            $user = $stmt->fetch();
            if ($user) return $user;
        } catch (Exception $e) {}
    }

    $users = get_mock_users();
    foreach ($users as $user) {
        if (strtolower($user['username']) === $clean || strtolower($user['email']) === $clean) {
            return $user;
        }
    }
    return null;
}

function authenticate_user(string $identifier, string $password): ?array {
    $user = find_user_by_login($identifier);
    if (!$user) {
        return null;
    }
    // Check hashed password
    if (!empty($user['password'])) {
        if (password_verify($password, $user['password'])) {
            return $user;
        }
        // Backward compatibility with plain text passwords in mock
        if ($user['password'] === $password) {
            return $user;
        }
    }
    // Allow mock default password '1234'
    if ($password === '1234') {
        return $user;
    }
    return null;
}

function register_new_user(array $data): array {
    $username = trim($data['username'] ?? '');
    $email    = trim($data['email'] ?? '');
    $name     = trim($data['name'] ?? '');
    $phone    = trim($data['phone'] ?? '');
    $pass     = $data['password'] ?? '';

    if (empty($username) || empty($email) || empty($pass)) {
        return ['success' => false, 'error' => 'กรุณากรอกข้อมูลให้ครบถ้วน'];
    }

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        return ['success' => false, 'error' => 'รูปแบบอีเมลไม่ถูกต้อง'];
    }

    if (strlen($pass) < 4) {
        return ['success' => false, 'error' => 'รหัสผ่านต้องมีความยาวอย่างน้อย 4 ตัวอักษร'];
    }

    if (find_user_by_login($username) || find_user_by_login($email)) {
        return ['success' => false, 'error' => 'ชื่อผู้ใช้หรืออีเมลนี้มีอยู่ในระบบแล้ว'];
    }

    $hashedPass = password_hash($pass, PASSWORD_DEFAULT);
    $newUser = [
        'id'       => time(),
        'img'      => 'assets/profile-mock.png',
        'username' => $username,
        'name'     => $name ?: $username,
        'email'    => $email,
        'phone'    => $phone,
        'role'     => 'user',
        'status'   => 'active',
        'password' => $hashedPass,
    ];

    $pdo = get_db_connection();
    if ($pdo && is_db_initialized($pdo)) {
        try {
            $stmt = $pdo->prepare("
                INSERT INTO users (username, password, name, email, phone, role, status, img)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?)
            ");
            $stmt->execute([
                $username,
                $hashedPass,
                $name ?: $username,
                $email,
                $phone,
                'user',
                'active',
                'assets/profile-mock.png',
            ]);
            $newUser['id'] = (int)$pdo->lastInsertId();
            return ['success' => true, 'user' => $newUser];
        } catch (Exception $e) {}
    }

    if (!isset($_SESSION['custom_users'])) {
        $_SESSION['custom_users'] = [];
    }
    $_SESSION['custom_users'][$username] = $newUser;

    return ['success' => true, 'user' => $newUser];
}
