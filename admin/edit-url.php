<?php
// admin/edit-url.php
require_once '../config.php';

// ตรวจสอบการล็อกอิน
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: ../login.php');
    exit;
}

$shortCode = $_GET['code'] ?? '';
$message = '';
$messageType = '';

if (empty($shortCode)) {
    header('Location: urls.php');
    exit;
}

// ดึงข้อมูล URL
$stmt = $pdo->prepare("SELECT * FROM short_urls WHERE short_code = ?");
$stmt->execute([$shortCode]);
$url = $stmt->fetch();

if (!$url) {
    header('Location: urls.php');
    exit;
}

// จัดการฟอร์ม submit
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $type = $url['type']; // ไม่ให้เปลี่ยนประเภท
        $isActive = isset($_POST['is_active']) ? 1 : 0;
        $password = !empty($_POST['password']) ? password_hash($_POST['password'], PASSWORD_DEFAULT) : $url['password'];
        $expiresAt = !empty($_POST['expires_at']) ? $_POST['expires_at'] : null;

        // ถ้าต้องการลบ password
        if (isset($_POST['remove_password']) && $_POST['remove_password'] == '1') {
            $password = null;
        }

        // อัพเดทข้อมูลพื้นฐาน
        $updateQuery = "UPDATE short_urls SET is_active = ?, expires_at = ?";
        $params = [$isActive, $expiresAt];

        // อัพเดท password ถ้ามีการเปลี่ยนแปลง
        if (isset($_POST['password']) && !empty($_POST['password'])) {
            $updateQuery .= ", password = ?";
            $params[] = $password;
        } elseif (isset($_POST['remove_password']) && $_POST['remove_password'] == '1') {
            $updateQuery .= ", password = NULL";
        }

        // อัพเดทข้อมูลตามประเภท
        switch ($type) {
            case 'link':
                $originalUrl = $_POST['original_url'] ?? '';
                if (empty($originalUrl) || !filter_var($originalUrl, FILTER_VALIDATE_URL)) {
                    throw new Exception('URL ไม่ถูกต้อง');
                }
                $updateQuery .= ", original_url = ?";
                $params[] = $originalUrl;
                break;

            case 'text':
                $content = $_POST['content'] ?? '';
                if (empty($content)) {
                    throw new Exception('กรุณากรอกเนื้อหา');
                }
                $updateQuery .= ", content = ?";
                $params[] = $content;
                break;

            case 'wifi':
                $wifiSsid = $_POST['wifi_ssid'] ?? '';
                $wifiPassword = $_POST['wifi_password'] ?? '';
                $wifiSecurity = $_POST['wifi_security'] ?? 'WPA2';
                $wifiHidden = isset($_POST['wifi_hidden']) ? 1 : 0;

                if (empty($wifiSsid)) {
                    throw new Exception('กรุณากรอกชื่อ WiFi');
                }

                $updateQuery .= ", wifi_ssid = ?, wifi_password = ?, wifi_security = ?, wifi_hidden = ?";
                $params[] = $wifiSsid;
                $params[] = $wifiPassword;
                $params[] = $wifiSecurity;
                $params[] = $wifiHidden;
                break;
        }

        // อัพเดท QR Code styling
        if (isset($_POST['update_qr']) && $_POST['update_qr'] == '1') {
            $bgColor = $_POST['bg_color'] ?? '#FFFFFF';
            $fgColor = $_POST['fg_color'] ?? '#000000';
            $dotStyle = $_POST['dot_style'] ?? 'square';

            $updateQuery .= ", bg_color = ?, fg_color = ?, dot_style = ?";
            $params[] = $bgColor;
            $params[] = $fgColor;
            $params[] = $dotStyle;

            // สร้าง QR Code ใหม่
            $shortUrl = SITE_URL . $url['short_code'];
            $qrFilename = QR_CACHE_DIR . $url['short_code'] . '.png';

            // ลบ QR Code เก่า
            if (file_exists($qrFilename)) {
                unlink($qrFilename);
            }

            // สร้าง QR Code ใหม่ด้วย styling
            generateStyledQRCode($shortUrl, $qrFilename, 300, $bgColor, $fgColor, $dotStyle);
        }

        $updateQuery .= " WHERE short_code = ?";
        $params[] = $shortCode;

        $stmt = $pdo->prepare($updateQuery);
        $stmt->execute($params);

        $message = 'บันทึกข้อมูลเรียบร้อยแล้ว';
        $messageType = 'success';

        // โหลดข้อมูลใหม่
        $stmt = $pdo->prepare("SELECT * FROM short_urls WHERE short_code = ?");
        $stmt->execute([$shortCode]);
        $url = $stmt->fetch();
    } catch (Exception $e) {
        $message = 'เกิดข้อผิดพลาด: ' . $e->getMessage();
        $messageType = 'danger';
    }
}
?>
<!DOCTYPE html>
<html lang="th">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>แก้ไข URL - <?php echo htmlspecialchars($shortCode); ?></title>
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
            background: rgba(255, 255, 255, 0.1);
            border-left: 3px solid white;
        }

        .edit-card {
            background: white;
            border-radius: 10px;
            padding: 30px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
        }

        .form-label {
            font-weight: 600;
            color: #495057;
        }

        .qr-preview {
            max-width: 200px;
            border: 1px solid #ddd;
            border-radius: 10px;
            padding: 10px;
        }

        .style-preview {
            width: 60px;
            height: 60px;
            border: 2px solid #dee2e6;
            border-radius: 8px;
            cursor: pointer;
            transition: all 0.3s;
            margin: 5px;
            display: inline-block;
        }

        .style-preview:hover {
            transform: scale(1.1);
            border-color: #6c757d;
        }

        .style-preview.selected {
            border-color: #0d6efd;
            box-shadow: 0 0 0 3px rgba(13, 110, 253, 0.25);
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
                    <a class="nav-link active" href="urls.php">
                        <i class="bi bi-link-45deg"></i> จัดการลิงก์
                    </a>
                    <a class="nav-link" href="analytics.php">
                        <i class="bi bi-graph-up"></i> Analytics
                    </a>
                    <a class="nav-link" href="settings.php">
                        <i class="bi bi-gear"></i> ตั้งค่า
                    </a>
                    <hr class="bg-white">
                    <a class="nav-link" href="../logout.php">
                        <i class="bi bi-box-arrow-left"></i> ออกจากระบบ
                    </a>
                </nav>
            </div>

            <!-- Main Content -->
            <div class="col-md-9 col-lg-10 py-4">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h1>แก้ไข URL</h1>
                    <div>
                        <a href="view-url.php?code=<?php echo $shortCode; ?>" class="btn btn-outline-secondary">
                            <i class="bi bi-eye"></i> ดูรายละเอียด
                        </a>
                        <a href="urls.php" class="btn btn-outline-secondary">
                            <i class="bi bi-arrow-left"></i> กลับ
                        </a>
                    </div>
                </div>

                <?php if ($message): ?>
                    <div class="alert alert-<?php echo $messageType; ?> alert-dismissible fade show" role="alert">
                        <?php echo $message; ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>

                <div class="row">
                    <div class="col-lg-8">
                        <form method="POST" class="edit-card">
                            <h5 class="mb-4">ข้อมูลพื้นฐาน</h5>

                            <!-- Short Code (Read-only) -->
                            <div class="mb-3">
                                <label class="form-label">Short Code</label>
                                <input type="text" class="form-control"
                                    value="<?php echo htmlspecialchars($url['short_code']); ?>" readonly>
                                <small class="text-muted">Short URL:
                                    <?php echo SITE_URL . $url['short_code']; ?></small>
                            </div>

                            <!-- Type (Read-only) -->
                            <div class="mb-3">
                                <label class="form-label">ประเภท</label>
                                <input type="text" class="form-control" value="<?php echo strtoupper($url['type']); ?>"
                                    readonly>
                            </div>

                            <!-- Content based on type -->
                            <?php if ($url['type'] === 'link'): ?>
                                <div class="mb-3">
                                    <label for="original_url" class="form-label">Original URL</label>
                                    <input type="url" class="form-control" id="original_url" name="original_url"
                                        value="<?php echo htmlspecialchars($url['original_url']); ?>" required>
                                </div>
                            <?php elseif ($url['type'] === 'text'): ?>
                                <div class="mb-3">
                                    <label for="content" class="form-label">เนื้อหา</label>
                                    <textarea class="form-control" id="content" name="content" rows="5"
                                        required><?php echo htmlspecialchars($url['content']); ?></textarea>
                                </div>
                            <?php elseif ($url['type'] === 'wifi'): ?>
                                <div class="mb-3">
                                    <label for="wifi_ssid" class="form-label">WiFi SSID</label>
                                    <input type="text" class="form-control" id="wifi_ssid" name="wifi_ssid"
                                        value="<?php echo htmlspecialchars($url['wifi_ssid']); ?>" required>
                                </div>
                                <div class="mb-3">
                                    <label for="wifi_password" class="form-label">WiFi Password</label>
                                    <input type="text" class="form-control" id="wifi_password" name="wifi_password"
                                        value="<?php echo htmlspecialchars($url['wifi_password']); ?>">
                                </div>
                                <div class="mb-3">
                                    <label for="wifi_security" class="form-label">Security Type</label>
                                    <select class="form-select" id="wifi_security" name="wifi_security">
                                        <option value="WPA2"
                                            <?php echo $url['wifi_security'] === 'WPA2' ? 'selected' : ''; ?>>WPA/WPA2
                                        </option>
                                        <option value="WEP"
                                            <?php echo $url['wifi_security'] === 'WEP' ? 'selected' : ''; ?>>WEP</option>
                                        <option value="nopass"
                                            <?php echo $url['wifi_security'] === 'nopass' ? 'selected' : ''; ?>>No Password
                                        </option>
                                    </select>
                                </div>
                                <div class="form-check mb-3">
                                    <input class="form-check-input" type="checkbox" id="wifi_hidden" name="wifi_hidden"
                                        <?php echo $url['wifi_hidden'] ? 'checked' : ''; ?>>
                                    <label class="form-check-label" for="wifi_hidden">
                                        Hidden Network
                                    </label>
                                </div>
                            <?php endif; ?>

                            <hr class="my-4">

                            <!-- Security Settings -->
                            <h5 class="mb-4">การตั้งค่าความปลอดภัย</h5>

                            <!-- Password Protection -->
                            <div class="mb-3">
                                <label for="password" class="form-label">Password Protection</label>
                                <?php if ($url['password']): ?>
                                    <div class="alert alert-info mb-2">
                                        <i class="bi bi-lock"></i> URL นี้มีการป้องกันด้วยรหัสผ่านอยู่แล้ว
                                    </div>
                                    <div class="form-check mb-2">
                                        <input class="form-check-input" type="checkbox" id="remove_password"
                                            name="remove_password" value="1">
                                        <label class="form-check-label" for="remove_password">
                                            ลบรหัสผ่าน
                                        </label>
                                    </div>
                                <?php endif; ?>
                                <input type="password" class="form-control" id="password" name="password"
                                    placeholder="<?php echo $url['password'] ? 'เว้นว่างถ้าไม่ต้องการเปลี่ยน' : 'เว้นว่างถ้าไม่ต้องการตั้งรหัสผ่าน'; ?>">
                                <small class="text-muted">ผู้ใช้จะต้องกรอกรหัสผ่านก่อนเข้าถึง URL</small>
                            </div>

                            <!-- Expiry Date -->
                            <div class="mb-3">
                                <label for="expires_at" class="form-label">วันหมดอายุ</label>
                                <input type="datetime-local" class="form-control" id="expires_at" name="expires_at"
                                    value="<?php echo $url['expires_at'] && $url['expires_at'] !== '0000-00-00 00:00:00' ? date('Y-m-d\TH:i', strtotime($url['expires_at'])) : ''; ?>">
                                <small class="text-muted">เว้นว่างถ้าไม่ต้องการให้หมดอายุ</small>
                            </div>

                            <!-- Status -->
                            <div class="form-check mb-3">
                                <input class="form-check-input" type="checkbox" id="is_active" name="is_active"
                                    <?php echo $url['is_active'] ? 'checked' : ''; ?>>
                                <label class="form-check-label" for="is_active">
                                    เปิดใช้งาน URL นี้
                                </label>
                            </div>

                            <div class="d-grid gap-2 d-md-block">
                                <button type="submit" class="btn btn-primary">
                                    <i class="bi bi-save"></i> บันทึกการเปลี่ยนแปลง
                                </button>
                                <a href="urls.php" class="btn btn-secondary">
                                    <i class="bi bi-x-circle"></i> ยกเลิก
                                </a>
                            </div>
                        </form>
                    </div>

                    <!-- QR Code Settings -->
                    <div class="col-lg-4">
                        <div class="edit-card">
                            <h5 class="mb-4">QR Code</h5>

                            <!-- QR Preview -->
                            <div class="text-center mb-4">
                                <?php
                                $qrPath = QR_CACHE_DIR . $url['short_code'] . '.png';
                                if (file_exists($qrPath)): ?>
                                    <img src="<?php echo '/' . $qrPath; ?>" class="qr-preview mb-3" alt="QR Code">
                                <?php else: ?>
                                    <div class="qr-preview d-flex align-items-center justify-content-center mb-3">
                                        <span class="text-muted">No QR Code</span>
                                    </div>
                                <?php endif; ?>
                            </div>

                            <form method="POST">
                                <input type="hidden" name="update_qr" value="1">

                                <!-- Background Color -->
                                <div class="mb-3">
                                    <label for="bg_color" class="form-label">สีพื้นหลัง</label>
                                    <div class="input-group">
                                        <input type="color" class="form-control form-control-color" id="bg_color"
                                            name="bg_color" value="<?php echo $url['bg_color'] ?: '#FFFFFF'; ?>">
                                        <input type="text" class="form-control"
                                            value="<?php echo $url['bg_color'] ?: '#FFFFFF'; ?>" readonly>
                                    </div>
                                </div>

                                <!-- Foreground Color -->
                                <div class="mb-3">
                                    <label for="fg_color" class="form-label">สี QR Code</label>
                                    <div class="input-group">
                                        <input type="color" class="form-control form-control-color" id="fg_color"
                                            name="fg_color" value="<?php echo $url['fg_color'] ?: '#000000'; ?>">
                                        <input type="text" class="form-control"
                                            value="<?php echo $url['fg_color'] ?: '#000000'; ?>" readonly>
                                    </div>
                                </div>

                                <!-- Dot Style -->
                                <div class="mb-3">
                                    <label class="form-label">รูปแบบจุด</label>
                                    <div class="d-flex flex-wrap">
                                        <?php
                                        $styles = ['square', 'dots', 'rounded', 'extra-rounded', 'classy'];
                                        $currentStyle = $url['dot_style'] ?: 'square';
                                        foreach ($styles as $style): ?>
                                            <div class="style-preview <?php echo $currentStyle === $style ? 'selected' : ''; ?>"
                                                data-style="<?php echo $style; ?>"
                                                onclick="selectStyle('<?php echo $style; ?>')">
                                            </div>
                                        <?php endforeach; ?>
                                    </div>
                                    <input type="hidden" id="dot_style" name="dot_style"
                                        value="<?php echo $currentStyle; ?>">
                                </div>

                                <button type="submit" class="btn btn-secondary w-100">
                                    <i class="bi bi-qr-code"></i> อัพเดท QR Code
                                </button>
                            </form>
                        </div>

                        <!-- Statistics -->
                        <div class="edit-card mt-3">
                            <h5 class="mb-3">สถิติ</h5>
                            <div class="d-flex justify-content-between mb-2">
                                <span>จำนวนคลิกทั้งหมด:</span>
                                <strong><?php echo number_format($url['clicks']); ?></strong>
                            </div>
                            <div class="d-flex justify-content-between mb-2">
                                <span>สร้างเมื่อ:</span>
                                <span><?php echo date('d/m/Y', strtotime($url['created_at'])); ?></span>
                            </div>
                            <?php if ($url['expires_at'] && $url['expires_at'] !== '0000-00-00 00:00:00'): ?>
                                <div class="d-flex justify-content-between">
                                    <span>หมดอายุ:</span>
                                    <span><?php echo date('d/m/Y', strtotime($url['expires_at'])); ?></span>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Color picker sync
        document.getElementById('bg_color').addEventListener('change', function() {
            this.nextElementSibling.value = this.value.toUpperCase();
        });

        document.getElementById('fg_color').addEventListener('change', function() {
            this.nextElementSibling.value = this.value.toUpperCase();
        });

        // Style selection
        function selectStyle(style) {
            document.querySelectorAll('.style-preview').forEach(el => {
                el.classList.remove('selected');
            });
            document.querySelector(`[data-style="${style}"]`).classList.add('selected');
            document.getElementById('dot_style').value = style;
        }

        // Password removal toggle
        document.getElementById('remove_password')?.addEventListener('change', function() {
            const passwordInput = document.getElementById('password');
            if (this.checked) {
                passwordInput.disabled = true;
                passwordInput.value = '';
                passwordInput.placeholder = 'รหัสผ่านจะถูกลบ';
            } else {
                passwordInput.disabled = false;
                passwordInput.placeholder = 'เว้นว่างถ้าไม่ต้องการเปลี่ยน';
            }
        });
    </script>
</body>

</html>