<?php
// 404.php
if (!defined('SITE_URL') && file_exists('config.php')) {
    require_once 'config.php';
}
?>
<!DOCTYPE html>
<html lang="th">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ไม่พบสิ่งที่ต้องการ - 404</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <style>
        body {
            background: #f8f9fa;
            display: flex;
            align-items: center;
            justify-content: center;
            min-height: 100vh;
            font-family: 'Sarabun', sans-serif;
        }

        .error-box {
            text-align: center;
            padding: 3rem;
            background: white;
            border-radius: 20px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
            max-width: 500px;
        }

        .error-icon {
            font-size: 5rem;
            color: #ffc107;
            margin-bottom: 1rem;
        }

        .error-code {
            font-size: 4rem;
            font-weight: bold;
            color: #343a40;
            margin-bottom: 0.5rem;
        }

        .error-message {
            font-size: 1.5rem;
            color: #6c757d;
            margin-bottom: 2rem;
        }

        .btn-home {
            background: #007bff;
            color: white;
            padding: 0.75rem 2rem;
            border-radius: 25px;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            transition: all 0.3s;
        }

        .btn-home:hover {
            background: #0056b3;
            color: white;
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(0, 123, 255, 0.3);
        }
    </style>
</head>

<body>
    <div class="error-box">
        <div class="error-icon">
            <i class="bi bi-exclamation-triangle"></i>
        </div>
        <div class="error-code">404</div>
        <div class="error-message">ไม่พบสิ่งที่ต้องการ</div>
        <p class="text-muted mb-4">
            รหัสลิงก์นี้ไม่ถูกต้องหรือถูกลบแล้ว
        </p>
        <a href="<?php echo defined('SITE_URL') ? SITE_URL : '/'; ?>" class="btn-home">
            <i class="bi bi-house"></i> กลับหน้าแรก
        </a>
    </div>
</body>

</html>