<section class="py-4 footer-section text-center text-white">
    <div class="container">
        <p class="mb-2"><?= e($memorial['share_footer_text'] ?: 'Created with love through FurEver Memories') ?></p>
        <div class="small opacity-75 mb-3">Share this memorial page with family and friends.</div>
        <div class="d-flex flex-column flex-md-row justify-content-center gap-2 align-items-center">
            <input type="text" class="form-control form-control-sm" style="max-width:420px" value="<?= e($publicUrl) ?>" readonly>
            <button class="btn btn-light btn-sm" type="button" onclick="navigator.clipboard.writeText('<?= e($publicUrl) ?>')">Copy Link</button>
        </div>
    </div>
</section>
