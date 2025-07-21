<?php
require_once '../config.php';

// ตรวจสอบการล็อกอิน
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: ../login.php');
    exit;
}

// Date range
$dateRange = $_GET['range'] ?? '7days';
$customStart = $_GET['start'] ?? '';
$customEnd = $_GET['end'] ?? '';

// คำนวณวันที่
switch ($dateRange) {
    case 'today':
        $startDate = date('Y-m-d');
        $endDate = date('Y-m-d');
        break;
    case 'yesterday':
        $startDate = date('Y-m-d', strtotime('-1 day'));
        $endDate = date('Y-m-d', strtotime('-1 day'));
        break;
    case '7days':
        $startDate = date('Y-m-d', strtotime('-7 days'));
        $endDate = date('Y-m-d');
        break;
    case '30days':
        $startDate = date('Y-m-d', strtotime('-30 days'));
        $endDate = date('Y-m-d');
        break;
    case 'custom':
        $startDate = $customStart ?: date('Y-m-d', strtotime('-30 days'));
        $endDate = $customEnd ?: date('Y-m-d');
        break;
    default:
        $startDate = date('Y-m-d', strtotime('-7 days'));
        $endDate = date('Y-m-d');
}

// ดึงสถิติรายวัน (แก้ไขชื่อตาราง)
$stmt = $pdo->prepare("
    SELECT 
        DATE(created_at) as date,
        COUNT(*) as urls_created,
        SUM(CASE WHEN type = 'link' THEN 1 ELSE 0 END) as links,
        SUM(CASE WHEN type = 'text' THEN 1 ELSE 0 END) as texts,
        SUM(CASE WHEN type = 'wifi' THEN 1 ELSE 0 END) as wifis
    FROM short_urls
    WHERE DATE(created_at) BETWEEN ? AND ?
    GROUP BY DATE(created_at)
    ORDER BY date ASC
");
$stmt->execute([$startDate, $endDate]);
$dailyStats = $stmt->fetchAll();

// ดึงสถิติการคลิกรายวัน (แก้ไขชื่อตาราง)
$stmt = $pdo->prepare("
    SELECT 
        DATE(clicked_at) as date,
        COUNT(*) as total_clicks,
        COUNT(DISTINCT user_ip) as unique_clicks
    FROM click_stats
    WHERE DATE(clicked_at) BETWEEN ? AND ?
    GROUP BY DATE(clicked_at)
    ORDER BY date ASC
");
$stmt->execute([$startDate, $endDate]);
$clickStats = $stmt->fetchAll();

// Top URLs (แก้ไขชื่อตารางและฟิลด์)
$stmt = $pdo->prepare("
    SELECT 
        u.short_code,
        u.original_url,
        u.content,
        u.type,
        u.clicks,
        COUNT(DISTINCT cs.user_ip) as unique_visitors
    FROM short_urls u
    LEFT JOIN click_stats cs ON u.short_code = cs.short_code AND DATE(cs.clicked_at) BETWEEN ? AND ?
    WHERE u.clicks > 0
    GROUP BY u.short_code
    ORDER BY u.clicks DESC
    LIMIT 10
");
$stmt->execute([$startDate, $endDate]);
$topUrls = $stmt->fetchAll();

// Browser Stats (แก้ไขชื่อตารางและฟิลด์)
$stmt = $pdo->prepare("
    SELECT 
        CASE 
            WHEN user_agent LIKE '%Chrome%' THEN 'Chrome'
            WHEN user_agent LIKE '%Firefox%' THEN 'Firefox'
            WHEN user_agent LIKE '%Safari%' AND user_agent NOT LIKE '%Chrome%' THEN 'Safari'
            WHEN user_agent LIKE '%Edge%' THEN 'Edge'
            WHEN user_agent LIKE '%Opera%' THEN 'Opera'
            ELSE 'Other'
        END as browser,
        COUNT(*) as count
    FROM click_stats
    WHERE DATE(clicked_at) BETWEEN ? AND ?
    GROUP BY browser
    ORDER BY count DESC
");
$stmt->execute([$startDate, $endDate]);
$browserStats = $stmt->fetchAll();

// Device Stats (แก้ไขชื่อตารางและฟิลด์)
$stmt = $pdo->prepare("
    SELECT 
        CASE
            WHEN user_agent LIKE '%Mobile%' THEN 'Mobile'
            WHEN user_agent LIKE '%Tablet%' THEN 'Tablet'
            ELSE 'Desktop'
        END as device,
        COUNT(*) as count
    FROM click_stats
    WHERE DATE(clicked_at) BETWEEN ? AND ?
    GROUP BY device
    ORDER BY count DESC
");
$stmt->execute([$startDate, $endDate]);
$deviceStats = $stmt->fetchAll();

// Top Referrers (แก้ไขชื่อตารางและฟิลด์)
$stmt = $pdo->prepare("
    SELECT 
        CASE 
            WHEN referer = '' OR referer IS NULL THEN 'Direct'
            ELSE referer
        END as ref_source,
        COUNT(*) as count
    FROM click_stats
    WHERE DATE(clicked_at) BETWEEN ? AND ?
    GROUP BY ref_source
    ORDER BY count DESC
    LIMIT 10
");
$stmt->execute([$startDate, $endDate]);
$referrerStats = $stmt->fetchAll();

// สร้างข้อมูลรวมสำหรับแสดงผล
$dateRange_text = '';
switch ($dateRange) {
    case 'today':
        $dateRange_text = 'วันนี้';
        break;
    case 'yesterday':
        $dateRange_text = 'เมื่อวาน';
        break;
    case '7days':
        $dateRange_text = '7 วันที่ผ่านมา';
        break;
    case '30days':
        $dateRange_text = '30 วันที่ผ่านมา';
        break;
    case 'custom':
        $dateRange_text = date('d/m/Y', strtotime($startDate)) . ' - ' . date('d/m/Y', strtotime($endDate));
        break;
}
?>
<!DOCTYPE html>
<html lang="th">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Analytics - Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
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

        .analytics-card {
            background: white;
            border-radius: 15px;
            padding: 20px;
            box-shadow: 0 5px 20px rgba(0, 0, 0, 0.05);
            margin-bottom: 20px;
            height: 100%;
        }

        .chart-container {
            position: relative;
            height: 300px;
        }

        .stat-number {
            font-size: 2.5rem;
            font-weight: bold;
            margin-bottom: 0.5rem;
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
                    <a class="nav-link active" href="analytics.php">
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
                    <div>
                        <h1>Analytics Dashboard</h1>
                        <p class="text-muted mb-0">ข้อมูล: <?php echo $dateRange_text; ?></p>
                    </div>

                    <!-- Date Range Selector -->
                    <div class="btn-group" role="group">
                        <button type="button"
                            class="btn btn-outline-primary <?php echo $dateRange == 'today' ? 'active' : ''; ?>"
                            onclick="location.href='?range=today'">วันนี้</button>
                        <button type="button"
                            class="btn btn-outline-primary <?php echo $dateRange == 'yesterday' ? 'active' : ''; ?>"
                            onclick="location.href='?range=yesterday'">เมื่อวาน</button>
                        <button type="button"
                            class="btn btn-outline-primary <?php echo $dateRange == '7days' ? 'active' : ''; ?>"
                            onclick="location.href='?range=7days'">7 วัน</button>
                        <button type="button"
                            class="btn btn-outline-primary <?php echo $dateRange == '30days' ? 'active' : ''; ?>"
                            onclick="location.href='?range=30days'">30 วัน</button>
                        <button type="button" class="btn btn-outline-primary" data-bs-toggle="modal"
                            data-bs-target="#customDateModal">
                            กำหนดเอง
                        </button>
                    </div>
                </div>

                <!-- Summary Stats -->
                <div class="row mb-4">
                    <div class="col-md-3 mb-3">
                        <div class="analytics-card text-center">
                            <div class="stat-number text-primary">
                                <?php
                                $totalUrls = array_sum(array_column($dailyStats, 'urls_created'));
                                echo number_format($totalUrls);
                                ?>
                            </div>
                            <p class="text-muted mb-0">URLs สร้างใหม่</p>
                        </div>
                    </div>
                    <div class="col-md-3 mb-3">
                        <div class="analytics-card text-center">
                            <div class="stat-number text-success">
                                <?php
                                $totalClicks = array_sum(array_column($clickStats, 'total_clicks'));
                                echo number_format($totalClicks);
                                ?>
                            </div>
                            <p class="text-muted mb-0">จำนวนคลิกทั้งหมด</p>
                        </div>
                    </div>
                    <div class="col-md-3 mb-3">
                        <div class="analytics-card text-center">
                            <div class="stat-number text-info">
                                <?php
                                $uniqueClicks = array_sum(array_column($clickStats, 'unique_clicks'));
                                echo number_format($uniqueClicks);
                                ?>
                            </div>
                            <p class="text-muted mb-0">ผู้เข้าชมไม่ซ้ำ</p>
                        </div>
                    </div>
                    <div class="col-md-3 mb-3">
                        <div class="analytics-card text-center">
                            <div class="stat-number text-warning">
                                <?php
                                $avgClicks = $totalUrls > 0 ? round($totalClicks / $totalUrls, 1) : 0;
                                echo $avgClicks;
                                ?>
                            </div>
                            <p class="text-muted mb-0">คลิกเฉลี่ย/ลิงก์</p>
                        </div>
                    </div>
                </div>

                <!-- Charts Row 1 -->
                <div class="row mb-4">
                    <div class="col-md-8">
                        <div class="analytics-card">
                            <h5 class="mb-3">การสร้าง URLs และคลิกต่อวัน</h5>
                            <?php if (empty($dailyStats) && empty($clickStats)): ?>
                                <div class="no-data">
                                    <i class="bi bi-graph-up" style="font-size: 3rem; color: #dee2e6;"></i>
                                    <p>ไม่มีข้อมูลในช่วงเวลาที่เลือก</p>
                                </div>
                            <?php else: ?>
                                <div class="chart-container">
                                    <canvas id="lineChart"></canvas>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="analytics-card">
                            <h5 class="mb-3">ประเภท URLs</h5>
                            <?php if (empty($dailyStats)): ?>
                                <div class="no-data">
                                    <i class="bi bi-pie-chart" style="font-size: 3rem; color: #dee2e6;"></i>
                                    <p>ไม่มีข้อมูล</p>
                                </div>
                            <?php else: ?>
                                <div class="chart-container">
                                    <canvas id="typeChart"></canvas>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <!-- Charts Row 2 -->
                <div class="row mb-4">
                    <div class="col-md-4">
                        <div class="analytics-card">
                            <h5 class="mb-3">เบราว์เซอร์</h5>
                            <?php if (empty($browserStats)): ?>
                                <div class="no-data">
                                    <i class="bi bi-browser-chrome" style="font-size: 3rem; color: #dee2e6;"></i>
                                    <p>ไม่มีข้อมูล</p>
                                </div>
                            <?php else: ?>
                                <div class="chart-container">
                                    <canvas id="browserChart"></canvas>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="analytics-card">
                            <h5 class="mb-3">อุปกรณ์</h5>
                            <?php if (empty($deviceStats)): ?>
                                <div class="no-data">
                                    <i class="bi bi-phone" style="font-size: 3rem; color: #dee2e6;"></i>
                                    <p>ไม่มีข้อมูล</p>
                                </div>
                            <?php else: ?>
                                <div class="chart-container">
                                    <canvas id="deviceChart"></canvas>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="analytics-card">
                            <h5 class="mb-3">แหล่งที่มา</h5>
                            <?php if (empty($referrerStats)): ?>
                                <div class="no-data">
                                    <i class="bi bi-link-45deg" style="font-size: 3rem; color: #dee2e6;"></i>
                                    <p>ไม่มีข้อมูล</p>
                                </div>
                            <?php else: ?>
                                <div style="max-height: 300px; overflow-y: auto;">
                                    <table class="table table-sm">
                                        <?php foreach ($referrerStats as $ref): ?>
                                            <tr>
                                                <td class="text-truncate" style="max-width: 200px;">
                                                    <?php
                                                    if ($ref['ref_source'] == 'Direct') {
                                                        echo '<i class="bi bi-arrow-right"></i> Direct';
                                                    } else {
                                                        $domain = parse_url($ref['ref_source'], PHP_URL_HOST);
                                                        echo '<i class="bi bi-link"></i> ' . ($domain ?: $ref['ref_source']);
                                                    }
                                                    ?>
                                                </td>
                                                <td class="text-end"><?php echo number_format($ref['count']); ?></td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </table>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <!-- Top URLs -->
                <div class="analytics-card">
                    <h5 class="mb-3">URLs ยอดนิยม</h5>
                    <?php if (empty($topUrls)): ?>
                        <div class="no-data">
                            <i class="bi bi-trophy" style="font-size: 3rem; color: #dee2e6;"></i>
                            <p>ไม่มีข้อมูลการคลิกในช่วงเวลาที่เลือก</p>
                        </div>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table">
                                <thead>
                                    <tr>
                                        <th>Short URL</th>
                                        <th>ประเภท</th>
                                        <th>เนื้อหา</th>
                                        <th class="text-end">คลิกทั้งหมด</th>
                                        <th class="text-end">ผู้เข้าชมไม่ซ้ำ</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($topUrls as $url): ?>
                                        <tr>
                                            <td>
                                                <a href="<?php echo SITE_URL . $url['short_code']; ?>" target="_blank">
                                                    <?php echo SITE_URL . $url['short_code']; ?>
                                                </a>
                                            </td>
                                            <td>
                                                <span class="badge bg-info"><?php echo strtoupper($url['type']); ?></span>
                                            </td>
                                            <td class="text-truncate" style="max-width: 300px;">
                                                <?php
                                                if ($url['type'] === 'link') {
                                                    echo htmlspecialchars($url['original_url']);
                                                } elseif ($url['type'] === 'text') {
                                                    echo htmlspecialchars(substr($url['content'], 0, 50)) . '...';
                                                } elseif ($url['type'] === 'wifi') {
                                                    echo 'WiFi Network';
                                                } else {
                                                    echo 'N/A';
                                                }
                                                ?>
                                            </td>
                                            <td class="text-end"><?php echo number_format($url['clicks']); ?></td>
                                            <td class="text-end"><?php echo number_format($url['unique_visitors']); ?></td>
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

    <!-- Custom Date Modal -->
    <div class="modal fade" id="customDateModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <form method="GET">
                    <input type="hidden" name="range" value="custom">
                    <div class="modal-header">
                        <h5 class="modal-title">เลือกช่วงวันที่</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label">วันที่เริ่มต้น</label>
                            <input type="date" name="start" class="form-control" value="<?php echo $startDate; ?>"
                                required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">วันที่สิ้นสุด</label>
                            <input type="date" name="end" class="form-control" value="<?php echo $endDate; ?>" required>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">ยกเลิก</button>
                        <button type="submit" class="btn btn-primary">ดูรายงาน</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Prepare data
        const dailyData = <?php echo json_encode($dailyStats); ?>;
        const clickData = <?php echo json_encode($clickStats); ?>;
        const browserData = <?php echo json_encode($browserStats); ?>;
        const deviceData = <?php echo json_encode($deviceStats); ?>;

        // Helper function to merge data by date
        function mergeDataByDate(dailyData, clickData) {
            const merged = {};

            // Add daily data
            dailyData.forEach(item => {
                merged[item.date] = {
                    date: item.date,
                    urls_created: parseInt(item.urls_created),
                    total_clicks: 0
                };
            });

            // Add click data
            clickData.forEach(item => {
                if (merged[item.date]) {
                    merged[item.date].total_clicks = parseInt(item.total_clicks);
                } else {
                    merged[item.date] = {
                        date: item.date,
                        urls_created: 0,
                        total_clicks: parseInt(item.total_clicks)
                    };
                }
            });

            return Object.values(merged).sort((a, b) => new Date(a.date) - new Date(b.date));
        }

        // Line Chart
        if (document.getElementById('lineChart') && (dailyData.length > 0 || clickData.length > 0)) {
            const lineCtx = document.getElementById('lineChart').getContext('2d');
            const mergedData = mergeDataByDate(dailyData, clickData);

            new Chart(lineCtx, {
                type: 'line',
                data: {
                    labels: mergedData.map(d => d.date),
                    datasets: [{
                        label: 'URLs Created',
                        data: mergedData.map(d => d.urls_created),
                        borderColor: 'rgb(75, 192, 192)',
                        backgroundColor: 'rgba(75, 192, 192, 0.2)',
                        tension: 0.1,
                        yAxisID: 'y'
                    }, {
                        label: 'Clicks',
                        data: mergedData.map(d => d.total_clicks),
                        borderColor: 'rgb(255, 99, 132)',
                        backgroundColor: 'rgba(255, 99, 132, 0.2)',
                        tension: 0.1,
                        yAxisID: 'y1'
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    interaction: {
                        mode: 'index',
                        intersect: false,
                    },
                    scales: {
                        y: {
                            type: 'linear',
                            display: true,
                            position: 'left',
                        },
                        y1: {
                            type: 'linear',
                            display: true,
                            position: 'right',
                            grid: {
                                drawOnChartArea: false,
                            },
                        }
                    }
                }
            });
        }

        // Type Chart
        if (document.getElementById('typeChart') && dailyData.length > 0) {
            const typeCtx = document.getElementById('typeChart').getContext('2d');
            const totalLinks = dailyData.reduce((sum, d) => sum + parseInt(d.links), 0);
            const totalTexts = dailyData.reduce((sum, d) => sum + parseInt(d.texts), 0);
            const totalWifis = dailyData.reduce((sum, d) => sum + parseInt(d.wifis), 0);

            new Chart(typeCtx, {
                type: 'doughnut',
                data: {
                    labels: ['Links', 'Texts', 'WiFi'],
                    datasets: [{
                        data: [totalLinks, totalTexts, totalWifis],
                        backgroundColor: [
                            'rgb(54, 162, 235)',
                            'rgb(255, 205, 86)',
                            'rgb(75, 192, 192)'
                        ]
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false
                }
            });
        }

        // Browser Chart
        if (document.getElementById('browserChart') && browserData.length > 0) {
            const browserCtx = document.getElementById('browserChart').getContext('2d');
            new Chart(browserCtx, {
                type: 'bar',
                data: {
                    labels: browserData.map(b => b.browser),
                    datasets: [{
                        label: 'การใช้งาน',
                        data: browserData.map(b => b.count),
                        backgroundColor: 'rgba(102, 126, 234, 0.8)'
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

        // Device Chart
        if (document.getElementById('deviceChart') && deviceData.length > 0) {
            const deviceCtx = document.getElementById('deviceChart').getContext('2d');
            new Chart(deviceCtx, {
                type: 'pie',
                data: {
                    labels: deviceData.map(d => d.device),
                    datasets: [{
                        data: deviceData.map(d => d.count),
                        backgroundColor: [
                            'rgb(255, 99, 132)',
                            'rgb(54, 162, 235)',
                            'rgb(255, 205, 86)'
                        ]
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false
                }
            });
        }
    </script>
</body>

</html>