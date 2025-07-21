<?php
require_once '../config.php';

$shortCode = $_GET['code'] ?? '';

if (!$shortCode) {
    die('ไม่พบ QR Code');
}

// ดึงข้อมูล
$stmt = $pdo->prepare("
    SELECT u.*, qs.* 
    FROM urls u
    LEFT JOIN qr_styles qs ON u.id = qs.url_id
    WHERE u.short_code = ?
");
$stmt->execute([$shortCode]);
$data = $stmt->fetch();

if (!$data) {
    die('ไม่พบข้อมูล');
}

$qrFile = '../' . QR_CACHE_DIR . 'qr_' . $data['id'] . '.png';
$shortUrl = SITE_URL . $shortCode;
?>
<!DOCTYPE html>
<html lang="th">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>QR Code - <?php echo $shortCode; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <style>
        body {
            background-color: #f8f9fa;
        }

        .qr-container {
            background: white;
            border-radius: 10px;
            padding: 30px;
            box-shadow: 0 0 20px rgba(0, 0, 0, 0.1);
            text-align: center;
            max-width: 400px;
            margin: 50px auto;
        }

        .qr-image {
            max-width: 100%;
            height: auto;
            margin: 20px 0;
        }

        .url-display {
            background-color: #f8f9fa;
            padding: 10px;
            border-radius: 5px;
            word-break: break-all;
            margin: 15px 0;
        }
    </style>
</head>

<body>
    <div class="container">
        <div class="qr-container">
            <h4 class="mb-3">
                <i class="bi bi-qr-code"></i> QR Code
            </h4>

            <?php if (file_exists($qrFile)): ?>
                <img src="<?php echo SITE_URL . QR_CACHE_DIR . 'qr_' . $data['id'] . '.png'; ?>" alt="QR Code"
                    class="qr-image">
            <?php else: ?>
                <div class="alert alert-warning">
                    <i class="bi bi-exclamation-triangle"></i> ไม่พบไฟล์ QR Code
                </div>
            <?php endif; ?>

            <div class="url-display">
                <small class="text-muted">Short URL:</small><br>
                <strong><?php echo $shortUrl; ?></strong>
            </div>

            <div class="d-grid gap-2">
                <button class="btn btn-primary" onclick="downloadQR()">
                    <i class="bi bi-download"></i> ดาวน์โหลด QR Code
                </button>
                <button class="btn btn-secondary" onclick="printQR()">
                    <i class="bi bi-printer"></i> พิมพ์ QR Code
                </button>
                <button class="btn btn-outline-secondary" onclick="window.close()">
                    <i class="bi bi-x-circle"></i> ปิดหน้าต่าง
                </button>
            </div>

            <hr class="my-4">

            <div class="text-start small text-muted">
                <p class="mb-1">
                    <i class="bi bi-calendar"></i>
                    สร้างเมื่อ: <?php echo date('d/m/Y H:i', strtotime($data['created_at'])); ?>
                </p>
                <p class="mb-1">
                    <i class="bi bi-eye"></i>
                    จำนวนการเข้าชม: <?php echo number_format($data['click_count']); ?> ครั้ง
                </p>
                <?php if ($data['expires_at']): ?>
                    <p class="mb-1">
                        <i class="bi bi-clock"></i>
                        หมดอายุ: <?php echo date('d/m/Y H:i', strtotime($data['expires_at'])); ?>
                    </p>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <script>
        function downloadQR() {
            const link = document.createElement('a');
            link.href = '<?php echo SITE_URL . QR_CACHE_DIR . 'qr_' . $data['id'] . '.png'; ?>';
            link.download = 'qrcode_<?php echo $shortCode; ?>.png';
            link.click();
        }

        function printQR() {
            window.print();
        }
    </script>

    <style media="print">
        body * {
            visibility: hidden;
        }

        .qr-container,
        .qr-container * {
            visibility: visible;
        }

        .qr-container {
            position: absolute;
            left: 0;
            top: 0;
            box-shadow: none;
        }

        button,
        hr,
        .text-muted {
            display: none !important;
        }
    </style>
</body>

</html>