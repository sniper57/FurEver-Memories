<?php if (!empty($music)): ?>
<?php
$musicThumb = !empty($memorial['cover_photo'])
    ? rtrim(UPLOAD_URL, '/') . '/' . ltrim((string)$memorial['cover_photo'], '/')
    : '';
?>
<div class="music-player-wrap" id="musicPlayerWrap">
    <div class="music-player-shell" id="musicPlayerShell">
        <div class="music-player-content">
            <div class="music-player-thumb">
                <?php if ($musicThumb !== ''): ?>
                    <img src="<?= e($musicThumb) ?>" alt="Memorial music cover">
                <?php else: ?>
                    <span class="music-player-note">&#9835;</span>
                <?php endif; ?>
            </div>
            <div class="music-player-body">
                <div class="music-player-topbar">
                    <div class="music-player-copy">
                        <span class="music-player-kicker">Background Music</span>
                        <h3 class="music-player-title" id="musicPlayerTitle">Memorial Playlist</h3>
                        <p class="music-player-subtitle" id="musicNowPlaying">Tap play to start the memorial music.</p>
                    </div>
                    <div class="music-player-window-actions">
                        <button class="music-player-icon-btn" id="musicMinimizeBtn" type="button" aria-label="Minimize player">&#8211;</button>
                    </div>
                </div>
                <div class="music-player-controls">
                    <button class="music-player-main-btn" id="musicPlayBtn" type="button" aria-label="Play or pause music">&#9658;</button>
                    <button class="music-player-secondary-btn" id="musicNextBtn" type="button">
                        <span>&#9834;</span>
                        <span>Next</span>
                    </button>
                </div>
                <div class="music-player-progress">
                    <div class="music-player-progress-bar" aria-hidden="true">
                        <div class="music-player-progress-fill" id="musicProgressFill"></div>
                    </div>
                    <div class="music-player-meta">
                        <span id="musicProgressCurrent">00:00</span>
                        <span id="musicProgressTotal">00:00</span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<button class="music-player-toggle" id="musicShowPlayerBtn" type="button" aria-label="Show player">
    <span class="music-player-toggle-icon">&#9835;</span>
    <span>Show Player</span>
</button>
<div class="music-consent-overlay" id="musicConsentOverlay" hidden>
    <div class="music-consent-backdrop"></div>
    <div class="music-consent-dialog" role="dialog" aria-modal="true" aria-labelledby="musicConsentTitle">
        <h2 class="music-consent-title" id="musicConsentTitle">Allow background music?</h2>
        <p class="music-consent-copy">Your permission is needed to play background music on this website. Click below to allow playback.</p>
        <div class="music-consent-actions">
            <button class="music-consent-btn music-consent-btn--muted" id="musicConsentMuteBtn" type="button">Mute</button>
            <button class="music-consent-btn music-consent-btn--play" id="musicConsentPlayBtn" type="button">Play music</button>
        </div>
    </div>
</div>
<audio id="bgMusicPlayer" preload="none"></audio>
<script>
    window.FM_PLAYLIST = <?= json_encode(array_values(array_filter(array_map(function($row){
        if (empty($row['music_url'])) {
            return null;
        }

        return [
            'title' => $row['title'] ?: 'Memorial Track',
            'url' => $row['music_url']
        ];
    }, $music)))) ?>;
</script>
<?php endif; ?>
