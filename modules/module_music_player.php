<?php if (!empty($music)): ?>
<div class="music-player-wrap">
    <div class="container py-3">
        <div class="card border-0 shadow-sm rounded-4">
            <div class="card-body d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3">
                <div>
                    <div class="fw-semibold">Background Music</div>
                    <div class="small text-muted" id="musicNowPlaying">Tap Play to start the memorial music.</div>
                </div>
                <div class="d-flex gap-2 align-items-center">
                    <button class="btn btn-dark btn-sm" id="musicPlayBtn" type="button">Play / Pause</button>
                    <button class="btn btn-outline-dark btn-sm" id="musicNextBtn" type="button">Next</button>
                </div>
            </div>
        </div>
    </div>
    <audio id="bgMusicPlayer" preload="none"></audio>
    <script>
        window.FM_PLAYLIST = <?= json_encode(array_values(array_map(function($row){ return ['title' => $row['title'], 'url' => $row['music_url']]; }, $music))) ?>;
    </script>
</div>
<?php endif; ?>
