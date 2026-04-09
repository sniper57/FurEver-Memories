<?php
require_once __DIR__ . '/includes/functions.php';
send_security_headers();

$clientGuid = trim($_GET['clientguid'] ?? $_GET['c'] ?? '');
if ($clientGuid === '') { http_response_code(404); exit('Memorial page not found.'); }
$client = fetch_client_by_guid($clientGuid);
if (!$client) { http_response_code(404); exit('Client not found.'); }
$memorial = fetch_memorial_by_client_id((int)$client['id']);
if (!$memorial) { http_response_code(404); exit('Memorial page not configured.'); }

$memorialId = (int)$memorial['id'];
$messageError = '';

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

        if (in_array($_POST['action'], ['candle', 'heart'], true)) {
            $visitorName = trim($_POST['visitor_name'] ?? 'Anonymous');
            db()->prepare('INSERT INTO memorial_reactions (memorial_page_id, reaction_type, visitor_name, visitor_ip_hash, user_agent, created_at) VALUES (?,?,?,?,?,?)')
                ->execute([$memorialId, $_POST['action'], $visitorName, hash_ip(client_ip()), user_agent(), now()]);
            log_audit('public.reaction.' . $_POST['action'], 'Public visitor sent reaction.', 'memorial_page', $memorialId, ['visitor_name' => $visitorName]);
            redirect(public_memorial_url($clientGuid) . '#tribute-actions');
        }
    } catch (Throwable $e) {
        $messageError = $e->getMessage();
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
$bgPortrait = !empty($memorial['bg_image_portrait']) ? UPLOAD_URL . '/' . $memorial['bg_image_portrait'] : '';
$bgLandscape = !empty($memorial['bg_image_landscape']) ? UPLOAD_URL . '/' . $memorial['bg_image_landscape'] : '';
$publicUrl = public_memorial_url($clientGuid);
$flashSuccess = flash_get('success');
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
<?php if ($flashSuccess || $messageError): ?>
<div class="container pt-3">
    <?php if ($flashSuccess): ?><div class="alert alert-success"><?= e($flashSuccess) ?></div><?php endif; ?>
    <?php if ($messageError): ?><div class="alert alert-danger"><?= e($messageError) ?></div><?php endif; ?>
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
