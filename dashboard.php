<?php
require_once __DIR__ . '/includes/auth.php';
require_login();
refresh_logged_user();
send_security_headers();
$user = current_user();
$clientGuid = $user['client_guid'] ?? '';
if (is_admin() && isset($_GET['clientguid']) && $_GET['clientguid'] !== '') {
    $clientGuid = $_GET['clientguid'];
}
$clientOwner = $user;
if (is_admin() && $clientGuid !== '') {
    $targetClient = fetch_client_by_guid($clientGuid);
    if ($targetClient) {
        $clientOwner = $targetClient;
    }
}
$link = $clientGuid ? public_memorial_url($clientGuid) : '';
$targetMemorial = $clientOwner ? fetch_memorial_by_client_id((int)$clientOwner['id']) : null;
$accessSummary = $clientOwner ? memorial_public_access_summary((int)$clientOwner['id'], $targetMemorial ?: null) : ['label' => 'Unavailable', 'is_public' => false];
$latestSubscription = $clientOwner ? fetch_latest_subscription_for_user((int)$clientOwner['id']) : null;
$qrDownloadName = preg_replace('/[^A-Za-z0-9]+/', '-', trim((string)($clientOwner['full_name'] ?? 'client')));
$qrDownloadName = trim((string)$qrDownloadName, '-');
if ($qrDownloadName === '') {
    $qrDownloadName = 'client';
}
$qrDownloadName .= '-furever-memories-qr.png';
$success = flash_get('success');
$warning = flash_get('warning');
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Dashboard - <?= e(APP_NAME) ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/site.css">
</head>
<body class="admin-page">
<?php include __DIR__ . '/includes/topbar.php'; ?>
<div class="container py-4 admin-shell">
    <?php if ($success): ?><div class="alert alert-success"><?= e($success) ?></div><?php endif; ?>
    <?php if ($warning): ?><div class="alert alert-warning"><?= e($warning) ?></div><?php endif; ?>
    <div class="row g-4">
        <div class="col-lg-8">
            <div class="card border-0 shadow-sm rounded-4">
                <div class="card-body p-4">
                    <h1 class="h3 mb-2">Welcome, <?= e($user['full_name']) ?></h1>
                    <p class="text-muted mb-3">Role: <?= e(ucfirst($user['role'])) ?></p>
                    <?php if (is_client()): ?>
                        <div class="mb-3">
                            <span class="badge <?= !empty($user['is_email_verified']) ? 'text-bg-success' : 'text-bg-warning' ?>">
                                <?= !empty($user['is_email_verified']) ? 'Email verified' : 'Email not verified' ?>
                            </span>
                        </div>
                    <?php endif; ?>
                    <div class="row g-3 mb-4">
                        <div class="col-md-6">
                            <div class="p-3 rounded-4 border bg-white h-100">
                                <div class="small text-uppercase text-muted mb-2">Memorial access</div>
                                <div class="fw-semibold"><?= e($accessSummary['label']) ?></div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="p-3 rounded-4 border bg-white h-100">
                                <div class="small text-uppercase text-muted mb-2">Subscription status</div>
                                <?php if ($latestSubscription): ?>
                                    <span class="badge <?= e(subscription_status_badge_class((string)$latestSubscription['status'])) ?>"><?= e(ucwords(str_replace('_', ' ', (string)$latestSubscription['status']))) ?></span>
                                    <div class="small text-muted mt-2"><?= e($latestSubscription['plan_name'] ?? 'Plan') ?></div>
                                <?php else: ?>
                                    <div class="fw-semibold">No subscription request yet</div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                    <?php if ($link): ?>
                        <div class="mb-3">
                            <label class="form-label"><?= !empty($accessSummary['is_public']) ? 'Public Link' : 'Private Preview Link' ?></label>
                            <div class="input-group">
                                <input type="text" class="form-control" id="publicLink" value="<?= e($link) ?>" readonly>
                                <button type="button" class="btn btn-outline-secondary" onclick="navigator.clipboard.writeText(document.getElementById('publicLink').value)">Copy Link</button>
                            </div>
                            <?php if (empty($accessSummary['is_public'])): ?>
                                <div class="form-text">This link is currently viewable only by the client owner and administrators.</div>
                            <?php endif; ?>
                        </div>
                        <?php if (!empty($accessSummary['is_public'])): ?>
                            <div class="row g-3 align-items-center">
                                <div class="col-md-4">
                                    <img src="<?= e(qrcode_data_uri($link)) ?>" class="img-fluid rounded-3 border" alt="QR code" id="dashboardQrImage">
                                </div>
                                <div class="col-md-8">
                                    <div class="d-flex flex-wrap gap-2">
                                        <a href="<?= e($link) ?>" target="_blank" class="btn btn-dark">Open Public Page</a>
                                        <button type="button" class="btn btn-success" id="downloadQrBtn">Download QR Code</button>
                                    </div>
                                </div>
                            </div>
                        <?php else: ?>
                            <div class="d-flex flex-wrap gap-2">
                                <a href="<?= e($link) ?>" target="_blank" class="btn btn-dark">Open Private Preview</a>
                                <?php if (is_client()): ?>
                                    <a href="subscription.php" class="btn btn-outline-dark">Billing &amp; Access</a>
                                <?php endif; ?>
                            </div>
                        <?php endif; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        <div class="col-lg-4">
            <div class="card border-0 shadow-sm rounded-4">
                <div class="card-body p-4">
                    <h2 class="h5">Quick Actions</h2>
                    <div class="d-grid gap-2">
                        <?php if (is_admin()): ?><a href="admin_clients.php" class="btn btn-outline-dark">Manage Clients</a><?php endif; ?>
                        <a href="memorial_edit.php<?= (is_admin() && $clientGuid !== '') ? '?clientguid=' . urlencode($clientGuid) : '' ?>" class="btn btn-outline-dark">Edit Memorial Page</a>
                        <?php if (is_client()): ?><a href="client_profile.php" class="btn btn-outline-dark">Update Profile</a><?php endif; ?>
                        <?php if (is_client()): ?><a href="subscription.php" class="btn btn-outline-dark">Billing &amp; Access</a><?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<?php if ($link && !empty($accessSummary['is_public'])): ?>
<script>
document.getElementById('downloadQrBtn').addEventListener('click', function () {
    const qrImage = document.getElementById('dashboardQrImage');
    if (!qrImage || !qrImage.src) {
        return;
    }

    const a = document.createElement('a');
    a.href = qrImage.src;
    a.download = <?= json_encode($qrDownloadName) ?>;
    document.body.appendChild(a);
    a.click();
    a.remove();
});
</script>
<?php endif; ?>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
