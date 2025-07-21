<?php
require_once 'config.php';

// ถ้าล็อกอินแล้วให้ไปหน้า admin
if (isset($_SESSION['user_id'])) {
    header('Location: admin/index.php');
    exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = sanitizeInput($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    if (empty($username) || empty($password)) {
        $error = 'กรุณากรอกข้อมูลให้ครบถ้วน';
    } else {
        // ตรวจสอบข้อมูลผู้ใช้
        $stmt = $pdo->prepare("SELECT id, username, password, role FROM users WHERE username = ? AND is_active = 1");
        $stmt->execute([$username]);
        $user = $stmt->fetch();

        if ($user && password_verify($password, $user['password'])) {
            // ล็อกอินสำเร็จ
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['role'] = $user['role'];

            // บันทึก login log
            $stmt = $pdo->prepare("INSERT INTO login_logs (user_id, ip_address, user_agent) VALUES (?, ?, ?)");
            $stmt->execute([
                $user['id'],
                $_SERVER['REMOTE_ADDR'],
                $_SERVER['HTTP_USER_AGENT'] ?? ''
            ]);

            // อัพเดท last login
            $stmt = $pdo->prepare("UPDATE users SET last_login = NOW() WHERE id = ?");
            $stmt->execute([$user['id']]);

            header('Location: admin/index.php');
            exit;
        } else {
            $error = 'ชื่อผู้ใช้หรือรหัสผ่านไม่ถูกต้อง';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="th">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>เข้าสู่ระบบ Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <style>
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .login-card {
            background: white;
            border-radius: 20px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.3);
            overflow: hidden;
            width: 100%;
            max-width: 400px;
        }

        .login-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 30px;
            text-align: center;
        }

        .login-body {
            padding: 40px;
        }

        .form-control {
            border-radius: 10px;
            border: 2px solid #e9ecef;
            padding: 15px;
        }

        .form-control:focus {
            border-color: #667eea;
            box-shadow: 0 0 0 0.2rem rgba(102, 126, 234, 0.25);
        }

        .btn-login {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border: none;
            border-radius: 10px;
            padding: 15px;
            font-weight: 500;
            transition: transform 0.2s;
        }

        .btn-login:hover {
            transform: translateY(-2px);
        }

        .input-group-text {
            background: transparent;
            border: none;
            color: #6c757d;
        }

        .alert {
            border-radius: 10px;
        }
    </style>
</head>

<body>
    <div class="login-card">
        <div class="login-header">
            <h2><i class="bi bi-shield-lock"></i> Admin Login</h2>
            <p class="mb-0">ระบบจัดการ URL Shortener</p>
        </div>

        <div class="login-body">
            <?php if ($error): ?>
                <div class="alert alert-danger" role="alert">
                    <i class="bi bi-exclamation-triangle"></i> <?php echo $error; ?>
                </div>
            <?php endif; ?>

            <form method="POST">
                <div class="mb-3">
                    <label for="username" class="form-label">ชื่อผู้ใช้</label>
                    <div class="input-group">
                        <span class="input-group-text"><i class="bi bi-person"></i></span>
                        <input type="text" class="form-control" id="username" name="username"
                            placeholder="กรอกชื่อผู้ใช้" required autofocus
                            value="<?php echo htmlspecialchars($username ?? ''); ?>">
                    </div>
                </div>

                <div class="mb-4">
                    <label for="password" class="form-label">รหัสผ่าน</label>
                    <div class="input-group">
                        <span class="input-group-text"><i class="bi bi-lock"></i></span>
                        <input type="password" class="form-control" id="password" name="password"
                            placeholder="กรอกรหัสผ่าน" required>
                    </div>
                </div>

                <div class="d-grid mb-3">
                    <button type="submit" class="btn btn-primary btn-login">
                        <i class="bi bi-box-arrow-in-right"></i> เข้าสู่ระบบ
                    </button>
                </div>

                <div class="text-center">
                    <a href="index.php" class="text-muted text-decoration-none">
                        <i class="bi bi-arrow-left"></i> กลับหน้าหลัก
                    </a>
                </div>
            </form>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>