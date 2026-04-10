<?php if (!empty($timelines)): ?>
<section class="py-5 bg-white">
    <div class="container">
        <div class="text-center mb-5">
            <h2 class="fw-bold">Story / Timeline of Memories</h2>
            <p class="text-muted">A life remembered through beautiful moments.</p>
        </div>
        <div class="timeline-wrap">
            <?php foreach ($timelines as $item): ?>
                <div class="timeline-card row g-4 align-items-center mb-4">
                    <div class="col-md-4">
                        <?php if (!empty($item['photo_path'])): ?>
                            <img src="<?= e(UPLOAD_URL . '/' . $item['photo_path']) ?>" class="img-fluid rounded-4 shadow-sm timeline-photo" alt="<?= e($item['title']) ?>">
                        <?php endif; ?>
                    </div>
                    <div class="col-md-8">
                        <div class="small text-uppercase text-muted mb-2"><?= e(format_display_date($item['event_date'])) ?></div>
                        <h3 class="h4"><?= e($item['title']) ?></h3>
                        <div class="mb-0 text-secondary"><?= render_rich_text($item['description']) ?></div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>
<?php endif; ?>
