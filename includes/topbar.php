<?php require_once __DIR__ . '/functions.php'; $u = current_user(); ?>
<nav class="navbar navbar-expand-lg bg-white border-bottom sticky-top">
    <div class="container">
        <a class="navbar-brand fw-bold" href="dashboard.php"><?= e(APP_NAME) ?></a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navMain">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="navMain">
            <ul class="navbar-nav ms-auto align-items-lg-center gap-lg-2">
                <?php if (is_admin()): ?>
                    <li class="nav-item"><a class="nav-link" href="admin_clients.php">Clients</a></li>
                    <li class="nav-item"><a class="nav-link" href="audit_logs.php">Audit Logs</a></li>
                <?php endif; ?>
                <?php if (is_client()): ?>
                    <li class="nav-item"><a class="nav-link" href="client_profile.php">My Profile</a></li>
                <?php endif; ?>
                <li class="nav-item"><a class="nav-link" href="memorial_edit.php">Page Builder</a></li>
                <li class="nav-item"><a class="nav-link" href="change_password.php">Change Password</a></li>
                <li class="nav-item"><span class="badge text-bg-light border"><?= e($u['role'] ?? '') ?></span></li>
                <li class="nav-item"><a class="nav-link text-danger" href="logout.php">Logout</a></li>
            </ul>
        </div>
    </div>
</nav>
