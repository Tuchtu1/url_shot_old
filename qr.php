<?php
// qr.php
require_once 'config.php';

$code = $_GET['code'] ?? '';

if (empty($code)) {
    header('Location: ' . SITE_URL);
    exit;
}

// ค้นหา URL จากฐานข้อมูล
$stmt = $pdo->prepare("SELECT * FROM short_urls WHERE short_code = ?");
$stmt->execute([$code]);
$url = $stmt->fetch();

if (!$url) {
    include '404.php';
    exit;
}

// สร้าง QR Code URL
$shortUrl = SITE_URL . $url['short_code'];
$qrFile = QR_CACHE_DIR . $url['short_code'] . '.png';

// ตรวจสอบว่ามี QR Code อยู่แล้วหรือไม่
if (!file_exists($qrFile)) {
    // สร้าง QR Code ใหม่
    generateQRCode($shortUrl, $qrFile);
}
?>
<!DOCTYPE html>
<html lang="th">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>QR Code - <?php echo $url['short_code']; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <style>
        body {
            background: #f8f9fa;
            font-family: 'Sarabun', sans-serif;
        }

        .qr-container {
            max-width: 600px;
            margin: 2rem auto;
            background: white;
            padding: 2rem;
            border-radius: 20px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
        }

        .qr-code {
            text-align: center;
            margin: 2rem 0;
        }

        .qr-code img {
            max-width: 300px;
            height: auto;
            border: 10px solid #f8f9fa;
            border-radius: 20px;
            box-shadow: 0 5px 20px rgba(0, 0, 0, 0.1);
        }

        .url-info {
            background: #f8f9fa;
            padding: 1rem;
            border-radius: 10px;
            word-break: break-all;
            margin-bottom: 1rem;
        }

        .action-buttons {
            display: flex;
            gap: 1rem;
            justify-content: center;
            flex-wrap: wrap;
        }

        @media print {
            body {
                background: white;
            }

            .qr-container {
                box-shadow: none;
                border: 1px solid #dee2e6;
            }

            .action-buttons,
            .navbar {
                display: none !important;
            }
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

    <div class="qr-container">
        <h3 class="text-center mb-4">
            <i class="bi bi-qr-code"></i> QR Code
        </h3>

        <div class="url-info">
            <strong>Short URL:</strong><br>
            <a href="<?php echo $shortUrl; ?>" target="_blank">
                <?php echo $shortUrl; ?>
            </a>
        </div>

        <?php if ($url['type'] == 'link' && $url['original_url']): ?>
            <div class="url-info">
                <strong>Original URL:</strong><br>
                <small><?php echo htmlspecialchars($url['original_url']); ?></small>
            </div>
        <?php endif; ?>

        <div class="qr-code">
            <?php if (file_exists($qrFile)): ?>
                <img src="<?php echo $qrFile; ?>" alt="QR Code">
            <?php else: ?>
                <img src="<?php echo generateQRCode($shortUrl); ?>" alt="QR Code">
            <?php endif; ?>
        </div>

        <div class="text-center mb-3">
            <p class="text-muted">
                <i class="bi bi-camera"></i> สแกน QR Code นี้ด้วยกล้องโทรศัพท์
            </p>
        </div>

        <div class="action-buttons">
            <button class="btn btn-primary" onclick="window.print()">
                <i class="bi bi-printer"></i> พิมพ์
            </button>

            <?php if (file_exists($qrFile)): ?>
                <a href="download.php?file=<?php echo urlencode($qrFile); ?>" class="btn btn-success">
                    <i class="bi bi-download"></i> ดาวน์โหลด
                </a>
            <?php endif; ?>

            <a href="stats.php?id=<?php echo $url['id']; ?>" class="btn btn-info">
                <i class="bi bi-bar-chart"></i> ดูสถิติ
            </a>

            <a href="<?php echo SITE_URL; ?>" class="btn btn-secondary">
                <i class="bi bi-house"></i> หน้าหลัก
            </a>
        </div>
    </div>
</body>

</html>