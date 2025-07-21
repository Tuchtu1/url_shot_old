<?php
require_once '../config.php';

// ตรวจสอบการล็อกอิน
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: ../login.php');
    exit;
}

$message = '';
$messageType = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['csv_file'])) {
    try {
        $uploadFile = $_FILES['csv_file'];

        if ($uploadFile['error'] !== UPLOAD_ERR_OK) {
            throw new Exception('อัพโหลดไฟล์ล้มเหลว');
        }

        $extension = pathinfo($uploadFile['name'], PATHINFO_EXTENSION);
        if (!in_array(strtolower($extension), ['csv', 'txt'])) {
            throw new Exception('รองรับเฉพาะไฟล์ .csv และ .txt เท่านั้น');
        }

        // ตรวจสอบขนาดไฟล์ (จำกัดที่ 5MB)
        if ($uploadFile['size'] > 5 * 1024 * 1024) {
            throw new Exception('ไฟล์ใหญ่เกินไป (จำกัดที่ 5MB)');
        }

        // อ่านไฟล์
        $file = fopen($uploadFile['tmp_name'], 'r');
        if (!$file) {
            throw new Exception('ไม่สามารถอ่านไฟล์ได้');
        }

        // สร้างบันทึก bulk import
        $stmt = $pdo->prepare("
            INSERT INTO bulk_imports (user_id, filename, status) 
            VALUES (?, ?, 'processing')
        ");
        $stmt->execute([$_SESSION['user_id'], $uploadFile['name']]);
        $importId = $pdo->lastInsertId();

        $total = 0;
        $success = 0;
        $failed = 0;
        $errors = [];

        // ข้ามหัวตาราง (ถ้ามี)
        $header = fgetcsv($file);

        // ประมวลผลแต่ละบรรทัด
        while (($data = fgetcsv($file)) !== FALSE && $total < 1000) { // จำกัดที่ 1000 รายการ
            $total++;

            try {
                $url = trim($data[0]);
                $customCode = isset($data[1]) ? trim($data[1]) : '';
                $password = isset($data[2]) ? trim($data[2]) : '';
                $expiresAt = isset($data[3]) ? trim($data[3]) : '';

                if (empty($url)) {
                    throw new Exception('URL ว่าง');
                }

                if (!validateUrl($url)) {
                    throw new Exception('URL ไม่ถูกต้อง: ' . $url);
                }

                // สร้าง short code
                if (!empty($customCode)) {
                    $shortCode = sanitizeInput($customCode);

                    // ตรวจสอบรูปแบบ custom code
                    if (!preg_match('/^[a-zA-Z0-9_-]+$/', $shortCode)) {
                        throw new Exception('รูปแบบ custom code ไม่ถูกต้อง: ' . $customCode);
                    }

                    // ตรวจสอบว่าใช้แล้วหรือยัง
                    $stmt = $pdo->prepare("SELECT id FROM short_urls WHERE short_code = ?");
                    $stmt->execute([$shortCode]);
                    if ($stmt->fetch()) {
                        throw new Exception('Short code ซ้ำ: ' . $customCode);
                    }
                } else {
                    // สร้าง short code อัตโนมัติ
                    do {
                        $shortCode = generateShortCode();
                        $stmt = $pdo->prepare("SELECT id FROM short_urls WHERE short_code = ?");
                        $stmt->execute([$shortCode]);
                    } while ($stmt->fetch());
                }

                // ตรวจสอบวันหมดอายุ
                $expiry = null;
                if (!empty($expiresAt)) {
                    $expiry = date('Y-m-d H:i:s', strtotime($expiresAt));
                    if (strtotime($expiry) <= time()) {
                        throw new Exception('วันหมดอายุต้องเป็นอนาคต');
                    }
                }

                // บันทึกลงฐานข้อมูล
                $hashedPassword = !empty($password) ? password_hash($password, PASSWORD_DEFAULT) : null;

                $stmt = $pdo->prepare("
                    INSERT INTO short_urls (short_code, original_url, type, password, expires_at, user_ip, user_agent) 
                    VALUES (?, ?, 'link', ?, ?, ?, ?)
                ");
                $stmt->execute([
                    $shortCode,
                    $url,
                    $hashedPassword,
                    $expiry,
                    $_SERVER['REMOTE_ADDR'],
                    $_SERVER['HTTP_USER_AGENT'] ?? ''
                ]);

                $success++;
            } catch (Exception $e) {
                $failed++;
                $errors[] = "บรรทัด $total: " . $e->getMessage();
            }
        }

        fclose($file);

        // อัพเดทสถานะ
        $stmt = $pdo->prepare("
            UPDATE bulk_imports 
            SET total_urls = ?, processed_urls = ?, failed_urls = ?, 
                status = 'completed', completed_at = NOW()
            WHERE id = ?
        ");
        $stmt->execute([$total, $success, $failed, $importId]);

        $message = "นำเข้าข้อมูลเสร็จสิ้น: สำเร็จ $success จาก $total รายการ";
        $messageType = 'success';

        if (count($errors) > 0) {
            $message .= "\n\nข้อผิดพลาด:\n" . implode("\n", array_slice($errors, 0, 10));
            if (count($errors) > 10) {
                $message .= "\n... และอื่นๆ อีก " . (count($errors) - 10) . " รายการ";
            }
        }
    } catch (Exception $e) {
        $message = 'เกิดข้อผิดพลาด: ' . $e->getMessage();
        $messageType = 'danger';

        // อัพเดทสถานะเป็น failed
        if (isset($importId)) {
            $stmt = $pdo->prepare("UPDATE bulk_imports SET status = 'failed' WHERE id = ?");
            $stmt->execute([$importId]);
        }
    }
}

// ดึงประวัติการ import
$stmt = $pdo->prepare("
    SELECT * FROM bulk_imports 
    WHERE user_id = ?
    ORDER BY created_at DESC 
    LIMIT 10
");
$stmt->execute([$_SESSION['user_id']]);
$imports = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="th">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Bulk URL Import - Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <style>
        body {
            background-color: #f8f9fa;
        }

        .sidebar {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            box-shadow: 2px 0 10px rgba(0, 0, 0, 0.1);
        }

        .sidebar .nav-link {
            color: rgba(255, 255, 255, 0.8);
            padding: 15px 20px;
            transition: all 0.3s;
        }

        .sidebar .nav-link:hover {
            color: white;
            background: rgba(255, 255, 255, 0.1);
            padding-left: 30px;
        }

        .sidebar .nav-link.active {
            color: white;
            background: rgba(255, 255, 255, 0.2);
            border-left: 4px solid white;
        }

        .upload-area {
            border: 2px dashed #dee2e6;
            border-radius: 10px;
            padding: 40px;
            text-align: center;
            background: white;
            transition: all 0.3s;
            cursor: pointer;
        }

        .upload-area:hover {
            border-color: #667eea;
            background: #f8f9ff;
        }

        .upload-area.dragover {
            border-color: #667eea;
            background: #e8ecff;
        }

        .progress-container {
            display: none;
            margin-top: 20px;
        }

        .no-data {
            text-align: center;
            color: #6c757d;
            padding: 2rem;
        }
    </style>
</head>

<body>
    <div class="container-fluid">
        <div class="row">
            <!-- Sidebar -->
            <div class="col-md-3 col-lg-2 px-0 sidebar">
                <div class="p-3 text-white">
                    <h4 class="mb-0"><i class="bi bi-speedometer2"></i> Admin Panel</h4>
                    <small>สวัสดี, <?php echo htmlspecialchars($_SESSION['username']); ?></small>
                </div>
                <nav class="nav flex-column">
                    <a class="nav-link" href="index.php">
                        <i class="bi bi-house"></i> Dashboard
                    </a>
                    <a class="nav-link" href="urls.php">
                        <i class="bi bi-link-45deg"></i> จัดการลิงก์
                    </a>
                    <a class="nav-link" href="users.php">
                        <i class="bi bi-people"></i> จัดการผู้ใช้
                    </a>
                    <a class="nav-link" href="analytics.php">
                        <i class="bi bi-graph-up"></i> Analytics
                    </a>
                    <a class="nav-link" href="api-keys.php">
                        <i class="bi bi-key"></i> API Keys
                    </a>
                    <a class="nav-link active" href="bulk-import.php">
                        <i class="bi bi-cloud-upload"></i> Bulk Import
                    </a>
                    <a class="nav-link" href="settings.php">
                        <i class="bi bi-gear"></i> ตั้งค่า
                    </a>
                    <hr class="bg-white">
                    <a class="nav-link" href="../logout.php">
                        <i class="bi bi-box-arrow-left"></i> ออกจากระบบ
                    </a>
                    <a class="nav-link" href="../index.php">
                        <i class="bi bi-arrow-left"></i> กลับหน้าหลัก
                    </a>
                </nav>
            </div>
            <!-- Main Content -->
            <div class="col-md-9 col-lg-10 py-4">
                <h1 class="mb-4">Bulk URL Import</h1>

                <?php if ($message): ?>
                    <div class="alert alert-<?php echo $messageType; ?> alert-dismissible fade show" role="alert">
                        <pre class="mb-0"><?php echo htmlspecialchars($message); ?></pre>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>

                <div class="card mb-4">
                    <div class="card-body">
                        <h5 class="card-title">
                            <i class="bi bi-cloud-upload"></i> นำเข้า URL จากไฟล์ CSV
                        </h5>

                        <form method="POST" enctype="multipart/form-data" id="uploadForm">
                            <div class="upload-area" id="uploadArea">
                                <i class="bi bi-cloud-upload" style="font-size: 48px; color: #667eea;"></i>
                                <h4 class="mt-3">ลากไฟล์มาวางที่นี่</h4>
                                <p class="text-muted">หรือคลิกเพื่อเลือกไฟล์</p>
                                <input type="file" name="csv_file" id="csvFile" accept=".csv,.txt"
                                    style="display: none;">
                                <button type="button" class="btn btn-primary"
                                    onclick="document.getElementById('csvFile').click()">
                                    <i class="bi bi-folder-open"></i> เลือกไฟล์
                                </button>
                            </div>

                            <div class="mt-3" id="fileInfo" style="display: none;">
                                <div class="alert alert-info">
                                    <i class="bi bi-file-earmark-text"></i> ไฟล์ที่เลือก: <strong
                                        id="fileName"></strong>
                                    <span id="fileSize" class="text-muted"></span>
                                </div>
                                <button type="submit" class="btn btn-success">
                                    <i class="bi bi-upload"></i> เริ่มนำเข้าข้อมูล
                                </button>
                            </div>

                            <div class="progress-container">
                                <div class="progress">
                                    <div class="progress-bar" role="progressbar" style="width: 0%"></div>
                                </div>
                                <p class="text-center mt-2">กำลังประมวลผล...</p>
                            </div>
                        </form>

                        <hr class="my-4">

                        <h6><i class="bi bi-info-circle"></i> รูปแบบไฟล์ CSV:</h6>
                        <pre class="bg-light p-3 rounded small">url,custom_code,password,expires_at
https://example.com,mylink,secret123,2024-12-31 23:59:59
https://google.com,,,
https://github.com,gh,password123,</pre>

                        <div class="row mt-3">
                            <div class="col-md-6">
                                <h6>คำอธิบายคอลัมน์:</h6>
                                <ul class="small">
                                    <li><strong>url</strong> - URL ที่ต้องการย่อ (บังคับ)</li>
                                    <li><strong>custom_code</strong> - รหัสกำหนดเอง (ไม่บังคับ)</li>
                                    <li><strong>password</strong> - รหัสผ่านป้องกัน (ไม่บังคับ)</li>
                                    <li><strong>expires_at</strong> - วันหมดอายุ (ไม่บังคับ)</li>
                                </ul>
                            </div>
                            <div class="col-md-6">
                                <h6>ข้อจำกัด:</h6>
                                <ul class="small">
                                    <li>รองรับไฟล์ .csv และ .txt</li>
                                    <li>ขนาดไฟล์ไม่เกิน 5MB</li>
                                    <li>จำกัดไม่เกิน 1,000 URLs ต่อครั้ง</li>
                                    <li>Custom code ใช้ได้: a-z, A-Z, 0-9, -, _</li>
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Import History -->
                <div class="card">
                    <div class="card-body">
                        <h5 class="card-title">
                            <i class="bi bi-clock-history"></i> ประวัติการนำเข้า
                        </h5>

                        <?php if (empty($imports)): ?>
                            <div class="no-data">
                                <i class="bi bi-inbox" style="font-size: 3rem; color: #dee2e6;"></i>
                                <p>ยังไม่มีประวัติการนำเข้า</p>
                            </div>
                        <?php else: ?>
                            <div class="table-responsive">
                                <table class="table">
                                    <thead>
                                        <tr>
                                            <th>วันที่</th>
                                            <th>ไฟล์</th>
                                            <th>จำนวน</th>
                                            <th>สำเร็จ</th>
                                            <th>ล้มเหลว</th>
                                            <th>สถานะ</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($imports as $import): ?>
                                            <tr>
                                                <td><?php echo date('d/m/Y H:i', strtotime($import['created_at'])); ?></td>
                                                <td>
                                                    <i class="bi bi-file-earmark-text"></i>
                                                    <?php echo htmlspecialchars($import['filename']); ?>
                                                </td>
                                                <td><?php echo number_format($import['total_urls']); ?></td>
                                                <td class="text-success">
                                                    <?php echo number_format($import['processed_urls']); ?>
                                                </td>
                                                <td class="text-danger">
                                                    <?php echo number_format($import['failed_urls']); ?>
                                                </td>
                                                <td>
                                                    <?php
                                                    $statusClass = [
                                                        'pending' => 'warning',
                                                        'processing' => 'info',
                                                        'completed' => 'success',
                                                        'failed' => 'danger'
                                                    ];
                                                    $statusIcon = [
                                                        'pending' => 'clock',
                                                        'processing' => 'arrow-repeat',
                                                        'completed' => 'check-circle',
                                                        'failed' => 'x-circle'
                                                    ];
                                                    ?>
                                                    <span class="badge bg-<?php echo $statusClass[$import['status']]; ?>">
                                                        <i class="bi bi-<?php echo $statusIcon[$import['status']]; ?>"></i>
                                                        <?php echo ucfirst($import['status']); ?>
                                                    </span>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Drag and drop functionality
        const uploadArea = document.getElementById('uploadArea');
        const fileInput = document.getElementById('csvFile');
        const fileInfo = document.getElementById('fileInfo');
        const fileName = document.getElementById('fileName');
        const fileSize = document.getElementById('fileSize');
        const uploadForm = document.getElementById('uploadForm');

        // Prevent default drag behaviors
        ['dragenter', 'dragover', 'dragleave', 'drop'].forEach(eventName => {
            uploadArea.addEventListener(eventName, preventDefaults, false);
            document.body.addEventListener(eventName, preventDefaults, false);
        });

        function preventDefaults(e) {
            e.preventDefault();
            e.stopPropagation();
        }

        // Highlight drop area when item is dragged over it
        ['dragenter', 'dragover'].forEach(eventName => {
            uploadArea.addEventListener(eventName, highlight, false);
        });

        ['dragleave', 'drop'].forEach(eventName => {
            uploadArea.addEventListener(eventName, unhighlight, false);
        });

        function highlight(e) {
            uploadArea.classList.add('dragover');
        }

        function unhighlight(e) {
            uploadArea.classList.remove('dragover');
        }

        // Handle dropped files
        uploadArea.addEventListener('drop', handleDrop, false);

        function handleDrop(e) {
            const dt = e.dataTransfer;
            const files = dt.files;
            handleFiles(files);
        }

        fileInput.addEventListener('change', function() {
            handleFiles(this.files);
        });

        function handleFiles(files) {
            if (files.length > 0) {
                const file = files[0];
                fileName.textContent = file.name;
                fileSize.textContent = `(${formatFileSize(file.size)})`;
                fileInfo.style.display = 'block';

                // Set the file input
                const dt = new DataTransfer();
                dt.items.add(file);
                fileInput.files = dt.files;
            }
        }

        function formatFileSize(bytes) {
            if (bytes === 0) return '0 Bytes';
            const k = 1024;
            const sizes = ['Bytes', 'KB', 'MB', 'GB'];
            const i = Math.floor(Math.log(bytes) / Math.log(k));
            return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
        }

        // Show progress on form submit
        uploadForm.addEventListener('submit', function() {
            document.querySelector('.progress-container').style.display = 'block';
            document.querySelector('.upload-area').style.display = 'none';
            document.querySelector('#fileInfo').style.display = 'none';
        });
    </script>
</body>

</html>