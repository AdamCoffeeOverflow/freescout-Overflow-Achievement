(function () {
  // FreeScout uses PJAX-ish navigation and loads many pages via AJAX.
  // We do NOT override ajaxFinish; we listen to jQuery ajaxComplete instead.
  function escapeHtml(s) {
    if (s === null || typeof s === 'undefined') return '';
    return String(s)
      .replace(/&/g, '&amp;')
      .replace(/</g, '&lt;')
      .replace(/>/g, '&gt;')
      .replace(/"/g, '&quot;')
      .replace(/'/g, '&#039;');
  }

  function getBaseUrl() {
    var base = (window.Vars && window.Vars.public_url) ? window.Vars.public_url : '';
    if (base && base[base.length - 1] === '/') base = base.slice(0, -1);
    return base;
  }

  function getAuthUserId() {
    // FreeScout sets the authenticated user id on <body data-auth_user_id="...">.
    // Some versions do not expose Vars.user_id.
    try {
      var b = document.body;
      if (!b) return null;
      var v = b.getAttribute('data-auth_user_id') || b.getAttribute('data-auth-user-id');
      if (!v) return null;
      var n = parseInt(v, 10);
      return isNaN(n) ? null : n;
    } catch (e) {
      return null;
    }
  }

  // Sound: browsers usually require user interaction before audio can play.
  var _oaUserInteracted = false;
  function initUserInteractionFlag() {
    try {
      var mark = function () {
        _oaUserInteracted = true;
        try {
          window.removeEventListener('pointerdown', mark, true);
          window.removeEventListener('keydown', mark, true);
          window.removeEventListener('touchstart', mark, true);
        } catch (e) {}
      };
      window.addEventListener('pointerdown', mark, true);
      window.addEventListener('keydown', mark, true);
      window.addEventListener('touchstart', mark, true);
    } catch (e) {}
  }

  function canPlayUnlockSound() {
    try {
      return !!(window.OVERFLOWACHIEVEMENT_UI && window.OVERFLOWACHIEVEMENT_UI.sound_enabled && _oaUserInteracted);
    } catch (e) {
      return false;
    }
  }

  function playUnlockSound(item) {
    if (!canPlayUnlockSound()) return;
    try {
      // Rate limit: bursts should not create audio spam.
      var cooldown = 1200;
      try {
        if (window.OVERFLOWACHIEVEMENT_UI && window.OVERFLOWACHIEVEMENT_UI.sound_cooldown_ms) {
          var cd = parseInt(window.OVERFLOWACHIEVEMENT_UI.sound_cooldown_ms, 10);
          if (!isNaN(cd) && cd >= 0) cooldown = cd;
        }
      } catch (e0) {}

      var nowMs = Date.now ? Date.now() : (new Date()).getTime();
      var last = playUnlockSound._lastMs || 0;
      if (cooldown > 0 && (nowMs - last) < cooldown) return;
      playUnlockSound._lastMs = nowMs;

      var AudioCtx = window.AudioContext || window.webkitAudioContext;
      if (!AudioCtx) return;
      var ctx = playUnlockSound._ctx;
      if (!ctx) {
        ctx = new AudioCtx();
        playUnlockSound._ctx = ctx;
      }
      if (ctx.state === 'suspended') {
        // Try to resume after interaction.
        try { ctx.resume(); } catch (e1) {}
      }

      // Determine rarity for sound profile.
      var rarity = (item && item.rarity) ? String(item.rarity) : 'common';
      // For batch toasts, pick the highest rarity from the batch.
      try {
        if (item && item.is_batch && item.batch_items && item.batch_items.length) {
          var order = { common: 1, rare: 2, epic: 3, legendary: 4 };
          var best = 'common';
          for (var i = 0; i < item.batch_items.length; i++) {
            var r = item.batch_items[i] && item.batch_items[i].rarity ? String(item.batch_items[i].rarity) : 'common';
            if ((order[r] || 1) > (order[best] || 1)) best = r;
          }
          rarity = best;
        }
      } catch (e2) {}

      if (item && item.is_level) rarity = 'level';

      // Rarity sound profiles: tiny, pleasant, and distinct.
      // (freq1 -> freq2 over dur; volume is conservative)
      var profiles = {
        common:    { f1: 440, f2: 550, dur: 0.20, vol: 0.06 },
        rare:      { f1: 520, f2: 700, dur: 0.22, vol: 0.065 },
        epic:      { f1: 620, f2: 880, dur: 0.24, vol: 0.07 },
        legendary: { f1: 740, f2: 1040, dur: 0.28, vol: 0.075 },
        level:     { f1: 660, f2: 990, dur: 0.30, vol: 0.075 }
      };
      var prof = profiles[rarity] || profiles.common;

      // Simple chime using oscillator + envelope.
      var now = ctx.currentTime;
      var gain = ctx.createGain();
      gain.gain.setValueAtTime(0.0001, now);
      gain.gain.exponentialRampToValueAtTime(Math.max(0.0002, prof.vol), now + 0.02);
      gain.gain.exponentialRampToValueAtTime(0.0001, now + prof.dur);

      var osc = ctx.createOscillator();
      osc.type = 'sine';
      osc.frequency.setValueAtTime(prof.f1, now);
      osc.frequency.exponentialRampToValueAtTime(prof.f2, now + Math.min(0.12, prof.dur));

      osc.connect(gain);
      gain.connect(ctx.destination);
      osc.start(now);
      osc.stop(now + prof.dur + 0.02);
    } catch (e) {
      // Silent failure: sound should never break UI.
    }
  }


function ensureToastWrap() {
    var wrap = document.querySelector('.oa-toast-wrap');
    if (wrap) return wrap;
    wrap = document.createElement('div');
    wrap.className = 'oa-toast-wrap';
    document.body.appendChild(wrap);
    return wrap;
  }

  function rarityClass(r) {
    r = String(r || 'common');
    if (r === 'legendary') return 'oa-r-legendary';
    if (r === 'epic') return 'oa-r-epic';
    if (r === 'rare') return 'oa-r-rare';
    return 'oa-r-common';
  }

  function normalizeIconUrl(v) {
    if (!v) return '';
    v = String(v);
    // Absolute URLs or full paths
    if (v.indexOf('http://') === 0 || v.indexOf('https://') === 0) return v;
    var base = getBaseUrl();
    // Stored as "/modules/..." (common) -> prefix with base (which includes subdir if any)
    if (v.indexOf('/modules/') === 0) return base + v;
    // Stored as "modules/..." without leading slash
    if (v.indexOf('modules/') === 0) return base + '/' + v;
    // Stored as bare filename (icon pack)
    if (v.indexOf('/') === -1) return base + '/modules/overflowachievement/icons/pack/' + v;
    // Any other relative path: treat as relative to base
    if (v[0] !== '/') return base + '/' + v;
    return base + v;
  }

  // Image icon fallback: if an icon pack image is missing/blocked, swap to a safe FontAwesome icon
  // (or glyphicon) so cards/toasts/modals never look "half baked".
  function bindIconFallback(root) {
    try {
      var scope = root || document;
      var imgs = scope.querySelectorAll ? scope.querySelectorAll('img.oa-icon-img') : [];
      for (var i = 0; i < imgs.length; i++) {
        (function (img) {
          if (!img || img.__oaFallbackBound) return;
          img.__oaFallbackBound = true;
          img.addEventListener('error', function () {
            try {
              if (img.__oaFallbackDone) return;
              img.__oaFallbackDone = true;
              var fa = img.getAttribute('data-oa-fallback-fa') || 'fa-trophy';
              var m = String(fa).match(/\bfa-[a-z0-9-]+\b/i);
              var faClass = (m && m[0]) ? m[0] : 'fa-trophy';
              var span = document.createElement('span');
              span.className = 'oa-icon-fallback';
              span.innerHTML = '<i class="fa ' + escapeHtml(faClass) + '"></i>';
              if (img.parentNode) {
                img.parentNode.replaceChild(span, img);
              }
            } catch (e) {}
          }, true);
        })(imgs[i]);
      }
    } catch (e) {}
  }

  function iconHtml(item) {
    if (!item) return '<i class="glyphicon glyphicon-star"></i>';

    // Batch toast: render a "mini collage" of the first few unlocked icons.
    // This avoids the top-left icon area looking empty/grey when the batch contains
    // image-based icons and FontAwesome may not be available for the placeholder.
    if (item.is_batch) {
      try {
        var list = (item.batch_items && item.batch_items.length) ? item.batch_items : [];
        if (!list.length) return '<i class="fa fa-trophy"></i>';
        var max = Math.min(4, list.length);
        var cells = '';
        for (var k = 0; k < max; k++) {
          cells += '<span class="oa-batch-icon-cell">' + iconHtml(list[k] || {}) + '</span>';
        }
        // If less than 4 items, pad so the grid layout stays consistent.
        for (var p = max; p < 4; p++) {
          cells += '<span class="oa-batch-icon-cell oa-batch-icon-pad"><i class="fa fa-trophy"></i></span>';
        }
        return '<div class="oa-batch-icon-grid" aria-hidden="true">' + cells + '</div>';
      } catch (e) {
        return '<i class="fa fa-trophy"></i>';
      }
    }

    if (item.icon_type === 'img' && item.icon_value) {
      var src = normalizeIconUrl(item.icon_value);
      return '<img class="oa-icon-img" data-oa-fallback-fa="fa-trophy" alt="" src="' + escapeHtml(src) + '" />';
    }

    // Prefer FontAwesome if available; fallback to glyphicon.
    // Normalize stored values like "fa fa-trophy" or "fas fa-trophy" into "fa-trophy".
    var v = String(item.icon_value || 'fa-trophy');
    var m = v.match(/\bfa-[a-z0-9-]+\b/i);
    if (m && m[0]) {
      return '<i class="fa ' + escapeHtml(m[0]) + '"></i>';
    }
    if (v.indexOf('fa-') === 0) {
      return '<i class="fa ' + escapeHtml(v) + '"></i>';
    }
    return '<i class="glyphicon glyphicon-star"></i>';
  }

  // Used by the trophy details modal (and any other UI that stores icon_type/icon_value on DOM nodes).
  function renderIconHtml(iconType, iconValue) {
    var t = String(iconType || 'fa').toLowerCase();
    var v = String(iconValue || 'fa-trophy');
    if (t === 'img' && v) {
      var src = normalizeIconUrl(v);
      return '<img class="oa-icon-img" data-oa-fallback-fa="fa-trophy" alt="" src="' + escapeHtml(src) + '" />';
    }
    var m = v.match(/\bfa-[a-z0-9-]+\b/i);
    if (m && m[0]) {
      return '<i class="fa ' + escapeHtml(m[0]) + '"></i>';
    }
    if (v.indexOf('fa-') === 0) {
      return '<i class="fa ' + escapeHtml(v) + '"></i>';
    }
    return '<i class="glyphicon glyphicon-star"></i>';
  }

  // Tiny i18n helper for JS UI.
  // Uses window.OVERFLOWACHIEVEMENT_I18N when available (from vars.js), otherwise falls back to English.
  function t(key, fallback, vars) {
    var dict = window.OVERFLOWACHIEVEMENT_I18N || {};
    var s = (dict && typeof dict[key] === 'string') ? dict[key] : fallback;
    if (!vars) return s;
    // Simple token replacement: :name
    for (var k in vars) {
      if (!Object.prototype.hasOwnProperty.call(vars, k)) continue;
      s = s.split(':' + k).join(String(vars[k]));
    }
    return s;
  }


  function shouldCelebrate(item) {
    if (!item) return false;
    if (!window.OVERFLOWACHIEVEMENT_EFFECT || window.OVERFLOWACHIEVEMENT_EFFECT === 'off') return false;
    if (item.is_level_up) return true;
    return item.rarity === 'epic' || item.rarity === 'legendary';
  }

  function getToastAnchorRect() {
    var wrap = document.querySelector('.oa-toast-wrap');
    if (wrap && wrap.firstChild && wrap.firstChild.getBoundingClientRect) {
      return wrap.firstChild.getBoundingClientRect();
    }
    // Fallback to top-right corner.
    var vw = Math.max(document.documentElement.clientWidth || 0, window.innerWidth || 0);
    return { left: vw - 380, top: 16, width: 360, height: 120 };
  }

  function confettiBurst() {
    // Tiny, dependency-free confetti (DOM nodes). Anchored near the toast so it's always visible.
    var root = document.createElement('div');
    root.className = 'oa-confetti';
    document.body.appendChild(root);

    var pieces = 28;
    var r = getToastAnchorRect();
    // Two-anchor burst: top-left and top-right corners of the toast.
    var anchors = [
      { x: r.left + 22, y: r.top + 18 },
      { x: r.left + r.width - 22, y: r.top + 18 }
    ];

    for (var a = 0; a < anchors.length; a++) {
      for (var i = 0; i < pieces; i++) {
        var p = document.createElement('div');
        p.className = 'oa-confetti-piece';
        p.style.left = (anchors[a].x - 80 + Math.random() * 140) + 'px';
        p.style.top = (anchors[a].y + Math.random() * 26) + 'px';
        p.style.transform = 'rotate(' + (Math.random() * 360) + 'deg)';
        p.style.animationDelay = (Math.random() * 120) + 'ms';
        root.appendChild(p);
      }
    }

    setTimeout(function () {
      if (root && root.parentNode) root.parentNode.removeChild(root);
    }, 1700);
  }

  function fireworksBurst() {
    // "Fireworks"-ish sparkle burst. Lightweight radial particles.
    var root = document.createElement('div');
    root.className = 'oa-fireworks';
    document.body.appendChild(root);

    var sparks = 18;
    var r = getToastAnchorRect();
    var centers = [
      { x: r.left + 24, y: r.top + 28 },
      { x: r.left + r.width - 24, y: r.top + 28 }
    ];

    for (var c = 0; c < centers.length; c++) {
      for (var i = 0; i < sparks; i++) {
        var s = document.createElement('div');
        s.className = 'oa-spark';
        var ang = (Math.PI * 2) * (i / sparks) + (Math.random() * 0.35);
        var dist = 60 + Math.random() * 40;
        s.style.left = centers[c].x + 'px';
        s.style.top = centers[c].y + 'px';
        s.style.setProperty('--dx', (Math.cos(ang) * dist) + 'px');
        s.style.setProperty('--dy', (Math.sin(ang) * dist) + 'px');
        s.style.animationDelay = (Math.random() * 90) + 'ms';
        root.appendChild(s);
      }
    }

    setTimeout(function () {
      if (root && root.parentNode) root.parentNode.removeChild(root);
    }, 1500);
  }

  function maxVisibleToasts() {
    // Default: one toast at a time to avoid missing unlocks.
    // Optional: allow a small stack (admin setting) for bursty unlock moments.
    var enabled = (window.OVERFLOWACHIEVEMENT_TOAST_STACK_ENABLED === true);
    var mx = 1;
    if (enabled) {
      var v = (typeof window.OVERFLOWACHIEVEMENT_TOAST_STACK_MAX !== "undefined") ? parseInt(window.OVERFLOWACHIEVEMENT_TOAST_STACK_MAX, 10) : 2;
      if (!isNaN(v) && v >= 1) mx = Math.max(1, Math.min(5, v));
      else mx = 2;
    }
    return mx;
  }

  var _oaToastQueue = [];
  var _oaActiveToasts = 0;

  // Persist toasts across full page loads.
  // Why: FreeScout sometimes performs full reloads and our AJAX endpoint marks unlocks as seen.
  // If a user navigates away while a toast is visible, the toast disappears AND will not reappear.
  // Solution: keep a small pending queue in sessionStorage and mark seen only after dismissal.
  // v2 stores richer payload: { id, item, stat } so we can render the same content after reload.
  var OA_STORAGE_KEY = 'overflowachievement_pending_v2';
  // Persist UI config too (duration/sticky/theme/effect) so the first toast after reload uses
  // the user's settings instead of falling back to defaults.
  var OA_UI_KEY = 'overflowachievement_ui_v1';
  var OA_STORAGE_TTL_MS = 6 * 60 * 60 * 1000; // 6 hours

  function storageAvailable() {
    try {
      var x = '__oa_test__' + String(Date.now());
      window.sessionStorage.setItem(x, '1');
      window.sessionStorage.removeItem(x);
      return true;
    } catch (e) {
      return false;
    }
  }

  function readPending() {
    if (!storageAvailable()) return [];
    try {
      var raw = window.sessionStorage.getItem(OA_STORAGE_KEY);
      if (!raw) return [];
      var parsed = JSON.parse(raw);
      if (!parsed || !parsed.items || !Array.isArray(parsed.items)) return [];
      if (parsed.ts && (Date.now() - parsed.ts) > OA_STORAGE_TTL_MS) {
        window.sessionStorage.removeItem(OA_STORAGE_KEY);
        return [];
      }
      return parsed.items;
    } catch (e) {
      return [];
    }
  }

  function writePending(items) {
    if (!storageAvailable()) return;
    try {
      var payload = { ts: Date.now(), items: items || [] };
      window.sessionStorage.setItem(OA_STORAGE_KEY, JSON.stringify(payload));
    } catch (e) {}
  }

  function upsertPending(newItems) {
    // newItems: [{id, item, stat}] OR legacy [{id, ...itemFields}]
    if (!newItems || !newItems.length) return;
    var cur = readPending();
    var seen = {};
    cur.forEach(function (it) {
      if (it && typeof it.id !== 'undefined') seen[String(it.id)] = true;
    });
    newItems.forEach(function (it) {
      if (!it || typeof it.id === 'undefined') return;
      var k = String(it.id);
      if (!seen[k]) {
        cur.push(it);
        seen[k] = true;
      } else {
        // If it already exists, refresh the stored item/stat so details remain accurate.
        // (e.g. user reloads quickly, we want the latest stat snapshot.)
        for (var i = 0; i < cur.length; i++) {
          if (cur[i] && typeof cur[i].id !== 'undefined' && String(cur[i].id) === k) {
            cur[i] = it;
            break;
          }
        }
      }
    });
    writePending(cur);
  }

  function removePendingById(id) {
    var cur = readPending();
    var sid = String(id);
    cur = cur.filter(function (it) {
      return !it || typeof it.id === 'undefined' ? true : (String(it.id) !== sid);
    });
    writePending(cur);
  }

  function readUiCache() {
    if (!storageAvailable()) return null;
    try {
      var raw = window.sessionStorage.getItem(OA_UI_KEY);
      if (!raw) return null;
      var parsed = JSON.parse(raw);
      return parsed || null;
    } catch (e) {
      return null;
    }
  }

  function writeUiCache(ui) {
    if (!storageAvailable()) return;
    try {
      window.sessionStorage.setItem(OA_UI_KEY, JSON.stringify(ui || {}));
    } catch (e) {}
  }

  function applyUiConfig(ui) {
    if (!ui) return;
    // Keep a canonical copy for features like sound toggles.
    try { window.OVERFLOWACHIEVEMENT_UI = ui; } catch (e0) {}
    if (typeof ui.effect !== 'undefined') {
      window.OVERFLOWACHIEVEMENT_EFFECT = String(ui.effect || 'confetti');
    } else if (typeof ui.confetti !== 'undefined') {
      window.OVERFLOWACHIEVEMENT_EFFECT = ui.confetti ? 'confetti' : 'off';
    }
    if (typeof ui.confetti !== 'undefined' && !ui.confetti) {
      window.OVERFLOWACHIEVEMENT_EFFECT = 'off';
    }
    if (ui.toast_theme) {
      var theme = String(ui.toast_theme || 'neon');
      document.body.setAttribute('data-oa-theme', theme);
      document.body.className = document.body.className.replace(/\boa-theme-[a-z0-9_-]+\b/g, '');
      document.body.className += ' oa-theme-' + theme;
    }
    if (typeof ui.toast_sticky !== 'undefined') {
      window.OVERFLOWACHIEVEMENT_TOAST_STICKY = !!ui.toast_sticky;
    }
    if (typeof ui.toast_duration_ms !== 'undefined') {
      var ms = parseInt(ui.toast_duration_ms, 10);
      if (!isNaN(ms) && ms >= 0) {
        window.OVERFLOWACHIEVEMENT_TOAST_DURATION = ms;
      }
    }

    if (typeof ui.toast_stack_enabled !== 'undefined') {
      window.OVERFLOWACHIEVEMENT_TOAST_STACK_ENABLED = !!ui.toast_stack_enabled;
    }
    if (typeof ui.toast_stack_max !== 'undefined') {
      var mx = parseInt(ui.toast_stack_max, 10);
      if (!isNaN(mx) && mx >= 1) {
        window.OVERFLOWACHIEVEMENT_TOAST_STACK_MAX = Math.max(1, Math.min(5, mx));
      }
    }
  }

  function markSeenIds(ids, cb) {
    if (!ids || !ids.length) { if (cb) cb(); return; }
    if (typeof fsAjax !== 'function') { if (cb) cb(); return; }

    // Hard safety: never send non-numeric IDs (e.g. demo toast uses id "demo").
    // Postgres will throw invalid bigint syntax otherwise.
    var safeIds = [];
    for (var i = 0; i < ids.length; i++) {
      var v = ids[i];
      if (typeof v === 'number' && isFinite(v)) { safeIds.push(v); continue; }
      if (typeof v === 'string' && /^\d+$/.test(v)) { safeIds.push(parseInt(v, 10)); continue; }
    }
    if (!safeIds.length) { if (cb) cb(); return; }
    var base = getBaseUrl();
    var markSeenUrl = (window.OVERFLOWACHIEVEMENT_MARK_SEEN_URL) ? String(window.OVERFLOWACHIEVEMENT_MARK_SEEN_URL) : (base + '/modules/overflowachievement/mark-seen');
    fsAjax({ ids: safeIds }, markSeenUrl, function () {
      if (cb) cb();
    }, true, function () {
      if (cb) cb();
    }, { method: 'post' });
  }

  
function closeToast(toast, done) {
  if (!toast) { if (done) done(); return; }
  if (toast._oaClosed) { if (done) done(); return; }
  toast._oaClosed = true;
  try {
    if (toast._oaTimer) { clearTimeout(toast._oaTimer); toast._oaTimer = null; }
  } catch (e) {}
  try { toast.classList.add('oa-toast-out'); } catch (e) {}
  setTimeout(function () {
    try {
      if (toast && toast.parentNode) toast.parentNode.removeChild(toast);
    } catch (e) {}
    if (done) done();
  }, 260);
}

// --- Single "identity" toast (one DOM node) that updates as the queue advances ---
var _oaToastSingleton = null;
var _oaToastCurrent = null; // { item, stat }
var _oaToastAnimating = false;
// Last known stat snapshot from the most recent poll. Used to animate XP/level
// changes smoothly when multiple unlocks arrive in a burst.
var _oaLastKnownStat = null;

// Persist last-known stat across reloads so the first real unlock after a page load
// can still animate from the correct starting point (older FreeScout installs
// often reload full pages between actions).
var OA_LAST_STAT_KEY = 'overflowachievement_last_stat_v1';
function readLastStatCache() {
  try {
    var raw = localStorage.getItem(OA_LAST_STAT_KEY);
    if (!raw) return null;
    var o = JSON.parse(raw);
    return (o && typeof o === 'object') ? o : null;
  } catch (e) {
    return null;
  }
}
function writeLastStatCache(stat) {
  try {
    if (!stat) return;
    localStorage.setItem(OA_LAST_STAT_KEY, JSON.stringify(stat));
  } catch (e) {}
}

// Bootstrap from cache immediately.
_oaLastKnownStat = readLastStatCache();

// Secondary "Level Up" toast (stacked under the main achievement toast)
var _oaLevelToast = null;

function ensureLevelToast() {
  if (_oaLevelToast && document.body.contains(_oaLevelToast)) return _oaLevelToast;
  var wrap = ensureToastWrap();
  var node = document.createElement('div');
  node.innerHTML = ''
    + '<div class="oa-level-toast" style="display:none">'
    + '  <div class="oa-level-toast-inner">'
    + '    <div class="oa-level-toast-badge">▲</div>'
    + '    <div class="oa-level-toast-text">'
    + '      <div class="oa-level-toast-title">' + escapeHtml(t('level_up', 'Level Up!')) + '</div>'
    + '      <div class="oa-level-toast-sub">Lv <span data-oa-lv="from">0</span> → <span data-oa-lv="to">0</span></div>'
    + '    </div>'
    + '  </div>'
    + '</div>';
  _oaLevelToast = node.firstChild;
  // Ensure it is stacked *under* the main achievement toast.
  // If created before the main toast, insert it after once the singleton exists.
  if (_oaToastSingleton && wrap.contains(_oaToastSingleton)) {
    wrap.insertBefore(_oaLevelToast, _oaToastSingleton.nextSibling);
  } else {
    wrap.appendChild(_oaLevelToast);
  }
  return _oaLevelToast;
}

function showLevelToast(prevStat, nextStat) {
  if (!prevStat || !nextStat) return;
  if (typeof prevStat.level === 'undefined' || typeof nextStat.level === 'undefined') return;
  var fromLv = fmtInt(prevStat.level);
  var toLv = fmtInt(nextStat.level);
  if (fromLv === null || toLv === null) return;
  if (toLv <= fromLv) return;

  var toast = ensureLevelToast();
  var fromEl = toast.querySelector('[data-oa-lv="from"]');
  var toEl = toast.querySelector('[data-oa-lv="to"]');
  if (fromEl) fromEl.textContent = String(fromLv);
  if (toEl) toEl.textContent = String(toLv);

  if (toast._oaTimer) { clearTimeout(toast._oaTimer); toast._oaTimer = null; }
  toast.classList.remove('oa-level-toast-out');
  toast.style.display = 'block';

  toast._oaTimer = setTimeout(function () {
    toast.classList.add('oa-level-toast-out');
    setTimeout(function () {
      toast.style.display = 'none';
    }, 260);
  }, 3200);
}

function ensureToastSingleton() {
  if (_oaToastSingleton && document.body.contains(_oaToastSingleton)) return _oaToastSingleton;

  var wrap = ensureToastWrap();
  var node = document.createElement('div');
  node.innerHTML = ''
	  + '<div class="oa-toast oa-r-common oa-toast-single">'
	  + '  <div class="oa-toast-fx oa-toast-fx-left" aria-hidden="true"></div>'
	  + '  <div class="oa-toast-fx oa-toast-fx-right" aria-hidden="true"></div>'
	  + '  <div class="oa-toast-clip">'
	  + '    <button type="button" class="oa-toast-dismiss" aria-label="Dismiss">×</button>'
	  + '    <div class="oa-toast-top">'
	  + '      <div class="oa-toast-icon" data-oa-slot="icon"></div>'
	  + '      <div class="oa-toast-head">'
	  + '        <div class="oa-toast-headline" data-oa-slot="headline"></div>'
	  + '        <div class="oa-toast-title" data-oa-slot="title"></div>'
	  + '      </div>'
	  + '      <div class="oa-toast-right">'
	  + '        <div class="oa-toast-rarity" data-oa-slot="rarity"></div>'
	  + '        <div class="oa-toast-count" data-oa-slot="count"></div>'
	  + '      </div>'
	  + '    </div>'
	  + '    <div class="oa-toast-body">'
	  + '      <div data-oa-slot="quote"></div>'
	  + '      <div class="oa-toast-meta">'
	  + '        <div class="oa-toast-pills" data-oa-slot="pills"></div>'
	  + '        <div class="oa-toast-to-next" data-oa-slot="toNext"></div>'
	  + '      </div>'
	  + '      <div class="oa-toast-progress" aria-hidden="true"><span data-oa-slot="bar" style="transform:scaleX(0)"></span></div>'
	  + '      <div class="oa-toast-actions">'
	  + '        <a class="oa-toast-btn" data-oa-slot="btn" href="#">View trophies</a>'
	  + '      </div>'
	  + '    </div>'
	  + '  </div>'
	  + '</div>';
  _oaToastSingleton = node.firstChild;
  // Ensure main achievement toast is always the first item in the stack.
  if (_oaLevelToast && wrap.contains(_oaLevelToast)) {
    wrap.insertBefore(_oaToastSingleton, _oaLevelToast);
  } else {
    wrap.appendChild(_oaToastSingleton);
  }

  // Dismiss closes the whole sequence and marks everything visible/queued as seen.
  var btn = _oaToastSingleton.querySelector('.oa-toast-dismiss');
  if (btn) {
    btn.addEventListener('click', function (e) {
      e.preventDefault();
      var ids = [];
      if (_oaToastCurrent) {
        if (_oaToastCurrent.ids && _oaToastCurrent.ids.length) {
          Array.prototype.push.apply(ids, _oaToastCurrent.ids);
        } else if (_oaToastCurrent.item && typeof _oaToastCurrent.item.id !== 'undefined') {
          ids.push(_oaToastCurrent.item.id);
        }
      }
      // Also include anything left in the in-memory queue.
      for (var i = 0; i < _oaToastQueue.length; i++) {
        if (_oaToastQueue[i] && _oaToastQueue[i].ids && _oaToastQueue[i].ids.length) {
          Array.prototype.push.apply(ids, _oaToastQueue[i].ids);
        } else if (_oaToastQueue[i] && _oaToastQueue[i].item && typeof _oaToastQueue[i].item.id !== 'undefined') {
          ids.push(_oaToastQueue[i].item.id);
        }
      }
      // Clear pending storage for those ids.
      ids.forEach(function (id) { removePendingById(id); });
      _oaToastQueue = [];
      _oaActiveToasts = 0;
      markSeenIds(ids);
      closeToast(_oaToastSingleton, function () {
        _oaToastSingleton = null;
        _oaToastCurrent = null;
      });
    });
  }

  return _oaToastSingleton;
}

function createToastNodeForStack(next) {
  var wrap = ensureToastWrap();
  var node = document.createElement('div');
  node.innerHTML = ''
    + '<div class="oa-toast oa-r-common oa-toast-stack-item">'
    + '  <div class="oa-toast-fx oa-toast-fx-left" aria-hidden="true"></div>'
    + '  <div class="oa-toast-fx oa-toast-fx-right" aria-hidden="true"></div>'
    + '  <div class="oa-toast-clip">'
    + '    <button type="button" class="oa-toast-dismiss" aria-label="Dismiss">×</button>'
    + '    <div class="oa-toast-top">'
    + '      <div class="oa-toast-icon" data-oa-slot="icon"></div>'
    + '      <div class="oa-toast-head">'
    + '        <div class="oa-toast-headline" data-oa-slot="headline"></div>'
    + '        <div class="oa-toast-title" data-oa-slot="title"></div>'
    + '      </div>'
    + '      <div class="oa-toast-right">'
    + '        <div class="oa-toast-rarity" data-oa-slot="rarity"></div>'
    + '        <div class="oa-toast-count" data-oa-slot="count"></div>'
    + '      </div>'
    + '    </div>'
    + '    <div class="oa-toast-body">'
    + '      <div data-oa-slot="quote"></div>'
    + '      <div class="oa-toast-meta">'
    + '        <div class="oa-toast-pills" data-oa-slot="pills"></div>'
    + '        <div class="oa-toast-to-next" data-oa-slot="toNext"></div>'
    + '      </div>'
    + '      <div class="oa-toast-progress" aria-hidden="true"><span data-oa-slot="bar" style="transform:scaleX(0)"></span></div>'
    + '      <div class="oa-toast-actions">'
    + '        <a class="oa-toast-btn" data-oa-slot="btn" href="#">View trophies</a>'
    + '      </div>'
    + '    </div>'
    + '  </div>'
    + '</div>';

  var toast = node.firstChild;
  wrap.appendChild(toast);

  var btn = toast.querySelector('.oa-toast-dismiss');
  if (btn) {
    btn.addEventListener('click', function (e) {
      e.preventDefault();
      try { if (toast._oaTimer) { clearTimeout(toast._oaTimer); toast._oaTimer = null; } } catch (e2) {}
      var ids = (next && next.ids && next.ids.length) ? next.ids : (next && next.item && typeof next.item.id !== 'undefined' ? [next.item.id] : []);
      if (ids && ids.length) {
        ids.forEach(function (id) { removePendingById(id); });
        if (next && next.item && typeof next.item.id === 'string' && next.item.id.indexOf('batch_') === 0) removePendingById(next.item.id);
        markSeenIds(ids);
      }
      closeToast(toast, function () {
        _oaActiveToasts = Math.max(0, _oaActiveToasts - 1);
        processToastQueue();
      });
    });
  }

  return toast;
}

function fmtInt(v) {
  if (v === null || typeof v === 'undefined') return null;
  var n = parseInt(v, 10);
  if (isNaN(n)) return null;
  return n;
}

function animateNumber(el, from, to, ms) {
  if (!el) return;
  from = fmtInt(from); to = fmtInt(to);
  if (from === null || to === null) { el.textContent = escapeHtml(String(to === null ? '' : to)); return; }
  if (ms <= 0 || from === to) { el.textContent = String(to); return; }

  // Cancel any in-flight animation on this element.
  el._oaAnimId = (el._oaAnimId || 0) + 1;
  var myAnimId = el._oaAnimId;

  var start = Date.now();
  function tick() {
    if (el._oaAnimId !== myAnimId) return;
    var t = (Date.now() - start) / ms;
    if (t >= 1) {
      el.textContent = String(to);
      return;
    }
    // Smooth-ish easing
    var k = t < 0.5 ? (2 * t * t) : (1 - Math.pow(-2 * t + 2, 2) / 2);
    var cur = Math.round(from + (to - from) * k);
    el.textContent = String(cur);
    requestAnimationFrame(tick);
  }
  requestAnimationFrame(tick);
}

function computeProgressPercent(stat) {
  // Prefer computing from xp_total / cur_min / next_min so the bar never exceeds the level target.
  // stat.progress is sometimes a rounded value and can look jumpy when multiple unlocks arrive fast.
  if (!stat) return 0;
  var xpTotal = fmtInt(stat.xp_total);
  var curMin = fmtInt(stat.cur_min);
  var nextMin = fmtInt(stat.next_min);
  if (xpTotal === null || curMin === null || nextMin === null) {
    var p = (typeof stat.progress !== 'undefined') ? parseFloat(stat.progress) : 0;
    if (isNaN(p)) p = 0;
    return Math.max(0, Math.min(100, p));
  }
  var den = Math.max(1, nextMin - curMin);
  var inLevel = Math.max(0, xpTotal - curMin);
  var pct = (inLevel / den) * 100;
  if (isNaN(pct)) pct = 0;
  return Math.max(0, Math.min(100, pct));
}

function setToastThemeClass(toast, item) {
  var rarity = rarityClass(item && item.rarity);
  toast.className = toast.className
    .replace(/\boa-r-(common|rare|epic|legendary)\b/g, '')
    .replace(/\s+/g, ' ')
    .trim();
  toast.className += ' ' + rarity + ' oa-toast-single';
}

function updateToastContent(toast, item, stat, prevStat) {
  // Slots
  var $icon = toast.querySelector('[data-oa-slot="icon"]');
  var $headline = toast.querySelector('[data-oa-slot="headline"]');
  var $title = toast.querySelector('[data-oa-slot="title"]');
  var $rarity = toast.querySelector('[data-oa-slot="rarity"]');
  var $count = toast.querySelector('[data-oa-slot="count"]');
  var $quote = toast.querySelector('[data-oa-slot="quote"]');
  var $pills = toast.querySelector('[data-oa-slot="pills"]');
  var $toNext = toast.querySelector('[data-oa-slot="toNext"]');
  var $bar = toast.querySelector('[data-oa-slot="bar"]');
  var $btn = toast.querySelector('[data-oa-slot="btn"]');

  // --- Minimal safe render first (never allow a blank toast) ---
  var safeItem = item || {};
  var isBatch = !!(safeItem && safeItem.is_batch && safeItem.batch_items && safeItem.batch_items.length);

  var safeHeadline = (safeItem && safeItem.is_level_up)
    ? t('level_up', 'Level Up!')
    : (isBatch ? t('achievements_unlocked', 'Achievements Unlocked') : t('trophy_unlocked', 'Trophy Unlocked'));

  // If title is missing, use the same wording as the headline (better than "Achievement")
  var safeTitle = escapeHtml((safeItem && safeItem.title) ? safeItem.title : safeHeadline);
  var safeRarityText = escapeHtml(String((safeItem && safeItem.rarity) ? safeItem.rarity : 'common').toUpperCase());

  try { if ($icon) $icon.innerHTML = iconHtml(safeItem); } catch (e) {}
  try { bindIconFallback(toast); } catch (e) {}
  try { if ($headline) $headline.textContent = safeHeadline; } catch (e) {}
  try { if ($title) $title.textContent = safeTitle; } catch (e) {}
  try { if ($rarity) $rarity.textContent = safeRarityText; } catch (e) {}
  try { if ($count) $count.textContent = ''; } catch (e) {}

  // Everything below is "nice to have". Guard hard so one bug doesn't wipe the toast UI.
  try {
    // Quote / batch list
    var quoteHtml = '';
    if (isBatch) {
      var rows = '';
      var maxShow = 6;
      for (var bi = 0; bi < safeItem.batch_items.length; bi++) {
        if (bi >= maxShow) break;
        var it = safeItem.batch_items[bi] || {};
        var tTitle = it.title ? it.title : 'Achievement';
        var rTxt = String(it.rarity ? it.rarity : 'common').toUpperCase();
        rows += ''
          + '<div class="oa-batch-row">'
          + '  <div class="oa-batch-ico">' + iconHtml(it) + '</div>'
          + '  <div class="oa-batch-title">' + escapeHtml(tTitle) + '</div>'
          + '  <div class="oa-batch-r ' + escapeHtml(rarityClass(it.rarity)) + '">' + escapeHtml(rTxt) + '</div>'
          + '</div>';
      }
      var more = '';
      if (safeItem.batch_items.length > maxShow) {
        more = '<div class="oa-batch-more">+' + escapeHtml(safeItem.batch_items.length - maxShow) + ' ' + escapeHtml(t('more', 'more')) + '</div>';
      }
      quoteHtml = ''
        + '<div class="oa-toast-batch">'
        + '  <div class="oa-batch-head">' + escapeHtml(t('unlocked_list', 'Unlocked')) + '</div>'
        + '  <div class="oa-batch-list" role="list">' + rows + '</div>'
        + more
        + '</div>';
    } else if (safeItem && safeItem.quote_text) {
      quoteHtml = '<div class="oa-toast-quote">“' + escapeHtml(safeItem.quote_text) + '”'
        + (safeItem.quote_author ? ('<div class="oa-toast-author">— ' + escapeHtml(safeItem.quote_author) + '</div>') : '')
        + '</div>';
    }
    if ($quote) $quote.innerHTML = quoteHtml;
    try { bindIconFallback(toast); } catch (e) {}

    // Pills (Lv + XP) with animated number updates
    var level = stat && typeof stat.level !== 'undefined' ? stat.level : null;
    var xpTotal = stat && typeof stat.xp_total !== 'undefined' ? stat.xp_total : null;

    if ($pills) {
      if (!$pills._oaInit) {
        $pills.innerHTML = ''
          + '<span class="oa-pill oa-pill-lv">Lv <span class="oa-num" data-oa-num="lv">0</span></span>'
          + '<span class="oa-pill oa-pill-xp"><span class="oa-num" data-oa-num="xp">0</span> XP</span>';
        $pills._oaInit = true;
      }
      var lvEl = $pills.querySelector('[data-oa-num="lv"]');
      var xpEl = $pills.querySelector('[data-oa-num="xp"]');
      var prevLv = prevStat && typeof prevStat.level !== 'undefined' ? prevStat.level : level;
      var prevXp = prevStat && typeof prevStat.xp_total !== 'undefined' ? prevStat.xp_total : xpTotal;
      animateNumber(lvEl, prevLv, level, 650);
      animateNumber(xpEl, prevXp, xpTotal, 650);

      // Pulse the level pill if we leveled up.
      try {
        var fromLv2 = fmtInt(prevLv);
        var toLv2 = fmtInt(level);
        var lvPill = $pills.querySelector('.oa-pill-lv');
        if (lvPill && fromLv2 !== null && toLv2 !== null && toLv2 > fromLv2) {
          lvPill.classList.remove('oa-pill-pulse');
          try { lvPill.offsetWidth; } catch (e) {}
          lvPill.classList.add('oa-pill-pulse');
        }
      } catch (e) {}
    }

    // "To next" meta
    if ($toNext) {
      var metaRight = '';
      if (stat && stat.next_min) {
        var curMin = stat.cur_min || 0;
        var den = Math.max(1, stat.next_min - curMin);
        var inLevel = Math.max(0, fmtInt(xpTotal) - fmtInt(curMin));
        metaRight = escapeHtml(inLevel) + '/' + escapeHtml(den);
      }
      $toNext.textContent = metaRight ? ('To next: ' + metaRight) : '';
    }

    // Progress bar animation
    if ($bar) {
      var $barWrap = toast.querySelector('.oa-toast-progress');

      // Cancel any in-flight bar rollover timers from a previous toast update.
      if ($bar._oaTimers && $bar._oaTimers.length) {
        for (var ti = 0; ti < $bar._oaTimers.length; ti++) clearTimeout($bar._oaTimers[ti]);
      }
      $bar._oaTimers = [];

      var fromVal = computeProgressPercent(prevStat);
      var toVal = computeProgressPercent(stat);

      $bar._oaAnimId = ($bar._oaAnimId || 0) + 1;
      var myBarAnimId = $bar._oaAnimId;

      var fromScale = Math.max(0, Math.min(1, fromVal / 100));
      var toScale = Math.max(0, Math.min(1, toVal / 100));

      // Make the motion visible and reliable across older browsers/webviews:
      // 1) set a starting transform,
      // 2) force a reflow,
      // 3) animate to the target using Web Animations API when available,
      //    otherwise fall back to CSS transition + requestAnimationFrame.
      try {
        if ($bar._oaWaAnim && $bar._oaWaAnim.cancel) $bar._oaWaAnim.cancel();
      } catch (e) {}

      $bar.style.transform = 'scaleX(' + fromScale + ')';
      try { $bar.offsetWidth; } catch (e) {}

      function waAnimate(fromS, toS, dur) {
        try {
          if (!$bar.animate) return false;
          $bar._oaWaAnim = $bar.animate(
            [{ transform: 'scaleX(' + fromS + ')' }, { transform: 'scaleX(' + toS + ')' }],
            { duration: dur, easing: 'cubic-bezier(.2,.8,.2,1)', fill: 'forwards' }
          );
          return true;
        } catch (e) {
          return false;
        }
      }

      // Add a brief sweep/glow when we hit the cap or roll over.
      try {
        if ($barWrap) {
          var hitCap = (toVal >= 99.8) || (toVal < fromVal);
          if (hitCap) {
            $barWrap.classList.remove('oa-bar-sweep');
            try { $barWrap.offsetWidth; } catch (e) {}
            $barWrap.classList.add('oa-bar-sweep');
          }
        }
      } catch (e) {}

      if (toVal < fromVal) {
        // Rollover case (leveled up): fill to 100%, reset, then animate to new progress.
        // Prefer WAAPI when available (prevents "no animation" in some webviews).
        if (!waAnimate(fromScale, 1, 520)) {
          requestAnimationFrame(function () {
            if ($bar._oaAnimId !== myBarAnimId) return;
            $bar.style.transform = 'scaleX(1)';
          });
        }

        var t1 = setTimeout(function () {
          if ($bar._oaAnimId !== myBarAnimId) return;
          var prevTransition = $bar.style.transition;
          $bar.style.transition = 'none';
          $bar.style.transform = 'scaleX(0)';
          try { $bar.offsetWidth; } catch (e) {}
          $bar.style.transition = prevTransition || '';
          var t2 = setTimeout(function () {
            if ($bar._oaAnimId !== myBarAnimId) return;
            if (!waAnimate(0, toScale, 680)) {
              requestAnimationFrame(function () {
                if ($bar._oaAnimId !== myBarAnimId) return;
                $bar.style.transform = 'scaleX(' + toScale + ')';
              });
            }
          }, 30);
          $bar._oaTimers.push(t2);
        }, 420);
        $bar._oaTimers.push(t1);
      } else {
        // Normal accumulation: animate from previous progress to new progress.
        if (!waAnimate(fromScale, toScale, 720)) {
          requestAnimationFrame(function () {
            if ($bar._oaAnimId !== myBarAnimId) return;
            $bar.style.transform = 'scaleX(' + toScale + ')';
          });
        }
      }
    }

    if ($btn) {
      $btn.href = getBaseUrl() + '/modules/overflowachievement/achievements';
    }

    setToastThemeClass(toast, safeItem);

    // Celebration (big moments only)
    if (shouldCelebrate(safeItem)) {
      if (window.OVERFLOWACHIEVEMENT_EFFECT === 'fireworks') fireworksBurst();
      else confettiBurst();
    }
  } catch (e) {
    // Never let a rendering bug produce an empty toast.
    try { if ($btn) $btn.href = getBaseUrl() + '/modules/overflowachievement/achievements'; } catch (e2) {}
    try { setToastThemeClass(toast, safeItem); } catch (e3) {}
  }
}

function currentDurationMs() {
  // Default 10s
  var duration = 10000;
  if (window.OVERFLOWACHIEVEMENT_TOAST_STICKY === true) return 0;
  if (typeof window.OVERFLOWACHIEVEMENT_TOAST_DURATION !== 'undefined') {
    var d = parseInt(window.OVERFLOWACHIEVEMENT_TOAST_DURATION, 10);
    if (!isNaN(d) && d > 0) duration = d;
  }
  return duration;
}

function toastCountText(item, queuedLeft) {
  var parts = [];
  try {
    if (item && item.is_batch && item.batch_items && item.batch_items.length) {
      parts.push(String(item.batch_items.length));
    }
  } catch (e) {}
  try {
    if (queuedLeft && queuedLeft > 0) {
      parts.push('+' + String(queuedLeft) + ' ' + t('queued', 'queued'));
    }
  } catch (e2) {}
  return parts.join(' • ');
}


function showNextToastStacked() {
  if (!_oaToastQueue.length) return;
  var next = _oaToastQueue.shift();
  var toast = createToastNodeForStack(next);

  var prevStat = (next && next.prevStat) ? next.prevStat : (next && next.stat ? next.stat : null);
  updateToastContent(toast, next.item, next.stat, prevStat);

  // Optional audio feedback (kept lightweight; never throws).
  try { playUnlockSound(next && next.item ? next.item : null); } catch (e) {}

  // Count indicator: remaining queued beyond the visible stack.
  try {
    var left = _oaToastQueue.length;
    var cEl = toast.querySelector('[data-oa-slot="count"]');
    if (cEl) cEl.textContent = toastCountText(next && next.item ? next.item : null, left);
  } catch (e) {}

  var duration = currentDurationMs();
  if (toast._oaTimer) { clearTimeout(toast._oaTimer); toast._oaTimer = null; }
  if (duration > 0) {
    toast._oaTimer = setTimeout(function () {
      var idsToMark = (next && next.ids && next.ids.length) ? next.ids : (next && next.item && typeof next.item.id !== 'undefined' ? [next.item.id] : []);
      if (idsToMark.length) {
        idsToMark.forEach(function (id) { removePendingById(id); });
        if (next && next.item && typeof next.item.id === 'string' && next.item.id.indexOf('batch_') === 0) {
          removePendingById(next.item.id);
        }
        markSeenIds(idsToMark);
      }
      closeToast(toast, function () {
        _oaActiveToasts = Math.max(0, _oaActiveToasts - 1);
        processToastQueue();
      });
    }, duration);
  }
}

function showNextToastInSequence() {
  if (_oaToastAnimating) return;
  if (maxVisibleToasts() > 1) {
    // Stacked mode: each toast is its own DOM node; no singleton updates.
    showNextToastStacked();
    return;
  }
  if (!_oaToastQueue.length) {
    // Nothing left; close if non-sticky.
    var dur = currentDurationMs();
    if (dur === 0) return;
    if (_oaToastSingleton && _oaToastSingleton._oaTimer) {
      clearTimeout(_oaToastSingleton._oaTimer);
      _oaToastSingleton._oaTimer = null;
    }
    // Give a tiny grace period; then close.
    if (_oaToastSingleton) {
      _oaToastSingleton._oaTimer = setTimeout(function () {
        closeToast(_oaToastSingleton, function () {
          _oaToastSingleton = null;
          _oaToastCurrent = null;
        });
      }, Math.max(800, Math.min(1800, dur)));
    }
    return;
  }

  _oaToastAnimating = true;
  var next = _oaToastQueue.shift();
  var toast = ensureToastSingleton();

  // Determine prev stat snapshot for animation.
  // Prefer the "before" snapshot captured at poll-time (next.prevStat), otherwise
  // fall back to the last displayed toast stat.
  var prevStat = (next && next.prevStat) ? next.prevStat
    : (_oaToastCurrent ? _oaToastCurrent.stat : (next && next.stat ? next.stat : null));
  _oaToastCurrent = { item: next.item, stat: next.stat, ids: next.ids || [] };

  // If leveling happened between prevStat and next.stat, show a separate level-up toast.
  try { showLevelToast(prevStat, next.stat); } catch (e) {}

  updateToastContent(toast, next.item, next.stat, prevStat);

  // Optional audio feedback.
  try { playUnlockSound(next && next.item ? next.item : null); } catch (e0) {}

  // Queue indicator: helps users understand bursts without stacking multiple cards.
  try {
    var left = _oaToastQueue.length;
    var cEl = toast.querySelector('[data-oa-slot="count"]');
    if (cEl) {
      cEl.textContent = toastCountText(next && next.item ? next.item : null, left);
    }
  } catch (e) {}

  // Reset timer for this item
  if (toast._oaTimer) { clearTimeout(toast._oaTimer); toast._oaTimer = null; }

  var duration = currentDurationMs();
  if (duration > 0) {
    toast._oaTimer = setTimeout(function () {
      // Mark this item seen + remove from pending, then advance.
      var idsToMark = (next && next.ids && next.ids.length) ? next.ids : (next && next.item && typeof next.item.id !== 'undefined' ? [next.item.id] : []);
      if (idsToMark.length) {
        idsToMark.forEach(function (id) { removePendingById(id); });
        // Server-batched pending records use a synthetic string id (e.g. "batch_...").
        if (next && next.item && typeof next.item.id === 'string' && next.item.id.indexOf('batch_') === 0) {
          removePendingById(next.item.id);
        }
        markSeenIds(idsToMark);
      }
      _oaActiveToasts = Math.max(0, _oaActiveToasts - 1);
      _oaToastAnimating = false;
      showNextToastInSequence();
    }, duration);
  } else {
    // Sticky: still allow queue to advance automatically after a short animation delay
    setTimeout(function () {
      // Do not mark seen until dismissed or advanced by duration (sticky has no duration),
      // but we *do* advance through queue to keep the "XP climbing" feeling.
      var idsToMark2 = (next && next.ids && next.ids.length) ? next.ids : (next && next.item && typeof next.item.id !== 'undefined' ? [next.item.id] : []);
      if (idsToMark2.length) {
        idsToMark2.forEach(function (id) { removePendingById(id); });
        if (next && next.item && typeof next.item.id === 'string' && next.item.id.indexOf('batch_') === 0) {
          removePendingById(next.item.id);
        }
        markSeenIds(idsToMark2);
      }
      _oaActiveToasts = Math.max(0, _oaActiveToasts - 1);
      _oaToastAnimating = false;
      showNextToastInSequence();
    }, 850);
  }

  // Allow another update soon (so a burst of unlocks feels continuous)
  setTimeout(function () { _oaToastAnimating = false; }, 80);
}

function processToastQueue() {
  var limit = maxVisibleToasts(); // still 1
  while (_oaActiveToasts < limit && _oaToastQueue.length) {
    _oaActiveToasts++;
    showNextToastInSequence();
    if (limit === 1) break;
  }
}

function enqueueToast(item, stat, ids) {
  // Backwards compatible signature: enqueueToast(item, stat, ids, prevStat)
  var prevStat = arguments.length >= 4 ? arguments[3] : null;
  _oaToastQueue.push({ item: item, stat: stat, prevStat: prevStat, ids: (ids && ids.length ? ids : (item && typeof item.id !== 'undefined' ? [item.id] : [])) });
  processToastQueue();
}

function enqueueFromPending(stat, prevStat) {
    var pending = readPending();
    if (!pending.length) return;

    var items = [];
    var st = stat || null;

    for (var i = 0; i < pending.length; i++) {
      var it = pending[i];

      // Server-side batch: enqueue directly so IDs remain intact.
      if (it && it.item && it.ids && it.ids.length) {
        // Ensure the pending record can be removed by id after dismissal.
        var bi = it.item;
        try {
          bi = Object.assign({}, it.item);
          bi.id = it.id;
        } catch (e) {}
        enqueueToast(bi, it.stat || st, it.ids, prevStat);
        continue;
      }

      if (it && it.item) {
        items.push(it.item);
        if (it.stat) st = it.stat; // keep last snapshot
      } else if (it) {
        items.push(it);
      }
    }

    // Use batch buffer to group bursts into one toast.
    addToBatch(items, st, prevStat);
  }


// Toast batching: group bursts of unlocks into a single toast for smoother UX.
var _oaBatch = { items: [], ids: [], stat: null, prevStat: null, timer: null };

function flushBatch() {
  if (_oaBatch.timer) { clearTimeout(_oaBatch.timer); _oaBatch.timer = null; }
  if (!_oaBatch.items.length) return;

  if (_oaBatch.items.length === 1) {
    enqueueToast(_oaBatch.items[0], _oaBatch.stat, _oaBatch.ids, _oaBatch.prevStat);
  } else {
    // Create a synthetic "batch" item.
    var count = _oaBatch.items.length;
    var bestR = 'common';
    var rank = { common: 1, rare: 2, epic: 3, legendary: 4 };
    for (var i = 0; i < _oaBatch.items.length; i++) {
      var r = String((_oaBatch.items[i] && _oaBatch.items[i].rarity) ? _oaBatch.items[i].rarity : 'common');
      if ((rank[r] || 1) > (rank[bestR] || 1)) bestR = r;
    }
    var batchItem = {
      is_batch: true,
      rarity: bestR,
      title: t('achievements_count', '+' + count + ' achievements', { count: count }),
      batch_items: _oaBatch.items
    };
    enqueueToast(batchItem, _oaBatch.stat, _oaBatch.ids, _oaBatch.prevStat);
  }

  _oaBatch.items = [];
  _oaBatch.ids = [];
  _oaBatch.stat = null;
  _oaBatch.prevStat = null;
}

function addToBatch(items, stat, prevStat) {
  if (!items || !items.length) return;
  // Merge into batch
  for (var i = 0; i < items.length; i++) {
    _oaBatch.items.push(items[i]);
    if (typeof items[i].id !== 'undefined') _oaBatch.ids.push(items[i].id);
  }
  _oaBatch.stat = stat || _oaBatch.stat;
  // Preserve the "before" snapshot for smooth progress animation.
  if (!_oaBatch.prevStat && prevStat) {
    _oaBatch.prevStat = prevStat;
  }

  // Flush after a short window so quick bursts group nicely.
  if (_oaBatch.timer) clearTimeout(_oaBatch.timer);
  _oaBatch.timer = setTimeout(flushBatch, 900);
}

  function fetchUnseen() {
    if (typeof fsAjax !== 'function') return;
    if (window.__oa_polling_disabled) return;

    // Do not poll when not logged in.
    if (!getAuthUserId()) return;

    if (window.__oa_fetching_unseen) return;
    window.__oa_fetching_unseen = true;

    var base = getBaseUrl();
    var unseenUrl = (window.OVERFLOWACHIEVEMENT_UNSEEN_URL) ? String(window.OVERFLOWACHIEVEMENT_UNSEEN_URL) : (base + '/modules/overflowachievement/unseen');

    // Request server-side batching when supported.
    fsAjax({ batch: 1 }, unseenUrl, function (resp) {
      window.__oa_fetching_unseen = false;

      // Apply UI config even if there are no items.
      if (resp && resp.ok && resp.ui) {
        applyUiConfig(resp.ui);
        writeUiCache(resp.ui);
      }

      // Keep an always-up-to-date stat snapshot (even when there are no items)
      // so the next burst can animate from the correct starting point.
      var stat = resp && resp.stat ? resp.stat : null;
      var prevFromServer = resp && resp.prev_stat ? resp.prev_stat : null;
      // Adaptive polling: when idle, back off; when achievements arrive, speed up.
      if (!resp || !resp.ok) {
        oaPollBackoff();
        if (stat) { _oaLastKnownStat = stat; writeLastStatCache(stat); }
        return;
      }

      // Server batch mode: single item with explicit ids.
      if (resp.item && resp.ids && resp.ids.length) {
        oaPollActive();
        var bid = 'batch_' + String(resp.ids.join('_'));
        upsertPending([{ id: bid, item: resp.item, stat: stat, ids: resp.ids }]);
        enqueueFromPending(stat, _oaLastKnownStat || prevFromServer);
        if (stat) { _oaLastKnownStat = stat; writeLastStatCache(stat); }
        return;
      }

      if (!resp.items || !resp.items.length) {
        oaPollBackoff();
        if (stat) { _oaLastKnownStat = stat; writeLastStatCache(stat); }
        return;
      }

      oaPollActive();

      // "Before" snapshot for smooth animations.
      var prevStatSnapshot = _oaLastKnownStat || prevFromServer;
      // Store rich payload so content (meta/progress) survives reload.
      var packed = [];
      for (var i = 0; i < resp.items.length; i++) {
        var item = resp.items[i];
        if (!item || typeof item.id === 'undefined') continue;
        // Prefer per-item snapshot when provided (better progression when multiple achievements arrive).
        var st = item.stat ? item.stat : stat;
        packed.push({ id: item.id, item: item, stat: st });
      }
      upsertPending(packed);
      enqueueFromPending(stat, prevStatSnapshot);

      // Update last-known snapshot after enqueuing, so the "before" state is preserved.
      if (stat) { _oaLastKnownStat = stat; writeLastStatCache(stat); }
    }, true, function (xhr) {
      window.__oa_fetching_unseen = false;
      var st = xhr && xhr.status ? xhr.status : 0;
      // Stop permanently only when the route is missing or user is logged out.
      if (st === 401 || st === 404) {
        window.__oa_polling_disabled = true;
      }
      oaPollBackoff();
      // 403/419 can happen transiently during session/CSRF refresh.
      // We keep polling enabled; next navigation will retry naturally.
    }, { method: 'post' });
  }

  // --- Adaptive polling loop (keeps background chatter low) ---
  var _oaPollTimer = null;
  var _oaPollDelay = 18000;
  var _oaPollMin = 8000;
  var _oaPollMax = 120000;

  function oaScheduleNextPoll() {
    if (window.__oa_polling_disabled) return;
    if (_oaPollTimer) clearTimeout(_oaPollTimer);
    _oaPollTimer = setTimeout(function () {
      fetchUnseen();
      oaScheduleNextPoll();
    }, _oaPollDelay);
  }

  function oaPollActive() {
    _oaPollDelay = _oaPollMin;
    oaScheduleNextPoll();
  }

  function oaPollBackoff() {
    _oaPollDelay = Math.min(_oaPollMax, Math.round(_oaPollDelay * 1.35));
    oaScheduleNextPoll();
  }

  // --- UI helpers for settings and trophy grid (delegated, jQuery-first) --- for settings and trophy grid (delegated, jQuery-first) ---
  function bindDelegatedUi() {
    if (!window.jQuery) return;
    var $ = window.jQuery;

    // Settings tabs (do not rely on Bootstrap tab JS; FreeScout themes sometimes override it)
    $(document).off('click.oaTabs').on('click.oaTabs', '.oa-settings .nav-tabs a', function (e) {
      var href = $(this).attr('href') || '';
      if (href.indexOf('#oa-tab-') !== 0) return;
      e.preventDefault();
      var $wrap = $(this).closest('.oa-settings');
      if (!$wrap.length) return;
      // Some themes rely on Bootstrap's .active, while our CSS uses .oa-active for safety.
      // Set BOTH to avoid tabs breaking (and in particular, avoid forms losing their inputs).
      $wrap.find('.nav-tabs li').removeClass('active oa-active');
      $(this).parent('li').addClass('active oa-active');
      $wrap.find('.tab-content .tab-pane').removeClass('active oa-active');
      $wrap.find(href).addClass('active oa-active');
    });

    // Trophy grid filters (works even when page is injected via AJAX).
    $(document).off('click.oaFilter').on('click.oaFilter', '.oa-filter-btn', function (e) {
      e.preventDefault();
      var filter = $(this).data('oaFilter') || 'all';
      $('.oa-filter-btn').removeClass('active');
      $(this).addClass('active');
      $('#oa-trophy-grid .oa-card').each(function () {
        var st = $(this).data('oaState');
        if (filter === 'all' || String(st) === String(filter)) {
          $(this).show();
        } else {
          $(this).hide();
        }
      });
    });

    // Icon pack toggle
    $(document).off('click.oaIconPackToggle').on('click.oaIconPackToggle', '.oa-icon-pack-toggle', function (e) {
      e.preventDefault();
      var target = $(this).data('oaTarget');
      if (!target) return;
      $('#' + target).toggle();
    });

    // Icon choice (applies within the nearest form scope)
    $(document).off('click.oaIconChoice').on('click.oaIconChoice', '.oa-icon-choice', function (e) {
      e.preventDefault();
      var url = $(this).data('oaUrl');
      if (!url) return;
      var $form = $(this).closest('form');
      if (!$form.length) return;

      $form.find('input[name="achievement[icon_type]"]').val('img');
      $form.find('input[name="achievement[icon_value]"]').val(url);

      // Update preview if present
      var $slot = $form.find('.oa-icon-preview-slot');
      if ($slot.length) {
        $slot.html('<img class="oa-icon-img" alt="" src="' + escapeHtml(url) + '">');
      }
    });

    // Quote picker: search + preview.
    $(document).off('input.oaQuoteSearch').on('input.oaQuoteSearch', '.oa-quote-search', function () {
        var targetId = $(this).data('oa-target');
        if (!targetId) return;
        var $sel = $('#' + targetId);
        if (!$sel.length) return;
        var q = ('' + ($(this).val() || '')).toLowerCase().trim();

        $sel.find('option').each(function () {
            // Always keep the auto option visible.
            if (!this.value) {
                this.hidden = false;
                return;
            }
            if (!q) {
                this.hidden = false;
                return;
            }
            var t = ('' + ($(this).text() || '')).toLowerCase();
            this.hidden = (t.indexOf(q) === -1);
        });

        // If an optgroup becomes empty, disable it (some browsers ignore optgroup display:none).
        $sel.find('optgroup').each(function () {
            var any = false;
            $(this).find('option').each(function () {
                if (!this.hidden) any = true;
            });
            $(this).prop('disabled', !any);
        });
    });

    $(document).off('change.oaQuoteSelect').on('change.oaQuoteSelect', '.oa-quote-select', function () {
        var previewSel = $(this).data('oa-preview');
        if (!previewSel) return;
        var $p = $(previewSel);
        if (!$p.length) return;

        var val = $(this).val();
        if (!val) {
            $p.text('Auto: a unique quote will be assigned.');
            return;
        }

        var $opt = $(this).find('option:selected');
        var txt = $opt.data('oa-text') || '';
        if (!txt) {
            $p.text('');
            return;
        }
        $p.text('“' + txt + '”');
    });

    // Initialize previews on load.
    $('.oa-quote-select').trigger('change');

    // Settings diagnostic: ping the module health endpoint.
    $(document).off('click.oaHealth').on('click.oaHealth', '.oa-health-check', function (e) {
      e.preventDefault();
      if (typeof fsAjax !== 'function') return;
      var base = getBaseUrl();
      var url = base + '/modules/overflowachievement/health';
      var $btn = $(this);
      var $out = $('.oa-health-output');
      $btn.prop('disabled', true);
      $out.text('Checking…');
      fsAjax({}, url, function (resp) {
        $btn.prop('disabled', false);
        if (resp && resp.ok) {
          $out.text('OK ✓ (user #' + resp.user_id + ')');
        } else {
          var reason = resp && resp.reason ? resp.reason : 'unreachable';
          $out.text('Not OK ✕ (' + reason + ')');
        }
      }, true, function (xhr) {
        $btn.prop('disabled', false);
        var st = xhr && xhr.status ? xhr.status : 0;
        $out.text('Error ✕ (HTTP ' + st + ')');
      }, { method: 'get' });
    });
    // Live preview (no server): apply theme/effect instantly and show a demo toast.
    function applyThemeAndEffect($scope) {
      var theme = String($scope.find('select[name="settings[overflowachievement.ui.toast_theme]"]').val() || 'neon');
      document.body.setAttribute('data-oa-theme', theme);
      document.body.className = document.body.className.replace(/\\boa-theme-[a-z0-9_-]+\\b/g, '');
      document.body.className += ' oa-theme-' + theme;

      var effect = String($scope.find('select[name="settings[overflowachievement.ui.effect]"]').val() || 'confetti');
      var compat = $scope.find('input[name="settings[overflowachievement.ui.confetti]"]').is(':checked');
      window.OVERFLOWACHIEVEMENT_EFFECT = compat ? effect : 'off';
    }

    function clearToastQueueAndActive() {
      _oaToastQueue = [];
      _oaActiveToasts = 0;
      var wrap = document.querySelector('.oa-toast-wrap');
      if (wrap) {
        while (wrap.firstChild) {
          try { wrap.removeChild(wrap.firstChild); } catch (e) { break; }
        }
      }
    }

    function demoToast() {
      clearToastQueueAndActive();
      var base = getBaseUrl();
      var demo = {
        id: 'demo',
        title: t('preview_title', 'Achievement Preview'),
        rarity: 'rare',
        quote_text: 'Small wins, stacked, become gravity.',
        quote_author: 'Overflow Achievement',
        icon_type: 'img',
        icon_value: base + '/modules/overflowachievement/icons/pack/icon_007.png',
        is_level_up: true
      };
      var stat = { level: 12, xp_total: 4200, progress: 62, cur_min: 3500, next_min: 5000 };
      enqueueToast(demo, stat);
    }

    // Settings: Advanced toggles (progressive enhancement)
    $(document).off('click.oaAdvanced').on('click.oaAdvanced', '.oa-advanced-toggle', function(e){
      try {
        var key = $(this).attr('data-oa-advanced');
        if (!key) return;
        var panel = $('[data-oa-advanced-panel="' + key + '"]');
        if (!panel.length) return;
        var open = panel.hasClass('oa-open');
        panel.toggleClass('oa-open', !open);
        var icon = $(this).find('.glyphicon').first();
        if (icon.length) {
          icon.toggleClass('glyphicon-chevron-right', open);
          icon.toggleClass('glyphicon-chevron-down', !open);
        }
      } catch(err) {}
    });


    // Single preview button (local). Keeps Settings clean and avoids relying on server routes.
    $(document).off('click.oaPreviewToast').on('click.oaPreviewToast', '.oa-preview-toast', function (e) {
      e.preventDefault();
      var $scope = $(this).closest('form');
      applyThemeAndEffect($scope);
      demoToast();
    });
  }

  // --- Settings tab persistence (stay on the same tab after saving) ---
  var OA_SETTINGS_TAB_KEY = 'overflowachievement_settings_tab_v1';
  function getQueryParam(name) {
    try {
      var s = window.location.search || '';
      if (!s) return null;
      var p = new URLSearchParams(s);
      return p.get(name);
    } catch (e) {
      return null;
    }
  }
  function setQueryParam(url, key, value) {
    try {
      var u = new URL(url, window.location.origin);
      u.searchParams.set(key, value);
      return u.pathname + '?' + u.searchParams.toString();
    } catch (e) {
      return url;
    }
  }
  function settingsTabPersistInit() {
    if (!document.querySelector('.oa-settings')) return;
    if (!storageAvailable()) return;

    function normalizeTab(t) {
      if (!t) return null;
      return String(t).replace(/^#?oa-tab-/, '').trim();
    }

    function activateDomTab(tabName) {
      tabName = normalizeTab(tabName);
      if (!tabName) return;
      var wrap = document.querySelector('.oa-settings');
      if (!wrap) return;

      var panes = wrap.querySelectorAll('.tab-content > .tab-pane');
      var lis = wrap.querySelectorAll('.nav-tabs > li');
      for (var i = 0; i < panes.length; i++) { panes[i].classList.remove('oa-active'); panes[i].classList.remove('active'); }
      for (var j = 0; j < lis.length; j++) { lis[j].classList.remove('oa-active'); lis[j].classList.remove('active'); }

      var pane = wrap.querySelector('#oa-tab-' + tabName);
      if (pane) { pane.classList.add('oa-active'); pane.classList.add('active'); }

      var link = wrap.querySelector('.nav-tabs a[href="#oa-tab-' + tabName + '"]');
      if (link && link.parentElement) { link.parentElement.classList.add('oa-active'); link.parentElement.classList.add('active'); }
    }

    // Prefer explicit query param, otherwise restore last tab without redirecting.
    var tab = normalizeTab(getQueryParam('tab'));
    if (tab) {
      try { window.sessionStorage.setItem(OA_SETTINGS_TAB_KEY, tab); } catch (e1) {}
      activateDomTab(tab);
    } else {
      var remembered = null;
      try { remembered = window.sessionStorage.getItem(OA_SETTINGS_TAB_KEY); } catch (e2) {}
      if (remembered) activateDomTab(remembered);
    }

    // Store when clicking hash-based tabs.
    document.addEventListener('click', function (e) {
      var a = e.target && e.target.closest ? e.target.closest('.oa-settings .nav-tabs a[href^="#oa-tab-"]') : null;
      if (!a) return;
      var href = a.getAttribute('href') || '';
      var t = normalizeTab(href);
      if (t) {
        try { window.sessionStorage.setItem(OA_SETTINGS_TAB_KEY, t); } catch (err) {}
      }
    }, true);

    // Store on submit (read current visible tab/pane).
    document.addEventListener('submit', function (e) {
      var form = e.target;
      if (!form || !form.closest || !form.closest('.oa-settings')) return;
      var cur = null;
      var li = document.querySelector('.oa-settings .nav-tabs > li.oa-active, .oa-settings .nav-tabs > li.active');
      if (li) {
        var a2 = li.querySelector('a[href^="#oa-tab-"]');
        if (a2) cur = normalizeTab(a2.getAttribute('href'));
      }
      if (!cur) {
        var pane2 = document.querySelector('.oa-settings .tab-content > .tab-pane.oa-active, .oa-settings .tab-content > .tab-pane.active');
        if (pane2 && pane2.id) cur = normalizeTab(pane2.id);
      }
      if (cur) {
        try { window.sessionStorage.setItem(OA_SETTINGS_TAB_KEY, cur); } catch (err2) {}
      }
    }, true);
  }

  // Build mailbox quote rules JSON from the Quotes settings table.
  function mailboxQuotesFormInit() {
    if (!window.jQuery) return;
    var $ = window.jQuery;
    $(document).off('submit.oaMailboxQuotes').on('submit.oaMailboxQuotes', 'form.oa-quotes-form', function () {
      var $form = $(this);
      var $json = $form.find('#oa-mailbox-quotes-json');
      if (!$json.length) return;

      var rules = {};
      $form.find('.oa-mailbox-quote-row').each(function () {
        var $row = $(this);
        var mbId = String($row.data('mailbox-id') || '').trim();
        if (!mbId) return;

        var tones = [];
        $row.find('input.oa-mailbox-tone:checked').each(function () {
          var v = String($(this).val() || '').trim();
          if (v) tones.push(v);
        });

        var limitRaw = String($row.find('input.oa-mailbox-limit').val() || '').trim();
        var limit = parseInt(limitRaw, 10);
        if (isNaN(limit) || limit < 0) limit = 0;

        if (tones.length || limit > 0) {
          rules[mbId] = { tones: tones, limit: limit };
        }
      });

      try {
        $json.val(Object.keys(rules).length ? JSON.stringify(rules) : '');
      } catch (e) {
        $json.val('');
      }
    });
  }

  function init() {
    initUserInteractionFlag();
    // Always attempt to fetch unseen unlocks; server will respond ok=false if disabled/not installed.
    if (typeof window.OVERFLOWACHIEVEMENT_EFFECT === 'undefined') { window.OVERFLOWACHIEVEMENT_EFFECT = 'confetti'; }
    // Apply current (vars.js) + cached UI settings immediately so toasts rendered from pending use correct duration/theme.
    if (window.OVERFLOWACHIEVEMENT_UI) {
      applyUiConfig(window.OVERFLOWACHIEVEMENT_UI);
      writeUiCache(window.OVERFLOWACHIEVEMENT_UI);
    }
    applyUiConfig(readUiCache());
    bindDelegatedUi();
    settingsTabPersistInit();
    mailboxQuotesFormInit();
    oaInitAchievementModal();
    // Ensure broken icon pack images never create empty UI placeholders.
    bindIconFallback(document);
    var enabled = (typeof window.OVERFLOWACHIEVEMENT_ENABLED === 'undefined') ? true : !!window.OVERFLOWACHIEVEMENT_ENABLED;
    if (!enabled) {
      return;
    }

    enqueueFromPending(null);
    setTimeout(fetchUnseen, 700);
    // Start background polling (adaptive backoff).
    oaScheduleNextPoll();

    // Re-check unseen after AJAX activity without touching FreeScout's ajaxFinish lifecycle.
    if (window.jQuery && !window.__oa_ajaxcomplete_bound) {
      window.__oa_ajaxcomplete_bound = true;
      var $ = window.jQuery;
      var t = null;
      $(document).on('ajaxComplete.overflowachievement', function (_ev, _xhr, settings) {
        try {
          var url = settings && settings.url ? String(settings.url) : '';
          if (url.indexOf('/modules/overflowachievement/unseen') !== -1) return;
          if (url.indexOf('/modules/overflowachievement/mark-seen') !== -1) return;
        } catch (e) {}
        if (t) clearTimeout(t);
        // Recent activity: poll sooner.
        oaPollActive();
        t = setTimeout(fetchUnseen, 450);
      });
    }
  }


  // Achievement cabinet: details modal with progress
  function oaInitAchievementModal() {
    var grid = document.getElementById('oa-trophy-grid');
    var modal = document.getElementById('oa-ach-modal');
    if (!grid || !modal) return;

    function closeModal() {
      modal.style.display = 'none';
      modal.setAttribute('aria-hidden', 'true');
      try { document.body.classList.remove('oa-modal-open'); } catch (e) {}
    }

    function getSlot(sel) {
      try { return modal.querySelector(sel); } catch (e) { return null; }
    }

    function openFromCard(card) {
      if (!card) return;
      var iconType = (card.getAttribute('data-oa-icon-type') || 'fa').toLowerCase();
      var iconValue = card.getAttribute('data-oa-icon-value') || 'fa-trophy';
      var rarity = (card.getAttribute('data-oa-rarity') || 'common').toLowerCase();
      var title = card.getAttribute('data-oa-title') || '';
      var desc = card.getAttribute('data-oa-desc') || '';
      var trigger = card.getAttribute('data-oa-trigger') || '';
      var triggerLabel = card.getAttribute('data-oa-trigger-label') || trigger;
      var triggerHint = card.getAttribute('data-oa-trigger-hint') || '';
      var threshold = parseInt(card.getAttribute('data-oa-threshold') || '0', 10) || 0;
      var current = parseInt(card.getAttribute('data-oa-current') || '0', 10) || 0;
      var xp = parseInt(card.getAttribute('data-oa-xp') || '0', 10) || 0;
      var unlockedAt = card.getAttribute('data-oa-unlocked-at') || '';
      var isUnlocked = (card.getAttribute('data-oa-state') === 'unlocked');

      // Quotes are shown only for unlocked trophies.
      var quoteText = card.getAttribute('data-oa-quote') || '';
      var quoteAuthor = card.getAttribute('data-oa-quote-author') || '';

      var iconSlot = getSlot('[data-oa-m="icon"]');
      var raritySlot = getSlot('[data-oa-m="rarity"]');
      var titleSlot = getSlot('[data-oa-m="title"]');
      var descSlot = getSlot('[data-oa-m="desc"]');
      var hintSlot = getSlot('[data-oa-m="hint"]');
      var quoteSlot = getSlot('[data-oa-m="quote"]');
      var xpSlot = getSlot('[data-oa-m="xp"]');
      var unlockedSlot = getSlot('[data-oa-m="unlocked"]');
      var scopeSlot = getSlot('[data-oa-m="scope"]');
      var plSlot = getSlot('[data-oa-m="progressLabel"]');
      var pvSlot = getSlot('[data-oa-m="progressVal"]');
      var bar = getSlot('[data-oa-m="bar"]');

      // Optional: scope badge (Lifetime / Daily / Per conversation)
      var scope = '';
      try {
        var meta = window.OVERFLOWACHIEVEMENT_TRIGGERS || {};
        if (meta && meta.scopes && trigger && meta.scopes[trigger]) {
          scope = String(meta.scopes[trigger] || '');
        }
      } catch (eMeta) {}
      // Fallback heuristic so custom triggers don't look raw.
      if (!scope && trigger) {
        if (trigger.indexOf('daily') !== -1) scope = 'daily';
        else if (trigger.indexOf('streak') !== -1) scope = 'lifetime';
        else if (trigger.indexOf('conversation') !== -1 || trigger.indexOf('reply') !== -1 || trigger.indexOf('close') !== -1) scope = 'per_conversation';
      }

      if (iconSlot) {
        try { iconSlot.innerHTML = renderIconHtml(iconType, iconValue); } catch (e) { iconSlot.innerHTML = ''; }
        try { bindIconFallback(modal); } catch (e2) {}
      }
      if (raritySlot) raritySlot.textContent = String(rarity).toUpperCase();
      if (titleSlot) titleSlot.textContent = title;
      if (descSlot) descSlot.textContent = desc;
      if (hintSlot) {
        hintSlot.textContent = triggerHint || '';
        hintSlot.style.display = triggerHint ? 'block' : 'none';
      }
      if (xpSlot) xpSlot.textContent = (xp > 0) ? ('+' + xp + ' XP') : '';

      if (quoteSlot) {
        if (isUnlocked && quoteText) {
          // Build a single-line quote. Keep it safe and simple.
          var q = '“' + quoteText + '”';
          if (quoteAuthor) q += ' — ' + quoteAuthor;
          quoteSlot.textContent = q;
          quoteSlot.style.display = 'block';
        } else {
          quoteSlot.textContent = '';
          quoteSlot.style.display = 'none';
        }
      }

      if (unlockedSlot) {
        if (isUnlocked) {
          unlockedSlot.textContent = unlockedAt ? (t('unlocked', 'Unlocked') + ': ' + unlockedAt) : t('unlocked', 'Unlocked');
        } else {
          unlockedSlot.textContent = t('locked', 'Locked');
        }
      }

      if (scopeSlot) {
        var scopeLabel = '';
        try {
          var meta2 = window.OVERFLOWACHIEVEMENT_TRIGGERS || {};
          if (meta2 && meta2.scope_labels && scope && meta2.scope_labels[scope]) {
            scopeLabel = String(meta2.scope_labels[scope] || '');
          }
        } catch (eScope) {}
        // If no label map, use a sane fallback.
        if (!scopeLabel && scope) {
          if (scope === 'daily') scopeLabel = 'Daily';
          else if (scope === 'per_conversation') scopeLabel = 'Per conversation';
          else if (scope === 'lifetime') scopeLabel = 'Lifetime';
        }
        scopeSlot.textContent = scopeLabel;
        scopeSlot.style.display = scopeLabel ? 'inline-flex' : 'none';
      }

      if (plSlot) {
        plSlot.textContent = triggerLabel ? (triggerLabel + ' ≥ ' + threshold) : t('progress', 'Progress');
      }
      if (pvSlot) {
        pvSlot.textContent = (threshold > 0) ? (current + ' / ' + threshold) : String(current);
      }
      if (bar) {
        bar.style.width = '0%';
        var pct = (threshold > 0) ? Math.max(0, Math.min(100, Math.round((current * 100) / threshold))) : 100;
        setTimeout(function () { bar.style.width = pct + '%'; }, 20);
      }

      modal.style.display = 'flex';
      modal.setAttribute('aria-hidden', 'false');
      try { document.body.classList.add('oa-modal-open'); } catch (e2) {}
    }

    grid.addEventListener('click', function (e) {
      var card = e.target && e.target.closest ? e.target.closest('.oa-card') : null;
      if (!card) return;
      openFromCard(card);
    });

    grid.addEventListener('keydown', function (e) {
      if (e.key !== 'Enter' && e.key !== ' ') return;
      var card = e.target && e.target.closest ? e.target.closest('.oa-card') : null;
      if (!card) return;
      e.preventDefault();
      openFromCard(card);
    });

    modal.addEventListener('click', function (e) {
      if (e.target === modal) {
        closeModal();
        return;
      }
      var btn = e.target && e.target.closest ? e.target.closest('[data-oa-modal-close]') : null;
      if (btn) {
        closeModal();
      }
    });

    document.addEventListener('keydown', function (e) {
      if (e.key === 'Escape' && modal.style.display !== 'none') {
        closeModal();
      }
    });
  }
  document.addEventListener('DOMContentLoaded', init);
})();
