<?php

/**
 * export_csv.php – Xuất dữ liệu crawl ra file CSV để tải về.
 *
 * Tham số POST:
 *   - type: 'products' | 'articles' | 'links' | 'images' | 'headings' | 'files'
 *   - data: JSON string chứa mảng các phần tử cần xuất
 *   - filename: (tuỳ chọn) tên file CSV muốn đặt
 *
 * Phản hồi: file CSV tải về trực tiếp (Content-Disposition: attachment)
 */

// Chỉ nhận POST
if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
    http_response_code(405);
    exit('Chỉ hỗ trợ POST.');
}

$type     = trim($_POST['type'] ?? 'data');
$rawData  = $_POST['data'] ?? '[]';
$items    = json_decode($rawData, true);
$filename = trim($_POST['filename'] ?? '');

if (!is_array($items)) {
    http_response_code(400);
    exit('Dữ liệu không hợp lệ.');
}

// Danh sách type được phép
$allowedTypes = ['products', 'articles', 'links', 'images', 'headings', 'files'];
if (!in_array($type, $allowedTypes, true)) {
    http_response_code(400);
    exit('Loại dữ liệu không được hỗ trợ.');
}

// Tên file CSV
if ($filename === '' || !preg_match('/^[\w\-]+$/', $filename)) {
    $filename = 'crawl_' . $type . '_' . date('Ymd_His');
}
$filename .= '.csv';

// Map cột theo loại dữ liệu
$headers = match ($type) {
    'products' => ['STT', 'Tiêu đề sản phẩm', 'Giá (GBP)', 'Đánh giá (sao)', 'Tình trạng', 'URL sản phẩm', 'URL hình ảnh'],
    'articles' => ['STT', 'Tiêu đề bài viết', 'Tác giả', 'Ngày đăng', 'Tóm tắt', 'URL bài viết', 'URL hình ảnh bìa'],
    'links'    => ['STT', 'Văn bản liên kết', 'URL', 'Phạm vi (Nội bộ/Ngoài)', 'Loại liên kết'],
    'images'   => ['STT', 'Mô tả (alt)', 'URL hình ảnh', 'Định dạng', 'Rộng (px)', 'Cao (px)', 'Phạm vi'],
    'headings' => ['STT', 'Cấp độ (H1-H6)', 'Nội dung tiêu đề', 'Thuộc tính ID'],
    'files'    => ['STT', 'Tên tệp', 'Định dạng', 'Văn bản neo (Anchor)', 'URL tải về', 'Phạm vi'],
    default    => ['STT', 'Dữ liệu'],
};

// Hàm lấy hàng dữ liệu theo loại
function buildRow(string $type, int $index, array $item): array
{
    $stt = $index + 1;

    return match ($type) {
        'products' => [
            $stt,
            $item['title']        ?? '',
            $item['price']        ?? '',
            $item['rating']       ?? '',
            $item['availability'] ?? '',
            $item['url']          ?? '',
            $item['image_url']    ?? '',
        ],
        'articles' => [
            $stt,
            $item['title']     ?? '',
            $item['author']    ?? '',
            $item['date']      ?? '',
            $item['excerpt']   ?? '',
            $item['url']       ?? '',
            $item['image_url'] ?? '',
        ],
        'links' => [
            $stt,
            $item['text']        ?? '',
            $item['url']         ?? '',
            ($item['is_internal'] ?? false) ? 'Nội bộ' : 'Ngoài',
            $item['type']        ?? '',
        ],
        'images' => [
            $stt,
            $item['alt']         ?? '',
            $item['src']         ?? '',
            $item['extension']   ?? '',
            $item['width']       ?? '',
            $item['height']      ?? '',
            ($item['is_internal'] ?? false) ? 'Nội bộ' : 'Ngoài',
        ],
        'headings' => [
            $stt,
            strtoupper($item['tag'] ?? 'h?'),
            $item['text'] ?? '',
            $item['id']   ?? '',
        ],
        'files' => [
            $stt,
            $item['name']        ?? '',
            $item['extension']   ?? '',
            $item['anchor_text'] ?? '',
            $item['url']         ?? '',
            ($item['is_internal'] ?? false) ? 'Nội bộ' : 'Ngoài',
        ],
        default => [$stt, json_encode($item, JSON_UNESCAPED_UNICODE)],
    };
}

// ==================================================
// TẠO VÀ GỬI FILE CSV
// ==================================================

// Bắt lỗi output buffer thừa
if (ob_get_level()) {
    ob_end_clean();
}

header('Content-Type: text/csv; charset=UTF-8');
header('Content-Disposition: attachment; filename="' . $filename . '"');
header('Cache-Control: no-cache, no-store, must-revalidate');
header('Pragma: no-cache');
header('Expires: 0');

// BOM UTF-8 để Excel mở đúng tiếng Việt
echo "\xEF\xBB\xBF";

$output = fopen('php://output', 'wb');

// Hàng tiêu đề
fputcsv($output, $headers);

// Các hàng dữ liệu
foreach ($items as $i => $item) {
    if (!is_array($item)) {
        continue;
    }
    fputcsv($output, buildRow($type, $i, $item));
}

fclose($output);
exit;

