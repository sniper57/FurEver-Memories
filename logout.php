<?php
require_once __DIR__ . '/includes/auth.php';
if (is_logged_in()) {
    log_audit('logout', 'User logged out.', 'user', (int)(current_user()['id'] ?? 0));
}
logout_user();
redirect('login.php');
