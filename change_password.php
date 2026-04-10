<?php
require_once __DIR__ . '/includes/auth.php';
require_login();
send_security_headers();
$success = '';
$error = '';
$user = current_user();
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf_or_fail();
    try {
        $current = (string)($_POST['current_password'] ?? '');
        $new = (string)($_POST['new_password'] ?? '');
        $confirm = (string)($_POST['confirm_password'] ?? '');
        $fresh = fetch_user_by_id((int)$user['id']);
        if (!$fresh || !password_verify($current, $fresh['password_hash'])) {
            throw new RuntimeException('Current password is incorrect.');
        }
        if ($new !== $confirm) {
            throw new RuntimeException('New password and confirmation do not match.');
        }
        if (!password_is_strong_enough($new)) {
            throw new RuntimeException('New password must be at least 8 chars and include uppercase, lowercase, and a number.');
        }
        db()->prepare('UPDATE users SET password_hash=?, updated_at=? WHERE id=?')->execute([password_hash($new, PASSWORD_DEFAULT), now(), $user['id']]);
        log_audit('password.change', 'User changed password.', 'user', (int)$user['id']);
        $success = 'Password updated successfully.';
    } catch (Throwable $e) {
        $error = $e->getMessage();
    }
}
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Change Password - <?= e(APP_NAME) ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/site.css">
</head>
<body class="admin-page">
<?php include __DIR__ . '/includes/topbar.php'; ?>
<div class="container py-4 admin-shell">
    <div class="row justify-content-center">
        <div class="col-lg-6">
            <div class="card border-0 shadow-sm rounded-4">
                <div class="card-body p-4">
                    <h1 class="h4 mb-3">Change Password</h1>
                    <?php if ($success): ?><div class="alert alert-success"><?= e($success) ?></div><?php endif; ?>
                    <?php if ($error): ?><div class="alert alert-danger"><?= e($error) ?></div><?php endif; ?>
                    <form method="post">
                        <?= csrf_input() ?>
                        <div class="mb-3"><label class="form-label">Current Password</label><input type="password" name="current_password" class="form-control" required></div>
                        <div class="mb-3"><label class="form-label">New Password</label><input type="password" name="new_password" class="form-control" required></div>
                        <div class="mb-3"><label class="form-label">Confirm New Password</label><input type="password" name="confirm_password" class="form-control" required></div>
                        <button class="btn btn-dark">Update Password</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
