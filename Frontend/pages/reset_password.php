<?php
/** Đặt lại mật khẩu bằng token trong liên kết (token chỉ dùng 1 lần, hết hạn sau 15 phút). */
require_once __DIR__ . '/../../backend/auth.php';
header('Referrer-Policy: no-referrer');          // không để lộ token qua header Referer
header('Cache-Control: no-store');

$token = (string) ($_GET['token'] ?? $_POST['token'] ?? '');
$valid = authResetTokenUser($token) !== null;
$error = '';

if ($valid && ($_SERVER['REQUEST_METHOD'] ?? '') === 'POST') {
    if (!csrfIsValid()) {
        $error = 'Phiên làm việc không hợp lệ. Vui lòng thử lại.';
    } else {
        $res = authResetPassword($token, (string) ($_POST['password'] ?? ''), (string) ($_POST['confirm'] ?? ''));
        if ($res['ok']) {
            header('Location: login.php?reset=1');
            exit;
        }
        $error = $res['message'];
        if (!empty($res['fatal'])) {
            $valid = false;
        }
    }
}
$csrf = csrfToken();

$pageTitle = 'Đặt lại mật khẩu';
$heading   = 'Đặt lại mật khẩu';
$subtitle  = $valid ? 'Nhập mật khẩu mới cho tài khoản của bạn' : 'Không thể tiếp tục';
$icon      = 'bi-shield-lock-fill';
require __DIR__ . '/../partials/auth_top.php';
?>
    <?php if (!$valid): ?>
      <div class="alert alert-danger"><i class="bi bi-x-octagon-fill me-2"></i><?= htmlspecialchars($error ?: 'Liên kết không hợp lệ hoặc đã hết hạn. Hãy yêu cầu liên kết mới.', ENT_QUOTES) ?></div>
      <a href="forgot_password.php" class="btn btn-primary w-100">Yêu cầu liên kết mới</a>
    <?php else: ?>
      <?php if ($error !== ''): ?><div class="alert alert-danger"><i class="bi bi-exclamation-triangle-fill me-2"></i><?= htmlspecialchars($error, ENT_QUOTES) ?></div><?php endif; ?>
      <form method="post" action="reset_password.php" novalidate>
        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf, ENT_QUOTES) ?>">
        <input type="hidden" name="token" value="<?= htmlspecialchars($token, ENT_QUOTES) ?>">
        <div class="mb-3">
          <label for="password" class="form-label">Mật khẩu mới</label>
          <div class="input-group"><span class="input-group-text"><i class="bi bi-lock"></i></span>
            <input type="password" class="form-control" id="password" name="password" required autofocus autocomplete="new-password"></div>
          <div class="form-text">Ít nhất 8 ký tự, gồm cả chữ và số.</div>
        </div>
        <div class="mb-4">
          <label for="confirm" class="form-label">Nhập lại mật khẩu mới</label>
          <div class="input-group"><span class="input-group-text"><i class="bi bi-lock-fill"></i></span>
            <input type="password" class="form-control" id="confirm" name="confirm" required autocomplete="new-password"></div>
        </div>
        <button type="submit" class="btn btn-primary w-100 btn-lg"><i class="bi bi-check2-circle me-2"></i> Đặt lại mật khẩu</button>
      </form>
    <?php endif; ?>
    <div class="auth-links justify-content-center"><a href="login.php"><i class="bi bi-arrow-left"></i> Quay lại đăng nhập</a></div>
<?php require __DIR__ . '/../partials/auth_bottom.php'; ?>
