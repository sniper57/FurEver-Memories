<?php
require_once __DIR__ . '/includes/auth.php';
send_security_headers();

$action = trim((string)($_GET['action'] ?? ''));
$token = trim((string)($_GET['token'] ?? ''));
$currentUser = current_user();
$currentUserId = (int)($currentUser['id'] ?? 0);

try {
    if (!paypal_is_configured()) {
        throw new RuntimeException('PayPal checkout is not configured on this environment yet.');
    }

    if ($action === 'cancel') {
        flash_set('warning', 'PayPal checkout was cancelled. You can try again anytime.');
        redirect(is_logged_in() ? 'subscription.php' : 'login.php');
    }

    if ($action === 'start') {
        require_login();
        require_verified_client();
        if (!is_client()) {
            redirect('dashboard.php');
        }

        $user = current_user();
        $subscriptionId = (int)($_GET['subscription_id'] ?? 0);
        $subscription = fetch_subscription_by_id($subscriptionId);
        if (!$subscription || (int)($subscription['user_id'] ?? 0) !== (int)($user['id'] ?? 0)) {
            throw new RuntimeException('Subscription request not found.');
        }

        $returnUrl = rtrim(BASE_URL, '/') . '/paypal_checkout.php?action=return';
        $cancelUrl = rtrim(BASE_URL, '/') . '/paypal_checkout.php?action=cancel&subscription_id=' . $subscriptionId;
        $order = create_paypal_checkout_order($subscription, $user, $returnUrl, $cancelUrl);
        register_paypal_checkout_order($subscription, $user, $order['order']);

        log_audit('paypal.checkout.start', 'Client started a PayPal checkout session.', 'client_subscription', (int)$subscription['id'], [
            'order_id' => $order['order']['id'] ?? null,
        ]);

        redirect($order['approval_url']);
    }

    if ($action === 'return') {
        if ($token === '') {
            throw new RuntimeException('PayPal did not return a valid order token.');
        }

        $orderDetails = paypal_request('GET', '/v2/checkout/orders/' . rawurlencode($token));
        $orderStatus = strtoupper(trim((string)($orderDetails['status'] ?? '')));

        if ($orderStatus === 'COMPLETED') {
            $captureResponse = $orderDetails;
        } elseif ($orderStatus === 'APPROVED') {
            $captureResponse = paypal_request('POST', '/v2/checkout/orders/' . rawurlencode($token) . '/capture', (object) []);
        } else {
            throw new RuntimeException('PayPal returned the order in an unexpected state: ' . ($orderStatus !== '' ? $orderStatus : 'unknown') . '.');
        }

        $result = activate_subscription_from_paypal_capture($captureResponse, $currentUserId);
        log_audit('paypal.checkout.capture', 'PayPal payment captured and memorial access activated.', 'client_subscription', (int)($result['subscription']['id'] ?? 0), [
            'order_id' => $captureResponse['id'] ?? null,
            'payment_id' => $result['payment']['id'] ?? null,
            'resolved_user_id' => $result['resolved_user_id'] ?? null,
        ]);

        if (!empty($result['current_user_matches'])) {
            flash_set('success', 'PayPal payment received successfully. Your memorial is now eligible for public sharing.');
            redirect('subscription.php');
        }

        flash_set('success', 'PayPal payment received successfully. Please sign in to the correct memorial account to view the updated subscription.');
        redirect('login.php');
    }

    redirect(is_logged_in() ? 'subscription.php' : 'login.php');
} catch (Throwable $e) {
    log_audit('paypal.checkout.error', 'PayPal checkout return failed.', 'user', $currentUserId > 0 ? $currentUserId : null, [
        'action' => $action !== '' ? $action : null,
        'token' => $token !== '' ? $token : null,
        'error' => $e->getMessage(),
    ]);
    flash_set('warning', $e->getMessage());
    redirect(is_logged_in() ? 'subscription.php' : 'login.php');
}
