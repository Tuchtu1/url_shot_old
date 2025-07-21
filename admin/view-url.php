<?php
require_once '../config.php';

// ตรวจสอบการล็อกอิน
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: ../login.php');
    exit;
}

$shortCode = $_GET['code'] ?? '';

if (empty($shortCode)) {
    header('Location: urls.php');
    exit;
}

// ดึงข้อมูล URL
$stmt = $pdo->prepare("SELECT * FROM short_urls WHERE short_code = ?");
$stmt->execute([$shortCode]);
$url = $stmt->fetch();

if (!$url) {
    header('Location: urls.php');
    exit;
}

// ดึงสถิติการคลิก
$stmt = $pdo->prepare("
    SELECT 
        DATE(clicked_at) as date,
        COUNT(*) as clicks,
        COUNT(DISTINCT user_ip) as unique_clicks
    FROM click_stats 
    WHERE short_code = ? 
    GROUP BY DATE(clicked_at)
    ORDER BY date DESC
    LIMIT 30
");
$stmt->execute([$shortCode]);
$dailyStats = $stmt->fetchAll();

// ดึงการคลิกล่าสุด
$stmt = $pdo->prepare("
    SELECT clicked_at, user_ip, user_agent, referer
    FROM click_stats 
    WHERE short_code = ? 
    ORDER BY clicked_at DESC
    LIMIT 50
");
$stmt->execute([$shortCode]);
$recentClicks = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="th">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ดูรายละเอียด - <?php echo htmlspecialchars($shortCode); ?></title>
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

    .info-card {
        background: white;
        border-radius: 10px;
        padding: 20px;
        margin-bottom: 20px;
        box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
    }

    .qr-code {
        max-width: 200px;
        border: 1px solid #ddd;
        border-radius: 10px;
        padding: 10px;
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
                    <h1>รายละเอียด URL</h1>
                    <a href="urls.php" class="btn btn-outline-secondary">
                        <i class="bi bi-arrow-left"></i> กลับ
                    </a>
                </div>

                <div class="row">
                    <!-- URL Info -->
                    <div class="col-md-8">
                        <div class="info-card">
                            <h5 class="mb-3">ข้อมูลพื้นฐาน</h5>

                            <div class="row mb-3">
                                <div class="col-md-3"><strong>Short Code:</strong></div>
                                <div class="col-md-9">
                                    <code><?php echo htmlspecialchars($url['short_code']); ?></code>
                                </div>
                            </div>

                            <div class="row mb-3">
                                <div class="col-md-3"><strong>Short URL:</strong></div>
                                <div class="col-md-9">
                                    <a href="<?php echo SITE_URL . $url['short_code']; ?>" target="_blank">
                                        <?php echo SITE_URL . $url['short_code']; ?>
                                    </a>
                                </div>
                            </div>

                            <div class="row mb-3">
                                <div class="col-md-3"><strong>ประเภท:</strong></div>
                                <div class="col-md-9">
                                    <span class="badge bg-info"><?php echo strtoupper($url['type']); ?></span>
                                </div>
                            </div>

                            <?php if ($url['type'] === 'link'): ?>
                            <div class="row mb-3">
                                <div class="col-md-3"><strong>Original URL:</strong></div>
                                <div class="col-md-9">
                                    <a href="<?php echo htmlspecialchars($url['original_url']); ?>" target="_blank">
                                        <?php echo htmlspecialchars($url['original_url']); ?>
                                    </a>
                                </div>
                            </div>
                            <?php elseif ($url['type'] === 'text'): ?>
                            <div class="row mb-3">
                                <div class="col-md-3"><strong>เนื้อหา:</strong></div>
                                <div class="col-md-9">
                                    <pre
                                        class="bg-light p-3 rounded"><?php echo htmlspecialchars($url['content']); ?></pre>
                                </div>
                            </div>
                            <?php elseif ($url['type'] === 'wifi'): ?>
                            <div class="row mb-3">
                                <div class="col-md-3"><strong>WiFi SSID:</strong></div>
                                <div class="col-md-9"><?php echo htmlspecialchars($url['wifi_ssid']); ?></div>
                            </div>
                            <div class="row mb-3">
                                <div class="col-md-3"><strong>Security:</strong></div>
                                <div class="col-md-9"><?php echo htmlspecialchars($url['wifi_security']); ?></div>
                            </div>
                            <div class="row mb-3">
                                <div class="col-md-3"><strong>Hidden:</strong></div>
                                <div class="col-md-9"><?php echo $url['wifi_hidden'] ? 'Yes' : 'No'; ?></div>
                            </div>
                            <?php endif; ?>

                            <div class="row mb-3">
                                <div class="col-md-3"><strong>Protected:</strong></div>
                                <div class="col-md-9">
                                    <?php if ($url['password']): ?>
                                    <span class="badge bg-warning">
                                        <i class="bi bi-lock"></i> Password Protected
                                    </span>
                                    <?php else: ?>
                                    <span class="text-muted">No</span>
                                    <?php endif; ?>
                                </div>
                            </div>

                            <div class="row mb-3">
                                <div class="col-md-3"><strong>Status:</strong></div>
                                <div class="col-md-9">
                                    <span class="badge bg-<?php echo $url['is_active'] ? 'success' : 'danger'; ?>">
                                        <?php echo $url['is_active'] ? 'Active' : 'Inactive'; ?>
                                    </span>
                                </div>
                            </div>

                            <div class="row mb-3">
                                <div class="col-md-3"><strong>Created:</strong></div>
                                <div class="col-md-9"><?php echo date('d/m/Y H:i:s', strtotime($url['created_at'])); ?>
                                </div>
                            </div>

                            <?php if ($url['expires_at'] && $url['expires_at'] !== '0000-00-00 00:00:00'): ?>
                            <div class="row mb-3">
                                <div class="col-md-3"><strong>Expires:</strong></div>
                                <div class="col-md-9">
                                    <?php echo date('d/m/Y H:i:s', strtotime($url['expires_at'])); ?>
                                    <?php if (strtotime($url['expires_at']) < time()): ?>
                                    <span class="badge bg-danger ms-2">Expired</span>
                                    <?php endif; ?>
                                </div>
                            </div>
                            <?php endif; ?>

                            <div class="row mb-3">
                                <div class="col-md-3"><strong>Total Clicks:</strong></div>
                                <div class="col-md-9">
                                    <strong class="text-primary"><?php echo number_format($url['clicks']); ?></strong>
                                </div>
                            </div>
                        </div>

                        <!-- Recent Clicks -->
                        <div class="info-card">
                            <h5 class="mb-3">การคลิกล่าสุด</h5>
                            <?php if (empty($recentClicks)): ?>
                            <p class="text-muted">ยังไม่มีการคลิก</p>
                            <?php else: ?>
                            <div class="table-responsive">
                                <table class="table table-sm">
                                    <thead>
                                        <tr>
                                            <th>เวลา</th>
                                            <th>IP Address</th>
                                            <th>User Agent</th>
                                            <th>Referer</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($recentClicks as $click): ?>
                                        <tr>
                                            <td><?php echo date('d/m/Y H:i:s', strtotime($click['clicked_at'])); ?></td>
                                            <td><?php echo htmlspecialchars($click['user_ip']); ?></td>
                                            <td class="text-truncate" style="max-width: 200px;">
                                                <?php echo htmlspecialchars($click['user_agent']); ?>
                                            </td>
                                            <td class="text-truncate" style="max-width: 200px;">
                                                <?php echo htmlspecialchars($click['referer'] ?: 'Direct'); ?>
                                            </td>
                                        </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- QR Code & Actions -->
                    <div class="col-md-4">
                        <div class="info-card text-center">
                            <h5 class="mb-3">QR Code</h5>
                            <?php
                            $qrPath = QR_CACHE_DIR . $url['short_code'] . '.png';
                            if (file_exists($qrPath)): ?>
                            <img src="<?php echo '/' . $qrPath; ?>" class="qr-code" alt="QR Code">
                            <?php else: ?>
                            <div class="qr-code d-flex align-items-center justify-content-center">
                                <span class="text-muted">QR Code not generated</span>
                            </div>
                            <?php endif; ?>
                        </div>

                        <div class="info-card">
                            <h5 class="mb-3">Actions</h5>
                            <div class="d-grid gap-2">
                                <a href="edit-url.php?code=<?php echo $url['short_code']; ?>" class="btn btn-primary">
                                    <i class="bi bi-pencil"></i> Edit
                                </a>
                                <button class="btn btn-warning"
                                    onclick="toggleUrl('<?php echo $url['short_code']; ?>')">
                                    <i class="bi bi-toggle-<?php echo $url['is_active'] ? 'off' : 'on'; ?>"></i>
                                    <?php echo $url['is_active'] ? 'Deactivate' : 'Activate'; ?>
                                </button>
                                <button class="btn btn-danger" onclick="deleteUrl('<?php echo $url['short_code']; ?>')">
                                    <i class="bi bi-trash"></i> Delete
                                </button>
                            </div>
                        </div>

                        <!-- Daily Stats -->
                        <div class="info-card">
                            <h5 class="mb-3">สถิติรายวัน</h5>
                            <?php if (empty($dailyStats)): ?>
                            <p class="text-muted">ยังไม่มีข้อมูล</p>
                            <?php else: ?>
                            <div class="table-responsive">
                                <table class="table table-sm">
                                    <thead>
                                        <tr>
                                            <th>วันที่</th>
                                            <th>Clicks</th>
                                            <th>Unique</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach (array_slice($dailyStats, 0, 10) as $stat): ?>
                                        <tr>
                                            <td><?php echo date('d/m', strtotime($stat['date'])); ?></td>
                                            <td><?php echo $stat['clicks']; ?></td>
                                            <td><?php echo $stat['unique_clicks']; ?></td>
                                        </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script>
    function toggleUrl(shortCode) {
        $.post('toggle-url.php', {
            short_code: shortCode
        }, function(response) {
            if (response.success) {
                location.reload();
            } else {
                alert('เกิดข้อผิดพลาด: ' + response.message);
            }
        }, 'json');
    }

    function deleteUrl(shortCode) {
        if (confirm('คุณแน่ใจที่จะลบลิงก์นี้?')) {
            $.post('delete-url.php', {
                short_code: shortCode
            }, function(response) {
                if (response.success) {
                    window.location.href = 'urls.php';
                } else {
                    alert('เกิดข้อผิดพลาด: ' + response.message);
                }
            }, 'json');
        }
    }
    </script>
</body>

</html>