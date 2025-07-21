<?php
require_once '../config.php';

// ตรวจสอบการล็อกอิน
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: ../login.php');
    exit;
}

// Pagination
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($page - 1) * ITEMS_PER_PAGE;

// Search และ Filter
$search = $_GET['search'] ?? '';
$filter_type = $_GET['type'] ?? '';
$filter_status = $_GET['status'] ?? '';

// สร้าง WHERE clause
$where = [];
$params = [];

if ($search) {
    $where[] = "(short_code LIKE ? OR original_url LIKE ? OR content LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
    $params[] = "%$search%";
}

if ($filter_type) {
    $where[] = "type = ?";
    $params[] = $filter_type;
}

if ($filter_status !== '') {
    $where[] = "is_active = ?";
    $params[] = $filter_status;
}

$whereClause = $where ? 'WHERE ' . implode(' AND ', $where) : '';

// นับจำนวนทั้งหมด
$stmt = $pdo->prepare("SELECT COUNT(*) FROM short_urls $whereClause");
$stmt->execute($params);
$totalItems = $stmt->fetchColumn();
$totalPages = ceil($totalItems / ITEMS_PER_PAGE);

// ดึงข้อมูล
$stmt = $pdo->prepare("
    SELECT su.*, 
           COUNT(DISTINCT cs.id) as unique_clicks,
           MAX(cs.clicked_at) as last_click
    FROM short_urls su
    LEFT JOIN click_stats cs ON su.short_code = cs.short_code
    $whereClause
    GROUP BY su.id
    ORDER BY su.created_at DESC
    LIMIT " . ITEMS_PER_PAGE . " OFFSET $offset
");
$stmt->execute($params);
$urls = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="th">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>จัดการลิงก์ - Admin</title>
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

        .filter-card {
            background: white;
            border-radius: 10px;
            padding: 20px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
            margin-bottom: 20px;
        }

        .url-card {
            background: white;
            border-radius: 10px;
            padding: 15px;
            margin-bottom: 15px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
            transition: transform 0.2s;
        }

        .url-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.1);
        }

        .type-badge {
            font-size: 0.8em;
            padding: 0.25em 0.5em;
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
                    <a class="nav-link" href="users.php">
                        <i class="bi bi-people"></i> จัดการผู้ใช้
                    </a>
                    <a class="nav-link" href="analytics.php">
                        <i class="bi bi-graph-up"></i> Analytics
                    </a>
                    <a class="nav-link" href="api-keys.php">
                        <i class="bi bi-key"></i> API Keys
                    </a>
                    <a class="nav-link" href="bulk-import.php">
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
                <!-- Add URL Button -->
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h1>จัดการลิงก์ทั้งหมด</h1>
                    <div>
                        <a href="../index.php" class="btn btn-outline-primary me-2">
                            <i class="bi bi-plus-circle"></i> สร้างลิงก์ใหม่
                        </a>
                        <span class="badge bg-primary fs-6">
                            <?php echo number_format($totalItems); ?> ลิงก์
                        </span>
                    </div>
                </div>

                <!-- Filters -->
                <div class="filter-card">
                    <form method="GET" class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">ค้นหา</label>
                            <input type="text" name="search" class="form-control"
                                placeholder="Short code, URL, หรือเนื้อหา..."
                                value="<?php echo htmlspecialchars($search); ?>">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">ประเภท</label>
                            <select name="type" class="form-select">
                                <option value="">ทั้งหมด</option>
                                <option value="link" <?php echo $filter_type === 'link' ? 'selected' : ''; ?>>Link
                                </option>
                                <option value="text" <?php echo $filter_type === 'text' ? 'selected' : ''; ?>>Text
                                </option>
                                <option value="wifi" <?php echo $filter_type === 'wifi' ? 'selected' : ''; ?>>WiFi
                                </option>
                            </select>
                        </div>
                        <div class="col-md-2">
                            <label class="form-label">สถานะ</label>
                            <select name="status" class="form-select">
                                <option value="">ทั้งหมด</option>
                                <option value="1" <?php echo $filter_status === '1' ? 'selected' : ''; ?>>Active
                                </option>
                                <option value="0" <?php echo $filter_status === '0' ? 'selected' : ''; ?>>Inactive
                                </option>
                            </select>
                        </div>
                        <div class="col-md-1 d-flex align-items-end">
                            <button type="submit" class="btn btn-primary w-100">
                                <i class="bi bi-search"></i>
                            </button>
                        </div>
                    </form>
                </div>

                <!-- URLs List -->
                <?php if (empty($urls)): ?>
                    <div class="alert alert-info text-center">
                        <i class="bi bi-info-circle"></i> ไม่พบข้อมูลลิงก์
                    </div>
                <?php else: ?>
                    <?php foreach ($urls as $url): ?>
                        <div class="url-card">
                            <div class="row align-items-center">
                                <div class="col-md-6">
                                    <h5 class="mb-1">
                                        <a href="<?php echo SITE_URL . $url['short_code']; ?>" target="_blank"
                                            class="text-decoration-none">
                                            <?php echo SITE_URL . $url['short_code']; ?>
                                        </a>
                                        <span class="badge bg-info type-badge ms-2">
                                            <?php echo strtoupper($url['type']); ?>
                                        </span>
                                        <?php if ($url['password']): ?>
                                            <span class="badge bg-warning ms-1" title="Password Protected">
                                                <i class="bi bi-lock"></i>
                                            </span>
                                        <?php endif; ?>
                                        <?php if ($url['expires_at'] && $url['expires_at'] !== '0000-00-00 00:00:00' && strtotime($url['expires_at']) < time()): ?>
                                            <span class="badge bg-danger ms-1">Expired</span>
                                        <?php endif; ?>
                                    </h5>

                                    <?php if ($url['type'] === 'link'): ?>
                                        <p class="mb-0 text-muted text-truncate" style="max-width: 500px;">
                                            <i class="bi bi-link"></i> <?php echo htmlspecialchars($url['original_url']); ?>
                                        </p>
                                    <?php elseif ($url['type'] === 'text'): ?>
                                        <p class="mb-0 text-muted text-truncate" style="max-width: 500px;">
                                            <i class="bi bi-text-paragraph"></i>
                                            <?php echo htmlspecialchars(substr($url['content'], 0, 100)); ?>...
                                        </p>
                                    <?php elseif ($url['type'] === 'wifi'): ?>
                                        <p class="mb-0 text-muted">
                                            <i class="bi bi-wifi"></i> WiFi: <?php echo htmlspecialchars($url['wifi_ssid']); ?>
                                        </p>
                                    <?php endif; ?>

                                    <small class="text-muted">
                                        <i class="bi bi-calendar"></i>
                                        <?php echo date('d/m/Y H:i', strtotime($url['created_at'])); ?>
                                        <?php if ($url['expires_at'] && $url['expires_at'] !== '0000-00-00 00:00:00'): ?>
                                            | <i class="bi bi-clock"></i> หมดอายุ:
                                            <?php echo date('d/m/Y H:i', strtotime($url['expires_at'])); ?>
                                        <?php endif; ?>
                                    </small>
                                </div>
                                <div class="col-md-3 text-center">
                                    <div class="row">
                                        <div class="col-6">
                                            <strong
                                                class="text-primary"><?php echo number_format($url['clicks']); ?></strong><br>
                                            <small class="text-muted">Total Clicks</small>
                                        </div>
                                        <div class="col-6">
                                            <strong
                                                class="text-success"><?php echo number_format($url['unique_clicks']); ?></strong><br>
                                            <small class="text-muted">Unique</small>
                                        </div>
                                    </div>
                                    <?php if ($url['last_click']): ?>
                                        <small class="text-muted d-block mt-1">
                                            <i class="bi bi-clock"></i> Last:
                                            <?php echo date('d/m H:i', strtotime($url['last_click'])); ?>
                                        </small>
                                    <?php endif; ?>
                                </div>
                                <div class="col-md-3 text-end">
                                    <div class="btn-group" role="group">
                                        <button
                                            class="btn btn-sm btn-outline-<?php echo $url['is_active'] ? 'success' : 'danger'; ?>"
                                            onclick="toggleUrl('<?php echo $url['short_code']; ?>')"
                                            title="<?php echo $url['is_active'] ? 'Active' : 'Inactive'; ?>">
                                            <i class="bi bi-toggle-<?php echo $url['is_active'] ? 'on' : 'off'; ?>"></i>
                                        </button>
                                        <a href="view-url.php?code=<?php echo $url['short_code']; ?>"
                                            class="btn btn-sm btn-outline-info" title="View Details">
                                            <i class="bi bi-eye"></i>
                                        </a>
                                        <a href="edit-url.php?code=<?php echo $url['short_code']; ?>"
                                            class="btn btn-sm btn-outline-primary" title="Edit">
                                            <i class="bi bi-pencil"></i>
                                        </a>
                                        <button class="btn btn-sm btn-outline-danger"
                                            onclick="deleteUrl('<?php echo $url['short_code']; ?>')" title="Delete">
                                            <i class="bi bi-trash"></i>
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>

                <!-- Pagination -->
                <?php if ($totalPages > 1): ?>
                    <nav aria-label="Page navigation" class="mt-4">
                        <ul class="pagination justify-content-center">
                            <li class="page-item <?php echo $page <= 1 ? 'disabled' : ''; ?>">
                                <a class="page-link"
                                    href="?page=<?php echo $page - 1; ?>&<?php echo http_build_query(array_diff_key($_GET, ['page' => ''])); ?>">
                                    <i class="bi bi-chevron-left"></i>
                                </a>
                            </li>

                            <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                                <?php if ($i == 1 || $i == $totalPages || ($i >= $page - 2 && $i <= $page + 2)): ?>
                                    <li class="page-item <?php echo $i == $page ? 'active' : ''; ?>">
                                        <a class="page-link"
                                            href="?page=<?php echo $i; ?>&<?php echo http_build_query(array_diff_key($_GET, ['page' => ''])); ?>">
                                            <?php echo $i; ?>
                                        </a>
                                    </li>
                                <?php elseif ($i == $page - 3 || $i == $page + 3): ?>
                                    <li class="page-item disabled"><span class="page-link">...</span></li>
                                <?php endif; ?>
                            <?php endfor; ?>

                            <li class="page-item <?php echo $page >= $totalPages ? 'disabled' : ''; ?>">
                                <a class="page-link"
                                    href="?page=<?php echo $page + 1; ?>&<?php echo http_build_query(array_diff_key($_GET, ['page' => ''])); ?>">
                                    <i class="bi bi-chevron-right"></i>
                                </a>
                            </li>
                        </ul>
                    </nav>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script>
        function toggleUrl(shortCode) {
            $.post('toggle-url.php', {
                short_code: shortCode
            }, function(response) {
                if (response.success) {
                    location.reload();
                } else {
                    alert('เกิดข้อผิดพลาด: ' + response.message);
                }
            }, 'json').fail(function() {
                alert('เกิดข้อผิดพลาดในการเชื่อมต่อ');
            });
        }

        function deleteUrl(shortCode) {
            if (confirm('คุณแน่ใจที่จะลบลิงก์นี้? การกระทำนี้ไม่สามารถย้อนกลับได้')) {
                $.post('delete-url.php', {
                    short_code: shortCode
                }, function(response) {
                    if (response.success) {
                        location.reload();
                    } else {
                        alert('เกิดข้อผิดพลาด: ' + response.message);
                    }
                }, 'json').fail(function() {
                    alert('เกิดข้อผิดพลาดในการเชื่อมต่อ');
                });
            }
        }

        // Select all functionality
        document.getElementById('selectAll').addEventListener('change', function() {
            const checkboxes = document.querySelectorAll('input[name="selected_urls[]"]');
            checkboxes.forEach(checkbox => {
                checkbox.checked = this.checked;
            });
        });

        // Bulk actions
        function bulkAction(action) {
            const selected = document.querySelectorAll('input[name="selected_urls[]"]:checked');
            if (selected.length === 0) {
                alert('กรุณาเลือกลิงก์ที่ต้องการจัดการ');
                return;
            }

            const shortCodes = Array.from(selected).map(cb => cb.value);
            let confirmMessage = '';

            switch (action) {
                case 'activate':
                    confirmMessage = `เปิดใช้งานลิงก์ ${shortCodes.length} รายการ?`;
                    break;
                case 'deactivate':
                    confirmMessage = `ปิดใช้งานลิงก์ ${shortCodes.length} รายการ?`;
                    break;
                case 'delete':
                    confirmMessage = `ลบลิงก์ ${shortCodes.length} รายการ? การกระทำนี้ไม่สามารถย้อนกลับได้`;
                    break;
            }

            if (confirm(confirmMessage)) {
                $.post('bulk-action.php', {
                    action: action,
                    short_codes: shortCodes
                }, function(response) {
                    if (response.success) {
                        alert(`ดำเนินการสำเร็จ: ${response.message}`);
                        location.reload();
                    } else {
                        alert('เกิดข้อผิดพลาด: ' + response.message);
                    }
                }, 'json').fail(function() {
                    alert('เกิดข้อผิดพลาดในการเชื่อมต่อ');
                });
            }
        }

        // Auto-refresh statistics every 30 seconds
        setInterval(function() {
            const cards = document.querySelectorAll('.url-card');
            cards.forEach(card => {
                // Update click statistics without full page reload
                const shortCode = card.querySelector('a').href.split('/').pop();
                // Implementation would fetch updated stats via AJAX
            });
        }, 30000);

        // Enhanced search functionality
        document.querySelector('form').addEventListener('submit', function(e) {
            const search = document.querySelector('input[name="search"]').value;
            if (search.length > 0 && search.length < 2) {
                e.preventDefault();
                alert('กรุณาใส่คำค้นหาอย่างน้อย 2 ตัวอักษร');
            }
        });

        // Add loading state to buttons
        document.querySelectorAll('button[onclick]').forEach(button => {
            button.addEventListener('click', function() {
                this.disabled = true;
                setTimeout(() => {
                    this.disabled = false;
                }, 2000);
            });
        });
    </script>
</body>

</html>