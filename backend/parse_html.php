
<?php

header('Content-Type: application/json; charset=utf-8');

// Chỉ cho phép yêu cầu POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);

    echo json_encode([
        'success' => false,
        'message' => 'Vui lòng gửi yêu cầu POST.'
    ], JSON_UNESCAPED_UNICODE);

    exit;
}

// Nhận HTML từ crawl.php thông qua index.php
$html = $_POST['html'] ?? '';

if (trim($html) === '') {
    http_response_code(400);

    echo json_encode([
        'success' => false,
        'message' => 'Chưa có nội dung HTML để phân tích.'
    ], JSON_UNESCAPED_UNICODE);

    exit;
}

// Cho phép phân tích HTML có ký tự tiếng Việt
libxml_use_internal_errors(true);

$dom = new DOMDocument('1.0', 'UTF-8');

$loaded = $dom->loadHTML(
    '<?xml encoding="UTF-8">' . $html
);

libxml_clear_errors();

if (!$loaded) {
    http_response_code(422);

    echo json_encode([
        'success' => false,
        'message' => 'Không thể phân tích HTML.'
    ], JSON_UNESCAPED_UNICODE);

    exit;
}

// Tạo XPath để tìm các thành phần HTML
$xpath = new DOMXPath($dom);

// 1. Lấy tiêu đề website
$titleNode = $xpath->query('//title')->item(0);

$title = $titleNode
    ? trim($titleNode->textContent)
    : 'Không có tiêu đề';

// 2. Lấy danh sách liên kết
$links = [];

$linkNodes = $xpath->query('//a[@href]');

foreach ($linkNodes as $node) {

    // Kiểm tra node có phải DOMElement không
    if (!$node instanceof DOMElement) {
        continue;
    }

    $href = trim($node->getAttribute('href'));

    if ($href === '') {
        continue;
    }

    $links[] = [
        'text' => trim($node->textContent),
        'url' => $href
    ];
}

// 3. Lấy danh sách hình ảnh
$images = [];

$imageNodes = $xpath->query('//img[@src]');

foreach ($imageNodes as $node) {

    // Kiểm tra node có phải DOMElement không
    if (!$node instanceof DOMElement) {
        continue;
    }

    $src = trim($node->getAttribute('src'));

    if ($src === '') {
        continue;
    }

    $images[] = [
        'alt' => $node->getAttribute('alt'),
        'src' => $src
    ];
}

// 4. Trả kết quả về dạng JSON
echo json_encode([
    'success' => true,
    'title' => $title,
    'total_links' => count($links),
    'total_images' => count($images),
    'links' => $links,
    'images' => $images
], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
