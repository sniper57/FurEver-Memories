<section class="py-5 bg-white" id="tribute-actions">
    <div class="container">
        <div class="text-center mb-4">
            <h2 class="fw-bold">Light a Candle &amp; Send Hearts</h2>
            <p class="text-muted">A simple tribute from family and friends.</p>
        </div>
        <div class="row g-4 justify-content-center">
            <div class="col-lg-4">
                <div class="card border-0 shadow-sm rounded-4 text-center h-100">
                    <div class="card-body p-4">
                        <div class="display-4 mb-2">&#128367;</div>
                        <div class="h2 mb-1"><?= e((string)$candleCount) ?></div>
                        <div class="text-muted mb-3">Candles Lit</div>
                        <form method="post" action="<?= e($publicUrl) ?>">
                            <?= csrf_input() ?>
                            <input type="hidden" name="action" value="candle">
                            <input class="form-control mb-2" name="visitor_name" placeholder="Your name" required>
                            <button class="btn btn-dark w-100">Light a Candle</button>
                        </form>
                        <?php if ($candleNames): ?>
                            <div class="d-flex flex-wrap justify-content-center gap-2 mt-3">
                                <?php foreach ($candleNames as $reactor): ?>
                                    <span class="badge rounded-pill text-bg-light memorial-reaction-badge"><?= e($reactor['visitor_name']) ?></span>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            <div class="col-lg-4">
                <div class="card border-0 shadow-sm rounded-4 text-center h-100">
                    <div class="card-body p-4">
                        <div class="display-4 mb-2">&#10084;</div>
                        <div class="h2 mb-1"><?= e((string)$heartCount) ?></div>
                        <div class="text-muted mb-3">Hearts Sent</div>
                        <form method="post" action="<?= e($publicUrl) ?>">
                            <?= csrf_input() ?>
                            <input type="hidden" name="action" value="heart">
                            <input class="form-control mb-2" name="visitor_name" placeholder="Your name" required>
                            <button class="btn btn-dark w-100">Send a Heart</button>
                        </form>
                        <?php if ($heartNames): ?>
                            <div class="d-flex flex-wrap justify-content-center gap-2 mt-3">
                                <?php foreach ($heartNames as $reactor): ?>
                                    <span class="badge rounded-pill text-bg-light memorial-reaction-badge"><?= e($reactor['visitor_name']) ?></span>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>
