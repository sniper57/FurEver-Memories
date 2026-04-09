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
</head>
<body class="bg-light">
<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-md-5">
            <div class="card shadow-sm border-0 rounded-4">
                <div class="card-body p-4">
                    <h1 class="h3 mb-3 text-center"><?= e(APP_NAME) ?></h1>
                    <p class="text-muted text-center">Administrator / Client Login</p>
                    <?php if ($warning): ?><div class="alert alert-warning"><?= e($warning) ?></div><?php endif; ?>
                    <?php if ($error): ?><div class="alert alert-danger"><?= e($error) ?></div><?php endif; ?>
                    <form method="post">
                        <?= csrf_input() ?>
                        <div class="mb-3">
                            <label class="form-label">Email</label>
                            <input type="email" name="email" class="form-control" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Password</label>
                            <input type="password" name="password" class="form-control" required>
                        </div>
                        <button class="btn btn-dark w-100">Login</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
</body>
</html>
