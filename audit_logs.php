<?php
require_once __DIR__ . '/includes/auth.php';
require_admin();
send_security_headers();

$success = '';
$error = '';
$perPage = 300;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf_or_fail();

    try {
        $action = trim((string)($_POST['action'] ?? ''));
        if ($action === 'delete_old_logs') {
            $deleteCount = (int)($_POST['delete_count'] ?? 0);
            if ($deleteCount <= 0) {
                throw new RuntimeException('Enter how many oldest logs you want to delete.');
            }

            $deleted = delete_oldest_audit_logs($deleteCount);
            log_audit('audit_logs.delete_oldest', 'Administrator deleted oldest audit log entries.', 'audit_logs', null, [
                'requested_count' => $deleteCount,
                'deleted_count' => $deleted,
            ]);
            $success = 'Deleted ' . $deleted . ' old audit log entr' . ($deleted === 1 ? 'y.' : 'ies.');
        }
    } catch (Throwable $e) {
        $error = $e->getMessage();
    }
}

$totalLogs = count_audit_logs();
$totalPages = max(1, (int)ceil($totalLogs / $perPage));
$page = max(1, min($totalPages, (int)($_GET['page'] ?? 1)));
$offset = ($page - 1) * $perPage;
$logs = fetch_audit_logs_page($perPage, $offset);
$from = $totalLogs > 0 ? $offset + 1 : 0;
$to = min($offset + $perPage, $totalLogs);
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Audit Logs - <?= e(APP_NAME) ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/site.css">
</head>
<body class="admin-page">
<?php include __DIR__ . '/includes/topbar.php'; ?>
<div class="container py-4 admin-shell">
    <?php if ($success): ?><div class="alert alert-success"><?= e($success) ?></div><?php endif; ?>
    <?php if ($error): ?><div class="alert alert-danger"><?= e($error) ?></div><?php endif; ?>

    <div class="row g-4">
        <div class="col-lg-8">
            <div class="card border-0 shadow-sm rounded-4">
                <div class="card-body p-4">
                    <div class="d-flex justify-content-between align-items-center gap-3 flex-wrap mb-3">
                        <div>
                            <h1 class="h4 mb-1">Audit Logs</h1>
                            <div class="small text-muted">Showing <?= e((string)$from) ?>-<?= e((string)$to) ?> of <?= e((string)$totalLogs) ?> logs. Default page size is <?= e((string)$perPage) ?>.</div>
                        </div>
                        <nav aria-label="Audit log pagination">
                            <ul class="pagination mb-0">
                                <li class="page-item <?= $page <= 1 ? 'disabled' : '' ?>">
                                    <a class="page-link" href="audit_logs.php?page=<?= max(1, $page - 1) ?>">Previous</a>
                                </li>
                                <li class="page-item disabled"><span class="page-link">Page <?= e((string)$page) ?> of <?= e((string)$totalPages) ?></span></li>
                                <li class="page-item <?= $page >= $totalPages ? 'disabled' : '' ?>">
                                    <a class="page-link" href="audit_logs.php?page=<?= min($totalPages, $page + 1) ?>">Next</a>
                                </li>
                            </ul>
                        </nav>
                    </div>

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
                            <?php if (!$logs): ?>
                                <tr><td colspan="6" class="text-muted text-center py-4">No audit logs yet.</td></tr>
                            <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-lg-4">
            <div class="card border-0 shadow-sm rounded-4">
                <div class="card-body p-4">
                    <h2 class="h5 mb-3">Delete Old Logs</h2>
                    <p class="small text-muted">Use this when audit logs are too many. This deletes the oldest entries first and keeps newer activity history.</p>
                    <form method="post" onsubmit="return confirm('Delete the oldest audit log entries now? This cannot be undone.');">
                        <?= csrf_input() ?>
                        <input type="hidden" name="action" value="delete_old_logs">
                        <div class="mb-3">
                            <label class="form-label">Number of oldest logs to delete</label>
                            <input type="number" name="delete_count" class="form-control" min="1" max="100000" placeholder="Example: 1000" required>
                        </div>
                        <button class="btn btn-outline-danger w-100">Delete Old Logs</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
