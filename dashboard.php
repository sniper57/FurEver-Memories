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
$link = $clientGuid ? public_memorial_url($clientGuid) : '';
$qrDownloadName = preg_replace('/[^A-Za-z0-9]+/', '-', trim((string)($user['full_name'] ?? 'client')));
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
                    <?php if ($link): ?>
                        <div class="mb-3">
                            <label class="form-label">Public Link</label>
                            <div class="input-group">
                                <input type="text" class="form-control" id="publicLink" value="<?= e($link) ?>" readonly>
                                <button type="button" class="btn btn-outline-secondary" onclick="navigator.clipboard.writeText(document.getElementById('publicLink').value)">Copy Link</button>
                            </div>
                        </div>
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
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<?php if ($link): ?>
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
