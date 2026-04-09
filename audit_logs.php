<?php
require_once __DIR__ . '/includes/auth.php';
require_admin();
send_security_headers();
$logs = fetch_recent_audit_logs();
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Audit Logs - <?= e(APP_NAME) ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
<?php include __DIR__ . '/includes/topbar.php'; ?>
<div class="container py-4">
    <div class="card border-0 shadow-sm rounded-4">
        <div class="card-body p-4">
            <h1 class="h4 mb-3">Audit Logs</h1>
            <div class="table-responsive">
                <table class="table table-sm align-middle">
                    <thead><tr><th>Date</th><th>Actor</th><th>Action</th><th>Message</th><th>Target</th><th>IP</th></tr></thead>
                    <tbody>
                    <?php foreach ($logs as $row): ?>
                        <tr>
                            <td><?= e($row['created_at']) ?></td>
                            <td><?= e($row['actor_name'] ?: $row['actor_role'] ?: 'System') ?></td>
                            <td><code><?= e($row['action_name']) ?></code></td>
                            <td><?= e($row['message']) ?></td>
                            <td><?= e(($row['target_type'] ?: '-') . ($row['target_id'] ? '#' . $row['target_id'] : '')) ?></td>
                            <td><?= e($row['ip_address']) ?></td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
</body>
</html>
