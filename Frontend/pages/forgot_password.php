<?php
/** Quên mật khẩu: nhập email để nhận liên kết đặt lại (có hiệu lực 15 phút). */
require_once __DIR__ . '/../../backend/auth.php';

$error = '';
$info  = '';
$link  = null;

if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST') {
    if (!csrfIsValid()) {
        $error = 'Phiên làm việc không hợp lệ. Vui lòng thử lại.';
    } else {
        $res = authRequestPasswordReset((string) ($_POST['email'] ?? ''));
        if ($res['ok']) {
            $info = $res['message'];
            $link = $res['link'];
        } else {
            $error = $res['message'];
        }
    }
}
$csrf = csrfToken();

$pageTitle = 'Quên mật khẩu';
$heading   = 'Quên mật khẩu?';
$subtitle  = 'Nhập email đã đăng ký, chúng tôi sẽ gửi liên kết đặt lại';
$icon      = 'bi-key-fill';
require __DIR__ . '/../partials/auth_top.php';
?>
    <?php if ($error !== ''): ?><div class="alert alert-danger"><i class="bi bi-exclamation-triangle-fill me-2"></i><?= htmlspecialchars($error, ENT_QUOTES) ?></div><?php endif; ?>
    <?php if ($info !== ''): ?>
      <div class="alert alert-success"><i class="bi bi-send-check-fill me-2"></i><?= htmlspecialchars($info, ENT_QUOTES) ?></div>
      <?php if ($link): ?>
        <div class="alert alert-warning small">
          <strong>Chế độ demo:</strong> máy chủ chưa cấu hình gửi email nên liên kết được hiển thị tại đây
          (hiệu lực 15 phút, chỉ dùng được 1 lần):
          <div class="reset-link-box"><a href="<?= htmlspecialchars($link, ENT_QUOTES) ?>"><?= htmlspecialchars($link, ENT_QUOTES) ?></a></div>
        </div>
      <?php endif; ?>
    <?php endif; ?>

    <form method="post" action="forgot_password.php" novalidate>
      <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf, ENT_QUOTES) ?>">
      <div class="mb-4">
        <label for="email" class="form-label">Email</label>
        <div class="input-group"><span class="input-group-text"><i class="bi bi-envelope"></i></span>
          <input type="email" class="form-control" id="email" name="email" required autofocus maxlength="100" autocomplete="email"></div>
      </div>
      <button type="submit" class="btn btn-primary w-100 btn-lg"><i class="bi bi-send me-2"></i> Gửi liên kết đặt lại</button>
    </form>

    <div class="auth-links justify-content-center"><a href="login.php"><i class="bi bi-arrow-left"></i> Quay lại đăng nhập</a></div>
<?php require __DIR__ . '/../partials/auth_bottom.php'; ?>
