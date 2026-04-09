<?php if (!empty($gallery)): ?>
<section class="py-5 gallery-section">
    <div class="container">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h2 class="fw-bold mb-1">Photo Gallery</h2>
                <p class="text-muted mb-0">Tap any photo to zoom and swipe.</p>
            </div>
        </div>

        <div id="galleryCarousel" class="carousel slide memorial-carousel" data-bs-ride="carousel">
            <div class="carousel-inner rounded-4 overflow-hidden shadow-sm">
                <?php foreach ($gallery as $index => $photo): ?>
                    <?php
                        $fullPath = __DIR__ . '/../uploads/' . $photo['photo_path'];
                        $imgW = 1600;
                        $imgH = 1200;
                        if (is_file($fullPath)) {
                            $imgInfo = @getimagesize($fullPath);
                            if ($imgInfo) {
                                $imgW = (int)$imgInfo[0];
                                $imgH = (int)$imgInfo[1];
                            }
                        }
                        $imgUrl = UPLOAD_URL . '/' . $photo['photo_path'];
                    ?>
                    <div class="carousel-item <?= $index === 0 ? 'active' : '' ?>">
                        <div class="gallery-main-frame">
                            <img src="<?= e($imgUrl) ?>"
                                 class="gallery-main-image"
                                 alt="<?= e($photo['caption'] ?: 'Gallery photo') ?>">
                        </div>
                        <?php if (!empty($photo['caption'])): ?>
                            <div class="carousel-caption d-none d-md-block"><p><?= e($photo['caption']) ?></p></div>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            </div>

            <?php if (count($gallery) > 1): ?>
                <button class="carousel-control-prev" type="button" data-bs-target="#galleryCarousel" data-bs-slide="prev">
                    <span class="carousel-control-prev-icon"></span>
                </button>
                <button class="carousel-control-next" type="button" data-bs-target="#galleryCarousel" data-bs-slide="next">
                    <span class="carousel-control-next-icon"></span>
                </button>
            <?php endif; ?>
        </div>

        <div id="gallery-photoswipe" class="gallery-masonry mt-4">
            <?php foreach ($gallery as $photo): ?>
                <?php
                    $fullPath = __DIR__ . '/../uploads/' . $photo['photo_path'];
                    $imgW = 1600;
                    $imgH = 1200;
                    if (is_file($fullPath)) {
                        $imgInfo = @getimagesize($fullPath);
                        if ($imgInfo) {
                            $imgW = (int)$imgInfo[0];
                            $imgH = (int)$imgInfo[1];
                        }
                    }
                    $imgUrl = UPLOAD_URL . '/' . $photo['photo_path'];
                ?>
                <a href="<?= e($imgUrl) ?>"
                   data-pswp-width="<?= e((string)$imgW) ?>"
                   data-pswp-height="<?= e((string)$imgH) ?>"
                   target="_blank"
                   class="gallery-masonry-item">
                    <img src="<?= e($imgUrl) ?>"
                         class="gallery-thumb"
                         alt="<?= e($photo['caption'] ?: 'Gallery photo') ?>">
                </a>
            <?php endforeach; ?>
        </div>
    </div>
</section>
<?php endif; ?>
