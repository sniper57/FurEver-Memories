<?php if (($memorial['video_type'] ?? 'none') !== 'none'): ?>
<section class="py-5 bg-white">
    <div class="container">
        <div class="text-center mb-4">
            <h2 class="fw-bold">Video Tribute</h2>
        </div>
        <div class="ratio ratio-16x9 rounded-4 overflow-hidden shadow-sm bg-dark">
            <?php if ($memorial['video_type'] === 'youtube' && !empty($memorial['youtube_embed_url'])): ?>
                <iframe src="<?= e($memorial['youtube_embed_url']) ?>" title="Video tribute" allowfullscreen></iframe>
            <?php elseif ($memorial['video_type'] === 'file' && !empty($memorial['video_file'])): ?>
                <video controls playsinline>
                    <source src="<?= e(UPLOAD_URL . '/' . $memorial['video_file']) ?>">
                </video>
            <?php endif; ?>
        </div>
    </div>
</section>
<?php endif; ?>
