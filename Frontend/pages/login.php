<?php
/** Trang đăng nhập: kiểm tra CSRF, giới hạn số lần thử, tạo session. Có thể dùng tên đăng nhập hoặc email. */
require_once __DIR__ . '/../../backend/auth.php';

if (authUser() !== null) {
    header('Location: index.php');
    exit;
}

$error = '';
$info  = isset($_GET['registered']) ? 'Đăng ký thành công. Hãy đăng nhập.' : (isset($_GET['reset']) ? 'Đã đặt lại mật khẩu. Hãy đăng nhập.' : '');
$username = '';

if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST') {
    $username = trim((string) ($_POST['username'] ?? ''));
    if (!csrfIsValid()) {
        $error = 'Phiên làm việc không hợp lệ. Vui lòng thử lại.';
    } else {
        $res = authLogin($username, (string) ($_POST['password'] ?? ''));
        if ($res['ok']) {
            header('Location: index.php');
            exit;
        }
        $error = $res['message'];
    }
}
$csrf = csrfToken();

$pageTitle = 'Đăng nhập';
$heading   = 'Hệ Thống Web Crawler';
$subtitle  = 'Đăng nhập để tiếp tục';
$icon      = 'bi-diagram-3-fill';
require __DIR__ . '/../partials/auth_top.php';
?>
    <?php if ($info !== ''): ?><div class="alert alert-success"><i class="bi bi-check-circle-fill me-2"></i><?= htmlspecialchars($info, ENT_QUOTES) ?></div><?php endif; ?>
    <?php if ($error !== ''): ?><div class="alert alert-danger"><i class="bi bi-exclamation-triangle-fill me-2"></i><?= htmlspecialchars($error, ENT_QUOTES) ?></div><?php endif; ?>

    <form method="post" action="login.php" novalidate>
      <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf, ENT_QUOTES) ?>">
      <div class="mb-3">
        <label for="username" class="form-label">Tên đăng nhập hoặc email</label>
        <div class="input-group">
          <span class="input-group-text"><i class="bi bi-person"></i></span>
          <input type="text" class="form-control" id="username" name="username" required autofocus maxlength="100"
                 autocomplete="username" value="<?= htmlspecialchars($username, ENT_QUOTES) ?>">
        </div>
      </div>
      <div class="mb-2">
        <label for="password" class="form-label">Mật khẩu</label>
        <div class="input-group">
          <span class="input-group-text"><i class="bi bi-lock"></i></span>
          <input type="password" class="form-control" id="password" name="password" required autocomplete="current-password">
        </div>
      </div>
      <div class="text-end mb-3"><a href="forgot_password.php" class="small">Quên mật khẩu?</a></div>
      <button type="submit" class="btn btn-primary w-100 btn-lg"><i class="bi bi-box-arrow-in-right me-2"></i> Đăng nhập</button>
    </form>

    <div class="auth-links justify-content-center"><span class="text-muted">Chưa có tài khoản?</span> <a href="register.php">Đăng ký ngay</a></div>

    <div class="auth-hint">
      <strong>Tài khoản demo:</strong><br>
      <code>admin</code> / <code>Admin@123</code> (quản trị viên)<br>
      <code>user</code> / <code>User@123</code> (người dùng)
    </div>
<?php require __DIR__ . '/../partials/auth_bottom.php'; ?>
