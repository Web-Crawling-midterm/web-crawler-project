# Web Data Crawler (PHP)

Dự án ghép giao diện và tài liệu của TV2 với backend PHP crawler của TV1.

## Công nghệ
- PHP 8.x, cURL, DOMDocument, XPath
- HTML, CSS, JavaScript, Bootstrap 5
- JSON để lưu dữ liệu thu thập

## Cách chạy trên Windows
1. Cài PHP 8.x và bật extension `curl` và `dom` trong `php.ini`.
2. Mở PowerShell tại thư mục chứa `index.php`.
3. Chạy: `php -S localhost:8000`
4. Mở `http://localhost:8000/index.php`.

Nếu lệnh `php` không chạy, dùng đường dẫn đầy đủ đến `php.exe`, ví dụ `C:\php\php.exe -S localhost:8000`.

## Cách sử dụng
1. Nhập URL bắt đầu bằng `http://` hoặc `https://`.
2. Phiên bản hiện tại thu thập một trang mỗi lần; để số trang là 1.
3. Bấm **Thu thập**. Backend tải HTML, phân tích tiêu đề/liên kết/hình ảnh và thông tin sản phẩm khi nhận diện được cấu trúc Books to Scrape, sau đó lưu vào `data/crawl_data.json`.
4. Có thể lọc, sắp xếp và xuất kết quả JSON/CSV.

## API PHP
- `POST backend/crawl.php`: nhận `url` dạng form-urlencoded, trả HTML dưới dạng JSON.
- `POST backend/parse_html.php`: nhận `html` và `url`, trả tiêu đề, liên kết, hình ảnh và danh sách sản phẩm nếu nhận diện được.
- `POST backend/save_data.php`: lưu kết quả vào JSON.
- `GET backend/results.php`: đọc dữ liệu đã lưu để hiển thị trên giao diện.

## Lưu ý
- Chỉ thu thập dữ liệu công khai và được phép truy cập; tôn trọng `robots.txt`, điều khoản sử dụng và giới hạn tốc độ của website.
- Parser có trường sản phẩm chuyên biệt cho Books to Scrape; website khác có thể chỉ trả tiêu đề, liên kết và hình ảnh.
- Chức năng duyệt nhiều trang chưa được triển khai trong bản PHP này.
- Kiểm thử thực tế phụ thuộc vào cấu hình PHP/cURL, chứng chỉ SSL và khả năng truy cập website nguồn.

## Tài liệu
Xem `docs/source_research.md` và `docs/architecture.md`.
