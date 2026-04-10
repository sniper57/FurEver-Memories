<?php
require_once __DIR__ . '/includes/auth.php';
send_security_headers();

if (is_logged_in()) {
    redirect('dashboard.php');
}

$error = '';
$warning = flash_get('warning');
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf_or_fail();
    $email = mb_strtolower(trim($_POST['email'] ?? ''));
    $password = (string)($_POST['password'] ?? '');
    $ip = client_ip();

    if (login_is_rate_limited($email, $ip)) {
        $error = 'Too many failed attempts. Please try again after ' . BRUTE_FORCE_WINDOW_MINUTES . ' minutes.';
        log_audit('login.blocked', 'Brute-force protection triggered.', 'user', null, ['email' => $email]);
    } else {
        $user = fetch_user_by_email($email);
        if ($user && !empty($user['is_active']) && password_verify($password, $user['password_hash'])) {
            if ($user['role'] === 'client' && empty($user['is_email_verified'])) {
                $error = 'Your account exists but your email is not yet verified.';
                record_login_attempt($email, $ip, False, (int)$user['id']);
                log_audit('login.unverified', 'Blocked login for unverified client.', 'user', (int)$user['id']);
            } else {
                db()->prepare('UPDATE users SET last_login_at = ?, last_login_ip = ?, updated_at = ? WHERE id = ?')->execute([now(), $ip, now(), $user['id']]);
                record_login_attempt($email, $ip, True, (int)$user['id']);
                login_user(fetch_user_by_id((int)$user['id']));
                log_audit('login.success', 'User logged in successfully.', 'user', (int)$user['id']);
                redirect('dashboard.php');
            }
        } else {
            record_login_attempt($email, $ip, False, $user['id'] ?? null);
            $error = 'Invalid login credentials.';
            log_audit('login.failed', 'Failed login attempt.', 'user', $user['id'] ?? null, ['email' => $email]);
        }
    }
}
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= e(APP_NAME) ?> - Login</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/site.css">
</head>
<body class="login-page">
<div class="login-shell">
    <div class="container">
        <div class="login-card">
            <div class="row g-0">
                <div class="col-lg-6">
                    <section class="login-brand-panel">
                        <a href="index.php" class="login-brand-mark text-decoration-none">
                            <img src="assets/images/logo-furever-memories.png" alt="FurEver Memories logo" class="login-brand-logo">
                            <span><?= e(APP_NAME) ?></span>
                        </a>
                        <span class="marketing-kicker">Forever in our hearts</span>
                        <h1 class="login-hero-title">Welcome back to the place where treasured pet memories live on beautifully.</h1>
                        <p class="login-hero-copy">Create memorial pages, share QR memory galleries, manage tribute stories, and keep every loving detail in one warm and peaceful space.</p>
                        <div class="login-brand-pills">
                            <span class="marketing-pill">Digital memorial pages</span>
                            <span class="marketing-pill">QR access</span>
                            <span class="marketing-pill">Printed keepsakes</span>
                        </div>
                        <div class="login-brand-note">
                            Built for modern pet families who want remembrance to feel loving, premium, and celebratory.
                        </div>
                    </section>
                </div>
                <div class="col-lg-6">
                    <section class="login-form-panel">
                        <div class="login-form-wrap">
                            <div class="login-form-copy">
                                <span class="login-form-kicker">Sign In</span>
                                <h2>Administrator / Client Login</h2>
                                <p>Access your dashboard, memorial pages, and client tools.</p>
                            </div>
                            <?php if ($warning): ?><div class="alert alert-warning"><?= e($warning) ?></div><?php endif; ?>
                            <?php if ($error): ?><div class="alert alert-danger"><?= e($error) ?></div><?php endif; ?>
                            <form method="post" class="login-form">
                                <?= csrf_input() ?>
                                <div class="mb-3">
                                    <label class="form-label login-form-label">Email address</label>
                                    <input type="email" name="email" class="form-control login-form-control" required>
                                </div>
                                <div class="mb-4">
                                    <label class="form-label login-form-label">Password</label>
                                    <input type="password" name="password" class="form-control login-form-control" required>
                                </div>
                                <button class="btn login-submit-btn w-100">Login</button>
                            </form>
                            <div class="login-form-footer">
                                <a href="index.php" class="text-decoration-none">Back to homepage</a>
                            </div>
                        </div>
                    </section>
                </div>
            </div>
        </div>
    </div>
</div>
</body>
</html>
