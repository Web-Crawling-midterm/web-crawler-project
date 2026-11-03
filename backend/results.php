<?php

/**
 * results.php – Trả về lịch sử crawl và danh sách items đã lưu.
 * BUG FIX: Đưa hàm resolveSavedUrl() lên đầu file để tránh lỗi
 *          "Call to undefined function" khi PHP parse file tuần tự.
 */

header('Content-Type: application/json; charset=utf-8');

// Yêu cầu đăng nhập
require_once __DIR__ . '/auth.php';
$currentUser = requireLogin();

// ==================================================
// HÀM TIỆN ÍCH: Chuẩn hóa URL tương đối → tuyệt đối
// Phải khai báo TRƯỚC khi gọi.
// ==================================================
function resolveSavedUrl(string $value, string $base): string
{
    if ($value === '') {
        return '';
    }

    // Đã là URL tuyệt đối
    if (preg_match('~^https?://~i', $value)) {
        return $value;
    }

    if ($base === '') {
        return $value;
    }

    $p = parse_url($base);

    if (!$p || empty($p['scheme']) || empty($p['host'])) {
        return $value;
    }

    $origin = $p['scheme'] . '://' . $p['host'] . (isset($p['port']) ? ':' . $p['port'] : '');

    if (strpos($value, '//') === 0) {
        return $p['scheme'] . ':' . $value;
    }

    if (strpos($value, '/') === 0) {
        return $origin . $value;
    }

    $basePath = $p['path'] ?? '/';
    $dir      = preg_replace('~/[^/]*$~', '/', $basePath);
    $full     = $origin . $dir . $value;

    // Chuẩn hóa . và ..
    $parts = [];
    foreach (explode('/', $full) as $seg) {
        if ($seg === '.' || $seg === '') {
            if (!$parts) {
                $parts[] = '';
            }
            continue;
        }
        if ($seg === '..') {
            array_pop($parts);
        } else {
            $parts[] = $seg;
        }
    }

    return implode('/', $parts);
}

// ==================================================
// ĐỌC FILE DỮ LIỆU
// ==================================================
$file = __DIR__ . '/../data/crawl_data.json';

if (!is_file($file)) {
    echo json_encode(['success' => true, 'total_records' => 0, 'records' => [], 'items' => []], JSON_UNESCAPED_UNICODE);
    exit;
}

$raw  = file_get_contents($file);
$data = json_decode($raw, true);

if (!is_array($data)) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Không đọc được dữ liệu đã lưu.'], JSON_UNESCAPED_UNICODE);
    exit;
}

// Phân quyền: user thường chỉ thấy dữ liệu do mình thu thập.
// Bản ghi cũ chưa có 'owner' được coi là của admin. Giữ nguyên khóa (index)
// để thao tác xóa vẫn trỏ đúng bản ghi trong file gốc.
if ($currentUser['role'] !== 'admin') {
    $data = array_filter($data, function ($rec) use ($currentUser) {
        return ($rec['owner'] ?? 'admin') === $currentUser['username'];
    });
}

// ==================================================
// TỔNG HỢP ITEMS TỪ TẤT CẢ BẢN GHI
// ==================================================
$items = [];

foreach ($data as $record) {
    $base = $record['url'] ?? '';

    if (!empty($record['products']) && is_array($record['products'])) {
        // Sản phẩm (Books to Scrape...)
        foreach ($record['products'] as $item) {
            $item['url']       = resolveSavedUrl($item['url'] ?? '', $base);
            $item['image_url'] = resolveSavedUrl($item['image_url'] ?? '', $base);
            if (!empty($item['url'])) {
                $items[] = $item;
            }
        }
    } elseif (!empty($record['articles']) && is_array($record['articles'])) {
        // Bài viết blog / tin tức
        foreach ($record['articles'] as $art) {
            $art['url']       = resolveSavedUrl($art['url'] ?? '', $base);
            $art['image_url'] = resolveSavedUrl($art['image_url'] ?? '', $base);
            if (!empty($art['url'])) {
                $items[] = [
                    'title'        => $art['title'] ?? '(không có tiêu đề)',
                    'url'          => $art['url'],
                    'image_url'    => $art['image_url'] ?? '',
                    'price'        => null,
                    'currency'     => '',
                    'rating'       => null,
                    'availability' => $art['author'] ?? '',
                ];
            }
        }
    } elseif (!empty($record['links']) && is_array($record['links'])) {
        // Danh sách liên kết
        foreach ($record['links'] as $link) {
            $href = resolveSavedUrl($link['url'] ?? '', $base);
            if ($href) {
                $items[] = [
                    'title'        => $link['text'] ?? '(không có tiêu đề)',
                    'url'          => $href,
                    'image_url'    => '',
                    'price'        => null,
                    'currency'     => '',
                    'rating'       => null,
                    'availability' => '',
                ];
            }
        }
    }
}

// Loại bỏ trùng lặp theo URL
$unique = [];
$seen   = [];
foreach ($items as $item) {
    $url = $item['url'] ?? '';
    if ($url && !isset($seen[$url])) {
        $seen[$url] = true;
        $unique[]   = $item;
    }
}

// ==================================================
// TÓM TẮT LỊCH SỬ CÁC PHIÊN CRAWL
// ==================================================
$recordsSummary = [];

foreach ($data as $i => $rec) {
    $recordsSummary[] = [
        'id'             => $i + 1,
        'index'          => $i,
        'url'            => $rec['url'] ?? '',
        'owner'          => $rec['owner'] ?? 'admin',
        'saved_at'       => $rec['saved_at'] ?? '',
        'title'          => $rec['title'] ?? '',
        'total_links'    => isset($rec['total_links'])    ? (int) $rec['total_links']    : (is_array($rec['links']    ?? null) ? count($rec['links'])    : 0),
        'total_images'   => isset($rec['total_images'])   ? (int) $rec['total_images']   : (is_array($rec['images']   ?? null) ? count($rec['images'])   : 0),
        'total_headings' => isset($rec['total_headings']) ? (int) $rec['total_headings'] : (is_array($rec['headings'] ?? null) ? count($rec['headings']) : 0),
        'total_articles' => isset($rec['total_articles']) ? (int) $rec['total_articles'] : (is_array($rec['articles'] ?? null) ? count($rec['articles']) : 0),
        'total_files'    => isset($rec['total_files'])    ? (int) $rec['total_files']    : (is_array($rec['files']    ?? null) ? count($rec['files'])    : 0),
        'total_products' => is_array($rec['products'] ?? null) ? count($rec['products']) : 0,
    ];
}

echo json_encode([
    'success'       => true,
    'total_records' => count($data),
    'user'          => $currentUser,
    'records'       => array_reverse($recordsSummary),
    'items'         => $unique,
], JSON_UNESCAPED_UNICODE);
