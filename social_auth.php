<?php
require_once __DIR__ . '/includes/auth.php';
send_security_headers();

$provider = strtolower(trim((string)($_GET['provider'] ?? '')));
$settings = social_provider_settings($provider);

if (empty($settings['configured'])) {
    flash_set('warning', ucfirst($provider) . ' sign-in is not configured yet on this environment.');
    redirect('login.php');
}

if (!function_exists('curl_init')) {
    flash_set('warning', 'Social sign-in requires the PHP cURL extension on this server.');
    redirect('login.php');
}

if (!empty($_GET['error'])) {
    flash_set('warning', ucfirst($provider) . ' sign-in was cancelled or denied.');
    redirect('login.php');
}

if (empty($_GET['code'])) {
    $state = issue_social_auth_state($provider);
    $query = [
        'client_id' => $settings['client_id'],
        'redirect_uri' => $settings['redirect_uri'],
        'response_type' => 'code',
        'scope' => $settings['scope'],
        'state' => $state,
    ];

    if ($provider === 'google') {
        $query['access_type'] = 'online';
        $query['prompt'] = 'select_account';
    }

    redirect($settings['auth_url'] . '?' . http_build_query($query));
}

$state = trim((string)($_GET['state'] ?? ''));
if (!verify_social_auth_state($provider, $state)) {
    flash_set('warning', 'The social login session expired. Please try again.');
    redirect('login.php');
}

$ch = curl_init($settings['token_url']);
$tokenFields = [
    'code' => trim((string)$_GET['code']),
    'client_id' => $settings['client_id'],
    'client_secret' => $settings['client_secret'],
    'redirect_uri' => $settings['redirect_uri'],
];

if ($provider === 'google') {
    $tokenFields['grant_type'] = 'authorization_code';
}

curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_POST => true,
    CURLOPT_POSTFIELDS => http_build_query($tokenFields),
    CURLOPT_HTTPHEADER => ['Accept: application/json'],
    CURLOPT_TIMEOUT => 30,
]);

$tokenResponse = curl_exec($ch);
$tokenHttpCode = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
$tokenError = curl_error($ch);
curl_close($ch);

if ($tokenError || $tokenHttpCode < 200 || $tokenHttpCode >= 300) {
    flash_set('warning', 'Unable to complete ' . ucfirst($provider) . ' sign-in right now.');
    redirect('login.php');
}

$tokenPayload = json_decode((string)$tokenResponse, true);
$accessToken = trim((string)($tokenPayload['access_token'] ?? ''));
if ($accessToken === '') {
    flash_set('warning', 'Unable to complete ' . ucfirst($provider) . ' sign-in right now.');
    redirect('login.php');
}

$userinfoUrl = $settings['userinfo_url'];
if ($provider === 'google') {
    $userinfoUrl .= '?alt=json';
}
if ($provider === 'facebook') {
    $userinfoUrl .= '&access_token=' . urlencode($accessToken);
}

$ch = curl_init($userinfoUrl);
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_TIMEOUT => 30,
    CURLOPT_HTTPHEADER => $provider === 'google'
        ? ['Authorization: Bearer ' . $accessToken, 'Accept: application/json']
        : ['Accept: application/json'],
]);
$profileResponse = curl_exec($ch);
$profileHttpCode = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
$profileError = curl_error($ch);
curl_close($ch);

if ($profileError || $profileHttpCode < 200 || $profileHttpCode >= 300) {
    flash_set('warning', 'Unable to fetch your ' . ucfirst($provider) . ' profile.');
    redirect('login.php');
}

$profile = json_decode((string)$profileResponse, true);
$providerUserId = trim((string)($profile['sub'] ?? $profile['id'] ?? ''));
$email = mb_strtolower(trim((string)($profile['email'] ?? '')));
$fullName = trim((string)($profile['name'] ?? ''));
$emailVerified = !empty($profile['email_verified']) || $provider === 'facebook';

if ($providerUserId === '' || $email === '') {
    flash_set('warning', ucfirst($provider) . ' did not return a usable email address for this account. Please use email/password registration instead.');
    redirect('login.php');
}

$user = fetch_user_by_social_identity($provider, $providerUserId);
if (!$user) {
    $user = fetch_user_by_email($email);
}

if ($user) {
    db()->prepare('UPDATE users SET auth_provider = ?, social_provider_user_id = ?, is_email_verified = ?, email_verified_at = COALESCE(email_verified_at, ?), updated_at = ? WHERE id = ?')
        ->execute([
            $provider,
            $providerUserId,
            $emailVerified ? 1 : (int)($user['is_email_verified'] ?? 0),
            $emailVerified ? now() : null,
            now(),
            $user['id'],
        ]);
    $user = fetch_user_by_id((int)$user['id']);
} else {
    $user = create_client_account([
        'full_name' => $fullName !== '' ? $fullName : 'FurEver Client',
        'contact_number' => '',
        'address' => '',
        'email' => $email,
        'password_hash' => password_hash(random_token(20), PASSWORD_DEFAULT),
        'auth_provider' => $provider,
        'social_provider_user_id' => $providerUserId,
        'is_email_verified' => $emailVerified,
    ]);
    log_audit('client.social_register', 'Client account created through social sign-in.', 'user', (int)$user['id'], [
        'provider' => $provider,
        'email' => $email,
    ]);
}

if (!$user || empty($user['id'])) {
    flash_set('warning', 'Unable to complete sign-in. Please try again.');
    redirect('login.php');
}

record_login_attempt($email, client_ip(), true, (int)$user['id']);
db()->prepare('UPDATE users SET last_login_at = ?, last_login_ip = ?, updated_at = ? WHERE id = ?')
    ->execute([now(), client_ip(), now(), $user['id']]);
login_user(fetch_user_by_id((int)$user['id']));
log_audit('login.social_success', 'User logged in successfully using social sign-in.', 'user', (int)$user['id'], ['provider' => $provider]);
redirect('dashboard.php');
