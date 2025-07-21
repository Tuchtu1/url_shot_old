<?php
require_once '../config.php';

// ตรวจสอบการล็อกอิน
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: ../login.php');
    exit;
}

$message = '';
$messageType = '';

// Generate new API key
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['generate'])) {
    $userId = $_POST['user_id'];

    if ($userId) {
        // สร้าง API key ใหม่
        $newApiKey = bin2hex(random_bytes(32));

        $stmt = $pdo->prepare("UPDATE users SET api_key = ? WHERE id = ?");
        if ($stmt->execute([$newApiKey, $userId])) {
            $message = "สร้าง API Key ใหม่สำเร็จ";
            $messageType = 'success';
        } else {
            $message = "เกิดข้อผิดพลาดในการสร้าง API Key";
            $messageType = 'danger';
        }
    }
}

// Revoke API key
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['revoke'])) {
    $userId = $_POST['user_id'];

    $stmt = $pdo->prepare("UPDATE users SET api_key = NULL WHERE id = ?");
    if ($stmt->execute([$userId])) {
        $message = "ยกเลิก API Key สำเร็จ";
        $messageType = 'warning';
    }
}

// ดึงข้อมูลผู้ใช้และ API keys
$stmt = $pdo->query("
    SELECT u.*, 
           COUNT(DISTINCT au.request_date) as days_used,
           SUM(au.request_count) as total_requests,
           MAX(au.request_date) as last_used
    FROM users u
    LEFT JOIN api_usage au ON u.id = au.user_id
    GROUP BY u.id
    ORDER BY u.username
");
$users = $stmt->fetchAll();

// ดึงสถิติการใช้ API
$stmt = $pdo->query("
    SELECT 
        request_date as date,
        SUM(request_count) as total_requests,
        COUNT(DISTINCT user_id) as active_users
    FROM api_usage
    WHERE request_date >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)
    GROUP BY request_date
    ORDER BY request_date DESC
");
$apiStats = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="th">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>API Keys Management - Admin</title>
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

        .api-key-card {
            background: white;
            border-radius: 10px;
            padding: 20px;
            margin-bottom: 15px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
        }

        .api-key {
            font-family: 'Courier New', monospace;
            background: #f8f9fa;
            padding: 10px;
            border-radius: 5px;
            word-break: break-all;
        }

        .usage-badge {
            font-size: 0.875rem;
            padding: 5px 10px;
            border-radius: 20px;
        }

        .copy-btn {
            position: relative;
        }

        .copy-feedback {
            position: absolute;
            top: -30px;
            right: 0;
            background: #28a745;
            color: white;
            padding: 5px 10px;
            border-radius: 5px;
            font-size: 12px;
            display: none;
        }

        .no-data {
            text-align: center;
            color: #6c757d;
            padding: 2rem;
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
                    <a class="nav-link active" href="api-keys.php">
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
                <h1 class="mb-4">API Keys Management</h1>

                <?php if ($message): ?>
                    <div class="alert alert-<?php echo $messageType; ?> alert-dismissible fade show" role="alert">
                        <?php echo $message; ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>

                <!-- API Documentation -->
                <div class="card mb-4">
                    <div class="card-body">
                        <h5 class="card-title">API Documentation</h5>
                        <p><strong>Base URL:</strong> <code><?php echo SITE_URL; ?>api/</code></p>
                        <p><strong>Rate Limit:</strong> <?php echo API_RATE_LIMIT; ?> requests per day</p>

                        <h6 class="mt-3">Authentication:</h6>
                        <p>ส่ง API Key ใน Header:</p>
                        <pre class="bg-light p-2 rounded small"><code>Authorization: Bearer YOUR_API_KEY</code></pre>

                        <h6 class="mt-3">Endpoints:</h6>
                        <div class="table-responsive">
                            <table class="table table-sm">
                                <thead>
                                    <tr>
                                        <th>Method</th>
                                        <th>Endpoint</th>
                                        <th>Description</th>
                                        <th>Status</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr>
                                        <td><span class="badge bg-success">POST</span></td>
                                        <td><code>/create.php</code></td>
                                        <td>สร้าง short URL</td>
                                        <td><span class="badge bg-success">Available</span></td>
                                    </tr>
                                    <tr>
                                        <td><span class="badge bg-primary">GET</span></td>
                                        <td><code>/stats.php</code></td>
                                        <td>ดูสถิติ URL</td>
                                        <td><span class="badge bg-warning">Coming Soon</span></td>
                                    </tr>
                                    <tr>
                                        <td><span class="badge bg-danger">DELETE</span></td>
                                        <td><code>/delete.php</code></td>
                                        <td>ลบ URL</td>
                                        <td><span class="badge bg-warning">Coming Soon</span></td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>

                        <h6 class="mt-3">Example Request:</h6>
                        <pre class="bg-light p-3 rounded small"><code>curl -X POST <?php echo SITE_URL; ?>api/create.php \
  -H "Authorization: Bearer YOUR_API_KEY" \
  -H "Content-Type: application/json" \
  -d '{
    "original_url": "https://example.com",
    "custom_code": "mylink",
    "password": "secret123",
    "expires_at": "2024-12-31 23:59:59"
  }'</code></pre>
                    </div>
                </div>

                <!-- Users with API Keys -->
                <h3 class="mb-3">User API Keys</h3>

                <?php if (empty($users)): ?>
                    <div class="no-data">
                        <i class="bi bi-people" style="font-size: 3rem; color: #dee2e6;"></i>
                        <p>ไม่มีผู้ใช้ในระบบ</p>
                    </div>
                <?php else: ?>
                    <?php foreach ($users as $user): ?>
                        <div class="api-key-card">
                            <div class="row align-items-center">
                                <div class="col-md-8">
                                    <h5 class="mb-1">
                                        <i class="bi bi-person"></i> <?php echo htmlspecialchars($user['username']); ?>
                                        <span
                                            class="badge bg-<?php echo $user['role'] === 'admin' ? 'danger' : 'primary'; ?> ms-2">
                                            <?php echo ucfirst($user['role']); ?>
                                        </span>
                                        <?php if (!$user['is_active']): ?>
                                            <span class="badge bg-secondary ms-1">Inactive</span>
                                        <?php endif; ?>
                                    </h5>
                                    <p class="text-muted mb-2">
                                        <i class="bi bi-envelope"></i> <?php echo htmlspecialchars($user['email']); ?>
                                    </p>

                                    <?php if ($user['api_key']): ?>
                                        <div class="api-key mb-2">
                                            <strong>API Key:</strong>
                                            <span id="key-<?php echo $user['id']; ?>"><?php echo $user['api_key']; ?></span>
                                            <button class="btn btn-sm btn-outline-secondary ms-2 copy-btn"
                                                onclick="copyApiKey('<?php echo $user['id']; ?>')">
                                                <i class="bi bi-clipboard"></i> Copy
                                                <span class="copy-feedback">Copied!</span>
                                            </button>
                                        </div>

                                        <div class="d-flex gap-2 flex-wrap">
                                            <span class="usage-badge bg-light text-dark">
                                                <i class="bi bi-calendar3"></i>
                                                ใช้งาน <?php echo $user['days_used'] ?? 0; ?> วัน
                                            </span>
                                            <span class="usage-badge bg-light text-dark">
                                                <i class="bi bi-arrow-left-right"></i>
                                                <?php echo number_format($user['total_requests'] ?? 0); ?> requests
                                            </span>
                                            <?php if ($user['last_used']): ?>
                                                <span class="usage-badge bg-light text-dark">
                                                    <i class="bi bi-clock"></i>
                                                    Last: <?php echo date('d/m/Y', strtotime($user['last_used'])); ?>
                                                </span>
                                            <?php endif; ?>
                                        </div>
                                    <?php else: ?>
                                        <p class="text-muted mb-0">
                                            <i class="bi bi-key"></i> ยังไม่ได้สร้าง API Key
                                        </p>
                                    <?php endif; ?>
                                </div>
                                <div class="col-md-4 text-end">
                                    <?php if ($user['api_key']): ?>
                                        <form method="POST" class="d-inline">
                                            <input type="hidden" name="user_id" value="<?php echo $user['id']; ?>">
                                            <button type="submit" name="generate" class="btn btn-warning btn-sm"
                                                onclick="return confirm('สร้าง API Key ใหม่? Key เดิมจะใช้ไม่ได้')">
                                                <i class="bi bi-arrow-clockwise"></i> Regenerate
                                            </button>
                                        </form>
                                        <form method="POST" class="d-inline">
                                            <input type="hidden" name="user_id" value="<?php echo $user['id']; ?>">
                                            <button type="submit" name="revoke" class="btn btn-danger btn-sm"
                                                onclick="return confirm('ยกเลิก API Key?')">
                                                <i class="bi bi-x-circle"></i> Revoke
                                            </button>
                                        </form>
                                    <?php else: ?>
                                        <form method="POST" class="d-inline">
                                            <input type="hidden" name="user_id" value="<?php echo $user['id']; ?>">
                                            <button type="submit" name="generate" class="btn btn-primary">
                                                <i class="bi bi-plus-circle"></i> Generate API Key
                                            </button>
                                        </form>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>

                <!-- API Usage Stats -->
                <h3 class="mt-4 mb-3">API Usage Statistics (Last 30 Days)</h3>
                <div class="card">
                    <div class="card-body">
                        <?php if (empty($apiStats)): ?>
                            <div class="no-data">
                                <i class="bi bi-graph-up" style="font-size: 3rem; color: #dee2e6;"></i>
                                <p>ยังไม่มีการใช้งาน API</p>
                            </div>
                        <?php else: ?>
                            <canvas id="apiUsageChart" height="100"></canvas>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script>
        function copyApiKey(userId) {
            const keyElement = document.getElementById('key-' + userId);
            const text = keyElement.textContent;

            if (navigator.clipboard) {
                navigator.clipboard.writeText(text).then(() => {
                    showCopyFeedback(userId);
                });
            } else {
                // Fallback for older browsers
                const textArea = document.createElement('textarea');
                textArea.value = text;
                document.body.appendChild(textArea);
                textArea.select();
                document.execCommand('copy');
                document.body.removeChild(textArea);
                showCopyFeedback(userId);
            }
        }

        function showCopyFeedback(userId) {
            const btn = document.querySelector(`#key-${userId}`).nextElementSibling;
            const feedback = btn.querySelector('.copy-feedback');
            feedback.style.display = 'block';

            setTimeout(() => {
                feedback.style.display = 'none';
            }, 2000);
        }

        // API Usage Chart
        const apiData = <?php echo json_encode($apiStats); ?>;

        if (apiData.length > 0 && document.getElementById('apiUsageChart')) {
            const ctx = document.getElementById('apiUsageChart').getContext('2d');

            new Chart(ctx, {
                type: 'line',
                data: {
                    labels: apiData.map(d => d.date).reverse(),
                    datasets: [{
                        label: 'Total Requests',
                        data: apiData.map(d => d.total_requests).reverse(),
                        borderColor: 'rgb(75, 192, 192)',
                        backgroundColor: 'rgba(75, 192, 192, 0.2)',
                        tension: 0.1
                    }, {
                        label: 'Active Users',
                        data: apiData.map(d => d.active_users).reverse(),
                        borderColor: 'rgb(255, 99, 132)',
                        backgroundColor: 'rgba(255, 99, 132, 0.2)',
                        tension: 0.1
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    scales: {
                        y: {
                            beginAtZero: true
                        }
                    }
                }
            });
        }
    </script>
</body>

</html>