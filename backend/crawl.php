
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
// Cấu hình cURL ban đầu
$curlOptions = [
    CURLOPT_URL => $url,
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_FOLLOWLOCATION => false,
    CURLOPT_CONNECTTIMEOUT => 10,
    CURLOPT_TIMEOUT => 30,
    CURLOPT_MAXFILESIZE => 15 * 1024 * 1024,
    CURLOPT_USERAGENT => $userAgent,
    CURLOPT_SSL_VERIFYPEER => true,
    CURLOPT_SSL_VERIFYHOST => 2,
    CURLOPT_PROTOCOLS => CURLPROTO_HTTP | CURLPROTO_HTTPS,
    CURLOPT_HEADER => true
];

if (file_exists($caCertPath)) {
    $curlOptions[CURLOPT_CAINFO] = realpath($caCertPath);
}

curl_setopt_array($ch, $curlOptions);

// Vòng lặp tải trang có xử lý chuyển hướng an toàn (tối đa 3 lần chuyển hướng)
$currentUrl = $url;
$redirectCount = 0;
$maxRedirects = 3;
$response = false;
$statusCode = 0;
$contentType = '';
$headerSize = 0;

while ($redirectCount <= $maxRedirects) {
    curl_setopt($ch, CURLOPT_URL, $currentUrl);
    $response = curl_exec($ch);
    $errorCode = curl_errno($ch);
    $error = curl_error($ch);

    // Thử lại nếu gặp lỗi chứng chỉ SSL trên môi trường Windows dev
    if ($errorCode === 60 || $errorCode === 77) {
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
        $response = curl_exec($ch);
        $errorCode = curl_errno($ch);
        $error = curl_error($ch);
    }

    if ($response === false || $errorCode !== 0) {
        sendError(502, 'Không thể tải website: ' . $error, [
            'error' => $error,
            'error_code' => $errorCode,
            'target_url' => $currentUrl
        ]);
    }

    $statusCode = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $contentType = (string) curl_getinfo($ch, CURLINFO_CONTENT_TYPE);
    $headerSize = (int) curl_getinfo($ch, CURLINFO_HEADER_SIZE);

    // Kiểm tra chuyển hướng 301, 302, 303, 307, 308
    if ($statusCode >= 300 && $statusCode < 400) {
        $redirectUrl = (string) curl_getinfo($ch, CURLINFO_REDIRECT_URL);
        if (!$redirectUrl) {
            // Đọc từ header Location
            $headerText = substr($response, 0, $headerSize);
            if (preg_match('/^Location:\s*(.+)$/im', $headerText, $matches)) {
                $redirectUrl = trim($matches[1]);
            }
        }

        if (!$redirectUrl) {
            sendError(502, 'Website yêu cầu chuyển hướng nhưng không cung cấp URL đích.', [
                'status_code' => $statusCode
            ]);
        }

        // Chuẩn hóa redirect URL nếu là tương đối
        if (!preg_match('~^https?://~i', $redirectUrl)) {
            $baseParts = parse_url($currentUrl);
            if (strpos($redirectUrl, '/') === 0) {
                $redirectUrl = ($baseParts['scheme'] ?? 'http') . '://' . ($baseParts['host'] ?? '') . $redirectUrl;
            } else {
                $baseDir = preg_replace('~/[^/]*$~', '/', $baseParts['path'] ?? '/');
                $redirectUrl = ($baseParts['scheme'] ?? 'http') . '://' . ($baseParts['host'] ?? '') . $baseDir . $redirectUrl;
            }
        }

        // Kiểm tra an toàn cho URL đích chuyển hướng (chống SSRF)
        $newHost = parse_url($redirectUrl, PHP_URL_HOST);
        $newScheme = strtolower((string) parse_url($redirectUrl, PHP_URL_SCHEME));

        if (!in_array($newScheme, ['http', 'https'], true) || !$newHost) {
            sendError(400, 'URL chuyển hướng không hợp lệ.');
        }

        $newIps = gethostbynamel($newHost);
        if (!$newIps && filter_var($newHost, FILTER_VALIDATE_IP)) {
            $newIps = [$newHost];
        }

        if (!$newIps) {
            sendError(400, 'Không thể xác định địa chỉ máy chủ chuyển hướng.');
        }

        foreach ($newIps as $newIp) {
            if (!isPublicIp($newIp)) {
                sendError(400, 'URL chuyển hướng trỏ tới địa chỉ nội bộ không được phép.');
            }
        }

        $currentUrl = $redirectUrl;
        $redirectCount++;
        continue;
    }

    break;
}

// Tách Header và Body
$body = substr($response, $headerSize);

// Kiểm tra mã HTTP
if ($statusCode < 200 || $statusCode >= 300) {
    sendError(502, 'Website phản hồi lỗi HTTP ' . $statusCode . '.', [
        'status_code' => $statusCode,
        'final_url' => $currentUrl
    ]);
}

// Xác định loại nội dung: HTML, văn bản / tệp tài liệu hay tệp nhị phân
$isHtml = (stripos($contentType, 'text/html') !== false || stripos($contentType, 'application/xhtml+xml') !== false);
$isTextFile = (
    stripos($contentType, 'text/') !== false ||
    stripos($contentType, 'application/json') !== false ||
    stripos($contentType, 'application/xml') !== false ||
    stripos($contentType, 'application/javascript') !== false
);

// Trả kết quả cho frontend
echo json_encode([
    'success' => true,
    'message' => 'Tải nội dung website thành công.',
    'url' => $url,
    'final_url' => $currentUrl,
    'status_code' => $statusCode,
    'content_type' => $contentType,
    'is_html' => $isHtml,
    'is_file' => !$isHtml,
    'html' => $isHtml ? $body : '',
    'raw_content' => $isTextFile ? $body : '',
    'file_size' => strlen($body)
], JSON_UNESCAPED_UNICODE);

