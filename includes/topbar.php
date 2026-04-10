<?php
require_once __DIR__ . '/functions.php';
$u = current_user();
$currentPage = basename((string)($_SERVER['PHP_SELF'] ?? ''));
$requestedClientGuid = trim((string)($_GET['clientguid'] ?? $_GET['c'] ?? ''));
if ($requestedClientGuid === '' && is_admin()) {
    $requestedClientGuid = trim((string)($_SESSION['admin_current_client_guid'] ?? ''));
}
if ($requestedClientGuid !== '' && is_admin()) {
    $_SESSION['admin_current_client_guid'] = $requestedClientGuid;
}
$dashboardUrl = 'dashboard.php' . ((is_admin() && $requestedClientGuid !== '') ? '?clientguid=' . urlencode($requestedClientGuid) : '');
$pageBuilderUrl = is_admin()
    ? ($requestedClientGuid !== '' ? 'memorial_edit.php?clientguid=' . urlencode($requestedClientGuid) : 'admin_clients.php')
    : 'memorial_edit.php';
?>
<nav class="navbar navbar-expand-lg navbar-light sticky-top furever-admin-nav">
    <div class="container admin-nav-shell">
        <a class="navbar-brand admin-brand" href="<?= e($dashboardUrl) ?>">
            <img src="assets/images/logo-furever-memories.png" alt="FurEver Memories logo" class="admin-brand-logo">
            <span class="admin-brand-text">
                <small class="admin-brand-kicker">Forever in our hearts</small>
                <strong><?= e(APP_NAME) ?></strong>
            </span>
        </a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navMain">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="navMain">
            <ul class="navbar-nav ms-auto align-items-lg-center gap-lg-2">
                <li class="nav-item"><a class="nav-link admin-nav-link<?= $currentPage === 'dashboard.php' ? ' active' : '' ?>" href="<?= e($dashboardUrl) ?>">Dashboard</a></li>
                <?php if (is_admin()): ?>
                    <li class="nav-item"><a class="nav-link admin-nav-link<?= $currentPage === 'admin_clients.php' ? ' active' : '' ?>" href="admin_clients.php">Clients</a></li>
                    <li class="nav-item"><a class="nav-link admin-nav-link<?= $currentPage === 'audit_logs.php' ? ' active' : '' ?>" href="audit_logs.php">Audit Logs</a></li>
                <?php endif; ?>
                <?php if (is_client()): ?>
                    <li class="nav-item"><a class="nav-link admin-nav-link<?= $currentPage === 'client_profile.php' ? ' active' : '' ?>" href="client_profile.php">My Profile</a></li>
                    <li class="nav-item"><a class="nav-link admin-nav-link<?= $currentPage === 'subscription.php' ? ' active' : '' ?>" href="subscription.php">Billing &amp; Access</a></li>
                <?php endif; ?>
                <li class="nav-item"><a class="nav-link admin-nav-link<?= in_array($currentPage, ['memorial_edit.php', 'moderation.php'], true) ? ' active' : '' ?>" href="<?= e($pageBuilderUrl) ?>">Page Builder</a></li>
                <li class="nav-item"><a class="nav-link admin-nav-link<?= $currentPage === 'change_password.php' ? ' active' : '' ?>" href="change_password.php">Change Password</a></li>
                <li class="nav-item"><span class="badge admin-role-badge"><?= e(ucfirst((string)($u['role'] ?? ''))) ?></span></li>
                <li class="nav-item"><a class="nav-link admin-nav-link admin-logout-link" href="logout.php">Logout</a></li>
            </ul>
        </div>
    </div>
</nav>
