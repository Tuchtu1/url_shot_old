<?php
// download.php
require_once 'config.php';

// รับ path ของไฟล์
$file = $_GET['file'] ?? '';

// ตรวจสอบว่าไฟล์อยู่ใน qr_cache
if (empty($file) || !preg_match('/^qr_cache\/[a-zA-Z0-9_\-]+\.png$/', $file)) {
    die('Invalid file');
}

// ตรวจสอบว่าไฟล์มีอยู่จริง
if (!file_exists($file)) {
    die('File not found');
}

// ส่ง headers สำหรับดาวน์โหลด
header('Content-Type: image/png');
header('Content-Disposition: attachment; filename="qrcode_' . date('YmdHis') . '.png"');
header('Content-Length: ' . filesize($file));
header('Cache-Control: no-cache, must-revalidate');
header('Pragma: no-cache');
header('Expires: 0');

// ส่งไฟล์
readfile($file);
exit;
