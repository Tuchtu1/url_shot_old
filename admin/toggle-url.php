<?php
// admin/toggle-url.php
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
    // ดึงสถานะปัจจุบัน
    $stmt = $pdo->prepare("SELECT is_active FROM short_urls WHERE short_code = ?");
    $stmt->execute([$shortCode]);
    $url = $stmt->fetch();

    if (!$url) {
        echo json_encode(['success' => false, 'message' => 'URL not found']);
        exit;
    }

    // สลับสถานะ
    $newStatus = $url['is_active'] ? 0 : 1;

    $stmt = $pdo->prepare("UPDATE short_urls SET is_active = ? WHERE short_code = ?");
    $stmt->execute([$newStatus, $shortCode]);

    echo json_encode([
        'success' => true,
        'message' => 'สถานะอัพเดทเรียบร้อยแล้ว',
        'new_status' => $newStatus
    ]);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}
