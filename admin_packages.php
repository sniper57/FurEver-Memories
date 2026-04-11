<?php
require_once __DIR__ . '/includes/auth.php';
require_admin();
send_security_headers();

$success = '';
$error = '';
$editingPlanId = (int)($_GET['edit'] ?? 0);
$editingPlan = $editingPlanId > 0 ? fetch_subscription_plan_by_id($editingPlanId) : null;
if (!$editingPlan) {
    $editingPlanId = 0;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf_or_fail();

    try {
        $action = trim((string)($_POST['action'] ?? ''));

        if ($action === 'save_package') {
            $wasCreate = empty($_POST['plan_id']);
            $savedPlan = save_subscription_plan($_POST);
            $editingPlanId = (int)($savedPlan['id'] ?? 0);
            $editingPlan = $savedPlan;
            log_audit($wasCreate ? 'subscription_plan.create' : 'subscription_plan.update', 'Administrator saved subscription package.', 'subscription_plan', $editingPlanId, [
                'name' => $savedPlan['name'] ?? '',
                'duration_days' => $savedPlan['duration_days'] ?? 0,
                'price_amount' => $savedPlan['price_amount'] ?? 0,
            ]);
            $success = 'Package saved successfully.';
        }

        if ($action === 'delete_package') {
            $planId = (int)($_POST['plan_id'] ?? 0);
            $plan = fetch_subscription_plan_by_id($planId);
            delete_subscription_plan($planId);
            log_audit('subscription_plan.delete', 'Administrator deleted subscription package.', 'subscription_plan', $planId, [
                'name' => $plan['name'] ?? '',
            ]);
            if ($editingPlanId === $planId) {
                $editingPlanId = 0;
                $editingPlan = null;
            }
            $success = 'Package deleted successfully.';
        }
    } catch (Throwable $e) {
        $error = $e->getMessage();
    }
}

$plans = fetch_subscription_plans(false);
$formDurationDays = (int)($editingPlan['duration_days'] ?? 31);
$formDurationMode = $formDurationDays <= 0 ? 'no_expiration' : 'days';
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Packages - <?= e(APP_NAME) ?></title>
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
                    <h1 class="h4 mb-3"><?= $editingPlan ? 'Edit Package' : 'Create Package' ?></h1>
                    <form method="post">
                        <?= csrf_input() ?>
                        <input type="hidden" name="action" value="save_package">
                        <input type="hidden" name="plan_id" value="<?= (int)($editingPlan['id'] ?? 0) ?>">

                        <div class="mb-3">
                            <label class="form-label">Package Name</label>
                            <input name="name" class="form-control" value="<?= e($editingPlan['name'] ?? '') ?>" required>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Package Inclusion / Description</label>
                            <textarea name="description" class="form-control" rows="5" required><?= e($editingPlan['description'] ?? '') ?></textarea>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Subscription Duration</label>
                            <select name="duration_mode" class="form-select" id="durationMode">
                                <option value="days" <?= $formDurationMode === 'days' ? 'selected' : '' ?>>Set duration in days</option>
                                <option value="no_expiration" <?= $formDurationMode === 'no_expiration' ? 'selected' : '' ?>>No expiration</option>
                            </select>
                        </div>

                        <div class="mb-3" id="durationDaysWrap">
                            <label class="form-label">Duration days</label>
                            <input type="number" name="duration_days" class="form-control" min="1" value="<?= e((string)max(1, $formDurationDays)) ?>">
                            <div class="form-text">Activation starts on payment approval/capture date. Example: 31 days from April 1 ends on May 2.</div>
                        </div>

                        <div class="row g-3">
                            <div class="col-md-8">
                                <label class="form-label">Price</label>
                                <input type="number" name="price_amount" class="form-control" min="0" step="0.01" value="<?= e((string)($editingPlan['price_amount'] ?? '0.00')) ?>">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Currency</label>
                                <input name="currency" class="form-control" value="<?= e($editingPlan['currency'] ?? 'PHP') ?>">
                            </div>
                        </div>

                        <div class="row g-3 mt-1">
                            <div class="col-md-6">
                                <label class="form-label">Sort Order</label>
                                <input type="number" name="sort_order" class="form-control" value="<?= e((string)($editingPlan['sort_order'] ?? 1)) ?>">
                            </div>
                            <div class="col-md-6 d-flex align-items-end">
                                <div class="form-check mb-2">
                                    <input class="form-check-input" type="checkbox" name="is_active" value="1" id="isActive" <?= !isset($editingPlan['is_active']) || !empty($editingPlan['is_active']) ? 'checked' : '' ?>>
                                    <label class="form-check-label" for="isActive">Active package</label>
                                </div>
                            </div>
                        </div>

                        <div class="d-flex gap-2 mt-4">
                            <button class="btn btn-dark flex-grow-1"><?= $editingPlan ? 'Save Changes' : 'Create Package' ?></button>
                            <?php if ($editingPlan): ?><a href="admin_packages.php" class="btn btn-outline-secondary">Cancel</a><?php endif; ?>
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
                            <h2 class="h4 mb-1">Package List</h2>
                            <div class="small text-muted">Active packages appear on `subscription.php` and the public pricing section of `index.php`.</div>
                        </div>
                        <a href="admin_packages.php" class="btn btn-outline-dark">New Package</a>
                    </div>

                    <div class="table-responsive">
                        <table class="table align-middle">
                            <thead><tr><th>Package</th><th>Duration</th><th>Price</th><th>Status</th><th>Usage</th><th>Actions</th></tr></thead>
                            <tbody>
                            <?php foreach ($plans as $plan): ?>
                                <?php $usageCount = subscription_plan_usage_count((int)$plan['id']); ?>
                                <tr>
                                    <td>
                                        <div class="fw-semibold"><?= e($plan['name']) ?></div>
                                        <div class="small text-muted"><?= e($plan['description']) ?></div>
                                    </td>
                                    <td><?= e(subscription_plan_duration_label($plan)) ?></td>
                                    <td><?= e($plan['currency']) ?> <?= e(number_format((float)$plan['price_amount'], 2)) ?></td>
                                    <td><span class="badge <?= !empty($plan['is_active']) ? 'text-bg-success' : 'text-bg-secondary' ?>"><?= !empty($plan['is_active']) ? 'Active' : 'Inactive' ?></span></td>
                                    <td><?= e((string)$usageCount) ?> subscription<?= $usageCount === 1 ? '' : 's' ?></td>
                                    <td class="text-nowrap">
                                        <div class="d-flex gap-2 flex-wrap">
                                            <a class="btn btn-sm btn-outline-primary" href="admin_packages.php?edit=<?= (int)$plan['id'] ?>">Edit</a>
                                            <?php if ($usageCount <= 0): ?>
                                                <form method="post" class="m-0" data-swal-confirm="Delete this package?" data-swal-title="Delete package?" data-swal-confirm-text="Yes, delete package">
                                                    <?= csrf_input() ?>
                                                    <input type="hidden" name="action" value="delete_package">
                                                    <input type="hidden" name="plan_id" value="<?= (int)$plan['id'] ?>">
                                                    <button class="btn btn-sm btn-outline-danger">Delete</button>
                                                </form>
                                            <?php else: ?>
                                                <span class="small text-muted align-self-center">Disable instead of deleting</span>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                            <?php if (!$plans): ?>
                                <tr><td colspan="6" class="text-muted text-center py-4">No packages configured yet.</td></tr>
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
<script>
const durationMode = document.getElementById('durationMode');
const durationDaysWrap = document.getElementById('durationDaysWrap');

function syncDurationFields() {
    if (!durationMode || !durationDaysWrap) {
        return;
    }
    durationDaysWrap.classList.toggle('d-none', durationMode.value === 'no_expiration');
}

if (durationMode) {
    durationMode.addEventListener('change', syncDurationFields);
    syncDurationFields();
}
</script>
</body>
</html>
