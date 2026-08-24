/* ==========================================================================
   GameCraft Studio - interface behaviour
   Plain JavaScript, no external libraries.
   ========================================================================== */

(function () {
  'use strict';

  /* ----------------------------------------------------------------------
     Quick action menus (FR-14)
     ---------------------------------------------------------------------- */

  function closeAllMenus(except) {
    document.querySelectorAll('[data-menu].is-open').forEach(function (m) {
      if (m !== except) {
        m.classList.remove('is-open');
        var t = m.querySelector('[data-menu-trigger]');
        if (t) t.setAttribute('aria-expanded', 'false');
      }
    });
  }

  document.addEventListener('click', function (e) {
    var trigger = e.target.closest('[data-menu-trigger]');

    if (trigger) {
      e.preventDefault();
      e.stopPropagation();
      var menu = trigger.closest('[data-menu]');
      var willOpen = !menu.classList.contains('is-open');
      closeAllMenus(menu);
      menu.classList.toggle('is-open', willOpen);
      trigger.setAttribute('aria-expanded', willOpen ? 'true' : 'false');
      return;
    }

    if (!e.target.closest('.menu__list')) {
      closeAllMenus(null);
    }
  });

  document.addEventListener('keydown', function (e) {
    if (e.key === 'Escape') closeAllMenus(null);
  });

  /* ----------------------------------------------------------------------
     Auto-dismissing notifications
     ---------------------------------------------------------------------- */

  document.addEventListener('click', function (e) {
    var close = e.target.closest('[data-flash-close]');
    if (close) close.closest('.flash').remove();
  });

  document.querySelectorAll('.flash').forEach(function (el, i) {
    setTimeout(function () {
      el.style.transition = 'opacity .3s, transform .3s';
      el.style.opacity = '0';
      el.style.transform = 'translateX(16px)';
      setTimeout(function () { el.remove(); }, 320);
    }, 5000 + i * 400);
  });

  /* ----------------------------------------------------------------------
     Copy the prompt (FR-30)
     ---------------------------------------------------------------------- */

  document.addEventListener('click', function (e) {
    var btn = e.target.closest('[data-copy]');
    if (!btn) return;

    var selector = btn.getAttribute('data-copy');
    var source = document.querySelector(selector);
    if (!source) return;

    var text = source.value !== undefined ? source.value : source.textContent;
    var original = btn.innerHTML;

    function done(ok) {
      btn.innerHTML = ok ? 'Copied!' : 'Could not copy';
      setTimeout(function () { btn.innerHTML = original; }, 1800);
    }

    if (navigator.clipboard && window.isSecureContext) {
      navigator.clipboard.writeText(text).then(function () { done(true); }, function () { done(false); });
    } else {
      // Fallback for non-HTTPS connections
      var ta = document.createElement('textarea');
      ta.value = text;
      ta.setAttribute('readonly', '');
      ta.style.position = 'fixed';
      ta.style.opacity = '0';
      document.body.appendChild(ta);
      ta.select();
      var ok = false;
      try { ok = document.execCommand('copy'); } catch (err) { ok = false; }
      document.body.removeChild(ta);
      done(ok);
    }
  });

  /* ----------------------------------------------------------------------
     Confirm before deleting
     ---------------------------------------------------------------------- */

  document.addEventListener('submit', function (e) {
    var form = e.target;
    var msg = form.getAttribute('data-confirm');
    if (msg && !window.confirm(msg)) {
      e.preventDefault();
    }
  });

  /* ----------------------------------------------------------------------
     Drag-and-drop uploads (FR-31)
     ---------------------------------------------------------------------- */

  document.querySelectorAll('[data-dropzone]').forEach(function (zone) {
    var input = zone.querySelector('input[type=file]');
    if (!input) return;

    ['dragenter', 'dragover'].forEach(function (type) {
      zone.addEventListener(type, function (e) {
        e.preventDefault();
        zone.classList.add('is-over');
      });
    });

    ['dragleave', 'drop'].forEach(function (type) {
      zone.addEventListener(type, function (e) {
        e.preventDefault();
        zone.classList.remove('is-over');
      });
    });

    zone.addEventListener('drop', function (e) {
      if (e.dataTransfer && e.dataTransfer.files.length) {
        input.files = e.dataTransfer.files;
        input.dispatchEvent(new Event('change', { bubbles: true }));
      }
    });

    input.addEventListener('change', function () {
      if (!input.files.length) return;

      var file = input.files[0];
      var label = zone.querySelector('[data-dropzone-label]');
      if (label) label.textContent = file.name + ' (' + Math.round(file.size / 1024) + ' KB)';

      // Preview in the browser before uploading
      var preview = document.querySelector(zone.getAttribute('data-preview') || '');
      if (preview && file.type.indexOf('image/') === 0) {
        var reader = new FileReader();
        reader.onload = function (ev) {
          preview.innerHTML = '<img src="' + ev.target.result + '" alt="Preview">';
          preview.classList.remove('hidden');
        };
        reader.readAsDataURL(file);
      }

      // Submit the form automatically when asked to
      if (zone.hasAttribute('data-autosubmit')) {
        var form = zone.closest('form');
        if (form) form.submit();
      }
    });
  });

  /* ----------------------------------------------------------------------
     Filter the list as you type (My Projects - FR-11)
     ---------------------------------------------------------------------- */

  var liveSearch = document.querySelector('[data-live-search]');
  if (liveSearch) {
    var targets = document.querySelectorAll(liveSearch.getAttribute('data-live-search'));
    var emptyMsg = document.querySelector('[data-search-empty]');

    liveSearch.addEventListener('input', function () {
      var q = liveSearch.value.trim().toLowerCase();
      var shown = 0;

      targets.forEach(function (el) {
        var hay = (el.getAttribute('data-search-text') || el.textContent).toLowerCase();
        var match = q === '' || hay.indexOf(q) !== -1;
        el.style.display = match ? '' : 'none';
        if (match) shown++;
      });

      if (emptyMsg) emptyMsg.classList.toggle('hidden', shown > 0);
    });
  }

  /* ----------------------------------------------------------------------
     Submit on filter / sort change (FR-12)
     ---------------------------------------------------------------------- */

  document.querySelectorAll('[data-autosubmit-select]').forEach(function (select) {
    select.addEventListener('change', function () {
      var form = select.closest('form');
      if (form) form.submit();
    });
  });

  /* ----------------------------------------------------------------------
     Character counter for text inputs
     ---------------------------------------------------------------------- */

  document.querySelectorAll('[data-counter]').forEach(function (input) {
    var out = document.querySelector(input.getAttribute('data-counter'));
    if (!out) return;
    function update() {
      out.textContent = input.value.length + ' / ' + (input.getAttribute('maxlength') || '');
    }
    input.addEventListener('input', update);
    update();
  });

  /* ----------------------------------------------------------------------
     AJAX submit (swapping a mission card - FR-26)
     ---------------------------------------------------------------------- */

  document.addEventListener('click', function (e) {
    var btn = e.target.closest('[data-reroll]');
    if (!btn) return;

    e.preventDefault();
    var url = btn.getAttribute('data-reroll');
    var row = btn.closest('.mission-row');
    var token = document.querySelector('meta[name=csrf-token]');

    btn.disabled = true;
    btn.classList.add('is-loading');

    fetch(url, {
      method: 'POST',
      headers: {
        'X-Requested-With': 'XMLHttpRequest',
        'X-CSRF-Token': token ? token.getAttribute('content') : '',
        'Accept': 'application/json'
      }
    })
      .then(function (r) { return r.json(); })
      .then(function (data) {
        if (data.ok && row) {
          var q = row.querySelector('[data-mission-question]');
          var a = row.querySelector('[data-mission-answer]');
          if (q) q.textContent = data.question;
          if (a) a.textContent = data.answer ? 'Answer: ' + data.answer : '';
          row.style.background = '#EFEAFD';
          setTimeout(function () { row.style.background = ''; }, 700);
        } else if (!data.ok) {
          window.alert(data.message || 'That card could not be swapped.');
        }
      })
      .catch(function () { window.alert('Could not reach the server.'); })
      .finally(function () {
        btn.disabled = false;
        btn.classList.remove('is-loading');
      });
  });

  /* ----------------------------------------------------------------------
     Printing
     ---------------------------------------------------------------------- */

  document.querySelectorAll('[data-print]').forEach(function (btn) {
    btn.addEventListener('click', function (e) {
      e.preventDefault();
      window.print();
    });
  });

})();
