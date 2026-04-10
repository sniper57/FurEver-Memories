<?php
require_once __DIR__ . '/includes/auth.php';
require_login();
require_verified_client();
send_security_headers();

if (!is_client()) {
    redirect('dashboard.php');
}

$user = current_user();

try {
    if (!paypal_is_configured()) {
        throw new RuntimeException('PayPal checkout is not configured on this environment yet.');
    }

    $action = trim((string)($_GET['action'] ?? ''));
    $token = trim((string)($_GET['token'] ?? ''));

    if ($action === 'cancel') {
        flash_set('warning', 'PayPal checkout was cancelled. You can try again anytime.');
        redirect('subscription.php');
    }

    if ($action === 'start') {
        $subscriptionId = (int)($_GET['subscription_id'] ?? 0);
        $subscription = fetch_subscription_by_id($subscriptionId);
        if (!$subscription || (int)($subscription['user_id'] ?? 0) !== (int)$user['id']) {
            throw new RuntimeException('Subscription request not found.');
        }

        $returnUrl = rtrim(BASE_URL, '/') . '/paypal_checkout.php?action=return';
        $cancelUrl = rtrim(BASE_URL, '/') . '/paypal_checkout.php?action=cancel&subscription_id=' . $subscriptionId;
        $order = create_paypal_checkout_order($subscription, $user, $returnUrl, $cancelUrl);

        log_audit('paypal.checkout.start', 'Client started a PayPal checkout session.', 'client_subscription', (int)$subscription['id'], [
            'order_id' => $order['order']['id'] ?? null,
        ]);

        redirect($order['approval_url']);
    }

    if ($action === 'return') {
        if ($token === '') {
            throw new RuntimeException('PayPal did not return a valid order token.');
        }

        $captureResponse = paypal_request('POST', '/v2/checkout/orders/' . rawurlencode($token) . '/capture');

        $result = activate_subscription_from_paypal_capture($captureResponse, (int)$user['id']);
        log_audit('paypal.checkout.capture', 'PayPal payment captured and memorial access activated.', 'client_subscription', (int)($result['subscription']['id'] ?? 0), [
            'order_id' => $captureResponse['id'] ?? null,
            'payment_id' => $result['payment']['id'] ?? null,
        ]);

        flash_set('success', 'PayPal payment received successfully. Your memorial is now eligible for public sharing.');
        redirect('subscription.php');
    }

    redirect('subscription.php');
} catch (Throwable $e) {
    flash_set('warning', $e->getMessage());
    redirect('subscription.php');
}
