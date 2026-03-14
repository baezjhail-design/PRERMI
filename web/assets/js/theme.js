/* ═══════════════════════════════════════════
   PRERMI Theme System — theme.js v1.0
   ═══════════════════════════════════════════ */

window._prermiMaps = window._prermiMaps || [];

/**
 * Toggle between light and dark theme.
 */
function toggleTheme() {
  var curr = document.documentElement.getAttribute('data-theme') || 'light';
  applyTheme(curr === 'dark' ? 'light' : 'dark');
}

/**
 * Apply a theme by name ('light' | 'dark').
 */
function applyTheme(t) {
  document.documentElement.setAttribute('data-theme', t);
  try { localStorage.setItem('prermi_theme', t); } catch(e){}

  /* Update toggle button icon */
  var btn = document.getElementById('btnTheme');
  if (btn) btn.innerHTML = t === 'dark'
    ? '<i class="fas fa-sun"></i>'
    : '<i class="fas fa-moon"></i>';

  /* Switch Leaflet map tile layers */
  var lightUrl = 'https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png';
  var darkUrl  = 'https://{s}.basemaps.cartocdn.com/dark_all/{z}/{x}/{y}{r}.png';
  var url = t === 'dark' ? darkUrl : lightUrl;
  window._prermiMaps.forEach(function(info) {
    if (info.tileLayer) {
      try { info.tileLayer.setUrl(url); } catch(e){}
    }
  });
}

/**
 * Toggle map fullscreen mode.
 */
function toggleMapFull(containerId, btnId) {
  var el  = document.getElementById(containerId);
  var btn = document.getElementById(btnId);
  if (!el) return;

  var isFs = el.classList.contains('map-container-fs');
  if (isFs) {
    el.classList.remove('map-container-fs');
    if (btn) btn.innerHTML = '<i class="fas fa-expand"></i>';
    if (btn) btn.title = 'Pantalla completa';
  } else {
    el.classList.add('map-container-fs');
    if (btn) btn.innerHTML = '<i class="fas fa-compress"></i>';
    if (btn) btn.title = 'Salir de pantalla completa';
  }

  /* Invalidate all map sizes after CSS transition */
  setTimeout(function() {
    window._prermiMaps.forEach(function(info) {
      if (info.map) {
        try { info.map.invalidateSize(); } catch(e){}
      }
    });
  }, 120);
}

/* ── Auto-init: restore theme + sync button icon on load ── */
(function init() {
  function syncIcon() {
    var t   = document.documentElement.getAttribute('data-theme') || 'light';
    var btn = document.getElementById('btnTheme');
    if (btn) btn.innerHTML = t === 'dark'
      ? '<i class="fas fa-sun"></i>'
      : '<i class="fas fa-moon"></i>';
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', syncIcon);
  } else {
    syncIcon();
  }
})();
