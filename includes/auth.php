<?php
require_once __DIR__ . '/functions.php';

function login_user(array $user): void
{
    session_regenerate_id(true);
    unset($user['password_hash']);
    $_SESSION['user'] = $user;
}

function refresh_logged_user(): void
{
    if (!empty($_SESSION['user']['id'])) {
        $fresh = fetch_user_by_id((int)$_SESSION['user']['id']);
        if ($fresh) {
            unset($fresh['password_hash']);
            $_SESSION['user'] = $fresh;
        }
    }
}

function logout_user(): void
{
    $_SESSION = [];
    if (ini_get('session.use_cookies')) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000, $params['path'], $params['domain'], $params['secure'], $params['httponly']);
    }
    session_destroy();
}
