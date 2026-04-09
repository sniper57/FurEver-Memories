<?php
require_once __DIR__ . '/includes/functions.php';
send_security_headers();
$token = trim($_GET['token'] ?? '');
$status = 'invalid';
$message = 'This verification link is invalid or expired.';
if ($token !== '') {
    $record = verify_email_token($token);
    if ($record) {
        mark_email_verified((int)$record['user_id'], (int)$record['id']);
        log_audit('email.verify', 'Email verification completed.', 'user', (int)$record['user_id']);
        $status = 'success';
        $message = 'Email verified successfully. You may now log in.';
    }
}
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Verify Email - <?= e(APP_NAME) ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light d-flex align-items-center" style="min-height:100vh;">
<div class="container">
    <div class="row justify-content-center">
        <div class="col-lg-5">
            <div class="card border-0 shadow-sm rounded-4"><div class="card-body p-4 text-center">
                <h1 class="h3 mb-3">Email Verification</h1>
                <div class="alert <?= $status === 'success' ? 'alert-success' : 'alert-danger' ?>"><?= e($message) ?></div>
                <a href="login.php" class="btn btn-dark">Go to Login</a>
            </div></div>
        </div>
    </div>
</div>
</body>
</html>
