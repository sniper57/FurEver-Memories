<section class="py-5 bg-light" id="messages">
    <div class="container">
        <div class="row g-4">
            <div class="col-lg-5">
                <div class="card border-0 shadow-sm rounded-4">
                    <div class="card-body p-4">
                        <h2 class="h4 mb-3">Message Wall</h2>
                        <p class="text-muted small">Messages are subject to approval by the family.</p>
                        <form method="post" enctype="multipart/form-data" action="<?= e($publicUrl) ?>">
                            <?= csrf_input() ?>
                            <input type="hidden" name="action" value="message">
                            <div class="mb-3"><label class="form-label">Your Name</label><input name="visitor_name" class="form-control" required></div>
                            <div class="mb-3"><label class="form-label">Your Photo</label><input type="file" name="visitor_photo" class="form-control" accept="image/*"></div>
                            <div class="mb-3"><label class="form-label">Message</label><textarea name="message" class="form-control" rows="4" required></textarea></div>
                            <button class="btn btn-dark w-100">Share Memory</button>
                        </form>
                    </div>
                </div>
            </div>
            <div class="col-lg-7">
                <?php if (empty($messages)): ?>
                    <div class="alert alert-secondary mb-0">No approved messages yet.</div>
                <?php else: ?>
                    <div id="messageWallCarousel" class="carousel slide message-wall-carousel" data-bs-ride="carousel">
                        <?php if (count($messages) > 1): ?>
                            <div class="carousel-indicators message-wall-indicators">
                                <?php foreach ($messages as $index => $message): ?>
                                    <button type="button" data-bs-target="#messageWallCarousel" data-bs-slide-to="<?= (int)$index ?>" class="<?= $index === 0 ? 'active' : '' ?>" aria-current="<?= $index === 0 ? 'true' : 'false' ?>" aria-label="Message <?= (int)$index + 1 ?>"></button>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                        <div class="carousel-inner">
                            <?php foreach ($messages as $index => $message): ?>
                                <div class="carousel-item <?= $index === 0 ? 'active' : '' ?>">
                                    <div class="message-wall-slide">
                                        <div class="message-wall-card">
                                            <div class="d-flex align-items-center gap-3 mb-4">
                                                <?php if (!empty($message['visitor_photo'])): ?>
                                                    <img src="<?= e(UPLOAD_URL . '/' . $message['visitor_photo']) ?>" class="visitor-photo message-wall-photo" alt="<?= e($message['visitor_name']) ?>">
                                                <?php else: ?>
                                                    <div class="message-wall-avatar" aria-hidden="true"><?= e(mb_strtoupper(mb_substr((string)$message['visitor_name'], 0, 1))) ?></div>
                                                <?php endif; ?>
                                                <div>
                                                    <div class="message-wall-name"><?= e($message['visitor_name']) ?></div>
                                                    <div class="small text-muted"><?= e(format_display_date($message['created_at'])) ?></div>
                                                </div>
                                            </div>
                                            <p class="message-wall-text mb-0"><?= nl2br(e($message['message'])) ?></p>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                        <?php if (count($messages) > 1): ?>
                            <button class="carousel-control-prev message-wall-control" type="button" data-bs-target="#messageWallCarousel" data-bs-slide="prev">
                                <span class="carousel-control-prev-icon" aria-hidden="true"></span>
                                <span class="visually-hidden">Previous</span>
                            </button>
                            <button class="carousel-control-next message-wall-control" type="button" data-bs-target="#messageWallCarousel" data-bs-slide="next">
                                <span class="carousel-control-next-icon" aria-hidden="true"></span>
                                <span class="visually-hidden">Next</span>
                            </button>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</section>
