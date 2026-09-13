<?php
/**
 * Authentication Page & Endpoint
 */
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/data/user_data.php';

$action = $_GET['action'] ?? '';
$isAjax = isset($_GET['ajax']) || (isset($_SERVER['HTTP_ACCEPT']) && str_contains($_SERVER['HTTP_ACCEPT'], 'application/json'));
$redirect = $_POST['redirect'] ?? $_GET['redirect'] ?? base_url('index.php');

// Prevent redirect loop or open redirect
if (!str_starts_with($redirect, '/') && !str_starts_with($redirect, base_url())) {
    $redirect = base_url('index.php');
}

$error = '';
$activeTab = $_GET['tab'] ?? 'login';

// If already logged in and not an action
if (is_logged_in() && empty($action)) {
    header("Location: " . base_url('index.php'));
    exit;
}

// Handle Form Submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $submittedToken = $_POST['csrf_token'] ?? '';
    if (!verify_csrf_token($submittedToken)) {
        $error = 'ความปลอดภัย: โทเค็นไม่ถูกต้องหรือหมดอายุ กรุณารีเฟรชแล้วลองใหม่';
        if ($isAjax) {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'error' => $error]);
            exit;
        }
    } elseif ($action === 'login') {
        $identifier = trim($_POST['username'] ?? '');
        $password   = $_POST['password'] ?? '';

        $user = authenticate_user($identifier, $password);
        if ($user) {
            login_user($user);
            if ($isAjax) {
                header('Content-Type: application/json');
                echo json_encode(['success' => true, 'redirect' => $redirect]);
                exit;
            }
            header("Location: " . $redirect);
            exit;
        } else {
            $error = 'ชื่อผู้ใช้หรือรหัสผ่านไม่ถูกต้อง';
            if ($isAjax) {
                header('Content-Type: application/json');
                echo json_encode(['success' => false, 'error' => $error]);
                exit;
            }
        }
    } elseif ($action === 'register') {
        $activeTab = 'register';
        $pass        = $_POST['password'] ?? '';
        $confirmPass = $_POST['confirm_password'] ?? '';

        if ($pass !== $confirmPass) {
            $error = 'รหัสผ่านและยืนยันรหัสผ่านไม่ตรงกัน';
        } else {
            $result = register_new_user([
                'name'     => $_POST['name'] ?? '',
                'username' => $_POST['username'] ?? '',
                'email'    => $_POST['email'] ?? '',
                'phone'    => $_POST['phone'] ?? '',
                'password' => $pass,
            ]);

            if ($result['success']) {
                login_user($result['user']);
                if ($isAjax) {
                    header('Content-Type: application/json');
                    echo json_encode(['success' => true, 'redirect' => $redirect]);
                    exit;
                }
                header("Location: " . $redirect);
                exit;
            } else {
                $error = $result['error'];
            }
        }

        if ($isAjax) {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'error' => $error]);
            exit;
        }
    }
}

$page_title = 'KKC - เข้าสู่ระบบ / สมัครสมาชิก';
$show_back_arrow = true;
$back_url = base_url('index.php');

include __DIR__ . '/includes/header.php';
?>

<main class="auth-wrapper" style="min-height: calc(100vh - 70px); padding: 0; margin: 0; width: 100%; max-width: 100%; display: flex; align-items: center; justify-content: center; background-color: var(--color-white);">
    <div class="auth-container" style="width: 100%; max-width: 100%; min-height: calc(100vh - 70px); border-radius: 0; box-shadow: none; display: flex; flex-direction: column; justify-content: center; align-items: center; padding: 40px 20px;">
        <div style="width: 100%; max-width: 460px;">
            <div class="auth-tabs">
                <button type="button" class="auth-tab <?= $activeTab === 'login' ? 'active' : '' ?>" id="pageTabLoginBtn" onclick="switchPageAuthTab('login')">
                    เข้าสู่ระบบ
                </button>
                <button type="button" class="auth-tab <?= $activeTab === 'register' ? 'active' : '' ?>" id="pageTabRegisterBtn" onclick="switchPageAuthTab('register')">
                    สมัครสมาชิก
                </button>
            </div>

        <?php if (!empty($error)): ?>
            <div class="auth-error" style="display: block; margin-bottom: 16px;"><?= e($error) ?></div>
        <?php endif; ?>

        <!-- Login Form -->
        <form id="pageLoginForm" class="auth-form" style="<?= $activeTab === 'login' ? '' : 'display: none;' ?>" method="POST" action="<?= base_url('login.php?action=login') ?>">
            <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
            <input type="hidden" name="redirect" value="<?= e($redirect) ?>">
            <div class="auth-field">
                <label for="loginUser">ชื่อผู้ใช้ หรือ อีเมล</label>
                <input type="text" name="username" id="loginUser" class="auth-input" placeholder="เช่น kkc1 หรือ kkc1@g.co" required />
            </div>
            <div class="auth-field">
                <label for="loginPass">รหัสผ่าน</label>
                <input type="password" name="password" id="loginPass" class="auth-input" placeholder="รหัสผ่าน (mock: 1234)" required />
            </div>
            <button type="submit" class="auth-btn">เข้าสู่ระบบ</button>
            <div class="auth-switch-text">
                ยังไม่มีบัญชี? <a href="javascript:void(0)" onclick="switchPageAuthTab('register')">สมัครสมาชิก</a>
            </div>
        </form>

        <!-- Register Form -->
        <form id="pageRegisterForm" class="auth-form" style="<?= $activeTab === 'register' ? '' : 'display: none;' ?>" method="POST" action="<?= base_url('login.php?action=register') ?>">
            <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
            <input type="hidden" name="redirect" value="<?= e($redirect) ?>">
            <div class="auth-field">
                <label for="regName">ชื่อ - นามสกุล</label>
                <input type="text" name="name" id="regName" class="auth-input" placeholder="เช่น สมชาย ใจดี" required />
            </div>
            <div class="auth-field">
                <label for="regUsername">ชื่อผู้ใช้</label>
                <input type="text" name="username" id="regUsername" class="auth-input" placeholder="เช่น somchai99" required />
            </div>
            <div class="auth-field">
                <label for="regEmail">อีเมล</label>
                <input type="email" name="email" id="regEmail" class="auth-input" placeholder="เช่น somchai@example.com" required />
            </div>
            <div class="auth-field">
                <label for="regPhone">เบอร์โทรศัพท์</label>
                <input type="tel" name="phone" id="regPhone" class="auth-input" placeholder="เช่น 0812345678" required />
            </div>
            <div class="auth-field">
                <label for="regPass">รหัสผ่าน</label>
                <input type="password" name="password" id="regPass" class="auth-input" placeholder="อย่างน้อย 4 ตัวอักษร" minlength="4" required />
            </div>
            <div class="auth-field">
                <label for="regConfirmPass">ยืนยันรหัสผ่าน</label>
                <input type="password" name="confirm_password" id="regConfirmPass" class="auth-input" placeholder="กรอกรหัสผ่านอีกครั้ง" required />
            </div>
            <button type="submit" class="auth-btn">สมัครสมาชิก</button>
            <div class="auth-switch-text">
                มีบัญชีอยู่แล้ว? <a href="javascript:void(0)" onclick="switchPageAuthTab('login')">เข้าสู่ระบบ</a>
            </div>
        </form>
        </div>
    </div>
</main>

<script>
    function switchPageAuthTab(tab) {
        const loginForm = document.getElementById("pageLoginForm");
        const registerForm = document.getElementById("pageRegisterForm");
        const tabLoginBtn = document.getElementById("pageTabLoginBtn");
        const tabRegisterBtn = document.getElementById("pageTabRegisterBtn");

        if (tab === "register") {
            loginForm.style.display = "none";
            registerForm.style.display = "flex";
            tabLoginBtn.classList.remove("active");
            tabRegisterBtn.classList.add("active");
        } else {
            loginForm.style.display = "flex";
            registerForm.style.display = "none";
            tabLoginBtn.classList.add("active");
            tabRegisterBtn.classList.remove("active");
        }
    }
</script>

<?php include __DIR__ . '/includes/footer.php'; ?>
