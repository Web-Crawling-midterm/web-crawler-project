# CrawlWise – Hệ thống Web Crawler & Khai thác dữ liệu

Đồ án môn học **Web Crawling & Scraping**. Ứng dụng web viết bằng **PHP (backend) + JavaScript thuần & Bootstrap 5 (frontend)**: người dùng đăng nhập, nhập một URL, hệ thống tải trang, kiểm tra `robots.txt`, trích xuất dữ liệu (bài viết, tiêu đề, liên kết, hình ảnh, tệp tin, sản phẩm), cho xem/lọc/sắp xếp, lưu lịch sử và xuất CSV/JSON.

> Dự án **không dùng Flutter, Composer, npm hay cơ sở dữ liệu**. Chỉ cần PHP và trình duyệt. Dữ liệu lưu trong các tệp JSON ở thư mục `data/`.

---

## Mục lục

1. [Tính năng](#1-tính-năng)
2. [Yêu cầu hệ thống](#2-yêu-cầu-hệ-thống)
3. [Cài đặt và chạy dự án](#3-cài-đặt-và-chạy-dự-án)
4. [Tài khoản mẫu](#4-tài-khoản-mẫu)
5. [Hướng dẫn kiểm thử tính năng](#5-hướng-dẫn-kiểm-thử-tính-năng)
6. [Cấu trúc thư mục](#6-cấu-trúc-thư-mục)
7. [Phương pháp crawl](#7-phương-pháp-crawl)
8. [Cấu hình](#8-cấu-hình)
9. [Xử lý sự cố](#9-xử-lý-sự-cố)
10. [Lưu ý bảo mật & đạo đức](#10-lưu-ý-bảo-mật--đạo-đức)

---

## 1. Tính năng

| Nhóm | Mô tả |
|---|---|
| **Thu thập dữ liệu** | Nhập URL + chọn loại dữ liệu (tự động / blog / tiêu đề / tệp / liên kết / hình ảnh / sản phẩm). Hiển thị thống kê và bảng kết quả có lọc, sắp xếp. |
| **Robots.txt** | Công cụ kiểm tra một URL có được phép crawl hay không, theo từng User-Agent (chuẩn RFC 9309). Crawler tự kiểm tra trước mỗi lần thu thập. |
| **Dữ liệu đã lưu** | Mỗi phiên crawl tự lưu vào `data/crawl_data.json`; xem lại, xóa từng phiên hoặc xóa tất cả. |
| **Xuất dữ liệu** | Xuất CSV (mở được bằng Excel) hoặc sao chép JSON. |
| **Tài khoản** | Đăng ký, đăng nhập, đăng xuất, quên/đặt lại mật khẩu. Hai vai trò: `admin` (xem/xóa mọi dữ liệu) và `user` (chỉ dữ liệu của mình). |
| **An toàn** | Chặn SSRF (không crawl IP nội bộ), chống brute-force đăng nhập, token CSRF, giới hạn tần suất đăng ký/quên mật khẩu. |

---

## 2. Yêu cầu hệ thống

| Thành phần | Yêu cầu |
|---|---|
| Hệ điều hành | Windows 10/11, macOS hoặc Linux (Ubuntu/Debian…) |
| **PHP** | **8.0 trở lên** (mã nguồn dùng biểu thức `match`). Đã kiểm thử với **PHP 8.3** |
| PHP extensions | `curl`, `dom` (kèm `libxml`), `mbstring`, `openssl`, `json`, `session` |
| Trình duyệt | Chrome, Edge, Firefox hoặc Safari bản mới |
| Kết nối Internet | **Bắt buộc**: giao diện tải Bootstrap từ CDN (`cdn.jsdelivr.net`) và crawler cần truy cập website đích |
| Quyền ghi | PHP phải ghi được vào thư mục `data/` |

Không cần cài Composer, Node.js, MySQL hay bất kỳ thư viện nào khác.

### Kiểm tra máy đã đủ điều kiện chưa

```bash
php -v
php -m
```

`php -v` phải hiện phiên bản **8.0 trở lên**. Trong kết quả `php -m` phải có đủ các dòng: `curl`, `dom`, `json`, `libxml`, `mbstring`, `openssl` (và `session`). Bật nhanh bằng lệnh lọc:

- **macOS / Linux:** `php -m | grep -Ei "curl|dom|mbstring|openssl|json|session"`
- **Windows (PowerShell):** `php -m | Select-String "curl|dom|mbstring|openssl|json|session"`

---

## 3. Cài đặt và chạy dự án

Có hai cách chạy. **Cách A** nhanh nhất, **Cách B** dùng XAMPP (phù hợp nếu máy đã có XAMPP).

### Bước 0 – Cài PHP (nếu chưa có)

| Hệ điều hành | Lệnh / thao tác |
|---|---|
| **Windows** | Cài [XAMPP](https://www.apachefriends.org/) (bản PHP 8.x), hoặc tải PHP tại <https://windows.php.net/download/> (bản *Thread Safe* zip), giải nén vào `C:\php` rồi thêm `C:\php` vào biến môi trường `PATH`. Mở lại PowerShell và gõ `php -v` để kiểm tra. |
| **macOS** | `brew install php` |
| **Ubuntu/Debian** | `sudo apt update && sudo apt install -y php-cli php-curl php-xml php-mbstring` |

> **Windows, PHP tải thủ công:** sao chép `php.ini-development` thành `php.ini`, mở bằng trình soạn thảo và bỏ dấu `;` ở đầu các dòng `extension=curl`, `extension=mbstring`, `extension=openssl` (và `extension_dir = "ext"`). Extension `dom` có sẵn, không cần bật. Với XAMPP các extension này thường đã bật sẵn.

### Cách A – Máy chủ tích hợp của PHP (khuyên dùng)

1. Giải nén dự án, mở terminal **tại thư mục gốc** (thư mục có `index.php` và `router.php`).
2. Chạy:

   ```bash
   php -S localhost:8000 router.php
   ```

3. Mở trình duyệt: **<http://localhost:8000/>** → tự chuyển tới trang chủ. Bấm **Đăng nhập** và dùng [tài khoản mẫu](#4-tài-khoản-mẫu).
4. Dừng máy chủ bằng `Ctrl + C`.

> **Vì sao phải có `router.php`?** Máy chủ tích hợp của PHP không đọc `.htaccess`, nên nếu chạy `php -S localhost:8000` (không kèm `router.php`) thì `data/users.json` có thể bị tải trực tiếp qua trình duyệt. `router.php` chặn truy cập vào `data/` và `certs/`.

Cổng 8000 đang bận? Đổi sang cổng khác, ví dụ `php -S localhost:8080 router.php`, rồi mở `http://localhost:8080/`.

### Cách B – XAMPP (Apache)

1. Chép toàn bộ thư mục dự án vào `C:\xampp\htdocs\crawlwise` (macOS: `/Applications/XAMPP/htdocs/crawlwise`).
2. Mở **XAMPP Control Panel** → **Start** module **Apache**.
3. Mở **<http://localhost/crawlwise/>**.

Trên Apache, `data/.htaccess` đã chặn truy cập trực tiếp vào `data/` nên không cần `router.php`.

### Lần chạy đầu tiên

- Hệ thống **tự tạo** `data/users.json` với hai tài khoản mẫu; các tệp `crawl_data.json`, `login_attempts.json`, `rate_limits.json` cũng tự tạo khi cần. Không phải cấu hình gì thêm.
- Không cần import cơ sở dữ liệu.

---

## 4. Tài khoản mẫu

| Vai trò | Tên đăng nhập | Mật khẩu |
|---|---|---|
| Quản trị | `admin` | `Admin@123` |
| Người dùng | `user` | `User@123` |

Bạn cũng có thể tự đăng ký tài khoản mới tại trang **Đăng ký** (luôn có vai trò `user`; mật khẩu tối thiểu 8 ký tự, gồm cả chữ và số).

> Đổi mật khẩu mặc định nếu triển khai ngoài máy cá nhân.

---

## 5. Hướng dẫn kiểm thử tính năng

Đăng nhập trước, rồi làm lần lượt các kịch bản bên dưới. Cột **Kết quả mong đợi** là kết quả đúng.

> Các mục **T7–T9** cần Internet để truy cập website đích.

### 5.1. Tài khoản & bảo mật

| # | Kịch bản | Các bước | Kết quả mong đợi |
|---|---|---|---|
| T1 | Chặn truy cập khi chưa đăng nhập | Mở thẳng `http://localhost:8000/Frontend/pages/index.php` khi chưa đăng nhập | Tự chuyển về trang **Đăng nhập** |
| T2 | Đăng nhập đúng | Nhập `admin` / `Admin@123` | Vào trang crawler; góc phải hiện tên `admin` cùng nhãn vai trò |
| T3 | Đăng nhập sai & khóa tạm | Với `user`, nhập sai mật khẩu liên tiếp **6 lần** | 5 lần đầu báo *"Tên đăng nhập hoặc mật khẩu không đúng"*; lần thứ 6 báo *"Đăng nhập sai quá nhiều lần. Thử lại sau 5 phút."* |
| T4 | Đăng ký | Trang **Đăng ký**, nhập tên (3–32 ký tự), email hợp lệ, mật khẩu ≥ 8 ký tự có chữ và số | Chuyển sang trang đăng nhập kèm thông báo thành công; đăng nhập được bằng tài khoản mới |
| T5 | Quên mật khẩu (chế độ demo) | **Quên mật khẩu?** → nhập email đã đăng ký → mở liên kết hiển thị trên trang → nhập mật khẩu mới | Đặt lại thành công, đăng nhập được bằng mật khẩu mới. Liên kết hiệu lực 15 phút, **chỉ dùng được 1 lần** |
| T6 | Đăng xuất | Bấm **Đăng xuất** | Về trang đăng nhập; mở lại `index.php` bị yêu cầu đăng nhập |

### 5.2. Thu thập dữ liệu

| # | Kịch bản | Các bước | Kết quả mong đợi |
|---|---|---|---|
| T7 | Crawl sản phẩm | Tab **Thu thập dữ liệu** → bấm nút mẫu **Books to Scrape** → chọn *Sản phẩm* → **Bắt đầu crawl** | Hiện thống kê và bảng sản phẩm (ảnh bìa, tên, giá, đánh giá sao, tình trạng kho). Thử lọc theo tên/đánh giá/tình trạng và bấm tiêu đề cột để sắp xếp |
| T8 | Crawl bài viết / tiêu đề | Bấm **VnExpress Số hóa**, chọn *Tất cả*, crawl, rồi chuyển qua các tab **Bài viết**, **Tiêu đề**, **Liên kết**, **Hình ảnh**, **Mã HTML** | Mỗi tab hiện đúng loại dữ liệu và số lượng khớp với con số ở đầu tab; ô lọc hoạt động |
| T9 | Xuất dữ liệu | Sau khi crawl, bấm **Xuất CSV** (chọn tab *Sản phẩm* hoặc *Liên kết* trước) và **Sao chép JSON** | Tải được tệp `.csv` mở bằng Excel đúng tiếng Việt; JSON được sao chép vào clipboard |
| T10 | URL không hợp lệ | Nhập `abc` rồi crawl | Ô URL báo lỗi *"Vui lòng nhập URL hợp lệ bắt đầu bằng http:// hoặc https://"* |
| T11 | Chặn SSRF | Nhập `http://127.0.0.1/` rồi `http://192.168.1.1/` và crawl | Cả hai bị từ chối: *"Website không thuộc phạm vi được phép truy cập."* |
| T12 | Chặn giao thức lạ | Nhập `ftp://example.com/` | Bị từ chối: *"Chỉ hỗ trợ URL HTTP hoặc HTTPS."* |

### 5.3. Robots.txt

| # | Kịch bản | Các bước | Kết quả mong đợi |
|---|---|---|---|
| T13 | URL được phép | Tab **Robots.txt & Đạo đức** → bấm mẫu **VnExpress Thời sự** → **Kiểm tra** | Huy hiệu xanh **CHO PHÉP TRUY CẬP (ALLOWED)**; hiện nút **Dùng URL này để thu thập dữ liệu** (bấm vào sẽ chuyển sang tab Thu thập và điền sẵn URL) |
| T14 | URL bị cấm | Bấm mẫu **Google Search (Disallow)** → **Kiểm tra** | Huy hiệu đỏ **BỊ CHẶN TRUY CẬP (DISALLOWED)**, kèm thông báo quy tắc khớp; không có nút crawl |
| T15 | Đổi User-Agent | Chọn `Googlebot`, `GPTBot` hoặc *Tùy chỉnh* rồi kiểm tra lại cùng URL | Kết quả có thể khác nhau tùy luật riêng của từng bot trong `robots.txt` |

### 5.4. Dữ liệu đã lưu

| # | Kịch bản | Các bước | Kết quả mong đợi |
|---|---|---|---|
| T16 | Lịch sử phiên crawl | Sau T7/T8, mở tab **Dữ liệu đã lưu** | "Phiên crawl" và "Mục đã lưu" tăng; bảng lịch sử có thêm dòng mới (thời gian, URL, số bài viết/tệp/liên kết/hình/sản phẩm) |
| T17 | Xem lại | Bấm **Xem trên bảng** | Quay về tab Thu thập và hiện dữ liệu đã lưu |
| T18 | Xóa | Bấm biểu tượng thùng rác ở một dòng, rồi **Xóa tất cả** | Dòng/lịch sử biến mất, bộ đếm cập nhật (`user` chỉ xóa được dữ liệu của mình, `admin` xóa được tất cả) |
| T19 | Phân quyền | Đăng nhập `user`, crawl một trang; đăng xuất, đăng nhập `admin` | `user` chỉ thấy phiên của mình; `admin` thấy phiên của tất cả người dùng |

### 5.5. Kiểm tra cú pháp mã nguồn (tuỳ chọn)

Chạy ở thư mục gốc; không được báo lỗi nào ngoài dòng `No syntax errors detected`:

- **macOS / Linux:** `find . -name "*.php" -not -path "./certs/*" -exec php -l {} \;`
- **Windows (PowerShell):** `Get-ChildItem -Recurse -Filter *.php | ForEach-Object { php -l $_.FullName }`

### 5.6. Đặt lại dữ liệu về trạng thái ban đầu

Dừng máy chủ, xóa các tệp sau trong `data/` (giữ lại `.htaccess`):

```
users.json   crawl_data.json   login_attempts.json   rate_limits.json   password_resets.json   outbox/
```

Lần chạy kế tiếp hệ thống tự tạo lại hai tài khoản mẫu.

---

## 6. Cấu trúc thư mục

```
.
├── index.php                 # Điểm vào: chuyển tới trang chủ
├── router.php                # Router cho `php -S` (chặn truy cập data/, certs/)
├── backend/                  # API PHP (trả JSON)
│   ├── auth.php              # Phiên, đăng nhập/đăng ký, CSRF, chống brute-force
│   ├── crawl.php             # Tải HTML bằng cURL (SSRF + robots.txt + redirect an toàn)
│   ├── check_robot.php       # Phân tích robots.txt (RFC 9309)
│   ├── parse_html.php        # Trích xuất dữ liệu bằng DOMDocument + XPath
│   ├── save_data.php         # Lưu / xóa phiên crawl (data/crawl_data.json)
│   ├── results.php           # Trả lịch sử và dữ liệu đã lưu
│   └── export_csv.php        # Xuất CSV
├── Frontend/
│   ├── pages/                # home, login, register, forgot_password, reset_password, index (crawler)
│   ├── partials/             # Phần đầu/cuối dùng chung cho các trang xác thực
│   └── assets/               # css/, js/main.js, img/
├── data/                     # Dữ liệu JSON (tự tạo; bị chặn truy cập trực tiếp)
└── certs/cacert.pem          # Gói chứng chỉ CA cho cURL
```

---

## 7. Phương pháp crawl

1. Trình duyệt gửi URL tới `backend/crawl.php`.
2. Server kiểm tra URL (chỉ http/https), phân giải tên miền và **từ chối IP riêng/nội bộ** (chống SSRF).
3. Đọc `robots.txt` bằng User-Agent `WebCrawlingStudentProject/1.0`; nếu bị `Disallow` thì dừng.
4. Tải HTML bằng **cURL** (timeout kết nối 10 giây, tổng 30 giây). Redirect được tự xử lý và **kiểm tra lại từng bước**.
5. `backend/parse_html.php` nạp HTML vào `DOMDocument` và trích xuất bằng `DOMXPath`.
6. Kết quả hiển thị lên giao diện và được lưu vào `data/crawl_data.json` (ghi kèm `LOCK_EX`).

**Giới hạn:** crawler chỉ đọc HTML tĩnh, **không chạy JavaScript**, nên các trang dựng nội dung bằng JS (React/Vue, cuộn vô hạn) có thể không lấy được dữ liệu. Phần trích xuất *Sản phẩm* viết cho cấu trúc của trang Books to Scrape.

---

## 8. Cấu hình

Các hằng số nằm đầu tệp `backend/auth.php`:

| Hằng số | Mặc định | Ý nghĩa |
|---|---|---|
| `AUTH_MAX_FAILS` | 5 | Số lần đăng nhập sai tối đa |
| `AUTH_LOCK_SECONDS` | 300 | Thời gian khóa sau khi sai quá số lần (giây) |
| `AUTH_SESSION_IDLE` | 3600 | Tự đăng xuất sau bao nhiêu giây không hoạt động |
| `AUTH_RESET_TTL_SECONDS` | 900 | Hiệu lực liên kết đặt lại mật khẩu |
| `AUTH_MIN_PASSWORD` | 8 | Độ dài mật khẩu tối thiểu |
| `AUTH_DEMO_SHOW_RESET_LINK` | `true` | Chế độ demo: hiển thị liên kết đặt lại mật khẩu ngay trên trang (vì localhost không gửi được email) |
| `AUTH_BASE_URL` | `''` | Khi triển khai thật, đặt thành `https://ten-mien/Frontend/pages` và đổi `AUTH_DEMO_SHOW_RESET_LINK` thành `false` để gửi email bằng `mail()` |
| `AUTH_DEFAULT_USERS` | admin, user | Tài khoản mẫu tạo ở lần chạy đầu tiên |

---

## 9. Xử lý sự cố

| Triệu chứng | Nguyên nhân & cách xử lý |
|---|---|
| `php` is not recognized / `command not found` | PHP chưa cài hoặc chưa nằm trong `PATH`. Cài theo [Bước 0](#bước-0--cài-php-nếu-chưa-có), mở lại terminal rồi gõ `php -v`. |
| Báo *"PHP chưa bật extension cURL"* | Bật `extension=curl` trong `php.ini` (xem `php --ini` để biết đường dẫn tệp), khởi động lại máy chủ. |
| `Call to undefined function mb_substr()` hoặc lỗi `DOMDocument` | Thiếu extension `mbstring` hoặc `dom`/`xml`. Bật trong `php.ini` hoặc `sudo apt install php-mbstring php-xml`. |
| Lỗi cú pháp ở dòng có `match` | PHP cũ hơn 8.0. Nâng cấp PHP. |
| `Failed to listen on localhost:8000 (reason: Address already in use)` | Cổng đang bận. Dùng cổng khác: `php -S localhost:8080 router.php`. |
| Trang hiện nhưng **không có giao diện** (chữ thô, không màu) | Máy không tải được Bootstrap từ `cdn.jsdelivr.net`. Kiểm tra Internet/tường lửa/VPN. |
| Báo lỗi SSL / *certificate* khi crawl | Cập nhật `certs/cacert.pem` (tải bản mới tại <https://curl.se/ca/cacert.pem>) hoặc đặt `curl.cainfo` trong `php.ini` trỏ tới tệp đó. |
| Không đăng nhập/lưu được, báo lỗi quyền ghi | PHP không ghi được vào `data/`. macOS/Linux: `chmod -R u+rwX data`. |
| Mở `index.php` bằng cách bấm đúp chỉ thấy mã nguồn | PHP phải chạy qua máy chủ. Dùng [Cách A hoặc B](#3-cài-đặt-và-chạy-dự-án) ở trên. |
| Đăng nhập sai nhiều lần bị khóa mà cần thử lại ngay | Xóa `data/login_attempts.json` (xem mục 5.6). |

---

## 10. Lưu ý bảo mật & đạo đức

- Chỉ crawl dữ liệu **công khai**, tuân thủ `robots.txt` và Điều khoản dịch vụ của website đích; dùng cho **mục đích học tập, nghiên cứu**.
- Không thu thập dữ liệu cá nhân nhạy cảm, không vượt cơ chế đăng nhập/CAPTCHA.
- Crawler khai báo User-Agent rõ ràng (`WebCrawlingStudentProject/1.0`) và đặt độ trễ giữa các lượt tải.
- Mật khẩu được băm bằng `password_hash()`; phiên dùng cookie `HttpOnly`, `SameSite=Lax`; mọi yêu cầu `POST` đều kiểm tra token CSRF.
- Khi chạy `php -S`, **luôn thêm `router.php`** để không lộ thư mục `data/`.
- Thư mục `data/` chứa dữ liệu thật của người dùng; không đưa `users.json` lên kho mã nguồn công khai (đã có sẵn trong `.gitignore`).
