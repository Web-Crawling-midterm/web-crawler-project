<?php

// Đặt múi giờ Việt Nam (mặc định của PHP là UTC nên giờ bị lệch 7 tiếng)
date_default_timezone_set('Asia/Ho_Chi_Minh');

/**
 * auth.php – Xác thực và phân quyền cho hệ thống Web Crawler.
 *
 * Chức năng:
 *  - Session an toàn (HttpOnly, SameSite=Lax, Secure khi chạy HTTPS)
 *  - Tài khoản lưu trong data/users.json, mật khẩu băm bằng password_hash()
 *  - Chống brute-force: khóa tạm thời sau nhiều lần đăng nhập sai
 *  - Token CSRF cho mọi yêu cầu thay đổi dữ liệu (POST)
 *  - Hai vai trò: "admin" (xem/xóa mọi dữ liệu) và "user" (chỉ dữ liệu của mình)
 *
 * Cách dùng trong các endpoint:
 *     require_once __DIR__ . '/auth.php';
 *     requireLogin();            // trả 401 JSON nếu chưa đăng nhập, kiểm tra CSRF với POST
 *
 * File này cũng là API: auth.php?action=login | logout | me
 */

const AUTH_MAX_FAILS     = 5;     // số lần sai tối đa
const AUTH_LOCK_SECONDS  = 300;   // thời gian khóa (5 phút)
const AUTH_SESSION_IDLE  = 3600;  // tự đăng xuất sau 1 giờ không hoạt động

const AUTH_RESET_TTL_SECONDS = 900;   // link đặt lại mật khẩu có hiệu lực 15 phút
const AUTH_MIN_PASSWORD      = 8;

// Chế độ DEMO: máy chạy localhost không có máy chủ email, nên link đặt lại mật khẩu
// được ghi vào data/outbox/ và hiển thị ngay trên trang. Khi triển khai thật hãy đặt
// AUTH_DEMO_SHOW_RESET_LINK = false và AUTH_BASE_URL = 'https://ten-mien-cua-ban/Frontend'
// để hệ thống gửi email bằng mail().
const AUTH_DEMO_SHOW_RESET_LINK = true;
const AUTH_BASE_URL             = '';

// Tài khoản mẫu, được tạo tự động lần chạy đầu tiên (xem README).
const AUTH_DEFAULT_USERS = [
    ['username' => 'admin', 'password' => 'Admin@123', 'role' => 'admin', 'email' => 'admin@example.com'],
    ['username' => 'user',  'password' => 'User@123',  'role' => 'user',  'email' => 'user@example.com'],
];

// --------------------------------------------------
// Session
// --------------------------------------------------
function authStartSession(): void
{
    if (session_status() === PHP_SESSION_ACTIVE) {
        return;
    }

    $https = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off');

    session_set_cookie_params([
        'lifetime' => 0,
        'path'     => '/',
        'secure'   => $https,
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
    session_name('CRAWLERSESSID');
    session_start();

    // Hết hạn nếu không hoạt động quá lâu
    if (isset($_SESSION['last_active']) && time() - $_SESSION['last_active'] > AUTH_SESSION_IDLE) {
        $_SESSION = [];
        session_regenerate_id(true);
    }
    $_SESSION['last_active'] = time();
}

authStartSession();

// --------------------------------------------------
// Lưu trữ file JSON (có khóa file)
// --------------------------------------------------
function authDataDir(): string
{
    $dir = __DIR__ . '/../data';
    if (!is_dir($dir)) {
        mkdir($dir, 0755, true);
    }
    return $dir;
}

function authReadJson(string $name): array
{
    $file = authDataDir() . '/' . $name;
    if (!is_file($file)) {
        return [];
    }
    $data = json_decode((string) file_get_contents($file), true);
    return is_array($data) ? $data : [];
}

function authWriteJson(string $name, array $data): void
{
    file_put_contents(
        authDataDir() . '/' . $name,
        json_encode($data, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT),
        LOCK_EX
    );
}

// --------------------------------------------------
// Người dùng
// --------------------------------------------------
function authLoadUsers(): array
{
    $users = authReadJson('users.json');

    if (!$users) {
        foreach (AUTH_DEFAULT_USERS as $u) {
            $users[$u['username']] = [
                'password_hash' => password_hash($u['password'], PASSWORD_DEFAULT),
                'role'          => $u['role'],
                'email'         => $u['email'] ?? '',
                'created_at'    => date('Y-m-d H:i:s'),
            ];
        }
        authWriteJson('users.json', $users);
    }

    return $users;
}

function authUser(): ?array
{
    if (empty($_SESSION['user'])) {
        return null;
    }
    return ['username' => $_SESSION['user'], 'role' => $_SESSION['role'] ?? 'user'];
}

function authIsAdmin(): bool
{
    $u = authUser();
    return $u !== null && $u['role'] === 'admin';
}

// --------------------------------------------------
// CSRF
// --------------------------------------------------
function csrfToken(): string
{
    if (empty($_SESSION['csrf'])) {
        $_SESSION['csrf'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf'];
}

function csrfIsValid(): bool
{
    $sent = $_SERVER['HTTP_X_CSRF_TOKEN'] ?? ($_POST['csrf_token'] ?? '');
    return is_string($sent) && $sent !== '' && hash_equals(csrfToken(), $sent);
}

// --------------------------------------------------
// Chặn truy cập
// --------------------------------------------------
function authJsonError(int $status, string $message): void
{
    http_response_code($status);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(['success' => false, 'message' => $message], JSON_UNESCAPED_UNICODE);
    exit;
}

/** Bắt buộc đăng nhập; với POST còn kiểm tra CSRF. */
function requireLogin(): array
{
    $user = authUser();
    if ($user === null) {
        authJsonError(401, 'Bạn chưa đăng nhập hoặc phiên đã hết hạn.');
    }
    if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST' && !csrfIsValid()) {
        authJsonError(403, 'Token CSRF không hợp lệ. Hãy tải lại trang.');
    }
    return $user;
}

// --------------------------------------------------
// Chống brute-force (theo tên đăng nhập + IP)
// --------------------------------------------------
function authAttemptKey(string $username): string
{
    return hash('sha256', strtolower($username) . '|' . ($_SERVER['REMOTE_ADDR'] ?? ''));
}

function authLockRemaining(string $username): int
{
    $all = authReadJson('login_attempts.json');
    $rec = $all[authAttemptKey($username)] ?? null;
    if ($rec && ($rec['locked_until'] ?? 0) > time()) {
        return (int) $rec['locked_until'] - time();
    }
    return 0;
}

function authRecordFailure(string $username): void
{
    $all = authReadJson('login_attempts.json');
    $key = authAttemptKey($username);

    // Dọn bản ghi đã hết hạn để file không phình to
    foreach ($all as $k => $r) {
        if (($r['locked_until'] ?? 0) < time() && ($r['last'] ?? 0) < time() - AUTH_LOCK_SECONDS) {
            unset($all[$k]);
        }
    }

    $rec = $all[$key] ?? ['fails' => 0, 'locked_until' => 0];
    if (($rec['locked_until'] ?? 0) && $rec['locked_until'] <= time()) {
        $rec = ['fails' => 0, 'locked_until' => 0];
    }
    $rec['fails']++;
    $rec['last'] = time();
    if ($rec['fails'] >= AUTH_MAX_FAILS) {
        $rec['locked_until'] = time() + AUTH_LOCK_SECONDS;
    }
    $all[$key] = $rec;
    authWriteJson('login_attempts.json', $all);
}

function authClearFailures(string $username): void
{
    $all = authReadJson('login_attempts.json');
    unset($all[authAttemptKey($username)]);
    authWriteJson('login_attempts.json', $all);
}

/**
 * Thử đăng nhập. Trả về ['ok' => bool, 'message' => string].
 */
function authLogin(string $username, string $password): array
{
    $username = trim($username);

    // Cho phép nhập email thay cho tên đăng nhập
    if ($username !== '' && strpos($username, '@') !== false) {
        $found = authFindUserByEmail($username);
        $username = $found ?? $username;
    }

    if ($username === '' || $password === '' || !preg_match('/^[A-Za-z0-9_.-]{1,32}$/', $username)) {
        return ['ok' => false, 'message' => 'Tên đăng nhập hoặc mật khẩu không đúng.'];
    }

    $wait = authLockRemaining($username);
    if ($wait > 0) {
        return ['ok' => false, 'message' => 'Đăng nhập sai quá nhiều lần. Thử lại sau ' . ceil($wait / 60) . ' phút.'];
    }

    $users = authLoadUsers();
    $u     = $users[$username] ?? null;

    // Luôn chạy password_verify để thời gian phản hồi không lộ tài khoản nào tồn tại
    $hash = $u['password_hash'] ?? '$2y$10$nOzc0QhdH2eAOMjk/l5Rw.wTL6NU0HOkku.v1AFTvG7Rsuslls1sO';
    $good = password_verify($password, $hash) && $u !== null;

    if (!$good) {
        authRecordFailure($username);
        return ['ok' => false, 'message' => 'Tên đăng nhập hoặc mật khẩu không đúng.'];
    }

    authClearFailures($username);
    session_regenerate_id(true);          // chống session fixation
    $_SESSION['user'] = $username;
    $_SESSION['role'] = $u['role'] ?? 'user';
    $_SESSION['csrf'] = bin2hex(random_bytes(32));

    if (password_needs_rehash($hash, PASSWORD_DEFAULT)) {
        $users[$username]['password_hash'] = password_hash($password, PASSWORD_DEFAULT);
        authWriteJson('users.json', $users);
    }

    return ['ok' => true, 'message' => 'Đăng nhập thành công.'];
}

function authLogout(): void
{
    $_SESSION = [];
    if (ini_get('session.use_cookies')) {
        $p = session_get_cookie_params();
        setcookie(session_name(), '', time() - 3600, $p['path'], $p['domain'], $p['secure'], $p['httponly']);
    }
    session_destroy();
}

// --------------------------------------------------
// Giới hạn tần suất theo IP (đăng ký, quên mật khẩu)
// --------------------------------------------------
function authRateLimited(string $bucket, int $max, int $windowSeconds): bool
{
    $all = authReadJson('rate_limits.json');
    $key = $bucket . '|' . ($_SERVER['REMOTE_ADDR'] ?? '');
    $now = time();

    foreach ($all as $k => $times) {            // dọn bản ghi cũ
        $all[$k] = array_values(array_filter((array) $times, fn($t) => $t > $now - 86400));
        if (!$all[$k]) {
            unset($all[$k]);
        }
    }

    $recent = array_values(array_filter($all[$key] ?? [], fn($t) => $t > $now - $windowSeconds));
    if (count($recent) >= $max) {
        authWriteJson('rate_limits.json', $all);
        return true;
    }
    $recent[] = $now;
    $all[$key] = $recent;
    authWriteJson('rate_limits.json', $all);
    return false;
}

// --------------------------------------------------
// Tìm người dùng theo email
// --------------------------------------------------
function authFindUserByEmail(string $email): ?string
{
    $email = strtolower(trim($email));
    if ($email === '') {
        return null;
    }
    foreach (authLoadUsers() as $name => $u) {
        if (strtolower((string) ($u['email'] ?? '')) === $email) {
            return (string) $name;
        }
    }
    return null;
}

// --------------------------------------------------
// Kiểm tra mật khẩu
// --------------------------------------------------
function authPasswordError(string $password): string
{
    if (strlen($password) < AUTH_MIN_PASSWORD) {
        return 'Mật khẩu phải có ít nhất ' . AUTH_MIN_PASSWORD . ' ký tự.';
    }
    if (strlen($password) > 72) {
        return 'Mật khẩu tối đa 72 ký tự.';
    }
    if (!preg_match('/[A-Za-z]/', $password) || !preg_match('/\d/', $password)) {
        return 'Mật khẩu phải gồm cả chữ và số.';
    }
    return '';
}

// --------------------------------------------------
// Đăng ký
// --------------------------------------------------
function authRegister(string $username, string $email, string $password, string $confirm): array
{
    $username = trim($username);
    $email    = trim($email);

    if (!preg_match('/^[A-Za-z0-9_.-]{3,32}$/', $username)) {
        return ['ok' => false, 'message' => 'Tên đăng nhập gồm 3–32 ký tự: chữ, số, dấu chấm, gạch dưới hoặc gạch ngang.'];
    }
    if (strlen($email) > 100 || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        return ['ok' => false, 'message' => 'Email không hợp lệ.'];
    }
    if ($err = authPasswordError($password)) {
        return ['ok' => false, 'message' => $err];
    }
    if (!hash_equals($password, $confirm)) {
        return ['ok' => false, 'message' => 'Mật khẩu nhập lại không khớp.'];
    }
    if (authRateLimited('register', 5, 3600)) {
        return ['ok' => false, 'message' => 'Bạn đăng ký quá nhiều lần. Vui lòng thử lại sau.'];
    }

    $users = authLoadUsers();
    foreach ($users as $name => $_) {
        if (strcasecmp((string) $name, $username) === 0) {
            return ['ok' => false, 'message' => 'Tên đăng nhập đã được sử dụng.'];
        }
    }
    if (authFindUserByEmail($email) !== null) {
        return ['ok' => false, 'message' => 'Email đã được sử dụng.'];
    }

    // Tài khoản đăng ký luôn là "user"; chỉ admin có sẵn mới có quyền quản trị
    $users[$username] = [
        'password_hash' => password_hash($password, PASSWORD_DEFAULT),
        'role'          => 'user',
        'email'         => $email,
        'created_at'    => date('Y-m-d H:i:s'),
    ];
    authWriteJson('users.json', $users);

    return ['ok' => true, 'message' => 'Đăng ký thành công. Bạn có thể đăng nhập.'];
}

// --------------------------------------------------
// Quên mật khẩu: tạo token (chỉ lưu bản băm SHA-256) và "gửi" link
// --------------------------------------------------
function authRequestPasswordReset(string $email): array
{
    // Thông báo luôn giống nhau để không lộ email nào tồn tại
    $res = ['ok' => true, 'link' => null,
            'message' => 'Nếu email tồn tại trong hệ thống, liên kết đặt lại mật khẩu đã được gửi.'];

    $email = trim($email);
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        return ['ok' => false, 'link' => null, 'message' => 'Email không hợp lệ.'];
    }
    if (authRateLimited('forgot', 5, 900)) {
        return ['ok' => false, 'link' => null, 'message' => 'Bạn yêu cầu quá nhiều lần. Vui lòng thử lại sau 15 phút.'];
    }

    $username = authFindUserByEmail($email);
    if ($username === null) {
        return $res;
    }

    $resets = authReadJson('password_resets.json');
    foreach ($resets as $h => $r) {                         // dọn token hết hạn
        if (($r['expires'] ?? 0) < time()) {
            unset($resets[$h]);
        }
    }
    foreach ($resets as $h => $r) {                         // mỗi người chỉ có 1 token hiệu lực
        if (($r['username'] ?? '') === $username) {
            unset($resets[$h]);
        }
    }

    $token = bin2hex(random_bytes(32));
    $resets[hash('sha256', $token)] = ['username' => $username, 'expires' => time() + AUTH_RESET_TTL_SECONDS];
    authWriteJson('password_resets.json', $resets);

    $path = 'reset_password.php?token=' . $token;
    $url  = AUTH_BASE_URL !== '' ? rtrim(AUTH_BASE_URL, '/') . '/' . $path : $path;

    if (AUTH_BASE_URL !== '') {
        @mail($email, 'Dat lai mat khau CrawlWise',
            "Xin chào $username,\n\nBấm vào liên kết sau để đặt lại mật khẩu (hiệu lực 15 phút):\n$url\n\nNếu bạn không yêu cầu, hãy bỏ qua email này.",
            "Content-Type: text/plain; charset=UTF-8");
    }

    // Chế độ demo: ghi "hộp thư" ra file để dễ kiểm thử
    $outbox = authDataDir() . '/outbox';
    if (!is_dir($outbox)) {
        mkdir($outbox, 0755, true);
    }
    file_put_contents($outbox . '/reset_' . date('Ymd_His') . '_' . $username . '.txt',
        "Gửi tới: $email\nLiên kết (hiệu lực 15 phút): $url\n", LOCK_EX);

    if (AUTH_DEMO_SHOW_RESET_LINK) {
        $res['link'] = $url;
    }
    return $res;
}

function authResetTokenUser(string $token): ?string
{
    if (!preg_match('/^[a-f0-9]{64}$/', $token)) {
        return null;
    }
    $r = authReadJson('password_resets.json')[hash('sha256', $token)] ?? null;
    if (!$r || ($r['expires'] ?? 0) < time()) {
        return null;
    }
    return (string) $r['username'];
}

function authResetPassword(string $token, string $password, string $confirm): array
{
    $username = authResetTokenUser($token);
    if ($username === null) {
        return ['ok' => false, 'fatal' => true, 'message' => 'Liên kết không hợp lệ hoặc đã hết hạn. Hãy yêu cầu liên kết mới.'];
    }
    if ($err = authPasswordError($password)) {
        return ['ok' => false, 'message' => $err];
    }
    if (!hash_equals($password, $confirm)) {
        return ['ok' => false, 'message' => 'Mật khẩu nhập lại không khớp.'];
    }

    $users = authLoadUsers();
    if (!isset($users[$username])) {
        return ['ok' => false, 'fatal' => true, 'message' => 'Tài khoản không tồn tại.'];
    }
    $users[$username]['password_hash'] = password_hash($password, PASSWORD_DEFAULT);
    authWriteJson('users.json', $users);

    $resets = authReadJson('password_resets.json');     // token chỉ dùng được 1 lần
    unset($resets[hash('sha256', $token)]);
    authWriteJson('password_resets.json', $resets);

    return ['ok' => true, 'message' => 'Đã đặt lại mật khẩu. Bạn có thể đăng nhập bằng mật khẩu mới.'];
}

// --------------------------------------------------
// API trực tiếp: auth.php?action=login|logout|me
// --------------------------------------------------
if (realpath($_SERVER['SCRIPT_FILENAME'] ?? '') === realpath(__FILE__)) {
    header('Content-Type: application/json; charset=utf-8');
    $action = $_GET['action'] ?? '';

    if ($action === 'me') {
        $u = authUser();
        echo json_encode(['success' => $u !== null, 'user' => $u, 'csrf_token' => $u ? csrfToken() : null], JSON_UNESCAPED_UNICODE);
        exit;
    }

    if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
        authJsonError(405, 'Vui lòng gửi yêu cầu POST.');
    }

    if ($action === 'login') {
        // Đăng nhập chưa có phiên nên dùng token CSRF của phiên khách
        if (!csrfIsValid()) {
            authJsonError(403, 'Token CSRF không hợp lệ. Hãy tải lại trang.');
        }
        $in  = json_decode(file_get_contents('php://input'), true) ?: $_POST;
        $res = authLogin((string) ($in['username'] ?? ''), (string) ($in['password'] ?? ''));
        http_response_code($res['ok'] ? 200 : 401);
        echo json_encode(['success' => $res['ok'], 'message' => $res['message'], 'csrf_token' => $res['ok'] ? csrfToken() : null], JSON_UNESCAPED_UNICODE);
        exit;
    }

    if ($action === 'logout') {
        if (!csrfIsValid()) {
            authJsonError(403, 'Token CSRF không hợp lệ.');
        }
        authLogout();
        echo json_encode(['success' => true, 'message' => 'Đã đăng xuất.'], JSON_UNESCAPED_UNICODE);
        exit;
    }

    authJsonError(400, 'Hành động không hợp lệ.');
}
