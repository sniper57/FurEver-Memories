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
$dashboardStats = [
    'views' => 0,
    'candles' => 0,
    'hearts' => 0,
    'messages' => 0,
];
$viewTrend = [
    'labels' => [],
    'data' => [],
];
if ($targetMemorial) {
    $memorialId = (int)$targetMemorial['id'];
    $dashboardStats = [
        'views' => count_memorial_views($memorialId),
        'candles' => count_candles($memorialId),
        'hearts' => count_hearts($memorialId),
        'messages' => count_messages($memorialId, false),
    ];
    $viewTrend = memorial_daily_views($memorialId, 7);
}
$reactionChart = [
    'labels' => ['Light a Candle', 'Send a Heart', 'Messages'],
    'data' => [$dashboardStats['candles'], $dashboardStats['hearts'], $dashboardStats['messages']],
];
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
<div class="container-fluid py-4 admin-shell">
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

                    <div class="row g-3 mb-4">
                        <div class="col-6 col-xl-3">
                            <div class="dashboard-stat-card">
                                <span class="dashboard-stat-icon">&#128065;</span>
                                <div class="dashboard-stat-value" id="dashboardViewsCount"><?= e((string)$dashboardStats['views']) ?></div>
                                <div class="dashboard-stat-label">Total Visits</div>
                            </div>
                        </div>
                        <div class="col-6 col-xl-3">
                            <div class="dashboard-stat-card">
                                <span class="dashboard-stat-icon">&#128367;</span>
                                <div class="dashboard-stat-value" id="dashboardCandlesCount"><?= e((string)$dashboardStats['candles']) ?></div>
                                <div class="dashboard-stat-label">Light a Candle</div>
                            </div>
                        </div>
                        <div class="col-6 col-xl-3">
                            <div class="dashboard-stat-card">
                                <span class="dashboard-stat-icon">&#9829;</span>
                                <div class="dashboard-stat-value" id="dashboardHeartsCount"><?= e((string)$dashboardStats['hearts']) ?></div>
                                <div class="dashboard-stat-label">Send Heart</div>
                            </div>
                        </div>
                        <div class="col-6 col-xl-3">
                            <div class="dashboard-stat-card">
                                <span class="dashboard-stat-icon">&#9993;</span>
                                <div class="dashboard-stat-value" id="dashboardMessagesCount"><?= e((string)$dashboardStats['messages']) ?></div>
                                <div class="dashboard-stat-label">Message Wall</div>
                            </div>
                        </div>
                    </div>

                    <div class="row g-3 mb-4">
                        <div class="col-lg-7">
                            <div class="dashboard-chart-card">
                                <div class="dashboard-chart-heading">
                                    <span>Visits over the last 7 days</span>
                                </div>
                                <canvas id="visitsChart" height="140"></canvas>
                            </div>
                        </div>
                        <div class="col-lg-5">
                            <div class="dashboard-chart-card">
                                <div class="dashboard-chart-heading">
                                    <span>Engagement summary</span>
                                </div>
                                <canvas id="engagementChart" height="140"></canvas>
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
                        <div class="row g-3 align-items-center">
                            <div class="col-md-4">
                                <div id="dashboardQrcode" class="rounded-3 border bg-white p-2 d-inline-block"></div>
                            </div>
                            <div class="col-md-8">
                                <div class="d-flex flex-wrap gap-2">
                                    <a href="<?= e($link) ?>" target="_blank" class="btn btn-dark"><?= !empty($accessSummary['is_public']) ? 'Open Public Page' : 'Open Private Preview' ?></a>
                                    <button type="button" class="btn btn-success" id="downloadQrBtn">Download QR Code</button>
                                    <?php if (empty($accessSummary['is_public']) && is_client()): ?>
                                        <a href="subscription.php" class="btn btn-outline-dark">Billing &amp; Access</a>
                                    <?php endif; ?>
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
                        <?php if (is_client()): ?><a href="subscription.php" class="btn btn-outline-dark">Billing &amp; Access</a><?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<?php if ($link): ?>
<script src="https://cdnjs.cloudflare.com/ajax/libs/qrcodejs/1.0.0/qrcode.min.js"></script>
<script>
const dashboardQrContainer = document.getElementById('dashboardQrcode');
if (dashboardQrContainer) {
    new QRCode(dashboardQrContainer, { text: <?= json_encode($link) ?>, width: 220, height: 220 });
}

document.getElementById('downloadQrBtn').addEventListener('click', function () {
    const img = dashboardQrContainer ? dashboardQrContainer.querySelector('img') : null;
    const canvas = dashboardQrContainer ? dashboardQrContainer.querySelector('canvas') : null;
    let dataUrl = '';

    if (img) {
        dataUrl = img.src;
    } else if (canvas) {
        dataUrl = canvas.toDataURL('image/png');
    }

    if (!dataUrl) {
        return;
    }

    const a = document.createElement('a');
    a.href = dataUrl;
    a.download = <?= json_encode($qrDownloadName) ?>;
    document.body.appendChild(a);
    a.click();
    a.remove();
});
</script>
<?php endif; ?>
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.7/dist/chart.umd.min.js"></script>
<script>
const visitChartData = <?= json_encode($viewTrend, JSON_UNESCAPED_SLASHES) ?>;
const reactionChartData = <?= json_encode($reactionChart, JSON_UNESCAPED_SLASHES) ?>;

if (window.Chart) {
    const visitsEl = document.getElementById('visitsChart');
    if (visitsEl) {
        new Chart(visitsEl, {
            type: 'line',
            data: {
                labels: visitChartData.labels,
                datasets: [{
                    label: 'Visits',
                    data: visitChartData.data,
                    borderColor: '#b88145',
                    backgroundColor: 'rgba(184,129,69,.16)',
                    borderWidth: 3,
                    tension: .35,
                    fill: true,
                    pointRadius: 4
                }]
            },
            options: {
                responsive: true,
                plugins: { legend: { display: false } },
                scales: { y: { beginAtZero: true, ticks: { precision: 0 } } }
            }
        });
    }

    const engagementEl = document.getElementById('engagementChart');
    if (engagementEl) {
        window.FM_ENGAGEMENT_CHART = new Chart(engagementEl, {
            type: 'doughnut',
            data: {
                labels: reactionChartData.labels,
                datasets: [{
                    data: reactionChartData.data,
                    backgroundColor: ['#d3a35f', '#9f6a43', '#ead4b8'],
                    borderWidth: 0
                }]
            },
            options: {
                responsive: true,
                plugins: { legend: { position: 'bottom' } },
                cutout: '62%'
            }
        });
    }
}
</script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
window.FM_DASHBOARD_ACTIVITY_CONFIG = {
    endpoint: <?= json_encode('dashboard_activity.php' . ((is_admin() && $clientGuid !== '') ? '?clientguid=' . urlencode($clientGuid) : '')) ?>,
    intervalMs: 8000,
    counts: <?= json_encode($dashboardStats, JSON_UNESCAPED_SLASHES) ?>,
    selectors: {
        views: '#dashboardViewsCount',
        candles: '#dashboardCandlesCount',
        hearts: '#dashboardHeartsCount',
        messages: '#dashboardMessagesCount'
    }
};
</script>
<?php include __DIR__ . '/includes/ui_feedback_assets.php'; ?>
</body>
</html>
