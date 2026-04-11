<?php
require_once __DIR__ . '/includes/auth.php';
require_login();
refresh_logged_user();
send_security_headers();
header('Content-Type: application/json; charset=utf-8');

try {
    $user = current_user();
    $clientGuid = trim((string)($user['client_guid'] ?? ''));
    if (is_admin() && isset($_GET['clientguid']) && $_GET['clientguid'] !== '') {
        $clientGuid = trim((string)$_GET['clientguid']);
    }

    $clientOwner = $user;
    if (is_admin() && $clientGuid !== '') {
        $targetClient = fetch_client_by_guid($clientGuid);
        if ($targetClient) {
            $clientOwner = $targetClient;
        }
    }

    $memorial = $clientOwner ? fetch_memorial_by_client_id((int)$clientOwner['id']) : null;
    $counts = [
        'views' => 0,
        'candles' => 0,
        'hearts' => 0,
        'messages' => 0,
    ];

    if ($memorial) {
        $memorialId = (int)$memorial['id'];
        $counts = [
            'views' => count_memorial_views($memorialId),
            'candles' => count_candles($memorialId),
            'hearts' => count_hearts($memorialId),
            'messages' => count_messages($memorialId, false),
        ];
    }

    echo json_encode([
        'status' => 'ok',
        'counts' => $counts,
        'generated_at' => now(),
    ], JSON_UNESCAPED_SLASHES);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode([
        'status' => 'error',
        'message' => 'Unable to load dashboard activity.',
    ]);
}
