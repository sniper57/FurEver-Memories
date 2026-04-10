<?php
require_once __DIR__ . '/includes/auth.php';
require_admin();
send_security_headers();

$success = '';
$error = '';
$verificationLink = '';
$editingClientId = (int)($_GET['edit'] ?? 0);
$editingClient = $editingClientId > 0 ? fetch_user_by_id($editingClientId) : null;
if ($editingClient && ($editingClient['role'] ?? '') !== 'client') {
    $editingClient = null;
    $editingClientId = 0;
}

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

        if ($action === 'update_client') {
            $clientId = (int)($_POST['client_id'] ?? 0);
            $result = update_client_account_by_admin($clientId, $_POST);
            $updatedClient = $result['client'] ?? null;
            if (!$updatedClient) {
                throw new RuntimeException('Client not found after update.');
            }

            $editingClientId = (int)$updatedClient['id'];
            $editingClient = $updatedClient;
            $verificationLink = (string)($result['verification_link'] ?? '');
            log_audit('client.update', 'Administrator updated client account.', 'user', $editingClientId, [
                'email_changed' => !empty($result['email_changed']),
                'verification_sent' => !empty($result['verification_sent']),
            ]);
            $success = !empty($result['email_changed'])
                ? 'Client updated successfully. The new email address must be verified before client login continues.'
                : 'Client updated successfully.';
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

        if ($action === 'delete_client') {
            $clientId = (int)($_POST['client_id'] ?? 0);
            $deleted = delete_client_account($clientId);
            log_audit('client.delete', 'Administrator deleted client account.', 'user', $clientId, [
                'email' => $deleted['client']['email'] ?? null,
                'client_guid' => $deleted['client_guid'] ?? null,
                'memorial_id' => $deleted['memorial_id'] ?? null,
            ]);
            if ($editingClientId === $clientId) {
                $editingClientId = 0;
                $editingClient = null;
            }
            $success = 'Client deleted successfully.';
        }
    } catch (Throwable $e) {
        $error = $e->getMessage();
    }
}

$clients = fetch_all_clients();
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
<div class="container-fluid py-4 admin-shell">
    <div class="row g-4">
        <div class="col-lg-4">
            <div class="card border-0 shadow-sm rounded-4">
                <div class="card-body p-4">
                    <h1 class="h4 mb-3"><?= $editingClient ? 'Edit Client' : 'Create Client' ?></h1>
                    <?php if ($success): ?><div class="alert alert-success"><?= e($success) ?></div><?php endif; ?>
                    <?php if ($error): ?><div class="alert alert-danger"><?= e($error) ?></div><?php endif; ?>
                    <?php if ($verificationLink): ?><div class="alert alert-secondary small">Manual verification link:<br><a href="<?= e($verificationLink) ?>" target="_blank"><?= e($verificationLink) ?></a></div><?php endif; ?>
                    <form method="post">
                        <?= csrf_input() ?>
                        <input type="hidden" name="action" value="<?= $editingClient ? 'update_client' : 'create' ?>">
                        <?php if ($editingClient): ?>
                            <input type="hidden" name="client_id" value="<?= (int)$editingClient['id'] ?>">
                        <?php endif; ?>
                        <div class="mb-3"><label class="form-label">Full Name</label><input name="full_name" class="form-control" value="<?= e($editingClient['full_name'] ?? '') ?>" required></div>
                        <div class="mb-3"><label class="form-label">Contact Number</label><input name="contact_number" class="form-control" value="<?= e($editingClient['contact_number'] ?? '') ?>"></div>
                        <div class="mb-3"><label class="form-label">Address</label><textarea name="address" class="form-control" rows="2"><?= e($editingClient['address'] ?? '') ?></textarea></div>
                        <div class="mb-3"><label class="form-label">Email</label><input type="email" name="email" class="form-control" value="<?= e($editingClient['email'] ?? '') ?>" required></div>
                        <?php if (!$editingClient): ?>
                            <div class="mb-3"><label class="form-label">Password</label><input type="text" name="password" class="form-control" required></div>
                        <?php else: ?>
                            <div class="alert alert-light border small">Changing the email will reset verification and send a fresh verification email.</div>
                        <?php endif; ?>
                        <div class="d-flex gap-2">
                            <button class="btn btn-dark flex-grow-1"><?= $editingClient ? 'Save Client Changes' : 'Create Client' ?></button>
                            <?php if ($editingClient): ?><a href="admin_clients.php" class="btn btn-outline-secondary">Cancel</a><?php endif; ?>
                        </div>
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
                                $clientQrDownloadName = preg_replace('/[^A-Za-z0-9]+/', '-', trim((string)$client['full_name']));
                                $clientQrDownloadName = trim((string)$clientQrDownloadName, '-');
                                if ($clientQrDownloadName === '') {
                                    $clientQrDownloadName = 'client';
                                }
                                $clientQrDownloadName .= '-furever-memories-qr.png';
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
                                                <div class="small mt-1">
                                                    <button type="button" class="btn btn-link btn-sm p-0 payment-proof-preview" data-bs-toggle="modal" data-bs-target="#paymentProofModal" data-proof-url="<?= e(UPLOAD_URL . '/' . ltrim((string)$latestPayment['proof_path'], '/')) ?>">View payment proof</button>
                                                </div>
                                            <?php endif; ?>
                                        <?php endif; ?>
                                    </td>
                                    <td class="text-nowrap">
                                        <div class="d-flex flex-wrap gap-2">
                                            <a class="btn btn-sm btn-outline-primary" href="admin_clients.php?edit=<?= (int)$client['id'] ?>">Edit</a>
                                            <a class="btn btn-sm btn-outline-dark" href="memorial_edit.php?clientguid=<?= e($client['client_guid']) ?>">Configure</a>
                                            <a class="btn btn-sm btn-outline-secondary" target="_blank" href="<?= e(public_memorial_url($client['client_guid'])) ?>">View</a>
                                            <button type="button" class="btn btn-sm btn-outline-success qr-download-btn" data-qr-link="<?= e(public_memorial_url($client['client_guid'])) ?>" data-qr-filename="<?= e($clientQrDownloadName) ?>">Download QR</button>
                                        </div>
                                        <div class="d-flex flex-wrap gap-2 mt-2">
                                            <?php if ($latestPayment && ($latestPayment['status'] ?? '') === 'pending'): ?>
                                                <form method="post" class="d-inline" data-swal-confirm="Approve this payment and enable public sharing for this client?" data-swal-title="Approve payment?" data-swal-confirm-text="Yes, approve">
                                                    <?= csrf_input() ?>
                                                    <input type="hidden" name="action" value="approve_payment">
                                                    <input type="hidden" name="payment_id" value="<?= (int)$latestPayment['id'] ?>">
                                                    <button class="btn btn-sm btn-success">Approve Payment</button>
                                                </form>
                                                <form method="post" class="d-inline" data-swal-confirm="Reject this payment submission?" data-swal-title="Reject payment?" data-swal-confirm-text="Yes, reject">
                                                    <?= csrf_input() ?>
                                                    <input type="hidden" name="action" value="reject_payment">
                                                    <input type="hidden" name="payment_id" value="<?= (int)$latestPayment['id'] ?>">
                                                    <button class="btn btn-sm btn-outline-danger">Reject Payment</button>
                                                </form>
                                            <?php endif; ?>
                                            <?php if (empty($clientMemorial['public_access_override'])): ?>
                                                <form method="post" class="d-inline" data-swal-confirm="Enable public access for this client's memorial?" data-swal-title="Enable public access?" data-swal-confirm-text="Yes, enable">
                                                    <?= csrf_input() ?>
                                                    <input type="hidden" name="action" value="enable_public_override">
                                                    <input type="hidden" name="client_id" value="<?= (int)$client['id'] ?>">
                                                    <button class="btn btn-sm btn-outline-primary">Enable Public</button>
                                                </form>
                                            <?php else: ?>
                                                <form method="post" class="d-inline" data-swal-confirm="Disable the manual public access override for this client?" data-swal-title="Disable public override?" data-swal-confirm-text="Yes, disable">
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
                                        <form method="post" class="d-inline-block mt-2" data-swal-confirm="Delete this client and all related memorial data? This cannot be undone." data-swal-title="Delete client?" data-swal-confirm-text="Yes, delete client">
                                            <?= csrf_input() ?>
                                            <input type="hidden" name="action" value="delete_client">
                                            <input type="hidden" name="client_id" value="<?= (int)$client['id'] ?>">
                                            <button class="btn btn-sm btn-outline-danger">Delete Client</button>
                                        </form>
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
<div id="adminQrScratch" class="position-absolute top-0 start-0 opacity-0 pe-none" aria-hidden="true"></div>
<div class="modal fade" id="paymentProofModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content rounded-4 border-0">
            <div class="modal-header">
                <h2 class="modal-title h5">Payment Proof</h2>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <img src="" alt="Payment proof preview" class="img-fluid rounded-4 w-100" id="paymentProofImage">
                <a href="#" target="_blank" rel="noopener noreferrer" class="btn btn-outline-dark btn-sm mt-3" id="paymentProofOpen">Open image in new tab</a>
            </div>
        </div>
    </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/qrcodejs/1.0.0/qrcode.min.js"></script>
<?php include __DIR__ . '/includes/ui_feedback_assets.php'; ?>
<script>
const paymentProofModal = document.getElementById('paymentProofModal');
if (paymentProofModal) {
    paymentProofModal.addEventListener('show.bs.modal', function(event) {
        const trigger = event.relatedTarget;
        const proofUrl = trigger ? trigger.getAttribute('data-proof-url') : '';
        const image = document.getElementById('paymentProofImage');
        const openLink = document.getElementById('paymentProofOpen');

        if (image) {
            image.src = proofUrl || '';
        }
        if (openLink) {
            openLink.href = proofUrl || '#';
        }
    });
}

document.querySelectorAll('.qr-download-btn').forEach((button) => {
    button.addEventListener('click', function() {
        const link = this.getAttribute('data-qr-link') || '';
        const filename = this.getAttribute('data-qr-filename') || 'furever-memories-qr.png';
        const scratch = document.getElementById('adminQrScratch');
        if (!link || !scratch || typeof QRCode === 'undefined') {
            return;
        }

        scratch.innerHTML = '';
        new QRCode(scratch, { text: link, width: 512, height: 512 });

        window.setTimeout(function() {
            const canvas = scratch.querySelector('canvas');
            const image = scratch.querySelector('img');
            const dataUrl = canvas ? canvas.toDataURL('image/png') : (image ? image.src : '');
            if (!dataUrl) {
                return;
            }

            const a = document.createElement('a');
            a.href = dataUrl;
            a.download = filename;
            document.body.appendChild(a);
            a.click();
            a.remove();
        }, 50);
    });
});
</script>
</body>
</html>
