
<?php

header('Content-Type: application/json; charset=utf-8');

// ==================================================
// 1. KIỂM TRA EXTENSION DOM
// ==================================================

if (!class_exists('DOMDocument')) {
    http_response_code(500);

    echo json_encode([
        'success' => false,
        'message' => 'PHP chưa bật extension DOM. Hãy bật extension=dom trong php.ini.'
    ], JSON_UNESCAPED_UNICODE);

    exit;
}

// ==================================================
// 2. KIỂM TRA REQUEST POST
// ==================================================

if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
    http_response_code(405);

    echo json_encode([
        'success' => false,
        'message' => 'Vui lòng gửi yêu cầu POST.'
    ], JSON_UNESCAPED_UNICODE);

    exit;
}

// ==================================================
// 3. LẤY DỮ LIỆU ĐẦU VÀO
// ==================================================

$html = $_POST['html'] ?? '';
$baseUrl = trim($_POST['url'] ?? '');

if (trim($html) === '') {
    http_response_code(400);

    echo json_encode([
        'success' => false,
        'message' => 'Chưa có nội dung HTML để phân tích.'
    ], JSON_UNESCAPED_UNICODE);

    exit;
}

// ==================================================
// 4. CHUẨN HÓA URL
// ==================================================

function resolveUrl($url, $baseUrl)
{
    $url = trim($url);

    if ($url === '') {
        return '';
    }

    // Bỏ URL không phải HTTP/HTTPS thông thường
    if (preg_match('/^(javascript|data|blob|mailto|tel):/i', $url)) {
        return '';
    }

    // URL tuyệt đối
    if (preg_match('/^https?:\/\//i', $url)) {
        return $url;
    }

    $base = parse_url($baseUrl);

    if (
        !$base ||
        empty($base['scheme']) ||
        empty($base['host']) ||
        !in_array(strtolower($base['scheme']), ['http', 'https'], true)
    ) {
        return '';
    }

    $origin = $base['scheme'] . '://' . $base['host'];

    if (isset($base['port'])) {
        $origin .= ':' . $base['port'];
    }

    // URL bắt đầu bằng // 
    if (strpos($url, '//') === 0) {
        return $base['scheme'] . ':' . $url;
    }

    $urlParts = parse_url($url);

    if ($urlParts === false) {
        return '';
    }

    $fragment = isset($urlParts['fragment'])
        ? '#' . $urlParts['fragment']
        : '';

    $query = isset($urlParts['query'])
        ? '?' . $urlParts['query']
        : '';

    $path = $urlParts['path'] ?? '';

    // URL chỉ chứa query hoặc fragment
    if ($path === '') {
        $basePath = $base['path'] ?? '/';

        return $origin . $basePath . $query . $fragment;
    }

    // Đường dẫn bắt đầu bằng /
    if (strpos($path, '/') === 0) {
        $fullPath = $path;
    } else {
        $basePath = $base['path'] ?? '/';
        $lastSlash = strrpos($basePath, '/');

        $directory = ($lastSlash !== false)
            ? substr($basePath, 0, $lastSlash + 1)
            : '/';

        $fullPath = $directory . $path;
    }

    // Chuẩn hóa đường dẫn . và ..
    $segments = explode('/', $fullPath);
    $normalized = [];

    foreach ($segments as $segment) {
        if ($segment === '' || $segment === '.') {
            continue;
        }

        if ($segment === '..') {
            array_pop($normalized);
        } else {
            $normalized[] = $segment;
        }
    }

    $fullPath = '/' . implode('/', $normalized);

    if (
        substr($path, -1) === '/' &&
        substr($fullPath, -1) !== '/'
    ) {
        $fullPath .= '/';
    }

    return $origin . $fullPath . $query . $fragment;
}

// ==================================================
// 5. LẤY URL ẢNH THẬT
// ==================================================

function getImageUrl(DOMElement $node, $baseUrl)
{
    // Ưu tiên thuộc tính có thể chứa ảnh lazy loading
    $attributes = [
        'data-src',
        'data-original',
        'data-lazy-src',
        'data-actualsrc',
        'data-url',
        'src'
    ];

    foreach ($attributes as $attribute) {
        $value = trim($node->getAttribute($attribute));

        if ($value === '') {
            continue;
        }

        // Bỏ ảnh placeholder dạng data:image
        if (stripos($value, 'data:image/') === 0) {
            continue;
        }

        $resolved = resolveUrl($value, $baseUrl);

        if ($resolved !== '') {
            return $resolved;
        }
    }

    // Nếu chưa tìm được ảnh, thử srcset
    $srcset = trim($node->getAttribute('srcset'));

    if ($srcset !== '') {
        $candidates = explode(',', $srcset);

        foreach ($candidates as $candidate) {
            $parts = preg_split('/\s+/', trim($candidate));

            if (empty($parts[0])) {
                continue;
            }

            $resolved = resolveUrl($parts[0], $baseUrl);

            if ($resolved !== '') {
                return $resolved;
            }
        }
    }

    return '';
}

// ==================================================
// 6. PHÂN TÍCH HTML
// ==================================================

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

$xpath = new DOMXPath($dom);

// ==================================================
// 7. LẤY TIÊU ĐỀ TRANG
// ==================================================

$titleNode = $xpath->query('//title')->item(0);

$title = $titleNode
    ? trim(preg_replace('/\s+/u', ' ', $titleNode->textContent))
    : 'Không có tiêu đề';

// ==================================================
// 8. LẤY LINKS VÀ LOẠI BỎ URL TRÙNG
// ==================================================


 // 8. LẤY LINKS VÀ LOẠI BỎ URL TRÙNG
$links = [];
$seenUrls = [];

foreach ($xpath->query('//a[@href]') as $node) {
    if (!$node instanceof DOMElement) {
        continue;
    }

    $href = trim($node->getAttribute('href'));

    if ($href === '') {
        continue;
    }

    // Bỏ link không có nội dung văn bản
    $text = trim(
        preg_replace('/\s+/u', ' ', $node->textContent)
    );

    if ($text === '') {
        continue;
    }

    $resolvedUrl = resolveUrl($href, $baseUrl);

    if ($resolvedUrl === '') {
        continue;
    }

    // Bỏ phần #fragment để nhận diện cùng một trang
    $parts = parse_url($resolvedUrl);

    if ($parts === false || empty($parts['host'])) {
        continue;
    }

    $normalizedUrl = strtolower($parts['scheme'] ?? 'https')
        . '://'
        . strtolower($parts['host'])
        . ($parts['port'] ?? '')
        . ($parts['path'] ?? '/');

    // Loại bỏ URL trùng
    if (isset($seenUrls[$normalizedUrl])) {
        continue;
    }

    $seenUrls[$normalizedUrl] = true;

    $links[] = [
        'text' => $text,
        'url' => $normalizedUrl
    ];
}


// ==================================================
// 9. LẤY IMAGES VÀ LOẠI BỎ ẢNH TRÙNG
// ==================================================

$images = [];
$seenImages = [];

foreach ($xpath->query('//img') as $node) {
    if (!$node instanceof DOMElement) {
        continue;
    }

    $src = getImageUrl($node, $baseUrl);

    if ($src === '') {
        continue;
    }

    if (isset($seenImages[$src])) {
        continue;
    }

    $seenImages[$src] = true;

    $images[] = [
        'alt' => trim($node->getAttribute('alt')),
        'src' => $src
    ];
}

// ==================================================
// 10. LẤY DỮ LIỆU SẢN PHẨM
// Dành cho website có cấu trúc Books to Scrape
// ==================================================

$products = [];

$productCards = $xpath->query(
    "//article[contains(
        concat(' ', normalize-space(@class), ' '),
        ' product_pod '
    )]"
);

foreach ($productCards as $card) {
    $nameNode = $xpath->query('.//h3/a', $card)->item(0);

    $priceNode = $xpath->query(
        ".//*[contains(
            concat(' ', normalize-space(@class), ' '),
            ' price_color '
        )]",
        $card
    )->item(0);

    $availabilityNode = $xpath->query(
        ".//*[contains(
            concat(' ', normalize-space(@class), ' '),
            ' availability '
        )]",
        $card
    )->item(0);

    $imageNode = $xpath->query('.//img', $card)->item(0);

    $ratingNode = $xpath->query(
        ".//*[contains(
            concat(' ', normalize-space(@class), ' '),
            ' star-rating '
        )]",
        $card
    )->item(0);

    // LẤY ĐÁNH GIÁ
    $rating = null;

    if ($ratingNode) {
        $classes = $ratingNode->getAttribute('class');

        $ratingMap = [
            'One' => 1,
            'Two' => 2,
            'Three' => 3,
            'Four' => 4,
            'Five' => 5
        ];

        foreach ($ratingMap as $word => $number) {
            if (
                preg_match(
                    '/\b' . preg_quote($word, '/') . '\b/',
                    $classes
                )
            ) {
                $rating = $number;
                break;
            }
        }
    }

    // LẤY GIÁ
    $priceText = $priceNode
        ? trim($priceNode->textContent)
        : '';

    $price = null;

    if (preg_match('/([0-9]+(?:\.[0-9]{1,2})?)/', $priceText, $match)) {
        $price = (float) $match[1];
    }

    // LẤY URL SẢN PHẨM
    $productUrl = $nameNode
        ? resolveUrl($nameNode->getAttribute('href'), $baseUrl)
        : '';

    // LẤY ẢNH SẢN PHẨM
    $productImage = ($imageNode instanceof DOMElement)
        ? getImageUrl($imageNode, $baseUrl)
        : '';

    // LẤY TÊN SẢN PHẨM
    $productTitle = '(không có tiêu đề)';

    if ($nameNode) {
        $productTitle = trim(
            $nameNode->getAttribute('title')
            ?: $nameNode->textContent
        );
    }

    $products[] = [
        'title' => $productTitle,
        'url' => $productUrl,
        'image_url' => $productImage,
        'price' => $price,
        'currency' => 'GBP',
        'rating' => $rating,

        'availability' => $availabilityNode
            ? trim(
                preg_replace(
                    '/\s+/u',
                    ' ',
                    $availabilityNode->textContent
                )
            )
            : ''
    ];
}

// ==================================================
// 11. TRẢ KẾT QUẢ JSON
// ==================================================

echo json_encode([
    'success' => true,
    'title' => $title,
    'total_links' => count($links),
    'total_images' => count($images),
    'links' => $links,
    'images' => $images,
    'products' => $products
], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);

