<?php
/**
 * router.php – Router cho máy chủ tích hợp của PHP (`php -S localhost:8000 router.php`).
 *
 * Máy chủ tích hợp không đọc .htaccess, nên nếu thiếu router này thì có thể tải trực tiếp
 * data/users.json (mã băm mật khẩu, email) hoặc certs/ qua trình duyệt.
 * Router chặn các thư mục/tệp nhạy cảm, còn lại giao cho PHP xử lý như bình thường.
 */
$path = rawurldecode(parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/');
$path = str_replace('\\', '/', $path);

// Chặn đường dẫn có "..", byte NUL, hoặc thư mục/tệp ẩn (.git, .gitignore, .htaccess, ...)
$blocked = strpos($path, "\0") !== false
        || strpos($path, '..') !== false
        || preg_match('#(^|/)\.#', $path)
        // Chặn toàn bộ thư mục dữ liệu và chứng chỉ (không phân biệt hoa/thường)
        || preg_match('#^/(data|certs)(/|$)#i', $path);

if ($blocked) {
    http_response_code(403);
    header('Content-Type: text/plain; charset=utf-8');
    echo '403 Forbidden';
    return true;
}

return false;   // để máy chủ tích hợp phục vụ tệp tĩnh / chạy PHP như thường