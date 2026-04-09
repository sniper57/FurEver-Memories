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
                'pet_name' => '', 'pet_birth_date' => null, 'pet_memorial_date' => null, 'short_tribute' => '', 'final_letter' => '',
                'video_type' => 'none', 'video_url' => '', 'video_file' => '', 'bg_image_portrait' => '', 'bg_image_landscape' => '',
                'cover_photo' => '', 'share_footer_text' => 'Created with love through FurEver Memories', 'youtube_embed_url' => '', 'video_max_mb' => DEFAULT_VIDEO_MAX_MB,
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
</head>
<body class="bg-light">
<?php include __DIR__ . '/includes/topbar.php'; ?>
<div class="container py-4">
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
                            <thead><tr><th>Name</th><th>Email</th><th>Status</th><th>GUID</th><th>Actions</th></tr></thead>
                            <tbody>
                            <?php foreach ($clients as $client): ?>
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
                                    <td><small><?= e($client['client_guid']) ?></small></td>
                                    <td class="text-nowrap">
                                        <a class="btn btn-sm btn-outline-dark" href="memorial_edit.php?clientguid=<?= e($client['client_guid']) ?>">Configure</a>
                                        <a class="btn btn-sm btn-outline-secondary" target="_blank" href="<?= e(public_memorial_url($client['client_guid'])) ?>">View</a>
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
</body>
</html>
