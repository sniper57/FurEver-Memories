<?php
require_once __DIR__ . '/includes/auth.php';
require_admin();
send_security_headers();

$success = '';
$error = '';
$editingFaqId = (int)($_GET['edit'] ?? 0);
$editingFaq = $editingFaqId > 0 ? fetch_marketing_faq_by_id($editingFaqId) : null;
if (!$editingFaq) {
    $editingFaqId = 0;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf_or_fail();

    try {
        $action = trim((string)($_POST['action'] ?? ''));

        if ($action === 'save_faq') {
            $wasCreate = empty($_POST['faq_id']);
            $savedFaq = save_marketing_faq($_POST);
            $editingFaqId = (int)($savedFaq['id'] ?? 0);
            $editingFaq = $savedFaq;
            log_audit($wasCreate ? 'marketing_faq.create' : 'marketing_faq.update', 'Administrator saved landing page FAQ.', 'marketing_faq', $editingFaqId, [
                'question' => $savedFaq['question'] ?? '',
                'is_active' => $savedFaq['is_active'] ?? 0,
            ]);
            $success = 'FAQ saved successfully.';
        }

        if ($action === 'delete_faq') {
            $faqId = (int)($_POST['faq_id'] ?? 0);
            $faq = fetch_marketing_faq_by_id($faqId);
            delete_marketing_faq($faqId);
            log_audit('marketing_faq.delete', 'Administrator deleted landing page FAQ.', 'marketing_faq', $faqId, [
                'question' => $faq['question'] ?? '',
            ]);
            if ($editingFaqId === $faqId) {
                $editingFaqId = 0;
                $editingFaq = null;
            }
            $success = 'FAQ deleted successfully.';
        }
    } catch (Throwable $e) {
        $error = $e->getMessage();
    }
}

$faqs = fetch_marketing_faqs(false);
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Landing FAQs - <?= e(APP_NAME) ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/site.css">
</head>
<body class="admin-page">
<?php include __DIR__ . '/includes/topbar.php'; ?>
<div class="container-fluid py-4 admin-shell">
    <?php if ($success): ?><div class="alert alert-success"><?= e($success) ?></div><?php endif; ?>
    <?php if ($error): ?><div class="alert alert-danger"><?= e($error) ?></div><?php endif; ?>

    <div class="row g-4">
        <div class="col-lg-4">
            <div class="card border-0 shadow-sm rounded-4">
                <div class="card-body p-4">
                    <h1 class="h4 mb-3"><?= $editingFaq ? 'Edit FAQ' : 'Create FAQ' ?></h1>
                    <form method="post">
                        <?= csrf_input() ?>
                        <input type="hidden" name="action" value="save_faq">
                        <input type="hidden" name="faq_id" value="<?= (int)($editingFaq['id'] ?? 0) ?>">

                        <div class="mb-3">
                            <label class="form-label">Question</label>
                            <input name="question" class="form-control" value="<?= e($editingFaq['question'] ?? '') ?>" required>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Answer</label>
                            <textarea name="answer" class="form-control" rows="7" required><?= e($editingFaq['answer'] ?? '') ?></textarea>
                        </div>

                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label">Sort Order</label>
                                <input type="number" name="sort_order" class="form-control" value="<?= e((string)($editingFaq['sort_order'] ?? 1)) ?>">
                            </div>
                            <div class="col-md-6 d-flex align-items-end">
                                <div class="form-check mb-2">
                                    <input class="form-check-input" type="checkbox" name="is_active" value="1" id="isActive" <?= !isset($editingFaq['is_active']) || !empty($editingFaq['is_active']) ? 'checked' : '' ?>>
                                    <label class="form-check-label" for="isActive">Show on landing page</label>
                                </div>
                            </div>
                        </div>

                        <div class="d-flex gap-2 mt-4">
                            <button class="btn btn-dark flex-grow-1"><?= $editingFaq ? 'Save FAQ' : 'Create FAQ' ?></button>
                            <?php if ($editingFaq): ?><a href="admin_faqs.php" class="btn btn-outline-secondary">Cancel</a><?php endif; ?>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <div class="col-lg-8">
            <div class="card border-0 shadow-sm rounded-4">
                <div class="card-body p-4">
                    <div class="d-flex justify-content-between align-items-center gap-3 flex-wrap mb-3">
                        <div>
                            <h2 class="h4 mb-1">Landing Page FAQs</h2>
                            <div class="small text-muted">Active items appear in the FAQ section of the default `index.php` landing page.</div>
                        </div>
                        <a href="admin_faqs.php" class="btn btn-outline-dark">New FAQ</a>
                    </div>

                    <div class="table-responsive">
                        <table class="table align-middle">
                            <thead><tr><th>Question</th><th>Answer Preview</th><th>Status</th><th>Sort</th><th>Actions</th></tr></thead>
                            <tbody>
                            <?php foreach ($faqs as $faq): ?>
                                <tr>
                                    <td class="fw-semibold"><?= e($faq['question']) ?></td>
                                    <td class="text-muted small"><?= e(mb_strimwidth((string)$faq['answer'], 0, 140, '...')) ?></td>
                                    <td><span class="badge <?= !empty($faq['is_active']) ? 'text-bg-success' : 'text-bg-secondary' ?>"><?= !empty($faq['is_active']) ? 'Active' : 'Hidden' ?></span></td>
                                    <td><?= e((string)$faq['sort_order']) ?></td>
                                    <td class="text-nowrap">
                                        <div class="d-flex gap-2 flex-wrap">
                                            <a class="btn btn-sm btn-outline-primary" href="admin_faqs.php?edit=<?= (int)$faq['id'] ?>">Edit</a>
                                            <form method="post" class="m-0" data-swal-confirm="Delete this FAQ item?" data-swal-title="Delete FAQ?" data-swal-confirm-text="Yes, delete FAQ">
                                                <?= csrf_input() ?>
                                                <input type="hidden" name="action" value="delete_faq">
                                                <input type="hidden" name="faq_id" value="<?= (int)$faq['id'] ?>">
                                                <button class="btn btn-sm btn-outline-danger">Delete</button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                            <?php if (!$faqs): ?>
                                <tr><td colspan="5" class="text-muted text-center py-4">No FAQ items configured yet.</td></tr>
                            <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<?php include __DIR__ . '/includes/ui_feedback_assets.php'; ?>
</body>
</html>
