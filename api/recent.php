<?php
require_once '../config.php';

try {
    // ดึงลิงก์ล่าสุด 10 รายการ
    $stmt = $pdo->prepare("
        SELECT 
            u.id,
            u.short_code,
            u.original_url,
            u.url_type,
            u.click_count,
            u.created_at,
            u.password IS NOT NULL as has_password,
            u.expires_at,
            CASE 
                WHEN u.url_type = 'text' THEN tc.content
                WHEN u.url_type = 'wifi' THEN wc.ssid
                ELSE u.original_url
            END as display_text
        FROM urls u
        LEFT JOIN text_contents tc ON u.id = tc.url_id
        LEFT JOIN wifi_configs wc ON u.id = wc.url_id
        WHERE u.is_active = 1
        ORDER BY u.created_at DESC
        LIMIT 10
    ");
    $stmt->execute();
    $urls = $stmt->fetchAll();

    if (count($urls) > 0) {
        foreach ($urls as $url) {
            $shortUrl = SITE_URL . $url['short_code'];
            $icon = '';
            $typeLabel = '';
            $displayText = '';

            switch ($url['url_type']) {
                case 'link':
                    $icon = 'bi-link-45deg';
                    $typeLabel = 'ลิงก์';
                    $displayText = strlen($url['display_text']) > 50
                        ? substr($url['display_text'], 0, 50) . '...'
                        : $url['display_text'];
                    break;
                case 'text':
                    $icon = 'bi-text-paragraph';
                    $typeLabel = 'ข้อความ';
                    $displayText = strlen($url['display_text']) > 50
                        ? substr($url['display_text'], 0, 50) . '...'
                        : $url['display_text'];
                    break;
                case 'wifi':
                    $icon = 'bi-wifi';
                    $typeLabel = 'WiFi';
                    $displayText = 'SSID: ' . $url['display_text'];
                    break;
            }

            // ตรวจสอบสถานะ
            $status = '';
            if ($url['has_password']) {
                $status .= '<span class="badge bg-warning ms-1" title="มีรหัสผ่าน"><i class="bi bi-lock"></i></span>';
            }
            if ($url['expires_at']) {
                $expiryDate = new DateTime($url['expires_at']);
                $now = new DateTime();
                if ($expiryDate < $now) {
                    $status .= '<span class="badge bg-danger ms-1">หมดอายุ</span>';
                } else {
                    $days = $now->diff($expiryDate)->days;
                    if ($days <= 7) {
                        $status .= '<span class="badge bg-info ms-1">หมดอายุใน ' . $days . ' วัน</span>';
                    }
                }
            }

?>
            <div class="list-group-item">
                <div class="d-flex w-100 justify-content-between">
                    <div class="flex-grow-1">
                        <h6 class="mb-1">
                            <i class="<?php echo $icon; ?>"></i>
                            <span class="text-muted small"><?php echo $typeLabel; ?></span>
                            <?php echo $status; ?>
                        </h6>
                        <p class="mb-1 text-truncate">
                            <a href="<?php echo $shortUrl; ?>" target="_blank" class="text-decoration-none">
                                <?php echo $shortUrl; ?>
                            </a>
                            <button class="btn btn-sm btn-outline-secondary ms-2" onclick="copyLink('<?php echo $shortUrl; ?>')">
                                <i class="bi bi-clipboard"></i>
                            </button>
                        </p>
                        <small class="text-muted d-block text-truncate">
                            <?php echo htmlspecialchars($displayText); ?>
                        </small>
                    </div>
                    <div class="text-end">
                        <small class="text-muted d-block">
                            <i class="bi bi-eye"></i> <?php echo number_format($url['click_count']); ?>
                        </small>
                        <small class="text-muted d-block">
                            <?php echo date('d/m/y H:i', strtotime($url['created_at'])); ?>
                        </small>
                        <div class="btn-group btn-group-sm mt-1" role="group">
                            <button class="btn btn-outline-primary" onclick="showQR('<?php echo $url['short_code']; ?>')"
                                title="ดู QR Code">
                                <i class="bi bi-qr-code"></i>
                            </button>
                            <button class="btn btn-outline-info" onclick="showStats(<?php echo $url['id']; ?>)" title="ดูสถิติ">
                                <i class="bi bi-bar-chart"></i>
                            </button>
                            <button class="btn btn-outline-danger" onclick="deleteUrl(<?php echo $url['id']; ?>)" title="ลบ">
                                <i class="bi bi-trash"></i>
                            </button>
                        </div>
                    </div>
                </div>
            </div>
<?php
        }
    } else {
        echo '<div class="alert alert-info">ยังไม่มีลิงก์ที่สร้าง</div>';
    }
} catch (Exception $e) {
    echo '<div class="alert alert-danger">เกิดข้อผิดพลาด: ' . $e->getMessage() . '</div>';
}
?>

<script>
    function copyLink(url) {
        navigator.clipboard.writeText(url).then(() => {
            // แสดงข้อความแจ้ง
            const btn = event.target.closest('button');
            const originalHTML = btn.innerHTML;
            btn.innerHTML = '<i class="bi bi-check"></i>';
            btn.classList.add('btn-success');
            btn.classList.remove('btn-outline-secondary');

            setTimeout(() => {
                btn.innerHTML = originalHTML;
                btn.classList.remove('btn-success');
                btn.classList.add('btn-outline-secondary');
            }, 2000);
        });
    }

    function showQR(shortCode) {
        window.open('<?php echo SITE_URL; ?>qr/' + shortCode, 'QR Code', 'width=400,height=450');
    }

    function showStats(urlId) {
        window.location.href = '<?php echo SITE_URL; ?>stats.php?id=' + urlId;
    }

    function deleteUrl(urlId) {
        if (confirm('คุณแน่ใจที่จะลบลิงก์นี้?')) {
            fetch('<?php echo SITE_URL; ?>api/delete.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({
                        id: urlId
                    })
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        loadRecentUrls(); // รีโหลดรายการ
                    } else {
                        alert('เกิดข้อผิดพลาด: ' + data.message);
                    }
                });
        }
    }
</script>