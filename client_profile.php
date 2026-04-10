<?php
require_once __DIR__ . '/includes/auth.php';
require_login();
if (!is_client()) redirect('dashboard.php');
refresh_logged_user();
send_security_headers();

$user = current_user();
$success = flash_get('success');
$warning = flash_get('warning');
$error = '';
$verificationLink = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf_or_fail();
    try {
        $action = $_POST['action'] ?? 'save';
        if ($action === 'save') {
            $fullName = trim($_POST['full_name'] ?? '');
            $contactNumber = trim($_POST['contact_number'] ?? '');
            $address = trim($_POST['address'] ?? '');
            $email = mb_strtolower(trim($_POST['email'] ?? ''));
            $oldEmail = $user['email'];

            $existing = fetch_user_by_email($email);
            if ($existing && (int)$existing['id'] !== (int)$user['id']) {
                throw new RuntimeException('That email is already used by another account.');
            }

            $verified = $user['is_email_verified'];
            $verifiedAt = $user['email_verified_at'];
            if ($email !== $oldEmail) {
                $verified = 0;
                $verifiedAt = null;
            }

            db()->prepare('UPDATE users SET full_name=?, contact_number=?, address=?, email=?, is_email_verified=?, email_verified_at=?, updated_at=? WHERE id=?')
                ->execute([$fullName, $contactNumber, $address, $email, $verified, $verifiedAt, now(), $user['id']]);
            log_audit('client.profile.update', 'Client profile updated.', 'user', (int)$user['id'], ['email_changed' => $email !== $oldEmail]);
            if ($email !== $oldEmail) {
                $issued = issue_email_verification_token((int)$user['id'], $email);
                $verificationLink = $issued['url'];
                $fresh = fetch_user_by_id((int)$user['id']);
                $sent = send_verification_email($fresh, $verificationLink);
                log_audit('client.verification.resend', 'Verification sent after email change.', 'user', (int)$user['id'], ['sent' => $sent]);
                $success = 'Profile updated. Please verify your new email address.';
            } else {
                $success = 'Profile updated.';
            }
            require_once __DIR__ . '/includes/auth.php';
            refresh_logged_user();
            $user = current_user();
        }

        if ($action === 'resend_verification') {
            $issued = issue_email_verification_token((int)$user['id'], $user['email']);
            $verificationLink = $issued['url'];
            $sent = send_verification_email(fetch_user_by_id((int)$user['id']), $verificationLink);
            log_audit('client.verification.resend', 'Client requested verification resend.', 'user', (int)$user['id'], ['sent' => $sent]);
            $success = $sent ? 'Verification email resent.' : 'Mail sending failed on this server. Manual verification link generated below.';
        }
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
    <title>My Profile - <?= e(APP_NAME) ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/site.css">
</head>
<body class="admin-page">
<?php include __DIR__ . '/includes/topbar.php'; ?>
<div class="container py-4 admin-shell">
    <div class="row justify-content-center">
        <div class="col-lg-7">
            <div class="card border-0 shadow-sm rounded-4">
                <div class="card-body p-4">
                    <h1 class="h4 mb-3">My Profile</h1>
                    <?php if ($success): ?><div class="alert alert-success"><?= e($success) ?></div><?php endif; ?>
                    <?php if ($warning): ?><div class="alert alert-warning"><?= e($warning) ?></div><?php endif; ?>
                    <?php if ($error): ?><div class="alert alert-danger"><?= e($error) ?></div><?php endif; ?>
                    <?php if ($verificationLink): ?><div class="alert alert-secondary small">Manual verification link:<br><a href="<?= e($verificationLink) ?>" target="_blank"><?= e($verificationLink) ?></a></div><?php endif; ?>
                    <div class="mb-3">
                        <span class="badge <?= !empty($user['is_email_verified']) ? 'text-bg-success' : 'text-bg-warning' ?>"><?= !empty($user['is_email_verified']) ? 'Verified' : 'Pending verification' ?></span>
                    </div>
                    <form method="post">
                        <?= csrf_input() ?>
                        <input type="hidden" name="action" value="save">
                        <div class="mb-3"><label class="form-label">Full Name</label><input name="full_name" value="<?= e($user['full_name']) ?>" class="form-control"></div>
                        <div class="mb-3"><label class="form-label">Contact Number</label><input name="contact_number" value="<?= e($user['contact_number']) ?>" class="form-control"></div>
                        <div class="mb-3"><label class="form-label">Address</label><textarea name="address" class="form-control" rows="2"><?= e($user['address']) ?></textarea></div>
                        <div class="mb-3"><label class="form-label">Email</label><input type="email" name="email" value="<?= e($user['email']) ?>" class="form-control"></div>
                        <button class="btn btn-dark">Save Changes</button>
                    </form>
                    <?php if (empty($user['is_email_verified'])): ?>
                        <form method="post" class="mt-3">
                            <?= csrf_input() ?>
                            <input type="hidden" name="action" value="resend_verification">
                            <button class="btn btn-outline-primary">Resend verification email</button>
                        </form>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
