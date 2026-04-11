<?php
require_once __DIR__ . '/../config/db.php';

function send_security_headers(): void
{
    header('X-Frame-Options: SAMEORIGIN');
    header('X-Content-Type-Options: nosniff');
    header('Referrer-Policy: strict-origin-when-cross-origin');
    header('Permissions-Policy: geolocation=(), microphone=(), camera=()');
}

function e($value): string
{
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

function h(?string $value): string
{
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

function now(): string
{
    return date('Y-m-d H:i:s');
}

function today(): string
{
    return date('Y-m-d');
}

function guid(): string
{
    $data = random_bytes(16);
    $data[6] = chr((ord($data[6]) & 0x0f) | 0x40);
    $data[8] = chr((ord($data[8]) & 0x3f) | 0x80);
    return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($data), 4));
}

function random_token(int $bytes = 32): string
{
    return bin2hex(random_bytes($bytes));
}

function client_ip(): string
{
    $keys = ['HTTP_CF_CONNECTING_IP', 'HTTP_X_FORWARDED_FOR', 'REMOTE_ADDR'];
    foreach ($keys as $key) {
        if (!empty($_SERVER[$key])) {
            return trim(explode(',', $_SERVER[$key])[0]);
        }
    }
    return '0.0.0.0';
}

function user_agent(): string
{
    return substr($_SERVER['HTTP_USER_AGENT'] ?? 'unknown', 0, 500);
}

function hash_ip(string $ip): string
{
    return hash('sha256', $ip);
}

function redirect(string $url): void
{
    header('Location: ' . $url);
    exit;
}

function flash_set(string $key, string $message): void
{
    $_SESSION['_flash'][$key] = $message;
}

function flash_get(string $key): string
{
    $msg = $_SESSION['_flash'][$key] ?? '';
    unset($_SESSION['_flash'][$key]);
    return $msg;
}

function csrf_token(): string
{
    if (empty($_SESSION[CSRF_TOKEN_NAME])) {
        $_SESSION[CSRF_TOKEN_NAME] = random_token(32);
    }
    return $_SESSION[CSRF_TOKEN_NAME];
}

function csrf_input(): string
{
    return '<input type="hidden" name="' . CSRF_TOKEN_NAME . '" value="' . e(csrf_token()) . '">';
}

function verify_csrf_or_fail(): void
{
    $submitted = $_POST[CSRF_TOKEN_NAME] ?? '';
    $session = $_SESSION[CSRF_TOKEN_NAME] ?? '';
    if (!$submitted || !$session || !hash_equals($session, $submitted)) {
        http_response_code(419);
        exit('Invalid or expired form token. Please refresh the page and try again.');
    }
}

function is_logged_in(): bool
{
    return !empty($_SESSION['user']);
}

function current_user(): ?array
{
    return $_SESSION['user'] ?? null;
}

function is_admin(): bool
{
    return (current_user()['role'] ?? '') === 'administrator';
}

function is_client(): bool
{
    return (current_user()['role'] ?? '') === 'client';
}

function require_login(): void
{
    if (!is_logged_in()) {
        redirect('login.php');
    }
}

function require_admin(): void
{
    require_login();
    if (!is_admin()) {
        http_response_code(403);
        exit('Access denied.');
    }
}

function require_verified_client(): void
{
    require_login();
    if (is_client() && empty(current_user()['is_email_verified'])) {
        flash_set('warning', 'Please verify your email address first.');
        redirect('client_profile.php');
    }
}

function is_local_host(?string $host = null): bool
{
    $host = $host ?? ($_SERVER['HTTP_HOST'] ?? '');
    return $host !== '' && (bool)preg_match('/^(localhost|127\.0\.0\.1)(:\d+)?$/i', $host);
}

function public_memorial_url(string $clientGuid): string
{
    if (is_local_host()) {
        return rtrim(BASE_URL, '/') . '/index.php?c=' . rawurlencode($clientGuid);
    }

    return rtrim(BASE_URL, '/') . '/c/' . rawurlencode($clientGuid);
}

function social_provider_settings(string $provider): array
{
    $provider = strtolower(trim($provider));

    if ($provider === 'google') {
        $clientId = defined('GOOGLE_OAUTH_CLIENT_ID') ? trim((string)GOOGLE_OAUTH_CLIENT_ID) : '';
        $clientSecret = defined('GOOGLE_OAUTH_CLIENT_SECRET') ? trim((string)GOOGLE_OAUTH_CLIENT_SECRET) : '';
        return [
            'provider' => 'google',
            'label' => 'Google',
            'client_id' => $clientId,
            'client_secret' => $clientSecret,
            'configured' => ($clientId !== '' && $clientSecret !== ''),
            'auth_url' => 'https://accounts.google.com/o/oauth2/v2/auth',
            'token_url' => 'https://oauth2.googleapis.com/token',
            'userinfo_url' => 'https://openidconnect.googleapis.com/v1/userinfo',
            'scope' => 'openid email profile',
            'redirect_uri' => rtrim(BASE_URL, '/') . '/social_auth.php?provider=google',
        ];
    }

    if ($provider === 'facebook') {
        $clientId = defined('FACEBOOK_APP_ID') ? trim((string)FACEBOOK_APP_ID) : '';
        $clientSecret = defined('FACEBOOK_APP_SECRET') ? trim((string)FACEBOOK_APP_SECRET) : '';
        $graphVersion = defined('FACEBOOK_GRAPH_VERSION') ? trim((string)FACEBOOK_GRAPH_VERSION) : 'v20.0';
        return [
            'provider' => 'facebook',
            'label' => 'Facebook',
            'client_id' => $clientId,
            'client_secret' => $clientSecret,
            'configured' => ($clientId !== '' && $clientSecret !== ''),
            'auth_url' => 'https://www.facebook.com/' . $graphVersion . '/dialog/oauth',
            'token_url' => 'https://graph.facebook.com/' . $graphVersion . '/oauth/access_token',
            'userinfo_url' => 'https://graph.facebook.com/' . $graphVersion . '/me?fields=id,name,email,picture',
            'scope' => 'email,public_profile',
            'redirect_uri' => rtrim(BASE_URL, '/') . '/social_auth.php?provider=facebook',
        ];
    }

    return [
        'provider' => $provider,
        'label' => ucfirst($provider),
        'client_id' => '',
        'client_secret' => '',
        'configured' => false,
        'auth_url' => '',
        'token_url' => '',
        'userinfo_url' => '',
        'scope' => '',
        'redirect_uri' => '',
    ];
}

function social_provider_enabled(string $provider): bool
{
    return !empty(social_provider_settings($provider)['configured']);
}

function paypal_settings(): array
{
    $clientId = defined('PAYPAL_CLIENT_ID') ? trim((string)PAYPAL_CLIENT_ID) : '';
    $clientSecret = defined('PAYPAL_CLIENT_SECRET') ? trim((string)PAYPAL_CLIENT_SECRET) : '';
    $mode = defined('PAYPAL_MODE') ? strtolower(trim((string)PAYPAL_MODE)) : 'sandbox';
    if (!in_array($mode, ['sandbox', 'live'], true)) {
        $mode = 'sandbox';
    }

    return [
        'client_id' => $clientId,
        'client_secret' => $clientSecret,
        'mode' => $mode,
        'configured' => ($clientId !== '' && $clientSecret !== ''),
        'base_url' => $mode === 'live' ? 'https://api-m.paypal.com' : 'https://api-m.sandbox.paypal.com',
    ];
}

function paypal_is_configured(): bool
{
    return !empty(paypal_settings()['configured']);
}

/**
 * @param array|object|null $payload
 */
function paypal_http_request(string $method, string $url, array $headers = [], $payload = null): array
{
    if (!function_exists('curl_init')) {
        throw new RuntimeException('PayPal checkout requires the PHP cURL extension.');
    }

    $ch = curl_init($url);
    $normalizedHeaders = array_merge(['Accept: application/json'], $headers);

    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => 45,
        CURLOPT_CUSTOMREQUEST => strtoupper($method),
        CURLOPT_HTTPHEADER => $normalizedHeaders,
    ]);

    if ($payload !== null) {
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
    }

    $responseBody = curl_exec($ch);
    $httpCode = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curlError = curl_error($ch);
    curl_close($ch);

    if ($curlError) {
        throw new RuntimeException('PayPal connection error: ' . $curlError);
    }

    $decoded = json_decode((string)$responseBody, true);
    if ($httpCode < 200 || $httpCode >= 300) {
        $message = $decoded['message'] ?? $decoded['error_description'] ?? 'PayPal request failed.';
        throw new RuntimeException('PayPal error: ' . $message);
    }

    return is_array($decoded) ? $decoded : [];
}

function paypal_access_token(): string
{
    $settings = paypal_settings();
    if (empty($settings['configured'])) {
        throw new RuntimeException('PayPal checkout is not configured on this environment.');
    }

    $ch = curl_init($settings['base_url'] . '/v1/oauth2/token');
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => 45,
        CURLOPT_POST => true,
        CURLOPT_USERPWD => $settings['client_id'] . ':' . $settings['client_secret'],
        CURLOPT_POSTFIELDS => 'grant_type=client_credentials',
        CURLOPT_HTTPHEADER => [
            'Accept: application/json',
            'Accept-Language: en_US',
            'Content-Type: application/x-www-form-urlencoded',
        ],
    ]);

    $responseBody = curl_exec($ch);
    $httpCode = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curlError = curl_error($ch);
    curl_close($ch);

    if ($curlError) {
        throw new RuntimeException('Unable to reach PayPal: ' . $curlError);
    }

    $decoded = json_decode((string)$responseBody, true);
    if ($httpCode < 200 || $httpCode >= 300 || empty($decoded['access_token'])) {
        $message = $decoded['error_description'] ?? $decoded['error'] ?? 'Unable to authenticate with PayPal.';
        throw new RuntimeException('PayPal authentication failed: ' . $message);
    }

    return (string)$decoded['access_token'];
}

/**
 * @param array|object|null $payload
 */
function paypal_request(string $method, string $path, $payload = null): array
{
    $settings = paypal_settings();
    $token = paypal_access_token();

    $headers = [
        'Authorization: Bearer ' . $token,
    ];

    if ($payload !== null) {
        $headers[] = 'Content-Type: application/json';
    }

    return paypal_http_request($method, $settings['base_url'] . $path, $headers, $payload);
}

function paypal_find_link(array $response, string $rel): string
{
    foreach (($response['links'] ?? []) as $link) {
        if (($link['rel'] ?? '') === $rel && !empty($link['href'])) {
            return (string)$link['href'];
        }
    }

    return '';
}

function issue_social_auth_state(string $provider): string
{
    $key = '_oauth_state_' . strtolower(trim($provider));
    $state = random_token(24);
    $_SESSION[$key] = [
        'value' => $state,
        'issued_at' => time(),
    ];
    return $state;
}

function verify_social_auth_state(string $provider, string $state, int $ttlSeconds = 900): bool
{
    $key = '_oauth_state_' . strtolower(trim($provider));
    $sessionState = $_SESSION[$key]['value'] ?? '';
    $issuedAt = (int)($_SESSION[$key]['issued_at'] ?? 0);
    unset($_SESSION[$key]);

    if ($state === '' || $sessionState === '' || !hash_equals($sessionState, $state)) {
        return false;
    }

    return $issuedAt > 0 && (time() - $issuedAt) <= $ttlSeconds;
}

function payment_method_options(): array
{
    return [
        'paypal' => 'PayPal',
        'gcash' => 'GCash',
        'maya' => 'Maya',
        'bank_transfer' => 'Bank Transfer',
    ];
}

function payment_method_instructions(): array
{
    return [
        'paypal' => [
            'label' => 'PayPal',
            'headline' => 'Pay online with PayPal',
            'details' => 'Use PayPal for instant checkout. Once payment is captured successfully, your memorial subscription can activate automatically.',
        ],
        'gcash' => [
            'label' => 'GCash',
            'headline' => 'Pay with GCash',
            'details' => defined('GCASH_PAYMENT_LABEL') && trim((string)GCASH_PAYMENT_LABEL) !== ''
                ? trim((string)GCASH_PAYMENT_LABEL)
                : 'GCash/Maya Name: John Patrick Galacgac' . "\n"
                    . 'GCash/Maya #: 09179524856' . "\n\n"
                    . 'Please send a screenshot of payment. We need the reference number for verification.',
        ],
        'maya' => [
            'label' => 'Maya',
            'headline' => 'Pay with Maya',
            'details' => defined('MAYA_PAYMENT_LABEL') && trim((string)MAYA_PAYMENT_LABEL) !== ''
                ? trim((string)MAYA_PAYMENT_LABEL)
                : 'GCash/Maya Name: John Patrick Galacgac' . "\n"
                    . 'GCash/Maya #: 09179524856' . "\n\n"
                    . 'Please send a screenshot of payment. We need the reference number for verification.',
        ],
        'bank_transfer' => [
            'label' => 'Bank Transfer',
            'headline' => 'Pay by Bank Transfer',
            'details' => defined('BANK_TRANSFER_LABEL') && trim((string)BANK_TRANSFER_LABEL) !== ''
                ? trim((string)BANK_TRANSFER_LABEL)
                : 'BDO' . "\n"
                    . 'Account Number: 005540238223' . "\n"
                    . 'Account Name: Shirley Irlandez' . "\n\n"
                    . 'BPI' . "\n"
                    . 'Account Name: Shirley Galacgac' . "\n"
                    . 'Account Number: 2569502171' . "\n\n"
                    . 'Please send a screenshot of payment. We need the reference number for verification.',
        ],
    ];
}

function default_memorial_payload(): array
{
    return [
        'pet_name' => '',
        'pet_birth_date' => null,
        'pet_memorial_date' => null,
        'short_tribute' => '',
        'final_letter' => '',
        'video_type' => 'none',
        'video_url' => '',
        'video_file' => '',
        'bg_image_portrait' => '',
        'bg_image_landscape' => '',
        'cover_photo' => '',
        'share_footer_text' => 'Created with love through FurEver Memories',
        'youtube_embed_url' => '',
        'video_max_mb' => DEFAULT_VIDEO_MAX_MB,
    ];
}

function create_client_account(array $input): array
{
    $fullName = trim((string)($input['full_name'] ?? ''));
    $contactNumber = trim((string)($input['contact_number'] ?? ''));
    $address = trim((string)($input['address'] ?? ''));
    $email = mb_strtolower(trim((string)($input['email'] ?? '')));
    $passwordHash = (string)($input['password_hash'] ?? '');
    $authProvider = trim((string)($input['auth_provider'] ?? 'password'));
    $providerUserId = trim((string)($input['social_provider_user_id'] ?? ''));
    $emailVerified = !empty($input['is_email_verified']) ? 1 : 0;
    $verifiedAt = $emailVerified ? now() : null;

    if ($fullName === '' || $email === '' || $passwordHash === '') {
        throw new RuntimeException('Full name, email, and account credentials are required.');
    }

    if (fetch_user_by_email($email)) {
        throw new RuntimeException('Email already exists.');
    }

    $clientGuid = guid();
    db()->prepare('INSERT INTO users (role, client_guid, full_name, contact_number, address, email, password_hash, is_active, is_email_verified, email_verified_at, auth_provider, social_provider_user_id, created_at, updated_at) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?)')
        ->execute([
            'client',
            $clientGuid,
            $fullName,
            $contactNumber,
            $address,
            $email,
            $passwordHash,
            1,
            $emailVerified,
            $verifiedAt,
            $authProvider,
            $providerUserId !== '' ? $providerUserId : null,
            now(),
            now(),
        ]);

    $clientId = (int)db()->lastInsertId();
    upsert_memorial($clientId, default_memorial_payload());
    return fetch_user_by_id($clientId) ?: [];
}

function dom_inner_html(DOMNode $node): string
{
    $html = '';
    foreach ($node->childNodes as $child) {
        $html .= $node->ownerDocument->saveHTML($child);
    }
    return $html;
}

function unwrap_dom_element(DOMElement $element): void
{
    $parent = $element->parentNode;
    if (!$parent) {
        return;
    }

    while ($element->firstChild) {
        $parent->insertBefore($element->firstChild, $element);
    }

    $parent->removeChild($element);
}

function sanitize_rich_text_node(DOMNode $node, array $allowedTags, array $allowedAttributes): void
{
    if (!$node->hasChildNodes()) {
        return;
    }

    $children = [];
    foreach ($node->childNodes as $child) {
        $children[] = $child;
    }

    foreach ($children as $child) {
        if ($child instanceof DOMComment) {
            $node->removeChild($child);
            continue;
        }

        if (!$child instanceof DOMElement) {
            continue;
        }

        $tag = strtolower($child->tagName);
        if (!in_array($tag, $allowedTags, true)) {
            unwrap_dom_element($child);
            continue;
        }

        $attributes = [];
        foreach ($child->attributes as $attribute) {
            $attributes[] = $attribute->name;
        }

        $allowedForTag = $allowedAttributes[$tag] ?? [];
        foreach ($attributes as $attributeName) {
            $normalizedName = strtolower($attributeName);
            if (!in_array($normalizedName, $allowedForTag, true)) {
                $child->removeAttribute($attributeName);
                continue;
            }

            if ($tag === 'a' && $normalizedName === 'href') {
                $href = trim($child->getAttribute($attributeName));
                if (
                    $href === '' ||
                    preg_match('/^\s*javascript:/i', $href) ||
                    !preg_match('~^(https?://|mailto:|tel:|/|#)~i', $href)
                ) {
                    $child->removeAttribute($attributeName);
                }
            }
        }

        if ($tag === 'a') {
            if ($child->hasAttribute('target')) {
                $child->setAttribute('rel', 'noopener noreferrer');
            } else {
                $child->removeAttribute('rel');
            }
        }

        sanitize_rich_text_node($child, $allowedTags, $allowedAttributes);
    }
}

function render_rich_text(?string $html): string
{
    $html = trim((string)$html);
    if ($html === '') {
        return '';
    }

    $allowedTags = ['p', 'br', 'strong', 'b', 'em', 'i', 'u', 'ul', 'ol', 'li', 'blockquote', 'a', 'h2', 'h3', 'h4'];
    $allowedAttributes = [
        'a' => ['href', 'target', 'rel'],
    ];

    if (!class_exists('DOMDocument')) {
        return nl2br(e(strip_tags($html, '<p><br><strong><b><em><i><u><ul><ol><li><blockquote><a><h2><h3><h4>')));
    }

    $previousErrors = libxml_use_internal_errors(true);
    $dom = new DOMDocument('1.0', 'UTF-8');
    $wrapperId = '__fm_rich_text__';
    $loaded = $dom->loadHTML(
        '<?xml encoding="utf-8" ?><div id="' . $wrapperId . '">' . $html . '</div>',
        LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD
    );

    if (!$loaded) {
        libxml_clear_errors();
        libxml_use_internal_errors($previousErrors);
        return nl2br(e($html));
    }

    $wrapper = $dom->getElementById($wrapperId);
    if (!$wrapper) {
        libxml_clear_errors();
        libxml_use_internal_errors($previousErrors);
        return nl2br(e($html));
    }

    sanitize_rich_text_node($wrapper, $allowedTags, $allowedAttributes);
    $output = trim(dom_inner_html($wrapper));

    libxml_clear_errors();
    libxml_use_internal_errors($previousErrors);

    return $output;
}

function format_display_date(?string $value, bool $includeTime = false): string
{
    $value = trim((string)$value);
    if ($value === '') {
        return '';
    }

    try {
        $date = new DateTime($value);
    } catch (Throwable $e) {
        return $value;
    }

    return $date->format($includeTime ? 'F j, Y g:i A' : 'F j, Y');
}

function password_is_strong_enough(string $password): bool
{
    if (strlen($password) < PASSWORD_MIN_LENGTH) return false;
    if (!preg_match('/[A-Z]/', $password)) return false;
    if (!preg_match('/[a-z]/', $password)) return false;
    if (!preg_match('/\d/', $password)) return false;
    return true;
}

function fetch_user_by_email(string $email): ?array
{
    $stmt = db()->prepare('SELECT * FROM users WHERE email = ? LIMIT 1');
    $stmt->execute([mb_strtolower(trim($email))]);
    return $stmt->fetch() ?: null;
}

function fetch_user_by_id(int $id): ?array
{
    $stmt = db()->prepare('SELECT * FROM users WHERE id = ? LIMIT 1');
    $stmt->execute([$id]);
    return $stmt->fetch() ?: null;
}

function fetch_all_clients(): array
{
    $stmt = db()->prepare('SELECT * FROM users WHERE role = ? ORDER BY id DESC');
    $stmt->execute(['client']);
    return $stmt->fetchAll();
}

function update_client_account_by_admin(int $clientId, array $input): array
{
    $client = fetch_user_by_id($clientId);
    if (!$client || ($client['role'] ?? '') !== 'client') {
        throw new RuntimeException('Client not found.');
    }

    $fullName = trim((string)($input['full_name'] ?? ''));
    $contactNumber = trim((string)($input['contact_number'] ?? ''));
    $address = trim((string)($input['address'] ?? ''));
    $email = mb_strtolower(trim((string)($input['email'] ?? '')));

    if ($fullName === '' || $email === '') {
        throw new RuntimeException('Full name and email are required.');
    }

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        throw new RuntimeException('Invalid email address.');
    }

    $existing = fetch_user_by_email($email);
    if ($existing && (int)$existing['id'] !== $clientId) {
        throw new RuntimeException('Email already exists.');
    }

    $emailChanged = $email !== (string)$client['email'];
    $verified = $emailChanged ? 0 : (int)($client['is_email_verified'] ?? 0);
    $verifiedAt = $emailChanged ? null : ($client['email_verified_at'] ?? null);

    db()->prepare('UPDATE users SET full_name = ?, contact_number = ?, address = ?, email = ?, is_email_verified = ?, email_verified_at = ?, updated_at = ? WHERE id = ?')
        ->execute([$fullName, $contactNumber, $address, $email, $verified, $verifiedAt, now(), $clientId]);

    $updatedClient = fetch_user_by_id($clientId);
    $verificationLink = '';
    $verificationSent = false;

    if ($emailChanged && $updatedClient) {
        $issued = issue_email_verification_token($clientId, $email);
        $verificationLink = (string)($issued['url'] ?? '');
        $verificationSent = send_verification_email($updatedClient, $verificationLink);
    }

    return [
        'client' => $updatedClient,
        'email_changed' => $emailChanged,
        'verification_link' => $verificationLink,
        'verification_sent' => $verificationSent,
    ];
}

function delete_directory_tree(string $path): void
{
    if ($path === '' || !is_dir($path)) {
        return;
    }

    $items = scandir($path);
    if (!is_array($items)) {
        return;
    }

    foreach ($items as $item) {
        if ($item === '.' || $item === '..') {
            continue;
        }

        $itemPath = $path . DIRECTORY_SEPARATOR . $item;
        if (is_dir($itemPath)) {
            delete_directory_tree($itemPath);
            continue;
        }

        @unlink($itemPath);
    }

    @rmdir($path);
}

function delete_client_account(int $clientId): array
{
    $client = fetch_user_by_id($clientId);
    if (!$client || ($client['role'] ?? '') !== 'client') {
        throw new RuntimeException('Client not found.');
    }

    $clientGuid = trim((string)($client['client_guid'] ?? ''));
    $memorial = fetch_memorial_by_client_id($clientId);

    db()->beginTransaction();
    try {
        db()->prepare('DELETE FROM users WHERE id = ? AND role = ?')->execute([$clientId, 'client']);
        db()->commit();
    } catch (Throwable $e) {
        if (db()->inTransaction()) {
            db()->rollBack();
        }
        throw $e;
    }

    if ($clientGuid !== '') {
        delete_directory_tree(UPLOAD_DIR . DIRECTORY_SEPARATOR . $clientGuid);
        delete_directory_tree(UPLOAD_DIR . DIRECTORY_SEPARATOR . 'music' . DIRECTORY_SEPARATOR . $clientGuid);
    }

    return [
        'client' => $client,
        'client_guid' => $clientGuid,
        'memorial_id' => (int)($memorial['id'] ?? 0),
    ];
}

function fetch_user_by_social_identity(string $provider, string $providerUserId): ?array
{
    $stmt = db()->prepare('SELECT * FROM users WHERE auth_provider = ? AND social_provider_user_id = ? LIMIT 1');
    $stmt->execute([strtolower(trim($provider)), trim($providerUserId)]);
    return $stmt->fetch() ?: null;
}

function fetch_client_by_guid(string $guid): ?array
{
    $stmt = db()->prepare('SELECT * FROM users WHERE client_guid = ? AND role = "client" LIMIT 1');
    $stmt->execute([$guid]);
    return $stmt->fetch() ?: null;
}

function fetch_memorial_by_client_id(int $clientId): ?array
{
    $stmt = db()->prepare('SELECT * FROM memorial_pages WHERE client_user_id = ? LIMIT 1');
    $stmt->execute([$clientId]);
    return $stmt->fetch() ?: null;
}

function fetch_subscription_plans(bool $activeOnly = true): array
{
    $sql = 'SELECT * FROM subscription_plans';
    if ($activeOnly) {
        $sql .= ' WHERE is_active = 1';
    }
    $sql .= ' ORDER BY sort_order ASC, id ASC';
    return db()->query($sql)->fetchAll();
}

function subscription_plan_duration_label(array $plan): string
{
    $days = (int)($plan['duration_days'] ?? 0);
    if ($days <= 0) {
        return 'No expiration';
    }

    return $days . ' day' . ($days === 1 ? '' : 's');
}

function subscription_plan_billing_cycle_from_days(int $durationDays): string
{
    if ($durationDays <= 0) {
        return 'no_expiration';
    }

    return $durationDays . '_days';
}

function subscription_plan_slug(string $name): string
{
    $slug = strtolower(trim((string)preg_replace('/[^A-Za-z0-9]+/', '-', $name), '-'));
    return $slug !== '' ? $slug : 'package-' . date('YmdHis');
}

function fetch_subscription_plan_by_id(int $planId): ?array
{
    $stmt = db()->prepare('SELECT * FROM subscription_plans WHERE id = ? LIMIT 1');
    $stmt->execute([$planId]);
    return $stmt->fetch() ?: null;
}

function subscription_plan_slug_exists(string $slug, int $excludePlanId = 0): bool
{
    $stmt = db()->prepare('SELECT COUNT(*) FROM subscription_plans WHERE slug = ? AND id <> ?');
    $stmt->execute([$slug, $excludePlanId]);
    return (int)$stmt->fetchColumn() > 0;
}

function unique_subscription_plan_slug(string $name, int $excludePlanId = 0): string
{
    $baseSlug = subscription_plan_slug($name);
    $slug = $baseSlug;
    $suffix = 2;

    while (subscription_plan_slug_exists($slug, $excludePlanId)) {
        $slug = $baseSlug . '-' . $suffix;
        $suffix++;
    }

    return $slug;
}

function save_subscription_plan(array $input): array
{
    $planId = (int)($input['plan_id'] ?? 0);
    $name = trim((string)($input['name'] ?? ''));
    $description = trim((string)($input['description'] ?? ''));
    $durationMode = trim((string)($input['duration_mode'] ?? 'days'));
    $durationDays = $durationMode === 'no_expiration' ? 0 : (int)($input['duration_days'] ?? 0);
    $priceAmount = (float)($input['price_amount'] ?? 0);
    $currency = strtoupper(trim((string)($input['currency'] ?? 'PHP')));
    $sortOrder = (int)($input['sort_order'] ?? 1);
    $isActive = !empty($input['is_active']) ? 1 : 0;

    if ($name === '') {
        throw new RuntimeException('Package name is required.');
    }

    if ($description === '') {
        throw new RuntimeException('Package inclusion/description is required.');
    }

    if ($durationMode !== 'no_expiration' && $durationDays <= 0) {
        throw new RuntimeException('Subscription duration must be at least 1 day, or choose No expiration.');
    }

    if ($priceAmount < 0) {
        throw new RuntimeException('Package price cannot be negative.');
    }

    if ($currency === '') {
        $currency = 'PHP';
    }

    $billingCycle = subscription_plan_billing_cycle_from_days($durationDays);

    if ($planId > 0) {
        $existing = fetch_subscription_plan_by_id($planId);
        if (!$existing) {
            throw new RuntimeException('Package not found.');
        }

        $slug = unique_subscription_plan_slug($name, $planId);
        db()->prepare('UPDATE subscription_plans SET slug = ?, name = ?, description = ?, billing_cycle = ?, duration_days = ?, price_amount = ?, currency = ?, is_active = ?, sort_order = ?, updated_at = ? WHERE id = ?')
            ->execute([$slug, $name, $description, $billingCycle, $durationDays, $priceAmount, $currency, $isActive, $sortOrder, now(), $planId]);
        return fetch_subscription_plan_by_id($planId) ?: [];
    }

    $slug = unique_subscription_plan_slug($name);
    db()->prepare('INSERT INTO subscription_plans (slug, name, description, billing_cycle, duration_days, price_amount, currency, is_active, sort_order, created_at, updated_at) VALUES (?,?,?,?,?,?,?,?,?,?,?)')
        ->execute([$slug, $name, $description, $billingCycle, $durationDays, $priceAmount, $currency, $isActive, $sortOrder, now(), now()]);

    return fetch_subscription_plan_by_id((int)db()->lastInsertId()) ?: [];
}

function subscription_plan_usage_count(int $planId): int
{
    $stmt = db()->prepare('SELECT COUNT(*) FROM client_subscriptions WHERE plan_id = ?');
    $stmt->execute([$planId]);
    return (int)$stmt->fetchColumn();
}

function delete_subscription_plan(int $planId): void
{
    $plan = fetch_subscription_plan_by_id($planId);
    if (!$plan) {
        throw new RuntimeException('Package not found.');
    }

    if (subscription_plan_usage_count($planId) > 0) {
        throw new RuntimeException('This package already has subscription history. Disable it instead of deleting it.');
    }

    db()->prepare('DELETE FROM subscription_plans WHERE id = ?')->execute([$planId]);
}

function default_marketing_faqs(): array
{
    return [
        [
            'question' => 'Can I include photos, videos, stories, and music on one memorial page?',
            'answer' => 'Yes. FurEver Memories is designed for multimedia remembrance, so families can preserve photos, tribute stories, timelines, guest messages, videos, and background music in one place.',
            'sort_order' => 1,
        ],
        [
            'question' => 'Can family and friends add messages and reactions?',
            'answer' => 'Yes. Loved ones can visit the memorial page, light a candle, send hearts, and leave messages that the page owner can review and manage.',
            'sort_order' => 2,
        ],
        [
            'question' => 'What makes FurEver Memories feel different from a sad memorial site?',
            'answer' => 'Our visual language is warm, loving, peaceful, and celebratory. We focus on honoring life and preserving joy, not creating a cold funeral atmosphere.',
            'sort_order' => 3,
        ],
        [
            'question' => 'Can this also connect to printed products and QR codes?',
            'answer' => 'Yes. The brand is built for both digital and physical remembrance, from QR memory galleries to printed keepsakes and tribute products.',
            'sort_order' => 4,
        ],
    ];
}

function ensure_marketing_faqs_table(): void
{
    static $ensured = false;
    if ($ensured) {
        return;
    }

    db()->exec("CREATE TABLE IF NOT EXISTS marketing_faqs (
        id INT UNSIGNED NOT NULL AUTO_INCREMENT,
        question VARCHAR(255) NOT NULL,
        answer TEXT NOT NULL,
        is_active TINYINT(1) NOT NULL DEFAULT 1,
        sort_order INT NOT NULL DEFAULT 1,
        created_at DATETIME NOT NULL,
        updated_at DATETIME NOT NULL,
        PRIMARY KEY (id),
        KEY idx_marketing_faqs_active (is_active, sort_order)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci");

    $count = (int)db()->query('SELECT COUNT(*) FROM marketing_faqs')->fetchColumn();
    if ($count <= 0) {
        $stmt = db()->prepare('INSERT INTO marketing_faqs (question, answer, is_active, sort_order, created_at, updated_at) VALUES (?,?,?,?,?,?)');
        foreach (default_marketing_faqs() as $faq) {
            $stmt->execute([$faq['question'], $faq['answer'], 1, $faq['sort_order'], now(), now()]);
        }
    }

    $ensured = true;
}

function fetch_marketing_faqs(bool $activeOnly = true): array
{
    ensure_marketing_faqs_table();
    $sql = 'SELECT * FROM marketing_faqs';
    if ($activeOnly) {
        $sql .= ' WHERE is_active = 1';
    }
    $sql .= ' ORDER BY sort_order ASC, id ASC';
    return db()->query($sql)->fetchAll();
}

function fetch_marketing_faq_by_id(int $faqId): ?array
{
    ensure_marketing_faqs_table();
    $stmt = db()->prepare('SELECT * FROM marketing_faqs WHERE id = ? LIMIT 1');
    $stmt->execute([$faqId]);
    return $stmt->fetch() ?: null;
}

function save_marketing_faq(array $input): array
{
    ensure_marketing_faqs_table();
    $faqId = (int)($input['faq_id'] ?? 0);
    $question = trim((string)($input['question'] ?? ''));
    $answer = trim((string)($input['answer'] ?? ''));
    $sortOrder = (int)($input['sort_order'] ?? 1);
    $isActive = !empty($input['is_active']) ? 1 : 0;

    if ($question === '') {
        throw new RuntimeException('FAQ question is required.');
    }

    if ($answer === '') {
        throw new RuntimeException('FAQ answer is required.');
    }

    if ($faqId > 0) {
        if (!fetch_marketing_faq_by_id($faqId)) {
            throw new RuntimeException('FAQ item not found.');
        }

        db()->prepare('UPDATE marketing_faqs SET question = ?, answer = ?, is_active = ?, sort_order = ?, updated_at = ? WHERE id = ?')
            ->execute([$question, $answer, $isActive, $sortOrder, now(), $faqId]);
        return fetch_marketing_faq_by_id($faqId) ?: [];
    }

    db()->prepare('INSERT INTO marketing_faqs (question, answer, is_active, sort_order, created_at, updated_at) VALUES (?,?,?,?,?,?)')
        ->execute([$question, $answer, $isActive, $sortOrder, now(), now()]);

    return fetch_marketing_faq_by_id((int)db()->lastInsertId()) ?: [];
}

function delete_marketing_faq(int $faqId): void
{
    ensure_marketing_faqs_table();
    if (!fetch_marketing_faq_by_id($faqId)) {
        throw new RuntimeException('FAQ item not found.');
    }

    db()->prepare('DELETE FROM marketing_faqs WHERE id = ?')->execute([$faqId]);
}

function fetch_latest_subscription_for_user(int $userId): ?array
{
    $stmt = db()->prepare('SELECT s.*, p.name AS plan_name, p.slug AS plan_slug, p.billing_cycle, p.duration_days, p.price_amount AS plan_price_amount, p.currency AS plan_currency FROM client_subscriptions s LEFT JOIN subscription_plans p ON p.id = s.plan_id WHERE s.user_id = ? ORDER BY s.id DESC LIMIT 1');
    $stmt->execute([$userId]);
    return $stmt->fetch() ?: null;
}

function fetch_subscription_by_id(int $subscriptionId): ?array
{
    $stmt = db()->prepare('SELECT s.*, p.name AS plan_name, p.slug AS plan_slug, p.billing_cycle, p.duration_days, p.price_amount AS plan_price_amount, p.currency AS plan_currency FROM client_subscriptions s LEFT JOIN subscription_plans p ON p.id = s.plan_id WHERE s.id = ? LIMIT 1');
    $stmt->execute([$subscriptionId]);
    return $stmt->fetch() ?: null;
}

function fetch_subscription_payments_for_user(int $userId, int $limit = 10): array
{
    $stmt = db()->prepare('SELECT sp.*, cs.plan_id, p.name AS plan_name FROM subscription_payments sp LEFT JOIN client_subscriptions cs ON cs.id = sp.subscription_id LEFT JOIN subscription_plans p ON p.id = cs.plan_id WHERE sp.user_id = ? ORDER BY sp.id DESC LIMIT ' . (int)$limit);
    $stmt->execute([$userId]);
    return $stmt->fetchAll();
}

function fetch_latest_payment_for_user(int $userId): ?array
{
    $stmt = db()->prepare('SELECT sp.*, cs.plan_id, p.name AS plan_name FROM subscription_payments sp LEFT JOIN client_subscriptions cs ON cs.id = sp.subscription_id LEFT JOIN subscription_plans p ON p.id = cs.plan_id WHERE sp.user_id = ? ORDER BY sp.id DESC LIMIT 1');
    $stmt->execute([$userId]);
    return $stmt->fetch() ?: null;
}

function fetch_subscription_payment_by_id(int $paymentId): ?array
{
    $stmt = db()->prepare('SELECT sp.*, cs.user_id AS subscription_user_id, cs.plan_id, p.name AS plan_name, p.duration_days, p.currency AS plan_currency FROM subscription_payments sp LEFT JOIN client_subscriptions cs ON cs.id = sp.subscription_id LEFT JOIN subscription_plans p ON p.id = cs.plan_id WHERE sp.id = ? LIMIT 1');
    $stmt->execute([$paymentId]);
    return $stmt->fetch() ?: null;
}

function subscription_is_active(?array $subscription): bool
{
    if (!$subscription || ($subscription['status'] ?? '') !== 'active') {
        return false;
    }

    $endsAt = trim((string)($subscription['ends_at'] ?? ''));
    if ($endsAt === '') {
        return true;
    }

    try {
        return (new DateTime($endsAt)) >= new DateTime();
    } catch (Throwable $e) {
        return false;
    }
}

function ensure_subscription_expiry_state(int $userId): void
{
    $subscription = fetch_latest_subscription_for_user($userId);
    if (!$subscription || ($subscription['status'] ?? '') !== 'active') {
        return;
    }

    if (subscription_is_active($subscription)) {
        return;
    }

    db()->prepare('UPDATE client_subscriptions SET status = ?, updated_at = ? WHERE id = ?')
        ->execute(['expired', now(), $subscription['id']]);
}

function memorial_public_access_summary(int $userId, ?array $memorial = null): array
{
    ensure_subscription_expiry_state($userId);
    $memorial = $memorial ?: fetch_memorial_by_client_id($userId);
    $subscription = fetch_latest_subscription_for_user($userId);

    if ($memorial && !empty($memorial['public_access_override'])) {
        return [
            'is_public' => true,
            'source' => 'admin_override',
            'label' => 'Public via admin approval',
            'subscription' => $subscription,
        ];
    }

    if (subscription_is_active($subscription)) {
        return [
            'is_public' => true,
            'source' => 'subscription',
            'label' => 'Public via active subscription',
            'subscription' => $subscription,
        ];
    }

    $status = $subscription['status'] ?? 'none';
    $labelMap = [
        'pending_payment' => 'Private preview only: waiting for payment submission',
        'pending_review' => 'Private preview only: payment under review',
        'rejected' => 'Private preview only: payment needs attention',
        'expired' => 'Private preview only: subscription expired',
        'cancelled' => 'Private preview only: subscription cancelled',
        'none' => 'Private preview only',
    ];

    return [
        'is_public' => false,
        'source' => 'private',
        'label' => $labelMap[$status] ?? 'Private preview only',
        'subscription' => $subscription,
    ];
}

function user_can_view_private_memorial(array $client): bool
{
    if (!is_logged_in()) {
        return false;
    }

    if (is_admin()) {
        return true;
    }

    return is_client() && (int)(current_user()['id'] ?? 0) === (int)$client['id'];
}

function create_subscription_request(int $userId, int $planId): array
{
    $plan = fetch_subscription_plan_by_id($planId);
    if (!$plan || empty($plan['is_active'])) {
        throw new RuntimeException('Selected subscription plan is not available.');
    }

    $existing = fetch_latest_subscription_for_user($userId);
    if ($existing) {
        $existingStatus = (string)($existing['status'] ?? '');
        $samePlan = (int)($existing['plan_id'] ?? 0) === $planId;

        if ($existingStatus === 'active') {
            throw new RuntimeException('Your subscription is already active.');
        }

        if ($existingStatus === 'pending_review') {
            throw new RuntimeException('Your latest payment is still under review. Please wait for the admin decision before starting a new request.');
        }

        if (in_array($existingStatus, ['pending_payment', 'rejected'], true)) {
            if ($samePlan) {
                return $existing;
            }

            throw new RuntimeException('Please cancel your current subscription request first before selecting a different plan.');
        }
    }

    db()->prepare('INSERT INTO client_subscriptions (user_id, plan_id, status, amount, currency, created_at, updated_at) VALUES (?,?,?,?,?,?,?)')
        ->execute([
            $userId,
            $planId,
            'pending_payment',
            $plan['price_amount'],
            $plan['currency'],
            now(),
            now(),
        ]);

    return fetch_subscription_by_id((int)db()->lastInsertId()) ?: [];
}

function cancel_subscription_request(int $subscriptionId, int $userId): array
{
    $subscription = fetch_subscription_by_id($subscriptionId);
    if (!$subscription || (int)($subscription['user_id'] ?? 0) !== $userId) {
        throw new RuntimeException('Subscription request not found.');
    }

    $status = (string)($subscription['status'] ?? '');
    if (!in_array($status, ['pending_payment', 'rejected'], true)) {
        throw new RuntimeException('Only pending subscription requests can be cancelled.');
    }

    $note = 'Cancelled by client before payment completion.';

    db()->prepare('UPDATE client_subscriptions SET status = ?, review_notes = ?, updated_at = ? WHERE id = ?')
        ->execute([
            'cancelled',
            $note,
            now(),
            $subscriptionId,
        ]);

    db()->prepare('UPDATE subscription_payments SET status = ?, reviewed_at = ?, review_notes = ?, updated_at = ? WHERE subscription_id = ? AND status = ?')
        ->execute([
            'rejected',
            now(),
            $note,
            now(),
            $subscriptionId,
            'pending',
        ]);

    return fetch_subscription_by_id($subscriptionId) ?: [];
}

function fetch_subscription_payment_by_order_id(string $orderId): ?array
{
    $stmt = db()->prepare('SELECT * FROM subscription_payments WHERE gateway_order_id = ? LIMIT 1');
    $stmt->execute([trim($orderId)]);
    return $stmt->fetch() ?: null;
}

function register_paypal_checkout_order(array $subscription, array $user, array $orderResponse): array
{
    $orderId = trim((string)($orderResponse['id'] ?? ''));
    if ($orderId === '') {
        throw new RuntimeException('PayPal did not return a valid order ID.');
    }

    $existing = fetch_subscription_payment_by_order_id($orderId);
    if ($existing) {
        return $existing;
    }

    db()->prepare('INSERT INTO subscription_payments (subscription_id, user_id, payment_method, amount, currency, reference_number, payer_name, payer_contact, status, gateway_provider, gateway_order_id, raw_response_json, created_at, updated_at) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?)')
        ->execute([
            $subscription['id'],
            $user['id'],
            'paypal',
            $subscription['amount'],
            $subscription['currency'] ?: 'PHP',
            $orderId,
            $user['full_name'] ?? 'PayPal Payer',
            $user['email'] ?? null,
            'pending',
            'paypal',
            $orderId,
            json_encode($orderResponse, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
            now(),
            now(),
        ]);

    return fetch_subscription_payment_by_id((int)db()->lastInsertId()) ?: [];
}

function paypal_subscription_id_from_response(array $response): int
{
    $purchaseUnit = $response['purchase_units'][0] ?? [];
    $capture = $purchaseUnit['payments']['captures'][0] ?? [];

    $candidates = [
        (string)($purchaseUnit['custom_id'] ?? ''),
        (string)($capture['custom_id'] ?? ''),
    ];

    $referenceId = trim((string)($purchaseUnit['reference_id'] ?? ''));
    if ($referenceId !== '' && preg_match('/subscription-(\d+)/i', $referenceId, $matches)) {
        $candidates[] = $matches[1];
    }

    foreach ($candidates as $candidate) {
        $value = (int)trim($candidate);
        if ($value > 0) {
            return $value;
        }
    }

    return 0;
}

function create_paypal_checkout_order(array $subscription, array $user, string $returnUrl, string $cancelUrl): array
{
    if (!$subscription || empty($subscription['id']) || empty($subscription['plan_name'])) {
        throw new RuntimeException('Subscription request not found.');
    }

    if ((int)($subscription['user_id'] ?? 0) !== (int)($user['id'] ?? 0)) {
        throw new RuntimeException('This subscription does not belong to the current client.');
    }

    if (($subscription['status'] ?? '') === 'active') {
        throw new RuntimeException('This subscription is already active.');
    }

    if (($subscription['status'] ?? '') === 'cancelled') {
        throw new RuntimeException('This subscription request was already cancelled. Please select a new plan before checking out.');
    }

    $amountValue = number_format((float)($subscription['amount'] ?? 0), 2, '.', '');
    if ((float)$amountValue <= 0) {
        throw new RuntimeException('Invalid subscription amount.');
    }

    $payload = [
        'intent' => 'CAPTURE',
        'purchase_units' => [[
            'reference_id' => 'subscription-' . (int)$subscription['id'],
            'custom_id' => (string)(int)$subscription['id'],
            'description' => 'FurEver Memories - ' . (string)$subscription['plan_name'],
            'amount' => [
                'currency_code' => (string)($subscription['currency'] ?: 'PHP'),
                'value' => $amountValue,
            ],
        ]],
        'application_context' => [
            'brand_name' => APP_NAME,
            'landing_page' => 'LOGIN',
            'user_action' => 'PAY_NOW',
            'return_url' => $returnUrl,
            'cancel_url' => $cancelUrl,
        ],
    ];

    $response = paypal_request('POST', '/v2/checkout/orders', $payload);
    $approvalUrl = paypal_find_link($response, 'approve');
    if ($approvalUrl === '') {
        throw new RuntimeException('PayPal did not return an approval link.');
    }

    return [
        'order' => $response,
        'approval_url' => $approvalUrl,
    ];
}

function activate_subscription_from_paypal_capture(array $captureResponse, int $expectedUserId = 0): array
{
    $orderId = trim((string)($captureResponse['id'] ?? ''));
    if ($orderId === '') {
        throw new RuntimeException('PayPal capture response is missing the order ID.');
    }

    $existingPayment = fetch_subscription_payment_by_order_id($orderId);
    if ($existingPayment && ($existingPayment['status'] ?? '') === 'approved') {
        $subscription = fetch_subscription_by_id((int)$existingPayment['subscription_id']);
        return [
            'payment' => $existingPayment,
            'subscription' => $subscription,
            'resolved_user_id' => (int)($existingPayment['user_id'] ?? 0),
            'current_user_matches' => $expectedUserId > 0 && (int)($existingPayment['user_id'] ?? 0) === $expectedUserId,
        ];
    }

    $purchaseUnit = $captureResponse['purchase_units'][0] ?? [];
    $subscriptionId = $existingPayment ? (int)($existingPayment['subscription_id'] ?? 0) : paypal_subscription_id_from_response($captureResponse);
    $subscription = fetch_subscription_by_id($subscriptionId);
    if (!$subscription) {
        throw new RuntimeException('Unable to match this PayPal payment to your memorial subscription.');
    }

    if (($subscription['status'] ?? '') === 'cancelled') {
        throw new RuntimeException('This PayPal checkout belongs to a cancelled subscription request. Please start a new checkout for your selected plan.');
    }

    $resolvedUserId = (int)($subscription['user_id'] ?? 0);

    $capture = $purchaseUnit['payments']['captures'][0] ?? [];
    $captureId = trim((string)($capture['id'] ?? ''));
    $captureStatus = strtoupper(trim((string)($capture['status'] ?? '')));
    if ($captureStatus !== 'COMPLETED') {
        throw new RuntimeException('PayPal payment was not completed yet.');
    }

    $amount = (float)($capture['amount']['value'] ?? $subscription['amount'] ?? 0);
    $currency = (string)($capture['amount']['currency_code'] ?? $subscription['currency'] ?? 'PHP');
    $payer = $captureResponse['payer'] ?? [];
    $payerName = trim((string)(
        ($payer['name']['given_name'] ?? '')
        . ' '
        . ($payer['name']['surname'] ?? '')
    ));
    if ($payerName === '') {
        $payerName = (string)($payer['email_address'] ?? ($subscription['full_name'] ?? 'PayPal Payer'));
    }

    $payerContact = (string)($payer['email_address'] ?? '');
    $referenceNumber = $captureId !== '' ? $captureId : $orderId;

    if ($existingPayment) {
        db()->prepare('UPDATE subscription_payments SET payment_method = ?, amount = ?, currency = ?, reference_number = ?, payer_name = ?, payer_contact = ?, status = ?, gateway_provider = ?, gateway_capture_id = ?, gateway_payer_id = ?, raw_response_json = ?, reviewed_at = ?, review_notes = ?, updated_at = ? WHERE id = ?')
            ->execute([
                'paypal',
                $amount,
                $currency,
                $referenceNumber,
                $payerName,
                $payerContact !== '' ? $payerContact : null,
                'approved',
                'paypal',
                $captureId !== '' ? $captureId : null,
                trim((string)($payer['payer_id'] ?? '')) ?: null,
                json_encode($captureResponse, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
                now(),
                'Activated automatically after PayPal payment capture.',
                now(),
                $existingPayment['id'],
            ]);
        $paymentId = (int)$existingPayment['id'];
    } else {
        db()->prepare('INSERT INTO subscription_payments (subscription_id, user_id, payment_method, amount, currency, reference_number, payer_name, payer_contact, status, gateway_provider, gateway_order_id, gateway_capture_id, gateway_payer_id, raw_response_json, reviewed_at, review_notes, created_at, updated_at) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)')
            ->execute([
                $subscription['id'],
                $resolvedUserId,
                'paypal',
                $amount,
                $currency,
                $referenceNumber,
                $payerName,
                $payerContact !== '' ? $payerContact : null,
                'approved',
                'paypal',
                $orderId,
                $captureId !== '' ? $captureId : null,
                trim((string)($payer['payer_id'] ?? '')) ?: null,
                json_encode($captureResponse, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
                now(),
                'Activated automatically after PayPal payment capture.',
                now(),
                now(),
            ]);
        $paymentId = (int)db()->lastInsertId();
    }

    approve_subscription_payment($paymentId, 0, 'Activated automatically after PayPal payment capture.');

    return [
        'payment' => fetch_subscription_payment_by_id($paymentId),
        'subscription' => fetch_subscription_by_id((int)$subscription['id']),
        'resolved_user_id' => $resolvedUserId,
        'current_user_matches' => $expectedUserId > 0 && $resolvedUserId === $expectedUserId,
    ];
}

function submit_subscription_payment(int $subscriptionId, int $userId, array $input): array
{
    $subscription = fetch_subscription_by_id($subscriptionId);
    if (!$subscription || (int)$subscription['user_id'] !== $userId) {
        throw new RuntimeException('Subscription request not found.');
    }

    $method = trim((string)($input['payment_method'] ?? ''));
    $amount = (float)($input['amount'] ?? 0);
    $referenceNumber = trim((string)($input['reference_number'] ?? ''));
    $payerName = trim((string)($input['payer_name'] ?? ''));
    $payerContact = trim((string)($input['payer_contact'] ?? ''));
    $notes = trim((string)($input['notes'] ?? ''));
    $proofPath = trim((string)($input['proof_path'] ?? ''));

    if (!array_key_exists($method, payment_method_options())) {
        throw new RuntimeException('Please select a valid payment method.');
    }

    if ($amount <= 0) {
        throw new RuntimeException('Please provide the payment amount.');
    }

    if ($referenceNumber === '' || $payerName === '') {
        throw new RuntimeException('Reference number and payer name are required.');
    }

    db()->prepare('INSERT INTO subscription_payments (subscription_id, user_id, payment_method, amount, currency, reference_number, payer_name, payer_contact, proof_path, notes, status, created_at, updated_at) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?)')
        ->execute([
            $subscriptionId,
            $userId,
            $method,
            $amount,
            $subscription['currency'] ?: 'PHP',
            $referenceNumber,
            $payerName,
            $payerContact,
            $proofPath !== '' ? $proofPath : null,
            $notes !== '' ? $notes : null,
            'pending',
            now(),
            now(),
        ]);
    $paymentId = (int)db()->lastInsertId();

    db()->prepare('UPDATE client_subscriptions SET status = ?, payment_method = ?, updated_at = ? WHERE id = ?')
        ->execute(['pending_review', $method, now(), $subscriptionId]);

    return fetch_subscription_payment_by_id($paymentId) ?: [];
}

function approve_subscription_payment(int $paymentId, int $reviewerId, string $reviewNotes = ''): void
{
    $payment = fetch_subscription_payment_by_id($paymentId);
    if (!$payment) {
        throw new RuntimeException('Payment submission not found.');
    }

    $reviewerValue = $reviewerId > 0 ? $reviewerId : null;

    $startsAt = new DateTime();
    $durationDays = (int)($payment['duration_days'] ?? 0);
    $endsAtValue = null;
    if ($durationDays > 0) {
        $endsAtValue = (clone $startsAt)->modify('+' . $durationDays . ' days')->format('Y-m-d H:i:s');
    }

    db()->prepare('UPDATE subscription_payments SET status = ?, reviewed_by_user_id = ?, reviewed_at = ?, review_notes = ?, updated_at = ? WHERE id = ?')
        ->execute(['approved', $reviewerValue, now(), $reviewNotes !== '' ? $reviewNotes : null, now(), $paymentId]);

    db()->prepare('UPDATE client_subscriptions SET status = ?, starts_at = ?, ends_at = ?, approved_at = ?, approved_by_user_id = ?, review_notes = ?, updated_at = ? WHERE id = ?')
        ->execute([
            'active',
            $startsAt->format('Y-m-d H:i:s'),
            $endsAtValue,
            now(),
            $reviewerValue,
            $reviewNotes !== '' ? $reviewNotes : null,
            now(),
            $payment['subscription_id'],
        ]);
}

function reject_subscription_payment(int $paymentId, int $reviewerId, string $reviewNotes = ''): void
{
    $payment = fetch_subscription_payment_by_id($paymentId);
    if (!$payment) {
        throw new RuntimeException('Payment submission not found.');
    }

    $reviewerValue = $reviewerId > 0 ? $reviewerId : null;

    db()->prepare('UPDATE subscription_payments SET status = ?, reviewed_by_user_id = ?, reviewed_at = ?, review_notes = ?, updated_at = ? WHERE id = ?')
        ->execute(['rejected', $reviewerValue, now(), $reviewNotes !== '' ? $reviewNotes : null, now(), $paymentId]);

    db()->prepare('UPDATE client_subscriptions SET status = ?, review_notes = ?, updated_at = ? WHERE id = ?')
        ->execute(['rejected', $reviewNotes !== '' ? $reviewNotes : null, now(), $payment['subscription_id']]);
}

function set_memorial_public_override(int $clientUserId, bool $enabled, ?int $adminUserId = null, string $note = ''): void
{
    db()->prepare('UPDATE memorial_pages SET public_access_override = ?, public_access_override_note = ?, public_access_enabled_at = ?, public_access_enabled_by_user_id = ?, updated_at = ? WHERE client_user_id = ?')
        ->execute([
            $enabled ? 1 : 0,
            $note !== '' ? $note : null,
            $enabled ? now() : null,
            $enabled ? $adminUserId : null,
            now(),
            $clientUserId,
        ]);
}

function subscription_status_badge_class(string $status): string
{
    $map = [
        'active' => 'text-bg-success',
        'approved' => 'text-bg-success',
        'pending' => 'text-bg-warning',
        'pending_payment' => 'text-bg-secondary',
        'pending_review' => 'text-bg-warning',
        'rejected' => 'text-bg-danger',
        'expired' => 'text-bg-dark',
        'cancelled' => 'text-bg-dark',
    ];

    return $map[$status] ?? 'text-bg-light';
}

function fetch_timeline_items(int $memorialId): array
{
    $stmt = db()->prepare('SELECT * FROM memorial_timelines WHERE memorial_page_id = ? ORDER BY sort_order ASC, event_date ASC, id ASC');
    $stmt->execute([$memorialId]);
    return $stmt->fetchAll();
}

function fetch_gallery_items(int $memorialId): array
{
    $stmt = db()->prepare('SELECT * FROM memorial_gallery WHERE memorial_page_id = ? ORDER BY sort_order ASC, id ASC');
    $stmt->execute([$memorialId]);
    return $stmt->fetchAll();
}

function fetch_music_items(int $memorialId): array
{
    $stmt = db()->prepare('SELECT * FROM memorial_playlist WHERE memorial_page_id = ? ORDER BY sort_order ASC, id ASC');
    $stmt->execute([$memorialId]);
    $items = $stmt->fetchAll();

    foreach ($items as &$item) {
        $filePath = isset($item['file_path']) ? preg_replace('~/+~', '/', (string)$item['file_path']) : '';
        if (($item['type'] ?? '') === 'mp3' && $filePath !== '') {
            $item['file_path'] = $filePath;
            $item['music_url'] = rtrim(BASE_URL, '/') . '/' . ltrim($filePath, '/');
        } else {
            $item['music_url'] = $item['url'] ?? '';
        }
    }
    unset($item);

    return $items;
}

function fetch_messages(int $memorialId, bool $approvedOnly = true): array
{
    $sql = 'SELECT * FROM memorial_messages WHERE memorial_page_id = ?';
    if ($approvedOnly) {
        $sql .= ' AND is_approved = 1';
    }
    $sql .= ' ORDER BY created_at DESC';
    $stmt = db()->prepare($sql);
    $stmt->execute([$memorialId]);
    return $stmt->fetchAll();
}

function record_memorial_view(int $memorialId, int $cooldownSeconds = 1800): void
{
    $sessionKey = 'memorial_viewed_at_' . $memorialId;
    $lastViewedAt = (int)($_SESSION[$sessionKey] ?? 0);
    if ($lastViewedAt > 0 && (time() - $lastViewedAt) < $cooldownSeconds) {
        return;
    }

    db()->prepare('INSERT INTO memorial_page_views (memorial_page_id, session_id, visitor_ip_hash, user_agent, viewed_at) VALUES (?,?,?,?,?)')
        ->execute([
            $memorialId,
            session_id() ?: null,
            hash_ip(client_ip()),
            user_agent(),
            now(),
        ]);

    $_SESSION[$sessionKey] = time();
}

function count_memorial_views(int $memorialId): int
{
    $stmt = db()->prepare('SELECT COUNT(*) FROM memorial_page_views WHERE memorial_page_id = ?');
    $stmt->execute([$memorialId]);
    return (int)$stmt->fetchColumn();
}

function count_pending_messages(int $memorialId): int
{
    $stmt = db()->prepare('SELECT COUNT(*) FROM memorial_messages WHERE memorial_page_id = ? AND is_approved = 0');
    $stmt->execute([$memorialId]);
    return (int)$stmt->fetchColumn();
}

function count_messages(int $memorialId, bool $approvedOnly = false): int
{
    $sql = 'SELECT COUNT(*) FROM memorial_messages WHERE memorial_page_id = ?';
    if ($approvedOnly) {
        $sql .= ' AND is_approved = 1';
    }
    $stmt = db()->prepare($sql);
    $stmt->execute([$memorialId]);
    return (int)$stmt->fetchColumn();
}

function memorial_daily_views(int $memorialId, int $days = 7): array
{
    $days = max(1, min(31, $days));
    $stmt = db()->prepare('SELECT DATE(viewed_at) AS view_date, COUNT(*) AS total FROM memorial_page_views WHERE memorial_page_id = ? AND viewed_at >= DATE_SUB(CURDATE(), INTERVAL ' . ($days - 1) . ' DAY) GROUP BY DATE(viewed_at)');
    $stmt->execute([$memorialId]);
    $rows = $stmt->fetchAll();
    $counts = [];
    foreach ($rows as $row) {
        $counts[(string)$row['view_date']] = (int)$row['total'];
    }

    $labels = [];
    $data = [];
    for ($i = $days - 1; $i >= 0; $i--) {
        $date = (new DateTime())->modify('-' . $i . ' days');
        $key = $date->format('Y-m-d');
        $labels[] = $date->format('M j');
        $data[] = $counts[$key] ?? 0;
    }

    return [
        'labels' => $labels,
        'data' => $data,
    ];
}

function count_candles(int $memorialId): int
{
    $stmt = db()->prepare("SELECT COUNT(*) FROM memorial_reactions WHERE memorial_page_id = ? AND reaction_type = 'candle'");
    $stmt->execute([$memorialId]);
    return (int)$stmt->fetchColumn();
}

function count_hearts(int $memorialId): int
{
    $stmt = db()->prepare("SELECT COUNT(*) FROM memorial_reactions WHERE memorial_page_id = ? AND reaction_type = 'heart'");
    $stmt->execute([$memorialId]);
    return (int)$stmt->fetchColumn();
}

function recent_reactors(int $memorialId, string $reactionType, int $limit = 12): array
{
    $stmt = db()->prepare('SELECT visitor_name, MAX(created_at) AS last_created_at FROM memorial_reactions WHERE memorial_page_id = ? AND reaction_type = ? GROUP BY visitor_name ORDER BY last_created_at DESC LIMIT ' . (int)$limit);
    $stmt->execute([$memorialId, $reactionType]);
    return $stmt->fetchAll();
}

function log_audit(string $action, string $message, ?string $targetType = null, ?int $targetId = null, array $meta = []): void
{
    $actor = current_user();
    $stmt = db()->prepare('INSERT INTO audit_logs (actor_user_id, actor_role, action_name, message, target_type, target_id, ip_address, user_agent, metadata_json, created_at) VALUES (?,?,?,?,?,?,?,?,?,?)');
    $stmt->execute([
        $actor['id'] ?? null,
        $actor['role'] ?? null,
        $action,
        $message,
        $targetType,
        $targetId,
        client_ip(),
        user_agent(),
        $meta ? json_encode($meta, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) : null,
        now(),
    ]);
}

function login_is_rate_limited(string $identifier, string $ip): bool
{
    $window = (int)BRUTE_FORCE_WINDOW_MINUTES;
    $stmt = db()->prepare('SELECT COUNT(*) FROM auth_login_attempts WHERE success = 0 AND attempt_key = ? AND ip_address = ? AND created_at >= DATE_SUB(NOW(), INTERVAL ' . $window . ' MINUTE)');
    $stmt->execute([mb_strtolower(trim($identifier)), $ip]);
    return (int)$stmt->fetchColumn() >= BRUTE_FORCE_MAX_ATTEMPTS;
}

function record_login_attempt(string $identifier, string $ip, bool $success, ?int $userId = null): void
{
    $stmt = db()->prepare('INSERT INTO auth_login_attempts (attempt_key, ip_address, success, user_id, created_at) VALUES (?,?,?,?,?)');
    $stmt->execute([mb_strtolower(trim($identifier)), $ip, $success ? 1 : 0, $userId, now()]);
}

function issue_email_verification_token(int $userId, string $email): array
{
    $plain = random_token(32);
    $hash = hash('sha256', $plain);
    db()->prepare('UPDATE email_verification_tokens SET is_used = 1, used_at = ? WHERE user_id = ? AND is_used = 0')->execute([now(), $userId]);
    db()->prepare('INSERT INTO email_verification_tokens (user_id, email, token_hash, expires_at, created_at) VALUES (?,?,?,?,?)')
        ->execute([$userId, $email, $hash, date('Y-m-d H:i:s', time() + EMAIL_VERIFICATION_EXPIRY_HOURS * 3600), now()]);
    $url = rtrim(BASE_URL, '/') . '/verify_email.php?token=' . urlencode($plain);
    return ['plain_token' => $plain, 'url' => $url];
}

function verify_email_token(string $plainToken): ?array
{
    $hash = hash('sha256', trim($plainToken));
    $stmt = db()->prepare('SELECT * FROM email_verification_tokens WHERE token_hash = ? AND is_used = 0 AND expires_at >= NOW() ORDER BY id DESC LIMIT 1');
    $stmt->execute([$hash]);
    return $stmt->fetch() ?: null;
}

function mark_email_verified(int $userId, int $tokenId): void
{
    db()->prepare('UPDATE users SET is_email_verified = 1, email_verified_at = ?, is_active = 1, updated_at = ? WHERE id = ?')
        ->execute([now(), now(), $userId]);
    db()->prepare('UPDATE email_verification_tokens SET is_used = 1, used_at = ? WHERE id = ?')
        ->execute([now(), $tokenId]);
}

function send_verification_email(array $user, string $verificationUrl): bool
{
    require_once __DIR__ . '/mailer.php';
    $html = '<p>Hello ' . e($user['full_name']) . ',</p>'
        . '<p>Please verify your FurEver Memories client account by clicking the button below:</p>'
        . '<p><a href="' . e($verificationUrl) . '" style="display:inline-block;padding:12px 18px;background:#111;color:#fff;text-decoration:none;border-radius:8px;">Verify Email</a></p>'
        . '<p>If the button does not work, copy this link:</p><p>' . e($verificationUrl) . '</p>'
        . '<p>This link expires in ' . EMAIL_VERIFICATION_EXPIRY_HOURS . ' hours.</p>';
    return send_basic_mail($user['email'], 'Verify your FurEver Memories account', $html);
}

function upsert_memorial(int $clientUserId, array $data): int
{
    $existing = fetch_memorial_by_client_id($clientUserId);
    if ($existing) {
        $sql = 'UPDATE memorial_pages SET pet_name=?, pet_birth_date=?, pet_memorial_date=?, short_tribute=?, final_letter=?, video_type=?, video_url=?, video_file=?, bg_image_portrait=?, bg_image_landscape=?, cover_photo=?, share_footer_text=?, youtube_embed_url=?, video_max_mb=?, updated_at=? WHERE client_user_id=?';
        db()->prepare($sql)->execute([
            $data['pet_name'], $data['pet_birth_date'], $data['pet_memorial_date'], $data['short_tribute'], $data['final_letter'],
            $data['video_type'], $data['video_url'], $data['video_file'], $data['bg_image_portrait'], $data['bg_image_landscape'],
            $data['cover_photo'], $data['share_footer_text'], $data['youtube_embed_url'], $data['video_max_mb'], now(), $clientUserId,
        ]);
        return (int)$existing['id'];
    }

    $sql = 'INSERT INTO memorial_pages (client_user_id, pet_name, pet_birth_date, pet_memorial_date, short_tribute, final_letter, video_type, video_url, youtube_embed_url, video_file, bg_image_portrait, bg_image_landscape, cover_photo, share_footer_text, video_max_mb, created_at, updated_at) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)';
    db()->prepare($sql)->execute([
        $clientUserId, $data['pet_name'], $data['pet_birth_date'], $data['pet_memorial_date'], $data['short_tribute'], $data['final_letter'],
        $data['video_type'], $data['video_url'], $data['youtube_embed_url'], $data['video_file'], $data['bg_image_portrait'], $data['bg_image_landscape'],
        $data['cover_photo'], $data['share_footer_text'], $data['video_max_mb'], now(), now(),
    ]);
    return (int)db()->lastInsertId();
}

function normalize_youtube_embed_url(string $url): string
{
    $url = trim($url);
    if ($url === '') return '';
    if (preg_match('~(?:youtube\.com/watch\?v=|youtu\.be/)([A-Za-z0-9_-]{6,})~', $url, $m)) {
        return 'https://www.youtube.com/embed/' . $m[1];
    }
    if (strpos($url, 'youtube.com/embed/') !== false) {
        return $url;
    }
    return $url;
}

function qrcode_data_uri(string $text): string
{
    return 'https://api.qrserver.com/v1/create-qr-code/?size=220x220&data=' . rawurlencode($text);
}

function delete_memorial_message(int $messageId, int $memorialId): void
{
    $stmt = db()->prepare('DELETE FROM memorial_messages WHERE id = ? AND memorial_page_id = ?');
    $stmt->execute([$messageId, $memorialId]);
}

function approve_memorial_message(int $messageId, int $memorialId): void
{
    $stmt = db()->prepare('UPDATE memorial_messages SET is_approved = 1 WHERE id = ? AND memorial_page_id = ?');
    $stmt->execute([$messageId, $memorialId]);
}

function fetch_recent_audit_logs(int $limit = 200): array
{
    $stmt = db()->query('SELECT a.*, u.full_name AS actor_name FROM audit_logs a LEFT JOIN users u ON u.id = a.actor_user_id ORDER BY a.id DESC LIMIT ' . (int)$limit);
    return $stmt->fetchAll();
}

function count_audit_logs(): int
{
    return (int)db()->query('SELECT COUNT(*) FROM audit_logs')->fetchColumn();
}

function fetch_audit_logs_page(int $limit = 300, int $offset = 0): array
{
    $limit = max(1, min(1000, $limit));
    $offset = max(0, $offset);
    $stmt = db()->query('SELECT a.*, u.full_name AS actor_name FROM audit_logs a LEFT JOIN users u ON u.id = a.actor_user_id ORDER BY a.id DESC LIMIT ' . $limit . ' OFFSET ' . $offset);
    return $stmt->fetchAll();
}

function delete_oldest_audit_logs(int $count): int
{
    $count = max(0, min(100000, $count));
    if ($count <= 0) {
        return 0;
    }

    $stmt = db()->prepare('DELETE FROM audit_logs ORDER BY id ASC LIMIT ' . $count);
    $stmt->execute();
    return $stmt->rowCount();
}
