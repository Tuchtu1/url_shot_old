<?php
// admin/delete-url.php
require_once '../config.php';

// ตรวจสอบการล็อกอิน
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

// ตรวจสอบว่าเป็น POST request
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

$shortCode = $_POST['short_code'] ?? '';

if (empty($shortCode)) {
    echo json_encode(['success' => false, 'message' => 'Short code is required']);
    exit;
}

try {
    // ตรวจสอบว่า URL มีอยู่จริง
    $stmt = $pdo->prepare("SELECT id, qr_code_path FROM short_urls WHERE short_code = ?");
    $stmt->execute([$shortCode]);
    $url = $stmt->fetch();

    if (!$url) {
        echo json_encode(['success' => false, 'message' => 'URL not found']);
        exit;
    }

    // ลบ QR Code file ถ้ามี
    $qrPath = QR_CACHE_DIR . $shortCode . '.png';
    if (file_exists($qrPath)) {
        unlink($qrPath);
    }

    // ลบ logo file ถ้ามี
    if (!empty($url['logo_path']) && file_exists($url['logo_path'])) {
        unlink($url['logo_path']);
    }

    // ลบข้อมูลจากฐานข้อมูล (click_logs จะถูกลบอัตโนมัติเพราะมี ON DELETE CASCADE)
    $stmt = $pdo->prepare("DELETE FROM short_urls WHERE short_code = ?");
    $stmt->execute([$shortCode]);

    // ลบข้อมูลจาก click_stats ถ้ามี
    $stmt = $pdo->prepare("DELETE FROM click_stats WHERE short_code = ?");
    $stmt->execute([$shortCode]);

    echo json_encode([
        'success' => true,
        'message' => 'ลบ URL เรียบร้อยแล้ว'
    ]);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}
