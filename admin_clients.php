<?php
require_once __DIR__ . '/includes/auth.php';
require_admin();
send_security_headers();

$success = '';
$error = '';
$verificationLink = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf_or_fail();
    try {
        $action = $_POST['action'] ?? 'create';
        if ($action === 'create') {
            $fullName = trim($_POST['full_name'] ?? '');
            $contactNumber = trim($_POST['contact_number'] ?? '');
            $address = trim($_POST['address'] ?? '');
            $email = mb_strtolower(trim($_POST['email'] ?? ''));
            $password = (string)($_POST['password'] ?? '');

            if ($fullName === '' || $email === '' || $password === '') {
                throw new RuntimeException('Full name, email, and password are required.');
            }
            if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                throw new RuntimeException('Invalid email address.');
            }
            if (!password_is_strong_enough($password)) {
                throw new RuntimeException('Password must be at least 8 chars and include uppercase, lowercase, and a number.');
            }
            if (fetch_user_by_email($email)) {
                throw new RuntimeException('Email already exists.');
            }

            $clientGuid = guid();
            db()->prepare('INSERT INTO users (role, client_guid, full_name, contact_number, address, email, password_hash, is_active, is_email_verified, created_at, updated_at) VALUES (?,?,?,?,?,?,?,?,?,?,?)')
                ->execute(['client', $clientGuid, $fullName, $contactNumber, $address, $email, password_hash($password, PASSWORD_DEFAULT), 1, 0, now(), now()]);

            $clientId = (int)db()->lastInsertId();
            upsert_memorial($clientId, [
                'pet_name' => '',
                'pet_birth_date' => null,
                'pet_memorial_date' => null,
                'short_tribute' => '',
                'final_letter' => '',
                'video_type' => 'none',
                'video_url' => '',
                'video_file' => '',
                'bg_image_portrait' => '',
                'bg_image_landscape' => '',
                'cover_photo' => '',
                'share_footer_text' => 'Created with love through FurEver Memories',
                'youtube_embed_url' => '',
                'video_max_mb' => DEFAULT_VIDEO_MAX_MB,
            ]);
            $issued = issue_email_verification_token($clientId, $email);
            $verificationLink = $issued['url'];
            $user = fetch_user_by_id($clientId);
            $sent = send_verification_email($user, $verificationLink);
            log_audit('client.create', 'Administrator created client account.', 'user', $clientId, ['email' => $email, 'verification_sent' => $sent]);
            $success = 'Client created successfully.' . ($sent ? ' Verification email sent.' : ' Mail sending failed on this server, so use the manual verification link below.');
        }

        if ($action === 'resend_verification') {
            $clientId = (int)($_POST['client_id'] ?? 0);
            $user = fetch_user_by_id($clientId);
            if (!$user || $user['role'] !== 'client') {
                throw new RuntimeException('Client not found.');
            }
            $issued = issue_email_verification_token($clientId, $user['email']);
            $verificationLink = $issued['url'];
            $sent = send_verification_email($user, $verificationLink);
            log_audit('client.verification.resend', 'Verification link resent by administrator.', 'user', $clientId, ['sent' => $sent]);
            $success = $sent ? 'Verification email resent.' : 'Mail sending failed on this server. Manual verification link generated below.';
        }

        if ($action === 'approve_payment') {
            $paymentId = (int)($_POST['payment_id'] ?? 0);
            approve_subscription_payment($paymentId, (int)current_user()['id'], trim((string)($_POST['review_notes'] ?? '')));
            log_audit('subscription.payment.approve', 'Administrator approved a subscription payment.', 'subscription_payment', $paymentId);
            $success = 'Payment approved. Public sharing is now enabled through the active subscription.';
        }

        if ($action === 'reject_payment') {
            $paymentId = (int)($_POST['payment_id'] ?? 0);
            reject_subscription_payment($paymentId, (int)current_user()['id'], trim((string)($_POST['review_notes'] ?? '')));
            log_audit('subscription.payment.reject', 'Administrator rejected a subscription payment.', 'subscription_payment', $paymentId);
            $success = 'Payment rejected. The client can submit a new payment proof.';
        }

        if ($action === 'enable_public_override') {
            $clientId = (int)($_POST['client_id'] ?? 0);
            set_memorial_public_override($clientId, true, (int)current_user()['id'], trim((string)($_POST['override_note'] ?? 'Manual admin approval')));
            log_audit('memorial.public_override.enable', 'Administrator enabled manual public access override.', 'user', $clientId);
            $success = 'Manual public access override enabled.';
        }

        if ($action === 'disable_public_override') {
            $clientId = (int)($_POST['client_id'] ?? 0);
            set_memorial_public_override($clientId, false, (int)current_user()['id']);
            log_audit('memorial.public_override.disable', 'Administrator disabled manual public access override.', 'user', $clientId);
            $success = 'Manual public access override disabled.';
        }
    } catch (Throwable $e) {
        $error = $e->getMessage();
    }
}

$clients = db()->query("SELECT * FROM users WHERE role='client' ORDER BY id DESC")->fetchAll();
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Clients - <?= e(APP_NAME) ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/site.css">
</head>
<body class="admin-page">
<?php include __DIR__ . '/includes/topbar.php'; ?>
<div class="container py-4 admin-shell">
    <div class="row g-4">
        <div class="col-lg-4">
            <div class="card border-0 shadow-sm rounded-4">
                <div class="card-body p-4">
                    <h1 class="h4 mb-3">Create Client</h1>
                    <?php if ($success): ?><div class="alert alert-success"><?= e($success) ?></div><?php endif; ?>
                    <?php if ($error): ?><div class="alert alert-danger"><?= e($error) ?></div><?php endif; ?>
                    <?php if ($verificationLink): ?><div class="alert alert-secondary small">Manual verification link:<br><a href="<?= e($verificationLink) ?>" target="_blank"><?= e($verificationLink) ?></a></div><?php endif; ?>
                    <form method="post">
                        <?= csrf_input() ?>
                        <input type="hidden" name="action" value="create">
                        <div class="mb-3"><label class="form-label">Full Name</label><input name="full_name" class="form-control" required></div>
                        <div class="mb-3"><label class="form-label">Contact Number</label><input name="contact_number" class="form-control"></div>
                        <div class="mb-3"><label class="form-label">Address</label><textarea name="address" class="form-control" rows="2"></textarea></div>
                        <div class="mb-3"><label class="form-label">Email</label><input type="email" name="email" class="form-control" required></div>
                        <div class="mb-3"><label class="form-label">Password</label><input type="text" name="password" class="form-control" required></div>
                        <button class="btn btn-dark w-100">Create Client</button>
                    </form>
                </div>
            </div>
        </div>
        <div class="col-lg-8">
            <div class="card border-0 shadow-sm rounded-4">
                <div class="card-body p-4">
                    <h2 class="h4 mb-3">Client List</h2>
                    <div class="table-responsive">
                        <table class="table align-middle">
                            <thead><tr><th>Name</th><th>Email</th><th>Verification</th><th>Access</th><th>Billing</th><th>Actions</th></tr></thead>
                            <tbody>
                            <?php foreach ($clients as $client): ?>
                                <?php
                                $clientMemorial = fetch_memorial_by_client_id((int)$client['id']);
                                $clientAccess = memorial_public_access_summary((int)$client['id'], $clientMemorial ?: null);
                                $latestSubscription = fetch_latest_subscription_for_user((int)$client['id']);
                                $latestPayment = fetch_latest_payment_for_user((int)$client['id']);
                                ?>
                                <tr>
                                    <td><?= e($client['full_name']) ?></td>
                                    <td><?= e($client['email']) ?></td>
                                    <td>
                                        <?php if (!empty($client['is_email_verified'])): ?>
                                            <span class="badge text-bg-success">Verified</span>
                                        <?php else: ?>
                                            <span class="badge text-bg-warning">Pending</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <div><span class="badge <?= e(!empty($clientAccess['is_public']) ? 'text-bg-success' : 'text-bg-secondary') ?>"><?= e($clientAccess['label']) ?></span></div>
                                        <div class="small text-muted mt-1"><?= e($client['client_guid']) ?></div>
                                    </td>
                                    <td>
                                        <?php if ($latestSubscription): ?>
                                            <div><span class="badge <?= e(subscription_status_badge_class((string)$latestSubscription['status'])) ?>"><?= e(ucwords(str_replace('_', ' ', (string)$latestSubscription['status']))) ?></span></div>
                                            <div class="small text-muted mt-1"><?= e($latestSubscription['plan_name'] ?? 'Plan') ?> &bull; PHP <?= e(number_format((float)($latestSubscription['amount'] ?? 0), 2)) ?></div>
                                        <?php else: ?>
                                            <span class="badge text-bg-light">No plan yet</span>
                                        <?php endif; ?>
                                        <?php if ($latestPayment): ?>
                                            <div class="small text-muted mt-1"><?= e(ucfirst(str_replace('_', ' ', (string)$latestPayment['payment_method']))) ?> &bull; Ref <?= e($latestPayment['reference_number']) ?></div>
                                            <?php if (!empty($latestPayment['proof_path'])): ?>
                                                <div class="small mt-1"><a href="<?= e(UPLOAD_URL . '/' . ltrim((string)$latestPayment['proof_path'], '/')) ?>" target="_blank" rel="noopener noreferrer">View payment proof</a></div>
                                            <?php endif; ?>
                                        <?php endif; ?>
                                    </td>
                                    <td class="text-nowrap">
                                        <div class="d-flex flex-wrap gap-2">
                                            <a class="btn btn-sm btn-outline-dark" href="memorial_edit.php?clientguid=<?= e($client['client_guid']) ?>">Configure</a>
                                            <a class="btn btn-sm btn-outline-secondary" target="_blank" href="<?= e(public_memorial_url($client['client_guid'])) ?>">View</a>
                                        </div>
                                        <div class="d-flex flex-wrap gap-2 mt-2">
                                            <?php if ($latestPayment && ($latestPayment['status'] ?? '') === 'pending'): ?>
                                                <form method="post" class="d-inline">
                                                    <?= csrf_input() ?>
                                                    <input type="hidden" name="action" value="approve_payment">
                                                    <input type="hidden" name="payment_id" value="<?= (int)$latestPayment['id'] ?>">
                                                    <button class="btn btn-sm btn-success">Approve Payment</button>
                                                </form>
                                                <form method="post" class="d-inline">
                                                    <?= csrf_input() ?>
                                                    <input type="hidden" name="action" value="reject_payment">
                                                    <input type="hidden" name="payment_id" value="<?= (int)$latestPayment['id'] ?>">
                                                    <button class="btn btn-sm btn-outline-danger">Reject Payment</button>
                                                </form>
                                            <?php endif; ?>
                                            <?php if (empty($clientMemorial['public_access_override'])): ?>
                                                <form method="post" class="d-inline">
                                                    <?= csrf_input() ?>
                                                    <input type="hidden" name="action" value="enable_public_override">
                                                    <input type="hidden" name="client_id" value="<?= (int)$client['id'] ?>">
                                                    <button class="btn btn-sm btn-outline-primary">Enable Public</button>
                                                </form>
                                            <?php else: ?>
                                                <form method="post" class="d-inline">
                                                    <?= csrf_input() ?>
                                                    <input type="hidden" name="action" value="disable_public_override">
                                                    <input type="hidden" name="client_id" value="<?= (int)$client['id'] ?>">
                                                    <button class="btn btn-sm btn-outline-secondary">Disable Public Override</button>
                                                </form>
                                            <?php endif; ?>
                                        </div>
                                        <?php if (empty($client['is_email_verified'])): ?>
                                            <form method="post" class="d-inline">
                                                <?= csrf_input() ?>
                                                <input type="hidden" name="action" value="resend_verification">
                                                <input type="hidden" name="client_id" value="<?= (int)$client['id'] ?>">
                                                <button class="btn btn-sm btn-outline-primary">Resend Verify</button>
                                            </form>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
