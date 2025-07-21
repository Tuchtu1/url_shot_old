<?php
// download_file_fixed.php - ใช้แทน download_file.php
require_once 'config.php';

// รับ short code
$shortCode = $_GET['code'] ?? '';

if (empty($shortCode)) {
    die('ไม่พบรหัสไฟล์');
}

try {
    // ดึงข้อมูลไฟล์
    $stmt = $pdo->prepare("
        SELECT * FROM short_urls 
        WHERE short_code = ? AND type = 'file' AND is_active = 1
    ");
    $stmt->execute([$shortCode]);
    $fileData = $stmt->fetch();

    if (!$fileData) {
        die('ไม่พบไฟล์ที่ต้องการ');
    }

    // Debug: แสดงข้อมูลไฟล์ (comment out ใน production)
    if (DEBUG_MODE) {
        echo "<pre>";
        echo "File Data:\n";
        echo "ID: " . $fileData['id'] . "\n";
        echo "Short Code: " . $fileData['short_code'] . "\n";
        echo "File Name: " . $fileData['file_name'] . "\n";
        echo "Expires At: " . $fileData['expires_at'] . "\n";
        echo "Current Time: " . date('Y-m-d H:i:s') . "\n";
        echo "Download Count: " . $fileData['download_count'] . "/" . ($fileData['download_limit'] ?? 'unlimited') . "\n";
        echo "</pre>";

        // ถ้าเป็น debug mode ให้ข้ามการตรวจสอบวันหมดอายุ
        // Comment บรรทัดนี้ออกถ้าต้องการทดสอบการหมดอายุ
        // $fileData['expires_at'] = null;
    }

    // ตรวจสอบว่าไฟล์ยังอยู่หรือไม่
    if (!file_exists($fileData['file_path'])) {
        die('ไฟล์ถูกลบหรือย้ายไปแล้ว (Path: ' . $fileData['file_path'] . ')');
    }

    // ตรวจสอบวันหมดอายุ (ปรับปรุงการตรวจสอบ)
    if (
        !empty($fileData['expires_at']) &&
        $fileData['expires_at'] !== '0000-00-00 00:00:00' &&
        $fileData['expires_at'] !== null
    ) {

        $expiryTime = strtotime($fileData['expires_at']);
        $currentTime = time();

        if ($expiryTime !== false && $expiryTime < $currentTime) {
            die('ไฟล์หมดอายุแล้ว (หมดอายุเมื่อ: ' . date('d/m/Y H:i', $expiryTime) . ')');
        }
    }

    // ตรวจสอบจำนวนดาวน์โหลด
    if ($fileData['download_limit'] && $fileData['download_count'] >= $fileData['download_limit']) {
        die('ไฟล์ถึงจำนวนดาวน์โหลดสูงสุดแล้ว (' . $fileData['download_count'] . '/' . $fileData['download_limit'] . ')');
    }

    // ตรวจสอบรหัสผ่าน (ถ้ามี)
    if ($fileData['password']) {
        session_start();
        $sessionKey = 'auth_' . $fileData['id'];
        if (!isset($_SESSION[$sessionKey])) {
            header('Location: ' . SITE_URL . $shortCode);
            exit;
        }
    }

    // อัพเดทจำนวนดาวน์โหลด
    $stmt = $pdo->prepare("
        UPDATE short_urls 
        SET download_count = download_count + 1,
            clicks = clicks + 1
        WHERE id = ?
    ");
    $stmt->execute([$fileData['id']]);

    // บันทึกสถิติการดาวน์โหลด
    try {
        $stmt = $pdo->prepare("
            INSERT INTO click_logs (url_id, ip_address, user_agent, referer, clicked_at) 
            VALUES (?, ?, ?, ?, NOW())
        ");
        $stmt->execute([
            $fileData['id'],
            $_SERVER['REMOTE_ADDR'] ?? null,
            $_SERVER['HTTP_USER_AGENT'] ?? null,
            $_SERVER['HTTP_REFERER'] ?? null
        ]);
    } catch (Exception $e) {
        // ไม่ต้องหยุดการดาวน์โหลดถ้าบันทึก log ไม่สำเร็จ
        error_log('Failed to log download: ' . $e->getMessage());
    }

    // เตรียมส่งไฟล์
    $filePath = $fileData['file_path'];
    $fileName = $fileData['file_name'];
    $fileSize = filesize($filePath);

    // ล้าง output buffer
    while (ob_get_level()) {
        ob_end_clean();
    }

    // กำหนดประเภทไฟล์ที่ถูกต้อง
    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mimeType = finfo_file($finfo, $filePath);
    finfo_close($finfo);

    // ถ้าไม่สามารถหา mime type ได้ ใช้ application/octet-stream
    if (!$mimeType) {
        $mimeType = 'application/octet-stream';
    }

    // ตั้งค่า headers สำหรับดาวน์โหลด
    header('Content-Type: ' . $mimeType);
    header('Content-Transfer-Encoding: Binary');
    header('Content-Length: ' . $fileSize);

    // ใช้ rawurlencode สำหรับชื่อไฟล์ที่มีภาษาไทยหรืออักขระพิเศษ
    $encodedFileName = rawurlencode($fileName);
    header('Content-Disposition: attachment; filename="' . $fileName . '"; filename*=UTF-8\'\'' . $encodedFileName);

    header('Cache-Control: no-cache, no-store, must-revalidate');
    header('Pragma: no-cache');
    header('Expires: 0');

    // ส่งไฟล์
    $handle = fopen($filePath, 'rb');
    if ($handle === false) {
        die('ไม่สามารถเปิดไฟล์ได้');
    }

    // ส่งไฟล์เป็นชุดๆ เพื่อประหยัดหน่วยความจำ
    while (!feof($handle)) {
        echo fread($handle, 8192);
        flush();
    }

    fclose($handle);
    exit;
} catch (Exception $e) {
    error_log('Download error: ' . $e->getMessage());
    die('เกิดข้อผิดพลาดในการดาวน์โหลด: ' . $e->getMessage());
}
