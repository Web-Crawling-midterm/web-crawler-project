
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
// Lấy tên miền nguồn để phân loại liên kết nội bộ / ngoài
$baseHost = (string) parse_url($baseUrl, PHP_URL_HOST);

// ==================================================
// 7. LẤY TIÊU ĐỀ TRANG & METADATA
// ==================================================

$titleNode = $xpath->query('//title')->item(0);

$title = $titleNode
    ? trim(preg_replace('/\s+/u', ' ', $titleNode->textContent))
    : 'Không có tiêu đề';

// Lấy thông tin Meta Tags (OpenGraph, Description, Author)
$metaDescription = '';
$ogTitle = '';
$ogDescription = '';
$ogImage = '';
$metaAuthor = '';

$descNode = $xpath->query('//meta[translate(@name, "DESCRIPTION", "description")="description"]/@content')->item(0);
if ($descNode) {
    $metaDescription = trim($descNode->nodeValue);
}

$ogTitleNode = $xpath->query('//meta[@property="og:title"]/@content')->item(0);
if ($ogTitleNode) {
    $ogTitle = trim($ogTitleNode->nodeValue);
}

$ogDescNode = $xpath->query('//meta[@property="og:description"]/@content')->item(0);
if ($ogDescNode) {
    $ogDescription = trim($ogDescNode->nodeValue);
}

$ogImgNode = $xpath->query('//meta[@property="og:image"]/@content')->item(0);
if ($ogImgNode) {
    $ogImage = resolveUrl($ogImgNode->nodeValue, $baseUrl);
}

$authorNode = $xpath->query('//meta[translate(@name, "AUTHOR", "author")="author"]/@content')->item(0);
if ($authorNode) {
    $metaAuthor = trim($authorNode->nodeValue);
}

// ==================================================
// 7.1. TRÍCH XUẤT CÁC TIÊU ĐỀ (HEADINGS H1 - H6)
// ==================================================

$headings = [];
$headingNodes = $xpath->query('//h1 | //h2 | //h3 | //h4 | //h5 | //h6');

foreach ($headingNodes as $hNode) {
    if (!$hNode instanceof DOMElement) {
        continue;
    }

    $hText = trim(preg_replace('/\s+/u', ' ', $hNode->textContent));
    if ($hText === '') {
        continue;
    }

    $tag = strtolower($hNode->tagName);
    $level = (int) substr($tag, 1);
    $hId = trim($hNode->getAttribute('id'));

    $headings[] = [
        'tag' => $tag,
        'level' => $level,
        'text' => $hText,
        'id' => $hId
    ];
}

// ==================================================
// 8. LẤY LINKS, PHÂN LOẠI & PHÁT HIỆN TỆP TẢI VỀ
// ==================================================

$links = [];
$files = [];
$seenUrls = [];
$seenFiles = [];

// Các đuôi tệp phổ biến cần phát hiện và trích xuất
$fileExtensions = [
    'pdf', 'docx', 'doc', 'xlsx', 'xls', 'pptx', 'ppt',
    'zip', 'rar', '7z', 'tar', 'gz', 'csv', 'txt', 'rtf',
    'epub', 'mobi', 'mp3', 'mp4', 'wav', 'apk', 'exe', 'json', 'xml'
];
$fileExtPattern = '/\.(' . implode('|', $fileExtensions) . ')(?:[\?#]|$)/i';

foreach ($xpath->query('//a[@href]') as $node) {
    if (!$node instanceof DOMElement) {
        continue;
    }

    $href = trim($node->getAttribute('href'));

    if ($href === '') {
        continue;
    }

    $text = trim(
        preg_replace('/\s+/u', ' ', $node->textContent)
    );

    $resolvedUrl = resolveUrl($href, $baseUrl);

    if ($resolvedUrl === '') {
        continue;
    }

    $parts = parse_url($resolvedUrl);

    if ($parts === false || empty($parts['host'])) {
        continue;
    }

    $linkHost = $parts['host'];
    $isInternal = (strcasecmp($linkHost, $baseHost) === 0);

    // Kiểm tra xem link có phải tệp tải về hay không
    $path = $parts['path'] ?? '';
    $isFile = false;
    $fileExt = '';

    if (preg_match($fileExtPattern, $path, $extMatch)) {
        $isFile = true;
        $fileExt = strtolower($extMatch[1]);
        $fileName = basename($path);

        if (!isset($seenFiles[$resolvedUrl])) {
            $seenFiles[$resolvedUrl] = true;
            $files[] = [
                'name' => $fileName ?: ($text ?: 'Tệp ' . strtoupper($fileExt)),
                'url' => $resolvedUrl,
                'extension' => $fileExt,
                'anchor_text' => $text,
                'is_internal' => $isInternal
            ];
        }
    }

    // Chuẩn hóa URL cho danh sách liên kết
    $normalizedUrl = strtolower($parts['scheme'] ?? 'https')
        . '://'
        . strtolower($linkHost)
        . ($parts['port'] ?? '')
        . ($path ?: '/');

    if (isset($parts['query'])) {
        $normalizedUrl .= '?' . $parts['query'];
    }

    if (isset($seenUrls[$normalizedUrl])) {
        continue;
    }

    $seenUrls[$normalizedUrl] = true;

    $links[] = [
        'text' => $text !== '' ? $text : '(liên kết không có chữ)',
        'url' => $resolvedUrl,
        'is_internal' => $isInternal,
        'type' => $isFile ? 'file' : ($isInternal ? 'internal' : 'external'),
        'extension' => $fileExt ?: null
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

    $imgParts = parse_url($src);
    $imgPath = $imgParts['path'] ?? '';
    $imgExt = '';

    if (preg_match('/\.([a-zA-Z0-9]+)(?:[\?#]|$)/', $imgPath, $m)) {
        $imgExt = strtolower($m[1]);
    }

    $imgHost = $imgParts['host'] ?? '';
    $imgIsInternal = (strcasecmp($imgHost, $baseHost) === 0);

    $images[] = [
        'alt' => trim($node->getAttribute('alt')),
        'src' => $src,
        'extension' => $imgExt ?: 'ảnh',
        'width' => $node->getAttribute('width') ?: null,
        'height' => $node->getAttribute('height') ?: null,
        'is_internal' => $imgIsInternal
    ];
}

// ==================================================
// 10. LẤY DỮ LIỆU SẢN PHẨM (BOOKS TO SCRAPE)
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
// 10.1. TRÍCH XUẤT BÀI VIẾT BLOG & TIN TỨC (BLOG EXTRACTION)
// ==================================================

$articles = [];
$seenArticleUrls = [];

// Tìm các thẻ đại diện cho bài viết blog phổ biến
$articleContainers = $xpath->query(
    "//article[not(contains(@class, 'product_pod'))] | " .
    "//*[contains(@class, 'post-item') or contains(@class, 'blog-post') or contains(@class, 'entry-card') or contains(@class, 'item-news') or contains(@class, 'article-card') or contains(@class, 'story-item')]"
);

// Nếu không tìm thấy bằng class chuyên biệt, quét các thẻ <article> thông thường hoặc cấu trúc h2/h3 có link bài
if ($articleContainers->length === 0) {
    $articleContainers = $xpath->query("//div[contains(@class, 'post') or contains(@class, 'article') or contains(@class, 'entry')]");
}

foreach ($articleContainers as $container) {
    if (!$container instanceof DOMElement) {
        continue;
    }

    // 1. Tiêu đề và link bài viết
    $aTitleNode = $xpath->query('.//h1//a | .//h2//a | .//h3//a | .//h4//a | .//*[contains(@class, "title")]//a | .//a[contains(@class, "title")]', $container)->item(0);
    $artTitle = '';
    $artUrl = '';

    if ($aTitleNode instanceof DOMElement) {
        $artTitle = trim(preg_replace('/\s+/u', ' ', $aTitleNode->textContent));
        $artUrl = resolveUrl($aTitleNode->getAttribute('href'), $baseUrl);
    } else {
        // Thử tìm thẻ h1/h2/h3 trực tiếp nếu không lồng thẻ <a>
        $hNode = $xpath->query('.//h1 | .//h2 | .//h3 | .//h4', $container)->item(0);
        if ($hNode) {
            $artTitle = trim(preg_replace('/\s+/u', ' ', $hNode->textContent));
        }
        $anyLink = $xpath->query('.//a[@href]', $container)->item(0);
        if ($anyLink instanceof DOMElement) {
            $artUrl = resolveUrl($anyLink->getAttribute('href'), $baseUrl);
        }
    }

    if ($artTitle === '' || $artUrl === '' || isset($seenArticleUrls[$artUrl])) {
        continue;
    }

    $seenArticleUrls[$artUrl] = true;

    // 2. Ngày đăng bài viết
    $artDate = '';
    $timeNode = $xpath->query('.//time', $container)->item(0);
    if ($timeNode instanceof DOMElement) {
        $artDate = trim($timeNode->getAttribute('datetime') ?: $timeNode->textContent);
    } else {
        $dateNode = $xpath->query('.//*[contains(@class, "date") or contains(@class, "time") or contains(@class, "published")]', $container)->item(0);
        if ($dateNode) {
            $artDate = trim(preg_replace('/\s+/u', ' ', $dateNode->textContent));
        }
    }

    // 3. Tác giả
    $artAuthor = '';
    $authorNode = $xpath->query('.//*[@rel="author"] | .//*[contains(@class, "author") or contains(@class, "byline")]', $container)->item(0);
    if ($authorNode) {
        $artAuthor = trim(preg_replace('/\s+/u', ' ', $authorNode->textContent));
    }

    // 4. Đoạn tóm tắt (Excerpt)
    $artExcerpt = '';
    $excerptNode = $xpath->query('.//*[contains(@class, "excerpt") or contains(@class, "summary") or contains(@class, "desc") or contains(@class, "snippet")] | .//p', $container)->item(0);
    if ($excerptNode) {
        $artExcerpt = trim(preg_replace('/\s+/u', ' ', $excerptNode->textContent));
        if (mb_strlen($artExcerpt, 'UTF-8') > 300) {
            $artExcerpt = mb_substr($artExcerpt, 0, 300, 'UTF-8') . '...';
        }
    }

    // 5. Ảnh bài viết
    $artImage = '';
    $artImgNode = $xpath->query('.//img', $container)->item(0);
    if ($artImgNode instanceof DOMElement) {
        $artImage = getImageUrl($artImgNode, $baseUrl);
    }

    $articles[] = [
        'title' => $artTitle,
        'url' => $artUrl,
        'author' => $artAuthor ?: ($metaAuthor ?: 'Không rõ tác giả'),
        'date' => $artDate ?: 'Không rõ ngày đăng',
        'excerpt' => $artExcerpt,
        'image_url' => $artImage ?: $ogImage
    ];
}

// Nếu trang hiện tại chính là một bài viết đơn lẻ (Single Post / Detail Page), trích xuất bài viết đó
if (empty($articles) && ($metaDescription || $ogTitle || count($headings) > 0)) {
    $mainH1 = $xpath->query('//h1')->item(0);
    if ($mainH1) {
        $singleTitle = trim(preg_replace('/\s+/u', ' ', $mainH1->textContent));
        $singleExcerpt = $metaDescription ?: ($ogDescription ?: '');
        $articles[] = [
            'title' => $singleTitle,
            'url' => $baseUrl,
            'author' => $metaAuthor ?: 'Tác giả bài viết',
            'date' => date('Y-m-d'),
            'excerpt' => $singleExcerpt,
            'image_url' => $ogImage
        ];
    }
}

// ==================================================
// 10.2. TÌM LIÊN KẾT PHÂN TRANG KẾ TIẾP (NEXT PAGE URL)
// ==================================================

$nextPageUrl = '';

$nextCandidates = $xpath->query(
    "//li[contains(concat(' ', normalize-space(@class), ' '), ' next ')]//a | " .
    "//a[@rel='next'] | " .
    "//a[contains(concat(' ', normalize-space(@class), ' '), ' next ')] | " .
    "//a[contains(@class, 'pagination-next')] | " .
    "//a[contains(translate(normalize-space(.), 'NEXT', 'next'), 'next')] | " .
    "//a[contains(translate(normalize-space(.), 'TIẾP', 'tiếp'), 'tiếp')]"
);

foreach ($nextCandidates as $cNode) {
    if ($cNode instanceof DOMElement) {
        $nextHref = trim($cNode->getAttribute('href'));
        if ($nextHref !== '') {
            $resolvedNext = resolveUrl($nextHref, $baseUrl);
            if ($resolvedNext !== '' && $resolvedNext !== $baseUrl) {
                $nextPageUrl = $resolvedNext;
                break;
            }
        }
    }
}

// ==================================================
// 10.3. XỬ LÝ TRUY VẤN XPATH TÙY CHỌN (CUSTOM FILTER)
// ==================================================

$customResults = [];
$customXpath = trim($_POST['custom_xpath'] ?? '');

if ($customXpath !== '') {
    try {
        $customNodes = $xpath->query($customXpath);
        if ($customNodes !== false) {
            foreach ($customNodes as $cNode) {
                $customResults[] = [
                    'text' => trim(preg_replace('/\s+/u', ' ', $cNode->textContent)),
                    'html' => $dom->saveHTML($cNode)
                ];
                if (count($customResults) >= 100) {
                    break;
                }
            }
        }
    } catch (\Throwable $e) {
        // Bỏ qua lỗi cú pháp XPath tùy biến
    }
}

// ==================================================
// 11. TRẢ KẾT QUẢ JSON ĐẦY ĐỦ
// ==================================================

echo json_encode([
    'success' => true,
    'title' => $title,
    'metadata' => [
        'description' => $metaDescription,
        'og_title' => $ogTitle,
        'og_description' => $ogDescription,
        'og_image' => $ogImage,
        'author' => $metaAuthor
    ],
    'total_links' => count($links),
    'total_images' => count($images),
    'total_headings' => count($headings),
    'total_articles' => count($articles),
    'total_files' => count($files),
    'total_products' => count($products),
    'next_page_url' => $nextPageUrl,
    'headings' => $headings,
    'articles' => $articles,
    'files' => $files,
    'links' => $links,
    'images' => $images,
    'products' => $products,
    'custom_results' => $customResults
], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);


