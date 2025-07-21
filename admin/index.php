<?php
require_once '../config.php';

// ตรวจสอบการล็อกอิน
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: ../login.php');
    exit;
}

// ดึงสถิติ
$stats = [];

// จำนวนลิงก์ทั้งหมด
$stmt = $pdo->query("SELECT COUNT(*) FROM short_urls");
$stats['total_urls'] = $stmt->fetchColumn();

// จำนวนลิงก์ที่ active
$stmt = $pdo->query("SELECT COUNT(*) FROM short_urls WHERE is_active = 1");
$stats['active_urls'] = $stmt->fetchColumn();

// จำนวนคลิกทั้งหมด
$stmt = $pdo->query("SELECT SUM(clicks) FROM short_urls");
$stats['total_clicks'] = $stmt->fetchColumn() ?: 0;

// จำนวนคลิกวันนี้
$stmt = $pdo->query("SELECT COUNT(*) FROM click_stats WHERE DATE(clicked_at) = CURDATE()");
$stats['today_clicks'] = $stmt->fetchColumn();

// ลิงก์ที่คลิกมากที่สุด
$stmt = $pdo->query("SELECT short_code, original_url, clicks, type FROM short_urls ORDER BY clicks DESC LIMIT 5");
$top_urls = $stmt->fetchAll();

// สถิติรายวัน (7 วันล่าสุด)
$stmt = $pdo->query("
    SELECT DATE(clicked_at) as date, COUNT(*) as clicks
    FROM click_stats 
    WHERE clicked_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)
    GROUP BY DATE(clicked_at)
    ORDER BY date DESC
");
$daily_stats = $stmt->fetchAll();

// สถิติประเภทลิงก์
$stmt = $pdo->query("
    SELECT type, COUNT(*) as count
    FROM short_urls
    GROUP BY type
    ORDER BY count DESC
");
$type_stats = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="th">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard</title>
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

        .stat-card {
            background: white;
            border-radius: 15px;
            padding: 25px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.1);
            transition: transform 0.3s;
        }

        .stat-card:hover {
            transform: translateY(-5px);
        }

        .stat-icon {
            font-size: 2.5rem;
            margin-bottom: 15px;
        }

        .chart-container {
            background: white;
            border-radius: 15px;
            padding: 25px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.1);
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
                    <small>URL Shortener Dashboard</small>
                </div>
                <nav class="nav flex-column">
                    <a class="nav-link active" href="index.php">
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
                    <h1>Dashboard</h1>
                    <span class="text-muted">อัพเดตล่าสุด: <?php echo date('d/m/Y H:i'); ?></span>
                </div>

                <!-- Stats Cards -->
                <div class="row mb-4">
                    <div class="col-md-3">
                        <div class="stat-card text-center">
                            <div class="stat-icon text-primary">
                                <i class="bi bi-link-45deg"></i>
                            </div>
                            <h3 class="mb-1"><?php echo number_format($stats['total_urls']); ?></h3>
                            <p class="text-muted mb-0">ลิงก์ทั้งหมด</p>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="stat-card text-center">
                            <div class="stat-icon text-success">
                                <i class="bi bi-check-circle"></i>
                            </div>
                            <h3 class="mb-1"><?php echo number_format($stats['active_urls']); ?></h3>
                            <p class="text-muted mb-0">ลิงก์ที่ใช้งานได้</p>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="stat-card text-center">
                            <div class="stat-icon text-info">
                                <i class="bi bi-mouse"></i>
                            </div>
                            <h3 class="mb-1"><?php echo number_format($stats['total_clicks']); ?></h3>
                            <p class="text-muted mb-0">คลิกทั้งหมด</p>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="stat-card text-center">
                            <div class="stat-icon text-warning">
                                <i class="bi bi-calendar-day"></i>
                            </div>
                            <h3 class="mb-1"><?php echo number_format($stats['today_clicks']); ?></h3>
                            <p class="text-muted mb-0">คลิกวันนี้</p>
                        </div>
                    </div>
                </div>

                <div class="row">
                    <!-- Top URLs -->
                    <div class="col-md-8">
                        <div class="chart-container">
                            <h5 class="mb-3">ลิงก์ที่คลิกมากที่สุด</h5>
                            <?php if (empty($top_urls)): ?>
                                <div class="text-center text-muted py-4">
                                    <i class="bi bi-inbox"></i> ยังไม่มีข้อมูล
                                </div>
                            <?php else: ?>
                                <div class="table-responsive">
                                    <table class="table table-borderless">
                                        <thead>
                                            <tr>
                                                <th>ลิงก์</th>
                                                <th>ประเภท</th>
                                                <th>คลิก</th>
                                                <th>Action</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($top_urls as $url): ?>
                                                <tr>
                                                    <td>
                                                        <a href="<?php echo SITE_URL . $url['short_code']; ?>" target="_blank"
                                                            class="text-decoration-none">
                                                            <?php echo SITE_URL . $url['short_code']; ?>
                                                        </a>
                                                        <br>
                                                        <small class="text-muted">
                                                            <?php echo htmlspecialchars(substr($url['original_url'] ?? 'N/A', 0, 50)); ?>
                                                        </small>
                                                    </td>
                                                    <td>
                                                        <span
                                                            class="badge bg-info"><?php echo strtoupper($url['type']); ?></span>
                                                    </td>
                                                    <td>
                                                        <strong><?php echo number_format($url['clicks']); ?></strong>
                                                    </td>
                                                    <td>
                                                        <a href="urls.php?search=<?php echo urlencode($url['short_code']); ?>"
                                                            class="btn btn-sm btn-outline-primary">
                                                            <i class="bi bi-eye"></i>
                                                        </a>
                                                    </td>
                                                </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- Type Stats -->
                    <div class="col-md-4">
                        <div class="chart-container">
                            <h5 class="mb-3">สถิติประเภทลิงก์</h5>
                            <?php if (empty($type_stats)): ?>
                                <div class="text-center text-muted py-4">
                                    <i class="bi bi-inbox"></i> ยังไม่มีข้อมูล
                                </div>
                            <?php else: ?>
                                <?php foreach ($type_stats as $type): ?>
                                    <div class="d-flex justify-content-between align-items-center mb-3">
                                        <div>
                                            <span class="badge bg-<?php
                                                                    echo $type['type'] === 'link' ? 'primary' : ($type['type'] === 'text' ? 'info' : 'success');
                                                                    ?>">
                                                <?php echo strtoupper($type['type']); ?>
                                            </span>
                                        </div>
                                        <div>
                                            <strong><?php echo number_format($type['count']); ?></strong>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <!-- Daily Stats -->
                <?php if (!empty($daily_stats)): ?>
                    <div class="row mt-4">
                        <div class="col-12">
                            <div class="chart-container">
                                <h5 class="mb-3">สถิติคลิกรายวัน (7 วันล่าสุด)</h5>
                                <div class="table-responsive">
                                    <table class="table table-striped">
                                        <thead>
                                            <tr>
                                                <th>วันที่</th>
                                                <th>จำนวนคลิก</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($daily_stats as $day): ?>
                                                <tr>
                                                    <td><?php echo date('d/m/Y', strtotime($day['date'])); ?></td>
                                                    <td><strong><?php echo number_format($day['clicks']); ?></strong></td>
                                                </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>