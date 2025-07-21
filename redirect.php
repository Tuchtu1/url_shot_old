<?php
// redirect.php
require_once 'config.php';

// เปิด error reporting สำหรับ debug (ปิดใน production)
if (defined('DEBUG_MODE') && DEBUG_MODE) {
    ini_set('display_errors', 1);
    error_reporting(E_ALL);
}

// ตรวจสอบว่ามีการเชื่อมต่อฐานข้อมูลหรือไม่
if (!isset($pdo)) {
    die('ไม่สามารถเชื่อมต่อฐานข้อมูลได้');
}

// รับ short code จาก URL
$shortCode = $_GET['code'] ?? '';

if (empty($shortCode)) {
    header('Location: ' . SITE_URL);
    exit;
}

try {
    // ดึงข้อมูล URL จากตาราง short_urls
    $stmt = $pdo->prepare("
        SELECT * FROM short_urls 
        WHERE short_code = ? AND is_active = 1
    ");
    $stmt->execute([$shortCode]);
    $urlData = $stmt->fetch();

    if (!$urlData) {
        showErrorPage('ไม่พบลิงก์ที่ต้องการ', 'รหัสลิงก์ไม่ถูกต้องหรือถูกลบแล้ว');
        exit;
    }

    // ตรวจสอบวันหมดอายุ
    if (
        $urlData['expires_at'] &&
        $urlData['expires_at'] !== '0000-00-00 00:00:00' &&
        strtotime($urlData['expires_at']) < time()
    ) {
        showErrorPage('ลิงก์หมดอายุแล้ว', 'ลิงก์นี้หมดอายุแล้ว กรุณาติดต่อผู้ที่แชร์ลิงก์นี้');
        exit;
    }

    // ตรวจสอบรหัสผ่าน
    if ($urlData['password']) {
        // ตรวจสอบว่า session เริ่มแล้วหรือยัง
        if (session_status() == PHP_SESSION_NONE) {
            session_start();
        }
        $sessionKey = 'auth_' . $urlData['id'];

        if (!isset($_SESSION[$sessionKey])) {
            // แสดงฟอร์มกรอกรหัสผ่าน
            if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['password'])) {
                if (password_verify($_POST['password'], $urlData['password'])) {
                    $_SESSION[$sessionKey] = true;
                    // Redirect เพื่อป้องกันการ submit ซ้ำ
                    header('Location: ' . SITE_URL . $shortCode);
                    exit;
                } else {
                    $error = 'รหัสผ่านไม่ถูกต้อง';
                }
            }

            if (!isset($_SESSION[$sessionKey])) {
                showPasswordForm($shortCode, $error ?? null);
                exit;
            }
        }
    }

    // บันทึกสถิติในตาราง click_logs
    try {
        $stmt = $pdo->prepare("
            INSERT INTO click_logs (url_id, ip_address, user_agent, referer, clicked_at) 
            VALUES (?, ?, ?, ?, NOW())
        ");
        $stmt->execute([
            $urlData['id'],
            $_SERVER['REMOTE_ADDR'] ?? null,
            $_SERVER['HTTP_USER_AGENT'] ?? null,
            $_SERVER['HTTP_REFERER'] ?? null
        ]);

        // อัพเดทจำนวนคลิก
        $stmt = $pdo->prepare("UPDATE short_urls SET clicks = clicks + 1 WHERE id = ?");
        $stmt->execute([$urlData['id']]);
    } catch (PDOException $e) {
        // Log error แต่ไม่หยุดการทำงาน
        error_log('Click tracking error: ' . $e->getMessage());
    }

    // จัดการตามประเภท
    switch ($urlData['type']) {
        case 'link':
            if (empty($urlData['original_url'])) {
                showErrorPage('ข้อผิดพลาด', 'ไม่พบ URL ปลายทาง');
                exit;
            }
            header('Location: ' . $urlData['original_url']);
            exit;

        case 'text':
            if (empty($urlData['content'])) {
                showErrorPage('ข้อผิดพลาด', 'ไม่พบข้อความ');
                exit;
            }
            showTextContent($urlData['content']);
            exit;

        case 'wifi':
            if (empty($urlData['wifi_ssid'])) {
                showErrorPage('ข้อผิดพลาด', 'ไม่พบข้อมูล WiFi');
                exit;
            }
            showWifiInfo($urlData);
            exit;
        case 'file':
            // ตรวจสอบจำนวนดาวน์โหลด
            if ($urlData['download_limit'] && $urlData['download_count'] >= $urlData['download_limit']) {
                showErrorPage('ไฟล์หมดโควต้าดาวน์โหลด', 'ไฟล์นี้ถึงจำนวนดาวน์โหลดสูงสุดแล้ว');
                exit;
            }

            // ตรวจสอบว่าไฟล์ยังอยู่หรือไม่
            if (!file_exists($urlData['file_path'])) {
                showErrorPage('ไม่พบไฟล์', 'ไฟล์ที่ต้องการดาวน์โหลดไม่พบหรือถูกลบไปแล้ว');
                exit;
            }

            // แสดงหน้าข้อมูลไฟล์ก่อนดาวน์โหลด
            showFileInfo($urlData);
            exit;
        default:
            showErrorPage('ข้อผิดพลาด', 'ประเภทลิงก์ไม่ถูกต้อง');
            exit;
    }
} catch (PDOException $e) {
    // Log error สำหรับ debugging
    error_log('Database Error in redirect.php: ' . $e->getMessage());
    error_log('SQL State: ' . $e->getCode());

    // แสดง error ที่ละเอียดขึ้นในโหมด development
    if (defined('DEBUG_MODE') && DEBUG_MODE) {
        showErrorPage('เกิดข้อผิดพลาดฐานข้อมูล', 'Error: ' . $e->getMessage());
    } else {
        showErrorPage('เกิดข้อผิดพลาด', 'ไม่สามารถเข้าถึงข้อมูลได้');
    }
    exit;
} catch (Exception $e) {
    // Log general errors
    error_log('General Error in redirect.php: ' . $e->getMessage());

    if (defined('DEBUG_MODE') && DEBUG_MODE) {
        showErrorPage('เกิดข้อผิดพลาด', 'Error: ' . $e->getMessage());
    } else {
        showErrorPage('เกิดข้อผิดพลาด', 'ไม่สามารถเข้าถึงข้อมูลได้');
    }
    exit;
}
// เพิ่มฟังก์ชันแสดงข้อมูลไฟล์ ไว้ท้ายไฟล์ (หลังฟังก์ชัน showWifiInfo)
function showFileInfo($fileData)
{
    // คำนวณขนาดไฟล์ที่อ่านง่าย
    $fileSize = formatFileSize($fileData['file_size']);

    // ตรวจสอบประเภทไฟล์เพื่อแสดง icon
    $fileExtension = strtolower(pathinfo($fileData['file_name'], PATHINFO_EXTENSION));
    $fileIcon = getFileIcon($fileExtension);
?>
    <!DOCTYPE html>
    <html lang="th">

    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>ดาวน์โหลดไฟล์ - <?php echo htmlspecialchars($fileData['content']); ?></title>
        <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
        <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
        <style>
            body {
                background: #f8f9fa;
                padding: 2rem;
                font-family: 'Sarabun', sans-serif;
            }

            .file-box {
                background: white;
                padding: 2rem;
                border-radius: 15px;
                box-shadow: 0 0 20px rgba(0, 0, 0, 0.1);
                max-width: 600px;
                margin: 0 auto;
            }

            .file-icon {
                font-size: 4rem;
                margin-bottom: 1rem;
            }

            .file-info {
                background: #f0f9ff;
                border: 1px solid #3b82f6;
                padding: 1.5rem;
                border-radius: 10px;
                margin: 1rem 0;
            }

            .file-field {
                margin-bottom: 0.75rem;
                padding-bottom: 0.75rem;
                border-bottom: 1px solid #e5e7eb;
            }

            .file-field:last-child {
                margin-bottom: 0;
                padding-bottom: 0;
                border-bottom: none;
            }

            .download-btn {
                font-size: 1.1rem;
                padding: 0.75rem 2rem;
            }

            .progress {
                display: none;
                margin-top: 1rem;
            }
        </style>
    </head>

    <body>
        <div class="file-box">
            <div class="text-center">
                <div class="file-icon <?php echo htmlspecialchars($fileIcon['class']); ?>">
                    <i class="bi <?php echo htmlspecialchars($fileIcon['icon']); ?>"></i>
                </div>
                <h4 class="mb-3"><?php echo htmlspecialchars($fileData['content']); ?></h4>
            </div>

            <div class="file-info">
                <div class="file-field">
                    <label class="text-muted small">ชื่อไฟล์</label>
                    <div class="fw-bold"><?php echo htmlspecialchars($fileData['file_name']); ?></div>
                </div>

                <div class="file-field">
                    <label class="text-muted small">ขนาดไฟล์</label>
                    <div><?php echo $fileSize; ?></div>
                </div>

                <div class="file-field">
                    <label class="text-muted small">ประเภทไฟล์</label>
                    <div><?php echo htmlspecialchars($fileExtension); ?></div>
                </div>

                <?php if ($fileData['download_limit']): ?>
                    <div class="file-field">
                        <label class="text-muted small">จำนวนดาวน์โหลดที่เหลือ</label>
                        <div>
                            <span class="badge bg-warning">
                                <?php echo ($fileData['download_limit'] - $fileData['download_count']); ?> /
                                <?php echo $fileData['download_limit']; ?> ครั้ง
                            </span>
                        </div>
                    </div>
                <?php endif; ?>

                <?php if ($fileData['expires_at'] && $fileData['expires_at'] !== '0000-00-00 00:00:00'): ?>
                    <div class="file-field">
                        <label class="text-muted small">วันหมดอายุ</label>
                        <div>
                            <i class="bi bi-calendar-event"></i>
                            <?php echo date('d/m/Y H:i', strtotime($fileData['expires_at'])); ?>
                        </div>
                    </div>
                <?php endif; ?>
            </div>

            <div class="alert alert-info">
                <i class="bi bi-info-circle"></i>
                <strong>หมายเหตุ:</strong> คลิกปุ่มด้านล่างเพื่อดาวน์โหลดไฟล์
            </div>

            <div class="d-grid gap-2">
                <button class="btn btn-success download-btn"
                    onclick="downloadFile('<?php echo htmlspecialchars($fileData['short_code']); ?>')">
                    <i class="bi bi-download"></i> ดาวน์โหลดไฟล์
                </button>
                <div class="progress">
                    <div class="progress-bar progress-bar-striped progress-bar-animated" role="progressbar"
                        style="width: 0%">0%</div>
                </div>
                <a href="<?php echo SITE_URL; ?>" class="btn btn-outline-primary">
                    <i class="bi bi-house"></i> กลับหน้าหลัก
                </a>
            </div>
        </div>

        <script>
            function downloadFile(shortCode) {
                // แสดง progress bar
                const progressBar = document.querySelector('.progress');
                const progressBarInner = document.querySelector('.progress-bar');
                progressBar.style.display = 'block';

                // จำลองการดาวน์โหลด
                let progress = 0;
                const interval = setInterval(() => {
                    progress += 10;
                    progressBarInner.style.width = progress + '%';
                    progressBarInner.textContent = progress + '%';

                    if (progress >= 100) {
                        clearInterval(interval);
                        // เริ่มดาวน์โหลดจริง
                        window.location.href = 'download_file.php?code=' + shortCode;
                    }
                }, 100);
            }
        </script>
    </body>

    </html>
<?php
}
// เพิ่มฟังก์ชัน helper สำหรับ format ขนาดไฟล์

// เพิ่มฟังก์ชันสำหรับ icon ตามประเภทไฟล์
function getFileIcon($extension)
{
    $types = [
        'pdf' => ['icon' => 'bi-file-earmark-pdf-fill', 'class' => 'text-danger'],
        'doc' => ['icon' => 'bi-file-earmark-word-fill', 'class' => 'text-primary'],
        'docx' => ['icon' => 'bi-file-earmark-word-fill', 'class' => 'text-primary'],
        'xls' => ['icon' => 'bi-file-earmark-excel-fill', 'class' => 'text-success'],
        'xlsx' => ['icon' => 'bi-file-earmark-excel-fill', 'class' => 'text-success'],
        'ppt' => ['icon' => 'bi-file-earmark-ppt-fill', 'class' => 'text-warning'],
        'pptx' => ['icon' => 'bi-file-earmark-ppt-fill', 'class' => 'text-warning'],
        'zip' => ['icon' => 'bi-file-earmark-zip-fill', 'class' => 'text-secondary'],
        'rar' => ['icon' => 'bi-file-earmark-zip-fill', 'class' => 'text-secondary'],
        'jpg' => ['icon' => 'bi-file-earmark-image-fill', 'class' => 'text-info'],
        'jpeg' => ['icon' => 'bi-file-earmark-image-fill', 'class' => 'text-info'],
        'png' => ['icon' => 'bi-file-earmark-image-fill', 'class' => 'text-info'],
        'gif' => ['icon' => 'bi-file-earmark-image-fill', 'class' => 'text-info'],
        'mp4' => ['icon' => 'bi-file-earmark-play-fill', 'class' => 'text-danger'],
        'avi' => ['icon' => 'bi-file-earmark-play-fill', 'class' => 'text-danger'],
        'mp3' => ['icon' => 'bi-file-earmark-music-fill', 'class' => 'text-purple'],
        'txt' => ['icon' => 'bi-file-earmark-text-fill', 'class' => 'text-dark'],
    ];

    $ext = strtolower($extension);
    return isset($types[$ext]) ? $types[$ext] : ['icon' => 'bi-file-earmark-fill', 'class' => 'text-secondary'];
}
// ฟังก์ชันแสดงหน้า error
function showErrorPage($title, $message)
{
?>
    <!DOCTYPE html>
    <html lang="th">

    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title><?php echo htmlspecialchars($title); ?></title>
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
                width: 90%;
            }

            .error-icon {
                font-size: 5rem;
                color: #ffc107;
                margin-bottom: 1rem;
            }
        </style>
    </head>

    <body>
        <div class="error-box">
            <div class="error-icon">
                <i class="bi bi-exclamation-triangle-fill"></i>
            </div>
            <h2 class="text-danger mb-3"><?php echo htmlspecialchars($title); ?></h2>
            <p class="text-muted mb-4"><?php echo htmlspecialchars($message); ?></p>
            <div class="d-grid gap-2 d-md-flex justify-content-md-center">
                <a href="<?php echo SITE_URL; ?>" class="btn btn-primary">
                    <i class="bi bi-house"></i> กลับหน้าแรก
                </a>
                <button class="btn btn-outline-secondary" onclick="history.back()">
                    <i class="bi bi-arrow-left"></i> กลับ
                </button>
            </div>
        </div>
    </body>

    </html>
<?php
}

// ฟังก์ชันแสดงฟอร์มรหัสผ่าน
function showPasswordForm($shortCode, $error = null)
{
?>
    <!DOCTYPE html>
    <html lang="th">

    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>ต้องการรหัสผ่าน</title>
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

            .password-box {
                background: white;
                padding: 2rem;
                border-radius: 15px;
                box-shadow: 0 0 20px rgba(0, 0, 0, 0.1);
                max-width: 400px;
                width: 90%;
            }
        </style>
    </head>

    <body>
        <div class="password-box">
            <h4 class="mb-4 text-center">
                <i class="bi bi-lock"></i> ลิงก์นี้ต้องการรหัสผ่าน
            </h4>
            <?php if ($error): ?>
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <?php echo htmlspecialchars($error); ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>
            <form method="POST" action="">
                <div class="mb-3">
                    <label for="password" class="form-label">รหัสผ่าน</label>
                    <input type="password" class="form-control" id="password" name="password" placeholder="กรอกรหัสผ่าน"
                        required autofocus>
                </div>
                <div class="d-grid">
                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-unlock"></i> เข้าชมลิงก์
                    </button>
                </div>
            </form>
            <div class="text-center mt-3">
                <a href="<?php echo SITE_URL; ?>" class="text-decoration-none">
                    <i class="bi bi-arrow-left"></i> กลับหน้าหลัก
                </a>
            </div>
        </div>
        <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    </body>

    </html>
<?php
}

// ฟังก์ชันแสดงข้อความ
function showTextContent($content)
{
?>
    <!DOCTYPE html>
    <html lang="th">

    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>ข้อความ</title>
        <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
        <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
        <style>
            body {
                background: #f8f9fa;
                padding: 2rem;
                font-family: 'Sarabun', sans-serif;
            }

            .content-box {
                background: white;
                padding: 2rem;
                border-radius: 15px;
                box-shadow: 0 0 20px rgba(0, 0, 0, 0.1);
                max-width: 800px;
                margin: 0 auto;
            }

            .content {
                background: #f8f9fa;
                padding: 1.5rem;
                border-radius: 10px;
                margin: 1rem 0;
            }
        </style>
    </head>

    <body>
        <div class="content-box">
            <h4 class="mb-4">
                <i class="bi bi-text-paragraph"></i> ข้อความ
            </h4>
            <div class="content">
                <pre
                    style="white-space: pre-wrap; word-wrap: break-word; margin: 0; font-family: 'Sarabun', sans-serif;"><?php echo htmlspecialchars($content); ?></pre>
            </div>
            <div class="d-flex gap-2 justify-content-center mt-4">
                <button class="btn btn-success" onclick="copyText()">
                    <i class="bi bi-clipboard"></i> คัดลอกข้อความ
                </button>
                <a href="<?php echo SITE_URL; ?>" class="btn btn-primary">
                    <i class="bi bi-house"></i> กลับหน้าหลัก
                </a>
            </div>
        </div>
        <script>
            function copyText() {
                const text = <?php echo json_encode($content); ?>;
                if (navigator.clipboard && window.isSecureContext) {
                    navigator.clipboard.writeText(text).then(() => {
                        showToast('คัดลอกข้อความเรียบร้อยแล้ว!');
                    }).catch(err => {
                        console.error('Failed to copy: ', err);
                        fallbackCopy(text);
                    });
                } else {
                    fallbackCopy(text);
                }
            }

            function fallbackCopy(text) {
                const textArea = document.createElement('textarea');
                textArea.value = text;
                textArea.style.position = 'fixed';
                textArea.style.left = '-999999px';
                document.body.appendChild(textArea);
                textArea.focus();
                textArea.select();
                try {
                    document.execCommand('copy');
                    showToast('คัดลอกข้อความเรียบร้อยแล้ว!');
                } catch (err) {
                    showToast('ไม่สามารถคัดลอกข้อความได้', 'error');
                }
                document.body.removeChild(textArea);
            }

            function showToast(message, type = 'success') {
                const toast = document.createElement('div');
                toast.className =
                    `alert alert-${type === 'success' ? 'success' : 'danger'} position-fixed top-0 start-50 translate-middle-x mt-3`;
                toast.style.zIndex = '9999';
                toast.innerHTML = `
                <div class="d-flex align-items-center">
                    <i class="bi bi-${type === 'success' ? 'check-circle' : 'exclamation-circle'} me-2"></i>
                    ${message}
                </div>
            `;
                document.body.appendChild(toast);
                setTimeout(() => toast.remove(), 3000);
            }
        </script>
    </body>

    </html>
<?php
}

// ฟังก์ชันแสดงข้อมูล WiFi
function showWifiInfo($wifiData)
{
?>
    <!DOCTYPE html>
    <html lang="th">

    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>ข้อมูล WiFi</title>
        <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
        <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
        <style>
            body {
                background: #f8f9fa;
                padding: 2rem;
                font-family: 'Sarabun', sans-serif;
            }

            .wifi-box {
                background: white;
                padding: 2rem;
                border-radius: 15px;
                box-shadow: 0 0 20px rgba(0, 0, 0, 0.1);
                max-width: 500px;
                margin: 0 auto;
            }

            .wifi-info {
                background: #f0f9ff;
                border: 1px solid #3b82f6;
                padding: 1.5rem;
                border-radius: 10px;
                margin: 1rem 0;
            }

            .wifi-field {
                margin-bottom: 1rem;
                padding-bottom: 1rem;
                border-bottom: 1px solid #e5e7eb;
            }

            .wifi-field:last-child {
                margin-bottom: 0;
                padding-bottom: 0;
                border-bottom: none;
            }

            .password-display {
                font-family: 'Courier New', monospace;
                font-size: 1.1rem;
                background: #f3f4f6;
                padding: 0.5rem 1rem;
                border-radius: 5px;
                display: inline-block;
            }
        </style>
    </head>

    <body>
        <div class="wifi-box">
            <h4 class="mb-4 text-center">
                <i class="bi bi-wifi"></i> ข้อมูล WiFi
            </h4>

            <div class="wifi-info">
                <div class="wifi-field">
                    <label class="text-muted small">ชื่อเครือข่าย (SSID)</label>
                    <div class="fs-5 fw-bold"><?php echo htmlspecialchars($wifiData['wifi_ssid']); ?></div>
                </div>

                <?php if (!empty($wifiData['wifi_password'])): ?>
                    <div class="wifi-field">
                        <label class="text-muted small">รหัสผ่าน</label>
                        <div class="d-flex align-items-center gap-2">
                            <span class="password-display"
                                id="wifiPassword"><?php echo htmlspecialchars($wifiData['wifi_password']); ?></span>
                            <button class="btn btn-sm btn-outline-primary" onclick="copyWifiPassword()" title="คัดลอกรหัสผ่าน">
                                <i class="bi bi-clipboard"></i>
                            </button>
                        </div>
                    </div>
                <?php endif; ?>

                <div class="wifi-field">
                    <label class="text-muted small">ประเภทการเข้ารหัส</label>
                    <div>
                        <span class="badge bg-secondary">
                            <?php echo htmlspecialchars($wifiData['wifi_security'] ?: 'WPA2'); ?>
                        </span>
                    </div>
                </div>

                <?php if ($wifiData['wifi_hidden']): ?>
                    <div class="wifi-field">
                        <div class="alert alert-warning mb-0 py-2">
                            <i class="bi bi-eye-slash"></i> นี่คือเครือข่ายที่ซ่อนอยู่
                        </div>
                    </div>
                <?php endif; ?>
            </div>

            <div class="alert alert-info">
                <i class="bi bi-info-circle"></i>
                <strong>วิธีใช้:</strong> สแกน QR Code ด้วยกล้องของสมาร์ทโฟนเพื่อเชื่อมต่อ WiFi อัตโนมัติ
            </div>

            <div class="text-center">
                <a href="<?php echo SITE_URL; ?>" class="btn btn-primary">
                    <i class="bi bi-house"></i> กลับหน้าหลัก
                </a>
            </div>
        </div>

        <script>
            function copyWifiPassword() {
                const password = document.getElementById('wifiPassword').textContent;
                if (navigator.clipboard && window.isSecureContext) {
                    navigator.clipboard.writeText(password).then(() => {
                        showToast('คัดลอกรหัสผ่านเรียบร้อยแล้ว!');
                    }).catch(err => {
                        fallbackCopy(password);
                    });
                } else {
                    fallbackCopy(password);
                }
            }

            function fallbackCopy(text) {
                const textArea = document.createElement('textarea');
                textArea.value = text;
                textArea.style.position = 'fixed';
                textArea.style.left = '-999999px';
                document.body.appendChild(textArea);
                textArea.focus();
                textArea.select();
                try {
                    document.execCommand('copy');
                    showToast('คัดลอกรหัสผ่านเรียบร้อยแล้ว!');
                } catch (err) {
                    showToast('ไม่สามารถคัดลอกรหัสผ่านได้', 'error');
                }
                document.body.removeChild(textArea);
            }

            function showToast(message, type = 'success') {
                const toast = document.createElement('div');
                toast.className =
                    `alert alert-${type === 'success' ? 'success' : 'danger'} position-fixed top-0 start-50 translate-middle-x mt-3`;
                toast.style.zIndex = '9999';
                toast.innerHTML = `
                <div class="d-flex align-items-center">
                    <i class="bi bi-${type === 'success' ? 'check-circle' : 'exclamation-circle'} me-2"></i>
                    ${message}
                </div>
            `;
                document.body.appendChild(toast);
                setTimeout(() => toast.remove(), 3000);
            }
        </script>
    </body>

    </html>
<?php
}
?>