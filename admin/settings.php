<?php
require_once '../config.php';

// ตรวจสอบการล็อกอิน
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: ../login.php');
    exit;
}

$message = '';
$messageType = '';

// บันทึกการตั้งค่า
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // อัพเดทข้อมูล Admin
        if (isset($_POST['update_profile'])) {
            $username = sanitizeInput($_POST['username']);
            $email = sanitizeInput($_POST['email']);

            // ตรวจสอบว่า username ซ้ำหรือไม่
            $stmt = $pdo->prepare("SELECT id FROM users WHERE username = ? AND id != ?");
            $stmt->execute([$username, $_SESSION['user_id']]);
            if ($stmt->fetch()) {
                throw new Exception('Username นี้ถูกใช้แล้ว');
            }

            // ตรวจสอบว่า email ซ้ำหรือไม่
            $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ? AND id != ?");
            $stmt->execute([$email, $_SESSION['user_id']]);
            if ($stmt->fetch()) {
                throw new Exception('Email นี้ถูกใช้แล้ว');
            }

            $stmt = $pdo->prepare("UPDATE users SET username = ?, email = ? WHERE id = ?");
            $stmt->execute([$username, $email, $_SESSION['user_id']]);

            $_SESSION['username'] = $username;
            $message = "อัพเดทข้อมูลส่วนตัวสำเร็จ";
            $messageType = 'success';
        }

        // เปลี่ยนรหัสผ่าน
        if (isset($_POST['change_password'])) {
            $currentPassword = $_POST['current_password'];
            $newPassword = $_POST['new_password'];
            $confirmPassword = $_POST['confirm_password'];

            // ตรวจสอบรหัสผ่านปัจจุบัน
            $stmt = $pdo->prepare("SELECT password FROM users WHERE id = ?");
            $stmt->execute([$_SESSION['user_id']]);
            $user = $stmt->fetch();

            if (!password_verify($currentPassword, $user['password'])) {
                throw new Exception('รหัสผ่านปัจจุบันไม่ถูกต้อง');
            }

            if ($newPassword !== $confirmPassword) {
                throw new Exception('รหัสผ่านใหม่ไม่ตรงกัน');
            }

            if (strlen($newPassword) < 6) {
                throw new Exception('รหัสผ่านต้องมีอย่างน้อย 6 ตัวอักษร');
            }

            $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("UPDATE users SET password = ? WHERE id = ?");
            $stmt->execute([$hashedPassword, $_SESSION['user_id']]);

            $message = "เปลี่ยนรหัสผ่านสำเร็จ";
            $messageType = 'success';
        }

        // ล้างข้อมูล (แก้ไขชื่อตารางและฟิลด์)
        if (isset($_POST['cleanup'])) {
            $cleanupType = $_POST['cleanup_type'];
            $days = (int)$_POST['cleanup_days'];

            switch ($cleanupType) {
                case 'expired':
                    $stmt = $pdo->prepare("
                        DELETE FROM short_urls 
                        WHERE expires_at IS NOT NULL 
                        AND expires_at != '0000-00-00 00:00:00' 
                        AND expires_at < NOW()
                    ");
                    $stmt->execute();
                    $deletedCount = $stmt->rowCount();
                    $message = "ลบลิงก์ที่หมดอายุแล้ว $deletedCount รายการ";
                    break;

                case 'inactive':
                    $stmt = $pdo->prepare("
                        DELETE FROM short_urls 
                        WHERE created_at < DATE_SUB(NOW(), INTERVAL ? DAY) 
                        AND clicks = 0
                    ");
                    $stmt->execute([$days]);
                    $deletedCount = $stmt->rowCount();
                    $message = "ลบลิงก์ที่ไม่มีการใช้งาน $deletedCount รายการ";
                    break;

                case 'logs':
                    $stmt = $pdo->prepare("
                        DELETE FROM click_stats 
                        WHERE clicked_at < DATE_SUB(NOW(), INTERVAL ? DAY)
                    ");
                    $stmt->execute([$days]);
                    $deletedCount = $stmt->rowCount();
                    $message = "ล้างประวัติการคลิก $deletedCount รายการ";
                    break;

                case 'qr_cache':
                    $deleted = 0;
                    $qrFiles = glob(QR_CACHE_DIR . '*.png');
                    foreach ($qrFiles as $file) {
                        if (filemtime($file) < (time() - ($days * 24 * 3600))) {
                            unlink($file);
                            $deleted++;
                        }
                    }
                    $message = "ล้างไฟล์ QR Code $deleted ไฟล์";
                    break;
            }

            $messageType = 'success';
        }

        // สร้าง config file
        if (isset($_POST['generate_config'])) {
            $configContent = generateConfigFile();
            file_put_contents('../config_backup.php', $configContent);
            $message = "สร้างไฟล์ config สำรองสำเร็จ";
            $messageType = 'success';
        }
    } catch (Exception $e) {
        $message = $e->getMessage();
        $messageType = 'danger';
    }
}

// ดึงข้อมูล admin
$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$_SESSION['user_id']]);
$admin = $stmt->fetch();

// ดึงสถิติระบบ
$stats = [];

// ขนาดฐานข้อมูล
try {
    $stmt = $pdo->query("
        SELECT 
            table_name,
            ROUND(((data_length + index_length) / 1024 / 1024), 2) AS size_mb,
            table_rows
        FROM information_schema.TABLES 
        WHERE table_schema = '" . DB_NAME . "'
        ORDER BY (data_length + index_length) DESC
    ");
    $tableStats = $stmt->fetchAll();
} catch (Exception $e) {
    $tableStats = [];
}

// จำนวนไฟล์ในระบบ
$uploadFiles = is_dir(UPLOAD_DIR) ? count(glob(UPLOAD_DIR . '*')) : 0;
$qrFiles = is_dir(QR_CACHE_DIR) ? count(glob(QR_CACHE_DIR . '*')) : 0;

// ลิงก์ที่หมดอายุ
$stmt = $pdo->query("
    SELECT COUNT(*) FROM short_urls 
    WHERE expires_at IS NOT NULL 
    AND expires_at != '0000-00-00 00:00:00' 
    AND expires_at < NOW()
");
$expiredUrls = $stmt->fetchColumn();

// ลิงก์ที่ไม่ active
$stmt = $pdo->query("
    SELECT COUNT(*) FROM short_urls 
    WHERE created_at < DATE_SUB(NOW(), INTERVAL 30 DAY) 
    AND clicks = 0
");
$inactiveUrls = $stmt->fetchColumn();

// สถิติทั่วไป
$stmt = $pdo->query("SELECT COUNT(*) FROM short_urls");
$totalUrls = $stmt->fetchColumn();

$stmt = $pdo->query("SELECT COUNT(*) FROM users");
$totalUsers = $stmt->fetchColumn();

$stmt = $pdo->query("SELECT COUNT(*) FROM click_stats");
$totalClicks = $stmt->fetchColumn();

// ฟังก์ชันสร้าง config file
function generateConfigFile()
{
    $content = "<?php\n";
    $content .= "// Auto-generated config backup - " . date('Y-m-d H:i:s') . "\n\n";
    $content .= "// Database Settings\n";
    $content .= "define('DB_HOST', '" . DB_HOST . "');\n";
    $content .= "define('DB_NAME', '" . DB_NAME . "');\n";
    $content .= "define('DB_USER', '" . DB_USER . "');\n";
    $content .= "define('DB_PASS', '" . DB_PASS . "');\n\n";
    $content .= "// Site Settings\n";
    $content .= "define('SITE_URL', '" . SITE_URL . "');\n";
    $content .= "define('SHORT_URL_LENGTH', " . SHORT_URL_LENGTH . ");\n";
    $content .= "define('API_RATE_LIMIT', " . API_RATE_LIMIT . ");\n";
    $content .= "?>";
    return $content;
}
?>
<!DOCTYPE html>
<html lang="th">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Settings - Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <style>
        body {
            background-color: #f8f9fa;
        }

        .sidebar {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            box-shadow: 2px 0 10px rgba(0, 0, 0, 0.1);
        }

        .sidebar .nav-link {
            color: rgba(255, 255, 255, 0.8);
            padding: 15px 20px;
            transition: all 0.3s;
        }

        .sidebar .nav-link:hover {
            color: white;
            background: rgba(255, 255, 255, 0.1);
            padding-left: 30px;
        }

        .sidebar .nav-link.active {
            color: white;
            background: rgba(255, 255, 255, 0.2);
            border-left: 4px solid white;
        }

        .settings-card {
            background: white;
            border-radius: 10px;
            padding: 25px;
            margin-bottom: 20px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
        }

        .stat-box {
            background: #f8f9fa;
            border-radius: 10px;
            padding: 20px;
            text-align: center;
            margin-bottom: 15px;
            transition: transform 0.2s;
        }

        .stat-box:hover {
            transform: translateY(-2px);
        }

        .stat-box h4 {
            color: #667eea;
            margin-bottom: 5px;
            font-size: 1.5rem;
        }

        .danger-zone {
            border: 2px solid #dc3545;
            border-radius: 10px;
            padding: 20px;
            background: #fff5f5;
        }

        .system-info {
            font-size: 0.9rem;
        }
    </style>
</head>

<body>
    <div class="container-fluid">
        <div class="row">
            <!-- Sidebar -->
            <div class="col-md-3 col-lg-2 px-0 sidebar">
                <div class="p-3 text-white">
                    <h4 class="mb-0"><i class="bi bi-speedometer2"></i> Admin Panel</h4>
                    <small>สวัสดี, <?php echo htmlspecialchars($_SESSION['username']); ?></small>
                </div>
                <nav class="nav flex-column">
                    <a class="nav-link" href="index.php">
                        <i class="bi bi-house"></i> Dashboard
                    </a>
                    <a class="nav-link" href="urls.php">
                        <i class="bi bi-link-45deg"></i> จัดการลิงก์
                    </a>
                    <a class="nav-link" href="users.php">
                        <i class="bi bi-people"></i> จัดการผู้ใช้
                    </a>
                    <a class="nav-link" href="analytics.php">
                        <i class="bi bi-graph-up"></i> Analytics
                    </a>
                    <a class="nav-link" href="api-keys.php">
                        <i class="bi bi-key"></i> API Keys
                    </a>
                    <a class="nav-link" href="bulk-import.php">
                        <i class="bi bi-cloud-upload"></i> Bulk Import
                    </a>
                    <a class="nav-link active" href="settings.php">
                        <i class="bi bi-gear"></i> ตั้งค่า
                    </a>
                    <hr class="bg-white">
                    <a class="nav-link" href="../logout.php">
                        <i class="bi bi-box-arrow-left"></i> ออกจากระบบ
                    </a>
                    <a class="nav-link" href="../index.php">
                        <i class="bi bi-arrow-left"></i> กลับหน้าหลัก
                    </a>
                </nav>
            </div>
            <!-- Main Content -->
            <div class="col-md-9 col-lg-10 py-4">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h1>System Settings</h1>
                    <span class="badge bg-primary">Version 1.0</span>
                </div>

                <?php if ($message): ?>
                    <div class="alert alert-<?php echo $messageType; ?> alert-dismissible fade show" role="alert">
                        <?php echo $message; ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>

                <div class="row">
                    <!-- Left Column -->
                    <div class="col-md-8">
                        <!-- Profile Settings -->
                        <div class="settings-card">
                            <h5 class="mb-4">
                                <i class="bi bi-person-circle"></i> ข้อมูลส่วนตัว
                            </h5>
                            <form method="POST">
                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label">Username</label>
                                        <input type="text" name="username" class="form-control"
                                            value="<?php echo htmlspecialchars($admin['username']); ?>" required>
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label">Email</label>
                                        <input type="email" name="email" class="form-control"
                                            value="<?php echo htmlspecialchars($admin['email']); ?>" required>
                                    </div>
                                </div>
                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label">Role</label>
                                        <input type="text" class="form-control"
                                            value="<?php echo ucfirst($admin['role']); ?>" readonly>
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label">Last Login</label>
                                        <input type="text" class="form-control"
                                            value="<?php echo $admin['last_login'] ? date('d/m/Y H:i', strtotime($admin['last_login'])) : 'N/A'; ?>"
                                            readonly>
                                    </div>
                                </div>
                                <button type="submit" name="update_profile" class="btn btn-primary">
                                    <i class="bi bi-check-circle"></i> บันทึกข้อมูล
                                </button>
                            </form>
                        </div>

                        <!-- Password Change -->
                        <div class="settings-card">
                            <h5 class="mb-4">
                                <i class="bi bi-lock"></i> เปลี่ยนรหัสผ่าน
                            </h5>
                            <form method="POST">
                                <div class="mb-3">
                                    <label class="form-label">รหัสผ่านปัจจุบัน</label>
                                    <input type="password" name="current_password" class="form-control" required>
                                </div>
                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label">รหัสผ่านใหม่</label>
                                        <input type="password" name="new_password" class="form-control" minlength="6"
                                            required>
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label">ยืนยันรหัสผ่านใหม่</label>
                                        <input type="password" name="confirm_password" class="form-control"
                                            minlength="6" required>
                                    </div>
                                </div>
                                <button type="submit" name="change_password" class="btn btn-warning">
                                    <i class="bi bi-shield-lock"></i> เปลี่ยนรหัสผ่าน
                                </button>
                            </form>
                        </div>

                        <!-- System Cleanup -->
                        <div class="settings-card">
                            <div class="danger-zone">
                                <h5 class="mb-4 text-danger">
                                    <i class="bi bi-exclamation-triangle"></i> Danger Zone
                                </h5>
                                <p class="text-muted">การดำเนินการเหล่านี้ไม่สามารถย้อนกลับได้</p>

                                <form method="POST"
                                    onsubmit="return confirm('คุณแน่ใจที่จะล้างข้อมูล? การกระทำนี้ไม่สามารถย้อนกลับได้')">
                                    <div class="row">
                                        <div class="col-md-6 mb-3">
                                            <label class="form-label">เลือกประเภท</label>
                                            <select name="cleanup_type" class="form-select" required>
                                                <option value="">-- เลือก --</option>
                                                <option value="expired">ลิงก์ที่หมดอายุ
                                                    (<?php echo number_format($expiredUrls); ?> รายการ)</option>
                                                <option value="inactive">ลิงก์ที่ไม่มีการใช้งาน
                                                    (<?php echo number_format($inactiveUrls); ?> รายการ)</option>
                                                <option value="logs">ประวัติการคลิก</option>
                                                <option value="qr_cache">QR Code Cache</option>
                                            </select>
                                        </div>
                                        <div class="col-md-6 mb-3">
                                            <label class="form-label">เก่ากว่า (วัน)</label>
                                            <input type="number" name="cleanup_days" class="form-control" value="30"
                                                min="1" max="365" required>
                                        </div>
                                    </div>
                                    <button type="submit" name="cleanup" class="btn btn-danger">
                                        <i class="bi bi-trash"></i> ล้างข้อมูล
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>

                    <!-- Right Column -->
                    <div class="col-md-4">
                        <!-- System Overview -->
                        <div class="settings-card">
                            <h5 class="mb-4">
                                <i class="bi bi-bar-chart"></i> ภาพรวมระบบ
                            </h5>

                            <div class="stat-box">
                                <h4><?php echo number_format($totalUrls); ?></h4>
                                <small class="text-muted">URLs ทั้งหมด</small>
                            </div>

                            <div class="stat-box">
                                <h4><?php echo number_format($totalUsers); ?></h4>
                                <small class="text-muted">ผู้ใช้ทั้งหมด</small>
                            </div>

                            <div class="stat-box">
                                <h4><?php echo number_format($totalClicks); ?></h4>
                                <small class="text-muted">คลิกทั้งหมด</small>
                            </div>
                        </div>

                        <!-- System Info -->
                        <div class="settings-card">
                            <h5 class="mb-4">
                                <i class="bi bi-info-circle"></i> ข้อมูลระบบ
                            </h5>

                            <div class="system-info">
                                <div class="d-flex justify-content-between mb-2">
                                    <span>ไฟล์อัพโหลด:</span>
                                    <strong><?php echo number_format($uploadFiles); ?></strong>
                                </div>
                                <div class="d-flex justify-content-between mb-2">
                                    <span>QR Code Files:</span>
                                    <strong><?php echo number_format($qrFiles); ?></strong>
                                </div>
                                <div class="d-flex justify-content-between mb-2">
                                    <span>PHP Version:</span>
                                    <strong><?php echo phpversion(); ?></strong>
                                </div>
                                <div class="d-flex justify-content-between mb-2">
                                    <span>Server:</span>
                                    <strong><?php echo $_SERVER['SERVER_SOFTWARE'] ?? 'Unknown'; ?></strong>
                                </div>
                                <div class="d-flex justify-content-between">
                                    <span>Site URL:</span>
                                    <strong><?php echo SITE_URL; ?></strong>
                                </div>
                            </div>
                        </div>

                        <!-- Database Info -->
                        <?php if (!empty($tableStats)): ?>
                            <div class="settings-card">
                                <h5 class="mb-4">
                                    <i class="bi bi-database"></i> ขนาดฐานข้อมูล
                                </h5>

                                <div class="table-responsive">
                                    <table class="table table-sm">
                                        <thead>
                                            <tr>
                                                <th>ตาราง</th>
                                                <th class="text-end">ขนาด</th>
                                                <th class="text-end">แถว</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php
                                            $totalSize = 0;
                                            foreach ($tableStats as $table):
                                                $totalSize += $table['size_mb'];
                                            ?>
                                                <tr>
                                                    <td><?php echo $table['table_name']; ?></td>
                                                    <td class="text-end"><?php echo $table['size_mb']; ?> MB</td>
                                                    <td class="text-end"><?php echo number_format($table['table_rows']); ?></td>
                                                </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                        <tfoot>
                                            <tr class="table-primary">
                                                <th>รวม</th>
                                                <th class="text-end"><?php echo number_format($totalSize, 2); ?> MB</th>
                                                <th></th>
                                            </tr>
                                        </tfoot>
                                    </table>
                                </div>
                            </div>
                        <?php endif; ?>

                        <!-- Quick Actions -->
                        <div class="settings-card">
                            <h5 class="mb-4">
                                <i class="bi bi-lightning"></i> Quick Actions
                            </h5>

                            <div class="d-grid gap-2">
                                <form method="POST" class="d-inline">
                                    <button type="submit" name="generate_config" class="btn btn-outline-primary w-100">
                                        <i class="bi bi-file-earmark-code"></i> Generate Config Backup
                                    </button>
                                </form>
                                <button class="btn btn-outline-warning" onclick="clearCache()">
                                    <i class="bi bi-arrow-clockwise"></i> Clear QR Cache
                                </button>
                                <a href="../debug_redirect.php" target="_blank" class="btn btn-outline-info">
                                    <i class="bi bi-bug"></i> Debug System
                                </a>
                                <a href="../test_db.php" target="_blank" class="btn btn-outline-success">
                                    <i class="bi bi-database-check"></i> Test Database
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function clearCache() {
            if (confirm('Clear all QR code cache files?')) {
                fetch('clear_cache.php', {
                        method: 'POST'
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            alert('Cache cleared successfully! Deleted ' + data.deleted + ' files.');
                            location.reload();
                        } else {
                            alert('Error clearing cache: ' + data.message);
                        }
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        alert('Error clearing cache');
                    });
            }
        }

        // Password confirmation
        document.querySelector('form').addEventListener('submit', function(e) {
            if (e.target.querySelector('[name="change_password"]')) {
                const newPassword = e.target.querySelector('[name="new_password"]').value;
                const confirmPassword = e.target.querySelector('[name="confirm_password"]').value;

                if (newPassword !== confirmPassword) {
                    e.preventDefault();
                    alert('รหัสผ่านใหม่ไม่ตรงกัน');
                }
            }
        });
    </script>
</body>

</html>