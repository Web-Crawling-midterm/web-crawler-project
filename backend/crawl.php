
<?php

if (!function_exists('curl_init')) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'PHP chưa bật extension cURL. Hãy bật extension=curl trong php.ini rồi khởi động lại máy chủ.'], JSON_UNESCAPED_UNICODE);
    exit;
}

// Trả kết quả dưới dạng JSON
header('Content-Type: application/json; charset=utf-8');

// Hàm trả lỗi JSON và dừng chương trình
function sendError($statusCode, $message, $extra = [])
{
    http_response_code($statusCode);

    echo json_encode(
        array_merge([
            'success' => false,
            'message' => $message
        ], $extra),
        JSON_UNESCAPED_UNICODE
    );

    exit;
}

// Chỉ nhận yêu cầu POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    sendError(405, 'Vui lòng gửi yêu cầu POST.');
}

// Nhận URL từ frontend
$url = trim($_POST['url'] ?? '');

// Kiểm tra URL
if (!filter_var($url, FILTER_VALIDATE_URL)) {
    sendError(400, 'URL không hợp lệ.');
}

// Chỉ cho phép HTTP và HTTPS
$scheme = strtolower((string) parse_url($url, PHP_URL_SCHEME));

if (!in_array($scheme, ['http', 'https'], true)) {
    sendError(400, 'Chỉ hỗ trợ URL HTTP hoặc HTTPS.');
}

// Kiểm tra hostname
$host = parse_url($url, PHP_URL_HOST);

if (!$host) {
    sendError(400, 'Không xác định được tên miền.');
}

// Hàm kiểm tra IP có phải IP công khai hay không
function isPublicIp($ip)
{
    return filter_var(
        $ip,
        FILTER_VALIDATE_IP,
        FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE
    ) !== false;
}

// Kiểm tra IP của hostname trước khi gửi yêu cầu HTTP
$ips = gethostbynamel($host);

// Nếu chưa tìm được IPv4, thử kiểm tra xem hostname có phải
// địa chỉ IP hợp lệ hay không
if (!$ips && filter_var($host, FILTER_VALIDATE_IP)) {
    $ips = [$host];
}

if (!$ips) {
    sendError(400, 'Không thể xác định địa chỉ website.');
}

foreach ($ips as $ip) {
    if (!isPublicIp($ip)) {
        sendError(
            400,
            'Website không thuộc phạm vi được phép truy cập.'
        );
    }
}

// Khai báo User-Agent
$userAgent = 'WebCrawlingStudentProject/1.0';

// Kiểm tra robots.txt sau khi kiểm tra IP
require_once __DIR__ . '/check_robot.php';

$robotsResult = checkRobots($url, $userAgent);

if (!$robotsResult['allowed']) {
    sendError(403, 'Không thể crawl URL này.', [
        'robots_message' => $robotsResult['message'],
        'robots_url' => $robotsResult['robots_url'] ?? null
    ]);
}

// Khởi tạo cURL
$ch = curl_init();

if ($ch === false) {
    sendError(500, 'Không thể khởi tạo cURL.');
}

// Đường dẫn file chứng chỉ SSL
$caCertPath = __DIR__ . '/../certs/cacert.pem';

if (!file_exists($caCertPath)) {
    sendError(500, 'Không tìm thấy file chứng chỉ SSL cacert.pem.');
}

// Không tự động theo chuyển hướng.
// Nếu website chuyển hướng, cần kiểm tra URL đích riêng.
curl_setopt_array($ch, [
    CURLOPT_URL => $url,
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_FOLLOWLOCATION => false,
    CURLOPT_CONNECTTIMEOUT => 10,
    CURLOPT_TIMEOUT => 20,
    CURLOPT_MAXFILESIZE => 2 * 1024 * 1024,
    CURLOPT_CAINFO => realpath($caCertPath),
    CURLOPT_USERAGENT => $userAgent,
    CURLOPT_SSL_VERIFYPEER => true,
    CURLOPT_SSL_VERIFYHOST => 2,
    CURLOPT_PROTOCOLS => CURLPROTO_HTTP | CURLPROTO_HTTPS
]);

// Tải nội dung website
$html = curl_exec($ch);

$error = curl_error($ch);
$errorCode = curl_errno($ch);
$statusCode = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
$contentType = curl_getinfo($ch, CURLINFO_CONTENT_TYPE);

// Không cần gọi curl_close() trong PHP 8.5

// Kiểm tra lỗi kết nối
if ($html === false || $errorCode !== 0) {
    sendError(502, 'Không thể tải website.', [
        'error' => $error,
        'error_code' => $errorCode
    ]);
}

// Kiểm tra chuyển hướng
if ($statusCode >= 300 && $statusCode < 400) {
    sendError(
        502,
        'Website yêu cầu chuyển hướng. Hãy kiểm tra URL đích trước khi truy cập.',
        [
            'status_code' => $statusCode
        ]
    );
}

// Kiểm tra mã HTTP
if ($statusCode < 200 || $statusCode >= 300) {
    sendError(502, 'Website phản hồi lỗi HTTP.', [
        'status_code' => $statusCode
    ]);
}

// Kiểm tra nội dung HTML
if (
    $contentType !== null &&
    stripos($contentType, 'text/html') === false
) {
    sendError(415, 'URL này không trả về nội dung HTML.', [
        'content_type' => $contentType
    ]);
}

// Trả kết quả cho frontend
echo json_encode([
    'success' => true,
    'message' => 'Tải nội dung website thành công.',
    'url' => $url,
    'status_code' => $statusCode,
    'content_type' => $contentType,
    'html' => $html
], JSON_UNESCAPED_UNICODE);
