<section class="py-5 bg-light" id="messages">
    <div class="container">
        <div class="row g-4">
            <div class="col-lg-5">
                <div class="card border-0 shadow-sm rounded-4">
                    <div class="card-body p-4">
                        <h2 class="h4 mb-3">Message Wall</h2>
                        <p class="text-muted small">Messages are subject to approval by the family.</p>
                        <form method="post" enctype="multipart/form-data">
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
                <div class="row g-3">
                    <?php if (empty($messages)): ?>
                        <div class="col-12"><div class="alert alert-secondary mb-0">No approved messages yet.</div></div>
                    <?php endif; ?>
                    <?php foreach ($messages as $message): ?>
                        <div class="col-md-6">
                            <div class="card border-0 shadow-sm rounded-4 h-100">
                                <div class="card-body p-3">
                                    <div class="d-flex align-items-center gap-3 mb-3">
                                        <?php if (!empty($message['visitor_photo'])): ?>
                                            <img src="<?= e(UPLOAD_URL . '/' . $message['visitor_photo']) ?>" class="visitor-photo" alt="<?= e($message['visitor_name']) ?>">
                                        <?php endif; ?>
                                        <div>
                                            <div class="fw-semibold"><?= e($message['visitor_name']) ?></div>
                                            <div class="small text-muted"><?= e($message['created_at']) ?></div>
                                        </div>
                                    </div>
                                    <p class="mb-0 text-secondary"><?= nl2br(e($message['message'])) ?></p>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </div>
</section>
