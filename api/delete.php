<?php
require_once '../config.php';

header('Content-Type: application/json');

try {
    // รับข้อมูล JSON
    $input = json_decode(file_get_contents('php://input'), true);
    $urlId = $input['id'] ?? 0;

    if (!$urlId) {
        throw new Exception('ไม่พบ ID ที่ต้องการลบ');
    }

    // ตรวจสอบว่ามีลิงก์นี้อยู่จริง
    $stmt = $pdo->prepare("SELECT id, short_code FROM urls WHERE id = ?");
    $stmt->execute([$urlId]);
    $url = $stmt->fetch();

    if (!$url) {
        throw new Exception('ไม่พบลิงก์ที่ต้องการลบ');
    }

    // ลบไฟล์ QR Code
    $qrFile = QR_CACHE_DIR . 'qr_' . $urlId . '.png';
    if (file_exists($qrFile)) {
        unlink($qrFile);
    }

    // ลบโลโก้ (ถ้ามี)
    $stmt = $pdo->prepare("SELECT logo_path FROM qr_styles WHERE url_id = ?");
    $stmt->execute([$urlId]);
    $style = $stmt->fetch();

    if ($style && $style['logo_path']) {
        $logoFile = UPLOAD_DIR . $style['logo_path'];
        if (file_exists($logoFile)) {
            unlink($logoFile);
        }
    }

    // ลบข้อมูลจากฐานข้อมูล (cascade delete จะลบตารางที่เกี่ยวข้องอัตโนมัติ)
    $stmt = $pdo->prepare("DELETE FROM urls WHERE id = ?");
    $stmt->execute([$urlId]);

    $response = [
        'success' => true,
        'message' => 'ลบลิงก์เรียบร้อยแล้ว'
    ];
} catch (Exception $e) {
    $response = [
        'success' => false,
        'message' => $e->getMessage()
    ];
}

echo json_encode($response);
