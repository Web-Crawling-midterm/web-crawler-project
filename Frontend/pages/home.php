<?php
/**
 * Trang chủ giới thiệu hệ thống (công khai, không cần đăng nhập).
 * Góc trên bên phải có nút Đăng nhập; đăng nhập xong sẽ vào giao diện crawler (index.php).
 */
require_once __DIR__ . '/../../backend/auth.php';
$user = authUser();
?>
<!DOCTYPE html>
<html lang="vi">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>CrawlWise | Thu thập dữ liệu web có kiểm soát</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&family=JetBrains+Mono:wght@500&display=swap" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
  <link href="../assets/css/home.css" rel="stylesheet">
</head>
<body>



<!-- Menu -->
<header class="site-header">
  <div class="wrap header-row">
    <a class="brand" href="home.php"><span class="brand-mark"><i class="bi bi-diagram-3-fill"></i></span><span>Crawl<b>Wise</b></span></a>

    <nav class="main-nav" id="main-nav">
      <a href="#gioi-thieu">Giới thiệu</a>
      <a href="#tinh-nang">Tính năng</a>
      <a href="#quy-trinh">Quy trình</a>
      <a href="#demo">Xem trước</a>
      <a href="#dao-duc">Đạo đức</a>
    </nav>

    <div class="header-actions">
      <?php if ($user): ?>
        <span class="hello d-none-sm"><i class="bi bi-person-circle"></i> <?= htmlspecialchars($user['username'], ENT_QUOTES) ?></span>
        <a class="btn-login" href="index.php"><i class="bi bi-box-arrow-in-right"></i><span>Vào hệ thống</span></a>
      <?php else: ?>
        <a class="link-register d-none-sm" href="register.php">Đăng ký</a>
        <a class="btn-login" href="login.php" title="Đăng nhập"><i class="bi bi-person"></i><span>Đăng nhập</span></a>
      <?php endif; ?>
      <button class="burger" id="burger" aria-label="Mở menu"><i class="bi bi-list"></i></button>
    </div>
  </div>
</header>

<main>
  <!-- Hero -->
  <section class="hero wrap" id="gioi-thieu">
    <div class="hero-text">
      <span class="chip"><i class="bi bi-stars"></i> Tuân thủ robots.txt · Chống SSRF · Phân quyền</span>
      <h1>Thu thập dữ liệu web <span class="grad">nhanh gọn</span> và có kiểm soát</h1>
      <p class="lead">Nhập một địa chỉ website, hệ thống kiểm tra quyền truy cập, tải trang, bóc tách tiêu đề, liên kết, hình ảnh, bài viết, sản phẩm rồi lưu lại để bạn xem, lọc và xuất ra CSV.</p>
      <div class="hero-cta">
        <a class="btn-main" href="<?= $user ? 'index.php' : 'login.php' ?>"><?= $user ? 'Vào hệ thống' : 'Đăng nhập để bắt đầu' ?> <i class="bi bi-arrow-right"></i></a>
        <a class="btn-ghost" href="#quy-trinh"><i class="bi bi-play-circle"></i> Xem cách hoạt động</a>
      </div>
      <ul class="hero-points">
        <li><i class="bi bi-check2-circle"></i> Không cần cài thêm thư viện</li>
        <li><i class="bi bi-check2-circle"></i> Dữ liệu riêng cho từng tài khoản</li>
      </ul>
    </div>

    <!-- Khung minh họa giao diện ứng dụng -->
    <div class="hero-visual" aria-hidden="true">
      <div class="float-badge fb-1"><i class="bi bi-shield-check"></i> robots.txt: được phép</div>
      <div class="float-badge fb-2"><i class="bi bi-lightning-charge-fill"></i> 48 bài viết</div>
      <div class="window">
        <div class="win-bar"><i></i><i></i><i></i><span>crawlwise.local</span></div>
        <div class="win-body">
          <div class="mock-input"><i class="bi bi-link-45deg"></i> https://books.toscrape.com/ <b>Crawl</b></div>
          <div class="mock-progress"><div></div></div>
          <div class="mock-row head"><span>Tiêu đề</span><span>Giá</span></div>
          <div class="mock-row"><span>A Light in the Attic</span><span>£51.77</span></div>
          <div class="mock-row"><span>Tipping the Velvet</span><span>£53.74</span></div>
          <div class="mock-row"><span>Soumission</span><span>£50.10</span></div>
          <div class="mock-row"><span>Sharp Objects</span><span>£47.82</span></div>
        </div>
      </div>
    </div>
  </section>

  <!-- Thống kê -->
  <section class="wrap stats reveal">
    <div><b>6</b><span>loại dữ liệu trích xuất</span></div>
    <div><b>2</b><span>vai trò người dùng</span></div>
    <div><b>100%</b><span>kiểm tra robots.txt</span></div>
    <div><b>CSV</b><span>xuất một chạm</span></div>
  </section>

  <!-- Tính năng -->
  <section class="wrap section" id="tinh-nang">
    <p class="kicker">Tính năng</p>
    <h2 class="h-title">Mọi thứ bạn cần cho một crawler hoàn chỉnh</h2>
    <div class="bento">
      <article class="feat feat-lg reveal"><span class="ico"><i class="bi bi-diagram-3"></i></span><h3>Phân trang tự động</h3><p>Theo liên kết “next” để thu thập nhiều trang trong một lần chạy, có giới hạn số trang để không gây quá tải cho website nguồn.</p><div class="mini-bars"><i></i><i></i><i></i><i></i><i></i></div></article>
      <article class="feat reveal"><span class="ico"><i class="bi bi-funnel"></i></span><h3>Bộ lọc trích xuất</h3><p>Chọn loại dữ liệu cần lấy hoặc tự viết XPath.</p></article>
      <article class="feat reveal"><span class="ico"><i class="bi bi-shield-lock"></i></span><h3>Chống SSRF</h3><p>Chặn IP nội bộ, chỉ nhận HTTP/HTTPS.</p></article>
      <article class="feat reveal"><span class="ico"><i class="bi bi-people"></i></span><h3>Phân quyền</h3><p>Admin xem tất cả; người dùng chỉ thấy dữ liệu của mình.</p></article>
      <article class="feat reveal"><span class="ico"><i class="bi bi-database"></i></span><h3>Lưu và xem lại</h3><p>Lịch sử crawl được lưu, xem bảng, sắp xếp, lọc.</p></article>
      <article class="feat feat-wide reveal"><span class="ico"><i class="bi bi-filetype-csv"></i></span><div><h3>Xuất CSV</h3><p>Tải kết quả về để mở bằng Excel hoặc dùng cho phân tích tiếp theo.</p></div></article>
    </div>
  </section>

  <!-- Quy trình -->
  <section class="section alt" id="quy-trinh">
    <div class="wrap">
      <p class="kicker">Quy trình</p>
      <h2 class="h-title">Từ một địa chỉ URL đến bảng dữ liệu</h2>
      <ol class="steps reveal">
        <li><span>1</span><b>Nhập URL</b><small>Kiểm tra hợp lệ</small></li>
        <li><span>2</span><b>Kiểm tra robots.txt</b><small>Được phép hay không</small></li>
        <li><span>3</span><b>Tải trang</b><small>cURL, giới hạn thời gian</small></li>
        <li><span>4</span><b>Phân tích HTML</b><small>DOM và XPath</small></li>
        <li><span>5</span><b>Lưu trữ</b><small>Gắn với chủ sở hữu</small></li>
        <li><span>6</span><b>Hiển thị</b><small>Bảng, lọc, CSV</small></li>
      </ol>
    </div>
  </section>

  <!-- Xem trước kết quả -->
  <section class="wrap section" id="demo">
    <p class="kicker">Xem trước</p>
    <h2 class="h-title">Kết quả trông như thế nào</h2>
    <p class="muted">Dữ liệu minh họa từ trang thử nghiệm books.toscrape.com. Đăng nhập để thu thập dữ liệu thật.</p>
    <div class="table-wrap reveal">
      <table class="demo-table">
        <thead><tr><th>#</th><th>Tiêu đề</th><th>Giá</th><th>Đánh giá</th><th>Tình trạng</th></tr></thead>
        <tbody>
          <tr><td>1</td><td>A Light in the Attic</td><td>£51.77</td><td class="stars">★★★☆☆</td><td><span class="pill">Còn hàng</span></td></tr>
          <tr><td>2</td><td>Tipping the Velvet</td><td>£53.74</td><td class="stars">★☆☆☆☆</td><td><span class="pill">Còn hàng</span></td></tr>
          <tr><td>3</td><td>Soumission</td><td>£50.10</td><td class="stars">★☆☆☆☆</td><td><span class="pill">Còn hàng</span></td></tr>
          <tr><td>4</td><td>Sharp Objects</td><td>£47.82</td><td class="stars">★★★★☆</td><td><span class="pill">Còn hàng</span></td></tr>
        </tbody>
      </table>
    </div>
  </section>

  <!-- Đạo đức -->
  <section class="section alt" id="dao-duc">
    <div class="wrap two-col">
      <div>
        <p class="kicker">Đạo đức và pháp lý</p>
        <h2 class="h-title">Crawl có trách nhiệm</h2>
        <p class="muted">Hệ thống được thiết kế để thu thập dữ liệu một cách lịch sự, không gây quá tải cho website nguồn.</p>
      </div>
      <ul class="checks reveal">
        <li><i class="bi bi-check-circle-fill"></i> Đọc và tuân thủ <code>robots.txt</code> trước khi tải trang</li>
        <li><i class="bi bi-check-circle-fill"></i> Khai báo User-Agent rõ ràng, có giới hạn thời gian chờ</li>
        <li><i class="bi bi-check-circle-fill"></i> Giới hạn số trang mỗi lần để tránh gây tải</li>
        <li><i class="bi bi-check-circle-fill"></i> Chỉ dùng dữ liệu công khai, tôn trọng điều khoản của website</li>
      </ul>
    </div>
  </section>

  <!-- Kêu gọi -->
  <section class="wrap"><div class="cta reveal">
    <h2 class="h-title">Sẵn sàng thu thập dữ liệu?</h2>
    <p>Đăng nhập để bắt đầu và quản lý lịch sử thu thập của riêng bạn.</p>
    <a class="btn-main" href="<?= $user ? 'index.php' : 'login.php' ?>"><?= $user ? 'Vào hệ thống' : 'Đăng nhập' ?> <i class="bi bi-arrow-right"></i></a>
  </div></section>
</main>

<footer class="site-footer">
  <div class="wrap">© <?= date('Y') ?> CrawlWise · Đồ án môn Lập trình Web và Ứng dụng (503073) · Chỉ phục vụ mục đích học tập</div>
</footer>

<script>
  // Mở/đóng menu trên điện thoại
  document.getElementById('burger').addEventListener('click', function () {
    document.getElementById('main-nav').classList.toggle('open');
  });

  // Hiệu ứng hiện dần khi cuộn; bóng đổ cho header
  document.documentElement.classList.add('js');
  (function () {
    var els = document.querySelectorAll('.reveal');
    if (!('IntersectionObserver' in window)) { els.forEach(function (e) { e.classList.add('in'); }); return; }
    var io = new IntersectionObserver(function (en) {
      en.forEach(function (x) { if (x.isIntersecting) { x.target.classList.add('in'); io.unobserve(x.target); } });
    }, { threshold: 0.12 });
    els.forEach(function (e) { io.observe(e); });
    var h = document.querySelector('.site-header');
    addEventListener('scroll', function () { h.classList.toggle('scrolled', scrollY > 8); }, { passive: true });
  })();
</script>
</body>
</html>
