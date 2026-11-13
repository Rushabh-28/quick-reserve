<?php
/**
 * Shared bootstrap for every Admin page: session, DB, security helpers.
 */
declare(strict_types=1);

if (session_status() === PHP_SESSION_NONE) {
    session_set_cookie_params([
        'lifetime' => 0,
        'path'     => '/',
        'httponly' => true,
        'samesite' => 'Lax',
        'secure'   => !empty($_SERVER['HTTPS']),
    ]);
    session_start();
}

require_once __DIR__ . '/../../config/connection.php';   // defines $conn and $qr_config

/* ---------- output / redirects ---------- */

/** Escape a value for safe output inside HTML. */
function h($value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function redirect(string $url): never
{
    header('Location: ' . $url);
    exit;
}

/* ---------- flash messages ---------- */

function flash(string $type, string $message): void
{
    $_SESSION['flash'][] = ['type' => $type, 'message' => $message];
}

function render_flash(): void
{
    foreach ($_SESSION['flash'] ?? [] as $f) {
        $cls = $f['type'] === 'error' ? 'danger' : $f['type'];
        echo '<div class="alert alert-' . h($cls) . '" role="alert">' . h($f['message']) . '</div>';
    }
    unset($_SESSION['flash']);
}

/* ---------- CSRF ---------- */

function csrf_token(): string
{
    if (empty($_SESSION['csrf'])) {
        $_SESSION['csrf'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf'];
}

function csrf_field(): string
{
    return '<input type="hidden" name="csrf" value="' . h(csrf_token()) . '">';
}

/** Call at the top of any POST handler. */
function csrf_verify(): void
{
    $sent = $_POST['csrf'] ?? '';
    if (!is_string($sent) || !hash_equals(csrf_token(), $sent)) {
        http_response_code(400);
        exit('Invalid or expired form token. Go back, refresh the page and try again.');
    }
}

function is_post(): bool
{
    return $_SERVER['REQUEST_METHOD'] === 'POST';
}

/* ---------- database helpers (always prepared statements) ---------- */

function db_run(string $sql, string $types = '', array $params = []): mysqli_stmt
{
    global $conn;
    $stmt = $conn->prepare($sql);
    if ($types !== '') {
        $stmt->bind_param($types, ...$params);
    }
    $stmt->execute();
    return $stmt;
}

function db_all(string $sql, string $types = '', array $params = []): array
{
    $stmt = db_run($sql, $types, $params);
    return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
}

function db_one(string $sql, string $types = '', array $params = []): ?array
{
    $rows = db_all($sql, $types, $params);
    return $rows[0] ?? null;
}

/** Run INSERT/UPDATE/DELETE. Returns affected rows. */
function db_exec(string $sql, string $types = '', array $params = []): int
{
    return db_run($sql, $types, $params)->affected_rows;
}

function db_insert_id(): int
{
    global $conn;
    return (int) $conn->insert_id;
}

/* ---------- authentication ---------- */

function current_user(): ?array
{
    static $user = false;
    if ($user === false) {
        $user = null;
        if (!empty($_SESSION['user_id'])) {
            $user = db_one('SELECT * FROM `user` WHERE User_id = ? AND is_admin = 1', 'i', [(int) $_SESSION['user_id']]);
            if (!$user) {
                unset($_SESSION['user_id']);
            }
        }
    }
    return $user;
}

function require_admin(): array
{
    $user = current_user();
    if (!$user) {
        redirect('login.php');
    }
    return $user;
}

function login_user(int $userId): void
{
    session_regenerate_id(true);
    $_SESSION['user_id'] = $userId;
}

function logout_user(): void
{
    $_SESSION = [];
    if (ini_get('session.use_cookies')) {
        $p = session_get_cookie_params();
        setcookie(session_name(), '', time() - 3600, $p['path'], $p['domain'], $p['secure'], $p['httponly']);
    }
    session_destroy();
}

function admin_exists(): bool
{
    return (bool) db_one('SELECT User_id FROM `user` WHERE is_admin = 1 LIMIT 1');
}

/**
 * Check a password. Also accepts legacy plain-text passwords from the old
 * version of the app; the caller should re-hash when $needsRehash is true.
 */
function verify_password(string $plain, string $stored, ?bool &$needsRehash = null): bool
{
    $needsRehash = false;
    if (password_get_info($stored)['algo'] !== null) {
        $ok = password_verify($plain, $stored);
        $needsRehash = $ok && password_needs_rehash($stored, PASSWORD_DEFAULT);
        return $ok;
    }
    $ok = hash_equals($stored, $plain);   // legacy plain text
    $needsRehash = $ok;
    return $ok;
}

/* ---------- validation helpers ---------- */

function post_str(string $key): string
{
    $v = $_POST[$key] ?? '';
    return is_string($v) ? trim($v) : '';
}

function valid_phone(string $p): bool
{
    return (bool) preg_match('/^[0-9+\-\s()]{7,20}$/', $p);
}

/* ---------- image upload ---------- */

/**
 * Handle an <input type="file" name="$field">. Returns the stored file name,
 * null when nothing was uploaded, or throws RuntimeException with a message.
 */
function handle_image_upload(string $field): ?string
{
    if (empty($_FILES[$field]) || $_FILES[$field]['error'] === UPLOAD_ERR_NO_FILE) {
        return null;
    }
    $f = $_FILES[$field];
    if ($f['error'] !== UPLOAD_ERR_OK) {
        throw new RuntimeException('Image upload failed (error code ' . (int) $f['error'] . ').');
    }
    if ($f['size'] > 2 * 1024 * 1024) {
        throw new RuntimeException('Image must be 2 MB or smaller.');
    }
    $mime = (new finfo(FILEINFO_MIME_TYPE))->file($f['tmp_name']);
    $allowed = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp'];
    if (!isset($allowed[$mime])) {
        throw new RuntimeException('Only JPG, PNG or WEBP images are allowed.');
    }
    $dir = __DIR__ . '/../uploads';
    if (!is_dir($dir) && !mkdir($dir, 0755, true)) {
        throw new RuntimeException('Upload folder is missing and could not be created.');
    }
    $name = bin2hex(random_bytes(12)) . '.' . $allowed[$mime];
    if (!move_uploaded_file($f['tmp_name'], $dir . '/' . $name)) {
        throw new RuntimeException('Could not save the uploaded image.');
    }
    return $name;
}

/** Upload file names are either random hex (real uploads) or demo_* (bundled sample images). */
function is_upload_name(?string $name): bool
{
    return $name !== null && (bool) preg_match('/^([a-f0-9]{24}|demo_[a-z0-9_]{2,40})\.(jpg|png|webp)$/', $name);
}

function delete_upload(?string $name): void
{
    if ($name && is_upload_name($name) && strncmp($name, 'demo_', 5) !== 0) {
        $path = __DIR__ . '/../uploads/' . $name;
        if (is_file($path)) {
            unlink($path);
        }
    }
}

function image_tag(?string $name, string $alt = ''): string
{
    if (!is_upload_name($name)) {
        return '<span class="text-muted">&mdash;</span>';
    }
    return '<img src="uploads/' . h($name) . '" alt="' . h($alt) . '" style="width:60px;height:45px;object-fit:cover;border-radius:4px">';
}

/* ---------- small UI helpers ---------- */

/** Inline POST form used instead of GET links for destructive actions. */
function delete_button(string $action, int $id): string
{
    return '<form method="post" action="' . h($action) . '" class="d-inline" '
         . 'onsubmit="return confirm(\'Delete this record? This cannot be undone.\');">'
         . csrf_field()
         . '<input type="hidden" name="id" value="' . $id . '">'
         . '<button type="submit" class="btn btn-link p-0 text-danger align-baseline" style="font-weight:normal;font-size:inherit;text-decoration:underline">Delete</button></form>';
}

function field_error(array $errors, string $key): string
{
    return isset($errors[$key]) ? '<small class="text-danger d-block mt-1">' . h($errors[$key]) . '</small>' : '';
}
