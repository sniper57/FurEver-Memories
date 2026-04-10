(function () {
  const list = window.FM_PLAYLIST || [];
  const player = document.getElementById('bgMusicPlayer');
  const shell = document.getElementById('musicPlayerShell');
  const wrap = document.getElementById('musicPlayerWrap');
  const showBtn = document.getElementById('musicShowPlayerBtn');
  const titleEl = document.getElementById('musicPlayerTitle');
  const subtitleEl = document.getElementById('musicNowPlaying');
  const playBtn = document.getElementById('musicPlayBtn');
  const nextBtn = document.getElementById('musicNextBtn');
  const minimizeBtn = document.getElementById('musicMinimizeBtn');
  const progressFill = document.getElementById('musicProgressFill');
  const progressCurrent = document.getElementById('musicProgressCurrent');
  const progressTotal = document.getElementById('musicProgressTotal');
  const consentOverlay = document.getElementById('musicConsentOverlay');
  const consentMuteBtn = document.getElementById('musicConsentMuteBtn');
  const consentPlayBtn = document.getElementById('musicConsentPlayBtn');
  const hiddenClass = 'is-minimized';
  const playingClass = 'is-playing';
  const toggleVisibleClass = 'is-visible';
  let idx = 0;

  if (player && shell && wrap && showBtn && list.length) {
    const stateKey = 'fm-player-minimized';
    const muteUntilKey = 'fm-player-muted-until';

    function formatTime(seconds) {
      if (!Number.isFinite(seconds) || seconds < 0) return '00:00';
      const mins = Math.floor(seconds / 60);
      const secs = Math.floor(seconds % 60);
      return String(mins).padStart(2, '0') + ':' + String(secs).padStart(2, '0');
    }

    function syncPlayerState() {
      const isPlaying = !player.paused && !player.ended;
      shell.classList.toggle(playingClass, isPlaying);
      if (playBtn) {
        playBtn.innerHTML = isPlaying ? '&#10073;&#10073;' : '&#9658;';
      }
    }

    function updateProgress() {
      const duration = Number.isFinite(player.duration) ? player.duration : 0;
      const current = Number.isFinite(player.currentTime) ? player.currentTime : 0;
      const percent = duration > 0 ? Math.min(100, (current / duration) * 100) : 0;

      if (progressFill) {
        progressFill.style.width = percent + '%';
      }
      if (progressCurrent) {
        progressCurrent.textContent = formatTime(current);
      }
      if (progressTotal) {
        progressTotal.textContent = formatTime(duration);
      }
    }

    function setTrack(index) {
      idx = (index + list.length) % list.length;
      const track = list[idx];
      player.src = track.url;

      if (titleEl) {
        titleEl.textContent = track.title || 'Memorial Track';
      }
      if (subtitleEl) {
        subtitleEl.textContent = 'Track ' + (idx + 1) + ' of ' + list.length;
      }

      player.load();
      updateProgress();
      syncPlayerState();
    }

    function playCurrent() {
      if (!player.src) {
        setTrack(idx);
      }
      player.play().catch(() => {});
      hideConsentPrompt();
      showPlayer();
    }

    function nextTrack() {
      setTrack(idx + 1);
      playCurrent();
    }

    function minimizePlayer() {
      shell.classList.add(hiddenClass);
      showBtn.classList.add(toggleVisibleClass);
      localStorage.setItem(stateKey, '1');
    }

    function showPlayer() {
      shell.classList.remove(hiddenClass);
      showBtn.classList.remove(toggleVisibleClass);
      localStorage.setItem(stateKey, '0');
    }

    function hideConsentPrompt() {
      if (consentOverlay) {
        consentOverlay.hidden = true;
      }
    }

    function showConsentPrompt() {
      if (consentOverlay) {
        consentOverlay.hidden = false;
      }
    }

    function isMuteWindowActive() {
      const mutedUntil = parseInt(localStorage.getItem(muteUntilKey) || '0', 10);
      return Number.isFinite(mutedUntil) && mutedUntil > Date.now();
    }

    if (localStorage.getItem(stateKey) === '1') {
      minimizePlayer();
    }

    setTrack(0);

    if (!isMuteWindowActive()) {
      window.setTimeout(showConsentPrompt, 350);
    }

    if (playBtn) {
      playBtn.addEventListener('click', function () {
        if (!player.src) {
          setTrack(idx);
        }
        if (player.paused) {
          playCurrent();
        } else {
          player.pause();
        }
      });
    }

    if (nextBtn) {
      nextBtn.addEventListener('click', nextTrack);
    }

    if (minimizeBtn) {
      minimizeBtn.addEventListener('click', minimizePlayer);
    }

    if (consentMuteBtn) {
      consentMuteBtn.addEventListener('click', function () {
        localStorage.setItem(muteUntilKey, String(Date.now() + (60 * 60 * 1000)));
        hideConsentPrompt();
      });
    }

    if (consentPlayBtn) {
      consentPlayBtn.addEventListener('click', function () {
        localStorage.removeItem(muteUntilKey);
        playCurrent();
      });
    }

    showBtn.addEventListener('click', showPlayer);
    player.addEventListener('ended', nextTrack);
    player.addEventListener('timeupdate', updateProgress);
    player.addEventListener('loadedmetadata', updateProgress);
    player.addEventListener('play', syncPlayerState);
    player.addEventListener('pause', syncPlayerState);
  }
})();

(function () {
  const revealSelectors = [
    'main > section',
    '.timeline-card',
    '.gallery-masonry-item',
    '#messages .card',
    '#tribute-actions .card',
    '.music-player-wrap'
  ];
  const targets = Array.from(document.querySelectorAll(revealSelectors.join(',')));

  targets.forEach(function (element, index) {
    element.classList.add('scroll-reveal');
    element.style.setProperty('--reveal-delay', (index % 6) * 70 + 'ms');
  });

  if (!targets.length) {
    return;
  }

  if (!('IntersectionObserver' in window)) {
    targets.forEach(function (element) {
      element.classList.add('is-visible');
    });
    return;
  }

  const observer = new IntersectionObserver(function (entries) {
    entries.forEach(function (entry) {
      if (entry.isIntersecting) {
        entry.target.classList.add('is-visible');
        observer.unobserve(entry.target);
      }
    });
  }, {
    threshold: 0.16,
    rootMargin: '0px 0px -40px 0px'
  });

  targets.forEach(function (element) {
    observer.observe(element);
  });
})();

(function () {
  const wrap = document.getElementById('publicSuggestionWrap');
  if (!wrap) {
    return;
  }

  let longPressTimer = null;
  let hideTimer = null;
  const visibleClass = 'is-tooltip-visible';

  function showTooltipTemporarily() {
    wrap.classList.add(visibleClass);
    window.clearTimeout(hideTimer);
    hideTimer = window.setTimeout(function () {
      wrap.classList.remove(visibleClass);
    }, 2200);
  }

  wrap.addEventListener('touchstart', function () {
    window.clearTimeout(longPressTimer);
    longPressTimer = window.setTimeout(showTooltipTemporarily, 500);
  }, { passive: true });

  ['touchend', 'touchcancel', 'touchmove'].forEach(function (eventName) {
    wrap.addEventListener(eventName, function () {
      window.clearTimeout(longPressTimer);
    }, { passive: true });
  });
})();

(function () {
  function addRow(btnId, wrapId, html) {
    const btn = document.getElementById(btnId);
    const wrap = document.getElementById(wrapId);
    if (btn && wrap) {
      btn.addEventListener('click', function () {
        wrap.insertAdjacentHTML('beforeend', html);
      });
    }
  }

  addRow('addTimeline', 'timelineWrap', window.TIMELINE_TEMPLATE || '');
  addRow('addGallery', 'galleryWrap', window.GALLERY_TEMPLATE || '');
  addRow('addMusic', 'musicWrap', window.MUSIC_TEMPLATE || '');
})();
