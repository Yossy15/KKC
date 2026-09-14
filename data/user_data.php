<?php
/**
 * User Data Store & Auth Logic
 * Direct SQLite PDO
 */
require_once __DIR__ . '/db.php';

function find_user_by_login(string $identifier): ?array {
    $clean = strtolower(trim($identifier));
    $pdo = get_db_connection();
    if ($pdo && is_db_initialized($pdo)) {
        try {
            $stmt = $pdo->prepare("SELECT * FROM users WHERE LOWER(username) = ? OR LOWER(email) = ? LIMIT 1");
            $stmt->execute([$clean, $clean]);
            return $stmt->fetch() ?: null;
        } catch (Exception $e) {}
    }
    return null;
}

function authenticate_user(string $identifier, string $password): ?array {
    $user = find_user_by_login($identifier);
    if (!$user) {
        return null;
    }
    if (!empty($user['password']) && password_verify($password, $user['password'])) {
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
        'username' => $username,
        'name'     => $name ?: $username,
        'email'    => $email,
        'phone'    => $phone,
        'role'     => 'user',
        'status'   => 'active',
        'img'      => 'assets/profile-mock.png',
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
        } catch (Exception $e) {
            return ['success' => false, 'error' => 'เกิดข้อผิดพลาดในการบันทึกข้อมูล'];
        }
    }

    return ['success' => false, 'error' => 'ฐานข้อมูลไม่พร้อมใช้งาน'];
}

function update_user_address(int $userId, string $address): bool {
    $clean = trim($address);
    $pdo = get_db_connection();
    if ($pdo && is_db_initialized($pdo)) {
        try {
            $stmt = $pdo->prepare("UPDATE users SET address = ? WHERE id = ?");
            $stmt->execute([$clean, $userId]);
            if (isset($_SESSION['user']) && (int)($_SESSION['user']['id'] ?? 0) === $userId) {
                $_SESSION['user']['address'] = $clean;
            }
            return true;
        } catch (Exception $e) {}
    }
    return false;
}

function get_user_by_id(int $userId): ?array {
    $pdo = get_db_connection();
    if ($pdo && is_db_initialized($pdo)) {
        try {
            $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ? LIMIT 1");
            $stmt->execute([$userId]);
            return $stmt->fetch() ?: null;
        } catch (Exception $e) {}
    }
    return null;
}
