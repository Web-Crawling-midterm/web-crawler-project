# Khảo sát nguồn dữ liệu và thiết kế dữ liệu

> Người thực hiện: Thành viên 2 · Ngày khảo sát: 11/10/2026
> Mục đích: so sánh ít nhất 2 website nguồn, kiểm tra `robots.txt` / điều khoản sử dụng, chọn 1 nguồn chính và thống nhất các trường dữ liệu cần thu thập.

---

## 1. Cơ sở lý thuyết ngắn

### 1.1. Web Crawling và Web Scraping

| Tiêu chí | Web Crawling | Web Scraping |
|---|---|---|
| Mục tiêu | Duyệt, khám phá nhiều trang bằng cách đi theo liên kết (như bot của công cụ tìm kiếm) | Trích xuất dữ liệu cụ thể từ một hoặc vài trang đã biết |
| Đầu ra | Danh sách URL/trang đã tải | Dữ liệu có cấu trúc (giá, tiêu đề, đánh giá...) |
| Quy mô | Thường rộng, nhiều trang | Thường hẹp, tập trung vào trường dữ liệu |
| Trong đề tài | Phân trang: đi theo liên kết `next` qua nhiều trang | Trích xuất tiêu đề, giá, đánh giá từ mỗi sản phẩm |

Đề tài của nhóm kết hợp cả hai: **crawl** (đi qua các trang phân trang) và **scrape** (trích xuất trường dữ liệu).

### 1.2. HTTP, HTML parsing và JSON

- **HTTP**: giao thức request/response. Crawler gửi `GET <url>` kèm header (đặc biệt `User-Agent`), server trả về mã trạng thái (200 thành công, 404 không tồn tại, 403 bị chặn, 429 quá nhiều yêu cầu, 5xx lỗi server) và nội dung HTML.
- **HTML parsing**: HTML là văn bản dạng cây thẻ (DOM). Thư viện DOMDocument và XPath chuyển chuỗi HTML thành cây để tìm phần tử bằng thẻ, class, selector CSS.
- **JSON**: định dạng văn bản trao đổi dữ liệu giữa backend và frontend. Backend trả JSON, JavaScript dùng `fetch()` đọc bằng `response.json()` rồi vẽ ra bảng.

---

## 2. Quy tắc truy cập: robots.txt và điều khoản

`robots.txt` là tệp tại gốc website cho bot biết đường dẫn nào được/không được truy cập. Nó là quy ước đạo đức và kỹ thuật, không thay thế điều khoản sử dụng của website. Nguyên tắc nhóm áp dụng:

1. Kiểm tra `robots.txt` và điều khoản trước khi crawl.
2. Chỉ crawl nguồn cho phép hoặc nguồn sinh ra để học tập.
3. Có độ trễ giữa các request (đề xuất ≥ 1 giây), đặt `User-Agent` rõ ràng, giới hạn số trang.
4. Không dùng dữ liệu cho mục đích thương mại, không đăng lại nội dung có bản quyền.

---

## 3. Khảo sát các website ứng viên

Kết quả kiểm tra thực tế ngày 11/10/2026.

### 3.1. Books to Scrape — https://books.toscrape.com

- **robots.txt**: truy cập `/robots.txt` trả về **404**, tức không có quy tắc cấm.
- **Điều khoản / tuyên bố**: trang ghi rõ đây là *demo website for web scraping purposes* và khẩu hiệu "We love being scraped!". Trang cũng cảnh báo giá và đánh giá được gán ngẫu nhiên, không có ý nghĩa thực tế.
- **Quy mô**: 1000 sản phẩm, 20 sản phẩm/trang, 50 trang, có liên kết `next`.
- **Cấu trúc trang danh sách** (HTML tĩnh, không cần JavaScript để hiển thị):
  - Mỗi sách nằm trong `article.product_pod`.
  - Tiêu đề đầy đủ nằm ở thuộc tính `title` của thẻ `a` trong `h3` (phần chữ hiển thị bị cắt ngắn, ví dụ "A Light in the ...").
  - Giá: `p.price_color` (ví dụ `£51.77`).
  - Tình trạng: `p.instock.availability` (ví dụ "In stock").
  - Đánh giá sao: `p.star-rating` với class chỉ số sao dạng chữ (`One`, `Two`, `Three`, `Four`, `Five`).
  - Ảnh bìa: `img.thumbnail` (đường dẫn tương đối).
  - Phân trang: `li.next > a`.
- **Trang chi tiết** có thêm: mô tả sản phẩm, UPC, loại sản phẩm, giá chưa/đã thuế, số lượng còn (ví dụ "In stock (22 available)"), số đánh giá, danh mục (breadcrumb).

### 3.2. Quotes to Scrape — https://quotes.toscrape.com

- **robots.txt**: trả về **404**, không có quy tắc cấm.
- **Điều khoản**: cùng hệ sandbox học web scraping với Books to Scrape.
- **Dữ liệu**: trích dẫn, tác giả, thẻ (tag), có phân trang.
- **Ghi chú**: nhóm chỉ kiểm tra `robots.txt` của site này, chưa phân tích HTML chi tiết vì không chọn làm nguồn chính.
- **Hạn chế**: dữ liệu thiên về văn bản ngắn, ít trường số (không có giá, không có tình trạng), bảng kết quả kém đa dạng khi trình diễn lọc/sắp xếp.

### 3.3. VnExpress — https://vnexpress.net (nguồn thực tế, tiếng Việt)

- **robots.txt**: nhóm `User-agent: *` có `Allow: /`, có khai báo sitemap. Tuy nhiên robots.txt **cấm hoàn toàn** một số bot cụ thể, trong đó có `Scrapy`, `Diffbot`, `archive.org_bot`, `AmazonBot`. Cần đặt User-Agent riêng, không giả mạo bot khác.
- **Điều khoản sử dụng**: mọi hành vi sao chép, phân phối, xuất bản nội dung hay hình ảnh của VnExpress vì mục đích thương mại mà không có chấp thuận bằng văn bản là xâm phạm quyền sở hữu của báo.
- **Rủi ro**: nội dung là tác phẩm báo chí có bản quyền. Giao diện thường thay đổi, có thể có chống bot. Một số báo Việt Nam khác còn ghi thẳng trong điều khoản việc cấm dùng robot/crawler (ví dụ vneconomy.vn), nên khi chọn nguồn tin tức phải đọc điều khoản của từng trang.
- **Ưu điểm**: dữ liệu thực, tiếng Việt, hấp dẫn khi demo.

---

## 4. Bảng so sánh nguồn dữ liệu

| Tiêu chí | Books to Scrape | Quotes to Scrape | VnExpress |
|---|---|---|---|
| Loại dữ liệu | Sản phẩm (sách) | Trích dẫn | Bài báo |
| robots.txt | Không có (404) | Không có (404) | Cho phép `*`, cấm một số bot |
| Điều khoản | Sandbox khuyến khích scraping | Sandbox học tập | Cấm sao chép thương mại, bản quyền báo chí |
| Số lượng dữ liệu | 1000 mục / 50 trang | Ít hơn, nhiều trang | Rất lớn, thay đổi liên tục |
| Độ ổn định HTML | Rất ổn định | Rất ổn định | Có thể thay đổi |
| Phân trang | Có (`li.next`) | Có | Có, cấu trúc phức tạp hơn |
| Số trường hữu ích | 6 (tiêu đề, URL, giá, sao, tình trạng, ảnh) | 3 (nội dung, tác giả, tag) | 4–5 (tiêu đề, URL, mô tả, thời gian, chuyên mục) |
| Phù hợp lọc/sắp xếp | Tốt (giá, sao, tình trạng) | Hạn chế | Trung bình |
| Rủi ro pháp lý/đạo đức | Thấp | Thấp | Trung bình đến cao |
| Phù hợp bài tập/demo | **Rất phù hợp** | Phù hợp | Chỉ nên dùng thử mức nhỏ, có giới hạn |

---

## 5. Quyết định chọn nguồn chính

**Nguồn chính: Books to Scrape (https://books.toscrape.com).**

Lý do:
1. Được tạo ra để thực hành scraping, rủi ro pháp lý và đạo đức thấp nhất.
2. HTML ổn định, selector rõ ràng, giúp TV1 viết crawler nhanh và chắc.
3. Có phân trang để thể hiện đúng khái niệm *crawling*.
4. Có trường số (giá, sao) để làm tìm kiếm, lọc và sắp xếp ở tuần sau.
5. Dữ liệu cố định nên dễ **đối chiếu kết quả** giữa giao diện và backend khi kiểm thử.

Nguồn phụ: Quotes to Scrape (dùng thử khả năng mở rộng sang nguồn khác nếu còn thời gian). VnExpress chỉ dùng để so sánh, không đưa vào bộ dữ liệu nộp bài.

---

## 6. Các trường dữ liệu cần thu thập (thống nhất giữa TV1 và TV2)

| Trường | Kiểu | Nguồn trong HTML | Ghi chú |
|---|---|---|---|
| `title` | string | thuộc tính `title` của `h3 > a` | Dùng thuộc tính, không dùng text hiển thị vì bị cắt |
| `url` | string | `href` của `h3 > a` | Chuyển thành URL tuyệt đối bằng `urljoin`. Dùng làm khóa chống trùng |
| `price` | number hoặc null | `p.price_color` | Bỏ ký hiệu `£`, đơn vị GBP, thiếu thì `null` |
| `currency` | string | cố định | `"GBP"` |
| `rating` | integer hoặc null | class của `p.star-rating` | `One`→1 ... `Five`→5, thiếu thì `null` |
| `availability` | string | `p.instock.availability` | Ví dụ `"In stock"` |
| `image_url` | string hoặc null | `img.thumbnail[src]` | URL tuyệt đối |

Dữ liệu thiếu để `null`, không bỏ trường, để frontend luôn đọc đủ khóa.

---

## 7. Hợp đồng API đề xuất (cần TV1 xác nhận)

Phần này để hai người code khớp nhau từ đầu. Frontend đã được viết đúng theo hợp đồng này.

### 7.1. `POST backend/crawl.php`

Yêu cầu (JSON):

```json
{ "url": "https://books.toscrape.com/", "max_pages": 1 }
```

- `url`: bắt buộc, `http` hoặc `https`.
- `max_pages`: tùy chọn, số nguyên 1–50, mặc định 1.

Phản hồi thành công (HTTP 200):

```json
{
  "status": "success",
  "source_url": "https://books.toscrape.com/",
  "crawled_at": "2026-10-11T23:30:00+07:00",
  "pages_crawled": 1,
  "total_found": 20,
  "duplicates_removed": 0,
  "items": [
    {
      "title": "A Light in the Attic",
      "url": "https://books.toscrape.com/catalogue/a-light-in-the-attic_1000/index.html",
      "price": 51.77,
      "currency": "GBP",
      "rating": 3,
      "availability": "In stock",
      "image_url": "https://books.toscrape.com/media/cache/2c/da/2cdad67c44b002e7ead0cc35693c0e8b.jpg"
    }
  ],
  "warnings": []
}
```

Trong đó `total_found` là số mục đã thu thập trước khi lọc trùng, `items` là danh sách sau khi lọc trùng. `warnings` là danh sách chuỗi (ví dụ một trang bị lỗi nhưng các trang khác vẫn thành công).

Phản hồi lỗi:

```json
{ "status": "error", "code": "INVALID_URL", "message": "URL không hợp lệ" }
```

| `code` | HTTP | Ý nghĩa |
|---|---|---|
| `INVALID_URL` | 400 | URL sai định dạng hoặc thiếu |
| `HTTP_ERROR` | 502 | Website nguồn trả mã lỗi (404, 403, 5xx) |
| `CONNECTION_ERROR` | 502 | Không kết nối được (DNS, mạng) |
| `TIMEOUT` | 504 | Quá thời gian chờ |
| `NO_DATA` | 200 hoặc 422 | Tải được trang nhưng không trích xuất được dữ liệu |

Frontend chỉ phụ thuộc vào `status`, `message`, `items` và đọc `code` nếu có, nên các chi tiết khác có thể thay đổi nhẹ.

### 7.2. `GET backend/results.php` (tuần 2, tùy chọn)

Trả về dữ liệu đã lưu: `{ "status": "success", "items": [...] }`. Nếu endpoint chưa có (404), frontend bỏ qua êm, không báo lỗi.

---

## 8. Phương án lưu dữ liệu

- Tuần 1: backend trả JSON và có thể ghi tạm `data/results.json`.
- Khi crawler cơ bản xong: SQLite `data/crawler.db`.

Bảng đề xuất:

```sql
CREATE TABLE IF NOT EXISTS items (
  id INTEGER PRIMARY KEY AUTOINCREMENT,
  url TEXT NOT NULL UNIQUE,      -- chống trùng ở mức CSDL
  title TEXT,
  price REAL,
  currency TEXT,
  rating INTEGER,
  availability TEXT,
  image_url TEXT,
  source_url TEXT,
  crawled_at TEXT
);
```

Các khóa trùng khớp với JSON ở mục 6 nên chuyển từ JSON sang SQLite không cần đổi cấu trúc.

---

## 9. Lưu ý kỹ thuật gửi TV1

1. Đặt `response.encoding = "utf-8"` (hoặc dùng `response.content` cho DOMDocument và XPath), nếu không ký hiệu `£` có thể bị lỗi thành `Â£`.
2. Link trong trang danh sách là đường dẫn tương đối; link `next` ở trang 1 khác dạng với các trang sau, nên luôn dùng `urllib.parse.urljoin(url_trang_hiện_tại, href)`.
3. Đặt `timeout` (ví dụ 10 giây), `User-Agent` riêng, nghỉ ≥ 1 giây giữa các trang, giới hạn `max_pages`.
4. Chỉ cho phép `http`/`https`, nên chặn địa chỉ nội bộ (localhost, 127.0.0.1, dải IP riêng) để tránh SSRF khi website có thể bị người khác truy cập.
5. Chống trùng theo `url` đã chuẩn hóa.
