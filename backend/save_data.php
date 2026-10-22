<?php

/**
 * Hàm lưu dữ liệu crawl vào file data/crawl_data.json
 *
 * @param string $url URL website đã crawl
 * @param string $html Nội dung HTML tải về
 * @param array $extra Thông tin bổ sung (title, total_links, total_images, links, images)
 * @return array Kết quả thực hiện
 */
function saveCrawlData($url, $html, $extra = [])
{
    // 1. Kiểm tra URL
    if (!filter_var($url, FILTER_VALIDATE_URL)) {
        return [
            'success' => false,
            'status_code' => 400,
            'message' => 'URL không hợp lệ.'
        ];
    }

    // 2. Chỉ chấp nhận HTTP và HTTPS
    $scheme = strtolower((string) parse_url($url, PHP_URL_SCHEME));

    if (!in_array($scheme, ['http', 'https'], true)) {
        return [
            'success' => false,
            'status_code' => 400,
            'message' => 'Chỉ hỗ trợ URL HTTP hoặc HTTPS.'
        ];
    }

    // 3. Kiểm tra dữ liệu HTML
    if (!is_string($html) || trim($html) === '') {
        return [
            'success' => false,
            'status_code' => 400,
            'message' => 'Không có dữ liệu HTML để lưu.'
        ];
    }

    // 4. Giới hạn kích thước HTML tối đa 2 MB
    if (strlen($html) > 2 * 1024 * 1024) {
        return [
            'success' => false,
            'status_code' => 413,
            'message' => 'Dữ liệu HTML vượt quá giới hạn 2 MB.'
        ];
    }

    // 5. Tạo thư mục lưu dữ liệu
    $dataDir = __DIR__ . '/../data';

    if (!is_dir($dataDir)) {
        if (!mkdir($dataDir, 0755, true) && !is_dir($dataDir)) {
            return [
                'success' => false,
                'status_code' => 500,
                'message' => 'Không thể tạo thư mục lưu dữ liệu.'
            ];
        }
    }

    // 6. Đường dẫn file JSON
    $filePath = $dataDir . '/crawl_data.json';

    // 7. Đọc dữ liệu cũ nếu file đã tồn tại
    $records = [];

    if (file_exists($filePath)) {
        $oldData = file_get_contents($filePath);

        if ($oldData === false) {
            return [
                'success' => false,
                'status_code' => 500,
                'message' => 'Không thể đọc dữ liệu đã lưu.'
            ];
        }

        if (trim($oldData) !== '') {
            $decodedData = json_decode($oldData, true);

            if (!is_array($decodedData)) {
                return [
                    'success' => false,
                    'status_code' => 500,
                    'message' => 'File dữ liệu cũ không đúng định dạng JSON.'
                ];
            }

            $records = $decodedData;
        }
    }

    // 8. Tạo bản ghi mới
    $newRecord = [
        'url' => $url,
        'saved_at' => date('Y-m-d H:i:s'),
        'title' => $extra['title'] ?? null,
        'total_links' => isset($extra['total_links']) ? (int) $extra['total_links'] : null,
        'total_images' => isset($extra['total_images']) ? (int) $extra['total_images'] : null,
        'total_headings' => isset($extra['total_headings']) ? (int) $extra['total_headings'] : (is_array($extra['headings'] ?? null) ? count($extra['headings']) : null),
        'total_articles' => isset($extra['total_articles']) ? (int) $extra['total_articles'] : (is_array($extra['articles'] ?? null) ? count($extra['articles']) : null),
        'total_files' => isset($extra['total_files']) ? (int) $extra['total_files'] : (is_array($extra['files'] ?? null) ? count($extra['files']) : null),
        'headings' => $extra['headings'] ?? null,
        'articles' => $extra['articles'] ?? null,
        'files' => $extra['files'] ?? null,
        'links' => $extra['links'] ?? null,
        'images' => $extra['images'] ?? null,
        'products' => $extra['products'] ?? null,
        'html' => $html
    ];

    // Loại bỏ các trường null để file JSON gọn gàng
    $newRecord = array_filter($newRecord, function ($value) {
        return $value !== null;
    });

    // Thêm bản ghi vào danh sách
    $records[] = $newRecord;

    // 9. Chuyển dữ liệu sang JSON
    $jsonData = json_encode(
        $records,
        JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT
    );

    if ($jsonData === false) {
        return [
            'success' => false,
            'status_code' => 500,
            'message' => 'Không thể chuyển dữ liệu sang JSON.'
        ];
    }

    // 10. Ghi file và khóa khi ghi để hạn chế ghi đè đồng thời
    $result = file_put_contents($filePath, $jsonData, LOCK_EX);

    if ($result === false) {
        return [
            'success' => false,
            'status_code' => 500,
            'message' => 'Không thể ghi dữ liệu vào file.'
        ];
    }

    return [
        'success' => true,
        'status_code' => 200,
        'message' => 'Đã lưu dữ liệu crawl thành công.',
        'url' => $url,
        'saved_at' => $newRecord['saved_at'],
        'file' => 'data/crawl_data.json'
    ];
}

/**
 * Xóa một bản ghi theo index (0-based) hoặc id (1-based)
 */
function deleteCrawlRecord($recordId)
{
    $filePath = __DIR__ . '/../data/crawl_data.json';
    if (!file_exists($filePath)) {
        return ['success' => false, 'status_code' => 404, 'message' => 'File dữ liệu không tồn tại.'];
    }

    $raw = file_get_contents($filePath);
    $records = json_decode($raw, true);
    if (!is_array($records)) {
        return ['success' => false, 'status_code' => 500, 'message' => 'Dữ liệu không hợp lệ.'];
    }

    // recordId có thể là index (0, 1...) hoặc id (1, 2...)
    $idx = (int) $recordId;
    if ($idx > 0 && !isset($records[$idx]) && isset($records[$idx - 1])) {
        $idx = $idx - 1; // Quy đổi từ 1-based id sang 0-based
    }

    if (!isset($records[$idx])) {
        return ['success' => false, 'status_code' => 404, 'message' => 'Không tìm thấy bản ghi cần xóa.'];
    }

    array_splice($records, $idx, 1);
    file_put_contents($filePath, json_encode($records, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT), LOCK_EX);

    return ['success' => true, 'status_code' => 200, 'message' => 'Đã xóa bản ghi thành công.'];
}

/**
 * Xóa toàn bộ lịch sử thu thập
 */
function clearAllCrawlRecords()
{
    $filePath = __DIR__ . '/../data/crawl_data.json';
    if (file_exists($filePath)) {
        file_put_contents($filePath, json_encode([], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT), LOCK_EX);
    }
    return ['success' => true, 'status_code' => 200, 'message' => 'Đã xóa toàn bộ lịch sử dữ liệu crawl.'];
}

/**
 * Lưu dữ liệu cụ thể (chỉ tiêu đề, chỉ link, chỉ ảnh, chỉ bài viết, chỉ file) ra file riêng
 */
function saveSpecificData($type, $items, $sourceUrl)
{
    $dataDir = __DIR__ . '/../data';
    if (!is_dir($dataDir)) {
        mkdir($dataDir, 0755, true);
    }

    $allowedTypes = ['headings', 'links', 'images', 'articles', 'files', 'products'];
    if (!in_array($type, $allowedTypes, true)) {
        return ['success' => false, 'status_code' => 400, 'message' => 'Loại dữ liệu cụ thể không hợp lệ.'];
    }

    $fileName = 'data/' . $type . '_export.json';
    $saveData = [
        'source_url' => $sourceUrl,
        'exported_at' => date('Y-m-d H:i:s'),
        'type' => $type,
        'total' => is_array($items) ? count($items) : 0,
        'items' => $items
    ];

    $result = file_put_contents($dataDir . '/' . $type . '_export.json', json_encode($saveData, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT), LOCK_EX);
    if ($result === false) {
        return ['success' => false, 'status_code' => 500, 'message' => 'Không thể lưu file dữ liệu cụ thể.'];
    }

    return [
        'success' => true,
        'status_code' => 200,
        'message' => 'Đã lưu danh sách ' . $type . ' thành công vào ' . $fileName,
        'file' => $fileName
    ];
}

// Xử lý trực tiếp khi được gọi qua HTTP API
if (realpath($_SERVER['SCRIPT_FILENAME'] ?? '') === realpath(__FILE__)) {
    header('Content-Type: application/json; charset=utf-8');

    // Chỉ nhận yêu cầu POST
    if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
        http_response_code(405);
        echo json_encode([
            'success' => false,
            'message' => 'Vui lòng gửi yêu cầu POST.'
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }

    // Nhận dữ liệu JSON từ frontend
    $input = json_decode(file_get_contents('php://input'), true);

    // Nếu frontend gửi form thông thường thì lấy dữ liệu từ $_POST
    if (!is_array($input)) {
        $input = $_POST;
    }

    $action = $input['action'] ?? 'save';

    if ($action === 'delete') {
        $recordId = $input['record_id'] ?? null;
        $result = deleteCrawlRecord($recordId);
    } elseif ($action === 'clear') {
        $result = clearAllCrawlRecords();
    } elseif ($action === 'save_specific') {
        $type = $input['type'] ?? '';
        $items = $input['items'] ?? [];
        $sourceUrl = $input['source_url'] ?? '';
        $result = saveSpecificData($type, $items, $sourceUrl);
    } else {
        $url = trim($input['url'] ?? '');
        $html = $input['html'] ?? '';
        $result = saveCrawlData($url, $html, $input);
    }

    http_response_code($result['status_code'] ?? 200);
    unset($result['status_code']);

    echo json_encode($result, JSON_UNESCAPED_UNICODE);
    exit;
}
