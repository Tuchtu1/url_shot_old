<?php
//stats.php
require_once 'config.php';

$urlId = $_GET['id'] ?? 0;

if (!$urlId) {
    header('Location: ' . SITE_URL);
    exit;
}

// ดึงข้อมูลลิงก์
// ดึงข้อมูลลิงก์
$stmt = $pdo->prepare("
    SELECT u.*, 
           COUNT(DISTINCT cl.ip_address) as unique_visitors,
           COUNT(cl.id) as total_clicks
    FROM short_urls u
    LEFT JOIN click_logs cl ON u.id = cl.url_id
    WHERE u.id = ?
    GROUP BY u.id
");
$stmt->execute([$urlId]);
$urlData = $stmt->fetch();

if (!$urlData) {
    die('ไม่พบข้อมูลลิงก์');
}

// ดึงสถิติรายวัน (7 วันล่าสุด)
$stmt = $pdo->prepare("
    SELECT 
        DATE(clicked_at) as click_date,
        COUNT(*) as clicks,
        COUNT(DISTINCT ip_address) as unique_clicks
    FROM click_logs
    WHERE url_id = ? AND clicked_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)
    GROUP BY DATE(clicked_at)
    ORDER BY click_date DESC
");
$stmt->execute([$urlId]);
$dailyStats = $stmt->fetchAll();

// ดึง Top Referrers
$stmt = $pdo->prepare("
    SELECT 
        referer,
        COUNT(*) as count
    FROM click_logs
    WHERE url_id = ? AND referer != ''
    GROUP BY referer
    ORDER BY count DESC
    LIMIT 10
");
$stmt->execute([$urlId]);
$referrers = $stmt->fetchAll();

// ดึง User Agents
$stmt = $pdo->prepare("
    SELECT 
        CASE
            WHEN user_agent LIKE '%Mobile%' THEN 'Mobile'
            WHEN user_agent LIKE '%Tablet%' THEN 'Tablet'
            ELSE 'Desktop'
        END as device_type,
        COUNT(*) as count
    FROM click_logs
    WHERE url_id = ?
    GROUP BY device_type
");
$stmt->execute([$urlId]);
$devices = $stmt->fetchAll();

// ดึงประวัติการคลิกล่าสุด
$stmt = $pdo->prepare("
    SELECT *
    FROM click_logs
    WHERE url_id = ?
    ORDER BY clicked_at DESC
    LIMIT 50
");
$stmt->execute([$urlId]);
$recentClicks = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="th">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>สถิติ - <?php echo $urlData['short_code']; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        body {
            background-color: #f8f9fa;
        }

        .stats-card {
            background: white;
            border-radius: 10px;
            padding: 20px;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.05);
            margin-bottom: 20px;
        }

        .stat-number {
            font-size: 2.5rem;
            font-weight: bold;
            color: #007bff;
        }

        .url-info {
            background: #e9ecef;
            padding: 15px;
            border-radius: 10px;
            word-break: break-all;
        }
    </style>
</head>

<body>
    <nav class="navbar navbar-expand-lg navbar-light bg-white shadow-sm">
        <div class="container">
            <a class="navbar-brand" href="<?php echo SITE_URL; ?>">
                <i class="bi bi-link-45deg"></i> URL Shortener
            </a>
        </div>
    </nav>

    <div class="container mt-4">
        <div class="row">
            <div class="col-md-12">
                <h2 class="mb-4">
                    <i class="bi bi-bar-chart"></i> สถิติสำหรับ: <?php echo $urlData['short_code']; ?>
                </h2>

                <!-- URL Info -->
                <div class="url-info mb-4">
                    <div class="row">
                        <div class="col-md-8">
                            <strong>Short URL:</strong>
                            <a href="<?php echo SITE_URL . $urlData['short_code']; ?>" target="_blank">
                                <?php echo SITE_URL . $urlData['short_code']; ?>
                            </a>
                            <br>
                            <strong>Original URL:</strong>
                            <small><?php echo htmlspecialchars($urlData['original_url']); ?></small>
                        </div>
                        <div class="col-md-4 text-end">
                            <button class="btn btn-primary"
                                onclick="window.location.href='<?php echo SITE_URL; ?>qr/<?php echo $urlData['short_code']; ?>'">
                                <i class="bi bi-qr-code"></i> ดู QR Code
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Summary Stats -->
        <div class="row mb-4">
            <div class="col-md-3">
                <div class="stats-card text-center">
                    <div class="stat-number"><?php echo number_format($urlData['total_clicks']); ?></div>
                    <div class="text-muted">จำนวนคลิกทั้งหมด</div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stats-card text-center">
                    <div class="stat-number"><?php echo number_format($urlData['unique_visitors']); ?></div>
                    <div class="text-muted">ผู้เข้าชมไม่ซ้ำ</div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stats-card text-center">
                    <div class="stat-number">
                        <?php
                        $days = ceil((time() - strtotime($urlData['created_at'])) / 86400);
                        echo $days;
                        ?>
                    </div>
                    <div class="text-muted">วันที่ใช้งาน</div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stats-card text-center">
                    <div class="stat-number">
                        <?php
                        $avgDaily = $days > 0 ? round($urlData['total_clicks'] / $days, 1) : 0;
                        echo $avgDaily;
                        ?>
                    </div>
                    <div class="text-muted">คลิกเฉลี่ยต่อวัน</div>
                </div>
            </div>
        </div>

        <div class="row">
            <!-- Daily Chart -->
            <div class="col-md-8">
                <div class="stats-card">
                    <h5 class="mb-3">สถิติรายวัน (7 วันล่าสุด)</h5>
                    <div style="position: relative; height: 300px;">
                        <canvas id="dailyChart"></canvas>
                    </div>
                </div>
            </div>

            <!-- Device Stats -->
            <div class="col-md-4">
                <div class="stats-card">
                    <h5 class="mb-3">อุปกรณ์ที่ใช้</h5>
                    <div style="position: relative; height: 300px;">
                        <canvas id="deviceChart"></canvas>
                    </div>
                </div>
            </div>
        </div>

        <!-- Referrers -->
        <?php if (count($referrers) > 0): ?>
            <div class="row mt-4">
                <div class="col-md-12">
                    <div class="stats-card">
                        <h5 class="mb-3">แหล่งที่มาของการเข้าชม</h5>
                        <div class="table-responsive">
                            <table class="table">
                                <thead>
                                    <tr>
                                        <th>แหล่งที่มา</th>
                                        <th class="text-end">จำนวน</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($referrers as $ref): ?>
                                        <tr>
                                            <td>
                                                <?php
                                                $domain = parse_url($ref['referer'], PHP_URL_HOST);
                                                echo $domain ?: 'Direct';
                                                ?>
                                            </td>
                                            <td class="text-end"><?php echo number_format($ref['count']); ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        <?php endif; ?>

        <!-- Recent Clicks -->
        <div class="row mt-4">
            <div class="col-md-12">
                <div class="stats-card">
                    <h5 class="mb-3">การเข้าชมล่าสุด</h5>
                    <div class="table-responsive">
                        <table class="table table-sm">
                            <thead>
                                <tr>
                                    <th>เวลา</th>
                                    <th>IP Address</th>
                                    <th>อุปกรณ์</th>
                                    <th>แหล่งที่มา</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($recentClicks as $click): ?>
                                    <tr>
                                        <td><?php echo date('d/m/Y H:i:s', strtotime($click['clicked_at'])); ?></td>
                                        <td><?php echo $click['ip_address']; ?></td>
                                        <td>
                                            <?php
                                            $ua = $click['user_agent'];
                                            if (strpos($ua, 'Mobile') !== false) {
                                                echo '<i class="bi bi-phone"></i> Mobile';
                                            } elseif (strpos($ua, 'Tablet') !== false) {
                                                echo '<i class="bi bi-tablet"></i> Tablet';
                                            } else {
                                                echo '<i class="bi bi-laptop"></i> Desktop';
                                            }
                                            ?>
                                        </td>
                                        <td>
                                            <?php
                                            if ($click['referer']) {
                                                $domain = parse_url($click['referer'], PHP_URL_HOST);
                                                echo $domain ?: 'Unknown';
                                            } else {
                                                echo 'Direct';
                                            }
                                            ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Prepare data for charts
        const dailyData = <?php echo json_encode($dailyStats); ?>;
        const deviceData = <?php echo json_encode($devices); ?>;

        // Daily Chart
        const dailyCtx = document.getElementById('dailyChart').getContext('2d');
        new Chart(dailyCtx, {
            type: 'line',
            data: {
                labels: dailyData.map(d => {
                    const date = new Date(d.click_date);
                    return date.toLocaleDateString('th-TH', {
                        day: 'numeric',
                        month: 'short'
                    });
                }).reverse(),
                datasets: [{
                    label: 'จำนวนคลิก',
                    data: dailyData.map(d => d.clicks).reverse(),
                    borderColor: 'rgb(75, 192, 192)',
                    backgroundColor: 'rgba(75, 192, 192, 0.2)',
                    tension: 0.1
                }, {
                    label: 'ผู้เข้าชมไม่ซ้ำ',
                    data: dailyData.map(d => d.unique_clicks).reverse(),
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

        // Device Chart
        const deviceCtx = document.getElementById('deviceChart').getContext('2d');
        new Chart(deviceCtx, {
            type: 'doughnut',
            data: {
                labels: deviceData.map(d => d.device_type),
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
                maintainAspectRatio: true
            }
        });
    </script>
</body>

</html>