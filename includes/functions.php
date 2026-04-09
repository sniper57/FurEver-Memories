<?php
require_once __DIR__ . '/../config/db.php';

function send_security_headers(): void
{
    header('X-Frame-Options: SAMEORIGIN');
    header('X-Content-Type-Options: nosniff');
    header('Referrer-Policy: strict-origin-when-cross-origin');
    header('Permissions-Policy: geolocation=(), microphone=(), camera=()');
}

function e($value): string
{
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

function h(?string $value): string
{
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

function now(): string
{
    return date('Y-m-d H:i:s');
}

function today(): string
{
    return date('Y-m-d');
}

function guid(): string
{
    $data = random_bytes(16);
    $data[6] = chr((ord($data[6]) & 0x0f) | 0x40);
    $data[8] = chr((ord($data[8]) & 0x3f) | 0x80);
    return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($data), 4));
}

function random_token(int $bytes = 32): string
{
    return bin2hex(random_bytes($bytes));
}

function client_ip(): string
{
    $keys = ['HTTP_CF_CONNECTING_IP', 'HTTP_X_FORWARDED_FOR', 'REMOTE_ADDR'];
    foreach ($keys as $key) {
        if (!empty($_SERVER[$key])) {
            return trim(explode(',', $_SERVER[$key])[0]);
        }
    }
    return '0.0.0.0';
}

function user_agent(): string
{
    return substr($_SERVER['HTTP_USER_AGENT'] ?? 'unknown', 0, 500);
}

function hash_ip(string $ip): string
{
    return hash('sha256', $ip);
}

function redirect(string $url): void
{
    header('Location: ' . $url);
    exit;
}

function flash_set(string $key, string $message): void
{
    $_SESSION['_flash'][$key] = $message;
}

function flash_get(string $key): string
{
    $msg = $_SESSION['_flash'][$key] ?? '';
    unset($_SESSION['_flash'][$key]);
    return $msg;
}

function csrf_token(): string
{
    if (empty($_SESSION[CSRF_TOKEN_NAME])) {
        $_SESSION[CSRF_TOKEN_NAME] = random_token(32);
    }
    return $_SESSION[CSRF_TOKEN_NAME];
}

function csrf_input(): string
{
    return '<input type="hidden" name="' . CSRF_TOKEN_NAME . '" value="' . e(csrf_token()) . '">';
}

function verify_csrf_or_fail(): void
{
    $submitted = $_POST[CSRF_TOKEN_NAME] ?? '';
    $session = $_SESSION[CSRF_TOKEN_NAME] ?? '';
    if (!$submitted || !$session || !hash_equals($session, $submitted)) {
        http_response_code(419);
        exit('Invalid or expired form token. Please refresh the page and try again.');
    }
}

function is_logged_in(): bool
{
    return !empty($_SESSION['user']);
}

function current_user(): ?array
{
    return $_SESSION['user'] ?? null;
}

function is_admin(): bool
{
    return (current_user()['role'] ?? '') === 'administrator';
}

function is_client(): bool
{
    return (current_user()['role'] ?? '') === 'client';
}

function require_login(): void
{
    if (!is_logged_in()) {
        redirect('login.php');
    }
}

function require_admin(): void
{
    require_login();
    if (!is_admin()) {
        http_response_code(403);
        exit('Access denied.');
    }
}

function require_verified_client(): void
{
    require_login();
    if (is_client() && empty(current_user()['is_email_verified'])) {
        flash_set('warning', 'Please verify your email address first.');
        redirect('client_profile.php');
    }
}

function public_memorial_url(string $clientGuid): string
{
    return rtrim(BASE_URL, '/') . '/c/' . rawurlencode($clientGuid);
}

function password_is_strong_enough(string $password): bool
{
    if (strlen($password) < PASSWORD_MIN_LENGTH) return false;
    if (!preg_match('/[A-Z]/', $password)) return false;
    if (!preg_match('/[a-z]/', $password)) return false;
    if (!preg_match('/\d/', $password)) return false;
    return true;
}

function fetch_user_by_email(string $email): ?array
{
    $stmt = db()->prepare('SELECT * FROM users WHERE email = ? LIMIT 1');
    $stmt->execute([mb_strtolower(trim($email))]);
    return $stmt->fetch() ?: null;
}

function fetch_user_by_id(int $id): ?array
{
    $stmt = db()->prepare('SELECT * FROM users WHERE id = ? LIMIT 1');
    $stmt->execute([$id]);
    return $stmt->fetch() ?: null;
}

function fetch_client_by_guid(string $guid): ?array
{
    $stmt = db()->prepare('SELECT * FROM users WHERE client_guid = ? AND role = "client" LIMIT 1');
    $stmt->execute([$guid]);
    return $stmt->fetch() ?: null;
}

function fetch_memorial_by_client_id(int $clientId): ?array
{
    $stmt = db()->prepare('SELECT * FROM memorial_pages WHERE client_user_id = ? LIMIT 1');
    $stmt->execute([$clientId]);
    return $stmt->fetch() ?: null;
}

function fetch_timeline_items(int $memorialId): array
{
    $stmt = db()->prepare('SELECT * FROM memorial_timelines WHERE memorial_page_id = ? ORDER BY sort_order ASC, event_date ASC, id ASC');
    $stmt->execute([$memorialId]);
    return $stmt->fetchAll();
}

function fetch_gallery_items(int $memorialId): array
{
    $stmt = db()->prepare('SELECT * FROM memorial_gallery WHERE memorial_page_id = ? ORDER BY sort_order ASC, id ASC');
    $stmt->execute([$memorialId]);
    return $stmt->fetchAll();
}

function fetch_music_items(int $memorialId): array
{
    $stmt = db()->prepare('SELECT * FROM memorial_music WHERE memorial_page_id = ? ORDER BY sort_order ASC, id ASC');
    $stmt->execute([$memorialId]);
    return $stmt->fetchAll();
}

function fetch_messages(int $memorialId, bool $approvedOnly = true): array
{
    $sql = 'SELECT * FROM memorial_messages WHERE memorial_page_id = ?';
    if ($approvedOnly) {
        $sql .= ' AND is_approved = 1';
    }
    $sql .= ' ORDER BY created_at DESC';
    $stmt = db()->prepare($sql);
    $stmt->execute([$memorialId]);
    return $stmt->fetchAll();
}

function count_pending_messages(int $memorialId): int
{
    $stmt = db()->prepare('SELECT COUNT(*) FROM memorial_messages WHERE memorial_page_id = ? AND is_approved = 0');
    $stmt->execute([$memorialId]);
    return (int)$stmt->fetchColumn();
}

function count_candles(int $memorialId): int
{
    $stmt = db()->prepare("SELECT COUNT(*) FROM memorial_reactions WHERE memorial_page_id = ? AND reaction_type = 'candle'");
    $stmt->execute([$memorialId]);
    return (int)$stmt->fetchColumn();
}

function count_hearts(int $memorialId): int
{
    $stmt = db()->prepare("SELECT COUNT(*) FROM memorial_reactions WHERE memorial_page_id = ? AND reaction_type = 'heart'");
    $stmt->execute([$memorialId]);
    return (int)$stmt->fetchColumn();
}

function recent_reactors(int $memorialId, string $reactionType, int $limit = 12): array
{
    $stmt = db()->prepare('SELECT visitor_name FROM memorial_reactions WHERE memorial_page_id = ? AND reaction_type = ? ORDER BY created_at DESC LIMIT ' . (int)$limit);
    $stmt->execute([$memorialId, $reactionType]);
    return $stmt->fetchAll();
}

function log_audit(string $action, string $message, ?string $targetType = null, ?int $targetId = null, array $meta = []): void
{
    $actor = current_user();
    $stmt = db()->prepare('INSERT INTO audit_logs (actor_user_id, actor_role, action_name, message, target_type, target_id, ip_address, user_agent, metadata_json, created_at) VALUES (?,?,?,?,?,?,?,?,?,?)');
    $stmt->execute([
        $actor['id'] ?? null,
        $actor['role'] ?? null,
        $action,
        $message,
        $targetType,
        $targetId,
        client_ip(),
        user_agent(),
        $meta ? json_encode($meta, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) : null,
        now(),
    ]);
}

function login_is_rate_limited(string $identifier, string $ip): bool
{
    $window = (int)BRUTE_FORCE_WINDOW_MINUTES;
    $stmt = db()->prepare('SELECT COUNT(*) FROM auth_login_attempts WHERE success = 0 AND attempt_key = ? AND ip_address = ? AND created_at >= DATE_SUB(NOW(), INTERVAL ' . $window . ' MINUTE)');
    $stmt->execute([mb_strtolower(trim($identifier)), $ip]);
    return (int)$stmt->fetchColumn() >= BRUTE_FORCE_MAX_ATTEMPTS;
}

function record_login_attempt(string $identifier, string $ip, bool $success, ?int $userId = null): void
{
    $stmt = db()->prepare('INSERT INTO auth_login_attempts (attempt_key, ip_address, success, user_id, created_at) VALUES (?,?,?,?,?)');
    $stmt->execute([mb_strtolower(trim($identifier)), $ip, $success ? 1 : 0, $userId, now()]);
}

function issue_email_verification_token(int $userId, string $email): array
{
    $plain = random_token(32);
    $hash = hash('sha256', $plain);
    db()->prepare('UPDATE email_verification_tokens SET is_used = 1, used_at = ? WHERE user_id = ? AND is_used = 0')->execute([now(), $userId]);
    db()->prepare('INSERT INTO email_verification_tokens (user_id, email, token_hash, expires_at, created_at) VALUES (?,?,?,?,?)')
        ->execute([$userId, $email, $hash, date('Y-m-d H:i:s', time() + EMAIL_VERIFICATION_EXPIRY_HOURS * 3600), now()]);
    $url = rtrim(BASE_URL, '/') . '/verify_email.php?token=' . urlencode($plain);
    return ['plain_token' => $plain, 'url' => $url];
}

function verify_email_token(string $plainToken): ?array
{
    $hash = hash('sha256', trim($plainToken));
    $stmt = db()->prepare('SELECT * FROM email_verification_tokens WHERE token_hash = ? AND is_used = 0 AND expires_at >= NOW() ORDER BY id DESC LIMIT 1');
    $stmt->execute([$hash]);
    return $stmt->fetch() ?: null;
}

function mark_email_verified(int $userId, int $tokenId): void
{
    db()->prepare('UPDATE users SET is_email_verified = 1, email_verified_at = ?, is_active = 1, updated_at = ? WHERE id = ?')
        ->execute([now(), now(), $userId]);
    db()->prepare('UPDATE email_verification_tokens SET is_used = 1, used_at = ? WHERE id = ?')
        ->execute([now(), $tokenId]);
}

function send_verification_email(array $user, string $verificationUrl): bool
{
    require_once __DIR__ . '/mailer.php';
    $html = '<p>Hello ' . e($user['full_name']) . ',</p>'
        . '<p>Please verify your FurEver Memories client account by clicking the button below:</p>'
        . '<p><a href="' . e($verificationUrl) . '" style="display:inline-block;padding:12px 18px;background:#111;color:#fff;text-decoration:none;border-radius:8px;">Verify Email</a></p>'
        . '<p>If the button does not work, copy this link:</p><p>' . e($verificationUrl) . '</p>'
        . '<p>This link expires in ' . EMAIL_VERIFICATION_EXPIRY_HOURS . ' hours.</p>';
    return send_basic_mail($user['email'], 'Verify your FurEver Memories account', $html);
}

function upsert_memorial(int $clientUserId, array $data): int
{
    $existing = fetch_memorial_by_client_id($clientUserId);
    if ($existing) {
        $sql = 'UPDATE memorial_pages SET pet_name=?, pet_birth_date=?, pet_memorial_date=?, short_tribute=?, final_letter=?, video_type=?, video_url=?, video_file=?, bg_image_portrait=?, bg_image_landscape=?, cover_photo=?, share_footer_text=?, youtube_embed_url=?, video_max_mb=?, updated_at=? WHERE client_user_id=?';
        db()->prepare($sql)->execute([
            $data['pet_name'], $data['pet_birth_date'], $data['pet_memorial_date'], $data['short_tribute'], $data['final_letter'],
            $data['video_type'], $data['video_url'], $data['video_file'], $data['bg_image_portrait'], $data['bg_image_landscape'],
            $data['cover_photo'], $data['share_footer_text'], $data['youtube_embed_url'], $data['video_max_mb'], now(), $clientUserId,
        ]);
        return (int)$existing['id'];
    }

    $sql = 'INSERT INTO memorial_pages (client_user_id, pet_name, pet_birth_date, pet_memorial_date, short_tribute, final_letter, video_type, video_url, youtube_embed_url, video_file, bg_image_portrait, bg_image_landscape, cover_photo, share_footer_text, video_max_mb, created_at, updated_at) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)';
    db()->prepare($sql)->execute([
        $clientUserId, $data['pet_name'], $data['pet_birth_date'], $data['pet_memorial_date'], $data['short_tribute'], $data['final_letter'],
        $data['video_type'], $data['video_url'], $data['youtube_embed_url'], $data['video_file'], $data['bg_image_portrait'], $data['bg_image_landscape'],
        $data['cover_photo'], $data['share_footer_text'], $data['video_max_mb'], now(), now(),
    ]);
    return (int)db()->lastInsertId();
}

function normalize_youtube_embed_url(string $url): string
{
    $url = trim($url);
    if ($url === '') return '';
    if (preg_match('~(?:youtube\.com/watch\?v=|youtu\.be/)([A-Za-z0-9_-]{6,})~', $url, $m)) {
        return 'https://www.youtube.com/embed/' . $m[1];
    }
    if (strpos($url, 'youtube.com/embed/') !== false) {
        return $url;
    }
    return $url;
}

function qrcode_data_uri(string $text): string
{
    return 'https://api.qrserver.com/v1/create-qr-code/?size=220x220&data=' . rawurlencode($text);
}

function delete_memorial_message(int $messageId, int $memorialId): void
{
    $stmt = db()->prepare('DELETE FROM memorial_messages WHERE id = ? AND memorial_page_id = ?');
    $stmt->execute([$messageId, $memorialId]);
}

function approve_memorial_message(int $messageId, int $memorialId): void
{
    $stmt = db()->prepare('UPDATE memorial_messages SET is_approved = 1 WHERE id = ? AND memorial_page_id = ?');
    $stmt->execute([$messageId, $memorialId]);
}

function fetch_recent_audit_logs(int $limit = 200): array
{
    $stmt = db()->query('SELECT a.*, u.full_name AS actor_name FROM audit_logs a LEFT JOIN users u ON u.id = a.actor_user_id ORDER BY a.id DESC LIMIT ' . (int)$limit);
    return $stmt->fetchAll();
}
