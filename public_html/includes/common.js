// ==============================================================================
// MitraNet Network OS - Common Core UI Utilities
// Shared across all modular packages (Modern White Enterprise Theme)
// ==============================================================================

const API_BASE = '';

function toggleSidebar() {
  if (typeof jQuery !== 'undefined') {
    $('body').toggleClass('sidebar-collapsed');
    const isCollapsed = $('body').hasClass('sidebar-collapsed');
    localStorage.setItem('mitranet_sidebar_collapsed', isCollapsed ? 'true' : 'false');
  } else {
    document.body.classList.toggle('sidebar-collapsed');
    const isCollapsed = document.body.classList.contains('sidebar-collapsed');
    localStorage.setItem('mitranet_sidebar_collapsed', isCollapsed ? 'true' : 'false');
  }
}

function showAlert(message, type = 'success') {
  const banner = document.getElementById('alert-banner');
  if (!banner) return;
  banner.className = `alert-banner ${type}`;
  banner.textContent = message;
  banner.classList.remove('hidden');
  setTimeout(() => {
    banner.classList.add('hidden');
  }, 4500);
}

function escapeHtml(str) {
  if (!str) return '';
  return String(str)
    .replace(/&/g, '&amp;')
    .replace(/</g, '&lt;')
    .replace(/>/g, '&gt;')
    .replace(/"/g, '&quot;')
    .replace(/'/g, '&#039;');
}

async function apiFetch(endpoint, options = {}) {
  try {
    const res = await fetch(`${API_BASE}${endpoint}`, {
      ...options,
      headers: {
        'Content-Type': 'application/json',
        ...(options.headers || {})
      }
    });
    if (res.status === 401) {
      showAlert('Session unauthorized. Please refresh and log in.', 'error');
      return null;
    }
    return await res.json();
  } catch (err) {
    console.error(`API Error on ${endpoint}:`, err);
    showAlert(`Error connecting to server (${endpoint})`, 'error');
    return null;
  }
}

async function triggerOptimize() {
  const res = await apiFetch('/api/optimize', { method: 'POST' });
  if (res && res.status === 'success') {
    showAlert(res.message);
  }
}

// Fixed Footer Live Clock
function startLiveClock() {
  function updateClock() {
    const clockEl = document.getElementById('footer-live-clock');
    if (!clockEl) return;
    const now = new Date();
    const hh = String(now.getHours()).padStart(2, '0');
    const mm = String(now.getMinutes()).padStart(2, '0');
    const ss = String(now.getSeconds()).padStart(2, '0');
    clockEl.textContent = `${hh}:${mm}:${ss}`;
  }
  updateClock();
  setInterval(updateClock, 1000);
}

// Drop-Right Sub-Menus positioning (Floating outside scrollable sidebar)
function initDropendMenus() {
  const wrappers = document.querySelectorAll('.nav-item-wrapper.dropend');
  wrappers.forEach(wrapper => {
    function positionMenu() {
      const menu = wrapper.querySelector('.dropend-menu');
      if (!menu) return;
      const rect = wrapper.getBoundingClientRect();
      const sidebar = document.getElementById('sidebar');
      const sidebarRect = sidebar ? sidebar.getBoundingClientRect() : null;
      const leftPos = sidebarRect ? sidebarRect.right + 6 : rect.right + 6;
      menu.style.position = 'fixed';
      menu.style.left = `${leftPos}px`;

      let topPos = rect.top;
      const menuHeight = menu.offsetHeight || 220;
      if (topPos + menuHeight > window.innerHeight - 45) {
        topPos = Math.max(10, window.innerHeight - menuHeight - 45);
      }
      menu.style.top = `${topPos}px`;
    }

    wrapper.addEventListener('mouseenter', positionMenu);
    wrapper.addEventListener('focusin', positionMenu);
  });
}

// Global Footer RAM telemetry poller
async function updateFooterTelemetry() {
  const footerRamEl = document.getElementById('footer-ram-usage');
  if (!footerRamEl) return;
  try {
    const data = await apiFetch('/api/status');
    if (data && data.memory && data.memory.used_mb) {
      footerRamEl.textContent = `${data.memory.used_mb} MB`;
    }
  } catch(e) {}
}

function initCommonApp() {
  if (localStorage.getItem('mitranet_sidebar_collapsed') === 'true') {
    if (typeof jQuery !== 'undefined') {
      $('body').addClass('sidebar-collapsed');
    } else {
      document.body.classList.add('sidebar-collapsed');
    }
  }

  startLiveClock();
  initDropendMenus();
  updateFooterTelemetry();
  setInterval(updateFooterTelemetry, 15000);

  // Progressive Web App (PWA) ServiceWorker Registration
  if ('serviceWorker' in navigator && (window.location.protocol === 'https:' || window.location.hostname === 'localhost')) {
    navigator.serviceWorker.register('/sw.js').then(reg => {
      console.log('[MitraNet PWA] ServiceWorker active with scope:', reg.scope);
    }).catch(err => {
      console.log('[MitraNet PWA] ServiceWorker registration skipped:', err);
    });
  }
}

if (typeof jQuery !== 'undefined') {
  $(document).ready(initCommonApp);
} else {
  document.addEventListener('DOMContentLoaded', initCommonApp);
}
