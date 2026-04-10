<?php
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/upload_helpers.php';
require_login();
require_verified_client();
send_security_headers();

$requestedGuid = trim($_GET['clientguid'] ?? '');
$client = null;

if (is_admin() && $requestedGuid !== '') {
    $client = fetch_client_by_guid($requestedGuid);
    if (!$client) {
        exit('Client not found.');
    }
} else {
    $client = fetch_user_by_id((int)current_user()['id']);
}

if (!$client || $client['role'] !== 'client') {
    exit('Client record not found.');
}

$memorial = fetch_memorial_by_client_id((int)$client['id']);
if (!$memorial) {
    $memorialId = upsert_memorial((int)$client['id'], [
        'pet_name' => '', 'pet_birth_date' => null, 'pet_memorial_date' => null,
        'short_tribute' => '', 'final_letter' => '', 'video_type' => 'none',
        'video_url' => '', 'video_file' => '', 'bg_image_portrait' => '', 'bg_image_landscape' => '',
        'cover_photo' => '', 'share_footer_text' => 'Created with love through FurEver Memories', 'youtube_embed_url' => '', 'video_max_mb' => DEFAULT_VIDEO_MAX_MB,
    ]);
    $memorial = fetch_memorial_by_client_id((int)$client['id']);
}

$success = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf_or_fail();
    try {
        $videoMaxMb = max(5, (int)($_POST['video_max_mb'] ?? DEFAULT_VIDEO_MAX_MB));
        $folder = $client['client_guid'];

        $bgPortrait = $memorial['bg_image_portrait'] ?? '';
        $bgLandscape = $memorial['bg_image_landscape'] ?? '';
        $coverPhoto = $memorial['cover_photo'] ?? '';
        $videoFile = $memorial['video_file'] ?? '';

        if (!empty($_FILES['bg_portrait']['tmp_name'])) {
            $bgPortrait = save_optimized_image($_FILES['bg_portrait'], $folder, 'bg_portrait');
        }
        if (!empty($_FILES['bg_landscape']['tmp_name'])) {
            $bgLandscape = save_optimized_image($_FILES['bg_landscape'], $folder, 'bg_landscape');
        }
        if (!empty($_FILES['cover_photo']['tmp_name'])) {
            $coverPhoto = save_optimized_image($_FILES['cover_photo'], $folder, 'cover');
        }

        $videoType = $_POST['video_type'] ?? 'none';
        if ($videoType === 'file' && !empty($_FILES['video_file']['tmp_name'])) {
            $videoFile = save_video_file($_FILES['video_file'], $folder, $videoMaxMb);
        }

        $youtubeUrl = normalize_youtube_embed_url(trim($_POST['video_url'] ?? ''));

        $memorialId = upsert_memorial((int)$client['id'], [
            'pet_name' => trim($_POST['pet_name'] ?? ''),
            'pet_birth_date' => ($_POST['pet_birth_date'] ?? '') ?: null,
            'pet_memorial_date' => ($_POST['pet_memorial_date'] ?? '') ?: null,
            'short_tribute' => trim($_POST['short_tribute'] ?? ''),
            'final_letter' => trim($_POST['final_letter'] ?? ''),
            'video_type' => $videoType,
            'video_url' => trim($_POST['video_url'] ?? ''),
            'video_file' => $videoFile,
            'bg_image_portrait' => $bgPortrait,
            'bg_image_landscape' => $bgLandscape,
            'cover_photo' => $coverPhoto,
            'share_footer_text' => trim($_POST['share_footer_text'] ?? 'Created with love through FurEver Memories'),
            'youtube_embed_url' => $youtubeUrl,
            'video_max_mb' => $videoMaxMb,
        ]);

        db()->prepare('DELETE FROM memorial_timelines WHERE memorial_page_id = ?')->execute([$memorialId]);
        if (!empty($_POST['timeline_title'])) {
            foreach ($_POST['timeline_title'] as $idx => $title) {
                $title = trim($title);
                $date = trim($_POST['timeline_date'][$idx] ?? '');
                $desc = trim($_POST['timeline_description'][$idx] ?? '');
                $existingPhoto = trim($_POST['timeline_existing_photo'][$idx] ?? '');
                $timelinePhoto = $existingPhoto;
                if (!empty($_FILES['timeline_photo']['tmp_name'][$idx])) {
                    $tmp = [
                        'name' => $_FILES['timeline_photo']['name'][$idx],
                        'type' => $_FILES['timeline_photo']['type'][$idx],
                        'tmp_name' => $_FILES['timeline_photo']['tmp_name'][$idx],
                        'error' => $_FILES['timeline_photo']['error'][$idx],
                        'size' => $_FILES['timeline_photo']['size'][$idx],
                    ];
                    $timelinePhoto = save_optimized_image($tmp, $folder, 'timeline');
                }
                if ($title !== '' || $desc !== '' || $timelinePhoto !== '') {
                    db()->prepare('INSERT INTO memorial_timelines (memorial_page_id, title, event_date, photo_path, description, sort_order, created_at) VALUES (?,?,?,?,?,?,?)')
                        ->execute([$memorialId, $title, $date ?: null, $timelinePhoto, $desc, $idx + 1, now()]);
                }
            }
        }
        
        

        db()->prepare('DELETE FROM memorial_gallery WHERE memorial_page_id = ?')->execute([$memorialId]);
        $gallerySort = 1;

        if (!empty($_POST['gallery_existing_photo'])) {
            foreach ($_POST['gallery_existing_photo'] as $existingPhoto) {
                $existingPhoto = trim((string)$existingPhoto);
                if ($existingPhoto !== '') {
                    db()->prepare('INSERT INTO memorial_gallery (memorial_page_id, photo_path, caption, sort_order, created_at) VALUES (?,?,?,?,?)')
                        ->execute([$memorialId, $existingPhoto, '', $gallerySort++, now()]);
                }
            }
        }

        if (!empty($_FILES['gallery_photo_batch']['name']) && is_array($_FILES['gallery_photo_batch']['name'])) {
            foreach ($_FILES['gallery_photo_batch']['name'] as $idx => $name) {
                if (empty($_FILES['gallery_photo_batch']['tmp_name'][$idx])) {
                    continue;
                }
                $tmp = [
                    'name' => $_FILES['gallery_photo_batch']['name'][$idx],
                    'type' => $_FILES['gallery_photo_batch']['type'][$idx],
                    'tmp_name' => $_FILES['gallery_photo_batch']['tmp_name'][$idx],
                    'error' => $_FILES['gallery_photo_batch']['error'][$idx],
                    'size' => $_FILES['gallery_photo_batch']['size'][$idx],
                ];
                $galleryPhoto = save_optimized_image($tmp, $folder, 'gallery');
                if ($galleryPhoto !== '') {
                    db()->prepare('INSERT INTO memorial_gallery (memorial_page_id, photo_path, caption, sort_order, created_at) VALUES (?,?,?,?,?)')
                        ->execute([$memorialId, $galleryPhoto, '', $gallerySort++, now()]);
                }
            }
        }

        db()->prepare('DELETE FROM memorial_music WHERE memorial_page_id = ?')->execute([$memorialId]);
        if (!empty($_POST['music_url'])) {
            foreach ($_POST['music_url'] as $idx => $musicUrl) {
                $musicUrl = trim($musicUrl);
                $musicTitle = trim($_POST['music_title'][$idx] ?? '');
                if ($musicUrl !== '') {
                    db()->prepare('INSERT INTO memorial_music (memorial_page_id, title, music_url, sort_order, created_at) VALUES (?,?,?,?,?)')
                        ->execute([$memorialId, $musicTitle, $musicUrl, $idx + 1, now()]);
                }
            }
        }
        
        $musicSort = 1;

        db()->prepare('DELETE FROM memorial_playlist WHERE memorial_page_id = ?')
            ->execute([$memorialId]);
        
        if (!empty($_POST['music_type']) && is_array($_POST['music_type'])) {
            foreach ($_POST['music_type'] as $i => $type) {
                $type = trim((string)$type);
                $title = trim($_POST['music_title'][$i] ?? '');
                $url = trim($_POST['music_url'][$i] ?? '');
                $filePath = trim($_POST['music_existing_file'][$i] ?? '');
        
                if (!in_array($type, ['youtube', 'mp3'], true)) {
                    continue;
                }
        
                if ($type === 'youtube') {
                    if ($url === '') {
                        continue;
                    }
        
                    db()->prepare('INSERT INTO memorial_playlist (memorial_page_id, type, title, url, file_path, sort_order, created_at) VALUES (?,?,?,?,?,?,?)')
                        ->execute([$memorialId, $type, $title, $url, '', $musicSort++, now()]);
                }
        
                if ($type === 'mp3') {
                    $hasUpload =
                        isset($_FILES['music_file']['name'][$i]) &&
                        $_FILES['music_file']['name'][$i] !== '' &&
                        isset($_FILES['music_file']['tmp_name'][$i]) &&
                        is_uploaded_file($_FILES['music_file']['tmp_name'][$i]);
        
                    if (!$hasUpload && $filePath !== '') {
                        if ($title === '') {
                            $title = pathinfo((string)basename($filePath), PATHINFO_FILENAME);
                        }
                        db()->prepare('INSERT INTO memorial_playlist (memorial_page_id, type, title, url, file_path, sort_order, created_at) VALUES (?,?,?,?,?,?,?)')
                            ->execute([$memorialId, $type, $title, '', $filePath, $musicSort++, now()]);
                        continue;
                    }

                    if (!$hasUpload) {
                        continue;
                    }

                    $tmpName = $_FILES['music_file']['tmp_name'][$i];
                    $originalName = $_FILES['music_file']['name'][$i];
                    $fileSize = (int)($_FILES['music_file']['size'][$i] ?? 0);
                    $fileError = (int)($_FILES['music_file']['error'][$i] ?? UPLOAD_ERR_NO_FILE);
                    if ($title === '') {
                        $title = pathinfo((string)$originalName, PATHINFO_FILENAME);
                    }

                    if ($fileError !== UPLOAD_ERR_OK) {
                        continue;
                    }
        
                    if ($fileSize <= 0 || $fileSize > 20 * 1024 * 1024) {
                        continue;
                    }
        
                    $ext = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));
                    if ($ext !== 'mp3') {
                        continue;
                    }
        
                    $musicDir = __DIR__ . '/uploads/music/' . $client['client_guid'] . '/';
                    $musicRelDir = 'uploads/music/' . $client['client_guid'] . '/';
        
                    if (!is_dir($musicDir)) {
                        mkdir($musicDir, 0775, true);
                    }
        
                    $newFileName = bin2hex(random_bytes(16)) . '.mp3';
                    $destAbs = $musicDir . $newFileName;
                    $filePath = $musicRelDir . $newFileName;
        
                    if (!move_uploaded_file($tmpName, $destAbs)) {
                        continue;
                    }
        
                    db()->prepare('INSERT INTO memorial_playlist (memorial_page_id, type, title, url, file_path, sort_order, created_at) VALUES (?,?,?,?,?,?,?)')
                        ->execute([$memorialId, $type, $title, '', $filePath, $musicSort++, now()]);
                }
            }
        }

        $success = 'Memorial page updated successfully.';
        $memorial = fetch_memorial_by_client_id((int)$client['id']);
    } catch (Throwable $e) {
        $error = $e->getMessage();
    }
}

$memorialId = (int)$memorial['id'];
$timelines = fetch_timeline_items($memorialId);
$gallery = fetch_gallery_items($memorialId);
$music = fetch_music_items($memorialId);
$publicUrl = public_memorial_url($client['client_guid']);
$accessSummary = memorial_public_access_summary((int)$client['id'], $memorial);
$qrDownloadName = preg_replace('/[^A-Za-z0-9]+/', '-', trim($client['full_name']));
$qrDownloadName = trim((string)$qrDownloadName, '-');
if ($qrDownloadName === '') {
    $qrDownloadName = 'client';
}
$qrDownloadName .= '-furever-memories-qr.png';
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Memorial Builder - <?= e(APP_NAME) ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/site.css">
    <style>
        .thumb-preview{width:80px;height:80px;object-fit:cover;border-radius:12px;border:1px solid #ddd}
        .builder-preview-frame{
            margin-top:12px;
            border:1px solid #d9d2c7;
            border-radius:20px;
            background:linear-gradient(180deg,#fbf8f4,#f0e9e0);
            overflow:hidden;
            display:flex;
            align-items:center;
            justify-content:center;
            box-shadow:0 10px 24px rgba(32,22,12,.08);
        }
        .builder-preview-frame--portrait{
            width:min(100%, 360px);
            aspect-ratio:9 / 16;
        }
        .builder-preview-frame--landscape{
            width:100%;
            aspect-ratio:16 / 9;
        }
        .builder-preview-frame--cover{
            width:min(100%, 360px);
            aspect-ratio:4 / 5;
        }
        .builder-preview-image{
            width:100%;
            height:100%;
            object-fit:contain;
            object-position:center;
            display:block;
            background:#f6f1ea;
        }
        .builder-preview-frame--timeline{
            width:min(100%, 240px);
            aspect-ratio:4 / 3;
        }
        .builder-preview-frame.is-empty{
            display:none;
        }
        .music-row .mp3-field .form-text{
            margin-top:6px;
        }
    </style>
</head>
<body class="admin-page">
<?php include __DIR__ . '/includes/topbar.php'; ?>
<div class="container py-4 admin-shell">
    <div class="d-flex justify-content-between align-items-center mb-3 gap-2 flex-wrap">
        <div>
            <h1 class="h3 mb-1">Memorial Builder</h1>
            <div class="text-muted">Client: <?= e($client['full_name']) ?></div>
            <div class="small text-muted mt-1"><?= e($accessSummary['label']) ?></div>
        </div>
        <div class="d-flex gap-2 flex-wrap">
            <a href="<?= e($publicUrl) ?>" target="_blank" class="btn btn-dark"><?= !empty($accessSummary['is_public']) ? 'Open Public Page' : 'Open Private Preview' ?></a>
            <button type="button" class="btn btn-outline-dark" onclick="navigator.clipboard.writeText('<?= e($publicUrl) ?>')"><?= !empty($accessSummary['is_public']) ? 'Copy Public Link' : 'Copy Preview Link' ?></button>
        </div>
    </div>

    <?php if ($success): ?><div class="alert alert-success"><?= e($success) ?></div><?php endif; ?>
    <?php if ($error): ?><div class="alert alert-danger"><?= e($error) ?></div><?php endif; ?>

    <div class="row g-4">
        <div class="col-lg-8">
            <form method="post" enctype="multipart/form-data" class="card border-0 shadow-sm rounded-4">
                <input type="hidden" name="<?= e(CSRF_TOKEN_NAME) ?>" value="<?= e(csrf_token()) ?>">
                <div class="card-body p-4">
                    <h2 class="h5">Main Page Details</h2>
                    <div class="row g-3">
                        <div class="col-md-6"><label class="form-label">Pet Name</label><input name="pet_name" class="form-control" value="<?= e($memorial['pet_name']) ?>"></div>
                        <div class="col-md-3"><label class="form-label">Birth Date</label><input type="date" name="pet_birth_date" class="form-control" value="<?= e($memorial['pet_birth_date']) ?>"></div>
                        <div class="col-md-3"><label class="form-label">Memorial Date</label><input type="date" name="pet_memorial_date" class="form-control" value="<?= e($memorial['pet_memorial_date']) ?>"></div>
                        <div class="col-12"><label class="form-label">Short Tribute</label><textarea name="short_tribute" class="form-control wysiwyg-editor" rows="7"><?= e($memorial['short_tribute']) ?></textarea></div>
                        
                        <!-- Portrait Background -->
                        <div class="col-12">
                            <label class="form-label">Portrait Background</label>
                            <input type="file" name="bg_portrait" class="form-control img-preview-input" data-preview="preview_portrait">
                            <div class="builder-preview-frame builder-preview-frame--portrait">
                                <img id="preview_portrait" src="<?= !empty($memorial['bg_image_portrait']) ? e(UPLOAD_URL . '/' . $memorial['bg_image_portrait']) : '' ?>" class="builder-preview-image" alt="Portrait background preview">
                            </div>
                        </div>
                         
                        <!-- Landscape Background -->
                        <div class="col-12">
                            <label class="form-label mt-3">Landscape Background</label>
                            <input type="file" name="bg_landscape" class="form-control img-preview-input" data-preview="preview_landscape">
                            <div class="builder-preview-frame builder-preview-frame--landscape">
                                <img id="preview_landscape" src="<?= !empty($memorial['bg_image_landscape']) ? e(UPLOAD_URL . '/' . $memorial['bg_image_landscape']) : '' ?>" class="builder-preview-image" alt="Landscape background preview">
                            </div>
                        </div>
                         
                        <!-- Cover Photo -->
                        <div class="col-12">
                            <label class="form-label mt-3">Pet Cover Photo</label>
                            <input type="file" name="cover_photo" class="form-control img-preview-input" data-preview="preview_cover">
                            <div class="builder-preview-frame builder-preview-frame--cover">
                                <img id="preview_cover" src="<?= !empty($memorial['cover_photo']) ? e(UPLOAD_URL . '/' . $memorial['cover_photo']) : '' ?>" class="builder-preview-image" alt="Cover photo preview">
                            </div>
                        </div>
                        
                        <div class="col-12"><label class="form-label">Final Letter</label><textarea name="final_letter" class="form-control wysiwyg-editor" rows="10"><?= e($memorial['final_letter']) ?></textarea></div>
                        <div class="col-md-4"><label class="form-label">Video Mode</label>
                            <select name="video_type" class="form-select">
                                <option value="none" <?= $memorial['video_type']==='none'?'selected':'' ?>>None</option>
                                <option value="file" <?= $memorial['video_type']==='file'?'selected':'' ?>>Upload File</option>
                                <option value="youtube" <?= $memorial['video_type']==='youtube'?'selected':'' ?>>YouTube Link</option>
                            </select>
                        </div>
                        <div class="col-md-4"><label class="form-label">Video Max MB</label><input type="number" name="video_max_mb" class="form-control" value="<?= e((string)$memorial['video_max_mb']) ?>"></div>
                        <div class="col-md-4"><label class="form-label">Video File</label><input type="file" name="video_file" class="form-control" accept="video/*"></div>
                        <div class="col-12"><label class="form-label">YouTube URL</label><input name="video_url" class="form-control" value="<?= e($memorial['video_url']) ?>"></div>
                        <div class="col-12"><label class="form-label">Footer Text</label><input name="share_footer_text" class="form-control" value="<?= e($memorial['share_footer_text']) ?>"></div>
                    </div>

                    <hr class="my-4">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h2 class="h5 mb-0">Timeline of Memories</h2>
                        <button type="button" class="btn btn-sm btn-outline-dark" id="addTimeline">Add Row</button>
                    </div>
                    <div id="timelineWrap">
                        <?php $timelineRows = $timelines ?: [['title'=>'','event_date'=>'','photo_path'=>'','description'=>'']]; ?>
                        <?php foreach ($timelineRows as $row): ?>
                            <div class="border rounded-4 p-3 mb-3 timeline-row">
                                <div class="row g-3">
                                    <div class="col-md-4"><input name="timeline_title[]" class="form-control" placeholder="Title" value="<?= e($row['title'] ?? '') ?>"></div>
                                    <div class="col-md-3"><input type="date" name="timeline_date[]" class="form-control" value="<?= e($row['event_date'] ?? '') ?>"></div>
                                    <div class="col-md-5">
                                        <div class="d-flex gap-2">
                                            <input type="file" name="timeline_photo[]" class="form-control timeline-photo-input" accept="image/*">
                                            <button type="button" class="btn btn-outline-danger timeline-remove-btn">Delete</button>
                                        </div>
                                        <input type="hidden" name="timeline_existing_photo[]" value="<?= e($row['photo_path'] ?? '') ?>">
                                    </div>
                                    <div class="col-12">
                                        <div class="builder-preview-frame builder-preview-frame--timeline timeline-preview-frame<?= empty($row['photo_path']) ? ' is-empty' : '' ?>">
                                            <img src="<?= !empty($row['photo_path']) ? e(UPLOAD_URL . '/' . $row['photo_path']) : '' ?>" class="builder-preview-image timeline-preview-image" alt="Timeline preview">
                                        </div>
                                    </div>
                                    <div class="col-12"><textarea name="timeline_description[]" class="form-control wysiwyg-editor" rows="6" placeholder="Description"><?= e($row['description'] ?? '') ?></textarea></div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>

                    <hr class="my-4">
                    <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
                        <h2 class="h5 mb-0">Photo Gallery</h2>
                        <div class="d-flex gap-2">
                            <button type="button" class="btn btn-sm btn-outline-danger" id="removeSelectedGallery">Delete Selected</button>
                            <button type="button" class="btn btn-sm btn-outline-secondary" id="clearGallerySelection">Clear Selection</button>
                        </div>
                    </div>
                    <div id="galleryWrap">
                        <div class="border rounded-4 p-3 mb-3">
                            <div class="row g-3">
                                <div class="col-12">
                                    <label class="form-label">Upload Gallery Photos</label>
                                    <input type="file" name="gallery_photo_batch[]" class="form-control" accept="image/*" multiple>
                                    <div class="form-text">You can select and upload multiple images at once. Caption field is hidden for now.</div>
                                </div>
                                <?php if (!empty($gallery)): ?>
                                    <div class="col-12">
                                        <div class="row g-3">
                                            <?php foreach ($gallery as $row): ?>
                                                <div class="col-6 col-md-4 col-xl-3 gallery-item">
                                                    <div class="border rounded-4 p-2 h-100">
                                                        <input type="hidden" name="gallery_existing_photo[]" value="<?= e($row['photo_path'] ?? '') ?>">
                                                        <img src="<?= e(UPLOAD_URL . '/' . $row['photo_path']) ?>" class="thumb-preview w-100 mb-2" style="height:120px">
                                                        <div class="d-flex justify-content-between align-items-center gap-2">
                                                            <div class="form-check mb-0">
                                                                <input class="form-check-input gallery-select-toggle" type="checkbox">
                                                                <label class="form-check-label small">Select</label>
                                                            </div>
                                                            <button type="button" class="btn btn-sm btn-outline-danger gallery-remove-btn">Delete</button>
                                                        </div>
                                                    </div>
                                                </div>
                                            <?php endforeach; ?>
                                        </div>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>

                    <hr class="my-4">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h2 class="h5 mb-0">Background Music Playlist</h2>
                        <button type="button" class="btn btn-outline-primary btn-sm mt-2" id="addMusic">+ Add Music</button>
                    </div>
                    <div id="musicWrap">
                        <?php if (!empty($music)): ?>
                            <?php foreach ($music as $track): ?>
                                <div class="border rounded-4 p-3 mb-3 music-row">
                                    <div class="row g-3">
                                        <div class="col-md-3">
                                            <select name="music_type[]" class="form-select music-type">
                                                <option value="youtube" <?= ($track['type'] ?? '') === 'youtube' ? 'selected' : '' ?>>YouTube</option>
                                                <option value="mp3" <?= ($track['type'] ?? '') === 'mp3' ? 'selected' : '' ?>>MP3 Upload</option>
                                            </select>
                                        </div>
                                        <div class="col-md-9 d-flex gap-2">
                                            <input name="music_title[]" class="form-control" placeholder="Track Title" value="<?= e($track['title'] ?? '') ?>">
                                            <button type="button" class="btn btn-outline-danger music-remove-btn">Delete</button>
                                        </div>
                                        <div class="col-md-12 youtube-field<?= ($track['type'] ?? '') === 'mp3' ? ' d-none' : '' ?>">
                                            <input name="music_url[]" class="form-control" placeholder="YouTube Link" value="<?= e($track['url'] ?? '') ?>">
                                        </div>
                                        <div class="col-md-12 mp3-field<?= ($track['type'] ?? '') === 'mp3' ? '' : ' d-none' ?>">
                                            <input type="file" name="music_file[]" class="form-control" accept=".mp3">
                                            <input type="hidden" name="music_existing_file[]" value="<?= e($track['file_path'] ?? '') ?>">
                                            <?php if (!empty($track['file_path'])): ?>
                                                <div class="form-text music-current-file">Current song: <?= e($track['title'] ?: pathinfo((string)basename($track['file_path']), PATHINFO_FILENAME)) ?></div>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>

                    <button class="btn btn-dark mt-2">Save Memorial Page</button>
                </div>
            </form>
        </div>
        <div class="col-lg-4">
            <div class="card border-0 shadow-sm rounded-4 mb-4">
                <div class="card-body p-4">
                    <h2 class="h5 mb-3"><?= !empty($accessSummary['is_public']) ? 'Share / QR' : 'Preview Access' ?></h2>
                    <?php if (count_pending_messages((int)$memorial['id']) > 0): ?><div class="alert alert-warning small">You have <?= (int)count_pending_messages((int)$memorial['id']) ?> pending message(s).</div><?php endif; ?>
                    <div id="qrcode" class="mb-3"></div>
                    <?php if (!empty($accessSummary['is_public'])): ?>
                        <button type="button" class="btn btn-success w-100 mb-2" id="downloadQrBtn">Download QR Code</button>
                    <?php else: ?>
                        <a href="subscription.php" class="btn btn-dark w-100 mb-2">Unlock Public Sharing</a>
                    <?php endif; ?>
                    <input class="form-control mb-2" readonly value="<?= e($publicUrl) ?>">
                    <button type="button" class="btn btn-outline-dark w-100 mb-2" onclick="navigator.clipboard.writeText('<?= e($publicUrl) ?>')"><?= !empty($accessSummary['is_public']) ? 'Copy Public Link' : 'Copy Preview Link' ?></button>
                    <a href="moderation.php<?= is_admin() ? '?clientguid=' . urlencode($client['client_guid']) : '' ?>" class="btn btn-outline-primary w-100">Moderate Messages</a>
                    <?php if (empty($accessSummary['is_public'])): ?>
                        <div class="small text-muted mt-3">This memorial stays private until subscription approval or an administrator manually enables public access.</div>
                    <?php endif; ?>
                </div>
            </div>
            <div class="card border-0 shadow-sm rounded-4">
                <div class="card-body p-4">
                    <h2 class="h5">Upload Notes</h2>
                    <ul class="small mb-0">
                        <li>Images are resized and optimized on upload.</li>
                        <li>Target image size is around 500 KB max.</li>
                        <li>Use portrait and landscape backgrounds for flexible viewing.</li>
                        <li>Video can be uploaded or embedded via YouTube.</li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</div>
<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/qrcodejs/1.0.0/qrcode.min.js"></script>
<script src="https://cdn.ckeditor.com/ckeditor5/41.4.2/classic/ckeditor.js"></script>
<script>
new QRCode(document.getElementById('qrcode'), { text: <?= json_encode($publicUrl) ?>, width: 180, height: 180 });

function initEditors(scope = document) {
    const editors = scope.querySelectorAll('.wysiwyg-editor');
    editors.forEach((el) => {
        if (el.dataset.ckeditorInitialized === '1') return;

        ClassicEditor
            .create(el, {
                toolbar: [
                    'heading', '|',
                    'bold', 'italic', 'link', '|',
                    'bulletedList', 'numberedList', '|',
                    'blockQuote', '|',
                    'undo', 'redo'
                ]
            })
            .then(editor => {
                const rowCount = parseInt(el.getAttribute('rows') || '4', 10);
                const minHeight = Math.max(140, rowCount * 28);
                editor.ui.view.editable.element.style.height = minHeight + 'px';
                editor.ui.view.editable.element.style.setProperty('min-height', minHeight + 'px', 'important');
                el.dataset.ckeditorInitialized = '1';
            })
            .catch(error => {
                console.error('CKEditor init error:', error);
            });
    });
}

initEditors();

$('#addTimeline').on('click', function(){
    const html = `
        <div class="border rounded-4 p-3 mb-3 timeline-row">
            <div class="row g-3">
                <div class="col-md-4">
                    <input name="timeline_title[]" class="form-control" placeholder="Title">
                </div>
                <div class="col-md-3">
                    <input type="date" name="timeline_date[]" class="form-control">
                </div>
        <div class="col-md-5">
            <div class="d-flex gap-2">
                <input type="file" name="timeline_photo[]" class="form-control timeline-photo-input" accept="image/*">
                <button type="button" class="btn btn-outline-danger timeline-remove-btn">Delete</button>
            </div>
            <input type="hidden" name="timeline_existing_photo[]" value="">
        </div>
        <div class="col-12">
            <div class="builder-preview-frame builder-preview-frame--timeline timeline-preview-frame is-empty">
                <img src="" class="builder-preview-image timeline-preview-image" alt="Timeline preview">
            </div>
        </div>
        <div class="col-12">
            <textarea name="timeline_description[]" class="form-control wysiwyg-editor" rows="6" placeholder="Description"></textarea>
        </div>
    </div>
</div>
    `;
    $('#timelineWrap').append(html);

    const newRow = $('#timelineWrap .timeline-row').last()[0];
    initEditors(newRow);
    bindTimelinePreview(newRow);
});

$(document).on('click', '.timeline-remove-btn', function(){
    $(this).closest('.timeline-row').remove();
});

$('#addMusic').on('click', function(){
    $('#musicWrap').append(`
        <div class="border rounded-4 p-3 mb-3 music-row">
            <div class="row g-3">
                <div class="col-md-3">
                    <select name="music_type[]" class="form-select music-type">
                        <option value="youtube">YouTube</option>
                        <option value="mp3">MP3 Upload</option>
                    </select>
                </div>
                <div class="col-md-9">
                    <div class="d-flex gap-2">
                        <input name="music_title[]" class="form-control" placeholder="Track Title">
                        <button type="button" class="btn btn-outline-danger music-remove-btn">Delete</button>
                    </div>
                </div>

                <div class="col-md-12 youtube-field">
                    <input name="music_url[]" class="form-control" placeholder="YouTube Link">
                </div>

                <div class="col-md-12 mp3-field d-none">
                    <input type="file" name="music_file[]" class="form-control" accept=".mp3">
                    <input type="hidden" name="music_existing_file[]" value="">
                    <div class="form-text music-current-file d-none"></div>
                </div>
            </div>
        </div>
    `);
});

$(document).on('change', '.music-type', function(){
    const parent = $(this).closest('.music-row');

    if($(this).val() === 'youtube'){
        parent.find('.youtube-field').removeClass('d-none');
        parent.find('.mp3-field').addClass('d-none');
    } else {
        parent.find('.youtube-field').addClass('d-none');
        parent.find('.mp3-field').removeClass('d-none');
    }
});

$(document).on('click', '.music-remove-btn', function(){
    $(this).closest('.music-row').remove();
});

$(document).on('change', 'input[name="music_file[]"]', function(){
    const row = $(this).closest('.music-row');
    const titleInput = row.find('input[name="music_title[]"]');
    const helper = row.find('.music-current-file');
    const file = this.files && this.files[0] ? this.files[0] : null;

    if (!file) {
        if (helper.length && !helper.text().trim()) {
            helper.addClass('d-none');
        }
        return;
    }

    if (titleInput.length && !titleInput.val().trim()) {
        titleInput.val(file.name.replace(/\.[^.]+$/, ''));
    }

    if (helper.length) {
        helper.text('Selected file: ' + file.name).removeClass('d-none');
    }
});

const downloadQrBtn = document.getElementById('downloadQrBtn');
if (downloadQrBtn) {
    downloadQrBtn.addEventListener('click', function () {
        const qrContainer = document.getElementById('qrcode');
        const img = qrContainer.querySelector('img');
        const canvas = qrContainer.querySelector('canvas');

        let dataUrl = '';
        if (img) {
            dataUrl = img.src;
        } else if (canvas) {
            dataUrl = canvas.toDataURL('image/png');
        }

        if (dataUrl) {
            const a = document.createElement('a');
            a.href = dataUrl;
            a.download = <?= json_encode($qrDownloadName) ?>;
            document.body.appendChild(a);
            a.click();
            a.remove();
        }
    });
}

function removeGalleryItems(items) {
    items.forEach(function(item) {
        if (item) {
            item.remove();
        }
    });
}

document.addEventListener('click', function(event) {
    const removeBtn = event.target.closest('.gallery-remove-btn');
    if (removeBtn) {
        const item = removeBtn.closest('.gallery-item');
        removeGalleryItems(item ? [item] : []);
    }
});

const removeSelectedGalleryBtn = document.getElementById('removeSelectedGallery');
if (removeSelectedGalleryBtn) {
    removeSelectedGalleryBtn.addEventListener('click', function() {
        const selectedItems = Array.from(document.querySelectorAll('.gallery-select-toggle:checked'))
            .map((checkbox) => checkbox.closest('.gallery-item'))
            .filter(Boolean);
        removeGalleryItems(selectedItems);
    });
}

const clearGallerySelectionBtn = document.getElementById('clearGallerySelection');
if (clearGallerySelectionBtn) {
    clearGallerySelectionBtn.addEventListener('click', function() {
        document.querySelectorAll('.gallery-select-toggle').forEach(function(checkbox) {
            checkbox.checked = false;
        });
    });
}



document.querySelectorAll('.img-preview-input').forEach(input => {
    input.addEventListener('change', function(e){
        const file = e.target.files[0];
        const previewId = e.target.dataset.preview;

        if(file){
            const reader = new FileReader();
            reader.onload = function(ev){
                document.getElementById(previewId).src = ev.target.result;
            }
            reader.readAsDataURL(file);
        }
    });
});

function bindTimelinePreview(scope = document) {
    scope.querySelectorAll('.timeline-photo-input').forEach((input) => {
        if (input.dataset.previewBound === '1') return;
        input.dataset.previewBound = '1';

        input.addEventListener('change', function(e) {
            const row = e.target.closest('.timeline-row');
            if (!row) return;

            const frame = row.querySelector('.timeline-preview-frame');
            const preview = row.querySelector('.timeline-preview-image');
            const file = e.target.files && e.target.files[0] ? e.target.files[0] : null;

            if (!frame || !preview) return;

            if (!file) {
                const hiddenInput = row.querySelector('input[name=\"timeline_existing_photo[]\"]');
                if (hiddenInput && hiddenInput.value) {
                    preview.src = <?= json_encode(rtrim(UPLOAD_URL, '/') . '/') ?> + hiddenInput.value.replace(/^\/+/, '');
                    frame.classList.remove('is-empty');
                } else {
                    preview.src = '';
                    frame.classList.add('is-empty');
                }
                return;
            }

            const reader = new FileReader();
            reader.onload = function(ev) {
                preview.src = ev.target.result;
                frame.classList.remove('is-empty');
            };
            reader.readAsDataURL(file);
        });
    });
}

bindTimelinePreview();



</script>
</body>
</html>
