<?php
require_once '../config.php';

// ตรวจสอบการล็อกอิน
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('HTTP/1.1 403 Forbidden');
    echo json_encode(['success' => false, 'message' => 'Access denied']);
    exit;
}

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

try {
    $deleted = 0;

    // ล้างไฟล์ QR Code cache
    if (is_dir(QR_CACHE_DIR)) {
        $files = glob(QR_CACHE_DIR . '*.png');
        foreach ($files as $file) {
            if (is_file($file)) {
                unlink($file);
                $deleted++;
            }
        }
    }

    // ล้างไฟล์ temporary อื่นๆ
    $tempDir = sys_get_temp_dir();
    $tempFiles = glob($tempDir . '/url_shortener_*');
    foreach ($tempFiles as $file) {
        if (is_file($file)) {
            unlink($file);
            $deleted++;
        }
    }

    echo json_encode([
        'success' => true,
        'message' => 'Cache cleared successfully',
        'deleted' => $deleted
    ]);
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
