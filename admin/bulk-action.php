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

$action = $_POST['action'] ?? '';
$shortCodes = $_POST['short_codes'] ?? [];

if (empty($action) || empty($shortCodes) || !is_array($shortCodes)) {
    echo json_encode(['success' => false, 'message' => 'Invalid parameters']);
    exit;
}

// ป้องกัน SQL injection
$placeholders = str_repeat('?,', count($shortCodes) - 1) . '?';

try {
    $pdo->beginTransaction();

    switch ($action) {
        case 'activate':
            $stmt = $pdo->prepare("UPDATE short_urls SET is_active = 1 WHERE short_code IN ($placeholders)");
            $stmt->execute($shortCodes);
            $affected = $stmt->rowCount();
            $message = "เปิดใช้งานลิงก์ $affected รายการ";
            break;

        case 'deactivate':
            $stmt = $pdo->prepare("UPDATE short_urls SET is_active = 0 WHERE short_code IN ($placeholders)");
            $stmt->execute($shortCodes);
            $affected = $stmt->rowCount();
            $message = "ปิดใช้งานลิงก์ $affected รายการ";
            break;

        case 'delete':
            // ลบสถิติก่อน
            $stmt = $pdo->prepare("DELETE FROM click_stats WHERE short_code IN ($placeholders)");
            $stmt->execute($shortCodes);

            // ลบลิงก์
            $stmt = $pdo->prepare("DELETE FROM short_urls WHERE short_code IN ($placeholders)");
            $stmt->execute($shortCodes);
            $affected = $stmt->rowCount();

            // ลบไฟล์ QR Code
            foreach ($shortCodes as $code) {
                $qrPath = QR_CACHE_DIR . $code . '.png';
                if (file_exists($qrPath)) {
                    unlink($qrPath);
                }
            }

            $message = "ลบลิงก์ $affected รายการ";
            break;

        default:
            throw new Exception('Invalid action');
    }

    $pdo->commit();

    echo json_encode([
        'success' => true,
        'message' => $message,
        'affected' => $affected
    ]);
} catch (Exception $e) {
    $pdo->rollBack();
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
