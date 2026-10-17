<!DOCTYPE html>
<html lang="vi">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Web Data Crawler</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
  <link href="static/css/style.css" rel="stylesheet">
</head>
<body>

<nav class="navbar navbar-dark app-navbar">
  <div class="container">
    <span class="navbar-brand mb-0 h1"><i class="bi bi-diagram-3-fill me-2"></i>Web Data Crawler</span>
    <span class="navbar-text small d-none d-sm-inline">Thu thập dữ liệu web &middot; PHP + DOMDocument</span>
  </div>
</nav>

<main class="container py-4">

  <!-- Form nhập URL -->
  <section class="card shadow-sm mb-4" aria-labelledby="form-title">
    <div class="card-body">
      <h2 id="form-title" class="h5 mb-3">Thu thập dữ liệu từ một URL</h2>
      <form id="crawl-form" novalidate>
        <div class="row g-3 align-items-start">
          <div class="col-12 col-lg-7">
            <label for="url-input" class="form-label fw-semibold">URL website nguồn</label>
            <input type="url" class="form-control form-control-lg" id="url-input"
                   placeholder="https://books.toscrape.com/" autocomplete="off" required>
            <div class="invalid-feedback" id="url-feedback">Vui lòng nhập URL hợp lệ bắt đầu bằng http:// hoặc https://</div>
          </div>
          <div class="col-6 col-lg-2">
            <label for="max-pages" class="form-label fw-semibold">Số trang</label>
            <input type="number" class="form-control form-control-lg" id="max-pages" min="1" max="50" value="1">
            <div class="invalid-feedback">Từ 1 đến 50.</div>
          </div>
          <div class="col-6 col-lg-3">
            <label class="form-label d-none d-lg-block">&nbsp;</label>
            <button type="submit" class="btn btn-primary btn-lg w-100" id="crawl-btn">
              <span class="spinner-border spinner-border-sm me-2 d-none" id="btn-spinner" role="status" aria-hidden="true"></span>
              <i class="bi bi-cloud-download me-1" id="btn-icon"></i>
              <span id="btn-text">Thu thập</span>
            </button>
          </div>
        </div>
        <div class="mt-3 small text-muted">
          Ví dụ nhanh:
          <button type="button" class="btn btn-link btn-sm p-0 align-baseline example-url" data-url="https://books.toscrape.com/">Books to Scrape</button>
          &middot;
          <button type="button" class="btn btn-link btn-sm p-0 align-baseline example-url" data-url="https://books.toscrape.com/catalogue/category/books/travel_2/index.html">Danh mục Travel</button>
        </div>
        <div class="form-text mt-2">
          <i class="bi bi-shield-check"></i>
          Chỉ thu thập từ website cho phép (kiểm tra robots.txt và điều khoản). Nguồn thử nghiệm: books.toscrape.com. Bản hiện tại thu thập một trang mỗi lần.
        </div>
      </form>
    </div>
  </section>

  <!-- Trạng thái -->
  <div id="status-area" aria-live="polite" class="mb-4"></div>

  <!-- Tóm tắt -->
  <section id="summary" class="row g-3 mb-4 d-none" aria-label="Tóm tắt kết quả">
    <div class="col-6 col-md-3"><div class="stat-card"><div class="stat-value" id="stat-items">0</div><div class="stat-label">Mục thu thập</div></div></div>
    <div class="col-6 col-md-3"><div class="stat-card"><div class="stat-value" id="stat-pages">0</div><div class="stat-label">Trang đã duyệt</div></div></div>
    <div class="col-6 col-md-3"><div class="stat-card"><div class="stat-value" id="stat-dups">0</div><div class="stat-label">URL trùng đã loại</div></div></div>
    <div class="col-6 col-md-3"><div class="stat-card"><div class="stat-value" id="stat-time">0</div><div class="stat-label">Thời gian (giây)</div></div></div>
  </section>

  <!-- Bảng kết quả -->
  <section id="results-card" class="card shadow-sm d-none" aria-labelledby="results-title">
    <div class="card-body">
      <div class="d-flex flex-wrap gap-2 justify-content-between align-items-center mb-3">
        <h2 id="results-title" class="h5 mb-0">Kết quả thu thập <span class="badge text-bg-secondary" id="count-badge">0</span></h2>
        <div class="d-flex flex-wrap gap-2">
          <div class="input-group input-group-sm search-box">
            <span class="input-group-text"><i class="bi bi-search"></i></span>
            <input type="search" class="form-control" id="filter-input" placeholder="Lọc theo tiêu đề..." aria-label="Lọc theo tiêu đề">
          </div>
          <button type="button" class="btn btn-outline-secondary btn-sm" id="export-json"><i class="bi bi-filetype-json me-1"></i>JSON</button>
          <button type="button" class="btn btn-outline-secondary btn-sm" id="export-csv"><i class="bi bi-filetype-csv me-1"></i>CSV</button>
        </div>
      </div>
      <div class="table-responsive">
        <table class="table table-hover align-middle" id="results-table">
          <thead class="table-light">
            <tr>
              <th scope="col" class="col-index">#</th>
              <th scope="col">Ảnh</th>
              <th scope="col" class="sortable" data-key="title">Tiêu đề <i class="bi bi-arrow-down-up sort-icon"></i></th>
              <th scope="col" class="sortable text-end" data-key="price">Giá <i class="bi bi-arrow-down-up sort-icon"></i></th>
              <th scope="col" class="sortable" data-key="rating">Đánh giá <i class="bi bi-arrow-down-up sort-icon"></i></th>
              <th scope="col" class="sortable" data-key="availability">Tình trạng <i class="bi bi-arrow-down-up sort-icon"></i></th>
              <th scope="col">URL</th>
            </tr>
          </thead>
          <tbody id="results-body"></tbody>
        </table>
      </div>
      <div id="empty-filter" class="text-center text-muted py-4 d-none">Không có mục nào khớp bộ lọc.</div>
      <div id="warnings" class="mt-3"></div>
    </div>
  </section>

  <!-- Lịch sử đã lưu (tuần 2, tự ẩn nếu backend chưa có) -->
  <section id="saved-card" class="card shadow-sm mt-4 d-none">
    <div class="card-body d-flex flex-wrap justify-content-between align-items-center gap-2">
      <div><strong>Dữ liệu đã lưu trong cơ sở dữ liệu:</strong> <span id="saved-count">0</span> mục</div>
      <button type="button" class="btn btn-outline-primary btn-sm" id="load-saved"><i class="bi bi-database me-1"></i>Xem dữ liệu đã lưu</button>
    </div>
  </section>

</main>

<footer class="text-center text-muted small py-4">
  Đề tài Web Crawling &middot; Dùng cho mục đích học tập
</footer>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="static/js/main.js"></script>
</body>
</html>
