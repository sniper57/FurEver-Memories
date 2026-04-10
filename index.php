<?php
require_once __DIR__ . '/includes/auth.php';
send_security_headers();

$clientGuid = trim($_GET['c'] ?? $_GET['clientguid'] ?? '');
if ($clientGuid === '') {
    $requestPath = parse_url($_SERVER['REQUEST_URI'] ?? '', PHP_URL_PATH) ?: '';
    if (preg_match('~/c/([^/?#]+)$~', $requestPath, $matches)) {
        $clientGuid = rawurldecode($matches[1]);
    }
}

$isMemorialPage = ($clientGuid !== '');

$marketingLoginError = '';
$marketingLoginWarning = '';
$marketingLoginEmail = '';
$showMarketingLoginModal = false;

if (!$isMemorialPage) {
    $marketingLoginWarning = flash_get('warning');

    if ($_SERVER['REQUEST_METHOD'] === 'POST' && (($_POST['action'] ?? '') === 'marketing_login')) {
        verify_csrf_or_fail();
        $marketingLoginEmail = mb_strtolower(trim($_POST['email'] ?? ''));
        $password = (string)($_POST['password'] ?? '');
        $ip = client_ip();

        try {
            if (login_is_rate_limited($marketingLoginEmail, $ip)) {
                throw new RuntimeException('Too many failed attempts. Please try again after ' . BRUTE_FORCE_WINDOW_MINUTES . ' minutes.');
            }

            $user = fetch_user_by_email($marketingLoginEmail);
            if ($user && !empty($user['is_active']) && password_verify($password, $user['password_hash'])) {
                if ($user['role'] === 'client' && empty($user['is_email_verified'])) {
                    $marketingLoginError = 'Your account exists but your email is not yet verified.';
                    record_login_attempt($marketingLoginEmail, $ip, false, (int)$user['id']);
                    log_audit('login.unverified', 'Blocked login for unverified client.', 'user', (int)$user['id']);
                } else {
                    db()->prepare('UPDATE users SET last_login_at = ?, last_login_ip = ?, updated_at = ? WHERE id = ?')
                        ->execute([now(), $ip, now(), $user['id']]);
                    record_login_attempt($marketingLoginEmail, $ip, true, (int)$user['id']);
                    login_user(fetch_user_by_id((int)$user['id']));
                    log_audit('login.success', 'User logged in successfully.', 'user', (int)$user['id']);
                    redirect('dashboard.php');
                }
            } else {
                record_login_attempt($marketingLoginEmail, $ip, false, $user['id'] ?? null);
                throw new RuntimeException('Invalid login credentials.');
            }
        } catch (Throwable $e) {
            $marketingLoginError = $e->getMessage();
            $showMarketingLoginModal = true;
            log_audit('login.failed', 'Failed login attempt from marketing modal.', 'user', $user['id'] ?? null, ['email' => $marketingLoginEmail]);
        }
    }

    if ($marketingLoginWarning !== '') {
        $showMarketingLoginModal = true;
    }
    ?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= e(APP_NAME) ?> | Forever in our hearts</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/site.css">
</head>
<body class="marketing-homepage">
<?php include __DIR__ . '/modules/module_marketing_home.php'; ?>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="assets/js/site.js"></script>
<?php if ($showMarketingLoginModal): ?>
<script>
document.addEventListener('DOMContentLoaded', function () {
    const marketingLoginModal = document.getElementById('marketingLoginModal');
    if (!marketingLoginModal || typeof bootstrap === 'undefined') {
        return;
    }
    bootstrap.Modal.getOrCreateInstance(marketingLoginModal).show();
});
</script>
<?php endif; ?>
</body>
</html>
    <?php
    exit;
}

$client = fetch_client_by_guid($clientGuid);
if (!$client) { http_response_code(404); exit('Client not found.'); }
$memorial = fetch_memorial_by_client_id((int)$client['id']);
if (!$memorial) { http_response_code(404); exit('Memorial page not configured.'); }

$memorialId = (int)$memorial['id'];
$messageError = '';
$supportError = '';
record_memorial_view($memorialId);

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    verify_csrf_or_fail();
    try {
        if ($_POST['action'] === 'message') {
            require_once __DIR__ . '/includes/upload_helpers.php';
            $visitorName = trim($_POST['visitor_name'] ?? '');
            $message = trim($_POST['message'] ?? '');
            if ($visitorName === '' || $message === '') {
                throw new RuntimeException('Name and message are required.');
            }
            $photoPath = '';
            if (!empty($_FILES['visitor_photo']['tmp_name'])) {
                $photoPath = save_optimized_image($_FILES['visitor_photo'], $clientGuid . '/visitors', 'visitor');
            }
            db()->prepare('INSERT INTO memorial_messages (memorial_page_id, visitor_name, visitor_photo, message, visitor_ip_hash, user_agent, is_approved, created_at) VALUES (?,?,?,?,?,?,?,?)')
                ->execute([$memorialId, $visitorName, $photoPath, $message, hash_ip(client_ip()), user_agent(), 0, now()]);
            log_audit('public.message.create', 'Public visitor submitted message.', 'memorial_page', $memorialId, ['visitor_name' => $visitorName]);
            flash_set('success', 'Your message was submitted and is waiting for approval.');
            redirect(public_memorial_url($clientGuid) . '#messages');
        }

        if ($_POST['action'] === 'support_contact') {
            require_once __DIR__ . '/includes/mailer.php';

            $inquiryType = trim($_POST['support_inquiry_type'] ?? 'Make a suggestion');
            $visitorName = trim($_POST['support_name'] ?? '');
            $visitorEmail = trim($_POST['support_email'] ?? '');
            $subject = trim($_POST['support_subject'] ?? '');
            $message = trim($_POST['support_message'] ?? '');

            if ($visitorName === '' || $visitorEmail === '' || $subject === '' || $message === '') {
                throw new RuntimeException('Please complete all support form fields.');
            }

            if (!filter_var($visitorEmail, FILTER_VALIDATE_EMAIL)) {
                throw new RuntimeException('Please enter a valid email address.');
            }

            $supportHtml = ''
                . '<p><strong>Memorial:</strong> ' . e($memorial['pet_name'] ?: 'Untitled memorial') . '</p>'
                . '<p><strong>Public URL:</strong> ' . e($publicUrl = public_memorial_url($clientGuid)) . '</p>'
                . '<p><strong>Inquiry Type:</strong> ' . e($inquiryType) . '</p>'
                . '<p><strong>Name:</strong> ' . e($visitorName) . '</p>'
                . '<p><strong>Email:</strong> ' . e($visitorEmail) . '</p>'
                . '<p><strong>Subject:</strong> ' . e($subject) . '</p>'
                . '<p><strong>Message:</strong></p>'
                . '<div>' . nl2br(e($message)) . '</div>';

            $mailSent = send_basic_mail(
                MAIL_FROM_EMAIL,
                '[FurEver Memories Support] ' . $subject,
                $supportHtml,
                $visitorEmail
            );

            if (!$mailSent) {
                throw new RuntimeException('We could not send your message right now. Please try again in a little while.');
            }

            log_audit('public.support_contact', 'Public visitor submitted support request.', 'memorial_page', $memorialId, [
                'inquiry_type' => $inquiryType,
                'visitor_name' => $visitorName,
                'visitor_email' => $visitorEmail,
                'subject' => $subject,
            ]);
            flash_set('support_success', 'Thank you. Your message was sent to our support team.');
            redirect(public_memorial_url($clientGuid) . '#footer-contact');
        }

        if (in_array($_POST['action'], ['candle', 'heart'], true)) {
            $visitorName = trim($_POST['visitor_name'] ?? 'Anonymous');
            db()->prepare('INSERT INTO memorial_reactions (memorial_page_id, reaction_type, visitor_name, visitor_ip_hash, user_agent, created_at) VALUES (?,?,?,?,?,?)')
                ->execute([$memorialId, $_POST['action'], $visitorName, hash_ip(client_ip()), user_agent(), now()]);
            log_audit('public.reaction.' . $_POST['action'], 'Public visitor sent reaction.', 'memorial_page', $memorialId, ['visitor_name' => $visitorName]);
            redirect(public_memorial_url($clientGuid) . '#tribute-actions');
        }
    } catch (Throwable $e) {
        if (($_POST['action'] ?? '') === 'support_contact') {
            $supportError = $e->getMessage();
        } else {
            $messageError = $e->getMessage();
        }
    }
}

$timelines = fetch_timeline_items($memorialId);
$gallery = fetch_gallery_items($memorialId);
$messages = fetch_messages($memorialId, true);
$music = fetch_music_items($memorialId);
$candleCount = count_candles($memorialId);
$heartCount = count_hearts($memorialId);
$candleNames = recent_reactors($memorialId, 'candle');
$heartNames = recent_reactors($memorialId, 'heart');
$viewCount = count_memorial_views($memorialId);
$bgPortrait = !empty($memorial['bg_image_portrait']) ? UPLOAD_URL . '/' . $memorial['bg_image_portrait'] : '';
$bgLandscape = !empty($memorial['bg_image_landscape']) ? UPLOAD_URL . '/' . $memorial['bg_image_landscape'] : '';
$publicUrl = public_memorial_url($clientGuid);
$flashSuccess = flash_get('success');
$supportSuccess = flash_get('support_success');
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= e($memorial['pet_name'] ?: 'FurEver Memories') ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/photoswipe@5.4.4/dist/photoswipe.css">
    <link rel="stylesheet" href="assets/css/site.css">
    <style>:root{--bg-portrait:url('<?= e($bgPortrait) ?>');--bg-landscape:url('<?= e($bgLandscape) ?>');}</style>
</head>
<body>
<?php if ($flashSuccess || $messageError || $supportSuccess || $supportError): ?>
<div class="container pt-3">
    <?php if ($flashSuccess): ?><div class="alert alert-success"><?= e($flashSuccess) ?></div><?php endif; ?>
    <?php if ($messageError): ?><div class="alert alert-danger"><?= e($messageError) ?></div><?php endif; ?>
    <?php if ($supportSuccess): ?><div class="alert alert-success"><?= e($supportSuccess) ?></div><?php endif; ?>
    <?php if ($supportError): ?><div class="alert alert-danger"><?= e($supportError) ?></div><?php endif; ?>
</div>
<?php endif; ?>
<header><?php include __DIR__ . '/modules/module_petcoverpage.php'; ?></header>
<main>
    <?php include __DIR__ . '/modules/module_storytimeline.php'; ?>
    <?php include __DIR__ . '/modules/module_petimagecarousell.php'; ?>
    <?php include __DIR__ . '/modules/module_video_tribute.php'; ?>
    <?php include __DIR__ . '/modules/module_messages.php'; ?>
    <?php include __DIR__ . '/modules/module_reactions.php'; ?>
    <?php include __DIR__ . '/modules/module_final_letter.php'; ?>
</main>
<footer><?php include __DIR__ . '/modules/module_footer.php'; ?></footer>
<?php include __DIR__ . '/modules/module_music_player.php'; ?>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script type="module">
import PhotoSwipeLightbox from 'https://cdn.jsdelivr.net/npm/photoswipe@5.4.4/dist/photoswipe-lightbox.esm.min.js';
const lightbox = new PhotoSwipeLightbox({gallery: '#gallery-photoswipe', children: 'a', pswpModule: () => import('https://cdn.jsdelivr.net/npm/photoswipe@5.4.4/dist/photoswipe.esm.min.js')});
lightbox.init();
</script>
<script src="assets/js/site.js"></script>
</body>
</html>
