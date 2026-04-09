<section class="hero-section text-white d-flex align-items-center">
    <div class="hero-overlay"></div>
    <div class="container position-relative py-5">
        <div class="row align-items-center g-4">
            <div class="col-lg-7 text-center text-lg-start">
                <span class="badge rounded-pill text-bg-light text-dark mb-3">FurEver Memories</span>
                <h1 class="display-4 fw-bold mb-3"><?= e($memorial['pet_name'] ?: 'Beloved Pet') ?></h1>
                <p class="lead mb-2"><?= nl2br(e($memorial['short_tribute'])) ?></p>
                <p class="opacity-75 mb-4">
                    <?= e($memorial['pet_birth_date'] ?: '') ?>
                    <?= !empty($memorial['pet_birth_date']) || !empty($memorial['pet_memorial_date']) ? ' — ' : '' ?>
                    <?= e($memorial['pet_memorial_date'] ?: '') ?>
                </p>
                <div class="d-flex flex-wrap gap-2 justify-content-center justify-content-lg-start">
                    <a href="#tribute-actions" class="btn btn-light btn-lg">Light a Candle</a>
                    <a href="#messages" class="btn btn-outline-light btn-lg">Leave a Memory</a>
                </div>
            </div>
            <div class="col-lg-5 text-center">
                <?php if (!empty($memorial['cover_photo'])): ?>
                    <img src="<?= e(UPLOAD_URL . '/' . $memorial['cover_photo']) ?>" class="cover-pet-photo shadow-lg" alt="Pet photo cover">
                <?php endif; ?>
            </div>
        </div>
    </div>
</section>
