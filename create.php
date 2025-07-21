<?php
// create_fixed.php - ใช้แทน create.php เดิม
require_once 'config.php';

// ปิดการแสดง error ที่ไม่จำเป็น
error_reporting(E_ERROR | E_PARSE);
ini_set('display_errors', 0);

// ตรวจสอบว่าเป็น POST request
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    sendJsonResponse(false, 'Method not allowed');
    exit;
}

// ล้าง output buffer
if (ob_get_level()) {
    ob_end_clean();
}

// เริ่ม output buffering ใหม่
ob_start();

try {
    // รับค่า type
    $type = isset($_POST['type']) ? $_POST['type'] : 'link';

    // Debug log
    error_log('CREATE.PHP - Type: ' . $type);
    error_log('CREATE.PHP - POST: ' . print_r($_POST, true));
    error_log('CREATE.PHP - FILES: ' . print_r($_FILES, true));

    // รับค่า styling
    $bgColor = isset($_POST['bg_color']) ? $_POST['bg_color'] : '#FFFFFF';
    $fgColor = isset($_POST['fg_color']) ? $_POST['fg_color'] : '#000000';
    $dotStyle = isset($_POST['dot_style']) ? $_POST['dot_style'] : 'square';

    // จัดการ logo upload (ถ้ามี)
    $logoPath = null;
    if (isset($_FILES['logo']) && $_FILES['logo']['error'] === UPLOAD_ERR_OK) {
        $uploadedFile = $_FILES['logo'];
        $extension = strtolower(pathinfo($uploadedFile['name'], PATHINFO_EXTENSION));
        $allowedExtensions = ['png', 'jpg', 'jpeg', 'gif'];

        if (in_array($extension, $allowedExtensions) && $uploadedFile['size'] <= 2097152) {
            $logoFilename = uniqid() . '.' . $extension;
            $logoPath = UPLOAD_DIR . $logoFilename;

            if (!is_dir(UPLOAD_DIR)) {
                mkdir(UPLOAD_DIR, 0755, true);
            }

            move_uploaded_file($uploadedFile['tmp_name'], $logoPath);
        }
    }

    $response = array();

    switch ($type) {
        case 'link':
            $originalUrl = isset($_POST['original_url']) ? $_POST['original_url'] : '';
            $customCode = isset($_POST['custom_code']) ? $_POST['custom_code'] : '';
            $password = isset($_POST['password']) ? $_POST['password'] : '';
            $expiresAt = isset($_POST['expires_at']) ? $_POST['expires_at'] : null;

            // ตรวจสอบ URL
            if (!validateUrl($originalUrl)) {
                sendJsonResponse(false, 'URL ไม่ถูกต้อง');
                exit;
            }

            // สร้างรหัสสั้น
            $shortCode = $customCode ? sanitizeInput($customCode) : generateShortCode();

            // ตรวจสอบว่ารหัสซ้ำหรือไม่
            $stmt = $pdo->prepare("SELECT id FROM short_urls WHERE short_code = ?");
            $stmt->execute([$shortCode]);
            if ($stmt->fetch()) {
                sendJsonResponse(false, 'รหัสนี้ถูกใช้แล้ว กรุณาใช้รหัสอื่น');
                exit;
            }

            // เพิ่มข้อมูลลงฐานข้อมูล
            $stmt = $pdo->prepare("
                INSERT INTO short_urls (short_code, original_url, password, expires_at, type, user_ip, user_agent, created_at) 
                VALUES (?, ?, ?, ?, ?, ?, ?, NOW())
            ");
            $stmt->execute([
                $shortCode,
                $originalUrl,
                $password ? password_hash($password, PASSWORD_DEFAULT) : null,
                $expiresAt,
                'link',
                $_SERVER['REMOTE_ADDR'] ?? null,
                $_SERVER['HTTP_USER_AGENT'] ?? null
            ]);

            $urlId = $pdo->lastInsertId();
            $shortUrl = SITE_URL . $shortCode;

            $response['short_url'] = $shortUrl;
            $response['url_id'] = $urlId;

            // สร้าง QR Code
            $qrFilename = QR_CACHE_DIR . $shortCode . '.png';
            $qrCodeUrl = generateStyledQRCode($shortUrl, $qrFilename, 300, $bgColor, $fgColor, $dotStyle, $logoPath);

            if (file_exists($qrFilename)) {
                $response['qr_code'] = $qrFilename;
            } else {
                $response['qr_code'] = $qrCodeUrl;
            }

            sendJsonResponse(true, 'สร้างลิงก์สำเร็จ', $response);
            break;

        case 'text':
            $textContent = isset($_POST['text_content']) ? $_POST['text_content'] : '';

            if (empty($textContent)) {
                sendJsonResponse(false, 'กรุณาใส่ข้อความ');
                exit;
            }

            $shortCode = generateShortCode();

            $stmt = $pdo->prepare("
                INSERT INTO short_urls (short_code, content, type, user_ip, user_agent, created_at) 
                VALUES (?, ?, ?, ?, ?, NOW())
            ");
            $stmt->execute([
                $shortCode,
                $textContent,
                'text',
                $_SERVER['REMOTE_ADDR'] ?? null,
                $_SERVER['HTTP_USER_AGENT'] ?? null
            ]);

            $urlId = $pdo->lastInsertId();

            $response['short_url'] = SITE_URL . $shortCode;
            $response['url_id'] = $urlId;

            // สร้าง QR Code สำหรับข้อความ
            $qrFilename = QR_CACHE_DIR . $shortCode . '.png';
            $qrCodeUrl = generateStyledQRCode($textContent, $qrFilename, 300, $bgColor, $fgColor, $dotStyle, $logoPath);

            if (file_exists($qrFilename)) {
                $response['qr_code'] = $qrFilename;
            } else {
                $response['qr_code'] = $qrCodeUrl;
            }

            sendJsonResponse(true, 'สร้าง QR Code สำเร็จ', $response);
            break;

        case 'wifi':
            $ssid = isset($_POST['ssid']) ? $_POST['ssid'] : '';
            $wifiPassword = isset($_POST['wifi_password']) ? $_POST['wifi_password'] : '';
            $securityType = isset($_POST['security_type']) ? $_POST['security_type'] : 'WPA2';
            $hidden = isset($_POST['hidden']) ? 1 : 0;

            if (empty($ssid)) {
                sendJsonResponse(false, 'กรุณาใส่ชื่อ WiFi');
                exit;
            }

            $shortCode = generateShortCode();

            $stmt = $pdo->prepare("
                INSERT INTO short_urls (short_code, type, wifi_ssid, wifi_password, wifi_security, wifi_hidden, user_ip, user_agent, created_at) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW())
            ");
            $stmt->execute([
                $shortCode,
                'wifi',
                $ssid,
                $wifiPassword,
                $securityType,
                $hidden,
                $_SERVER['REMOTE_ADDR'] ?? null,
                $_SERVER['HTTP_USER_AGENT'] ?? null
            ]);

            $urlId = $pdo->lastInsertId();

            $response['short_url'] = SITE_URL . $shortCode;
            $response['url_id'] = $urlId;

            // สร้าง WiFi QR Code
            $wifiString = "WIFI:T:$securityType;S:$ssid;P:$wifiPassword;H:" . ($hidden ? 'true' : 'false') . ";;";
            $qrFilename = QR_CACHE_DIR . $shortCode . '.png';
            $qrCodeUrl = generateStyledQRCode($wifiString, $qrFilename, 300, $bgColor, $fgColor, $dotStyle, $logoPath);

            if (file_exists($qrFilename)) {
                $response['qr_code'] = $qrFilename;
            } else {
                $response['qr_code'] = $qrCodeUrl;
            }

            sendJsonResponse(true, 'สร้าง WiFi QR Code สำเร็จ', $response);
            break;

        case 'file':
            // ตรวจสอบการอัพโหลดไฟล์
            if (!isset($_FILES['upload_file']) || $_FILES['upload_file']['error'] !== UPLOAD_ERR_OK) {
                $errorMessage = 'กรุณาเลือกไฟล์ที่ต้องการอัพโหลด';

                if (isset($_FILES['upload_file']['error'])) {
                    switch ($_FILES['upload_file']['error']) {
                        case UPLOAD_ERR_INI_SIZE:
                            $errorMessage = 'ไฟล์มีขนาดใหญ่เกินค่า upload_max_filesize ใน php.ini';
                            break;
                        case UPLOAD_ERR_FORM_SIZE:
                            $errorMessage = 'ไฟล์มีขนาดใหญ่เกินค่า MAX_FILE_SIZE ในฟอร์ม';
                            break;
                        case UPLOAD_ERR_PARTIAL:
                            $errorMessage = 'อัพโหลดไฟล์ไม่สมบูรณ์';
                            break;
                        case UPLOAD_ERR_NO_FILE:
                            $errorMessage = 'ไม่มีไฟล์ถูกอัพโหลด';
                            break;
                        case UPLOAD_ERR_NO_TMP_DIR:
                            $errorMessage = 'ไม่พบโฟลเดอร์ temp';
                            break;
                        case UPLOAD_ERR_CANT_WRITE:
                            $errorMessage = 'ไม่สามารถเขียนไฟล์ลงดิสก์';
                            break;
                        case UPLOAD_ERR_EXTENSION:
                            $errorMessage = 'PHP extension หยุดการอัพโหลด';
                            break;
                    }
                }

                sendJsonResponse(false, $errorMessage);
                exit;
            }

            $uploadedFile = $_FILES['upload_file'];
            $fileTitle = isset($_POST['file_title']) ? $_POST['file_title'] : $uploadedFile['name'];
            $customCode = isset($_POST['custom_code']) ? $_POST['custom_code'] : '';
            $password = isset($_POST['password']) ? $_POST['password'] : '';
            $expiresAt = isset($_POST['expires_at']) ? $_POST['expires_at'] : null;
            $downloadLimit = isset($_POST['download_limit']) ? intval($_POST['download_limit']) : null;

            // ตรวจสอบขนาดไฟล์
            if ($uploadedFile['size'] > MAX_FILE_SIZE) {
                sendJsonResponse(false, 'ไฟล์มีขนาดใหญ่เกินไป (สูงสุด ' . formatFileSize(MAX_FILE_SIZE) . ')');
                exit;
            }

            // ตรวจสอบประเภทไฟล์
            $fileExtension = strtolower(pathinfo($uploadedFile['name'], PATHINFO_EXTENSION));
            if (!isAllowedFileExtension($uploadedFile['name'])) {
                sendJsonResponse(false, 'ประเภทไฟล์ ' . $fileExtension . ' ไม่อนุญาตให้อัพโหลด');
                exit;
            }

            // สร้างโฟลเดอร์สำหรับเก็บไฟล์
            if (!is_dir(FILE_UPLOAD_DIR)) {
                mkdir(FILE_UPLOAD_DIR, 0755, true);
            }

            // สร้างชื่อไฟล์ที่ปลอดภัย
            $safeFileName = generateSafeFileName($uploadedFile['name']);
            $filePath = FILE_UPLOAD_DIR . $safeFileName;

            // ย้ายไฟล์ไปยังโฟลเดอร์
            if (!move_uploaded_file($uploadedFile['tmp_name'], $filePath)) {
                sendJsonResponse(false, 'ไม่สามารถอัพโหลดไฟล์ได้');
                exit;
            }

            // สร้างรหัสสั้น
            $shortCode = $customCode ? sanitizeInput($customCode) : generateShortCode();

            // ตรวจสอบว่ารหัสซ้ำหรือไม่
            $stmt = $pdo->prepare("SELECT id FROM short_urls WHERE short_code = ?");
            $stmt->execute([$shortCode]);
            if ($stmt->fetch()) {
                // ลบไฟล์ที่อัพโหลดไปแล้ว
                unlink($filePath);
                sendJsonResponse(false, 'รหัสนี้ถูกใช้แล้ว กรุณาใช้รหัสอื่น');
                exit;
            }

            // บันทึกข้อมูลลงฐานข้อมูล
            $stmt = $pdo->prepare("
                INSERT INTO short_urls (
                    short_code, type, content, password, expires_at,
                    file_path, file_name, file_size, file_type, download_limit,
                    user_ip, user_agent, created_at
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())
            ");

            $stmt->execute([
                $shortCode,
                'file',
                $fileTitle,
                $password ? password_hash($password, PASSWORD_DEFAULT) : null,
                $expiresAt,
                $filePath,
                $uploadedFile['name'],
                $uploadedFile['size'],
                $uploadedFile['type'],
                $downloadLimit,
                $_SERVER['REMOTE_ADDR'] ?? null,
                $_SERVER['HTTP_USER_AGENT'] ?? null
            ]);

            $urlId = $pdo->lastInsertId();
            $shortUrl = SITE_URL . $shortCode;

            $response['short_url'] = $shortUrl;
            $response['url_id'] = $urlId;

            // สร้าง QR Code สำหรับลิงก์ดาวน์โหลด
            $qrFilename = QR_CACHE_DIR . $shortCode . '.png';
            $qrCodeUrl = generateStyledQRCode($shortUrl, $qrFilename, 300, $bgColor, $fgColor, $dotStyle, $logoPath);

            if (file_exists($qrFilename)) {
                $response['qr_code'] = $qrFilename;
            } else {
                $response['qr_code'] = $qrCodeUrl;
            }

            sendJsonResponse(true, 'อัพโหลดไฟล์สำเร็จ', $response);
            break;

        default:
            sendJsonResponse(false, 'ประเภทข้อมูลไม่ถูกต้อง: ' . $type);
            break;
    }
} catch (PDOException $e) {
    error_log('Database error in create.php: ' . $e->getMessage());
    sendJsonResponse(false, 'เกิดข้อผิดพลาดฐานข้อมูล: ' . $e->getMessage());
} catch (Exception $e) {
    error_log('General error in create.php: ' . $e->getMessage());
    sendJsonResponse(false, 'เกิดข้อผิดพลาด: ' . $e->getMessage());
} finally {
    // ลบไฟล์ logo ชั่วคราวถ้ามี
    if ($logoPath && file_exists($logoPath)) {
        unlink($logoPath);
    }
}
