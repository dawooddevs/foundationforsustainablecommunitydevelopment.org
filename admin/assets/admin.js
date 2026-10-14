/* Website dashboard SPA (vanilla JS, AJAX-driven, no build step) */
(function () {
  'use strict';

  var A = window.APP_CONFIG;
  var app = document.getElementById('app');
  var S = { user: null, schema: null, csrf: A.csrf, dirty: false, lastHash: '', relCache: {}, newCount: 0 };

  /* =====================================================================
     Utilities
     ===================================================================== */
  var ICONS = {
    circle: '<circle cx="12" cy="12" r="9"/>',
    dashboard: '<rect width="7" height="9" x="3" y="3" rx="1"/><rect width="7" height="5" x="14" y="3" rx="1"/><rect width="7" height="9" x="14" y="12" rx="1"/><rect width="7" height="5" x="3" y="16" rx="1"/>',
    activity: '<path d="M22 12h-4l-3 9L9 3l-3 9H2"/>',
    users: '<path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M22 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/>',
    user: '<path d="M19 21v-2a4 4 0 0 0-4-4H9a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/>',
    'file-text': '<path d="M15 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V7Z"/><path d="M14 2v4a2 2 0 0 0 2 2h4"/><path d="M10 9H8"/><path d="M16 13H8"/><path d="M16 17H8"/>',
    file: '<path d="M15 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V7Z"/><path d="M14 2v4a2 2 0 0 0 2 2h4"/>',
    quote: '<path d="M3 21c3 0 7-1 7-8V5c0-1.25-.76-2-2-2H4c-1.25 0-2 .75-2 1.97V11c0 1.25.75 2 2 2 1 0 1 0 1 1v1c0 1-1 2-2 2s-1 0-1 1.03V20c0 1 0 1 1 1z"/><path d="M15 21c3 0 7-1 7-8V5c0-1.25-.76-2-2-2h-4c-1.25 0-2 .75-2 1.97V11c0 1.25.75 2 2 2h.75c0 2.25.25 4-2.75 4v3c0 1 0 1 1 1z"/>',
    'message-square': '<path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/>',
    'map-pin': '<path d="M20 10c0 6-8 12-8 12s-8-6-8-12a8 8 0 0 1 16 0Z"/><circle cx="12" cy="10" r="3"/>',
    route: '<circle cx="6" cy="19" r="3"/><path d="M9 19h8.5a3.5 3.5 0 0 0 0-7h-11a3.5 3.5 0 0 1 0-7H15"/><circle cx="18" cy="5" r="3"/>',
    image: '<rect width="18" height="18" x="3" y="3" rx="2"/><circle cx="9" cy="9" r="2"/><path d="m21 15-3.09-3.09a2 2 0 0 0-2.82 0L6 21"/>',
    inbox: '<path d="M22 12h-6l-2 3h-4l-2-3H2"/><path d="M5.45 5.11 2 12v6a2 2 0 0 0 2 2h16a2 2 0 0 0 2-2v-6l-3.45-6.89A2 2 0 0 0 16.76 4H7.24a2 2 0 0 0-1.79 1.11z"/>',
    settings: '<path d="M12.22 2h-.44a2 2 0 0 0-2 2v.18a2 2 0 0 1-1 1.73l-.43.25a2 2 0 0 1-2 0l-.15-.08a2 2 0 0 0-2.73.73l-.22.38a2 2 0 0 0 .73 2.73l.15.1a2 2 0 0 1 1 1.72v.51a2 2 0 0 1-1 1.74l-.15.09a2 2 0 0 0-.73 2.73l.22.38a2 2 0 0 0 2.73.73l.15-.08a2 2 0 0 1 2 0l.43.25a2 2 0 0 1 1 1.73V20a2 2 0 0 0 2 2h.44a2 2 0 0 0 2-2v-.18a2 2 0 0 1 1-1.73l.43-.25a2 2 0 0 1 2 0l.15.08a2 2 0 0 0 2.73-.73l.22-.39a2 2 0 0 0-.73-2.73l-.15-.08a2 2 0 0 1-1-1.74v-.5a2 2 0 0 1 1-1.74l.15-.09a2 2 0 0 0 .73-2.73l-.22-.38a2 2 0 0 0-2.73-.73l-.15.08a2 2 0 0 1-2 0l-.43-.25a2 2 0 0 1-1-1.73V4a2 2 0 0 0-2-2z"/><circle cx="12" cy="12" r="3"/>',
    'log-out': '<path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><path d="m16 17 5-5-5-5"/><path d="M21 12H9"/>',
    pencil: '<path d="M21.17 6.81a1 1 0 0 0-3.99-3.99L3.84 16.17a2 2 0 0 0-.5.83l-1.32 4.35a.5.5 0 0 0 .62.62l4.35-1.32a2 2 0 0 0 .83-.5z"/>',
    trash: '<path d="M3 6h18"/><path d="M19 6v14c0 1-1 2-2 2H7c-1 0-2-1-2-2V6"/><path d="M8 6V4c0-1 1-2 2-2h4c1 0 2 1 2 2v2"/>',
    copy: '<rect width="14" height="14" x="8" y="8" rx="2"/><path d="M4 16c-1.1 0-2-.9-2-2V4c0-1.1.9-2 2-2h10c1.1 0 2 .9 2 2"/>',
    plus: '<path d="M5 12h14"/><path d="M12 5v14"/>',
    minus: '<path d="M5 12h14"/>',
    search: '<circle cx="11" cy="11" r="8"/><path d="m21 21-4.3-4.3"/>',
    x: '<path d="M18 6 6 18"/><path d="m6 6 12 12"/>',
    check: '<path d="M20 6 9 17l-5-5"/>',
    'check-circle': '<circle cx="12" cy="12" r="10"/><path d="m9 12 2 2 4-4"/>',
    'chevron-down': '<path d="m6 9 6 6 6-6"/>',
    'chevron-up': '<path d="m18 15-6-6-6 6"/>',
    'chevron-right': '<path d="m9 18 6-6-6-6"/>',
    'arrow-left': '<path d="m12 19-7-7 7-7"/><path d="M19 12H5"/>',
    'arrow-right': '<path d="M5 12h14"/><path d="m12 5 7 7-7 7"/>',
    upload: '<path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><path d="m17 8-5-5-5 5"/><path d="M12 3v12"/>',
    download: '<path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><path d="m7 10 5 5 5-5"/><path d="M12 15V3"/>',
    eye: '<path d="M2 12s3-7 10-7 10 7 10 7-3 7-10 7-10-7-10-7Z"/><circle cx="12" cy="12" r="3"/>',
    external: '<path d="M15 3h6v6"/><path d="M10 14 21 3"/><path d="M18 13v6a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h6"/>',
    moon: '<path d="M12 3a6 6 0 0 0 9 9 9 9 0 1 1-9-9Z"/>',
    sun: '<circle cx="12" cy="12" r="4"/><path d="M12 2v2"/><path d="M12 20v2"/><path d="m4.93 4.93 1.41 1.41"/><path d="m17.66 17.66 1.41 1.41"/><path d="M2 12h2"/><path d="M20 12h2"/><path d="m6.34 17.66-1.41 1.41"/><path d="m19.07 4.93-1.41 1.41"/>',
    menu: '<path d="M4 7h16"/><path d="M4 12h16"/><path d="M4 17h16"/>',
    grip: '<circle cx="9" cy="6" r="1"/><circle cx="9" cy="12" r="1"/><circle cx="9" cy="18" r="1"/><circle cx="15" cy="6" r="1"/><circle cx="15" cy="12" r="1"/><circle cx="15" cy="18" r="1"/>',
    alert: '<path d="m21.73 18-8-14a2 2 0 0 0-3.48 0l-8 14A2 2 0 0 0 4 21h16a2 2 0 0 0 1.73-3"/><path d="M12 9v4"/><path d="M12 17h.01"/>',
    info: '<circle cx="12" cy="12" r="10"/><path d="M12 16v-4"/><path d="M12 8h.01"/>',
    star: '<path d="M12 2.5l2.94 5.96 6.56.95-4.75 4.63 1.12 6.54L12 17.5l-5.87 3.08 1.12-6.54L2.5 9.41l6.56-.95z"/>',
    mail: '<rect width="20" height="16" x="2" y="4" rx="2"/><path d="m22 7-8.97 5.7a1.94 1.94 0 0 1-2.06 0L2 7"/>',
    phone: '<path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72 12.84 12.84 0 0 0 .7 2.81 2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45 12.84 12.84 0 0 0 2.81.7A2 2 0 0 1 22 16.92z"/>',
    refresh: '<path d="M3 12a9 9 0 0 1 9-9 9.75 9.75 0 0 1 6.74 2.74L21 8"/><path d="M21 3v5h-5"/><path d="M21 12a9 9 0 0 1-9 9 9.75 9.75 0 0 1-6.74-2.74L3 16"/><path d="M8 16H3v5"/>',
    'shield-check': '<path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/><path d="m9 12 2 2 4-4"/>',
    key: '<path d="m15.5 7.5 2.3 2.3a1 1 0 0 0 1.4 0l2.1-2.1a1 1 0 0 0 0-1.4L19 4"/><path d="m21 2-9.6 9.6"/><circle cx="7.5" cy="15.5" r="5.5"/>',
    database: '<ellipse cx="12" cy="5" rx="9" ry="3"/><path d="M3 5V19A9 3 0 0 0 21 19V5"/><path d="M3 12A9 3 0 0 0 21 12"/>',
    clock: '<circle cx="12" cy="12" r="10"/><path d="M12 6v6l4 2"/>',
    flag: '<path d="M4 15s1-1 4-1 5 2 8 2 4-1 4-1V3s-1 1-4 1-5-2-8-2-4 1-4 1z"/><path d="M4 22v-7"/>',
    video: '<path d="m16 13 5.22 3.48a.5.5 0 0 0 .78-.42V7.87a.5.5 0 0 0-.75-.43L16 10.5"/><rect x="2" y="6" width="14" height="12" rx="2"/>',
    trending: '<path d="m22 7-8.5 8.5-5-5L2 17"/><path d="M16 7h6v6"/>',
    sparkles: '<path d="M9.94 15.5A2 2 0 0 0 8.5 14.06l-6.14-1.58a.5.5 0 0 1 0-.96L8.5 9.94A2 2 0 0 0 9.94 8.5l1.58-6.14a.5.5 0 0 1 .96 0l1.58 6.14a2 2 0 0 0 1.44 1.44l6.14 1.58a.5.5 0 0 1 0 .96l-6.14 1.58a2 2 0 0 0-1.44 1.44l-1.58 6.14a.5.5 0 0 1-.96 0z"/>',
    building: '<path d="M6 22V4a2 2 0 0 1 2-2h8a2 2 0 0 1 2 2v18Z"/><path d="M6 12H4a2 2 0 0 0-2 2v6a2 2 0 0 0 2 2h2"/><path d="M18 9h2a2 2 0 0 1 2 2v9a2 2 0 0 1-2 2h-2"/><path d="M10 6h4"/><path d="M10 10h4"/><path d="M10 14h4"/><path d="M10 18h4"/>',
    'heart-pulse': '<path d="M19 14c1.49-1.46 3-3.21 3-5.5A5.5 5.5 0 0 0 16.5 3c-1.76 0-3 .5-4.5 2-1.5-1.5-2.74-2-4.5-2A5.5 5.5 0 0 0 2 8.5c0 2.3 1.5 4.05 3 5.5l7 7Z"/><path d="M3.22 12H9.5l.5-1 2 4.5 2-7 1.5 3.5h5.27"/>',
    zap: '<path d="M13 2 3 14h9l-1 8 10-12h-9l1-8z"/>',
    globe: '<circle cx="12" cy="12" r="10"/><path d="M12 2a14.5 14.5 0 0 0 0 20 14.5 14.5 0 0 0 0-20"/><path d="M2 12h20"/>',
    bold: '<path d="M6 12h9a4 4 0 0 1 0 8H7a1 1 0 0 1-1-1V5a1 1 0 0 1 1-1h7a4 4 0 0 1 0 8"/>',
    italic: '<path d="M19 4h-9"/><path d="M14 20H5"/><path d="M15 4 9 20"/>',
    underline: '<path d="M6 4v6a6 6 0 0 0 12 0V4"/><path d="M4 20h16"/>',
    'list-ul': '<path d="M8 6h13"/><path d="M8 12h13"/><path d="M8 18h13"/><path d="M3 6h.01"/><path d="M3 12h.01"/><path d="M3 18h.01"/>',
    'list-ol': '<path d="M10 6h11"/><path d="M10 12h11"/><path d="M10 18h11"/><path d="M4 6h1v4"/><path d="M4 10h2"/><path d="M6 18H4c0-1 2-2 2-3s-1-1.5-2-1"/>',
    link: '<path d="M10 13a5 5 0 0 0 7.54.54l3-3a5 5 0 0 0-7.07-7.07l-1.72 1.71"/><path d="M14 11a5 5 0 0 0-7.54-.54l-3 3a5 5 0 0 0 7.07 7.07l1.71-1.71"/>',
    unlink: '<path d="m18.84 12.25 1.72-1.71a5 5 0 0 0-7.07-7.07l-1.72 1.71"/><path d="m5.17 11.75-1.71 1.71a5 5 0 0 0 7.07 7.07l1.71-1.71"/><path d="M8 2v3"/><path d="M2 8h3"/><path d="M16 19v3"/><path d="M19 16h3"/>',
    undo: '<path d="M3 7v6h6"/><path d="M21 17a9 9 0 0 0-9-9 9 9 0 0 0-6 2.3L3 13"/>',
    redo: '<path d="M21 7v6h-6"/><path d="M3 17a9 9 0 0 1 9-9 9 9 0 0 1 6 2.3l3 2.7"/>',
    eraser: '<path d="m7 21-4.3-4.3c-1-1-1-2.5 0-3.4l9.6-9.6c1-1 2.5-1 3.4 0l5.6 5.6c1 1 1 2.5 0 3.4L13 21"/><path d="M22 21H7"/><path d="m5 11 9 9"/>',
    code: '<path d="m16 18 6-6-6-6"/><path d="m8 6-6 6 6 6"/>',
    archive: '<rect width="20" height="5" x="2" y="3" rx="1"/><path d="M4 8v11a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8"/><path d="M10 12h4"/>',
    search2: '<circle cx="11" cy="11" r="8"/><path d="m21 21-4.3-4.3"/>'
  };
  function ic(name, cls) {
    return '<svg class="' + (cls || 'i') + '" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">' + (ICONS[name] || ICONS.circle) + '</svg>';
  }
  function esc(s) {
    return String(s == null ? '' : s).replace(/[&<>"']/g, function (c) { return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]; });
  }
  function $(s, c) { return (c || document).querySelector(s); }
  function $$(s, c) { return Array.prototype.slice.call((c || document).querySelectorAll(s)); }
  function el(html) { var t = document.createElement('template'); t.innerHTML = html.trim(); return t.content.firstElementChild; }
  function can(cap) { var c = (S.user && S.user.caps) || []; return c.indexOf('*') > -1 || c.indexOf(cap) > -1; }
  function isAdmin() { return !!(S.user && S.user.role === 'admin'); }
  function shown(k) { var r = S.schema && S.schema.resources[k]; return !!r && !r.hidden; }
  // Field previews: bust the year-long image cache once per dashboard load so replaced files show
  var MEDIA_BUST = Date.now().toString(36);
  function mediaUrl(p) { if (!p) return ''; if (/^(https?:)?\/\//.test(p)) return p; p = String(p).replace(/^\/+/, ''); return A.base + p + (/^uploads\//.test(p) && p.indexOf('?') < 0 ? '?v=' + MEDIA_BUST : ''); }
  function debounce(fn, ms) { var t; return function () { var a = arguments, s = this; clearTimeout(t); t = setTimeout(function () { fn.apply(s, a); }, ms); }; }
  function slugify(s) { return String(s || '').toLowerCase().normalize('NFKD').replace(/[̀-ͯ]/g, '').replace(/[^a-z0-9]+/g, '-').replace(/^-+|-+$/g, ''); }
  function bytes(n) { n = +n || 0; if (n < 1024) return n + ' B'; if (n < 1048576) return (n / 1024).toFixed(0) + ' KB'; if (n < 1073741824) return (n / 1048576).toFixed(1) + ' MB'; return (n / 1073741824).toFixed(2) + ' GB'; }
  // Server timestamps are in the site timezone set in app/bootstrap.php (offset passed as A.tz)
  function parseDate(s) { if (!s) return null; s = String(s); return new Date(/[zZ]|[+-]\d\d:\d\d$/.test(s) ? s : s.replace(' ', 'T') + (A.tz || '+00:00')); }
  function ago(s) {
    var d = parseDate(s); if (!d || isNaN(d)) return '';
    var x = (Date.now() - d.getTime()) / 1000;
    if (x < 60) return 'just now';
    if (x < 3600) return Math.floor(x / 60) + 'm ago';
    if (x < 86400) return Math.floor(x / 3600) + 'h ago';
    if (x < 604800) return Math.floor(x / 86400) + 'd ago';
    return d.toLocaleDateString(undefined, { month: 'short', day: 'numeric', year: 'numeric' });
  }
  function fullDate(s) { var d = parseDate(s); return d && !isNaN(d) ? d.toLocaleString(undefined, { dateStyle: 'medium', timeStyle: 'short' }) : '—'; }
  function initials(n) { return String(n || '?').replace(/^Dr\.?\s+/i, '').split(/\s+/).map(function (w) { return w[0]; }).join('').slice(0, 2).toUpperCase(); }
  function avatar(u, lg) { return '<span class="avatar' + (lg ? ' avatar--lg' : '') + '">' + (u && u.avatar ? '<img src="' + esc(u.avatar) + '" alt="">' : esc(initials(u && (u.name || u.username)))) + '</span>'; }

  /* =====================================================================
     API layer — every call is AJAX
     ===================================================================== */
  function api(route, data, opts) {
    opts = opts || {};
    var method = opts.method || (data && !opts.get ? 'POST' : 'GET');
    var url = A.api + '?r=' + encodeURIComponent(route);
    var init = { method: method, headers: { Accept: 'application/json' }, credentials: 'same-origin' };
    if (method === 'GET' && data) {
      Object.keys(data).forEach(function (k) { if (data[k] !== '' && data[k] != null) url += '&' + encodeURIComponent(k) + '=' + encodeURIComponent(data[k]); });
    } else if (data instanceof FormData) {
      init.body = data;
    } else if (data) {
      init.body = JSON.stringify(data);
      init.headers['Content-Type'] = 'application/json';
    }
    if (method === 'POST') init.headers['X-CSRF-Token'] = S.csrf;
    return fetch(url, init).then(function (res) {
      return res.json().catch(function () { throw new Error('Unexpected server response (' + res.status + ').'); }).then(function (json) {
        if (res.status === 401 && route !== 'auth.login') { S.user = null; renderLogin('Your session expired. Please sign in again.'); }
        if (!json.ok) { var e = new Error(json.error || 'Request failed.'); e.fields = json.fields; e.status = res.status; throw e; }
        if (json.data && json.data.csrf) S.csrf = json.data.csrf;
        return json.data;
      });
    });
  }
  function get(route, params) { return api(route, params || {}, { get: true, method: 'GET' }); }

  function upload(file, onProgress, route, extra) {
    return new Promise(function (resolve, reject) {
      var fd = new FormData();
      fd.append('file', file);
      Object.keys(extra || {}).forEach(function (k) { fd.append(k, extra[k]); });
      var xhr = new XMLHttpRequest();
      xhr.open('POST', A.api + '?r=' + (route || 'media.upload'));
      xhr.setRequestHeader('X-CSRF-Token', S.csrf);
      xhr.setRequestHeader('Accept', 'application/json');
      xhr.upload.onprogress = function (e) { if (e.lengthComputable && onProgress) onProgress(e.loaded / e.total); };
      xhr.onload = function () {
        var json; try { json = JSON.parse(xhr.responseText); } catch (e) { return reject(new Error(xhr.status === 413 ? 'File is too large for the server.' : 'Upload failed (' + xhr.status + ').')); }
        json.ok ? resolve(json.data) : reject(new Error(json.error || 'Upload failed.'));
      };
      xhr.onerror = function () { reject(new Error('Network error during upload.')); };
      xhr.send(fd);
    });
  }

  /* =====================================================================
     Toasts, modals, confirm
     ===================================================================== */
  function toast(msg, type) {
    var t = el('<div class="toast toast--' + (type || 'ok') + '">' + ic(type === 'err' ? 'alert' : 'check-circle') + '<span>' + esc(msg) + '</span></div>');
    $('#toasts').appendChild(t);
    setTimeout(function () { t.classList.add('out'); setTimeout(function () { t.remove(); }, 260); }, type === 'err' ? 5200 : 3000);
  }
  function modal(opts) {
    var m = el('<div class="modal"><div class="modal__bg"></div><div class="modal__box ' + (opts.size ? 'modal__box--' + opts.size : '') + '" role="dialog" aria-modal="true" aria-label="' + esc(opts.title || '') + '">' +
      '<div class="modal__h"><h2>' + esc(opts.title || '') + '</h2><button class="icon-btn" data-close aria-label="Close">' + ic('x') + '</button></div>' +
      '<div class="modal__b"></div>' + (opts.footer !== false ? '<div class="modal__f"></div>' : '') + '</div></div>');
    var body = $('.modal__b', m);
    if (typeof opts.body === 'string') body.innerHTML = opts.body; else if (opts.body) body.appendChild(opts.body);
    var foot = $('.modal__f', m);
    var prevFocus = document.activeElement;
    function close(v) { m.remove(); document.removeEventListener('keydown', onKey); if (prevFocus && prevFocus.focus) prevFocus.focus(); if (opts.onClose) opts.onClose(v); }
    function onKey(e) { if (e.key === 'Escape') close(null); }
    (opts.buttons || []).forEach(function (b) {
      var btn = el('<button class="btn ' + (b.cls || 'btn--ghost') + '">' + (b.icon ? ic(b.icon) : '') + esc(b.label) + '</button>');
      btn.addEventListener('click', function () { b.onClick ? b.onClick(close, btn) : close(b.value); });
      foot.appendChild(btn);
    });
    $$('[data-close], .modal__bg', m).forEach(function (x) { x.addEventListener('click', function () { close(null); }); });
    document.addEventListener('keydown', onKey);
    $('#modal-root').appendChild(m);
    setTimeout(function () { var f = $('input, textarea, select, button.btn--primary', body) || $('[data-close]', m); if (f) f.focus(); }, 30);
    return { el: m, body: body, close: close };
  }
  function confirmBox(title, text, okLabel, danger) {
    return new Promise(function (resolve) {
      modal({
        title: title, body: '<p>' + esc(text) + '</p>', onClose: function (v) { resolve(!!v); },
        buttons: [{ label: 'Cancel', value: false }, { label: okLabel || 'Confirm', cls: danger ? 'btn--accent' : 'btn--primary', value: true }]
      });
    });
  }
  function busy(btn, on) { if (!btn) return; btn.classList.toggle('is-loading', on); btn.disabled = on; }

  /* =====================================================================
     Login
     ===================================================================== */
  function renderLogin(msg) {
    document.title = 'Sign in · ' + A.siteName;
    app.innerHTML =
      '<div class="login">' +
      '<div class="login__art"><div class="login__brand"><span>' + ic('plus') + '</span>' + esc(A.siteName) + '</div>' +
      '<div class="login__quote"><h2>Everything for your website, in one place.</h2><p>Edit pages, manage the media library, read form submissions and control who has access.</p></div>' +
      '<div class="login__feat"><span>Pages</span><span>Media library</span><span>Form inbox</span><span>Users &amp; roles</span><span>SEO &amp; redirects</span></div></div>' +
      '<div class="login__panel"><form class="login__form" novalidate>' +
      '<h1>Welcome back</h1><p>Sign in to the ' + esc(A.siteName) + ' dashboard.</p>' +
      (msg ? '<div class="login__err">' + esc(msg) + '</div>' : '<div class="login__err" hidden></div>') +
      '<div class="fields" style="padding:0;gap:14px">' +
      '<div class="f"><label for="lg-u">Username or email</label><input class="in" id="lg-u" name="login" autocomplete="username" required></div>' +
      '<div class="f"><label for="lg-p">Password</label><input class="in" id="lg-p" name="password" type="password" autocomplete="current-password" required></div>' +
      '<div class="f"><button class="btn btn--primary btn--lg btn--block" type="submit">Sign in ' + ic('arrow-right') + '</button></div>' +
      '</div><p class="muted" style="margin-top:18px;font-size:.82rem"><a href="' + esc(A.base) + '">← Back to website</a></p></form></div></div>';
    var form = $('.login__form');
    $('#lg-u').focus();
    form.addEventListener('submit', function (e) {
      e.preventDefault();
      var btn = $('button[type=submit]', form), err = $('.login__err', form);
      busy(btn, true);
      api('auth.login', { login: form.login.value, password: form.password.value }).then(function (d) {
        S.user = d.user; S.csrf = d.csrf;
        return boot();
      }).catch(function (e2) {
        busy(btn, false);
        err.hidden = false; err.textContent = e2.message;
        form.password.select();
      });
    });
  }

  /* =====================================================================
     Shell & navigation
     ===================================================================== */
  function navItems() {
    var R = S.schema.resources;
    var items = [
      { group: 'Overview' },
      { hash: '#/dashboard', label: 'Dashboard', icon: 'dashboard' },
      { group: 'Content' }
    ];
    ['services', 'providers', 'pages', 'testimonials', 'faqs', 'locations'].forEach(function (k) {
      if (R[k] && !R[k].hidden && can('content.view')) items.push({ hash: '#/' + k, label: R[k].label, icon: R[k].icon });
    });
    if (can('media.view')) items.push({ hash: '#/media', label: 'Media Library', icon: 'image' });
    if (can('submissions.view')) { items.push({ group: 'Inbox' }); items.push({ hash: '#/submissions', label: 'Form Inbox', icon: 'inbox', badge: true }); }
    var adm = [];
    if (can('users.manage')) adm.push({ hash: '#/users', label: 'Users & Roles', icon: 'users' });
    if (can('settings.manage')) adm.push({ hash: '#/settings', label: 'Settings', icon: 'settings' });
    if (can('redirects.manage')) adm.push({ hash: '#/redirects', label: 'Redirects', icon: 'route' });
    if (can('activity.view')) adm.push({ hash: '#/activity', label: 'Activity Log', icon: 'clock' });
    if (adm.length) { items.push({ group: 'Administration' }); items = items.concat(adm); }
    return items;
  }

  function renderShell() {
    var nav = navItems().map(function (n) {
      if (n.group) return '<div class="side__label">' + esc(n.group) + '</div>';
      return '<a class="side__link" href="' + n.hash + '" data-nav="' + n.hash + '">' + ic(n.icon) + '<span>' + esc(n.label) + '</span>' + (n.badge ? '<span class="side__badge" data-new-badge hidden>0</span>' : '') + '</a>';
    }).join('');
    var dark = document.documentElement.getAttribute('data-theme') === 'dark';
    app.innerHTML =
      '<div class="shell">' +
      '<aside class="side" aria-label="Dashboard navigation"><a class="side__brand" href="#/dashboard"><span class="side__logo">' + ic('plus') + '</span><span>' + esc(A.siteName) + '<small>Dashboard</small></span></a>' +
      '<nav class="side__nav">' + nav + '</nav>' +
      '<div class="side__foot"><a class="side__site" href="' + esc(A.base) + '" target="_blank" rel="noopener">' + ic('external') + 'View website</a><div class="side__ver">v' + esc(A.version) + (S.schema.site.noindex ? ' · <span title="Search engines blocked">Staging mode</span>' : '') + '</div></div></aside>' +
      '<div class="main"><header class="top">' +
      '<button class="icon-btn top__menu" data-menu aria-label="Menu">' + ic('menu') + '</button>' +
      '<button class="top__search" data-palette>' + ic('search') + '<span>Search or jump to…</span><kbd>Ctrl K</kbd></button>' +
      '<div class="top__right">' +
      '<button class="icon-btn" data-theme-toggle aria-label="Toggle dark mode" title="Toggle dark mode">' + ic(dark ? 'sun' : 'moon') + '</button>' +
      '<a class="icon-btn" href="' + esc(A.base) + '" target="_blank" rel="noopener" title="View website">' + ic('external') + '</a>' +
      '<button class="top__user" data-user-menu>' + avatar(S.user) + '<span><strong>' + esc(S.user.name || S.user.username) + '</strong><small>' + esc(S.user.role_label) + '</small></span>' + ic('chevron-down') + '</button>' +
      '</div></header><div class="view" id="view"></div></div></div>';

    $('[data-menu]').addEventListener('click', function () { $('.shell').classList.toggle('nav-open'); });
    $('.shell').addEventListener('click', function (e) { if (e.target === $('.shell')) $('.shell').classList.remove('nav-open'); });
    $$('.side__link').forEach(function (a) { a.addEventListener('click', function () { $('.shell').classList.remove('nav-open'); }); });
    $('[data-palette]').addEventListener('click', openPalette);
    $('[data-theme-toggle]').addEventListener('click', function (e) {
      var d = document.documentElement.getAttribute('data-theme') === 'dark';
      if (d) document.documentElement.removeAttribute('data-theme'); else document.documentElement.setAttribute('data-theme', 'dark');
      try { localStorage.setItem('dash-theme', d ? 'light' : 'dark'); } catch (x) {}
      e.currentTarget.innerHTML = ic(d ? 'moon' : 'sun');
    });
    $('[data-user-menu]').addEventListener('click', userMenu);
    refreshBadge();
  }

  function userMenu(e) {
    var btn = e.currentTarget;
    var existing = $('.umenu'); if (existing) { existing.remove(); return; }
    var r = btn.getBoundingClientRect();
    var m = el('<div class="umenu card" style="position:fixed;z-index:60;top:' + (r.bottom + 6) + 'px;right:' + (window.innerWidth - r.right) + 'px;min-width:220px;padding:6px">' +
      '<div style="padding:10px 12px;border-bottom:1px solid var(--line);margin-bottom:4px"><strong style="color:var(--ink)">' + esc(S.user.name || S.user.username) + '</strong><div class="muted" style="font-size:.8rem">' + esc(S.user.email) + '</div></div>' +
      '<a class="side__link" style="color:var(--text)" href="#/profile">' + ic('user') + 'My profile</a>' +
      '<button class="side__link" style="color:var(--text);width:100%;border:0;background:none" data-logout>' + ic('log-out') + 'Sign out</button></div>');
    document.body.appendChild(m);
    function away(ev) { if (!m.contains(ev.target) && ev.target !== btn) { m.remove(); document.removeEventListener('click', away, true); } }
    setTimeout(function () { document.addEventListener('click', away, true); });
    $('a', m).addEventListener('click', function () { m.remove(); });
    $('[data-logout]', m).addEventListener('click', function () {
      m.remove();
      api('auth.logout', {}).then(function () { S.user = null; location.hash = ''; renderLogin(); toast('Signed out.'); });
    });
  }

  function refreshBadge() {
    if (!can('submissions.view')) return;
    get('submissions.list', { status: 'new' }).then(function (d) {
      S.newCount = d.counts.new;
      var b = $('[data-new-badge]');
      if (b) { b.textContent = d.counts.new; b.hidden = !d.counts.new; }
    }).catch(function () {});
  }

  function setActiveNav(hash) {
    $$('.side__link').forEach(function (a) {
      var h = a.getAttribute('data-nav');
      a.classList.toggle('is-active', hash === h || hash.indexOf(h + '/') === 0 || hash.indexOf(h + '?') === 0);
    });
  }

  /* =====================================================================
     Router
     ===================================================================== */
  var RES = 'services|providers|pages|testimonials|faqs|locations|redirects';
  var routes = [
    [/^#\/dashboard$/, viewDashboard],
    [new RegExp('^#\\/(' + RES + ')$'), viewList],
    [new RegExp('^#\\/(' + RES + ')\\/(new|\\d+)$'), viewEdit],
    [/^#\/media$/, viewMedia],
    [/^#\/submissions(?:\/(\d+))?$/, viewSubmissions],
    [/^#\/users$/, viewUsers],
    [/^#\/settings(?:\/([a-z_]+))?$/, viewSettings],
    [/^#\/profile$/, viewProfile],
    [/^#\/activity$/, viewActivity]
  ];
  function route() {
    saveHandler = null;
    var hash = location.hash || '#/dashboard';
    if (S.dirty && hash !== S.lastHash) {
      if (!window.confirm('You have unsaved changes. Leave this page and discard them?')) {
        history.replaceState(null, '', S.lastHash);
        return;
      }
      S.dirty = false;
    }
    S.lastHash = hash;
    var view = $('#view');
    if (!view) return;
    setActiveNav(hash.split('?')[0]);
    var path = hash.split('?')[0];
    for (var i = 0; i < routes.length; i++) {
      var m = path.match(routes[i][0]);
      if (m) {
        view.innerHTML = '';
        view.style.animation = 'none'; void view.offsetWidth; view.style.animation = '';
        window.scrollTo(0, 0);
        routes[i][1].apply(null, [view].concat(m.slice(1)));
        return;
      }
    }
    location.replace('#/dashboard');
  }
  function go(hash) { location.hash = hash; }
  window.addEventListener('hashchange', route);
  window.addEventListener('beforeunload', function (e) { if (S.dirty) { e.preventDefault(); e.returnValue = ''; } });

  function header(title, sub, actions, crumb) {
    return '<div class="ph"><div>' + (crumb || '') + '<h1>' + title + '</h1>' + (sub ? '<p>' + sub + '</p>' : '') + '</div><div class="ph__actions">' + (actions || '') + '</div></div>';
  }
  function denied(view) { view.innerHTML = '<div class="card"><div class="empty-s">' + ic('shield-check') + 'You do not have access to this area.</div></div>'; }
  function errorBox(view, e) { view.innerHTML = '<div class="card"><div class="empty-s">' + ic('alert') + esc(e.message) + '</div></div>'; }

  /* =====================================================================
     Dashboard
     ===================================================================== */
  function viewDashboard(view) {
    document.title = 'Dashboard · ' + A.siteName;
    var hr = new Date().getHours();
    var greet = hr < 12 ? 'Good morning' : hr < 18 ? 'Good afternoon' : 'Good evening';
    view.innerHTML = '<div class="welcome"><div><h1>' + greet + ', ' + esc((S.user.name || S.user.username).split(' ')[0]) + '</h1><p>Here’s what’s happening on ' + esc(A.siteName) + '.</p></div>' +
      '<div class="ph__actions">' + (can('content.edit') && shown('services') ? '<a class="btn btn--ghost" href="#/services/new">' + ic('plus') + 'New treatment</a>' : '') + (can('content.view') ? '<a class="btn btn--ghost" href="#/pages">' + ic('file-text') + 'Pages</a>' : '') + (can('media.upload') ? '<a class="btn btn--ghost" href="#/media">' + ic('upload') + 'Upload media</a>' : '') + '<a class="btn btn--accent" href="' + esc(A.base) + '" target="_blank" rel="noopener">' + ic('external') + 'View site</a></div></div>' +
      '<div class="grid grid--kpi" id="kpis">' + [1, 2, 3, 4].map(function () { return '<div class="card kpi"><div class="skel" style="width:40%"></div><div class="skel" style="width:60%;height:26px;margin-top:10px"></div></div>'; }).join('') + '</div>';
    get('dashboard').then(function (d) {
      var c = d.counts, v = d.views;
      var delta = v.prev ? Math.round((v.total - v.prev) / v.prev * 100) : null;
      $('#kpis', view).innerHTML =
        kpi('#/dashboard', 'trending', '', 'Page views · 30 days', v.total.toLocaleString(), delta === null ? 'Tracking since install' : (delta >= 0 ? '▲ ' : '▼ ') + Math.abs(delta) + '% vs previous 30 days', delta === null ? '' : delta >= 0 ? 'up' : 'down') +
        (c.new_submissions !== undefined ? kpi('#/submissions', 'inbox', 'red', 'New form submissions', c.new_submissions, c.week_submissions + ' in the last 7 days') : kpi('#/pages', 'file-text', 'red', 'Published pages', c.pages, '')) +
        (shown('services') ? kpi('#/services', 'activity', 'green', 'Published treatments', c.services, c.providers + ' provider profiles · ' + c.pages + ' pages') : kpi('#/pages', 'file-text', 'green', 'Published pages', c.pages, c.media + ' media files')) +
        kpi(shown('services') ? '#/services' : '#/pages?review=1', 'flag', 'amber', 'Needs content review', c.review, c.drafts + ' drafts · ' + c.media + ' media files');
      var g = el('<div class="grid grid--2" style="margin-top:18px"></div>');
      g.appendChild(el('<div class="card"><div class="card__h"><h2>Page views — last 30 days</h2><span class="muted" style="font-size:.82rem">Visitors excluding bots</span></div><div class="card__b"><div class="chart" id="chart"></div></div></div>'));
      var max = d.top.length ? d.top[0].v : 1;
      g.appendChild(el('<div class="card"><div class="card__h"><h2>Top pages</h2><span class="muted" style="font-size:.82rem">30 days</span></div><div class="card__b--flush">' +
        (d.top.length ? '<ul class="list">' + d.top.map(function (t) { return '<li><div class="list__main"><a href="' + esc(A.base + t.path.replace(/^\//, '')) + '" target="_blank" rel="noopener"><strong class="mono">' + esc(t.path) + '</strong></a><div class="bar"><span style="width:' + Math.max(4, t.v / max * 100) + '%"></span></div></div><strong style="color:var(--ink)">' + t.v.toLocaleString() + '</strong></li>'; }).join('') + '</ul>' : '<div class="empty-s">' + ic('trending') + 'No visits recorded yet.</div>') + '</div></div>'));
      view.appendChild(g);
      drawChart($('#chart', view), v.days);

      var g2 = el('<div class="grid grid--2e" style="margin-top:18px"></div>');
      g2.appendChild(el('<div class="card"><div class="card__h"><h2>Website health</h2></div><div class="card__b--flush">' +
        (d.health.length ? '<ul class="list health">' + d.health.map(function (h) { return '<li><span class="' + h.level + '">' + ic(h.level === 'warn' ? 'alert' : 'info') + '</span><div class="list__main"><strong style="white-space:normal">' + esc(h.text) + '</strong></div>' + (can('settings.manage') || h.link.indexOf('settings') < 0 ? '<a class="btn btn--sm btn--ghost" href="' + h.link + '">Fix</a>' : '') + '</li>'; }).join('') + '</ul>' : '<div class="empty-s">' + ic('check-circle') + 'Everything looks great.</div>') + '</div></div>'));
      g2.appendChild(el('<div class="card"><div class="card__h"><h2>Needs content review <span class="pill pill--amber pill--plain" style="margin-left:6px">' + d.review.length + '</span></h2></div><div class="card__b--flush" style="max-height:380px;overflow:auto">' +
        (d.review.length ? '<ul class="list">' + d.review.map(function (r) { return '<li><a class="list__row" href="#/' + r.type + '/' + r.id + '"><span class="list__ico">' + ic(S.schema.resources[r.type].icon) + '</span><span class="list__main"><strong>' + esc(r.title) + '</strong><small>' + esc(S.schema.resources[r.type].singular) + ' · ' + esc(r.status) + '</small></span>' + ic('chevron-right') + '</a></li>'; }).join('') + '</ul>' : '<div class="empty-s">' + ic('check-circle') + 'All content has been reviewed.</div>') + '</div></div>'));
      view.appendChild(g2);

      var g3 = el('<div class="grid grid--2e" style="margin-top:18px"></div>');
      if (d.submissions) {
        g3.appendChild(el('<div class="card"><div class="card__h"><h2>Latest form submissions</h2><a class="btn btn--sm btn--ghost" href="#/submissions">Open inbox</a></div><div class="card__b--flush">' +
          (d.submissions.length ? '<ul class="list">' + d.submissions.map(function (s) { return '<li><a class="list__row" href="#/submissions/' + s.id + '"><span class="list__ico">' + ic(s.form === 'appointment' ? 'clock' : s.form === 'benefits' ? 'shield-check' : 'mail') + '</span><span class="list__main"><strong>' + esc(s.name || s.email || 'Anonymous') + '</strong><small>' + esc(s.form_label) + ' · ' + ago(s.created_at) + '</small></span>' + (s.status === 'new' ? '<span class="pill pill--new">New</span>' : '') + '</a></li>'; }).join('') + '</ul>' : '<div class="empty-s">' + ic('inbox') + 'No submissions yet.</div>') + '</div></div>'));
      }
      g3.appendChild(el('<div class="card"><div class="card__h"><h2>Recent activity</h2>' + (can('activity.view') ? '<a class="btn btn--sm btn--ghost" href="#/activity">View all</a>' : '') + '</div><div class="card__b--flush">' + activityList(d.activity) + '</div></div>'));
      view.appendChild(g3);
    }).catch(function (e) { errorBox(view, e); });
  }
  function kpi(href, icon, tone, label, value, sub, cls) {
    return '<a class="card kpi" href="' + href + '"><span class="kpi__icon ' + (tone ? 'kpi__icon--' + tone : '') + '">' + ic(icon) + '</span><span class="kpi__label">' + esc(label) + '</span><span class="kpi__value">' + esc(value) + '</span><span class="kpi__delta ' + (cls || '') + '">' + esc(sub) + '</span></a>';
  }
  function activityList(items) {
    if (!items || !items.length) return '<div class="empty-s">' + ic('clock') + 'No activity yet.</div>';
    return '<ul class="list">' + items.map(function (a) {
      return '<li>' + avatar({ name: a.user_name }) + '<div class="list__main"><strong style="font-weight:550;white-space:normal"><b style="color:var(--ink)">' + esc(a.user_name) + '</b> ' + esc(a.action) + ' ' + esc(a.object_type ? a.object_type.replace(/s$/, '') : '') + (a.label ? ' “' + esc(a.label) + '”' : '') + '</strong><small>' + ago(a.created_at) + '</small></div></li>';
    }).join('') + '</ul>';
  }

  /* Single-series area chart with crosshair tooltip */
  function drawChart(box, days) {
    var W = box.clientWidth || 600, H = box.clientHeight || 240, pl = 36, pr = 10, pt = 12, pb = 26;
    var max = Math.max.apply(null, days.map(function (d) { return d.v; }).concat([4]));
    var nice = Math.pow(10, Math.floor(Math.log10(max)));
    max = Math.ceil(max / nice) * nice;
    var x = function (i) { return pl + i * (W - pl - pr) / (days.length - 1); };
    var y = function (v) { return pt + (H - pt - pb) * (1 - v / max); };
    var line = days.map(function (d, i) { return (i ? 'L' : 'M') + x(i).toFixed(1) + ' ' + y(d.v).toFixed(1); }).join(' ');
    var area = line + ' L' + x(days.length - 1) + ' ' + y(0) + ' L' + x(0) + ' ' + y(0) + ' Z';
    var grid = '';
    for (var k = 0; k <= 4; k++) {
      var gv = max * k / 4, gy = y(gv);
      grid += '<line class="grid-line" x1="' + pl + '" x2="' + (W - pr) + '" y1="' + gy + '" y2="' + gy + '"/><text class="axis-label" x="' + (pl - 8) + '" y="' + (gy + 4) + '" text-anchor="end">' + Math.round(gv) + '</text>';
    }
    var labels = '';
    days.forEach(function (d, i) {
      if ((i % 7 === 0 && i < days.length - 4) || i === days.length - 1) {
        var dt = new Date(d.day + 'T00:00:00');
        labels += '<text class="axis-label" x="' + x(i) + '" y="' + (H - 6) + '" text-anchor="' + (i === 0 ? 'start' : i === days.length - 1 ? 'end' : 'middle') + '">' + dt.toLocaleDateString(undefined, { month: 'short', day: 'numeric' }) + '</text>';
      }
    });
    var total = days.reduce(function (s, d) { return s + d.v; }, 0);
    box.innerHTML = '<svg viewBox="0 0 ' + W + ' ' + H + '" role="img" aria-label="Daily page views for the last 30 days, ' + total + ' total"><defs><linearGradient id="chartFill" x1="0" y1="0" x2="0" y2="1"><stop offset="0" stop-color="var(--brand)" stop-opacity=".22"/><stop offset="1" stop-color="var(--brand)" stop-opacity="0"/></linearGradient></defs>' +
      grid + labels + '<path class="area" d="' + area + '"/><path class="series" d="' + line + '"/>' +
      '<line class="xhair" y1="' + pt + '" y2="' + y(0) + '" visibility="hidden"/><circle class="dot" r="5" visibility="hidden"/>' +
      '<rect x="' + pl + '" y="0" width="' + (W - pl - pr) + '" height="' + H + '" fill="transparent" data-hit/></svg>' +
      '<div class="chart__tip" hidden></div>' + (total === 0 ? '<div class="chart__empty">Visits will appear here as people browse the site.</div>' : '');
    var hit = $('[data-hit]', box), xh = $('.xhair', box), dot = $('.dot', box), tip = $('.chart__tip', box);
    function show(e) {
      var r = box.getBoundingClientRect();
      var px = ((e.touches ? e.touches[0].clientX : e.clientX) - r.left) * (W / r.width);
      var i = Math.max(0, Math.min(days.length - 1, Math.round((px - pl) / ((W - pl - pr) / (days.length - 1)))));
      var d = days[i];
      xh.setAttribute('x1', x(i)); xh.setAttribute('x2', x(i)); xh.setAttribute('visibility', 'visible');
      dot.setAttribute('cx', x(i)); dot.setAttribute('cy', y(d.v)); dot.setAttribute('visibility', 'visible');
      tip.hidden = false;
      tip.style.left = (x(i) * r.width / W) + 'px';
      tip.style.top = (y(d.v) * r.height / H) + 'px';
      tip.innerHTML = '<strong>' + d.v.toLocaleString() + ' views</strong>' + new Date(d.day + 'T00:00:00').toLocaleDateString(undefined, { weekday: 'short', month: 'short', day: 'numeric' });
    }
    function hide() { xh.setAttribute('visibility', 'hidden'); dot.setAttribute('visibility', 'hidden'); tip.hidden = true; }
    hit.addEventListener('mousemove', show); hit.addEventListener('touchmove', show, { passive: true });
    hit.addEventListener('mouseleave', hide); hit.addEventListener('touchend', hide);
  }

  /* =====================================================================
     Generic content list
     ===================================================================== */
  function optionLabel(def, key, value) {
    var f = (def.fields || []).filter(function (x) { return x.key === key; })[0];
    if (f && f.options) { var o = f.options.filter(function (x) { return String(x.value) === String(value); })[0]; if (o) return o.label; }
    if (def.filters && def.filters[key]) { var o2 = def.filters[key].filter(function (x) { return x.value === value; })[0]; if (o2) return o2.label; }
    return value;
  }
  function cell(def, col, item) {
    var v = item[col.key];
    switch (col.type) {
      case 'title':
        return '<a class="tbl-title" href="#/' + def._type + '/' + item.id + '">' + esc(v || '(untitled)') + '</a>' + (item._review ? '<span class="flag" title="Needs content review">' + ic('flag') + 'Review</span>' : '') + (item._system ? ' <span class="tag" title="Core page — cannot be deleted">Core</span>' : '') + (item._url ? '<span class="tbl-sub mono">' + esc(item._url.replace(A.base, '/')) + '</span>' : '');
      case 'status': return '<span class="pill pill--' + esc(v) + '">' + esc(v === 'published' ? 'Published' : 'Draft') + '</span>';
      case 'option': return '<span class="tag">' + esc(optionLabel(def, col.key, v)) + '</span>';
      case 'bool': return +v ? '<span style="color:var(--green)">' + ic('check-circle') + '</span>' : '<span class="muted">—</span>';
      case 'date': return '<span class="muted nowrap" title="' + esc(fullDate(v)) + '">' + ago(v) + '</span>';
      case 'thumb': return v ? '<img class="tbl-thumb" src="' + esc(v) + '" alt="">' : '<span class="tbl-thumb" style="display:grid;place-items:center;color:var(--faint)">' + ic('user') + '</span>';
      case 'excerpt': return v ? '<span class="tbl-excerpt">' + esc(v) + '</span>' : '<span class="pill pill--amber">Needs original text</span>';
      case 'path': return '<span class="mono muted">' + esc(def._type === 'pages' ? '/' + v + '/' : v) + '</span>';
      case 'number': return '<strong style="color:var(--ink)">' + (+v || 0).toLocaleString() + '</strong>';
      default: return esc(v);
    }
  }

  function viewList(view, type) {
    var def = S.schema.resources[type];
    if (!def) return go('#/dashboard');
    def._type = type;
    if (def.cap && !can(def.cap)) return denied(view);
    document.title = def.label + ' · ' + A.siteName;
    var state = { q: '', page: 1, filters: {}, review: /review=1/.test(location.hash) ? 1 : 0, selected: {} };
    var canEdit = can('content.edit') && (!def.cap || can(def.cap));
    var canDel = can('content.delete') && (!def.cap || can(def.cap));
    var canPub = can('content.publish');
    var subs = { services: 'Service and treatment pages. Drag to reorder how they appear in menus and listings.', providers: 'Provider profiles shown on the website.', pages: 'Core and standard pages. Core pages keep their URLs and cannot be deleted.', testimonials: 'Patient stories. Only published testimonials with text appear on the site.', faqs: 'Questions shown on the website, grouped by where they appear (with FAQ schema).', locations: 'Office details used in the header, footer, maps and local SEO schema.', redirects: '301 redirects keep old URLs and SEO value working.' };
    view.innerHTML = header(esc(def.label), esc(subs[type] || ''), canEdit ? '<a class="btn btn--primary" href="#/' + type + '/new">' + ic('plus') + 'New ' + esc(def.singular.toLowerCase()) + '</a>' : '') +
      '<div class="card"><div class="toolbar">' +
      '<label class="search-in">' + ic('search') + '<input type="search" placeholder="Search ' + esc(def.label.toLowerCase()) + '…" data-q></label>' +
      Object.keys(def.filters || {}).map(function (k) { return '<select class="sel" data-filter="' + k + '"><option value="">' + ({ status: 'All statuses', category: 'All categories', type: 'All types', grp: 'All sections' }[k] || 'All') + '</option>' + def.filters[k].map(function (o) { return '<option value="' + esc(o.value) + '">' + esc(o.label) + '</option>'; }).join('') + '</select>'; }).join('') +
      (def.review ? '<label class="checks"><label><input type="checkbox" data-review' + (state.review ? ' checked' : '') + '> Needs review</label></label>' : '') +
      '<div class="bulk" data-bulk hidden><span data-bulk-count></span>' + (canPub && def.columns.some(function (c) { return c.type === 'status'; }) ? '<button class="btn btn--sm btn--ghost" data-bulk-pub>Publish</button><button class="btn btn--sm btn--ghost" data-bulk-unpub>Unpublish</button>' : '') + (canDel ? '<button class="btn btn--sm btn--danger" data-bulk-del>' + ic('trash') + 'Delete</button>' : '') + '</div>' +
      '</div><div class="tbl-wrap"><table class="tbl"><thead><tr>' +
      (def.orderable ? '<th class="col-drag"></th>' : '') + '<th class="col-check"><input type="checkbox" class="check" data-all aria-label="Select all"></th>' +
      def.columns.map(function (c) { return '<th>' + esc(c.label) + '</th>'; }).join('') + '<th class="col-actions"></th></tr></thead><tbody data-rows>' +
      '<tr><td colspan="9"><div class="skel"></div></td></tr></tbody></table></div><div class="pager" data-pager></div></div>';

    var tbody = $('[data-rows]', view);
    function load() {
      var p = { type: type, q: state.q, page: state.page, per: def.orderable ? 200 : 50, review: state.review || '' };
      Object.keys(state.filters).forEach(function (k) { p['f_' + k] = state.filters[k]; });
      get('res.list', p).then(render).catch(function (e) { tbody.innerHTML = '<tr><td colspan="9"><div class="empty-s">' + esc(e.message) + '</div></td></tr>'; });
    }
    function render(d) {
      state.selected = {};
      updateBulk();
      var dragOk = def.orderable && canEdit && !state.q && !Object.keys(state.filters).some(function (k) { return state.filters[k]; }) && !state.review;
      if (!d.items.length) {
        tbody.innerHTML = '<tr><td colspan="9"><div class="empty-s">' + ic(def.icon) + 'Nothing here yet.' + (canEdit ? ' <a href="#/' + type + '/new">Create the first ' + esc(def.singular.toLowerCase()) + '</a>.' : '') + '</div></td></tr>';
      } else {
        tbody.innerHTML = d.items.map(function (it) {
          return '<tr data-id="' + it.id + '"' + (dragOk ? ' draggable="true"' : '') + '>' +
            (def.orderable ? '<td class="col-drag" title="' + (dragOk ? 'Drag to reorder' : 'Clear filters to reorder') + '">' + (dragOk ? ic('grip') : '') + '</td>' : '') +
            '<td class="col-check"><input type="checkbox" class="check" data-sel="' + it.id + '" aria-label="Select"></td>' +
            def.columns.map(function (c) { return '<td>' + cell(def, c, it) + '</td>'; }).join('') +
            '<td class="col-actions"><span class="row-actions">' +
            (it._url ? '<a class="btn btn--sm btn--plain btn--icon" href="' + esc(it._url) + (it.status === 'draft' ? '?preview=1' : '') + '" target="_blank" rel="noopener" title="View">' + ic('eye') + '</a>' : '') +
            '<a class="btn btn--sm btn--plain btn--icon" href="#/' + type + '/' + it.id + '" title="Edit">' + ic('pencil') + '</a>' +
            (canEdit && type !== 'redirects' ? '<button class="btn btn--sm btn--plain btn--icon" data-dup="' + it.id + '" title="Duplicate">' + ic('copy') + '</button>' : '') +
            (canDel && !it._system ? '<button class="btn btn--sm btn--plain btn--icon" data-del="' + it.id + '" title="Delete" style="color:var(--red)">' + ic('trash') + '</button>' : '') +
            '</span></td></tr>';
        }).join('');
      }
      $('[data-pager]', view).innerHTML = '<span>' + d.total + ' ' + (d.total === 1 ? def.singular.toLowerCase() : def.label.toLowerCase()) + '</span>' +
        (d.pages > 1 ? '<span class="ph__actions"><button class="btn btn--sm btn--ghost" data-page="' + (d.page - 1) + '"' + (d.page <= 1 ? ' disabled' : '') + '>' + ic('arrow-left') + '</button><span>Page ' + d.page + ' of ' + d.pages + '</span><button class="btn btn--sm btn--ghost" data-page="' + (d.page + 1) + '"' + (d.page >= d.pages ? ' disabled' : '') + '>' + ic('arrow-right') + '</button></span>' : '');
      if (dragOk) enableDrag();
    }
    function updateBulk() {
      var ids = Object.keys(state.selected);
      $('[data-bulk]', view).hidden = !ids.length;
      $('[data-bulk-count]', view).textContent = ids.length + ' selected';
      $$('tbody tr', view).forEach(function (tr) { tr.classList.toggle('is-selected', !!state.selected[tr.getAttribute('data-id')]); });
    }
    function enableDrag() {
      var dragging = null;
      $$('tr[draggable]', tbody).forEach(function (tr) {
        tr.addEventListener('dragstart', function (e) { dragging = tr; tr.classList.add('dragging'); e.dataTransfer.effectAllowed = 'move'; });
        tr.addEventListener('dragend', function () { tr.classList.remove('dragging'); $$('.drop-before', tbody).forEach(function (x) { x.classList.remove('drop-before'); }); });
        tr.addEventListener('dragover', function (e) { e.preventDefault(); $$('.drop-before', tbody).forEach(function (x) { x.classList.remove('drop-before'); }); if (tr !== dragging) tr.classList.add('drop-before'); });
        tr.addEventListener('drop', function (e) {
          e.preventDefault();
          if (!dragging || dragging === tr) return;
          tbody.insertBefore(dragging, tr);
          var ids = $$('tr[data-id]', tbody).map(function (r) { return r.getAttribute('data-id'); });
          api('res.reorder', { type: type, ids: ids }).then(function () { toast('Order saved.'); }).catch(function (er) { toast(er.message, 'err'); load(); });
        });
      });
    }
    function del(ids) {
      confirmBox('Delete ' + (ids.length > 1 ? ids.length + ' items' : 'this ' + def.singular.toLowerCase()) + '?', 'This cannot be undone.', 'Delete', true).then(function (yes) {
        if (!yes) return;
        api('res.delete', { type: type, ids: ids }).then(function (r) { toast(r.deleted + ' deleted.'); load(); }).catch(function (e) { toast(e.message, 'err'); });
      });
    }
    $('[data-q]', view).addEventListener('input', debounce(function (e) { state.q = e.target.value; state.page = 1; load(); }, 250));
    $$('[data-filter]', view).forEach(function (s) { s.addEventListener('change', function () { state.filters[s.getAttribute('data-filter')] = s.value; state.page = 1; load(); }); });
    var rv = $('[data-review]', view); if (rv) rv.addEventListener('change', function () { state.review = rv.checked ? 1 : 0; load(); });
    $('[data-all]', view).addEventListener('change', function (e) {
      $$('[data-sel]', view).forEach(function (c) { c.checked = e.target.checked; if (c.checked) state.selected[c.getAttribute('data-sel')] = 1; else delete state.selected[c.getAttribute('data-sel')]; });
      updateBulk();
    });
    view.addEventListener('change', function (e) {
      var c = e.target.closest('[data-sel]'); if (!c) return;
      if (c.checked) state.selected[c.getAttribute('data-sel')] = 1; else delete state.selected[c.getAttribute('data-sel')];
      updateBulk();
    });
    view.addEventListener('click', function (e) {
      var b;
      if ((b = e.target.closest('[data-del]'))) del([b.getAttribute('data-del')]);
      else if ((b = e.target.closest('[data-dup]'))) api('res.duplicate', { type: type, id: b.getAttribute('data-dup') }).then(function (r) { toast('Duplicated as a draft.'); go('#/' + type + '/' + r.id); }).catch(function (er) { toast(er.message, 'err'); });
      else if ((b = e.target.closest('[data-page]'))) { state.page = +b.getAttribute('data-page'); load(); }
      else if (e.target.closest('[data-bulk-del]')) del(Object.keys(state.selected));
      else if ((b = e.target.closest('[data-bulk-pub], [data-bulk-unpub]'))) {
        api('res.status', { type: type, ids: Object.keys(state.selected), status: b.hasAttribute('data-bulk-pub') ? 'published' : 'draft' }).then(function () { toast('Status updated.'); load(); }).catch(function (er) { toast(er.message, 'err'); });
      }
    });
    load();
  }

  /* =====================================================================
     Generic editor
     ===================================================================== */
  function viewEdit(view, type, id) {
    var def = S.schema.resources[type];
    if (!def) return go('#/dashboard');
    if (def.cap && !can(def.cap)) return denied(view);
    var isNew = id === 'new';
    var canEdit = can('content.edit');
    view.innerHTML = '<div class="card"><div class="card__b"><div class="skel" style="width:30%;height:24px"></div><div class="skel" style="margin-top:16px"></div><div class="skel" style="margin-top:10px;width:70%"></div></div></div>';
    (isNew ? Promise.resolve(null) : get('res.get', { type: type, id: id })).then(function (item) {
      var vals = {};
      def.fields.forEach(function (f) { vals[f.key] = item ? item[f.key] : (f.default !== undefined ? (f.type === 'toggle' ? !!+f.default : f.default) : (f.type === 'repeater' || f.type === 'checks' || f.type === 'relation' ? [] : f.type === 'toggle' ? false : '')); });
      if (isNew && def.filters && def.filters.category && !vals.category) vals.category = def.filters.category[0].value;
      var title = item ? (item[def.titleKey] || '(untitled)') : 'New ' + def.singular.toLowerCase();
      document.title = title + ' · ' + A.siteName;
      var readOnly = !canEdit || (!can('content.publish') && item && item.status === 'published');
      // Raw-HTML pages (template "html"): code editor, Administrators only
      var isHtml = type === 'pages' && vals.template === 'html';
      var htmlLocked = isHtml && !isAdmin();
      if (htmlLocked) readOnly = true;
      var skipKeys = isHtml ? ['eyebrow', 'intro', 'image', 'show_cta'] : [];
      var groups = def.groups || { content: 'Content' };
      var mainFields = def.fields.filter(function (f) { return !f.side && skipKeys.indexOf(f.key) < 0; });
      var sideFields = def.fields.filter(function (f) { return f.side && f.key !== 'status' && skipKeys.indexOf(f.key) < 0; });
      var hasStatus = def.fields.some(function (f) { return f.key === 'status'; });
      var crumb = '<div class="crumb"><a href="#/' + type + '">' + esc(def.label) + '</a>' + ic('chevron-right') + '<span>' + (isNew ? 'New' : 'Edit') + '</span></div>';
      var viewUrl = item && item._url ? item._url + (item.status === 'draft' || isHtml ? '?preview=1' : '') : '';
      view.innerHTML = header('<span data-title>' + esc(title) + '</span>', '', (viewUrl ? '<a class="btn btn--ghost" href="' + esc(viewUrl) + '" target="_blank" rel="noopener" title="Opens the last saved version">' + ic('eye') + (isHtml ? 'Preview' : 'View') + '</a>' : '') + (item && can('content.delete') && !item.is_system ? '<button class="btn btn--danger" data-delete>' + ic('trash') + 'Delete</button>' : ''), crumb) +
        (htmlLocked ? '<div class="notice">' + ic('info') + '<span>This page is raw HTML. Only administrators can edit it.</span></div>' : readOnly && canEdit ? '<div class="notice">' + ic('info') + '<span>This item is published. Only editors and administrators can change published content.</span></div>' : '') +
        '<div class="editor"><div class="card" data-main><div class="tabs" role="tablist">' + Object.keys(groups).filter(function (g) { return mainFields.some(function (f) { return (f.group || 'content') === g; }); }).map(function (g, i) { return '<button class="tab' + (i ? '' : ' is-active') + '" role="tab" data-tab="' + g + '">' + esc(groups[g]) + '</button>'; }).join('') + '</div><div data-panels></div></div>' +
        '<div class="editor__side"><div class="card"><div class="card__h"><h3>Publish</h3><span data-dirty hidden><span class="dirty-dot"></span><span class="muted" style="font-size:.8rem">Unsaved</span></span></div><div class="pub" data-pub></div></div>' +
        (sideFields.length ? '<div class="card"><div class="card__h"><h3>Settings</h3></div><div class="fields" data-side style="padding:16px;gap:16px"></div></div>' : '') +
        (type === 'pages' && item ? '<div class="card"><div class="card__h"><h3>Revisions</h3><span class="muted" style="font-size:.78rem">latest 30</span></div><div class="card__b--flush" data-revs><div class="card__b"><div class="skel"></div></div></div></div>' : '') + '</div></div>';

      var panels = $('[data-panels]', view);
      Object.keys(groups).forEach(function (g, i) {
        var fs = mainFields.filter(function (f) { return (f.group || 'content') === g; });
        if (!fs.length) return;
        var p = el('<div class="fields" data-panel="' + g + '"' + (i ? ' hidden' : '') + '></div>');
        fs.forEach(function (f) { var fe = isHtml && f.key === 'content' ? htmlEditor(f, vals, onChange, readOnly) : buildField(f, vals, onChange, { def: def, isNew: isNew }); if (fe) p.appendChild(fe); });
        panels.appendChild(p);
        if (g === 'seo') { var sp = el('<div data-panel="seo-preview"' + (i ? ' hidden' : '') + '></div>'); panels.appendChild(sp); renderSerp(); }
      });
      $$('[data-tab]', view).forEach(function (t) {
        t.addEventListener('click', function () {
          $$('[data-tab]', view).forEach(function (x) { x.classList.toggle('is-active', x === t); });
          $$('[data-panel]', view).forEach(function (p) { var g = p.getAttribute('data-panel'); p.hidden = g !== t.getAttribute('data-tab') && !(g === 'seo-preview' && t.getAttribute('data-tab') === 'seo'); });
        });
      });
      var side = $('[data-side]', view);
      sideFields.forEach(function (f) { var fe = buildField(f, vals, onChange, { def: def, isNew: isNew }); if (fe) side.appendChild(fe); });

      // Publish box
      var pub = $('[data-pub]', view);
      pub.innerHTML = (hasStatus ? '<div class="f"><label for="pub-status">Status</label><select class="in" id="pub-status"' + (!can('content.publish') ? ' disabled title="Authors save drafts — an editor publishes"' : '') + '><option value="published">Published</option><option value="draft">Draft</option></select></div>' : '') +
        '<div class="pub__meta">' + (item ? '<div><span>Created</span><strong>' + fullDate(item.created_at) + '</strong></div><div><span>Last updated</span><strong>' + fullDate(item.updated_at) + '</strong></div>' : '<div><span>Not saved yet</span></div>') + '</div>' +
        '<div class="pub__row"><button class="btn btn--primary btn--block btn--lg" data-save' + (readOnly ? ' disabled' : '') + '>' + ic('check') + (isNew ? 'Create ' + esc(def.singular.toLowerCase()) : 'Save changes') + '</button></div><p class="muted" style="font-size:.76rem;margin:0;text-align:center">Tip: press Ctrl + S to save</p>';
      var st = $('#pub-status', view);
      if (st) { st.value = vals.status || 'draft'; if (!can('content.publish')) st.value = 'draft'; st.addEventListener('change', function () { vals.status = st.value; onChange('status'); }); }

      function onChange(key) {
        S.dirty = true;
        $('[data-dirty]', view).hidden = false;
        if (key === def.titleKey) $('[data-title]', view).textContent = vals[def.titleKey] || '(untitled)';
        if (key === 'template' && type === 'pages' && !isNew && (vals.template === 'html') !== isHtml) toast('Save the page, then reload it to switch editors.');
        if (key === 'meta_title' || key === 'meta_description' || key === def.titleKey || key === 'excerpt' || key === 'intro' || key === 'slug') renderSerp();
      }
      function renderSerp() {
        var sp = $('[data-panel="seo-preview"]', view);
        if (!sp) return;
        var t = vals.meta_title || vals[def.titleKey] || '';
        var d = vals.meta_description || vals.excerpt || vals.intro || vals.short_bio || '';
        var u = S.schema.site.abs + (def.url ? def.url.replace('{slug}', vals.slug || '') : '');
        sp.innerHTML = '<div class="serp"><div class="serp__url">' + esc(u) + '</div><div class="serp__t">' + esc((t + ' | ' + A.siteName).slice(0, 70)) + '</div><div class="serp__d">' + esc(String(d).slice(0, 165)) + '</div></div>';
      }
      function save() {
        if (readOnly) return;
        var btn = $('[data-save]', view);
        busy(btn, true);
        $$('.f.has-error', view).forEach(function (f) { f.classList.remove('has-error'); var e = $('.f__err', f); if (e) e.remove(); });
        api('res.save', { type: type, id: isNew ? 0 : +id, data: vals }).then(function (saved) {
          S.dirty = false;
          toast(isNew ? def.singular + ' created.' : 'Changes saved.');
          if (saved._notice) { toast(saved._notice); if (!isNew) { route(); return; } }
          if (!isNew) loadRevs();
          if (isNew) { go('#/' + type + '/' + saved.id); } else { busy(btn, false); $('[data-dirty]', view).hidden = true; if (saved.slug && vals.slug !== saved.slug) { vals.slug = saved.slug; var si = $('[data-key="slug"] input', view); if (si) si.value = saved.slug; } }
        }).catch(function (e) {
          busy(btn, false);
          toast(e.message, 'err');
          if (e.fields) Object.keys(e.fields).forEach(function (k) { var f = $('[data-key="' + k + '"]', view); if (f) { f.classList.add('has-error'); f.appendChild(el('<span class="f__err">' + esc(e.fields[k]) + '</span>')); var tabEl = f.closest('[data-panel]'); if (tabEl && tabEl.hidden) { var tb = $('[data-tab="' + tabEl.getAttribute('data-panel') + '"]', view); if (tb) tb.click(); } } });
        });
      }
      $('[data-save]', view).addEventListener('click', save);
      bindSaveKey(save);

      // Revisions: every save of a page's content keeps the previous version
      function loadRevs() {
        var box = $('[data-revs]', view);
        if (!box) return;
        get('page.revisions', { id: item.id }).then(function (rs) {
          box.innerHTML = rs.length ? '<ul class="list">' + rs.map(function (r) {
            return '<li><div class="list__main"><strong style="font-weight:600">' + esc(fullDate(r.created_at)) + '</strong><small>' + esc(r.user) + ' · ' + bytes(r.size) + '</small></div>' +
              '<button class="btn btn--sm btn--plain" data-rev-view="' + r.id + '">View</button>' + (readOnly ? '' : '<button class="btn btn--sm btn--ghost" data-rev-restore="' + r.id + '">Restore</button>') + '</li>';
          }).join('') + '</ul>' : '<div class="empty-s" style="padding:18px">' + ic('clock') + 'No earlier versions yet.</div>';
        }).catch(function (e) { box.innerHTML = '<div class="empty-s">' + esc(e.message) + '</div>'; });
      }
      if (type === 'pages' && item) {
        loadRevs();
        $('[data-revs]', view).addEventListener('click', function (e) {
          var b;
          if ((b = e.target.closest('[data-rev-view]'))) {
            get('page.revision', { id: b.getAttribute('data-rev-view') }).then(function (r) {
              var ta = el('<textarea class="in in--code" readonly spellcheck="false" style="width:100%;height:60vh;white-space:pre"></textarea>');
              ta.value = r.content || '';
              modal({ title: 'Revision from ' + fullDate(r.created_at), body: ta, size: 'wide', buttons: [{ label: 'Close', value: null }] });
            }).catch(function (er) { toast(er.message, 'err'); });
          } else if ((b = e.target.closest('[data-rev-restore]'))) {
            confirmBox('Restore this version?', 'The page content is replaced by this version. The current content is kept as a revision, so you can undo this.', 'Restore', false).then(function (yes) {
              if (!yes) return;
              api('page.restore', { id: +b.getAttribute('data-rev-restore') }).then(function () { S.dirty = false; toast('Version restored.'); route(); }).catch(function (er) { toast(er.message, 'err'); });
            });
          }
        });
      }
      var delBtn = $('[data-delete]', view);
      if (delBtn) delBtn.addEventListener('click', function () {
        confirmBox('Delete “' + title + '”?', 'This cannot be undone.', 'Delete', true).then(function (yes) {
          if (!yes) return;
          api('res.delete', { type: type, ids: [item.id] }).then(function () { S.dirty = false; toast('Deleted.'); go('#/' + type); }).catch(function (e) { toast(e.message, 'err'); });
        });
      });
      if (readOnly) $$('input, select, textarea, [contenteditable]', $('.editor', view)).forEach(function (x) { if (x.hasAttribute('contenteditable')) x.setAttribute('contenteditable', 'false'); else x.disabled = true; });
    }).catch(function (e) { errorBox(view, e); });
  }
  var saveHandler = null;
  function bindSaveKey(fn) { saveHandler = fn; }

  /* Code editor for raw-HTML pages: CodeMirror 5 from cdnjs (HTML mode, line numbers, search); plain textarea if it can't load */
  var CM_BASE = 'https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.16/';
  var cmLoading = null;
  function loadCodeMirror() {
    if (window.CodeMirror && window.CodeMirror.modes && window.CodeMirror.modes.htmlmixed && window.CodeMirror.commands.find) return Promise.resolve(window.CodeMirror);
    if (cmLoading) return cmLoading;
    function css(h) { var l = document.createElement('link'); l.rel = 'stylesheet'; l.href = CM_BASE + h; document.head.appendChild(l); }
    function js(h) { return new Promise(function (res, rej) { var x = document.createElement('script'); x.src = CM_BASE + h; x.onload = res; x.onerror = function () { rej(new Error('Could not load ' + h)); }; document.head.appendChild(x); }); }
    css('codemirror.min.css'); css('addon/dialog/dialog.min.css');
    cmLoading = js('codemirror.min.js')
      .then(function () { return Promise.all(['mode/xml/xml.min.js', 'mode/javascript/javascript.min.js', 'mode/css/css.min.js', 'addon/dialog/dialog.min.js', 'addon/search/searchcursor.min.js'].map(js)); })
      .then(function () { return Promise.all(['mode/htmlmixed/htmlmixed.min.js', 'addon/search/search.min.js', 'addon/search/jump-to-line.min.js'].map(js)); })
      .then(function () { return window.CodeMirror; });
    cmLoading.catch(function () { cmLoading = null; });
    return cmLoading;
  }
  function htmlEditor(f, vals, onChange, readOnly) {
    var wrap = el('<div class="f" data-key="' + esc(f.key) + '"><span class="f__label">Page HTML</span>' +
      '<div class="notice notice--blue" style="margin:0">' + ic('info') + '<span>Paste only this page’s content. The header and footer are shared — use <code>{{header}}</code> and <code>{{footer}}</code>. Use <code>{{media:file-name.jpg}}</code> or a Media Library URL for images.</span></div>' +
      '<textarea class="in in--code" spellcheck="false" rows="30" style="min-height:60vh;white-space:pre;overflow:auto"></textarea>' +
      '<p class="f__help" data-cm-status>Loading the code editor… Search: Ctrl/Cmd + F · Jump to line: Alt + G · Save: Ctrl/Cmd + S</p></div>');
    var ta = $('textarea', wrap);
    ta.value = vals[f.key] || '';
    ta.readOnly = !!readOnly;
    ta.addEventListener('input', function () { vals[f.key] = ta.value; onChange(f.key); });
    loadCodeMirror().then(function (CM) {
      if (!document.body.contains(ta)) return;
      var cm = CM.fromTextArea(ta, { mode: 'htmlmixed', lineNumbers: true, lineWrapping: true, readOnly: !!readOnly, indentUnit: 2, extraKeys: { 'Alt-G': 'jumpToLine' } });
      cm.setSize(null, '70vh');
      cm.on('change', function () { vals[f.key] = cm.getValue(); onChange(f.key); });
      $('[data-cm-status]', wrap).textContent = 'Search: Ctrl/Cmd + F · Jump to line: Alt + G · Save: Ctrl/Cmd + S';
    }).catch(function () {
      var st = $('[data-cm-status]', wrap);
      if (st) st.textContent = 'Plain text editor (the code editor could not be loaded). Save: Ctrl/Cmd + S';
    });
    return wrap;
  }
  document.addEventListener('keydown', function (e) {
    if ((e.ctrlKey || e.metaKey) && e.key.toLowerCase() === 's' && saveHandler && document.body.contains($('[data-save]'))) { e.preventDefault(); saveHandler(); }
    if ((e.ctrlKey || e.metaKey) && e.key.toLowerCase() === 'k' && S.user) { e.preventDefault(); openPalette(); }
  });

  /* =====================================================================
     Field builder
     ===================================================================== */
  var uid = 0;
  function buildField(f, vals, onChange, ctx) {
    if (f.type === 'hidden') return null;
    var id = 'f' + (++uid);
    var wrap = el('<div class="f' + (f.half ? ' f--half' : '') + (f.third ? ' f--third' : '') + '" data-key="' + esc(f.key) + '"></div>');
    var label = '<label for="' + id + '">' + esc(f.label) + (f.required ? '<span class="f__req">*</span>' : '') + (f.max ? '<span class="f__count" data-count></span>' : '') + '</label>';
    var help = f.help ? '<p class="f__help">' + esc(f.help) + '</p>' : '';
    var v = vals[f.key];
    function set(nv) { vals[f.key] = nv; onChange(f.key); }

    switch (f.type) {
      case 'text':
        wrap.innerHTML = label + '<input class="in" id="' + id + '" value="' + esc(v) + '" placeholder="' + esc(f.placeholder || '') + '">' + help;
        $('input', wrap).addEventListener('input', function (e) { set(e.target.value); counter(); });
        break;
      case 'textarea': case 'lines':
        wrap.innerHTML = label + '<textarea class="in" id="' + id + '" rows="' + (f.rows || (f.type === 'lines' ? 6 : 4)) + '" placeholder="' + esc(f.placeholder || (f.type === 'lines' ? 'One item per line' : '')) + '">' + esc(v) + '</textarea>' + help;
        $('textarea', wrap).addEventListener('input', function (e) { set(e.target.value); counter(); });
        break;
      case 'code':
        wrap.innerHTML = label + '<textarea class="in in--code" id="' + id + '" rows="' + (f.rows || 6) + '" spellcheck="false" placeholder="' + esc(f.placeholder || '<script>…</script>') + '">' + esc(v) + '</textarea>' + help;
        $('textarea', wrap).addEventListener('input', function (e) { set(e.target.value); });
        break;
      case 'slug':
        wrap.innerHTML = label + '<div class="prefix"><span>' + esc(f.prefix || '/') + '</span><input id="' + id + '" value="' + esc(v) + '" spellcheck="false" placeholder="auto-generated"></div>' + help;
        var si = $('input', wrap), touched = !ctx.isNew || !!v;
        si.addEventListener('input', function () { touched = true; set(slugify(si.value)); });
        si.addEventListener('blur', function () { si.value = slugify(si.value); });
        if (ctx.isNew) document.addEventListener('dash:title', function (e) { if (!touched && document.body.contains(si)) { si.value = slugify(e.detail); vals[f.key] = si.value; } });
        break;
      case 'select':
        wrap.innerHTML = label + '<select class="in" id="' + id + '">' + f.options.map(function (o) { return '<option value="' + esc(o.value) + '"' + (String(o.value) === String(v) ? ' selected' : '') + '>' + esc(o.label) + '</option>'; }).join('') + '</select>' + help;
        if (v === '' || v == null) vals[f.key] = $('select', wrap).value;
        $('select', wrap).addEventListener('change', function (e) { set(e.target.value); });
        break;
      case 'toggle':
        wrap.innerHTML = '<label class="toggle"><span>' + esc(f.label) + '</span><input type="checkbox" id="' + id + '"' + (v ? ' checked' : '') + '><span class="toggle__ui"></span></label>' + help;
        $('input', wrap).addEventListener('change', function (e) { set(e.target.checked); });
        break;
      case 'checks':
        var cur = Array.isArray(v) ? v : [];
        wrap.innerHTML = '<span class="f__label">' + esc(f.label) + '</span><div class="checks">' + f.options.map(function (o) { return '<label><input type="checkbox" value="' + esc(o.value) + '"' + (cur.indexOf(o.value) > -1 ? ' checked' : '') + '>' + esc(o.label) + '</label>'; }).join('') + '</div>' + help;
        wrap.addEventListener('change', function () { set($$('input:checked', wrap).map(function (c) { return c.value; })); });
        break;
      case 'color':
        wrap.innerHTML = label + '<div class="color-row"><input type="color" value="' + esc(v || '#000000') + '"><input class="in mono" id="' + id + '" value="' + esc(v) + '" maxlength="7"></div>' + help;
        var cp = $('input[type=color]', wrap), ct = $('input.in', wrap);
        cp.addEventListener('input', function () { ct.value = cp.value; set(cp.value); });
        ct.addEventListener('input', function () { if (/^#[0-9a-f]{6}$/i.test(ct.value)) { cp.value = ct.value; set(ct.value); } });
        break;
      case 'richtext':
        wrap.innerHTML = '<span class="f__label">' + esc(f.label) + '</span>';
        wrap.appendChild(richText(v || '', set));
        if (help) wrap.appendChild(el(help));
        break;
      case 'repeater':
        wrap.innerHTML = '<span class="f__label">' + esc(f.label) + '</span>' + help;
        wrap.appendChild(repeater(f, Array.isArray(v) ? v : [], set));
        break;
      case 'relation':
        wrap.innerHTML = '<span class="f__label">' + esc(f.label) + '</span>';
        wrap.appendChild(relation(f, Array.isArray(v) ? v : [], set));
        if (help) wrap.appendChild(el(help));
        break;
      case 'image': case 'file':
        wrap.innerHTML = '<span class="f__label">' + esc(f.label) + '</span>';
        wrap.appendChild(f.type === 'image' ? imageField(f, vals, set, onChange) : fileField(f, v, set));
        if (help) wrap.appendChild(el(help));
        break;
      default:
        wrap.innerHTML = label + '<input class="in" id="' + id + '" value="' + esc(v) + '">' + help;
        $('input', wrap).addEventListener('input', function (e) { set(e.target.value); });
    }
    if (ctx && ctx.def && f.key === ctx.def.titleKey) {
      var ti = $('input', wrap);
      if (ti) ti.addEventListener('input', function () { document.dispatchEvent(new CustomEvent('dash:title', { detail: ti.value })); });
    }
    function counter() {
      if (!f.max) return;
      var c = $('[data-count]', wrap), n = String(vals[f.key] || '').length;
      c.textContent = n + ' / ' + f.max; c.classList.toggle('over', n > f.max);
    }
    counter();
    return wrap;
  }

  /* Rich text editor (contenteditable) */
  function richText(html, onChange) {
    var w = el('<div class="rte"><div class="rte__bar" role="toolbar" aria-label="Formatting"></div><div class="rte__area" contenteditable="true" data-placeholder="Start writing…"></div><textarea class="rte__src" hidden spellcheck="false"></textarea></div>');
    var bar = $('.rte__bar', w), area = $('.rte__area', w), src = $('.rte__src', w);
    area.innerHTML = html;
    var tools = [
      ['p', 'P', 'Paragraph'], ['h2', 'H2', 'Heading'], ['h3', 'H3', 'Subheading'], '|',
      ['bold', ic('bold'), 'Bold'], ['italic', ic('italic'), 'Italic'], ['underline', ic('underline'), 'Underline'], '|',
      ['ul', ic('list-ul'), 'Bulleted list'], ['ol', ic('list-ol'), 'Numbered list'], ['quote', ic('quote'), 'Quote'], '|',
      ['link', ic('link'), 'Insert link'], ['unlink', ic('unlink'), 'Remove link'], ['image', ic('image'), 'Insert image from library'], '|',
      ['clear', ic('eraser'), 'Clear formatting'], ['undo', ic('undo'), 'Undo'], ['redo', ic('redo'), 'Redo'], ['source', ic('code'), 'Edit HTML']
    ];
    bar.innerHTML = tools.map(function (t) { return t === '|' ? '<span class="sep"></span>' : '<button type="button" data-cmd="' + t[0] + '" title="' + t[2] + '" aria-label="' + t[2] + '">' + t[1] + '</button>'; }).join('');
    function changed() { onChange(src.hidden ? area.innerHTML : src.value); }
    function exec(cmd, arg) { area.focus(); document.execCommand(cmd, false, arg); changed(); refresh(); }
    var savedRange = null;
    function saveSel() { var s = window.getSelection(); if (s.rangeCount && area.contains(s.anchorNode)) savedRange = s.getRangeAt(0).cloneRange(); }
    function restoreSel() { if (savedRange) { var s = window.getSelection(); s.removeAllRanges(); s.addRange(savedRange); } }
    bar.addEventListener('mousedown', function (e) { if (e.target.closest('button')) e.preventDefault(); });
    bar.addEventListener('click', function (e) {
      var b = e.target.closest('[data-cmd]'); if (!b) return;
      var c = b.getAttribute('data-cmd');
      if (c === 'source') {
        if (src.hidden) { src.value = area.innerHTML; src.hidden = false; area.hidden = true; b.classList.add('is-on'); }
        else { area.innerHTML = src.value; src.hidden = true; area.hidden = false; b.classList.remove('is-on'); changed(); }
        return;
      }
      if (!src.hidden) return;
      if (c === 'p' || c === 'h2' || c === 'h3') exec('formatBlock', '<' + c + '>');
      else if (c === 'quote') exec('formatBlock', '<blockquote>');
      else if (c === 'ul') exec('insertUnorderedList');
      else if (c === 'ol') exec('insertOrderedList');
      else if (c === 'clear') { exec('removeFormat'); exec('formatBlock', '<p>'); }
      else if (c === 'link') {
        saveSel();
        promptBox('Insert link', 'URL (e.g. /services/chiropractic-care/ or https://…)', '').then(function (url) {
          if (!url) return;
          restoreSel();
          var s = window.getSelection();
          if (s.isCollapsed) exec('insertHTML', '<a href="' + esc(url) + '">' + esc(url) + '</a>'); else exec('createLink', url);
        });
      } else if (c === 'image') {
        saveSel();
        mediaPicker({ kind: 'image' }).then(function (m) {
          if (!m) return;
          restoreSel();
          exec('insertHTML', '<img src="' + esc(m.url) + '" alt="' + esc(m.alt || m.title || '') + '"' + (m.width ? ' width="' + m.width + '" height="' + m.height + '"' : '') + '>');
        });
      } else exec(c);
    });
    area.addEventListener('input', changed);
    src.addEventListener('input', changed);
    area.addEventListener('paste', function (e) {
      var html = e.clipboardData.getData('text/html'), text = e.clipboardData.getData('text/plain');
      e.preventDefault();
      if (html) {
        var d = document.createElement('div'); d.innerHTML = html;
        $$('script,style,meta,link,title,xml,o\\:p', d).forEach(function (n) { n.remove(); });
        $$('*', d).forEach(function (n) {
          Array.prototype.slice.call(n.attributes).forEach(function (a) { if (['href', 'src', 'alt'].indexOf(a.name) < 0) n.removeAttribute(a.name); });
          if (/^(SPAN|FONT|DIV)$/.test(n.tagName)) { if (n.tagName === 'DIV') { var p = document.createElement('p'); p.innerHTML = n.innerHTML; n.replaceWith(p); } else n.replaceWith.apply(n, Array.prototype.slice.call(n.childNodes)); }
          if (n.tagName === 'H1') { var h = document.createElement('h2'); h.innerHTML = n.innerHTML; n.replaceWith(h); }
        });
        document.execCommand('insertHTML', false, d.innerHTML);
      } else {
        document.execCommand('insertHTML', false, text.split(/\n{2,}/).map(function (p) { return '<p>' + esc(p).replace(/\n/g, '<br>') + '</p>'; }).join(''));
      }
      changed();
    });
    function refresh() {
      ['bold', 'italic', 'underline'].forEach(function (c) { var b = $('[data-cmd="' + c + '"]', bar); try { b.classList.toggle('is-on', document.queryCommandState(c)); } catch (x) {} });
    }
    document.addEventListener('selectionchange', function () { if (document.activeElement === area) refresh(); });
    return w;
  }
  function promptBox(title, label, value) {
    return new Promise(function (resolve) {
      var body = el('<div class="f"><label>' + esc(label) + '</label><input class="in" value="' + esc(value) + '"></div>');
      var input = $('input', body);
      var m = modal({ title: title, body: body, onClose: function (v) { resolve(v); }, buttons: [{ label: 'Cancel', value: null }, { label: 'Insert', cls: 'btn--primary', onClick: function (close) { close(input.value.trim()); } }] });
      input.addEventListener('keydown', function (e) { if (e.key === 'Enter') { e.preventDefault(); m.close(input.value.trim()); } });
    });
  }

  /* Repeater rows */
  function repeater(f, rows, set) {
    rows = rows.map(function (r) { return Object.assign({}, r); });
    var w = el('<div class="rep"><div data-rows></div><div><button type="button" class="btn btn--ghost btn--sm" data-add>' + ic('plus') + esc(f.add || 'Add row') + '</button></div></div>');
    var box = $('[data-rows]', w);
    var stack = f.fields.some(function (sf) { return sf.type === 'textarea'; });
    function render() {
      box.innerHTML = '';
      box.style.display = 'grid'; box.style.gap = '10px';
      rows.forEach(function (r, i) {
        var row = el('<div class="rep__row"><div class="rep__head"><span>' + esc(f.label.replace(/s$/, '')) + ' ' + (i + 1) + '</span>' +
          '<button type="button" class="btn btn--sm btn--plain btn--icon" data-up title="Move up"' + (i ? '' : ' disabled') + '>' + ic('chevron-up') + '</button>' +
          '<button type="button" class="btn btn--sm btn--plain btn--icon" data-down title="Move down"' + (i < rows.length - 1 ? '' : ' disabled') + '>' + ic('chevron-down') + '</button>' +
          '<button type="button" class="btn btn--sm btn--plain btn--icon" data-rm title="Remove" style="color:var(--red);margin-left:0">' + ic('trash') + '</button></div>' +
          '<div class="rep__cols' + (stack ? ' rep__cols--stack' : '') + '"></div></div>');
        var cols = $('.rep__cols', row);
        f.fields.forEach(function (sf) {
          var v = r[sf.key] == null ? '' : r[sf.key];
          var c = el('<div class="f"><label>' + esc(sf.label) + '</label>' + (sf.type === 'textarea' ? '<textarea class="in" rows="' + (sf.rows || 3) + '"></textarea>' : '<input class="in">') + '</div>');
          var inp = $('.in', c); inp.value = v;
          inp.addEventListener('input', function () { r[sf.key] = inp.value; set(rows); });
          cols.appendChild(c);
        });
        $('[data-rm]', row).addEventListener('click', function () { rows.splice(i, 1); set(rows); render(); });
        $('[data-up]', row).addEventListener('click', function () { rows.splice(i - 1, 0, rows.splice(i, 1)[0]); set(rows); render(); });
        $('[data-down]', row).addEventListener('click', function () { rows.splice(i + 1, 0, rows.splice(i, 1)[0]); set(rows); render(); });
        box.appendChild(row);
      });
      if (!rows.length) box.innerHTML = '<p class="muted" style="margin:0;font-size:.86rem">Nothing added yet.</p>';
    }
    $('[data-add]', w).addEventListener('click', function () { var r = {}; f.fields.forEach(function (sf) { r[sf.key] = ''; }); rows.push(r); set(rows); render(); var ins = $$('.rep__row', box); var last = ins[ins.length - 1]; if (last) $('.in', last).focus(); });
    render();
    return w;
  }

  /* Relation picker (chips + type-ahead, drag to reorder) */
  function relation(f, selected, set) {
    selected = selected.slice();
    var w = el('<div class="rel"><div class="rel__chips"></div><div class="rel__search"><input placeholder="Search to add…" aria-label="Add ' + esc(f.label) + '"><div class="rel__drop" hidden></div></div></div>');
    var chips = $('.rel__chips', w), input = $('input', w), drop = $('.rel__drop', w);
    var opts = [];
    function labelOf(v) { var o = opts.filter(function (x) { return x.value === v; })[0]; return o ? o.label : v; }
    function render() {
      chips.innerHTML = selected.length ? selected.map(function (v, i) { return '<span class="rel__chip" draggable="true" data-i="' + i + '">' + esc(labelOf(v)) + '<button type="button" data-rm="' + i + '" aria-label="Remove">' + ic('x') + '</button></span>'; }).join('') : '<span class="muted" style="font-size:.84rem;padding:4px">None selected</span>';
    }
    var hl = 0;
    function showDrop() {
      var q = input.value.toLowerCase();
      var list = opts.filter(function (o) { return selected.indexOf(o.value) < 0 && (!q || o.label.toLowerCase().indexOf(q) > -1); }).slice(0, 30);
      hl = 0;
      drop.innerHTML = list.length ? list.map(function (o, i) { return '<button type="button" data-v="' + esc(o.value) + '"' + (i === 0 ? ' class="is-hl"' : '') + '><span>' + esc(o.label) + '</span>' + (o.category ? '<small>' + esc(o.category) + '</small>' : '') + '</button>'; }).join('') : '<div class="muted" style="padding:10px 12px;font-size:.85rem">No matches</div>';
      drop.hidden = false;
    }
    function add(v) { if (v && selected.indexOf(v) < 0) { selected.push(v); set(selected.slice()); render(); } input.value = ''; showDrop(); input.focus(); }
    chips.addEventListener('click', function (e) { var b = e.target.closest('[data-rm]'); if (b) { selected.splice(+b.getAttribute('data-rm'), 1); set(selected.slice()); render(); } });
    var dragI = null;
    chips.addEventListener('dragstart', function (e) { var c = e.target.closest('[data-i]'); if (c) dragI = +c.getAttribute('data-i'); });
    chips.addEventListener('dragover', function (e) { e.preventDefault(); });
    chips.addEventListener('drop', function (e) { e.preventDefault(); var c = e.target.closest('[data-i]'); if (c && dragI !== null) { var to = +c.getAttribute('data-i'); selected.splice(to, 0, selected.splice(dragI, 1)[0]); set(selected.slice()); render(); } dragI = null; });
    input.addEventListener('focus', showDrop);
    input.addEventListener('input', showDrop);
    input.addEventListener('keydown', function (e) {
      var items = $$('button', drop);
      if (e.key === 'ArrowDown' || e.key === 'ArrowUp') { e.preventDefault(); hl = Math.max(0, Math.min(items.length - 1, hl + (e.key === 'ArrowDown' ? 1 : -1))); items.forEach(function (b, i) { b.classList.toggle('is-hl', i === hl); }); if (items[hl]) items[hl].scrollIntoView({ block: 'nearest' }); }
      else if (e.key === 'Enter') { e.preventDefault(); if (items[hl]) add(items[hl].getAttribute('data-v')); }
      else if (e.key === 'Escape') drop.hidden = true;
    });
    drop.addEventListener('mousedown', function (e) { e.preventDefault(); var b = e.target.closest('[data-v]'); if (b) add(b.getAttribute('data-v')); });
    input.addEventListener('blur', function () { setTimeout(function () { drop.hidden = true; }, 120); });
    var cats = {}; (S.schema.resources.services.fields.filter(function (x) { return x.key === 'category'; })[0] || { options: [] }).options.forEach(function (o) { cats[o.value] = o.label; });
    (S.relCache[f.source] ? Promise.resolve(S.relCache[f.source]) : get('res.options', { type: f.source }).then(function (r) { S.relCache[f.source] = r; return r; })).then(function (r) {
      opts = r.map(function (o) { return { value: String(o.value), label: o.label, category: cats[o.category] || '' }; });
      render();
    });
    render();
    return w;
  }

  /* Image field with media-library picker and optional focal point */
  function imageField(f, vals, set, onChange) {
    var w = el('<div class="imgf"><div class="imgf__preview"></div><div class="imgf__actions"><button type="button" class="btn btn--ghost btn--sm" data-pick>' + ic('image') + 'Choose image</button><button type="button" class="btn btn--plain btn--sm" data-rm>' + ic('x') + 'Remove</button></div></div>');
    var prev = $('.imgf__preview', w);
    function render() {
      var v = vals[f.key];
      $('[data-rm]', w).hidden = !v;
      if (!v) { prev.classList.remove('is-focal'); prev.innerHTML = '<div class="imgf__empty">' + ic('image') + '<span>No image selected</span></div>'; return; }
      prev.innerHTML = '<img src="' + esc(mediaUrl(v)) + '" alt="">';
      if (f.focal) {
        prev.classList.add('is-focal');
        var pos = String(vals[f.focal] || '50% 20%').split(' ');
        $('img', prev).style.objectPosition = pos.join(' ');
        prev.appendChild(el('<span class="imgf__dot" style="left:' + parseFloat(pos[0]) + '%;top:' + parseFloat(pos[1] || 50) + '%"></span>'));
      }
    }
    prev.addEventListener('click', function (e) {
      if (!f.focal || !vals[f.key]) { $('[data-pick]', w).click(); return; }
      var r = prev.getBoundingClientRect();
      var px = Math.round((e.clientX - r.left) / r.width * 100), py = Math.round((e.clientY - r.top) / r.height * 100);
      vals[f.focal] = px + '% ' + py + '%';
      onChange(f.focal);
      render();
    });
    $('[data-pick]', w).addEventListener('click', function () { mediaPicker({ kind: 'image' }).then(function (m) { if (m) { set(m.path); render(); } }); });
    $('[data-rm]', w).addEventListener('click', function () { set(''); render(); });
    render();
    return w;
  }
  function fileField(f, v, set) {
    var w = el('<div class="filef">' + ic('file') + '<span class="filef__name"></span><button type="button" class="btn btn--ghost btn--sm" data-pick>Choose file</button><button type="button" class="btn btn--plain btn--sm btn--icon" data-rm title="Remove">' + ic('x') + '</button></div>');
    function render() {
      $('.filef__name', w).innerHTML = v ? '<a href="' + esc(mediaUrl(v)) + '" target="_blank" rel="noopener">' + esc(String(v).split('/').pop()) + '</a>' : '<span class="muted">No file selected</span>';
      $('[data-rm]', w).hidden = !v;
    }
    $('[data-pick]', w).addEventListener('click', function () { mediaPicker({ kind: 'document' }).then(function (m) { if (m) { v = m.path; set(v); render(); } }); });
    $('[data-rm]', w).addEventListener('click', function () { v = ''; set(''); render(); });
    render();
    return w;
  }

  /* =====================================================================
     Media library (page + picker)
     ===================================================================== */
  function mediaBrowser(container, opts) {
    opts = opts || {};
    var st = { q: '', kind: opts.kind && opts.kind !== 'any' ? opts.kind : '', page: 1, items: [], selected: null, multi: {} };
    var accept = st.kind === 'image' ? 'image/*' : st.kind === 'document' ? '.pdf,.doc,.docx,.xls,.xlsx' : '';
    container.innerHTML =
      (can('media.upload') ? '<label class="drop" data-drop><input type="file" multiple hidden accept="' + accept + '">' + ic('upload') + '<strong>Drop files here or click to upload</strong><span>Images are optimised automatically (resized + WebP). PDFs, documents and videos are supported.</span></label><div class="uploads" data-uploads></div>' : '') +
      '<div class="card"><div class="toolbar"><label class="search-in">' + ic('search') + '<input type="search" placeholder="Search files…" data-mq></label>' +
      '<select class="sel" data-kind><option value="">All files</option><option value="image">Images</option><option value="document">Documents</option><option value="video">Videos</option></select>' +
      '<div class="bulk" data-mbulk hidden><span data-mcount></span>' + (opts.picker ? '' : '<button class="btn btn--sm btn--danger" data-mdel>' + ic('trash') + 'Delete</button>') + '</div>' +
      '<span class="muted" style="font-size:.82rem;margin-left:auto" data-mstats></span></div>' +
      '<div class="card__b"><div class="mgrid" data-grid></div><div style="text-align:center;margin-top:16px" data-more></div></div></div>';
    $('[data-kind]', container).value = st.kind;
    var grid = $('[data-grid]', container);
    function load(append) {
      if (!append) { st.page = 1; grid.innerHTML = '<div class="skel" style="height:120px;grid-column:1/-1"></div>'; }
      get('media.list', { q: st.q, kind: st.kind, page: st.page, per: 48 }).then(function (d) {
        st.items = append ? st.items.concat(d.items) : d.items;
        $('[data-mstats]', container).textContent = d.total + ' files · ' + bytes(d.bytes);
        render();
        $('[data-more]', container).innerHTML = d.page < d.pages ? '<button class="btn btn--ghost" data-loadmore>Load more</button>' : '';
      }).catch(function (e) { grid.innerHTML = '<div class="empty-s">' + esc(e.message) + '</div>'; });
    }
    function thumb(m) {
      var ext = m.path.split('.').pop();
      return '<button type="button" class="mitem' + ((st.selected && st.selected.id === m.id) || st.multi[m.id] ? ' is-selected' : '') + '" data-mid="' + m.id + '" title="' + esc(m.original_name) + '">' +
        '<span class="mitem__thumb">' + (m.kind === 'image' ? '<img src="' + esc(m.thumb_url) + '" alt="" loading="lazy">' : ic(m.kind === 'video' ? 'video' : 'file-text')) + '</span>' +
        '<span class="mitem__check">' + ic('check') + '</span><span class="mitem__ext">' + esc(ext) + '</span><span class="mitem__name">' + esc(m.title || m.original_name) + '</span></button>';
    }
    function render() {
      grid.innerHTML = st.items.length ? st.items.map(thumb).join('') : '<div class="empty-s" style="grid-column:1/-1">' + ic('image') + 'No files found.</div>';
      var n = Object.keys(st.multi).length;
      $('[data-mbulk]', container).hidden = !n;
      $('[data-mcount]', container).textContent = n + ' selected';
    }
    grid.addEventListener('click', function (e) {
      var b = e.target.closest('[data-mid]'); if (!b) return;
      var m = st.items.filter(function (x) { return x.id === +b.getAttribute('data-mid'); })[0];
      if (!opts.picker && (e.shiftKey || e.ctrlKey || e.metaKey)) { if (st.multi[m.id]) delete st.multi[m.id]; else st.multi[m.id] = 1; render(); return; }
      st.multi = {};
      st.selected = m;
      render();
      if (opts.onSelect) opts.onSelect(m);
    });
    grid.addEventListener('dblclick', function (e) { var b = e.target.closest('[data-mid]'); if (b && opts.onPick) opts.onPick(st.selected); });
    container.addEventListener('click', function (e) {
      if (e.target.closest('[data-loadmore]')) { st.page++; load(true); }
      if (e.target.closest('[data-mdel]')) {
        var ids = Object.keys(st.multi);
        confirmBox('Delete ' + ids.length + ' file(s)?', 'Pages using these files will show broken images. This cannot be undone.', 'Delete', true).then(function (y) {
          if (!y) return;
          api('media.delete', { ids: ids }).then(function (r) { toast(r.deleted + ' file(s) deleted.'); st.multi = {}; load(); }).catch(function (er) { toast(er.message, 'err'); });
        });
      }
    });
    $('[data-mq]', container).addEventListener('input', debounce(function (e) { st.q = e.target.value; load(); }, 250));
    $('[data-kind]', container).addEventListener('change', function (e) { st.kind = e.target.value; load(); });
    var dz = $('[data-drop]', container);
    if (dz) {
      var fi = $('input[type=file]', dz);
      fi.addEventListener('change', function () { handleFiles(fi.files); fi.value = ''; });
      ['dragenter', 'dragover'].forEach(function (ev) { dz.addEventListener(ev, function (e) { e.preventDefault(); dz.classList.add('is-over'); }); });
      ['dragleave', 'drop'].forEach(function (ev) { dz.addEventListener(ev, function (e) { e.preventDefault(); dz.classList.remove('is-over'); }); });
      dz.addEventListener('drop', function (e) { handleFiles(e.dataTransfer.files); });
    }
    function handleFiles(files) {
      var list = $('[data-uploads]', container);
      var queue = Array.prototype.slice.call(files);
      (function next() {
        var file = queue.shift(); if (!file) { load(); return; }
        var row = el('<div class="up"><span>' + esc(file.name) + ' <span class="muted">· ' + bytes(file.size) + '</span></span><span data-pct>0%</span><div class="up__bar"><span style="width:0"></span></div></div>');
        list.prepend(row);
        if (S.schema.upload_max && file.size > S.schema.upload_max) { row.classList.add('is-error'); $('[data-pct]', row).textContent = 'Too large (max ' + bytes(S.schema.upload_max) + ')'; return next(); }
        upload(file, function (p) { $('.up__bar span', row).style.width = Math.round(p * 100) + '%'; $('[data-pct]', row).textContent = Math.round(p * 100) + '%'; }).then(function (m) {
          row.classList.add('is-done'); $('[data-pct]', row).textContent = 'Uploaded';
          setTimeout(function () { row.remove(); }, 2500);
          if (opts.onUploaded) opts.onUploaded(m);
          next();
        }).catch(function (e) { row.classList.add('is-error'); $('[data-pct]', row).textContent = e.message; next(); });
      })();
    }
    load();
    return { reload: load, select: function (m) { st.selected = m; render(); } };
  }

  function mediaPicker(opts) {
    return new Promise(function (resolve) {
      var body = el('<div></div>');
      var chosen = null;
      var m = modal({
        title: opts.kind === 'image' ? 'Choose an image' : 'Choose a file', body: body, size: 'wide', onClose: function (v) { resolve(v || null); },
        buttons: [{ label: 'Cancel', value: null }, { label: 'Use selected', cls: 'btn--primary', onClick: function (close) { if (chosen) close(chosen); else toast('Select a file first.', 'err'); } }]
      });
      mediaBrowser(body, { picker: true, kind: opts.kind, onSelect: function (x) { chosen = x; }, onPick: function (x) { m.close(x); }, onUploaded: function (x) { chosen = x; } });
    });
  }

  function viewMedia(view) {
    document.title = 'Media Library · ' + A.siteName;
    view.innerHTML = header('Media Library', 'All images, PDFs and documents used on the website. Shift/Ctrl-click to select several.', can('content.edit') ? '<button class="btn btn--primary" data-autoassign>' + ic('sparkles') + 'Auto-assign images</button>' : '') + '<div class="media-layout"><div data-browser></div><div class="mdetail" data-detail></div></div>';
    var aa = $('[data-autoassign]', view);
    if (aa) aa.addEventListener('click', autoAssign);
    var detail = $('[data-detail]', view);
    function showDetail(m) {
      if (!m) { detail.innerHTML = '<div class="card"><div class="empty-s">' + ic('image') + 'Select a file to see its details.</div></div>'; return; }
      detail.innerHTML = '<div class="card"><div class="mdetail__preview">' + (m.kind === 'image' ? '<img src="' + esc(m.url) + '" alt="">' : ic(m.kind === 'video' ? 'video' : 'file-text')) + '</div><div class="card__b" style="display:grid;gap:14px">' +
        '<div><strong style="color:var(--ink);word-break:break-all">' + esc(m.original_name) + '</strong></div>' +
        '<div class="kv"><div><span>Type</span><strong>' + esc(m.mime) + '</strong></div><div><span>Size</span><strong>' + bytes(m.size) + '</strong></div>' + (m.width ? '<div><span>Dimensions</span><strong>' + m.width + ' × ' + m.height + '</strong></div>' : '') + '<div><span>WebP version</span><strong>' + (m.webp ? 'Yes' : '—') + '</strong></div><div><span>Uploaded</span><strong>' + fullDate(m.created_at) + '</strong></div></div>' +
        '<div class="f"><label>File URL</label><div class="prefix"><input readonly value="' + esc(m.abs_url) + '"><button class="btn btn--plain btn--sm" data-copy style="border-radius:0">' + ic('copy') + '</button></div></div>' +
        '<div class="f"><label>Title</label><input class="in" data-title value="' + esc(m.title) + '"></div>' +
        (m.kind === 'image' ? '<div class="f"><label>Alt text</label><textarea class="in" rows="2" data-alt>' + esc(m.alt) + '</textarea><p class="f__help">Describe the image for accessibility and SEO.</p></div>' : '') +
        '<div class="pub__row">' + (can('media.upload') ? '<button class="btn btn--primary" data-msave>' + ic('check') + 'Save</button>' : '') + '<a class="btn btn--ghost" href="' + esc(m.url) + '" target="_blank" rel="noopener">' + ic('external') + 'Open</a>' + (can('media.delete') || m.uploaded_by === S.user.id ? '<button class="btn btn--danger btn--icon" data-mdel1 title="Delete">' + ic('trash') + '</button>' : '') + '</div>' +
        (can('media.upload') && (can('media.delete') || m.uploaded_by === S.user.id) ? '<div class="f"><label class="btn btn--ghost btn--block" data-mreplace>' + ic('upload') + '<span data-mreplace-label>Replace file…</span><input type="file" hidden></label><p class="f__help">Upload a new version. Every page using this file switches to the new one automatically.</p></div>' : '') +
        '</div></div>';
      $('[data-copy]', detail).addEventListener('click', function () { navigator.clipboard && navigator.clipboard.writeText(m.abs_url).then(function () { toast('URL copied.'); }); });
      var sv = $('[data-msave]', detail);
      if (sv) sv.addEventListener('click', function () {
        busy(sv, true);
        var alt = $('[data-alt]', detail);
        api('media.update', { id: m.id, title: $('[data-title]', detail).value, alt: alt ? alt.value : '' }).then(function (u) { busy(sv, false); Object.assign(m, u); toast('Saved.'); }).catch(function (e) { busy(sv, false); toast(e.message, 'err'); });
      });
      var rp = $('[data-mreplace] input', detail);
      if (rp) rp.addEventListener('change', function () {
        var file = rp.files && rp.files[0];
        if (!file) return;
        var lbl = $('[data-mreplace-label]', detail);
        lbl.textContent = 'Uploading… 0%';
        upload(file, function (p) { lbl.textContent = 'Uploading… ' + Math.round(p * 100) + '%'; }, 'media.replace', { id: m.id }).then(function (nm) {
          toast('File replaced.' + (nm.updated_refs ? ' Updated ' + nm.updated_refs + ' place(s) that use it.' : ''));
          browser.reload();
          showDetail(nm);
        }).catch(function (e) { lbl.textContent = 'Replace file…'; rp.value = ''; toast(e.message, 'err'); });
      });
      var dl = $('[data-mdel1]', detail);
      if (dl) dl.addEventListener('click', function () {
        confirmBox('Delete this file?', 'Any page or setting using it will be cleared. To swap in a new version instead, use Replace file.', 'Delete', true).then(function (y) {
          if (!y) return;
          api('media.delete', { ids: [m.id] }).then(function (r) { toast('File deleted.' + (r.cleared_refs ? ' Removed it from ' + r.cleared_refs + ' place(s) that used it.' : '')); showDetail(null); browser.reload(); }).catch(function (e) { toast(e.message, 'err'); });
        });
      });
    }
    var browser = mediaBrowser($('[data-browser]', view), { onSelect: showDetail, onUploaded: function (m) { showDetail(m); } });
    showDetail(null);
  }

  /* Match uploaded images to treatments/pages/settings by file name */
  function autoAssign() {
    var body = el('<div><div class="notice notice--blue">' + ic('info') + '<span>Images are matched by file name: <b>regenerative-medicine.jpg</b> → Regenerative Medicine, <b>page-contact-us.jpg</b> → Contact page, <b>location-main.jpg</b>, <b>provider-dr-jane-smith.jpg</b>, <b>home-hero.jpg</b>, <b>home-integrated-care.jpg</b>, <b>social-share.jpg</b>, <b>logo.png</b>. Treatment titles also work as file names.</span></div>' +
      '<label class="toggle" style="margin-bottom:14px"><span>Replace images that are already set</span><input type="checkbox" data-ow><span class="toggle__ui"></span></label><div data-res><div class="skel"></div></div></div>');
    var res = $('[data-res]', body), ow = $('[data-ow]', body);
    function render(d) {
      if (!d.matches.length) { res.innerHTML = '<div class="empty-s">' + ic('image') + 'No matching file names found among ' + d.files + ' images. Upload images named after the treatment first.</div>'; return; }
      var todo = d.matches.filter(function (m) { return m.status === 'will assign' || m.status === 'assigned'; }).length;
      res.innerHTML = '<p class="muted" style="margin:0 0 10px">' + d.matches.length + ' matches · <strong style="color:var(--ink)">' + todo + '</strong> to assign</p><div class="card"><ul class="list">' + d.matches.map(function (m) {
        var tone = m.status === 'assigned' || m.status === 'will assign' ? 'green' : m.status === 'already set' ? 'blue' : 'amber';
        return '<li><img class="tbl-thumb" src="' + esc(m.thumb) + '" alt=""><div class="list__main"><strong>' + esc(m.label) + '</strong><small>' + esc(m.kind) + ' · ' + esc(m.file) + '</small></div><span class="pill pill--' + tone + '">' + esc(m.status) + '</span></li>';
      }).join('') + '</ul></div>';
    }
    function preview() { res.innerHTML = '<div class="skel"></div>'; api('media.autoassign', { apply: false, overwrite: ow.checked }).then(render).catch(function (e) { res.innerHTML = '<div class="empty-s">' + esc(e.message) + '</div>'; }); }
    ow.addEventListener('change', preview);
    modal({
      title: 'Auto-assign images', body: body, size: 'md',
      buttons: [{ label: 'Close', value: null }, {
        label: 'Assign images', cls: 'btn--primary', icon: 'check', onClick: function (close, btn) {
          busy(btn, true);
          api('media.autoassign', { apply: true, overwrite: ow.checked }).then(function (d) {
            busy(btn, false); render(d);
            var n = d.matches.filter(function (m) { return m.status === 'assigned'; }).length;
            toast(n + ' image' + (n === 1 ? '' : 's') + ' assigned.');
          }).catch(function (e) { busy(btn, false); toast(e.message, 'err'); });
        }
      }]
    });
    preview();
  }

  /* =====================================================================
     Submissions inbox
     ===================================================================== */
  function viewSubmissions(view, openId) {
    if (!can('submissions.view')) return denied(view);
    document.title = 'Form Inbox · ' + A.siteName;
    var st = { status: '', form: '', q: '', page: 1, active: openId ? +openId : null };
    view.innerHTML = header('Form Inbox', 'Messages submitted through the website forms.', '<a class="btn btn--ghost" href="' + A.api + '?r=submissions.export">' + ic('download') + 'Export CSV</a>') +
      '<div class="sub-layout"><div class="card"><div class="toolbar">' +
      '<label class="search-in">' + ic('search') + '<input type="search" placeholder="Search name, phone, email…" data-sq></label>' +
      '<select class="sel" data-sstatus><option value="">Inbox</option><option value="new">New</option><option value="read">Read</option><option value="archived">Archived</option></select>' +
      '<select class="sel" data-sform><option value="">All forms</option></select>' +
      '</div><div data-slist><div class="card__b"><div class="skel"></div></div></div><div class="pager" data-spager></div></div><div class="sub-detail" data-sdetail></div></div>';
    var list = $('[data-slist]', view), detail = $('[data-sdetail]', view);
    function load() {
      get('submissions.list', { status: st.status, form: st.form, q: st.q, page: st.page }).then(function (d) {
        list.innerHTML = d.items.length ? '<ul class="list sub-list">' + d.items.map(function (s) {
          return '<li data-sid="' + s.id + '" class="' + (s.status === 'new' ? 'is-new ' : '') + (s.id === st.active ? 'is-active' : '') + '"><span class="list__ico">' + ic(s.form === 'appointment' ? 'clock' : s.form === 'benefits' ? 'shield-check' : 'mail') + '</span><div class="list__main"><strong>' + esc(s.name || s.email || 'Anonymous') + '</strong><small>' + esc(s.form_label) + ' · ' + esc(s.phone || s.email) + '</small></div><span class="muted nowrap" style="font-size:.78rem">' + ago(s.created_at) + '</span></li>';
        }).join('') + '</ul>' : '<div class="empty-s">' + ic('inbox') + 'No submissions here.</div>';
        $('[data-spager]', view).innerHTML = '<span>' + d.total + ' total · ' + d.counts.new + ' new</span>' + (d.pages > 1 ? '<span class="ph__actions"><button class="btn btn--sm btn--ghost" data-sp="' + (d.page - 1) + '"' + (d.page <= 1 ? ' disabled' : '') + '>' + ic('arrow-left') + '</button><button class="btn btn--sm btn--ghost" data-sp="' + (d.page + 1) + '"' + (d.page >= d.pages ? ' disabled' : '') + '>' + ic('arrow-right') + '</button></span>' : '');
        var b = $('[data-new-badge]'); if (b) { b.textContent = d.counts.new; b.hidden = !d.counts.new; }
        var sf = $('[data-sform]', view);
        if (sf && d.types && sf.options.length === 1) Object.keys(d.types).forEach(function (k) { var o = document.createElement('option'); o.value = k; o.textContent = d.types[k]; sf.appendChild(o); });
      }).catch(function (e) { list.innerHTML = '<div class="empty-s">' + esc(e.message) + '</div>'; });
    }
    function show(id) {
      st.active = id;
      $$('[data-sid]', list).forEach(function (li) { li.classList.toggle('is-active', +li.getAttribute('data-sid') === id); });
      if (!id) { detail.innerHTML = '<div class="card"><div class="empty-s">' + ic('mail') + 'Select a submission to read it.</div></div>'; return; }
      history.replaceState(null, '', '#/submissions/' + id); S.lastHash = location.hash;
      get('submissions.get', { id: id }).then(function (s) {
        var li = $('[data-sid="' + id + '"]', list); if (li) li.classList.remove('is-new');
        var rows = Object.keys(s.data).map(function (k) { return '<div><span>' + esc(k.replace(/_/g, ' ').replace(/^\w/, function (c) { return c.toUpperCase(); })) + '</span><strong>' + esc(s.data[k] || '—') + '</strong></div>'; }).join('');
        detail.innerHTML = '<div class="card"><div class="card__h"><div><h2>' + esc(s.name || 'Anonymous') + '</h2><span class="muted" style="font-size:.82rem">' + esc(s.form_label) + ' · ' + fullDate(s.created_at) + '</span></div><span class="pill pill--' + esc(s.status) + '">' + esc(s.status) + '</span></div>' +
          '<div class="card__b"><div class="ph__actions" style="margin-bottom:14px">' + (s.phone ? '<a class="btn btn--primary btn--sm" href="tel:' + esc(s.phone) + '">' + ic('phone') + 'Call</a><a class="btn btn--ghost btn--sm" href="sms:' + esc(s.phone) + '">' + ic('message-square') + 'Text</a>' : '') + (s.email ? '<a class="btn btn--ghost btn--sm" href="mailto:' + esc(s.email) + '">' + ic('mail') + 'Email</a>' : '') + '</div>' +
          '<div class="sub-fields">' + rows + '<div><span>Submitted from</span><strong class="mono">' + esc(s.page || '—') + '</strong></div><div><span>Sent to GoHighLevel</span><strong>' + (s.forwarded ? 'Yes' : 'No') + '</strong></div></div>' +
          (can('submissions.manage') ? '<div class="ph__actions" style="margin-top:16px"><button class="btn btn--ghost btn--sm" data-mark="new">Mark unread</button><button class="btn btn--ghost btn--sm" data-mark="' + (s.status === 'archived' ? 'read' : 'archived') + '">' + ic('archive') + (s.status === 'archived' ? 'Restore' : 'Archive') + '</button><button class="btn btn--danger btn--sm" data-sdel>' + ic('trash') + 'Delete</button></div>' : '') + '</div></div>';
        $$('[data-mark]', detail).forEach(function (b) { b.addEventListener('click', function () { api('submissions.update', { ids: [id], status: b.getAttribute('data-mark') }).then(function () { toast('Updated.'); load(); show(b.getAttribute('data-mark') === 'archived' && st.status !== 'archived' ? null : id); }); }); });
        var d = $('[data-sdel]', detail);
        if (d) d.addEventListener('click', function () { confirmBox('Delete this submission?', 'This permanently removes it from the inbox.', 'Delete', true).then(function (y) { if (y) api('submissions.delete', { ids: [id] }).then(function () { toast('Deleted.'); load(); show(null); }); }); });
      }).catch(function (e) { detail.innerHTML = '<div class="card"><div class="empty-s">' + esc(e.message) + '</div></div>'; });
    }
    list.addEventListener('click', function (e) { var li = e.target.closest('[data-sid]'); if (li) show(+li.getAttribute('data-sid')); });
    view.addEventListener('click', function (e) { var b = e.target.closest('[data-sp]'); if (b) { st.page = +b.getAttribute('data-sp'); load(); } });
    $('[data-sq]', view).addEventListener('input', debounce(function (e) { st.q = e.target.value; st.page = 1; load(); }, 250));
    $('[data-sstatus]', view).addEventListener('change', function (e) { st.status = e.target.value; st.page = 1; load(); });
    $('[data-sform]', view).addEventListener('change', function (e) { st.form = e.target.value; st.page = 1; load(); });
    load();
    show(st.active);
  }

  /* =====================================================================
     Users & roles
     ===================================================================== */
  function viewUsers(view) {
    if (!can('users.manage')) return denied(view);
    document.title = 'Users & Roles · ' + A.siteName;
    view.innerHTML = header('Users & Roles', 'Invite team members and control what each person can do.', '<button class="btn btn--primary" data-add>' + ic('plus') + 'Add user</button>') +
      '<div class="grid grid--2"><div class="card"><div class="tbl-wrap"><table class="tbl"><thead><tr><th>User</th><th>Role</th><th>Status</th><th>Last sign-in</th><th class="col-actions"></th></tr></thead><tbody data-urows><tr><td colspan="5"><div class="skel"></div></td></tr></tbody></table></div></div>' +
      '<div class="card"><div class="card__h"><h2>Role permissions</h2></div><div class="card__b--flush"><ul class="list">' + S.schema.roles.map(function (r) { return '<li><span class="list__ico">' + ic(r.value === 'admin' ? 'key' : r.value === 'editor' ? 'pencil' : r.value === 'author' ? 'file-text' : 'eye') + '</span><div class="list__main"><strong>' + esc(r.label) + '</strong><small style="white-space:normal">' + esc(r.description) + '</small></div></li>'; }).join('') + '</ul></div></div></div>';
    var users = [];
    function load() {
      get('users.list').then(function (d) {
        users = d;
        $('[data-urows]', view).innerHTML = d.map(function (u) {
          return '<tr><td><div style="display:flex;align-items:center;gap:10px">' + avatar(u) + '<div><strong style="color:var(--ink)">' + esc(u.name || u.username) + (u.id === S.user.id ? ' <span class="tag">You</span>' : '') + '</strong><span class="tbl-sub">' + esc(u.username) + ' · ' + esc(u.email) + '</span></div></div></td>' +
            '<td><span class="pill pill--blue pill--plain">' + esc(u.role_label) + '</span></td><td><span class="pill pill--' + esc(u.status) + '">' + esc(u.status) + '</span></td><td class="muted">' + (u.last_login ? ago(u.last_login) : 'Never') + '</td>' +
            '<td class="col-actions"><span class="row-actions" style="opacity:1"><button class="btn btn--sm btn--plain btn--icon" data-edit="' + u.id + '" title="Edit">' + ic('pencil') + '</button>' + (u.id !== S.user.id ? '<button class="btn btn--sm btn--plain btn--icon" data-del="' + u.id + '" title="Delete" style="color:var(--red)">' + ic('trash') + '</button>' : '') + '</span></td></tr>';
        }).join('');
      }).catch(function (e) { toast(e.message, 'err'); });
    }
    function form(u) {
      u = u || { name: '', username: '', email: '', role: 'editor', status: 'active' };
      var body = el('<div class="fields" style="padding:0">' +
        '<div class="f f--half"><label>Full name</label><input class="in" name="name" value="' + esc(u.name) + '"></div>' +
        '<div class="f f--half"><label>Username<span class="f__req">*</span></label><input class="in" name="username" value="' + esc(u.username) + '" autocomplete="off"></div>' +
        '<div class="f"><label>Email<span class="f__req">*</span></label><input class="in" type="email" name="email" value="' + esc(u.email) + '"></div>' +
        '<div class="f"><span class="f__label">Role</span><div class="role-cards">' + S.schema.roles.map(function (r) { return '<label class="role-card"><input type="radio" name="role" value="' + r.value + '"' + (u.role === r.value ? ' checked' : '') + '><span><strong>' + esc(r.label) + '</strong><small>' + esc(r.description) + '</small></span></label>'; }).join('') + '</div></div>' +
        '<div class="f f--half"><label>Status</label><select class="in" name="status"><option value="active">Active</option><option value="disabled">Disabled</option></select></div>' +
        '<div class="f f--half"><label>' + (u.id ? 'New password (optional)' : 'Password<span class="f__req">*</span>') + '</label><div class="prefix"><input name="password" type="text" autocomplete="new-password" placeholder="Min. 8 characters"><button type="button" class="btn btn--plain btn--sm" data-gen style="border-radius:0" title="Generate strong password">' + ic('refresh') + '</button></div></div>' +
        '</div>');
      $('[name=status]', body).value = u.status;
      $('[data-gen]', body).addEventListener('click', function () {
        var chars = 'ABCDEFGHJKLMNPQRSTUVWXYZabcdefghijkmnpqrstuvwxyz23456789!@#$%*', a = new Uint32Array(16), s = '';
        crypto.getRandomValues(a); a.forEach(function (n) { s += chars[n % chars.length]; });
        $('[name=password]', body).value = s;
      });
      modal({
        title: u.id ? 'Edit user' : 'Add user', body: body, size: 'md',
        buttons: [{ label: 'Cancel', value: null }, {
          label: u.id ? 'Save user' : 'Create user', cls: 'btn--primary', onClick: function (close, btn) {
            var data = {}; $$('input, select', body).forEach(function (i) { if (i.type === 'radio') { if (i.checked) data[i.name] = i.value; } else if (i.name) data[i.name] = i.value; });
            busy(btn, true);
            api('users.save', { id: u.id || 0, data: data }).then(function (r) {
              close(true); toast(u.id ? 'User updated.' : 'User created. Share the password securely.');
              if (r.id === S.user.id) { S.user = Object.assign(S.user, r); }
              load();
            }).catch(function (e) { busy(btn, false); toast(e.message, 'err'); });
          }
        }]
      });
    }
    view.addEventListener('click', function (e) {
      var b;
      if (e.target.closest('[data-add]')) form();
      else if ((b = e.target.closest('[data-edit]'))) form(users.filter(function (u) { return u.id === +b.getAttribute('data-edit'); })[0]);
      else if ((b = e.target.closest('[data-del]'))) {
        var u = users.filter(function (x) { return x.id === +b.getAttribute('data-del'); })[0];
        confirmBox('Delete ' + (u.name || u.username) + '?', 'They will immediately lose access to the dashboard.', 'Delete user', true).then(function (y) {
          if (y) api('users.delete', { id: u.id }).then(function () { toast('User deleted.'); load(); }).catch(function (er) { toast(er.message, 'err'); });
        });
      }
    });
    load();
  }

  /* =====================================================================
     Settings
     ===================================================================== */
  function viewSettings(view, tab) {
    if (!can('settings.manage')) return denied(view);
    var tabs = S.schema.settings;
    tab = tab || 'general';
    if ((!tabs[tab] || tabs[tab].hidden) && tab !== 'system') tab = 'general';
    document.title = 'Settings · ' + A.siteName;
    view.innerHTML = header('Settings', 'Brand, shared header and footer, integrations and SEO.') +
      '<div class="set-layout"><nav class="card set-nav">' + Object.keys(tabs).filter(function (k) { return !tabs[k].hidden; }).map(function (k) { return '<a href="#/settings/' + k + '" class="' + (k === tab ? 'is-active' : '') + '">' + ic(tabs[k].icon) + esc(tabs[k].label) + '</a>'; }).join('') + '<a href="#/settings/system" class="' + (tab === 'system' ? 'is-active' : '') + '">' + ic('database') + 'System &amp; Backup</a></nav><div data-panel></div></div>';
    var panel = $('[data-panel]', view);
    if (tab === 'system') return systemPanel(panel);
    get('settings.get').then(function (all) {
      var vals = {}, changed = {};
      tabs[tab].fields.forEach(function (f) { vals[f.key] = all[f.key]; });
      var card = el('<div class="card"><div class="card__h"><h2>' + esc(tabs[tab].label) + '</h2></div><div class="fields"></div></div>');
      if (tab === 'integrations') card.insertBefore(el('<div class="notice notice--blue" style="margin:16px 20px 0">' + ic('info') + '<span>The website’s built-in forms work out of the box: submissions land in the Form Inbox and are emailed to the notification address. Paste GoHighLevel embed codes here to switch any form to GHL, or add a webhook URL so GHL also receives built-in submissions.</span></div>'), $('.fields', card));
      if (tab === 'seo' && vals.seo_noindex) card.insertBefore(el('<div class="notice" style="margin:16px 20px 0">' + ic('alert') + '<span>Search engines are currently blocked. Turn this off when the site goes live on its real domain.</span></div>'), $('.fields', card));
      var fields = $('.fields', card);
      function onChange(k) { changed[k] = true; S.dirty = true; bar.hidden = false; }
      tabs[tab].fields.forEach(function (f) { var fe = buildField(f, vals, onChange, {}); if (fe) fields.appendChild(fe); });
      panel.appendChild(card);
      var bar = el('<div class="savebar" hidden><span>You have unsaved changes</span><span class="ph__actions"><button class="btn btn--plain" data-reset style="color:var(--panel)">Discard</button><button class="btn btn--accent" data-save>' + ic('check') + 'Save settings</button></span></div>');
      panel.appendChild(bar);
      function save() {
        var btn = $('[data-save]', bar), data = {};
        Object.keys(changed).forEach(function (k) { data[k] = vals[k]; });
        busy(btn, true);
        api('settings.save', { data: data }).then(function () { busy(btn, false); S.dirty = false; changed = {}; bar.hidden = true; toast('Settings saved.'); if (tab === 'seo') boot(true); }).catch(function (e) { busy(btn, false); toast(e.message, 'err'); });
      }
      $('[data-save]', bar).addEventListener('click', save);
      $('[data-reset]', bar).addEventListener('click', function () { S.dirty = false; route(); });
      bindSaveKey(save);
    }).catch(function (e) { errorBox(panel, e); });
  }
  function systemPanel(panel) {
    get('system.info').then(function (s) {
      panel.innerHTML = '<div class="card"><div class="card__h"><h2>System</h2></div><div class="card__b"><div class="kv" style="font-size:.9rem;gap:10px">' +
        [['Dashboard version', s.version], ['PHP', s.php], ['Database', s.database], ['Image optimisation (GD)', s.gd ? 'Enabled' : 'Not available'], ['WebP conversion', s.webp ? 'Enabled' : 'Not available'], ['Upload limit', s.upload_max + ' (post ' + s.post_max + ')'], ['Memory limit', s.memory], ['Uploads folder writable', s.uploads_writable ? 'Yes' : 'NO — fix permissions'], ['Media library size', bytes(s.media_bytes)], ['Installed', fullDate(s.installed_at)], ['Server', s.server || '—']].map(function (r) { return '<div><span>' + esc(r[0]) + '</span><strong>' + esc(r[1]) + '</strong></div>'; }).join('') +
        '</div></div></div><div class="card" style="margin-top:18px"><div class="card__h"><h2>Backup</h2></div><div class="card__b"><p>Download a JSON export of all content, settings and redirects. SiteGround also keeps daily automatic backups of files and the database (Site Tools → Security → Backups).</p><a class="btn btn--primary" href="' + A.api + '?r=backup.export">' + ic('download') + 'Download content backup</a></div></div>';
    }).catch(function (e) { errorBox(panel, e); });
  }

  /* =====================================================================
     Profile
     ===================================================================== */
  function viewProfile(view) {
    document.title = 'My profile · ' + A.siteName;
    var vals = { name: S.user.name, email: S.user.email, avatar: S.user.avatar ? S.user.avatar.replace(A.base, '') : '', current_password: '', new_password: '' };
    view.innerHTML = header('My profile', 'Update your details and password.') + '<div class="grid grid--2"><div class="card"><div class="card__h"><h2>Account</h2><span class="pill pill--blue pill--plain">' + esc(S.user.role_label) + '</span></div><div class="fields" data-f></div></div><div class="card"><div class="card__h"><h2>Change password</h2></div><div class="fields" data-p></div></div></div><div style="margin-top:18px"><button class="btn btn--primary btn--lg" data-save>' + ic('check') + 'Save profile</button></div>';
    var noop = function () { S.dirty = true; };
    [{ key: 'name', label: 'Full name', type: 'text' }, { key: 'email', label: 'Email', type: 'text' }, { key: 'avatar', label: 'Profile photo', type: 'image' }].forEach(function (f) { $('[data-f]', view).appendChild(buildField(f, vals, noop, {})); });
    [{ key: 'current_password', label: 'Current password', type: 'text' }, { key: 'new_password', label: 'New password (min. 8 characters)', type: 'text' }].forEach(function (f) { var fe = buildField(f, vals, noop, {}); $('input', fe).type = 'password'; $('input', fe).autocomplete = 'new-password'; $('[data-p]', view).appendChild(fe); });
    function save() {
      var btn = $('[data-save]', view);
      busy(btn, true);
      api('profile.save', { data: vals }).then(function (r) {
        busy(btn, false); S.dirty = false; S.user = r.user; toast('Profile saved.');
        renderShell(); route();
      }).catch(function (e) { busy(btn, false); toast(e.message, 'err'); });
    }
    $('[data-save]', view).addEventListener('click', save);
    bindSaveKey(save);
  }

  /* =====================================================================
     Activity log
     ===================================================================== */
  function viewActivity(view) {
    if (!can('activity.view')) return denied(view);
    document.title = 'Activity Log · ' + A.siteName;
    view.innerHTML = header('Activity Log', 'Every sign-in, edit, upload and deletion — newest first.') + '<div class="card"><div data-a><div class="card__b"><div class="skel"></div></div></div><div class="pager" data-ap></div></div>';
    var page = 1;
    function load() {
      get('activity.list', { page: page }).then(function (d) {
        $('[data-a]', view).innerHTML = activityList(d.items);
        $('[data-ap]', view).innerHTML = '<span>' + d.total + ' events</span>' + (d.pages > 1 ? '<span class="ph__actions"><button class="btn btn--sm btn--ghost" data-ap-p="' + (d.page - 1) + '"' + (d.page <= 1 ? ' disabled' : '') + '>' + ic('arrow-left') + '</button><span>Page ' + d.page + ' of ' + d.pages + '</span><button class="btn btn--sm btn--ghost" data-ap-p="' + (d.page + 1) + '"' + (d.page >= d.pages ? ' disabled' : '') + '>' + ic('arrow-right') + '</button></span>' : '');
      }).catch(function (e) { errorBox(view, e); });
    }
    view.addEventListener('click', function (e) { var b = e.target.closest('[data-ap-p]'); if (b) { page = +b.getAttribute('data-ap-p'); load(); } });
    load();
  }

  /* =====================================================================
     Command palette (Ctrl/Cmd + K)
     ===================================================================== */
  function openPalette() {
    if ($('.palette')) return;
    var p = el('<div class="palette"><div class="modal__bg"></div><div class="palette__box" role="dialog" aria-label="Search"><div class="palette__in">' + ic('search') + '<input placeholder="Search content or jump to a section…" aria-label="Search"><kbd class="muted" style="font-size:.72rem">Esc</kbd></div><div class="palette__list"></div></div></div>');
    document.body.appendChild(p);
    var input = $('input', p), list = $('.palette__list', p), hl = 0, results = [];
    var nav = navItems().filter(function (n) { return !n.group; }).map(function (n) { return { title: n.label, hash: n.hash, icon: n.icon, label: 'Go to' }; });
    if (can('content.edit')) ['services', 'providers', 'pages', 'testimonials', 'faqs'].forEach(function (k) { var r = S.schema.resources[k]; if (!r || r.hidden) return; nav.push({ title: 'New ' + r.singular.toLowerCase(), hash: '#/' + k + '/new', icon: 'plus', label: 'Create' }); });
    nav.push({ title: 'My profile', hash: '#/profile', icon: 'user', label: 'Go to' });
    function render(items) {
      results = items; hl = 0;
      list.innerHTML = items.length ? items.map(function (r, i) { return '<button class="palette__item' + (i ? '' : ' is-hl') + '" data-i="' + i + '">' + ic(r.icon) + '<span>' + esc(r.title) + '</span><small>' + esc(r.label) + '</small></button>'; }).join('') : '<div class="empty-s">No results</div>';
    }
    function close() { p.remove(); }
    function pick(r) { close(); if (r) go(r.hash); }
    var search = debounce(function (q) {
      var local = nav.filter(function (n) { return n.title.toLowerCase().indexOf(q.toLowerCase()) > -1; });
      if (q.length < 2) return render(local);
      get('search', { q: q }).then(function (rs) {
        render(local.concat(rs.map(function (r) { return { title: r.title, hash: r.type === 'media' ? '#/media' : '#/' + r.type + '/' + r.id, icon: r.icon === 'image' ? 'image' : (S.schema.resources[r.type] || { icon: 'file' }).icon, label: r.label }; })));
      });
    }, 160);
    input.addEventListener('input', function () { search(input.value.trim()); });
    input.addEventListener('keydown', function (e) {
      var items = $$('.palette__item', list);
      if (e.key === 'Escape') close();
      else if (e.key === 'ArrowDown' || e.key === 'ArrowUp') { e.preventDefault(); hl = Math.max(0, Math.min(items.length - 1, hl + (e.key === 'ArrowDown' ? 1 : -1))); items.forEach(function (b, i) { b.classList.toggle('is-hl', i === hl); }); if (items[hl]) items[hl].scrollIntoView({ block: 'nearest' }); }
      else if (e.key === 'Enter') { e.preventDefault(); pick(results[hl]); }
    });
    list.addEventListener('click', function (e) { var b = e.target.closest('[data-i]'); if (b) pick(results[+b.getAttribute('data-i')]); });
    $('.modal__bg', p).addEventListener('click', close);
    render(nav);
    input.focus();
  }

  /* =====================================================================
     Boot
     ===================================================================== */
  function boot(keepView) {
    return get('schema').then(function (schema) {
      S.schema = schema;
      renderShell();
      if (!location.hash || location.hash === '#' || location.hash === '#/') history.replaceState(null, '', '#/dashboard');
      S.lastHash = '';
      route();
      if (!keepView) setInterval(refreshBadge, 60000);
    });
  }
  get('auth.me').then(function (d) {
    S.csrf = d.csrf;
    if (!d.user) return renderLogin();
    S.user = d.user;
    return boot();
  }).catch(function (e) {
    app.innerHTML = '<div class="boot"><p>Could not load the dashboard: ' + esc(e.message) + '</p><button class="btn btn--primary" onclick="location.reload()">Retry</button></div>';
  });
})();
