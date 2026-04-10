<?php
require_once __DIR__ . '/includes/auth.php';
send_security_headers();

if (is_logged_in()) {
    redirect('dashboard.php');
}

$success = '';
$error = '';
$verificationLink = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf_or_fail();

    try {
        $fullName = trim((string)($_POST['full_name'] ?? ''));
        $contactNumber = trim((string)($_POST['contact_number'] ?? ''));
        $address = trim((string)($_POST['address'] ?? ''));
        $email = mb_strtolower(trim((string)($_POST['email'] ?? '')));
        $password = (string)($_POST['password'] ?? '');
        $confirmPassword = (string)($_POST['confirm_password'] ?? '');

        if ($fullName === '' || $email === '' || $password === '') {
            throw new RuntimeException('Full name, email, and password are required.');
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new RuntimeException('Please enter a valid email address.');
        }

        if ($password !== $confirmPassword) {
            throw new RuntimeException('Password confirmation does not match.');
        }

        if (!password_is_strong_enough($password)) {
            throw new RuntimeException('Password must be at least 8 characters long and include uppercase, lowercase, and a number.');
        }

        $user = create_client_account([
            'full_name' => $fullName,
            'contact_number' => $contactNumber,
            'address' => $address,
            'email' => $email,
            'password_hash' => password_hash($password, PASSWORD_DEFAULT),
            'auth_provider' => 'password',
            'is_email_verified' => false,
        ]);

        $issued = issue_email_verification_token((int)$user['id'], $user['email']);
        $verificationLink = $issued['url'];
        $sent = send_verification_email($user, $verificationLink);

        log_audit('client.self_register', 'Client created an account from the public registration page.', 'user', (int)$user['id'], [
            'email' => $user['email'],
            'verification_sent' => $sent,
        ]);

        $success = 'Your FurEver Memories account has been created. Please verify your email before accessing the Memorial Builder.';
        if (!$sent) {
            $success .= ' Mail sending is not available on this server right now. Please try again later or contact support.';
        }
    } catch (Throwable $e) {
        $error = $e->getMessage();
    }
}
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Create Your Memorial Account - <?= e(APP_NAME) ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/site.css">
</head>
<body class="login-page">
<div class="login-shell">
    <div class="container">
        <div class="login-card">
            <div class="row g-0">
                <div class="col-lg-5">
                    <section class="login-brand-panel">
                        <a href="index.php" class="login-brand-mark text-decoration-none">
                            <img src="assets/images/logo-furever-memories.png" alt="FurEver Memories logo" class="login-brand-logo">
                            <span><?= e(APP_NAME) ?></span>
                        </a>
                        <span class="marketing-kicker">Create a memorial</span>
                        <h1 class="login-hero-title">Start with a private memorial space, then open it to the people who loved your pet most.</h1>
                        <p class="login-hero-copy">Create your client account, verify your email, build your memorial in private preview mode, and submit your subscription payment when you are ready to share it publicly.</p>
                        <div class="login-brand-pills">
                            <span class="marketing-pill">Private preview first</span>
                            <span class="marketing-pill">Email verification</span>
                            <span class="marketing-pill">Public sharing after approval</span>
                        </div>
                    </section>
                </div>
                <div class="col-lg-7">
                    <section class="login-form-panel">
                        <div class="login-form-wrap">
                            <div class="login-form-copy">
                                <span class="login-form-kicker">Create account</span>
                                <h2>Set up your FurEver Memories client account</h2>
                                <p>After registration, we will send a verification link to your email before you can access the Memorial Builder.</p>
                            </div>
                            <?php if ($success): ?><div class="alert alert-success"><?= e($success) ?></div><?php endif; ?>
                            <?php if ($error): ?><div class="alert alert-danger"><?= e($error) ?></div><?php endif; ?>
                            <form method="post" class="login-form">
                                <?= csrf_input() ?>
                                <div class="row g-3">
                                    <div class="col-md-6">
                                        <label class="form-label login-form-label">Full name</label>
                                        <input type="text" name="full_name" class="form-control login-form-control" required>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label login-form-label">Contact number</label>
                                        <input type="text" name="contact_number" class="form-control login-form-control">
                                    </div>
                                    <div class="col-12">
                                        <label class="form-label login-form-label">Address</label>
                                        <textarea name="address" class="form-control login-form-control" rows="3"></textarea>
                                    </div>
                                    <div class="col-12">
                                        <label class="form-label login-form-label">Email address</label>
                                        <input type="email" name="email" class="form-control login-form-control" required>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label login-form-label">Password</label>
                                        <input type="password" name="password" class="form-control login-form-control" required>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label login-form-label">Confirm password</label>
                                        <input type="password" name="confirm_password" class="form-control login-form-control" required>
                                    </div>
                                </div>
                                <button class="btn login-submit-btn w-100 mt-4">Create My Memorial Account</button>
                            </form>
                            <div class="login-form-footer">
                                <a href="login.php" class="text-decoration-none">Already have an account? Sign in</a>
                            </div>
                        </div>
                    </section>
                </div>
            </div>
        </div>
    </div>
</div>
</body>
</html>
