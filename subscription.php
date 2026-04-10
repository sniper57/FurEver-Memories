<?php
require_once __DIR__ . '/includes/auth.php';
require_login();
require_verified_client();
send_security_headers();

if (!is_client()) {
    redirect('dashboard.php');
}

require_once __DIR__ . '/includes/upload_helpers.php';

$user = current_user();
$memorial = fetch_memorial_by_client_id((int)$user['id']);
$plans = fetch_subscription_plans(true);
$subscription = fetch_latest_subscription_for_user((int)$user['id']);
$payments = fetch_subscription_payments_for_user((int)$user['id']);
$accessSummary = memorial_public_access_summary((int)$user['id'], $memorial ?: null);
$success = flash_get('success');
$warning = flash_get('warning');
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf_or_fail();

    try {
        $action = trim((string)($_POST['action'] ?? ''));

        if ($action === 'start_subscription') {
            $subscription = create_subscription_request((int)$user['id'], (int)($_POST['plan_id'] ?? 0));
            log_audit('subscription.request.create', 'Client started a subscription request.', 'user', (int)$user['id'], [
                'subscription_id' => $subscription['id'] ?? null,
                'plan_id' => $subscription['plan_id'] ?? null,
            ]);
            $success = 'Subscription request created. You can now submit your payment proof for review.';
        }

        if ($action === 'cancel_subscription') {
            $subscriptionId = (int)($_POST['subscription_id'] ?? 0);
            $cancelledSubscription = cancel_subscription_request($subscriptionId, (int)$user['id']);
            log_audit('subscription.request.cancel', 'Client cancelled a pending subscription request.', 'client_subscription', (int)($cancelledSubscription['id'] ?? $subscriptionId), [
                'subscription_id' => $cancelledSubscription['id'] ?? $subscriptionId,
                'plan_id' => $cancelledSubscription['plan_id'] ?? null,
                'status' => $cancelledSubscription['status'] ?? 'cancelled',
            ]);
            $success = 'Subscription request cancelled. You can now select a different plan.';
        }

        if ($action === 'submit_payment') {
            $subscriptionId = (int)($_POST['subscription_id'] ?? 0);
            $proofPath = '';
            if (!empty($_FILES['payment_proof']['tmp_name'])) {
                $proofPath = (string)save_optimized_image($_FILES['payment_proof'], ($user['client_guid'] ?? 'client') . '/payments', 'payment');
            }

            $payment = submit_subscription_payment($subscriptionId, (int)$user['id'], [
                'payment_method' => $_POST['payment_method'] ?? '',
                'amount' => $_POST['amount'] ?? 0,
                'reference_number' => $_POST['reference_number'] ?? '',
                'payer_name' => $_POST['payer_name'] ?? '',
                'payer_contact' => $_POST['payer_contact'] ?? '',
                'notes' => $_POST['notes'] ?? '',
                'proof_path' => $proofPath,
            ]);

            log_audit('subscription.payment.submit', 'Client submitted a subscription payment for review.', 'user', (int)$user['id'], [
                'payment_id' => $payment['id'] ?? null,
                'subscription_id' => $payment['subscription_id'] ?? null,
            ]);

            $success = 'Your payment was submitted successfully. Our team will review it before enabling public memorial sharing.';
        }

        refresh_logged_user();
        $user = current_user();
        $memorial = fetch_memorial_by_client_id((int)$user['id']);
        $plans = fetch_subscription_plans(true);
        $subscription = fetch_latest_subscription_for_user((int)$user['id']);
        $payments = fetch_subscription_payments_for_user((int)$user['id']);
        $accessSummary = memorial_public_access_summary((int)$user['id'], $memorial ?: null);
    } catch (Throwable $e) {
        $error = $e->getMessage();
    }
}

$paymentInstructions = payment_method_instructions();
$paypalConfigured = paypal_is_configured();
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Billing & Access - <?= e(APP_NAME) ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/site.css">
</head>
<body class="admin-page">
<?php include __DIR__ . '/includes/topbar.php'; ?>
<div class="container py-4 admin-shell">
    <?php if ($success): ?><div class="alert alert-success"><?= e($success) ?></div><?php endif; ?>
    <?php if ($warning): ?><div class="alert alert-warning"><?= e($warning) ?></div><?php endif; ?>
    <?php if ($error): ?><div class="alert alert-danger"><?= e($error) ?></div><?php endif; ?>

    <div class="row g-4">
        <div class="col-lg-8">
            <div class="card border-0 shadow-sm rounded-4">
                <div class="card-body p-4 p-lg-5">
                    <span class="marketing-kicker">Billing & access</span>
                    <h1 class="h3 mb-2">Subscription access for public memorial sharing</h1>
                    <p class="text-muted mb-4">Your memorial stays in private preview mode until a subscription payment is reviewed and approved. Once active, your memorial can be shared publicly with friends and family.</p>

                    <div class="row g-3 mb-4">
                        <div class="col-md-6">
                            <div class="p-3 rounded-4 border h-100 bg-white">
                                <div class="small text-uppercase text-muted mb-2">Current access</div>
                                <div class="fw-semibold fs-5"><?= e($accessSummary['label']) ?></div>
                                <?php if (!empty($subscription['ends_at']) && ($subscription['status'] ?? '') === 'active'): ?>
                                    <div class="small text-muted mt-2">Active until <?= e(format_display_date($subscription['ends_at'])) ?></div>
                                <?php endif; ?>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="p-3 rounded-4 border h-100 bg-white">
                                <div class="small text-uppercase text-muted mb-2">Latest subscription status</div>
                                <?php if ($subscription): ?>
                                    <span class="badge <?= e(subscription_status_badge_class((string)$subscription['status'])) ?>"><?= e(ucwords(str_replace('_', ' ', (string)$subscription['status']))) ?></span>
                                    <div class="mt-2 fw-semibold"><?= e($subscription['plan_name'] ?? 'Selected plan') ?></div>
                                    <div class="small text-muted">PHP <?= e(number_format((float)($subscription['amount'] ?? 0), 2)) ?></div>
                                    <?php if (in_array((string)($subscription['status'] ?? ''), ['pending_payment', 'rejected'], true)): ?>
                                        <form method="post" class="mt-3">
                                            <?= csrf_input() ?>
                                            <input type="hidden" name="action" value="cancel_subscription">
                                            <input type="hidden" name="subscription_id" value="<?= (int)$subscription['id'] ?>">
                                            <button class="btn btn-outline-danger btn-sm" type="submit">Cancel Current Request</button>
                                        </form>
                                        <div class="small text-muted mt-2">Cancel this request first if you want to switch to a different plan.</div>
                                    <?php endif; ?>
                                <?php else: ?>
                                    <div class="fw-semibold">No subscription request yet</div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>

                    <h2 class="h5 mb-3">Choose a subscription plan</h2>
                    <div class="row g-3 mb-4">
                        <?php foreach ($plans as $plan): ?>
                            <div class="col-md-4">
                                <div class="card border-0 shadow-sm rounded-4 h-100">
                                    <div class="card-body p-4">
                                        <div class="small text-uppercase text-muted mb-2"><?= e($plan['billing_cycle']) ?></div>
                                        <h3 class="h5"><?= e($plan['name']) ?></h3>
                                        <div class="display-6 fw-bold mb-2">PHP <?= e(number_format((float)$plan['price_amount'], 2)) ?></div>
                                        <p class="text-muted small mb-4"><?= e($plan['description']) ?></p>
                                        <form method="post">
                                            <?= csrf_input() ?>
                                            <input type="hidden" name="action" value="start_subscription">
                                            <input type="hidden" name="plan_id" value="<?= (int)$plan['id'] ?>">
                                            <button class="btn btn-dark w-100" type="submit">Select Plan</button>
                                        </form>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>

                    <?php if ($subscription && in_array((string)$subscription['status'], ['pending_payment', 'rejected'], true)): ?>
                        <h2 class="h5 mb-3">Complete your payment</h2>
                        <div class="alert alert-light border rounded-4 d-flex flex-column flex-lg-row justify-content-between align-items-lg-center gap-3 mb-4">
                            <div>
                                <strong>Need to change your selected plan?</strong>
                                <div class="small text-muted">Cancel this pending request first, then choose the plan you really want to pay for.</div>
                            </div>
                            <form method="post" class="m-0">
                                <?= csrf_input() ?>
                                <input type="hidden" name="action" value="cancel_subscription">
                                <input type="hidden" name="subscription_id" value="<?= (int)$subscription['id'] ?>">
                                <button class="btn btn-outline-danger" type="submit">Cancel This Plan</button>
                            </form>
                        </div>
                        <div class="card border-0 shadow-sm rounded-4 mb-4">
                            <div class="card-body p-4">
                                <div class="d-flex flex-column flex-lg-row justify-content-between align-items-lg-center gap-3">
                                    <div>
                                        <div class="small text-uppercase text-muted mb-2">Recommended</div>
                                        <h3 class="h5 mb-2">PayPal instant checkout</h3>
                                        <p class="text-muted mb-0">Use PayPal for the fastest activation path. Once payment is captured successfully, your memorial can switch from private preview to public sharing automatically.</p>
                                    </div>
                                    <div class="text-lg-end">
                                        <?php if ($paypalConfigured): ?>
                                            <a href="paypal_checkout.php?action=start&amp;subscription_id=<?= (int)$subscription['id'] ?>" class="btn btn-dark btn-lg">Pay with PayPal</a>
                                            <div class="small text-muted mt-2">Amount: PHP <?= e(number_format((float)($subscription['amount'] ?? 0), 2)) ?></div>
                                        <?php else: ?>
                                            <button type="button" class="btn btn-outline-secondary btn-lg" disabled>PayPal not configured yet</button>
                                            <div class="small text-muted mt-2">Add `PAYPAL_CLIENT_ID` and `PAYPAL_CLIENT_SECRET` in your local config to enable this.</div>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <h2 class="h5 mb-3">Manual payment fallback</h2>
                        <div class="row g-3 mb-4">
                            <?php foreach ($paymentInstructions as $methodKey => $instruction): ?>
                                <?php if ($methodKey === 'paypal') { continue; } ?>
                                <div class="col-md-4">
                                    <div class="p-3 rounded-4 border bg-white h-100">
                                        <div class="small text-uppercase text-muted mb-2"><?= e($instruction['label']) ?></div>
                                        <div class="fw-semibold mb-2"><?= e($instruction['headline']) ?></div>
                                        <p class="small text-muted mb-0"><?= nl2br(e($instruction['details'])) ?></p>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>

                        <form method="post" enctype="multipart/form-data" class="row g-3">
                            <?= csrf_input() ?>
                            <input type="hidden" name="action" value="submit_payment">
                            <input type="hidden" name="subscription_id" value="<?= (int)$subscription['id'] ?>">
                            <div class="col-md-6">
                                <label class="form-label">Payment method</label>
                                <select name="payment_method" class="form-select" required>
                                    <option value="">Select a payment method</option>
                                    <?php foreach (payment_method_options() as $methodKey => $label): ?>
                                        <?php if ($methodKey === 'paypal') { continue; } ?>
                                        <option value="<?= e($methodKey) ?>"><?= e($label) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Amount paid</label>
                                <input type="number" min="0" step="0.01" name="amount" class="form-control" value="<?= e((string)($subscription['amount'] ?? '')) ?>" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Reference number</label>
                                <input type="text" name="reference_number" class="form-control" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Payer name</label>
                                <input type="text" name="payer_name" class="form-control" value="<?= e((string)($user['full_name'] ?? '')) ?>" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Payer contact</label>
                                <input type="text" name="payer_contact" class="form-control" value="<?= e((string)($user['contact_number'] ?? '')) ?>">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Payment proof image</label>
                                <input type="file" name="payment_proof" class="form-control" accept="image/jpeg,image/png,image/webp">
                            </div>
                            <div class="col-12">
                                <label class="form-label">Notes</label>
                                <textarea name="notes" class="form-control" rows="4" placeholder="Optional notes for the admin reviewer. Include transfer timing or any payment remarks."></textarea>
                            </div>
                            <div class="col-12">
                                <button class="btn btn-dark" type="submit">Submit Payment for Review</button>
                            </div>
                        </form>
                    <?php elseif ($subscription && ($subscription['status'] ?? '') === 'pending_review'): ?>
                        <div class="alert alert-warning mt-4 mb-0">Your payment is currently under review. We will enable public access once it is approved.</div>
                    <?php elseif ($subscription && ($subscription['status'] ?? '') === 'active'): ?>
                        <div class="alert alert-success mt-4 mb-0">Your subscription is active. Your memorial is eligible for public sharing<?= !empty($memorial['public_access_override']) ? ' via admin approval' : '' ?>.</div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <div class="col-lg-4">
            <div class="card border-0 shadow-sm rounded-4 mb-4">
                <div class="card-body p-4">
                    <h2 class="h5 mb-3">How access works</h2>
                    <ol class="small text-muted ps-3 mb-0">
                        <li>Create your account and verify your email.</li>
                        <li>Build your memorial in private preview mode.</li>
                        <li>Select a plan and submit your payment proof.</li>
                        <li>Wait for admin approval to unlock public viewing.</li>
                    </ol>
                </div>
            </div>

            <div class="card border-0 shadow-sm rounded-4">
                <div class="card-body p-4">
                    <h2 class="h5 mb-3">Recent payment submissions</h2>
                    <?php if ($payments): ?>
                        <div class="d-grid gap-3">
                            <?php foreach ($payments as $payment): ?>
                                <div class="p-3 rounded-4 border bg-white">
                                    <div class="d-flex justify-content-between align-items-center gap-2">
                                        <strong><?= e($payment['plan_name'] ?? 'Plan') ?></strong>
                                        <span class="badge <?= e(subscription_status_badge_class((string)($payment['status'] ?? 'pending_review'))) ?>"><?= e(ucfirst((string)$payment['status'])) ?></span>
                                    </div>
                                    <div class="small text-muted mt-2"><?= e(ucfirst(str_replace('_', ' ', (string)$payment['payment_method']))) ?> &bull; Ref <?= e($payment['reference_number']) ?></div>
                                    <div class="small text-muted">Submitted <?= e(format_display_date($payment['created_at'], true)) ?></div>
                                    <?php if (!empty($payment['proof_path'])): ?>
                                        <div class="small mt-2">
                                            <button type="button" class="btn btn-link btn-sm p-0 payment-proof-preview" data-bs-toggle="modal" data-bs-target="#paymentProofModal" data-proof-url="<?= e(UPLOAD_URL . '/' . ltrim((string)$payment['proof_path'], '/')) ?>">View uploaded payment proof</button>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php else: ?>
                        <p class="text-muted small mb-0">No payment submission yet.</p>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>
<div class="modal fade" id="paymentProofModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content rounded-4 border-0">
            <div class="modal-header">
                <h2 class="modal-title h5">Payment Proof</h2>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <img src="" alt="Payment proof preview" class="img-fluid rounded-4 w-100" id="paymentProofImage">
                <a href="#" target="_blank" rel="noopener noreferrer" class="btn btn-outline-dark btn-sm mt-3" id="paymentProofOpen">Open image in new tab</a>
            </div>
        </div>
    </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
const paymentProofModal = document.getElementById('paymentProofModal');
if (paymentProofModal) {
    paymentProofModal.addEventListener('show.bs.modal', function(event) {
        const trigger = event.relatedTarget;
        const proofUrl = trigger ? trigger.getAttribute('data-proof-url') : '';
        const image = document.getElementById('paymentProofImage');
        const openLink = document.getElementById('paymentProofOpen');

        if (image) {
            image.src = proofUrl || '';
        }
        if (openLink) {
            openLink.href = proofUrl || '#';
        }
    });
}
</script>
</body>
</html>
