<?php
// config.php

// Debug mode (เปลี่ยนเป็น false ใน production)
define('DEBUG_MODE', true);

// การตั้งค่าฐานข้อมูล
define('DB_HOST', 'localhost');
define('DB_NAME', 'url_shortener_db');
define('DB_USER', 'root');
define('DB_PASS', '');

// การตั้งค่าเว็บไซต์
define('SITE_URL', 'http://10.40.11.168/url-shortener/');
define('SHORT_URL_LENGTH', 6);
define('ENABLE_PASSWORD_PROTECTION', true);
define('MAX_URL_LENGTH', 2048);

// การตั้งค่า QR Code
define('QR_SIZE', 300);
define('QR_MARGIN', 10);
define('QR_ERROR_CORRECTION', 'L');
define('UPLOAD_DIR', 'uploads/');
define('QR_CACHE_DIR', 'qr_cache/');

// การตั้งค่าการอัพโหลดไฟล์
define('FILE_UPLOAD_DIR', 'uploads/files/');
define('MAX_FILE_SIZE', 52428800); // 50MB in bytes
define('ALLOWED_FILE_EXTENSIONS', [
    // Documents
    'pdf',
    'doc',
    'docx',
    'xls',
    'xlsx',
    'ppt',
    'pptx',
    'odt',
    'ods',
    'odp',
    // Images
    'jpg',
    'jpeg',
    'png',
    'gif',
    'bmp',
    'svg',
    'webp',
    // Archives
    'zip',
    'rar',
    '7z',
    'tar',
    'gz',
    // Text
    'txt',
    'csv',
    'xml',
    'json',
    // Media
    'mp3',
    'mp4',
    'avi',
    'mov',
    'wmv',
    'flv',
    'mkv',
    'wav',
    'flac',
    // Others
    'rtf',
    'epub',
    'mobi'
]);

// ไฟล์ที่ห้ามอัพโหลด (เพื่อความปลอดภัย)
define('BLOCKED_FILE_EXTENSIONS', [
    'php',
    'php3',
    'php4',
    'php5',
    'phtml',
    'exe',
    'sh',
    'bat',
    'cmd',
    'com',
    'scr',
    'vbs',
    'js',
    'jar',
    'msi',
    'dll',
    'app'
]);

// การตั้งค่าการจำกัดการดาวน์โหลด
define('DEFAULT_DOWNLOAD_LIMIT', null); // null = ไม่จำกัด
define('ENABLE_DOWNLOAD_TRACKING', true);

// การตั้งค่า API
define('API_RATE_LIMIT', 100);
define('API_VERSION', 'v1');

// การตั้งค่าระบบ
define('ITEMS_PER_PAGE', 20);
define('TIMEZONE', 'Asia/Bangkok');
date_default_timezone_set(TIMEZONE);

// การตั้งค่าความปลอดภัย
define('ENABLE_CSRF_PROTECTION', true);
define('SESSION_TIMEOUT', 3600); // 1 hour
define('MAX_LOGIN_ATTEMPTS', 5);
define('LOCKOUT_TIME', 900); // 15 minutes

// การตั้งค่าการทำความสะอาดอัตโนมัติ
define('AUTO_CLEANUP_ENABLED', true);
define('CLEANUP_EXPIRED_DAYS', 30); // ลบไฟล์ที่หมดอายุเกิน 30 วัน
define('CLEANUP_TEMP_FILES_HOURS', 24); // ลบไฟล์ชั่วคราวที่เก่ากว่า 24 ชั่วโมง

// เชื่อมต่อฐานข้อมูล
try {
    $pdo = new PDO(
        "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4",
        DB_USER,
        DB_PASS,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false
        ]
    );
} catch (PDOException $e) {
    $isAjaxRequest = !empty($_SERVER['HTTP_X_REQUESTED_WITH']) &&
        strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest';

    if ($isAjaxRequest) {
        header('Content-Type: application/json');
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'message' => 'ไม่สามารถเชื่อมต่อฐานข้อมูลได้: ' . $e->getMessage()
        ]);
        exit;
    } else {
        die("Connection failed: " . $e->getMessage());
    }
}

// ฟังก์ชันสร้างรหัสสั้น
function generateShortCode($length = SHORT_URL_LENGTH)
{
    global $pdo;
    $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';

    do {
        $shortCode = '';
        for ($i = 0; $i < $length; $i++) {
            $shortCode .= $characters[rand(0, strlen($characters) - 1)];
        }

        // ตรวจสอบว่ารหัสนี้ไม่ซ้ำในฐานข้อมูล
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM short_urls WHERE short_code = ?");
        $stmt->execute([$shortCode]);
        $exists = $stmt->fetchColumn() > 0;
    } while ($exists);

    return $shortCode;
}

// ฟังก์ชันตรวจสอบ URL
function validateUrl($url)
{
    if (strlen($url) > MAX_URL_LENGTH) {
        return false;
    }
    return filter_var($url, FILTER_VALIDATE_URL) !== false;
}

// ฟังก์ชันทำความสะอาดข้อมูล
function sanitizeInput($input)
{
    return htmlspecialchars(trim($input), ENT_QUOTES, 'UTF-8');
}

// ฟังก์ชันตรวจสอบนามสกุลไฟล์
function isAllowedFileExtension($filename)
{
    $extension = strtolower(pathinfo($filename, PATHINFO_EXTENSION));

    // ตรวจสอบว่าไม่ใช่ไฟล์ต้องห้าม
    if (in_array($extension, BLOCKED_FILE_EXTENSIONS)) {
        return false;
    }

    // ตรวจสอบว่าอยู่ในรายการที่อนุญาต
    return in_array($extension, ALLOWED_FILE_EXTENSIONS);
}

// ฟังก์ชันสร้างชื่อไฟล์ที่ปลอดภัย
function generateSafeFileName($originalName)
{
    $extension = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));
    $baseName = pathinfo($originalName, PATHINFO_FILENAME);

    // ทำความสะอาดชื่อไฟล์
    $safeName = preg_replace('/[^a-zA-Z0-9_-]/', '_', $baseName);
    $safeName = substr($safeName, 0, 50); // จำกัดความยาว

    // สร้างชื่อไฟล์ใหม่พร้อม unique ID
    return uniqid() . '_' . $safeName . '.' . $extension;
}

// ฟังก์ชันแปลงขนาดไฟล์
function formatFileSize($bytes)
{
    if ($bytes === 0) return '0 Bytes';

    $k = 1024;
    $sizes = ['Bytes', 'KB', 'MB', 'GB'];
    $i = floor(log($bytes) / log($k));

    return round($bytes / pow($k, $i), 2) . ' ' . $sizes[$i];
}

// ฟังก์ชันส่ง JSON response
function sendJsonResponse($success, $message = '', $data = [])
{
    if (ob_get_level()) {
        ob_end_clean();
    }

    header('Content-Type: application/json; charset=utf-8');
    header('Cache-Control: no-cache, must-revalidate');

    $response = [
        'success' => $success,
        'message' => $message
    ];

    if (!empty($data)) {
        $response = array_merge($response, $data);
    }

    echo json_encode($response, JSON_UNESCAPED_UNICODE);
    exit;
}

// สร้างโฟลเดอร์ที่จำเป็น
$directories = [
    UPLOAD_DIR,
    QR_CACHE_DIR,
    FILE_UPLOAD_DIR,
    FILE_UPLOAD_DIR . 'temp/'
];

foreach ($directories as $dir) {
    if (!file_exists($dir)) {
        mkdir($dir, 0755, true);

        // สร้าง index.html เพื่อป้องกันการ list directory
        file_put_contents($dir . 'index.html', '<!DOCTYPE html><html><head><title>403 Forbidden</title></head><body><h1>Forbidden</h1></body></html>');
    }
}

// สร้าง .htaccess สำหรับโฟลเดอร์ uploads/files/ ถ้ายังไม่มี
$htaccessPath = FILE_UPLOAD_DIR . '.htaccess';
if (!file_exists($htaccessPath)) {
    $htaccessContent = "# Deny direct access to files
Order Deny,Allow
Deny from all

# Block PHP execution
<FilesMatch \"\.(?:php|php3|php4|php5|phtml)$\">
    Order Deny,Allow
    Deny from all
</FilesMatch>

# Disable directory listing
Options -Indexes";

    file_put_contents($htaccessPath, $htaccessContent);
}

// เริ่ม session ด้วยการตั้งค่าความปลอดภัย
if (session_status() == PHP_SESSION_NONE) {
    ini_set('session.cookie_httponly', 1);
    ini_set('session.use_only_cookies', 1);
    ini_set('session.cookie_secure', isset($_SERVER['HTTPS']) ? 1 : 0);
    ini_set('session.cookie_samesite', 'Strict');
    session_start();
}

// ตรวจสอบ session timeout
if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity'] > SESSION_TIMEOUT)) {
    session_unset();
    session_destroy();
    session_start();
}
$_SESSION['last_activity'] = time();

// ฟังก์ชันสร้าง CSRF token
function generateCSRFToken()
{
    if (!isset($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

// ฟังก์ชันตรวจสอบ CSRF token
function verifyCSRFToken($token)
{
    if (!ENABLE_CSRF_PROTECTION) {
        return true;
    }

    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}

// ฟังก์ชันสร้าง QR Code พื้นฐาน (คงเดิม)
function generateQRCode($data, $filename = null, $size = 300)
{
    $qrUrl = 'https://api.qrserver.com/v1/create-qr-code/?' . http_build_query([
        'size' => $size . 'x' . $size,
        'data' => $data,
        'format' => 'png',
        'ecc' => 'L'
    ]);

    if ($filename) {
        $dir = dirname($filename);
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }

        $originalErrorReporting = error_reporting(0);

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $qrUrl);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

        $qrData = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        error_reporting($originalErrorReporting);

        if ($qrData !== false && $httpCode === 200) {
            file_put_contents($filename, $qrData);
            return str_replace($_SERVER['DOCUMENT_ROOT'], '', $filename);
        }
    }

    return $qrUrl;
}

// ฟังก์ชันสร้าง QR Code พร้อม styling (คงเดิม)
function generateStyledQRCode($data, $filename = null, $size = 300, $bgColor = '#FFFFFF', $fgColor = '#000000', $dotStyle = 'square', $logoPath = null)
{
    // แปลงสีจาก hex เป็น RGB
    $bgColor = str_replace('#', '', $bgColor);
    $fgColor = str_replace('#', '', $fgColor);

    // ใช้ qr-server.com API พร้อม parameters สำหรับ styling
    $params = [
        'size' => $size . 'x' . $size,
        'data' => $data,
        'format' => 'png',
        'ecc' => $logoPath ? 'M' : 'L', // ใช้ M ถ้ามี logo
        'bgcolor' => $bgColor,
        'color' => $fgColor,
        'qzone' => 1,
        'margin' => 1
    ];

    $qrUrl = 'https://api.qrserver.com/v1/create-qr-code/?' . http_build_query($params);

    if ($filename) {
        $dir = dirname($filename);
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $qrUrl);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

        $qrData = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($qrData !== false && $httpCode === 200) {
            file_put_contents($filename, $qrData);

            // ถ้ามี logo และมี GD library ให้เพิ่ม logo
            if ($logoPath && file_exists($logoPath) && extension_loaded('gd')) {
                addLogoToQRCode($filename, $logoPath);
            }

            return $filename;
        }
    }

    return $qrUrl;
}

// ฟังก์ชันเพิ่ม logo ลงบน QR Code (คงเดิม)
function addLogoToQRCode($qrPath, $logoPath)
{
    if (!extension_loaded('gd')) {
        return false;
    }

    // โหลด QR Code
    $qr = @imagecreatefrompng($qrPath);
    if (!$qr) return false;

    // โหลด logo
    $logoInfo = @getimagesize($logoPath);
    if (!$logoInfo) return false;

    switch ($logoInfo['mime']) {
        case 'image/png':
            $logo = @imagecreatefrompng($logoPath);
            break;
        case 'image/jpeg':
        case 'image/jpg':
            $logo = @imagecreatefromjpeg($logoPath);
            break;
        case 'image/gif':
            $logo = @imagecreatefromgif($logoPath);
            break;
        default:
            return false;
    }

    if (!$logo) return false;

    // คำนวณขนาด logo (20% ของ QR Code)
    $qrWidth = imagesx($qr);
    $qrHeight = imagesy($qr);
    $logoWidth = imagesx($logo);
    $logoHeight = imagesy($logo);

    $logoQrWidth = $qrWidth / 5;
    $logoQrHeight = $logoHeight / $logoWidth * $logoQrWidth;

    // สร้าง logo ขนาดใหม่
    $logoResized = imagecreatetruecolor($logoQrWidth, $logoQrHeight);
    imagealphablending($logoResized, false);
    imagesavealpha($logoResized, true);

    // Resize logo
    imagecopyresampled($logoResized, $logo, 0, 0, 0, 0, $logoQrWidth, $logoQrHeight, $logoWidth, $logoHeight);

    // คำนวณตำแหน่งกลาง
    $logoX = ($qrWidth - $logoQrWidth) / 2;
    $logoY = ($qrHeight - $logoQrHeight) / 2;

    // สร้างพื้นหลังสีขาวสำหรับ logo
    $white = imagecolorallocate($qr, 255, 255, 255);
    imagefilledrectangle($qr, $logoX - 5, $logoY - 5, $logoX + $logoQrWidth + 5, $logoY + $logoQrHeight + 5, $white);

    // วาง logo บน QR Code
    imagecopy($qr, $logoResized, $logoX, $logoY, 0, 0, $logoQrWidth, $logoQrHeight);

    // บันทึกภาพ
    imagepng($qr, $qrPath);

    // ทำความสะอาดหน่วยความจำ
    imagedestroy($qr);
    imagedestroy($logo);
    imagedestroy($logoResized);

    return true;
}

// ฟังก์ชันทำความสะอาดไฟล์ที่หมดอายุ
function cleanupExpiredFiles()
{
    global $pdo;

    if (!AUTO_CLEANUP_ENABLED) {
        return;
    }

    try {
        // ลบไฟล์ที่หมดอายุ
        $stmt = $pdo->prepare("
            SELECT id, file_path, short_code 
            FROM short_urls 
            WHERE type = 'file' 
            AND expires_at IS NOT NULL 
            AND expires_at < DATE_SUB(NOW(), INTERVAL ? DAY)
        ");
        $stmt->execute([CLEANUP_EXPIRED_DAYS]);

        while ($row = $stmt->fetch()) {
            // ลบไฟล์จริง
            if (file_exists($row['file_path'])) {
                unlink($row['file_path']);
            }

            // ลบ QR Code
            $qrPath = QR_CACHE_DIR . $row['short_code'] . '.png';
            if (file_exists($qrPath)) {
                unlink($qrPath);
            }

            // ลบข้อมูลจากฐานข้อมูล
            $deleteStmt = $pdo->prepare("DELETE FROM short_urls WHERE id = ?");
            $deleteStmt->execute([$row['id']]);
        }

        // ลบไฟล์ temp ที่เก่า
        $tempDir = FILE_UPLOAD_DIR . 'temp/';
        if (is_dir($tempDir)) {
            $files = glob($tempDir . '*');
            $now = time();

            foreach ($files as $file) {
                if (is_file($file)) {
                    if ($now - filemtime($file) >= CLEANUP_TEMP_FILES_HOURS * 3600) {
                        unlink($file);
                    }
                }
            }
        }
    } catch (Exception $e) {
        error_log('Cleanup error: ' . $e->getMessage());
    }
}

// เรียกใช้การทำความสะอาดเมื่อมีโอกาส (1% chance)
if (rand(1, 100) === 1) {
    cleanupExpiredFiles();
}
