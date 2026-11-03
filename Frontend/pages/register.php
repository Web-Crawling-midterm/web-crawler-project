<?php
/** Trang đăng ký tài khoản mới (vai trò mặc định: user). */
require_once __DIR__ . '/../../backend/auth.php';

if (authUser() !== null) {
    header('Location: index.php');
    exit;
}

$error = '';
$f = ['username' => '', 'email' => ''];

if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST') {
    $f['username'] = trim((string) ($_POST['username'] ?? ''));
    $f['email']    = trim((string) ($_POST['email'] ?? ''));

    if (!csrfIsValid()) {
        $error = 'Phiên làm việc không hợp lệ. Vui lòng thử lại.';
    } else {
        $res = authRegister($f['username'], $f['email'], (string) ($_POST['password'] ?? ''), (string) ($_POST['confirm'] ?? ''));
        if ($res['ok']) {
            header('Location: login.php?registered=1');
            exit;
        }
        $error = $res['message'];
    }
}
$csrf = csrfToken();

$pageTitle = 'Đăng ký';
$heading   = 'Tạo tài khoản';
$subtitle  = 'Đăng ký để bắt đầu thu thập dữ liệu';
$icon      = 'bi-person-plus-fill';
require __DIR__ . '/../partials/auth_top.php';
?>
    <?php if ($error !== ''): ?><div class="alert alert-danger"><i class="bi bi-exclamation-triangle-fill me-2"></i><?= htmlspecialchars($error, ENT_QUOTES) ?></div><?php endif; ?>

    <form method="post" action="register.php" novalidate>
      <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf, ENT_QUOTES) ?>">
      <div class="mb-3">
        <label for="username" class="form-label">Tên đăng nhập</label>
        <div class="input-group"><span class="input-group-text"><i class="bi bi-person"></i></span>
          <input type="text" class="form-control" id="username" name="username" required autofocus maxlength="32"
                 autocomplete="username" value="<?= htmlspecialchars($f['username'], ENT_QUOTES) ?>"></div>
        <div class="form-text">3–32 ký tự: chữ, số, dấu chấm, gạch dưới, gạch ngang.</div>
      </div>
      <div class="mb-3">
        <label for="email" class="form-label">Email</label>
        <div class="input-group"><span class="input-group-text"><i class="bi bi-envelope"></i></span>
          <input type="email" class="form-control" id="email" name="email" required maxlength="100"
                 autocomplete="email" value="<?= htmlspecialchars($f['email'], ENT_QUOTES) ?>"></div>
        <div class="form-text">Dùng để khôi phục mật khẩu khi bạn quên.</div>
      </div>
      <div class="mb-3">
        <label for="password" class="form-label">Mật khẩu</label>
        <div class="input-group"><span class="input-group-text"><i class="bi bi-lock"></i></span>
          <input type="password" class="form-control" id="password" name="password" required autocomplete="new-password"></div>
        <div class="pw-meter"><div id="pw-bar"></div></div>
        <div class="form-text" id="pw-text">Ít nhất 8 ký tự, gồm cả chữ và số.</div>
      </div>
      <div class="mb-4">
        <label for="confirm" class="form-label">Nhập lại mật khẩu</label>
        <div class="input-group"><span class="input-group-text"><i class="bi bi-lock-fill"></i></span>
          <input type="password" class="form-control" id="confirm" name="confirm" required autocomplete="new-password"></div>
      </div>
      <button type="submit" class="btn btn-primary w-100 btn-lg"><i class="bi bi-person-check me-2"></i> Đăng ký</button>
    </form>

    <div class="auth-links justify-content-center"><span class="text-muted">Đã có tài khoản?</span> <a href="login.php">Đăng nhập</a></div>

    <script>
      // Thanh đo độ mạnh mật khẩu (chỉ mang tính gợi ý; máy chủ mới là nơi kiểm tra thật)
      (function () {
        var pw = document.getElementById('password'), bar = document.getElementById('pw-bar'), txt = document.getElementById('pw-text');
        pw.addEventListener('input', function () {
          var v = pw.value, s = 0;
          if (v.length >= 8) s++;
          if (/[A-Za-z]/.test(v) && /\d/.test(v)) s++;
          if (v.length >= 12) s++;
          if (/[^A-Za-z0-9]/.test(v)) s++;
          var cols = ['#e03131', '#f59f00', '#74b816', '#2f9e44'], names = ['Yếu', 'Trung bình', 'Khá', 'Mạnh'];
          bar.style.width = (v ? Math.max(1, s) * 25 : 0) + '%';
          bar.style.background = cols[Math.max(0, s - 1)];
          txt.textContent = v ? 'Độ mạnh: ' + names[Math.max(0, s - 1)] : 'Ít nhất 8 ký tự, gồm cả chữ và số.';
        });
      })();
    </script>
<?php require __DIR__ . '/../partials/auth_bottom.php'; ?>
