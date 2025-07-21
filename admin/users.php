<?php
require_once '../config.php';

// ตรวจสอบการล็อกอิน
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: ../login.php');
    exit;
}

$message = '';
$messageType = '';

// จัดการ Actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // เพิ่มผู้ใช้ใหม่
        if (isset($_POST['add_user'])) {
            $username = sanitizeInput($_POST['username']);
            $email = sanitizeInput($_POST['email']);
            $password = $_POST['password'];
            $role = $_POST['role'];

            // ตรวจสอบความแข็งแกร่งของรหัสผ่าน
            if (strlen($password) < 6) {
                throw new Exception('รหัสผ่านต้องมีอย่างน้อย 6 ตัวอักษร');
            }

            // ตรวจสอบ username ซ้ำ
            $stmt = $pdo->prepare("SELECT id FROM users WHERE username = ? OR email = ?");
            $stmt->execute([$username, $email]);
            if ($stmt->fetch()) {
                throw new Exception('Username หรือ Email นี้ถูกใช้งานแล้ว');
            }

            // สร้างผู้ใช้ใหม่
            $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
            $apiKey = bin2hex(random_bytes(32));

            $stmt = $pdo->prepare("
                INSERT INTO users (username, email, password, role, api_key, is_active) 
                VALUES (?, ?, ?, ?, ?, 1)
            ");
            $stmt->execute([$username, $email, $hashedPassword, $role, $apiKey]);

            $message = "เพิ่มผู้ใช้ $username สำเร็จ";
            $messageType = 'success';
        }

        // แก้ไขผู้ใช้
        if (isset($_POST['edit_user'])) {
            $userId = $_POST['user_id'];
            $username = sanitizeInput($_POST['username']);
            $email = sanitizeInput($_POST['email']);
            $role = $_POST['role'];
            $isActive = isset($_POST['is_active']) ? 1 : 0;

            // ตรวจสอบว่าไม่ใช่การแก้ไขตัวเองเป็น inactive
            if ($userId == $_SESSION['user_id'] && $isActive == 0) {
                throw new Exception('ไม่สามารถปิดการใช้งานบัญชีตัวเองได้');
            }

            // ตรวจสอบ username/email ซ้ำ (ยกเว้นตัวเอง)
            $stmt = $pdo->prepare("
                SELECT id FROM users 
                WHERE (username = ? OR email = ?) AND id != ?
            ");
            $stmt->execute([$username, $email, $userId]);
            if ($stmt->fetch()) {
                throw new Exception('Username หรือ Email นี้ถูกใช้งานแล้ว');
            }

            // อัพเดทข้อมูล
            $sql = "UPDATE users SET username = ?, email = ?, role = ?, is_active = ?";
            $params = [$username, $email, $role, $isActive];

            // ถ้ามีการเปลี่ยนรหัสผ่าน
            if (!empty($_POST['new_password'])) {
                if (strlen($_POST['new_password']) < 6) {
                    throw new Exception('รหัสผ่านต้องมีอย่างน้อย 6 ตัวอักษร');
                }
                $sql .= ", password = ?";
                $params[] = password_hash($_POST['new_password'], PASSWORD_DEFAULT);
            }

            $sql .= " WHERE id = ?";
            $params[] = $userId;

            $stmt = $pdo->prepare($sql);
            $stmt->execute($params);

            // อัพเดต session หากแก้ไขตัวเอง
            if ($userId == $_SESSION['user_id']) {
                $_SESSION['username'] = $username;
                $_SESSION['role'] = $role;
            }

            $message = "แก้ไขข้อมูลผู้ใช้สำเร็จ";
            $messageType = 'success';
        }

        // ลบผู้ใช้
        if (isset($_POST['delete_user'])) {
            $userId = $_POST['user_id'];

            // ห้ามลบตัวเอง
            if ($userId == $_SESSION['user_id']) {
                throw new Exception('ไม่สามารถลบบัญชีตัวเองได้');
            }

            // เริ่ม transaction
            $pdo->beginTransaction();

            // ลบ API usage ของผู้ใช้ (ถ้ามี table นี้)
            $stmt = $pdo->prepare("DELETE FROM api_usage WHERE user_id = ?");
            $stmt->execute([$userId]);

            // ลบ bulk imports ของผู้ใช้ (ถ้ามี table นี้)
            $stmt = $pdo->prepare("DELETE FROM bulk_imports WHERE user_id = ?");
            $stmt->execute([$userId]);

            // ลบ login logs ของผู้ใช้ (ถ้ามี table นี้)
            $stmt = $pdo->prepare("DELETE FROM login_logs WHERE user_id = ?");
            $stmt->execute([$userId]);

            // ลบ URLs ของผู้ใช้
            if (in_array('user_id', $shortUrlsColumns)) {
                $stmt = $pdo->prepare("DELETE FROM short_urls WHERE user_id = ?");
                $stmt->execute([$userId]);
            }

            // ลบผู้ใช้
            $stmt = $pdo->prepare("DELETE FROM users WHERE id = ?");
            $stmt->execute([$userId]);

            $pdo->commit();

            $message = "ลบผู้ใช้สำเร็จ";
            $messageType = 'success';
        }

        // Toggle สถานะ
        if (isset($_POST['toggle_status'])) {
            $userId = $_POST['user_id'];

            // ห้ามปิดการใช้งานตัวเอง
            if ($userId == $_SESSION['user_id']) {
                throw new Exception('ไม่สามารถเปลี่ยนสถานะบัญชีตัวเองได้');
            }

            $stmt = $pdo->prepare("UPDATE users SET is_active = NOT is_active WHERE id = ?");
            $stmt->execute([$userId]);

            $message = "เปลี่ยนสถานะผู้ใช้สำเร็จ";
            $messageType = 'success';
        }

        // สร้าง API Key ใหม่
        if (isset($_POST['generate_api_key'])) {
            $userId = $_POST['user_id'];
            $apiKey = bin2hex(random_bytes(32));

            $stmt = $pdo->prepare("UPDATE users SET api_key = ? WHERE id = ?");
            $stmt->execute([$apiKey, $userId]);

            $message = "สร้าง API Key ใหม่สำเร็จ";
            $messageType = 'success';
        }
    } catch (Exception $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        $message = $e->getMessage();
        $messageType = 'danger';
    }
}

// Pagination
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($page - 1) * ITEMS_PER_PAGE;

// Search
$search = $_GET['search'] ?? '';
$filterRole = $_GET['role'] ?? '';
$filterStatus = $_GET['status'] ?? '';

// สร้าง WHERE clause
$where = [];
$params = [];

if ($search) {
    $where[] = "(username LIKE ? OR email LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
}

if ($filterRole) {
    $where[] = "role = ?";
    $params[] = $filterRole;
}

if ($filterStatus !== '') {
    $where[] = "is_active = ?";
    $params[] = $filterStatus;
}

$whereClause = $where ? 'WHERE ' . implode(' AND ', $where) : '';

// นับจำนวนทั้งหมด
$stmt = $pdo->prepare("SELECT COUNT(*) FROM users $whereClause");
$stmt->execute($params);
$totalItems = $stmt->fetchColumn();
$totalPages = ceil($totalItems / ITEMS_PER_PAGE);

// ดึงข้อมูลผู้ใช้ (ปรับให้เข้ากับฐานข้อมูลจริง)
// ตรวจสอบ column ที่มีอยู่ใน short_urls table
$shortUrlsColumns = [];
try {
    $stmt = $pdo->query("DESCRIBE short_urls");
    $columns = $stmt->fetchAll();
    foreach ($columns as $column) {
        $shortUrlsColumns[] = $column['Field'];
    }
} catch (PDOException $e) {
    // ถ้าไม่มี table short_urls
    $shortUrlsColumns = [];
}

// ตรวจสอบ column ที่มีอยู่ใน api_usage table
$apiUsageColumns = [];
try {
    $stmt = $pdo->query("DESCRIBE api_usage");
    $columns = $stmt->fetchAll();
    foreach ($columns as $column) {
        $apiUsageColumns[] = $column['Field'];
    }
} catch (PDOException $e) {
    // ถ้าไม่มี table api_usage
    $apiUsageColumns = [];
}

// สร้าง query ตามโครงสร้างจริง
$sql = "SELECT u.*";

// เพิ่มข้อมูลจาก short_urls ถ้ามี
if (in_array('user_id', $shortUrlsColumns)) {
    $sql .= ", COUNT(DISTINCT su.id) as url_count, COALESCE(SUM(su.clicks), 0) as total_clicks, MAX(su.created_at) as last_activity";
} else {
    $sql .= ", 0 as url_count, 0 as total_clicks, NULL as last_activity";
}

// เพิ่มข้อมูลจาก api_usage ถ้ามี
if (in_array('user_id', $apiUsageColumns) && in_array('request_date', $apiUsageColumns)) {
    $sql .= ", COUNT(DISTINCT DATE(au.request_date)) as api_usage_days";
} else {
    $sql .= ", 0 as api_usage_days";
}

$sql .= " FROM users u";

// เพิ่ม LEFT JOIN ถ้ามี columns ที่ต้องการ
if (in_array('user_id', $shortUrlsColumns)) {
    $sql .= " LEFT JOIN short_urls su ON u.id = su.user_id";
}

if (in_array('user_id', $apiUsageColumns) && in_array('request_date', $apiUsageColumns)) {
    $sql .= " LEFT JOIN api_usage au ON u.id = au.user_id";
}

$sql .= " $whereClause GROUP BY u.id ORDER BY u.created_at DESC LIMIT " . ITEMS_PER_PAGE . " OFFSET $offset";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$users = $stmt->fetchAll();

// ดึงสถิติรวม
$stmt = $pdo->query("
    SELECT 
        COUNT(*) as total_users,
        SUM(CASE WHEN is_active = 1 THEN 1 ELSE 0 END) as active_users,
        SUM(CASE WHEN role = 'admin' THEN 1 ELSE 0 END) as admin_users,
        SUM(CASE WHEN api_key IS NOT NULL THEN 1 ELSE 0 END) as api_users
    FROM users
");
$stats = $stmt->fetch();
?>
<!DOCTYPE html>
<html lang="th">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>จัดการผู้ใช้ - Admin</title>
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

        .user-card {
            background: white;
            border-radius: 15px;
            padding: 20px;
            margin-bottom: 20px;
            box-shadow: 0 5px 20px rgba(0, 0, 0, 0.05);
            transition: all 0.3s;
        }

        .user-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 30px rgba(0, 0, 0, 0.1);
        }

        .user-avatar {
            width: 60px;
            height: 60px;
            border-radius: 50%;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 24px;
            font-weight: bold;
        }

        .filter-card {
            background: white;
            border-radius: 15px;
            padding: 25px;
            margin-bottom: 25px;
            box-shadow: 0 5px 20px rgba(0, 0, 0, 0.05);
        }

        .stats-card {
            background: white;
            border-radius: 15px;
            padding: 20px;
            margin-bottom: 20px;
            box-shadow: 0 5px 20px rgba(0, 0, 0, 0.05);
        }

        .stat-item {
            text-align: center;
            padding: 15px;
            border-radius: 10px;
            background: #f8f9fa;
            margin: 5px;
        }

        .stat-number {
            font-size: 2rem;
            font-weight: bold;
            color: #667eea;
        }

        .no-users {
            text-align: center;
            color: #6c757d;
            padding: 3rem;
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
                    <a class="nav-link active" href="users.php">
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
                    <a class="nav-link" href="settings.php">
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
                    <h1>จัดการผู้ใช้งาน</h1>
                    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addUserModal">
                        <i class="bi bi-person-plus"></i> เพิ่มผู้ใช้ใหม่
                    </button>
                </div>

                <?php if ($message): ?>
                    <div class="alert alert-<?php echo $messageType; ?> alert-dismissible fade show" role="alert">
                        <?php echo $message; ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>

                <!-- Stats Summary -->
                <div class="stats-card">
                    <div class="row">
                        <div class="col-md-3">
                            <div class="stat-item">
                                <div class="stat-number"><?php echo number_format($stats['total_users']); ?></div>
                                <div class="text-muted">ผู้ใช้ทั้งหมด</div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="stat-item">
                                <div class="stat-number"><?php echo number_format($stats['active_users']); ?></div>
                                <div class="text-muted">ผู้ใช้ที่ใช้งานได้</div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="stat-item">
                                <div class="stat-number"><?php echo number_format($stats['admin_users']); ?></div>
                                <div class="text-muted">ผู้ดูแลระบบ</div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="stat-item">
                                <div class="stat-number"><?php echo number_format($stats['api_users']); ?></div>
                                <div class="text-muted">ผู้ใช้ API</div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Filters -->
                <div class="filter-card">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h6 class="mb-0">
                            <i class="bi bi-funnel"></i> ตัวกรอง
                        </h6>
                        <a href="users.php" class="btn btn-sm btn-outline-secondary">
                            <i class="bi bi-arrow-clockwise"></i> รีเซ็ต
                        </a>
                    </div>
                    <form method="GET" class="row g-3">
                        <div class="col-md-4">
                            <label class="form-label">ค้นหา</label>
                            <input type="text" name="search" class="form-control" placeholder="Username หรือ Email..."
                                value="<?php echo htmlspecialchars($search); ?>">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">บทบาท</label>
                            <select name="role" class="form-select">
                                <option value="">ทั้งหมด</option>
                                <option value="admin" <?php echo $filterRole === 'admin' ? 'selected' : ''; ?>>Admin
                                </option>
                                <option value="user" <?php echo $filterRole === 'user' ? 'selected' : ''; ?>>User
                                </option>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">สถานะ</label>
                            <select name="status" class="form-select">
                                <option value="">ทั้งหมด</option>
                                <option value="1" <?php echo $filterStatus === '1' ? 'selected' : ''; ?>>Active</option>
                                <option value="0" <?php echo $filterStatus === '0' ? 'selected' : ''; ?>>Inactive
                                </option>
                            </select>
                        </div>
                        <div class="col-md-2 d-flex align-items-end">
                            <button type="submit" class="btn btn-primary w-100">
                                <i class="bi bi-search"></i> ค้นหา
                            </button>
                        </div>
                    </form>
                </div>

                <!-- Users Grid -->
                <?php if (empty($users)): ?>
                    <div class="no-users">
                        <i class="bi bi-people" style="font-size: 4rem; color: #dee2e6;"></i>
                        <h4>ไม่พบผู้ใช้</h4>
                        <p>ไม่มีผู้ใช้ที่ตรงกับเงื่อนไขที่ค้นหา</p>
                    </div>
                <?php else: ?>
                    <div class="row">
                        <?php foreach ($users as $user): ?>
                            <div class="col-md-6 col-lg-4">
                                <div class="user-card">
                                    <div class="d-flex align-items-start mb-3">
                                        <div class="user-avatar me-3">
                                            <?php echo strtoupper(substr($user['username'], 0, 1)); ?>
                                        </div>
                                        <div class="flex-grow-1">
                                            <h5 class="mb-1">
                                                <?php echo htmlspecialchars($user['username']); ?>
                                                <?php if ($user['id'] == $_SESSION['user_id']): ?>
                                                    <span class="badge bg-info ms-1">คุณ</span>
                                                <?php endif; ?>
                                            </h5>
                                            <p class="text-muted mb-0 small">
                                                <?php echo htmlspecialchars($user['email']); ?>
                                            </p>
                                            <div class="mt-2">
                                                <span
                                                    class="badge bg-<?php echo $user['role'] === 'admin' ? 'danger' : 'primary'; ?>">
                                                    <?php echo ucfirst($user['role']); ?>
                                                </span>
                                                <span
                                                    class="badge bg-<?php echo $user['is_active'] ? 'success' : 'secondary'; ?> ms-1">
                                                    <?php echo $user['is_active'] ? 'Active' : 'Inactive'; ?>
                                                </span>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="mb-3">
                                        <div class="row text-center">
                                            <div class="col-3">
                                                <strong
                                                    class="d-block"><?php echo number_format($user['url_count']); ?></strong>
                                                <small class="text-muted">URLs</small>
                                            </div>
                                            <div class="col-3">
                                                <strong
                                                    class="d-block"><?php echo number_format($user['total_clicks'] ?? 0); ?></strong>
                                                <small class="text-muted">Clicks</small>
                                            </div>
                                            <div class="col-3">
                                                <strong class="d-block">
                                                    <?php
                                                    if ($user['api_key']) {
                                                        echo '<i class="bi bi-check-circle text-success"></i>';
                                                    } else {
                                                        echo '<i class="bi bi-x-circle text-danger"></i>';
                                                    }
                                                    ?>
                                                </strong>
                                                <small class="text-muted">API</small>
                                            </div>
                                            <div class="col-3">
                                                <strong
                                                    class="d-block"><?php echo number_format($user['api_usage_days']); ?></strong>
                                                <small class="text-muted">API วัน</small>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="small text-muted mb-3">
                                        <i class="bi bi-calendar3"></i>
                                        สมัคร: <?php echo date('d/m/Y', strtotime($user['created_at'])); ?>
                                        <?php if ($user['last_login']): ?>
                                            <br>
                                            <i class="bi bi-clock"></i>
                                            เข้าใช้: <?php echo date('d/m/Y H:i', strtotime($user['last_login'])); ?>
                                        <?php endif; ?>
                                    </div>

                                    <div class="d-flex gap-1">
                                        <button class="btn btn-sm btn-outline-primary" title="แก้ไข"
                                            onclick="editUser(<?php echo htmlspecialchars(json_encode($user)); ?>)">
                                            <i class="bi bi-pencil"></i>
                                        </button>

                                        <?php if ($user['api_key']): ?>
                                            <form method="POST" class="d-inline">
                                                <input type="hidden" name="user_id" value="<?php echo $user['id']; ?>">
                                                <button type="submit" name="generate_api_key" class="btn btn-sm btn-outline-warning"
                                                    title="สร้าง API Key ใหม่"
                                                    onclick="return confirm('สร้าง API Key ใหม่? Key เดิมจะใช้ไม่ได้')">
                                                    <i class="bi bi-key"></i>
                                                </button>
                                            </form>
                                        <?php endif; ?>

                                        <?php if ($user['id'] != $_SESSION['user_id']): ?>
                                            <form method="POST" class="d-inline">
                                                <input type="hidden" name="user_id" value="<?php echo $user['id']; ?>">
                                                <button type="submit" name="toggle_status"
                                                    class="btn btn-sm btn-outline-<?php echo $user['is_active'] ? 'warning' : 'success'; ?>"
                                                    title="<?php echo $user['is_active'] ? 'ปิดใช้งาน' : 'เปิดใช้งาน'; ?>">
                                                    <i class="bi bi-toggle-<?php echo $user['is_active'] ? 'on' : 'off'; ?>"></i>
                                                </button>
                                            </form>
                                            <form method="POST" class="d-inline"
                                                onsubmit="return confirm('คุณแน่ใจที่จะลบผู้ใช้ <?php echo htmlspecialchars($user['username']); ?>? การกระทำนี้ไม่สามารถย้อนกลับได้')">
                                                <input type="hidden" name="user_id" value="<?php echo $user['id']; ?>">
                                                <button type="submit" name="delete_user" class="btn btn-sm btn-outline-danger"
                                                    title="ลบผู้ใช้">
                                                    <i class="bi bi-trash"></i>
                                                </button>
                                            </form>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>

                <!-- Pagination -->
                <?php if ($totalPages > 1): ?>
                    <nav aria-label="Page navigation" class="mt-4">
                        <ul class="pagination justify-content-center">
                            <li class="page-item <?php echo $page <= 1 ? 'disabled' : ''; ?>">
                                <a class="page-link"
                                    href="?page=<?php echo $page - 1; ?>&<?php echo http_build_query(array_diff_key($_GET, ['page' => ''])); ?>">
                                    <i class="bi bi-chevron-left"></i>
                                </a>
                            </li>

                            <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                                <?php if ($i == 1 || $i == $totalPages || ($i >= $page - 2 && $i <= $page + 2)): ?>
                                    <li class="page-item <?php echo $i == $page ? 'active' : ''; ?>">
                                        <a class="page-link"
                                            href="?page=<?php echo $i; ?>&<?php echo http_build_query(array_diff_key($_GET, ['page' => ''])); ?>">
                                            <?php echo $i; ?>
                                        </a>
                                    </li>
                                <?php elseif ($i == $page - 3 || $i == $page + 3): ?>
                                    <li class="page-item disabled"><span class="page-link">...</span></li>
                                <?php endif; ?>
                            <?php endfor; ?>

                            <li class="page-item <?php echo $page >= $totalPages ? 'disabled' : ''; ?>">
                                <a class="page-link"
                                    href="?page=<?php echo $page + 1; ?>&<?php echo http_build_query(array_diff_key($_GET, ['page' => ''])); ?>">
                                    <i class="bi bi-chevron-right"></i>
                                </a>
                            </li>
                        </ul>
                    </nav>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Add User Modal -->
    <div class="modal fade" id="addUserModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <form method="POST">
                    <div class="modal-header">
                        <h5 class="modal-title">เพิ่มผู้ใช้ใหม่</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label">Username <span class="text-danger">*</span></label>
                            <input type="text" name="username" class="form-control" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Email <span class="text-danger">*</span></label>
                            <input type="email" name="email" class="form-control" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Password <span class="text-danger">*</span></label>
                            <input type="password" name="password" class="form-control" minlength="6" required>
                            <small class="text-muted">รหัสผ่านต้องมีอย่างน้อย 6 ตัวอักษร</small>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">บทบาท</label>
                            <select name="role" class="form-select">
                                <option value="user">User</option>
                                <option value="admin">Admin</option>
                            </select>
                        </div>
                        <div class="alert alert-info">
                            <i class="bi bi-info-circle"></i>
                            ผู้ใช้ใหม่จะได้รับ API Key อัตโนมัติและสถานะ Active
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">ยกเลิก</button>
                        <button type="submit" name="add_user" class="btn btn-primary">
                            <i class="bi bi-person-plus"></i> เพิ่มผู้ใช้
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Edit User Modal -->
    <div class="modal fade" id="editUserModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <form method="POST">
                    <input type="hidden" name="user_id" id="edit_user_id">
                    <div class="modal-header">
                        <h5 class="modal-title">แก้ไขข้อมูลผู้ใช้</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label">Username <span class="text-danger">*</span></label>
                            <input type="text" name="username" id="edit_username" class="form-control" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Email <span class="text-danger">*</span></label>
                            <input type="email" name="email" id="edit_email" class="form-control" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Password ใหม่ (ไม่บังคับ)</label>
                            <input type="password" name="new_password" class="form-control" minlength="6">
                            <small class="text-muted">เว้นว่างไว้หากไม่ต้องการเปลี่ยน</small>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">บทบาท</label>
                            <select name="role" id="edit_role" class="form-select">
                                <option value="user">User</option>
                                <option value="admin">Admin</option>
                            </select>
                        </div>
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" name="is_active" id="edit_is_active">
                            <label class="form-check-label" for="edit_is_active">
                                บัญชีใช้งานได้ (Active)
                            </label>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">ยกเลิก</button>
                        <button type="submit" name="edit_user" class="btn btn-primary">
                            <i class="bi bi-check-circle"></i> บันทึกการแก้ไข
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function editUser(user) {
            document.getElementById('edit_user_id').value = user.id;
            document.getElementById('edit_username').value = user.username;
            document.getElementById('edit_email').value = user.email;
            document.getElementById('edit_role').value = user.role;
            document.getElementById('edit_is_active').checked = user.is_active == 1;

            const modal = new bootstrap.Modal(document.getElementById('editUserModal'));
            modal.show();
        }

        // Form validation
        document.querySelectorAll('form').forEach(form => {
            form.addEventListener('submit', function(e) {
                const passwordInput = this.querySelector(
                    'input[name="password"], input[name="new_password"]');
                if (passwordInput && passwordInput.value && passwordInput.value.length < 6) {
                    e.preventDefault();
                    alert('รหัสผ่านต้องมีอย่างน้อย 6 ตัวอักษร');
                }
            });
        });
    </script>
</body>

</html>