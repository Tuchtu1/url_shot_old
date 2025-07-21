<?php
// check_installation.php - ไฟล์ตรวจสอบการติดตั้งระบบ
require_once 'config.php';

?>
<!DOCTYPE html>
<html lang="th">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ตรวจสอบการติดตั้งระบบ</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            padding: 20px;
            background: #f8f9fa;
        }

        .check-item {
            padding: 10px;
            margin: 5px 0;
            border-radius: 5px;
        }

        .check-pass {
            background: #d4edda;
            color: #155724;
        }

        .check-fail {
            background: #f8d7da;
            color: #721c24;
        }

        .check-warn {
            background: #fff3cd;
            color: #856404;
        }
    </style>
</head>

<body>
    <div class="container">
        <h2 class="mb-4">ตรวจสอบการติดตั้งระบบ URL Shortener</h2>

        <?php
        $checks = [];

        // 1. ตรวจสอบ PHP Version
        $phpVersion = phpversion();
        $checks[] = [
            'name' => 'PHP Version',
            'status' => version_compare($phpVersion, '7.0.0', '>='),
            'message' => "PHP $phpVersion" . (version_compare($phpVersion, '7.0.0', '>=') ? ' (ผ่าน)' : ' (ต้องการ 7.0+)')
        ];

        // 2. ตรวจสอบการเชื่อมต่อฐานข้อมูล
        try {
            $testPdo = new PDO(
                "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4",
                DB_USER,
                DB_PASS
            );
            $checks[] = [
                'name' => 'การเชื่อมต่อฐานข้อมูล',
                'status' => true,
                'message' => 'เชื่อมต่อสำเร็จ'
            ];
        } catch (Exception $e) {
            $checks[] = [
                'name' => 'การเชื่อมต่อฐานข้อมูล',
                'status' => false,
                'message' => 'ไม่สามารถเชื่อมต่อได้: ' . $e->getMessage()
            ];
        }

        // 3. ตรวจสอบตารางในฐานข้อมูล
        if (isset($pdo)) {
            try {
                $tables = ['short_urls', 'click_logs'];
                foreach ($tables as $table) {
                    $stmt = $pdo->query("SHOW TABLES LIKE '$table'");
                    $exists = $stmt->rowCount() > 0;
                    $checks[] = [
                        'name' => "ตาราง $table",
                        'status' => $exists,
                        'message' => $exists ? 'มีอยู่' : 'ไม่พบ (ต้องสร้างตาราง)'
                    ];
                }
            } catch (Exception $e) {
                $checks[] = [
                    'name' => 'ตรวจสอบตาราง',
                    'status' => false,
                    'message' => 'Error: ' . $e->getMessage()
                ];
            }
        }

        // 4. ตรวจสอบ PHP Extensions
        $requiredExtensions = ['pdo', 'pdo_mysql', 'gd', 'curl', 'json', 'mbstring'];
        foreach ($requiredExtensions as $ext) {
            $loaded = extension_loaded($ext);
            $checks[] = [
                'name' => "PHP Extension: $ext",
                'status' => $loaded,
                'message' => $loaded ? 'โหลดแล้ว' : 'ไม่พบ (จำเป็นต้องติดตั้ง)'
            ];
        }

        // 5. ตรวจสอบการตั้งค่า PHP
        $uploadMaxFilesize = ini_get('upload_max_filesize');
        $postMaxSize = ini_get('post_max_size');
        $fileUploads = ini_get('file_uploads');

        $checks[] = [
            'name' => 'file_uploads',
            'status' => $fileUploads == '1',
            'message' => $fileUploads == '1' ? 'เปิดใช้งาน' : 'ปิดใช้งาน (ต้องเปิด)'
        ];

        $checks[] = [
            'name' => 'upload_max_filesize',
            'status' => intval($uploadMaxFilesize) >= 50,
            'message' => $uploadMaxFilesize . (intval($uploadMaxFilesize) >= 50 ? ' (เพียงพอ)' : ' (แนะนำ 50M+)')
        ];

        $checks[] = [
            'name' => 'post_max_size',
            'status' => intval($postMaxSize) >= 50,
            'message' => $postMaxSize . (intval($postMaxSize) >= 50 ? ' (เพียงพอ)' : ' (แนะนำ 50M+)')
        ];

        // 6. ตรวจสอบโฟลเดอร์
        $directories = [
            'uploads/' => 'โฟลเดอร์ uploads',
            'uploads/files/' => 'โฟลเดอร์สำหรับไฟล์',
            'uploads/logos/' => 'โฟลเดอร์สำหรับ logo',
            'qr_cache/' => 'โฟลเดอร์ QR Cache'
        ];

        foreach ($directories as $dir => $name) {
            $exists = is_dir($dir);
            $writable = $exists ? is_writable($dir) : false;

            $checks[] = [
                'name' => $name,
                'status' => $exists && $writable,
                'message' => !$exists ? 'ไม่พบโฟลเดอร์' : ($writable ? 'พร้อมใช้งาน' : 'ไม่สามารถเขียนได้')
            ];
        }

        // 7. ตรวจสอบไฟล์ที่จำเป็น
        $requiredFiles = [
            'index.php' => 'หน้าหลัก',
            'create.php' => 'ไฟล์สร้างลิงก์',
            'redirect.php' => 'ไฟล์ redirect',
            'download_file.php' => 'ไฟล์ดาวน์โหลด',
            'config.php' => 'ไฟล์การตั้งค่า'
        ];

        foreach ($requiredFiles as $file => $name) {
            $exists = file_exists($file);
            $checks[] = [
                'name' => $name . " ($file)",
                'status' => $exists,
                'message' => $exists ? 'พบไฟล์' : 'ไม่พบไฟล์'
            ];
        }

        // 8. ตรวจสอบ .htaccess
        if (file_exists('.htaccess')) {
            $htaccess = file_get_contents('.htaccess');
            $hasRewrite = strpos($htaccess, 'RewriteEngine On') !== false;
            $checks[] = [
                'name' => '.htaccess',
                'status' => $hasRewrite,
                'message' => $hasRewrite ? 'มี mod_rewrite' : 'ไม่พบ mod_rewrite'
            ];
        } else {
            $checks[] = [
                'name' => '.htaccess',
                'status' => false,
                'message' => 'ไม่พบไฟล์ .htaccess'
            ];
        }

        // 9. ตรวจสอบ URL Configuration
        $checks[] = [
            'name' => 'SITE_URL',
            'status' => true,
            'message' => SITE_URL
        ];

        // แสดงผลการตรวจสอบ
        $passCount = 0;
        $totalCount = count($checks);

        foreach ($checks as $check) {
            if ($check['status']) $passCount++;

            $class = $check['status'] ? 'check-pass' : 'check-fail';
            $icon = $check['status'] ? '✓' : '✗';

            echo "<div class='check-item $class'>";
            echo "<strong>$icon {$check['name']}:</strong> {$check['message']}";
            echo "</div>";
        }

        // สรุปผล
        echo "<div class='mt-4 p-3 " . ($passCount == $totalCount ? 'check-pass' : 'check-warn') . "'>";
        echo "<h4>สรุปผล: ผ่าน $passCount จาก $totalCount รายการ</h4>";

        if ($passCount < $totalCount) {
            echo "<p>กรุณาแก้ไขรายการที่ไม่ผ่านก่อนใช้งานระบบ</p>";
        } else {
            echo "<p>ระบบพร้อมใช้งาน!</p>";
        }
        echo "</div>";
        ?>

        <div class="mt-4">
            <h4>ทดสอบการอัพโหลดไฟล์</h4>
            <form id="testUpload" enctype="multipart/form-data">
                <div class="mb-3">
                    <input type="file" class="form-control" id="testFile" name="testFile">
                </div>
                <button type="submit" class="btn btn-primary">ทดสอบอัพโหลด</button>
            </form>
            <div id="uploadResult" class="mt-3"></div>
        </div>

        <div class="mt-4">
            <a href="index.php" class="btn btn-success">กลับหน้าหลัก</a>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script>
        $('#testUpload').on('submit', function(e) {
            e.preventDefault();

            var formData = new FormData();
            var file = $('#testFile')[0].files[0];

            if (!file) {
                $('#uploadResult').html('<div class="alert alert-warning">กรุณาเลือกไฟล์</div>');
                return;
            }

            formData.append('type', 'file');
            formData.append('upload_file', file);
            formData.append('file_title', 'Test Upload');

            $.ajax({
                url: 'create.php',
                type: 'POST',
                data: formData,
                processData: false,
                contentType: false,
                success: function(response) {
                    console.log('Response:', response);
                    if (response.success) {
                        $('#uploadResult').html(
                            '<div class="alert alert-success">อัพโหลดสำเร็จ! URL: ' + response
                            .short_url + '</div>');
                    } else {
                        $('#uploadResult').html('<div class="alert alert-danger">Error: ' + response
                            .message + '</div>');
                    }
                },
                error: function(xhr, status, error) {
                    console.error('Error:', xhr.responseText);
                    $('#uploadResult').html('<div class="alert alert-danger">Ajax Error: ' + error +
                        '<br>Response: ' + xhr.responseText + '</div>');
                }
            });
        });
    </script>
</body>

</html>