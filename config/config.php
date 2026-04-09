<?php
// ===== DATABASE =====
define('DB_HOST', '127.0.0.1');
define('DB_NAME', 'prod_furever_memories_db');
define('DB_USER', 'momentoshare_user');
define('DB_PASS', '1nc0rr3ct57.');

// ===== APP =====
define('APP_NAME', 'FurEver Memories');
define('BASE_URL', 'http://furevermemories.momentoshare.com');
define('UPLOAD_DIR', __DIR__ . '/../uploads');
define('UPLOAD_URL', rtrim(BASE_URL, '/') . '/uploads');
define('DEFAULT_VIDEO_MAX_MB', 50);
define('DEFAULT_IMAGE_TARGET_MAX_BYTES', 500 * 1024);
define('SESSION_NAME', 'furever_memories_session');
define('CSRF_TOKEN_NAME', '_csrf');
define('BRUTE_FORCE_MAX_ATTEMPTS', 5);
define('BRUTE_FORCE_WINDOW_MINUTES', 15);
define('EMAIL_VERIFICATION_EXPIRY_HOURS', 48);
define('MAIL_FROM_NAME', 'FurEver Memories');
define('MAIL_FROM_EMAIL', 'hello.momentoshare@gmail.com');
define('BREVO_API_KEY', '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'); // set your Brevo API key here
define('BREVO_API_URL', 'https://api.brevo.com/v3/smtp/email');
define('PASSWORD_MIN_LENGTH', 8);

date_default_timezone_set('Asia/Manila');

if (session_status() === PHP_SESSION_NONE) {
    $https = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off');
    session_name(SESSION_NAME);
    session_set_cookie_params([
        'lifetime' => 0,
        'path' => '/',
        'domain' => '',
        'secure' => $https,
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
    session_start();
}
