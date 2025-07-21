<?php
require_once '../../config.php';

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, GET');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

$response = ['success' => false];

try {
    // ตรวจสอบ API Key
    $apiKey = $_SERVER['HTTP_AUTHORIZATION'] ?? $_REQUEST['api_key'] ?? '';
    $apiKey = str_replace('Bearer ', '', $apiKey);

    if (empty($apiKey)) {
        throw new Exception('API key is required', 401);
    }

    // ตรวจสอบ user จาก API key
    $stmt = $pdo->prepare("SELECT id, username FROM users WHERE api_key = ? AND is_active = 1");
    $stmt->execute([$apiKey]);
    $user = $stmt->fetch();

    if (!$user) {
        throw new Exception('Invalid API key', 401);
    }

    // บันทึก API usage
    $stmt = $pdo->prepare("
        INSERT INTO api_usage (user_id, endpoint, request_date, request_count) 
        VALUES (?, 'shorten', CURDATE(), 1)
        ON DUPLICATE KEY UPDATE request_count = request_count + 1
    ");
    $stmt->execute([$user['id']]);

    // ตรวจสอบ rate limit (100 requests per day)
    $stmt = $pdo->prepare("
        SELECT request_count FROM api_usage 
        WHERE user_id = ? AND request_date = CURDATE() AND endpoint = 'shorten'
    ");
    $stmt->execute([$user['id']]);
    $usage = $stmt->fetch();

    if ($usage && $usage['request_count'] > 100) {
        throw new Exception('Rate limit exceeded (100 requests per day)', 429);
    }

    // รับข้อมูล
    $data = json_decode(file_get_contents('php://input'), true);
    if (!$data) {
        $data = $_REQUEST;
    }

    $url = $data['url'] ?? '';
    $customCode = $data['custom_code'] ?? '';
    $password = $data['password'] ?? '';
    $expiresAt = $data['expires_at'] ?? '';

    if (empty($url)) {
        throw new Exception('URL is required', 400);
    }

    if (!validateUrl($url)) {
        throw new Exception('Invalid URL format', 400);
    }

    // สร้าง short code
    if (!empty($customCode)) {
        $shortCode = sanitizeInput($customCode);

        // ตรวจสอบว่าใช้แล้วหรือยัง
        $stmt = $pdo->prepare("SELECT id FROM urls WHERE short_code = ?");
        $stmt->execute([$shortCode]);
        if ($stmt->fetch()) {
            throw new Exception('Custom code already exists', 409);
        }
    } else {
        do {
            $shortCode = generateShortCode();
            $stmt = $pdo->prepare("SELECT id FROM urls WHERE short_code = ?");
            $stmt->execute([$shortCode]);
        } while ($stmt->fetch());
    }

    // บันทึกลงฐานข้อมูล
    $hashedPassword = !empty($password) ? password_hash($password, PASSWORD_DEFAULT) : null;

    $stmt = $pdo->prepare("
        INSERT INTO urls (short_code, original_url, url_type, password, expires_at, user_id) 
        VALUES (?, ?, 'link', ?, ?, ?)
    ");
    $stmt->execute([$shortCode, $url, $hashedPassword, $expiresAt ?: null, $user['id']]);

    $urlId = $pdo->lastInsertId();
    $shortUrl = SITE_URL . $shortCode;

    // สร้าง QR Code
    $qrApiUrl = 'https://api.qrserver.com/v1/create-qr-code/?';
    $qrParams = [
        'size' => '300x300',
        'data' => $shortUrl,
        'format' => 'png'
    ];
    $qrUrl = $qrApiUrl . http_build_query($qrParams);

    $response = [
        'success' => true,
        'data' => [
            'id' => $urlId,
            'short_url' => $shortUrl,
            'short_code' => $shortCode,
            'qr_code' => $qrUrl,
            'created_at' => date('c'),
            'expires_at' => $expiresAt ?: null
        ]
    ];
} catch (Exception $e) {
    http_response_code($e->getCode() ?: 500);
    $response = [
        'success' => false,
        'error' => [
            'code' => $e->getCode() ?: 500,
            'message' => $e->getMessage()
        ]
    ];
}

echo json_encode($response, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);

/**
 * API Documentation:
 * 
 * POST /api/v1/shorten.php
 * 
 * Headers:
 *   Authorization: Bearer YOUR_API_KEY
 * 
 * Body (JSON):
 * {
 *   "url": "https://example.com",
 *   "custom_code": "my-link" (optional),
 *   "password": "secret123" (optional),
 *   "expires_at": "2024-12-31 23:59:59" (optional)
 * }
 * 
 * Response:
 * {
 *   "success": true,
 *   "data": {
 *     "id": 123,
 *     "short_url": "http://yourdomain.com/abc123",
 *     "short_code": "abc123",
 *     "qr_code": "https://api.qrserver.com/...",
 *     "created_at": "2024-01-01T12:00:00+00:00",
 *     "expires_at": null
 *   }
 * }
 */
