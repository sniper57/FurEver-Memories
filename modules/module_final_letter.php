<?php if (!empty($memorial['final_letter'])): ?>
<section class="py-5 bg-light">
    <div class="container">
        <div class="card border-0 shadow-sm rounded-4 mx-auto" style="max-width:900px;">
            <div class="card-body p-4 p-md-5 text-center">
                <h2 class="fw-bold mb-4">Final Letter</h2>
                <div class="fs-5 text-secondary memorial-letter"><?= render_rich_text($memorial['final_letter']) ?></div>
            </div>
        </div>
    </div>
</section>
<?php endif; ?>
