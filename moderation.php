<?php
require_once __DIR__ . '/includes/auth.php';
require_login();
require_verified_client();
send_security_headers();

$requestedGuid = trim($_GET['clientguid'] ?? '');
if (is_admin() && $requestedGuid !== '') {
    $client = fetch_client_by_guid($requestedGuid);
} else {
    $client = fetch_user_by_id((int)current_user()['id']);
}
if (!$client || $client['role'] !== 'client') exit('Client record not found.');
$memorial = fetch_memorial_by_client_id((int)$client['id']);
if (!$memorial) exit('Memorial not found.');
$memorialId = (int)$memorial['id'];
$builderUrl = 'memorial_edit.php' . (is_admin() ? '?clientguid=' . urlencode($client['client_guid']) : '');
$error = '';
$success = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf_or_fail();
    try {
        $messageId = (int)($_POST['message_id'] ?? 0);
        $action = $_POST['action'] ?? '';
        if ($action === 'approve') {
            approve_memorial_message($messageId, $memorialId);
            log_audit('message.approve', 'Message approved.', 'memorial_message', $messageId, ['memorial_id' => $memorialId]);
            $success = 'Message approved.';
        } elseif ($action === 'delete') {
            delete_memorial_message($messageId, $memorialId);
            log_audit('message.delete', 'Message deleted.', 'memorial_message', $messageId, ['memorial_id' => $memorialId]);
            $success = 'Message deleted.';
        }
    } catch (Throwable $e) {
        $error = $e->getMessage();
    }
}
$messages = fetch_messages($memorialId, false);
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Moderate Messages - <?= e(APP_NAME) ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/site.css">
</head>
<body class="admin-page">
<?php include __DIR__ . '/includes/topbar.php'; ?>
<div class="container py-4 admin-shell">
    <?php if ($success): ?><div class="alert alert-success"><?= e($success) ?></div><?php endif; ?>
    <?php if ($error): ?><div class="alert alert-danger"><?= e($error) ?></div><?php endif; ?>
    <div class="card border-0 shadow-sm rounded-4">
        <div class="card-body p-4">
            <div class="d-flex justify-content-between align-items-center gap-2 flex-wrap mb-3">
                <h1 class="h4 mb-0">Message Moderation</h1>
                <a href="<?= e($builderUrl) ?>" class="btn btn-outline-secondary btn-sm">Back to Memorial Builder</a>
            </div>
            <div class="table-responsive">
                <table class="table align-middle">
                    <thead><tr><th>Visitor</th><th>Photo</th><th>Message</th><th>Status</th><th>Date</th><th>Action</th></tr></thead>
                    <tbody>
                    <?php foreach ($messages as $row): ?>
                        <tr>
                            <td><?= e($row['visitor_name']) ?></td>
                            <td>
                                <?php if (!empty($row['visitor_photo'])): ?>
                                    <a href="<?= e(UPLOAD_URL . '/' . $row['visitor_photo']) ?>" target="_blank" rel="noopener noreferrer">
                                        <img src="<?= e(UPLOAD_URL . '/' . $row['visitor_photo']) ?>" alt="<?= e($row['visitor_name']) ?>" style="width:72px;height:72px;object-fit:cover;border-radius:14px;border:1px solid #ddd;">
                                    </a>
                                <?php else: ?>
                                    <span class="text-muted small">No photo</span>
                                <?php endif; ?>
                            </td>
                            <td style="min-width:260px; white-space:normal;"><?= nl2br(e($row['message'])) ?></td>
                            <td><?= !empty($row['is_approved']) ? 'Approved' : 'Pending' ?></td>
                            <td><?= e($row['created_at']) ?></td>
                            <td class="text-nowrap">
                                <?php if (empty($row['is_approved'])): ?>
                                <form method="post" class="d-inline"><?= csrf_input() ?><input type="hidden" name="action" value="approve"><input type="hidden" name="message_id" value="<?= (int)$row['id'] ?>"><button class="btn btn-sm btn-outline-success">Approve</button></form>
                                <?php endif; ?>
                                <form method="post" class="d-inline" data-swal-confirm="Delete this visitor message?" data-swal-title="Delete message?" data-swal-confirm-text="Yes, delete message"><?= csrf_input() ?><input type="hidden" name="action" value="delete"><input type="hidden" name="message_id" value="<?= (int)$row['id'] ?>"><button class="btn btn-sm btn-outline-danger">Delete</button></form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<?php include __DIR__ . '/includes/ui_feedback_assets.php'; ?>
</body>
</html>
