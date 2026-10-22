<!DOCTYPE html>
<html lang="vi">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Hệ Thống Web Crawler & Khai Thác Dữ Liệu | Đồ Án Môn Học</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
  <link href="assets/css/style.css" rel="stylesheet">
</head>
<body>

<!-- Thanh điều hướng chính -->
<nav class="navbar navbar-dark academic-navbar">
  <div class="container">
    <div class="d-flex align-items-center gap-2">
      <i class="bi bi-diagram-3-fill text-white fs-5"></i>
      <span class="navbar-brand mb-0">Hệ Thống Web Crawler &amp; Khai Thác Dữ Liệu</span>
      <span class="academic-badge d-none d-md-inline">Đồ án Sinh viên Năm 3</span>
    </div>
    <span class="navbar-text small text-white-50 d-none d-lg-inline">
      PHP 8 &middot; cURL &middot; DOMDocument &middot; XPath
    </span>
  </div>
</nav>

<div class="container py-3">
  <!-- Thanh điều hướng Tabs -->
  <ul class="nav nav-tabs academic-tabs" id="projectTabs" role="tablist">
    <li class="nav-item" role="presentation">
      <button class="nav-link active" id="crawler-tab" data-bs-toggle="tab" data-bs-target="#tab-crawler" type="button" role="tab">
        <i class="bi bi-cloud-arrow-down me-1"></i> Thu thập Dữ liệu (Live Demo)
      </button>
    </li>
    <li class="nav-item" role="presentation">
      <button class="nav-link" id="robots-tab" data-bs-toggle="tab" data-bs-target="#tab-robots" type="button" role="tab">
        <i class="bi bi-shield-check me-1"></i> Kiểm tra Robots.txt &amp; Đạo đức
      </button>
    </li>
    <li class="nav-item" role="presentation">
      <button class="nav-link" id="saved-tab" data-bs-toggle="tab" data-bs-target="#tab-saved" type="button" role="tab">
        <i class="bi bi-database me-1"></i> Dữ liệu Đã Lưu &amp; Lịch sử
      </button>
    </li>
    <li class="nav-item" role="presentation">
      <button class="nav-link" id="theory-tab" data-bs-toggle="tab" data-bs-target="#tab-theory" type="button" role="tab">
        <i class="bi bi-journal-text me-1"></i> Báo cáo Lý thuyết (Key Points)
      </button>
    </li>
  </ul>

  <!-- Nội dung các Tabs -->
  <div class="tab-content pt-3" id="projectTabsContent">

    <!-- ==================== TAB 1: CRAWLER DEMO ==================== -->
    <div class="tab-pane fade show active" id="tab-crawler" role="tabpanel" aria-labelledby="crawler-tab">
      
      <!-- Card Form Thu thập -->
      <section class="card card-academic" aria-labelledby="crawler-title">
        <div class="card-header">
          <span><i class="bi bi-sliders me-2"></i>Tham số cấu hình Crawler</span>
          <span class="badge text-bg-light border text-muted small fw-normal">Polite Crawler &middot; Timeout 20s</span>
        </div>
        <div class="card-body">
          <form id="crawl-form" novalidate>
            <div class="row g-3 align-items-end">
              <div class="col-12 col-md-5 col-lg-5">
                <label for="url-input" class="form-label fw-semibold small">URL Website nguồn cần thu thập (Blog, Store, File...):</label>
                <div class="input-group">
                  <span class="input-group-text bg-light"><i class="bi bi-link-45deg"></i></span>
                  <input type="url" class="form-control" id="url-input"
                         placeholder="https://books.toscrape.com/ hoặc URL Blog/Tệp..." autocomplete="off" required>
                </div>
                <div class="invalid-feedback" id="url-feedback">Vui lòng nhập URL hợp lệ bắt đầu bằng http:// hoặc https://</div>
              </div>

              <div class="col-6 col-md-3 col-lg-3">
                <label for="crawl-mode" class="form-label fw-semibold small">Bộ lọc trích xuất mục tiêu:</label>
                <select class="form-select" id="crawl-mode">
                  <option value="all">⚡ Tất cả (Tự động phát hiện)</option>
                  <option value="blog">📰 Blog &amp; Bài viết (Tin tức)</option>
                  <option value="headings">📑 Cây Tiêu đề (Headings H1-H6)</option>
                  <option value="files">📁 Tệp tin &amp; Tài liệu (PDF, ZIP...)</option>
                  <option value="links">🔗 Danh sách Liên kết (Links)</option>
                  <option value="images">🖼️ Bộ sưu tập Hình ảnh</option>
                  <option value="products">🛍️ Sản phẩm (Books to Scrape)</option>
                </select>
              </div>

              <div class="col-6 col-md-2 col-lg-2">
                <label for="max-pages" class="form-label fw-semibold small">Số trang (Pages):</label>
                <input type="number" class="form-control" id="max-pages" min="1" max="10" value="1" title="Crawler đa trang tự động theo liên kết Next">
              </div>

              <div class="col-12 col-md-2 col-lg-2">
                <button type="submit" class="btn btn-primary w-100" id="crawl-btn">
                  <span class="spinner-border spinner-border-sm me-1 d-none" id="btn-spinner" role="status"></span>
                  <i class="bi bi-play-circle me-1" id="btn-icon"></i>
                  <span id="btn-text">Bắt đầu Crawl</span>
                </button>
              </div>
            </div>

            <!-- Bộ lọc tùy biến nâng cao (XPath Filter) -->
            <div class="mt-2">
              <a class="small text-decoration-none" data-bs-toggle="collapse" href="#advanced-filter-box" role="button" aria-expanded="false">
                <i class="bi bi-funnel me-1"></i>Tùy chọn bộ lọc nâng cao (Custom XPath Selector) <i class="bi bi-chevron-down small"></i>
              </a>
              <div class="collapse mt-2" id="advanced-filter-box">
                <div class="p-2.5 bg-light rounded border">
                  <div class="row g-2 align-items-center">
                    <div class="col-12 col-md-8">
                      <label for="custom-xpath" class="form-label small fw-semibold mb-1">Cú pháp XPath tùy chỉnh (tùy chọn):</label>
                      <input type="text" class="form-control form-control-sm" id="custom-xpath" placeholder="//article | //h2[@class='entry-title'] | //a[contains(@href, '.pdf')]">
                    </div>
                    <div class="col-12 col-md-4">
                      <label class="form-label small text-muted mb-1 d-block">Gợi ý cú pháp:</label>
                      <span class="badge text-bg-secondary me-1 xpath-preset" style="cursor: pointer;" data-xpath="//h1 | //h2">//h1|//h2</span>
                      <span class="badge text-bg-secondary me-1 xpath-preset" style="cursor: pointer;" data-xpath="//article">//article</span>
                      <span class="badge text-bg-secondary xpath-preset" style="cursor: pointer;" data-xpath="//a[contains(@href,'.pdf')]">File .PDF</span>
                    </div>
                  </div>
                </div>
              </div>
            </div>

            <!-- Nguồn mẫu -->
            <div class="mt-3 pt-2 border-top">
              <span class="small text-muted me-2"><i class="bi bi-lightning-charge me-1"></i>Website thử nghiệm mẫu:</span>
              <button type="button" class="quick-pill example-url" data-url="https://books.toscrape.com/">Books to Scrape (Sản phẩm)</button>
              <button type="button" class="quick-pill example-url" data-url="https://vnexpress.net/so-hoa">VnExpress Số Hóa (Blog/Tin tức)</button>
              <button type="button" class="quick-pill example-url" data-url="https://quotes.toscrape.com/">Quotes to Scrape</button>
              <button type="button" class="quick-pill example-url" data-url="https://www.w3.org/WAI/ER/tests/xhtml/testfiles/resources/pdf/">Trang chứa tệp PDF mẫu (Tệp)</button>
            </div>

            <!-- Ghi chú cơ chế -->
            <div class="small text-muted mt-2 d-flex align-items-center justify-content-between flex-wrap gap-2">
              <div>
                <i class="bi bi-shield-lock me-1 text-success"></i>
                Hỗ trợ cào Đa trang (Pagination), lọc Blog/Bài viết, Tiêu đề, Tệp tin, Liên kết, Hình ảnh; kiểm tra IP chống SSRF và kiểm tra robots.txt.
              </div>
              <a href="#" class="text-decoration-none small" id="quick-check-robots-link">
                <i class="bi bi-box-arrow-up-right me-1"></i>Kiểm tra robots.txt của URL này
              </a>
            </div>
          </form>
        </div>
      </section>

      <!-- Vùng thông báo trạng thái -->
      <div id="status-area" aria-live="polite" class="mb-3"></div>

      <!-- Thống kê kết quả -->
      <section id="summary" class="row g-2 mb-3 d-none" aria-label="Thống kê">
        <div class="col-4 col-md-2">
          <div class="stat-box">
            <div class="stat-num" id="stat-articles">0</div>
            <div class="stat-desc"><i class="bi bi-journal-richtext me-1"></i>Bài viết Blog</div>
          </div>
        </div>
        <div class="col-4 col-md-2">
          <div class="stat-box">
            <div class="stat-num" id="stat-headings">0</div>
            <div class="stat-desc"><i class="bi bi-type-h1 me-1"></i>Cây Tiêu đề</div>
          </div>
        </div>
        <div class="col-4 col-md-2">
          <div class="stat-box">
            <div class="stat-num" id="stat-files">0</div>
            <div class="stat-desc"><i class="bi bi-file-earmark-arrow-down me-1"></i>Tệp tài liệu</div>
          </div>
        </div>
        <div class="col-4 col-md-2">
          <div class="stat-box">
            <div class="stat-num" id="stat-items">0</div>
            <div class="stat-desc"><i class="bi bi-basket me-1"></i>Sản phẩm</div>
          </div>
        </div>
        <div class="col-4 col-md-2">
          <div class="stat-box">
            <div class="stat-num" id="stat-links">0</div>
            <div class="stat-desc"><i class="bi bi-link-45deg me-1"></i>Liên kết</div>
          </div>
        </div>
        <div class="col-4 col-md-2">
          <div class="stat-box">
            <div class="stat-num" id="stat-images">0</div>
            <div class="stat-desc"><i class="bi bi-image me-1"></i>Hình ảnh</div>
          </div>
        </div>
      </section>

      <!-- Khung kết quả thu thập -->
      <section id="results-card" class="card card-academic d-none" aria-labelledby="results-title">
        <div class="card-header flex-wrap gap-2">
          <div class="d-flex align-items-center gap-2">
            <span id="results-title"><i class="bi bi-table me-2"></i>Dữ liệu thu thập được</span>
            <span class="badge text-bg-primary rounded-pill" id="count-badge">0</span>
          </div>

          <!-- Bộ chọn chế độ xem dữ liệu (Sản phẩm / Bài viết / Tiêu đề / Tệp tin / Liên kết / Hình ảnh / Raw HTML) -->
          <ul class="nav nav-pills nav-fill small gap-1 flex-wrap" id="data-view-pills" role="tablist">
            <li class="nav-item" role="presentation">
              <button class="nav-link active py-1 px-2.5" id="view-articles-tab" data-view="articles" type="button">
                <i class="bi bi-journal-richtext me-1"></i>Bài viết / Blog (<span id="pill-art-count">0</span>)
              </button>
            </li>
            <li class="nav-item" role="presentation">
              <button class="nav-link py-1 px-2.5" id="view-headings-tab" data-view="headings" type="button">
                <i class="bi bi-type-h1 me-1"></i>Cây Tiêu đề (<span id="pill-head-count">0</span>)
              </button>
            </li>
            <li class="nav-item" role="presentation">
              <button class="nav-link py-1 px-2.5" id="view-files-tab" data-view="files" type="button">
                <i class="bi bi-file-earmark-arrow-down me-1"></i>Tệp tin (<span id="pill-file-count">0</span>)
              </button>
            </li>
            <li class="nav-item" role="presentation">
              <button class="nav-link py-1 px-2.5" id="view-products-tab" data-view="products" type="button">
                <i class="bi bi-basket me-1"></i>Sản phẩm (<span id="pill-prod-count">0</span>)
              </button>
            </li>
            <li class="nav-item" role="presentation">
              <button class="nav-link py-1 px-2.5" id="view-links-tab" data-view="links" type="button">
                <i class="bi bi-link me-1"></i>Liên kết (<span id="pill-link-count">0</span>)
              </button>
            </li>
            <li class="nav-item" role="presentation">
              <button class="nav-link py-1 px-2.5" id="view-images-tab" data-view="images" type="button">
                <i class="bi bi-images me-1"></i>Hình ảnh (<span id="pill-img-count">0</span>)
              </button>
            </li>
            <li class="nav-item" role="presentation">
              <button class="nav-link py-1 px-2.5" id="view-raw-tab" data-view="raw" type="button">
                <i class="bi bi-code-slash me-1"></i>Mã HTML
              </button>
            </li>
          </ul>
        </div>

        <div class="card-body">
          
          <!-- Thanh công cụ xuất dữ liệu & Menu xuất dữ liệu cụ thể -->
          <div class="d-flex flex-wrap gap-2 justify-content-between align-items-center mb-3 pb-2 border-bottom">
            <div class="small text-muted" id="view-hint-text">
              <i class="bi bi-info-circle me-1"></i>Chọn tab để lọc và xem dữ liệu cụ thể tương ứng.
            </div>

            <!-- Các nút Xuất file & Lưu trữ cụ thể -->
            <div class="d-flex gap-2">
              <div class="dropdown">
                <button class="btn btn-outline-primary btn-sm dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false" id="btn-export-dropdown">
                  <i class="bi bi-download me-1"></i>Lưu &amp; Xuất dữ liệu cụ thể
                </button>
                <ul class="dropdown-menu dropdown-menu-end shadow-sm small">
                  <li><h6 class="dropdown-header">Xuất toàn bộ</h6></li>
                  <li><a class="dropdown-item export-action" href="#" data-type="all" data-format="json"><i class="bi bi-filetype-json me-2 text-danger"></i>Toàn bộ dữ liệu (JSON)</a></li>
                  <li><a class="dropdown-item export-action" href="#" data-type="all" data-format="csv"><i class="bi bi-filetype-csv me-2 text-success"></i>Toàn bộ dữ liệu (CSV)</a></li>
                  <li><hr class="dropdown-divider"></li>
                  <li><h6 class="dropdown-header">Xuất dữ liệu cụ thể</h6></li>
                  <li><a class="dropdown-item export-action" href="#" data-type="articles" data-format="csv"><i class="bi bi-journal-text me-2 text-primary"></i>Chỉ Bài viết Blog (CSV)</a></li>
                  <li><a class="dropdown-item export-action" href="#" data-type="headings" data-format="csv"><i class="bi bi-type-h1 me-2 text-warning"></i>Chỉ Cây Tiêu đề H1-H6 (CSV)</a></li>
                  <li><a class="dropdown-item export-action" href="#" data-type="files" data-format="csv"><i class="bi bi-file-earmark-zip me-2 text-info"></i>Chỉ Danh sách Tệp tin (CSV)</a></li>
                  <li><a class="dropdown-item export-action" href="#" data-type="links" data-format="csv"><i class="bi bi-link-45deg me-2 text-secondary"></i>Chỉ Danh sách Liên kết (CSV)</a></li>
                  <li><a class="dropdown-item export-action" href="#" data-type="images" data-format="csv"><i class="bi bi-images me-2 text-danger"></i>Chỉ Danh sách Hình ảnh (CSV)</a></li>
                  <li><a class="dropdown-item export-action" href="#" data-type="products" data-format="csv"><i class="bi bi-basket me-2 text-success"></i>Chỉ Sản phẩm Books (CSV)</a></li>
                </ul>
              </div>
              <button type="button" class="btn btn-outline-secondary btn-sm" id="export-json" title="Xuất định dạng JSON chuẩn">
                <i class="bi bi-filetype-json me-1 text-danger"></i>JSON
              </button>
              <button type="button" class="btn btn-outline-secondary btn-sm" id="export-csv" title="Xuất CSV hỗ trợ tiếng Việt UTF-8">
                <i class="bi bi-filetype-csv me-1 text-success"></i>CSV
              </button>
            </div>
          </div>

          <!-- VIEW 0: BÀI VIẾT BLOG & TIN TỨC (MỚI) -->
          <div id="view-articles-content">
            <div class="d-flex flex-wrap gap-2 align-items-center mb-3">
              <div class="input-group input-group-sm" style="max-width: 280px;">
                <span class="input-group-text bg-light"><i class="bi bi-search"></i></span>
                <input type="search" class="form-control" id="filter-art-input" placeholder="Lọc tiêu đề bài viết hoặc tác giả...">
              </div>
              <button type="button" class="btn btn-outline-secondary btn-sm" id="btn-reset-art-filter" title="Đặt lại bộ lọc bài viết">
                <i class="bi bi-arrow-counterclockwise"></i>
              </button>
            </div>

            <div class="results-table-wrapper table-responsive">
              <table class="table table-hover align-middle table-academic">
                <thead>
                  <tr>
                    <th scope="col" style="width: 45px;">#</th>
                    <th scope="col" style="width: 70px;">Ảnh bìa</th>
                    <th scope="col">Tiêu đề bài viết Blog</th>
                    <th scope="col" style="width: 140px;">Tác giả</th>
                    <th scope="col" style="width: 130px;">Ngày đăng</th>
                    <th scope="col">Tóm tắt trích dẫn</th>
                    <th scope="col" style="width: 65px;" class="text-center">Đọc</th>
                  </tr>
                </thead>
                <tbody id="articles-table-body"></tbody>
              </table>
            </div>
            <div id="empty-articles" class="text-center text-muted py-4 d-none">
              <i class="bi bi-journal-x fs-3 d-block mb-1"></i>
              Không tìm thấy bài viết nào khớp với tiêu chí lọc hoặc website không có cấu trúc bài viết blog.
            </div>
          </div>

          <!-- VIEW 1: CÂY TIÊU ĐỀ HEADINGS H1-H6 (MỚI) -->
          <div id="view-headings-content" class="d-none">
            <div class="d-flex flex-wrap gap-2 align-items-center mb-3">
              <div class="input-group input-group-sm" style="max-width: 260px;">
                <span class="input-group-text bg-light"><i class="bi bi-search"></i></span>
                <input type="search" class="form-control" id="filter-heading-input" placeholder="Lọc từ khóa tiêu đề...">
              </div>
              <select class="form-select form-select-sm" id="filter-heading-level" style="max-width: 150px;">
                <option value="">Tất cả cấp độ</option>
                <option value="h1">Chỉ thẻ H1</option>
                <option value="h2">Chỉ thẻ H2</option>
                <option value="h3">Chỉ thẻ H3</option>
                <option value="h4-h6">Thẻ H4 - H6</option>
              </select>
              <button type="button" class="btn btn-outline-secondary btn-sm" id="btn-reset-head-filter" title="Đặt lại bộ lọc tiêu đề">
                <i class="bi bi-arrow-counterclockwise"></i>
              </button>
            </div>

            <div class="results-table-wrapper table-responsive">
              <table class="table table-hover align-middle table-academic">
                <thead>
                  <tr>
                    <th style="width: 45px;">#</th>
                    <th style="width: 90px;">Cấp độ</th>
                    <th>Nội dung tiêu đề (Heading Text)</th>
                    <th style="width: 150px;">Thuộc tính ID / Anchor</th>
                  </tr>
                </thead>
                <tbody id="headings-table-body"></tbody>
              </table>
            </div>
            <div id="empty-headings" class="text-center text-muted py-4 d-none">
              <i class="bi bi-fonts fs-3 d-block mb-1"></i>
              Không tìm thấy tiêu đề nào phù hợp.
            </div>
          </div>

          <!-- VIEW 2: TỆP TIN & TÀI LIỆU DOWNLOADABLE FILES (MỚI) -->
          <div id="view-files-content" class="d-none">
            <div class="d-flex flex-wrap gap-2 align-items-center mb-3">
              <div class="input-group input-group-sm" style="max-width: 260px;">
                <span class="input-group-text bg-light"><i class="bi bi-search"></i></span>
                <input type="search" class="form-control" id="filter-file-input" placeholder="Lọc tên tệp tài liệu...">
              </div>
              <select class="form-select form-select-sm" id="filter-file-ext" style="max-width: 150px;">
                <option value="">Tất cả định dạng</option>
                <option value="pdf">Tệp PDF (.pdf)</option>
                <option value="doc">Tệp Word (.doc, .docx)</option>
                <option value="xls">Tệp Excel (.xls, .xlsx, .csv)</option>
                <option value="zip">Tệp nén (.zip, .rar, .7z)</option>
                <option value="txt">Tệp văn bản (.txt)</option>
              </select>
              <button type="button" class="btn btn-outline-secondary btn-sm" id="btn-reset-file-filter" title="Đặt lại bộ lọc tệp">
                <i class="bi bi-arrow-counterclockwise"></i>
              </button>
            </div>

            <div class="results-table-wrapper table-responsive">
              <table class="table table-hover align-middle table-academic">
                <thead>
                  <tr>
                    <th style="width: 45px;">#</th>
                    <th style="width: 90px;" class="text-center">Định dạng</th>
                    <th>Tên tệp tin</th>
                    <th>Văn bản liên kết (Anchor text)</th>
                    <th style="width: 110px;" class="text-center">Phạm vi</th>
                    <th style="width: 110px;" class="text-center">Thao tác</th>
                  </tr>
                </thead>
                <tbody id="files-table-body"></tbody>
              </table>
            </div>
            <div id="empty-files" class="text-center text-muted py-4 d-none">
              <i class="bi bi-file-earmark-x fs-3 d-block mb-1"></i>
              Không phát hiện tệp tài liệu (PDF, Word, Excel, ZIP...) nào trên website này.
            </div>
          </div>

          <!-- VIEW 3: BẢNG SẢN PHẨM (BOOKS TO SCRAPE) -->
          <div id="view-products-content" class="d-none">
            <!-- Bộ lọc sản phẩm -->
            <div class="d-flex flex-wrap gap-2 align-items-center flex-grow-1 mb-3" id="filter-controls-area">
              <div class="input-group input-group-sm" style="max-width: 240px;">
                <span class="input-group-text bg-light"><i class="bi bi-search"></i></span>
                <input type="search" class="form-control" id="filter-input" placeholder="Lọc tiêu đề..." aria-label="Lọc theo tiêu đề">
              </div>

              <select class="form-select form-select-sm" id="filter-rating" style="max-width: 150px;">
                <option value="">Tất cả đánh giá</option>
                <option value="5">5 sao (★★★★★)</option>
                <option value="4">4 sao (★★★★☆)</option>
                <option value="3">3 sao (★★★☆☆)</option>
                <option value="2">2 sao (★★☆☆☆)</option>
                <option value="1">1 sao (★☆☆☆☆)</option>
              </select>

              <select class="form-select form-select-sm" id="filter-stock" style="max-width: 150px;">
                <option value="">Tất cả tình trạng</option>
                <option value="in-stock">Còn hàng (In stock)</option>
                <option value="out-of-stock">Hết hàng</option>
              </select>

              <button type="button" class="btn btn-outline-secondary btn-sm" id="btn-reset-filter" title="Xóa tất cả bộ lọc">
                <i class="bi bi-arrow-counterclockwise"></i>
              </button>
            </div>

            <div class="results-table-wrapper table-responsive">
              <table class="table table-hover align-middle table-academic" id="results-table">
                <thead>
                  <tr>
                    <th scope="col" style="width: 45px;">#</th>
                    <th scope="col" style="width: 60px;">Ảnh</th>
                    <th scope="col" class="sortable" data-key="title">
                      Tiêu đề <i class="bi bi-arrow-down-up sort-icon"></i>
                    </th>
                    <th scope="col" class="sortable text-end" data-key="price" style="width: 110px;">
                      Giá <i class="bi bi-arrow-down-up sort-icon"></i>
                    </th>
                    <th scope="col" class="sortable text-center" data-key="rating" style="width: 110px;">
                      Đánh giá <i class="bi bi-arrow-down-up sort-icon"></i>
                    </th>
                    <th scope="col" class="sortable text-center" data-key="availability" style="width: 120px;">
                      Tình trạng <i class="bi bi-arrow-down-up sort-icon"></i>
                    </th>
                    <th scope="col" style="width: 70px;" class="text-center">Nguồn</th>
                  </tr>
                </thead>
                <tbody id="results-body"></tbody>
              </table>
            </div>
            <div id="empty-filter" class="text-center text-muted py-4 d-none">
              <i class="bi bi-inbox fs-3 d-block mb-1"></i>
              Không tìm thấy mục nào khớp với bộ lọc hiện tại.
            </div>
          </div>

          <!-- VIEW 4: DANH SÁCH LIÊN KẾT (LINKS) -->
          <div id="view-links-content" class="d-none">
            <div class="d-flex flex-wrap gap-2 align-items-center mb-3">
              <div class="input-group input-group-sm" style="max-width: 260px;">
                <span class="input-group-text bg-light"><i class="bi bi-search"></i></span>
                <input type="search" class="form-control" id="filter-link-input" placeholder="Lọc từ khóa text hoặc URL...">
              </div>
              <select class="form-select form-select-sm" id="filter-link-type" style="max-width: 170px;">
                <option value="">Tất cả loại liên kết</option>
                <option value="internal">Chỉ liên kết nội bộ</option>
                <option value="external">Chỉ liên kết ngoài</option>
                <option value="file">Chỉ liên kết tệp</option>
              </select>
              <button type="button" class="btn btn-outline-secondary btn-sm" id="btn-reset-link-filter" title="Đặt lại bộ lọc liên kết">
                <i class="bi bi-arrow-counterclockwise"></i>
              </button>
            </div>

            <div class="results-table-wrapper table-responsive">
              <table class="table table-hover align-middle table-academic">
                <thead>
                  <tr>
                    <th style="width: 45px;">#</th>
                    <th style="width: 110px;" class="text-center">Phạm vi</th>
                    <th>Văn bản liên kết (Anchor text)</th>
                    <th>Đường dẫn (URL trích xuất)</th>
                    <th style="width: 70px;" class="text-center">Mở</th>
                  </tr>
                </thead>
                <tbody id="links-table-body"></tbody>
              </table>
            </div>
            <div id="empty-links" class="text-center text-muted py-4 d-none">
              <i class="bi bi-link fs-3 d-block mb-1"></i>
              Không tìm thấy liên kết nào khớp với bộ lọc.
            </div>
          </div>

          <!-- VIEW 5: BỘ SƯU TẬP HÌNH ẢNH (IMAGES) -->
          <div id="view-images-content" class="d-none">
            <div class="d-flex flex-wrap gap-2 align-items-center mb-3">
              <div class="input-group input-group-sm" style="max-width: 260px;">
                <span class="input-group-text bg-light"><i class="bi bi-search"></i></span>
                <input type="search" class="form-control" id="filter-img-input" placeholder="Lọc mô tả alt hoặc URL ảnh...">
              </div>
              <select class="form-select form-select-sm" id="filter-img-ext" style="max-width: 150px;">
                <option value="">Tất cả định dạng</option>
                <option value="jpg">JPG / JPEG</option>
                <option value="png">PNG</option>
                <option value="webp">WEBP</option>
                <option value="svg">SVG</option>
              </select>
              <button type="button" class="btn btn-outline-secondary btn-sm" id="btn-reset-img-filter" title="Đặt lại bộ lọc ảnh">
                <i class="bi bi-arrow-counterclockwise"></i>
              </button>
            </div>

            <div class="image-grid" id="images-grid-body"></div>
            <div id="empty-images" class="text-center text-muted py-4 d-none">
              <i class="bi bi-image fs-3 d-block mb-1"></i>
              Không tìm thấy hình ảnh nào khớp với bộ lọc.
            </div>
          </div>

          <!-- VIEW 6: MÃ HTML THÔ (RAW HTML PREVIEW) -->
          <div id="view-raw-content" class="d-none">
            <div class="d-flex justify-content-between align-items-center mb-2">
              <span class="small text-muted"><i class="bi bi-file-earmark-code me-1"></i>Nội dung HTML thô đã tải về từ server:</span>
              <button type="button" class="btn btn-outline-secondary btn-sm" id="btn-copy-html">
                <i class="bi bi-clipboard me-1"></i>Sao chép HTML
              </button>
            </div>
            <pre class="code-preview" id="raw-html-preview"></pre>
          </div>

          <!-- Cảnh báo nếu có -->
          <div id="warnings" class="mt-3"></div>
        </div>
      </section>

    </div>

    <!-- ==================== TAB 2: ROBOTS.TXT & ETHICS ==================== -->
    <div class="tab-pane fade" id="tab-robots" role="tabpanel" aria-labelledby="robots-tab">
      
      <!-- Công cụ kiểm tra robots.txt trực tiếp -->
      <section class="card card-academic mb-4">
        <div class="card-header">
          <span><i class="bi bi-search me-2"></i>Công cụ kiểm tra Robots.txt trực tuyến (Live Inspector)</span>
          <span class="badge text-bg-light border text-muted small fw-normal">RFC 9309 Compliant</span>
        </div>
        <div class="card-body">
          <p class="small text-muted">
            Nhập bất kỳ URL nào để kiểm tra xem robot crawler có được phép truy cập theo quy tắc <code>robots.txt</code> của website nguồn hay không.
          </p>

          <form id="robots-form" class="row g-3 align-items-end mb-3">
            <div class="col-12 col-md-6 col-lg-7">
              <label for="robots-test-url" class="form-label fw-semibold small">URL hoặc Tên miền cần kiểm tra:</label>
              <input type="url" class="form-control" id="robots-test-url"
                     placeholder="https://books.toscrape.com/catalogue/category/books/travel_2/index.html" required>
            </div>
            <div class="col-8 col-md-4 col-lg-3">
              <label for="robots-user-agent" class="form-label fw-semibold small">User-Agent kiểm thử:</label>
              <input type="text" class="form-control" id="robots-user-agent" value="WebCrawlingStudentProject/1.0">
            </div>
            <div class="col-4 col-md-2 col-lg-2">
              <button type="submit" class="btn btn-primary w-100" id="btn-check-robots">
                <span class="spinner-border spinner-border-sm me-1 d-none" id="robots-spinner"></span>
                <i class="bi bi-search me-1" id="robots-icon"></i>Kiểm tra
              </button>
            </div>
          </form>

          <!-- Ví dụ test nhanh -->
          <div class="small text-muted mb-3">
            Thử nghiệm nhanh:
            <button type="button" class="quick-pill robots-preset" data-url="https://books.toscrape.com/catalogue/category/books_1/index.html">Books to Scrape (404 - Cho phép)</button>
            <button type="button" class="quick-pill robots-preset" data-url="https://vnexpress.net/thoi-su">VnExpress Thời sự (Allow: /)</button>
            <button type="button" class="quick-pill robots-preset" data-url="https://google.com/search">Google Search (Disallow: /search)</button>
          </div>

          <!-- Kết quả kiểm tra Robots.txt -->
          <div id="robots-result-box" class="d-none mt-3 p-3 border rounded bg-light">
            <div class="d-flex align-items-center justify-content-between mb-2">
              <h6 class="mb-0 fw-bold" id="robots-status-title">Kết quả phân tích</h6>
              <span class="badge" id="robots-status-badge">ĐANG XỬ LÝ</span>
            </div>
            <div id="robots-status-desc" class="mb-2 small"></div>
            <div class="small text-muted" id="robots-status-meta"></div>
          </div>
        </div>
      </section>

      <!-- Cẩm nang Đạo đức & Pháp lý (Ethical & Legal Considerations) -->
      <section class="card card-academic">
        <div class="card-header">
          <span><i class="bi bi-shield-shaded me-2"></i>Cẩm nang Đạo đức &amp; Pháp lý trong Web Crawling</span>
          <span class="badge text-bg-light border text-muted small fw-normal">Trọng tâm đánh giá Đồ án</span>
        </div>
        <div class="card-body">
          <div class="row g-3">
            <div class="col-md-6">
              <div class="theory-box h-100">
                <h6><i class="bi bi-file-text me-2 text-primary"></i>1. Tôn trọng Robots.txt (Robots Exclusion Protocol)</h6>
                <p>
                  Tệp <code>robots.txt</code> là tiêu chuẩn đạo đức (RFC 9309) do người quản trị website đặt ra để hướng dẫn bot. Khi có lệnh <code>Disallow</code> cho User-Agent, crawler tuyệt đối không được truy cập.
                </p>
                <ul class="mb-0 ps-3">
                  <li>Không giả mạo User-Agent của công cụ tìm kiếm khác.</li>
                  <li>Nếu file trả về 404/410, mặc định được phép thu thập hợp lý.</li>
                </ul>
              </div>
            </div>

            <div class="col-md-6">
              <div class="theory-box h-100">
                <h6><i class="bi bi-speedometer2 me-2 text-primary"></i>2. Cơ chế Polite Crawler (Tránh quá tải / DoS)</h6>
                <p>
                  Crawler gửi yêu cầu tự động với tốc độ cao có thể làm cạn kiệt tài nguyên máy chủ mục tiêu (tương tự tấn công từ chối dịch vụ vô ý).
                </p>
                <ul class="mb-0 ps-3">
                  <li>Thiết lập độ trễ (delay) ít nhất 1.0 đến 2.0 giây giữa các lượt tải.</li>
                  <li>Thu thập vào các khung giờ thấp điểm (ban đêm).</li>
                  <li>Giới hạn số trang tối đa trong một phiên làm việc.</li>
                </ul>
              </div>
            </div>

            <div class="col-md-6">
              <div class="theory-box h-100">
                <h6><i class="bi bi-journal-check me-2 text-primary"></i>3. Điều khoản Dịch vụ (ToS) &amp; Bản quyền (Copyright)</h6>
                <p>
                  Đạo luật và án lệ quốc tế (hiQ v. LinkedIn, eBay v. Bidder's Edge) chỉ ra: Dữ liệu sự thật công khai có thể thu thập cho nghiên cứu, nhưng nội dung sáng tạo có bản quyền thì không được sao chép thương mại.
                </p>
                <ul class="mb-0 ps-3">
                  <li>Kiểm tra Điều khoản sử dụng (Terms of Service) của trang đích.</li>
                  <li>Chỉ dùng dữ liệu cho mục đích học tập, báo cáo nghiên cứu.</li>
                </ul>
              </div>
            </div>

            <div class="col-md-6">
              <div class="theory-box h-100">
                <h6><i class="bi bi-person-lock me-2 text-primary"></i>4. Bảo vệ Dữ liệu Cá nhân &amp; Quyền Riêng tư (Privacy / PII)</h6>
                <p>
                  Tuyệt đối tuân thủ các quy định bảo vệ dữ liệu cá nhân (GDPR, Nghị định 13/2023/NĐ-CP tại Việt Nam):
                </p>
                <ul class="mb-0 ps-3">
                  <li>Không thu thập dữ liệu nhạy cảm: email, số điện thoại, mật khẩu.</li>
                  <li>Không cố tình vượt qua cơ chế xác thực (CAPTCHA, Login) trái phép.</li>
                </ul>
              </div>
            </div>
          </div>
        </div>
      </section>

    </div>

    <!-- ==================== TAB 3: SAVED DATA & HISTORY ==================== -->
    <div class="tab-pane fade" id="tab-saved" role="tabpanel" aria-labelledby="saved-tab">
      
      <!-- Thông tin CSDL -->
      <section class="card card-academic mb-4">
        <div class="card-header">
          <span><i class="bi bi-hdd-network me-2"></i>Quản lý Cơ sở Dữ liệu Thu thập</span>
          <span class="badge text-bg-light border text-muted small fw-normal">data/crawl_data.json</span>
        </div>
        <div class="card-body">
          <div class="row align-items-center g-3">
            <div class="col-12 col-md-6">
              <div class="d-flex align-items-center gap-3">
                <div class="bg-light p-3 rounded border text-center" style="min-width: 120px;">
                  <div class="h4 mb-0 fw-bold text-primary" id="saved-sessions-count">0</div>
                  <div class="small text-muted">Phiên crawl</div>
                </div>
                <div class="bg-light p-3 rounded border text-center" style="min-width: 120px;">
                  <div class="h4 mb-0 fw-bold text-success" id="saved-total-items">0</div>
                  <div class="small text-muted">Mục đã lưu</div>
                </div>
                <div>
                  <div class="fw-semibold small">Lưu trữ tệp JSON cục bộ</div>
                  <div class="text-muted small">Dữ liệu được ghi kèm khóa độc quyền (<code>LOCK_EX</code>) tránh xung đột đồng thời.</div>
                </div>
              </div>
            </div>

            <div class="col-12 col-md-6 text-md-end">
              <button type="button" class="btn btn-outline-danger btn-sm me-2" id="btn-clear-saved" title="Xóa toàn bộ lịch sử">
                <i class="bi bi-trash3 me-1"></i>Xóa tất cả
              </button>
              <button type="button" class="btn btn-outline-primary btn-sm me-2" id="btn-refresh-saved">
                <i class="bi bi-arrow-clockwise me-1"></i>Tải lại
              </button>
              <button type="button" class="btn btn-primary btn-sm" id="btn-view-saved-items">
                <i class="bi bi-eye me-1"></i>Xem trên bảng
              </button>
            </div>
          </div>
        </div>
      </section>

      <!-- Lịch sử các phiên crawl -->
      <section class="card card-academic">
        <div class="card-header">
          <span><i class="bi bi-clock-history me-2"></i>Lịch sử các phiên Crawl gần đây</span>
        </div>
        <div class="card-body p-0">
          <div class="table-responsive">
            <table class="table table-hover table-academic mb-0">
              <thead>
                <tr>
                  <th style="width: 45px;">#</th>
                  <th style="width: 150px;">Thời gian</th>
                  <th>URL Website nguồn</th>
                  <th>Tiêu đề trang</th>
                  <th style="width: 85px;" class="text-center">Bài viết</th>
                  <th style="width: 75px;" class="text-center">Tệp tin</th>
                  <th style="width: 75px;" class="text-center">Liên kết</th>
                  <th style="width: 75px;" class="text-center">Hình ảnh</th>
                  <th style="width: 75px;" class="text-center">Sản phẩm</th>
                  <th style="width: 80px;" class="text-center">Thao tác</th>
                </tr>
              </thead>
              <tbody id="saved-history-body">
                <tr><td colspan="10" class="text-center py-4 text-muted">Đang tải lịch sử...</td></tr>
              </tbody>
            </table>
          </div>
        </div>
      </section>

    </div>

    <!-- ==================== TAB 4: THEORY & REPORT ==================== -->
    <div class="tab-pane fade" id="tab-theory" role="tabpanel" aria-labelledby="theory-tab">
      
      <div class="row g-4">
        
        <!-- Mục 1: How It Works -->
        <div class="col-lg-6">
          <div class="card card-academic h-100">
            <div class="card-header bg-white">
              <span class="text-primary"><i class="bi bi-gear-wide-connected me-2"></i>1. Nguyên lý Hoạt động (How It Works)</span>
            </div>
            <div class="card-body">
              <div class="theory-box">
                <h6>A. Quy trình 4 bước của Web Crawler</h6>
                <ol class="small ps-3 mb-2">
                  <li><strong>Gửi yêu cầu HTTP:</strong> Sử dụng cURL để gửi HTTP GET đến máy chủ mục tiêu với header định danh <code>User-Agent</code> rõ ràng.</li>
                  <li><strong>Kiểm tra Robots.txt:</strong> Đọc tệp <code>/robots.txt</code>, phân tích luật Disallow/Allow để xác định quyền hợp lệ trước khi gửi crawler.</li>
                  <li><strong>Phân tích cú pháp HTML:</strong> Dùng thư viện <code>DOMDocument</code> kết hợp <code>DOMXPath</code> để duyệt cây DOM, tìm kiếm thẻ theo selector ngữ nghĩa (ví dụ <code>//article[@class='product_pod']</code>).</li>
                  <li><strong>Lọc trùng lặp &amp; Lưu trữ:</strong> Chuẩn hóa đường dẫn tuyệt đối (Absolute URL), loại bỏ trùng lặp và ghi dữ liệu ra tệp JSON/CSDL.</li>
                </ol>
              </div>

              <div class="theory-box mb-0">
                <h6>B. So sánh Web Crawling vs Web Scraping</h6>
                <div class="table-responsive">
                  <table class="table table-sm table-bordered small mb-0">
                    <thead class="table-light">
                      <tr><th>Tiêu chí</th><th>Web Crawling</th><th>Web Scraping</th></tr>
                    </thead>
                    <tbody>
                      <tr>
                        <td><strong>Mục tiêu</strong></td>
                        <td>Khám phá và duyệt qua các liên kết mạng</td>
                        <td>Trích xuất dữ liệu cụ thể từ trang</td>
                      </tr>
                      <tr>
                        <td><strong>Đầu ra</strong></td>
                        <td>Danh sách URL, sitemap, HTML</td>
                        <td>Dữ liệu có cấu trúc (bảng, giá, số)</td>
                      </tr>
                      <tr>
                        <td><strong>Đề tài nhóm</strong></td>
                        <td colspan="2" class="text-center text-primary fw-semibold">Kết hợp cả Crawling (phân trang) và Scraping (trích xuất sản phẩm)</td>
                      </tr>
                    </tbody>
                  </table>
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- Mục 2: Ethics & Legal -->
        <div class="col-lg-6">
          <div class="card card-academic h-100">
            <div class="card-header bg-white">
              <span class="text-primary"><i class="bi bi-balance me-2"></i>2. Đạo đức &amp; Pháp lý (Ethics &amp; Legal)</span>
            </div>
            <div class="card-body">
              <div class="theory-box">
                <h6>A. Khía cạnh Đạo đức (Ethical Implications)</h6>
                <p class="small mb-1">
                  Crawler tiêu tốn băng thông, CPU và bộ nhớ của máy chủ chủ quản. Hành vi crawl ồ ạt không giới hạn có thể dẫn tới sự cố sập hệ thống (Unintentional DoS).
                </p>
                <div class="small text-muted">
                  &rarr; <strong>Giải pháp chuẩn:</strong> Áp dụng cơ chế Rate Limiting, đặt độ trễ &ge; 1s, khai báo User-Agent minh bạch.
                </div>
              </div>

              <div class="theory-box mb-0">
                <h6>B. Khía cạnh Pháp lý &amp; Án lệ Tiêu biểu</h6>
                <ul class="small ps-3 mb-0">
                  <li class="mb-2">
                    <strong>hiQ Labs v. LinkedIn (2019):</strong> Tòa án Mỹ phán quyết việc crawl dữ liệu công khai (publicly available data) mà không cần login không vi phạm đạo luật CFAA.
                  </li>
                  <li class="mb-2">
                    <strong>eBay v. Bidder's Edge (2000):</strong> Tòa án bảo vệ máy chủ của eBay dựa trên nguyên lý "xâm phạm tài sản vô hình" (Trespass to Chattels) khi crawler gửi hàng triệu request gây tắc nghẽn.
                  </li>
                  <li>
                    <strong>Quyền sở hữu trí tuệ:</strong> Thu thập thông tin sự thật (Facts) là hợp pháp, nhưng sao chép nguyên văn tác phẩm sáng tạo, hình ảnh bản quyền để phân phối thương mại là vi phạm.
                  </li>
                </ul>
              </div>
            </div>
          </div>
        </div>

        <!-- Mục 3: Use Cases -->
        <div class="col-12">
          <div class="card card-academic">
            <div class="card-header bg-white">
              <span class="text-primary"><i class="bi bi-briefcase me-2"></i>3. Các Ứng dụng Thực tế (Use Cases)</span>
            </div>
            <div class="card-body">
              <div class="row g-3">
                <div class="col-md-4">
                  <div class="theory-box h-100">
                    <h6><i class="bi bi-newspaper me-2 text-primary"></i>Content Aggregation (Tổng hợp nội dung)</h6>
                    <p class="small mb-0">
                      Gom dữ liệu tin tức từ nhiều báo điện tử, so sánh giá sản phẩm thương mại điện tử (Tiki, Shopee, Amazon), tổng hợp giá vé máy bay hoặc phòng khách sạn theo thời gian thực.
                    </p>
                  </div>
                </div>
                <div class="col-md-4">
                  <div class="theory-box h-100">
                    <h6><i class="bi bi-graph-up-arrow me-2 text-primary"></i>Search Engines &amp; SEO Audit</h6>
                    <p class="small mb-0">
                      Động cơ tìm kiếm (Googlebot, Bingbot) thu thập chỉ mục toàn bộ web; các chuyên gia SEO sử dụng crawler để kiểm tra liên kết hỏng (broken links), cấu trúc thẻ H1-H6, sitemap.
                    </p>
                  </div>
                </div>
                <div class="col-md-4">
                  <div class="theory-box h-100">
                    <h6><i class="bi bi-cpu me-2 text-primary"></i>Nghiên cứu Học thuật &amp; AI (Research)</h6>
                    <p class="small mb-0">
                      Xây dựng tập dữ liệu (dataset) phục vụ huấn luyện mô hình ngôn ngữ lớn (LLM), phân tích cảm xúc dư luận (Sentiment Analysis) và nghiên cứu xu hướng thị trường xã hội học.
                    </p>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>

      </div>

    </div>

  </div>
</div>

<!-- Chân trang học thuật -->
<footer class="academic-footer text-center">
  <div class="container">
    <div><strong>Đề tài Môn học: Web Crawling &amp; Data Extraction</strong> &middot; Nhóm Sinh viên Năm 3</div>
    <div class="text-muted mt-1">Dùng cho mục đích học tập và nghiên cứu công nghệ &middot; 2026</div>
  </div>
</footer>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="assets/js/main.js"></script>
</body>
</html>
