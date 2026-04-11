(function () {
  function showToast(type, message, title) {
    if (!message || typeof toastr === 'undefined') {
      return;
    }

    const method = toastr[type] ? type : 'info';
    toastr[method](message, title || '');
  }

  if (typeof toastr !== 'undefined') {
    toastr.options = {
      closeButton: true,
      progressBar: true,
      newestOnTop: true,
      positionClass: 'toast-top-right',
      timeOut: 4500,
      extendedTimeOut: 1800
    };
  }

  document.addEventListener('DOMContentLoaded', function () {
    const toasts = Array.isArray(window.FM_TOASTS) ? window.FM_TOASTS : [];
    toasts.forEach(function (toast) {
      showToast(toast.type || 'info', toast.message || '', toast.title || '');
    });

    document.addEventListener('submit', function (event) {
      const form = event.target.closest('form[data-swal-confirm]');
      if (!form || form.dataset.swalConfirmed === '1') {
        return;
      }

      event.preventDefault();
      const message = form.getAttribute('data-swal-confirm') || 'Continue with this action?';
      const title = form.getAttribute('data-swal-title') || 'Please confirm';
      const confirmText = form.getAttribute('data-swal-confirm-text') || 'Yes, continue';
      const cancelText = form.getAttribute('data-swal-cancel-text') || 'Cancel';
      const icon = form.getAttribute('data-swal-icon') || 'warning';

      if (typeof Swal === 'undefined') {
        if (window.confirm(message)) {
          form.dataset.swalConfirmed = '1';
          form.submit();
        }
        return;
      }

      Swal.fire({
        title: title,
        text: message,
        icon: icon,
        showCancelButton: true,
        confirmButtonText: confirmText,
        cancelButtonText: cancelText,
        confirmButtonColor: '#9f6a43',
        cancelButtonColor: '#6c757d',
        reverseButtons: true
      }).then(function (result) {
        if (result.isConfirmed) {
          form.dataset.swalConfirmed = '1';
          form.submit();
        }
      });
    });

    initDashboardActivityPolling();
  });

  function initDashboardActivityPolling() {
    const config = window.FM_DASHBOARD_ACTIVITY_CONFIG || null;
    if (!config || !config.endpoint) {
      return;
    }

    let lastCounts = Object.assign({
      candles: 0,
      hearts: 0,
      messages: 0
    }, config.counts || {});

    function updateText(selector, value) {
      if (!selector) {
        return;
      }
      document.querySelectorAll(selector).forEach(function (element) {
        element.textContent = String(value);
      });
    }

    function pluralize(count, singular, plural) {
      return count === 1 ? singular : plural;
    }

    function notifyIncreases(nextCounts) {
      const candleDiff = Math.max(0, nextCounts.candles - lastCounts.candles);
      const heartDiff = Math.max(0, nextCounts.hearts - lastCounts.hearts);
      const messageDiff = Math.max(0, nextCounts.messages - lastCounts.messages);

      if (candleDiff > 0) {
        showToast('info', candleDiff + ' new ' + pluralize(candleDiff, 'candle was lit.', 'candles were lit.'), 'Memorial activity');
      }
      if (heartDiff > 0) {
        showToast('success', heartDiff + ' new ' + pluralize(heartDiff, 'heart was sent.', 'hearts were sent.'), 'Memorial activity');
      }
      if (messageDiff > 0) {
        showToast('warning', messageDiff + ' new ' + pluralize(messageDiff, 'message is waiting on the wall.', 'messages are waiting on the wall.'), 'Message wall');
      }
    }

    function syncCounts(nextCounts) {
      updateText(config.selectors && config.selectors.candles, nextCounts.candles);
      updateText(config.selectors && config.selectors.hearts, nextCounts.hearts);
      updateText(config.selectors && config.selectors.messages, nextCounts.messages);
      updateText(config.selectors && config.selectors.views, nextCounts.views);

      if (window.FM_ENGAGEMENT_CHART && window.FM_ENGAGEMENT_CHART.data && window.FM_ENGAGEMENT_CHART.data.datasets[0]) {
        window.FM_ENGAGEMENT_CHART.data.datasets[0].data = [
          nextCounts.candles,
          nextCounts.hearts,
          nextCounts.messages
        ];
        window.FM_ENGAGEMENT_CHART.update();
      }
    }

    function poll() {
      fetch(config.endpoint, {
        method: 'GET',
        credentials: 'same-origin',
        headers: {
          Accept: 'application/json'
        },
        cache: 'no-store'
      })
        .then(function (response) {
          if (!response.ok) {
            throw new Error('Unable to load dashboard activity.');
          }
          return response.json();
        })
        .then(function (payload) {
          if (!payload || payload.status !== 'ok' || !payload.counts) {
            return;
          }

          const nextCounts = Object.assign({}, lastCounts, payload.counts);
          notifyIncreases(nextCounts);
          syncCounts(nextCounts);
          lastCounts = nextCounts;
        })
        .catch(function () {});
    }

    window.setInterval(poll, Math.max(5000, parseInt(config.intervalMs || '10000', 10)));
  }
})();
