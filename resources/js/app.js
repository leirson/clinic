import './patient-search.js';
import Chart from 'chart.js/auto';

window.Chart = Chart;

(function () {
  const bootstrap = window.ClinicAppBootstrap || {};
  const appUrl = bootstrap.appUrl || '';
  const settings = bootstrap.featureSettings || {};

  function applyMask(el, pattern) {
    el.addEventListener('input', e => {
      let v = e.target.value.replace(/\D/g, '');
      let f = '';
      let i = 0;
      for (const c of pattern) {
        if (i >= v.length) break;
        if (c === '#') {
          f += v[i++];
        } else {
          f += c;
        }
      }
      e.target.value = f;
    });
  }

  document.querySelectorAll('[data-mask]').forEach(el => applyMask(el, el.dataset.mask));

  document.querySelectorAll('[data-confirm]').forEach(el => {
    el.addEventListener('click', e => {
      if (!confirm(el.dataset.confirm)) {
        e.preventDefault();
      }
    });
  });

  document.querySelectorAll('[data-cep]').forEach(el => {
    el.addEventListener('blur', async () => {
      const cep = el.value.replace(/\D/g, '');
      if (cep.length !== 8) return;
      try {
        const r = await fetch('https://viacep.com.br/ws/' + cep + '/json/');
        const d = await r.json();
        if (d.erro) return;
        const prefix = el.dataset.cep || '';
        const fill = (n, v) => {
          const target = document.querySelector('[name="' + prefix + n + '"]');
          if (target && !target.value) target.value = v || '';
        };
        fill('logradouro', d.logradouro);
        fill('bairro', d.bairro);
        fill('cidade', d.localidade);
        fill('uf', d.uf);
        fill('codigo_municipio', d.ibge);
      } catch (error) {
        console.error(error);
      }
    });
  });

  function ensureFloatingButton(id, html, onClick) {
    let btn = document.getElementById(id);
    if (btn) return btn;
    btn = document.createElement('button');
    btn.id = id;
    btn.type = 'button';
    btn.innerHTML = html;
    btn.className = 'fixed bottom-4 right-4 z-50 px-4 py-3 rounded-full shadow-lg bg-sky-600 text-white text-sm font-semibold';
    btn.addEventListener('click', onClick);
    document.body.appendChild(btn);
    return btn;
  }

  let deferredPrompt = null;
  window.addEventListener('beforeinstallprompt', e => {
    e.preventDefault();
    deferredPrompt = e;
    if (!settings.pwa_install_enabled) return;

    const existing = document.getElementById('pwa-install-btn');
    const clickHandler = async () => {
      if (!deferredPrompt) return;
      deferredPrompt.prompt();
      deferredPrompt = null;
      if (existing) existing.classList.add('hidden');
      const floating = document.getElementById('clinicapp-install-floating');
      if (floating) floating.remove();
    };

    if (existing) {
      existing.classList.remove('hidden');
      existing.onclick = clickHandler;
    } else {
      ensureFloatingButton('clinicapp-install-floating', '📲 Instalar app', clickHandler);
    }
  });

  async function showBrowserNotification(item) {
    if (!('serviceWorker' in navigator)) return;
    const reg = await navigator.serviceWorker.ready;
    if (reg.active) {
      reg.active.postMessage({
        type: 'show-notification',
        title: item.title || 'ClinicApp',
        body: item.body || '',
        url: item.url || appUrl + '/dashboard.php',
        tag: 'clinicapp-' + (item.id || Date.now())
      });
    }
  }

  function maybeEnablePushUI() {
    if (!settings.reminder_push_enabled || !('Notification' in window)) return;
    if (Notification.permission === 'default') {
      ensureFloatingButton('clinicapp-notify-floating', '🔔 Ativar notificações', async () => {
        const perm = await Notification.requestPermission();
        if (perm === 'granted') {
          const btn = document.getElementById('clinicapp-notify-floating');
          if (btn) btn.remove();
        }
      });
    }
  }

  async function pollNotifications() {
    if (!settings.reminder_push_enabled) return;
    if (!('Notification' in window) || Notification.permission !== 'granted') return;
    try {
      const response = await fetch(appUrl + '/api/notifications.php', { credentials: 'same-origin', cache: 'no-store' });
      const data = await response.json();
      if (!data.ok || !Array.isArray(data.items)) return;
      for (const item of data.items) {
        showBrowserNotification(item);
      }
    } catch (error) {
      console.error('Falha ao buscar notificações', error);
    }
  }

  function bindSpecialtySelector() {
    const radios = Array.from(document.querySelectorAll('[data-specialty-radio]'));
    if (!radios.length) return;

    const sync = () => {
      const cards = document.querySelectorAll('[data-specialty-option]');
      cards.forEach(card => card.classList.remove('is-selected', 'border-primary', 'ring-4', 'ring-primary/10'));
      radios.forEach(radio => {
        const card = radio.closest('[data-specialty-option]');
        if (!card) return;
        if (radio.checked) {
          card.classList.add('is-selected', 'border-primary', 'ring-4', 'ring-primary/10');
        }
      });
    };

    radios.forEach(radio => {
      radio.addEventListener('change', sync);
      radio.addEventListener('click', sync);
    });

    document.querySelectorAll('[data-specialty-option]').forEach(card => {
      card.addEventListener('click', () => {
        const radio = card.querySelector('[data-specialty-radio]');
        if (!radio) return;
        radio.checked = true;
        sync();
      });
    });

    sync();
  }

  function highlightCurrentNavItem() {
    document.querySelectorAll('.nav-item').forEach(link => {
      if (link.href.split('?')[0] === window.location.href.split('?')[0]) {
        link.classList.add('bg-white/20', 'font-semibold');
      }
    });
  }

  function bindUserMenuDismiss() {
    document.addEventListener('click', e => {
      const menu = document.getElementById('userMenu');
      if (menu && !menu.classList.contains('hidden') && !e.target.closest('.relative')) {
        menu.classList.add('hidden');
      }
    });
  }

  function registerServiceWorker() {
    if ('serviceWorker' in navigator) {
      navigator.serviceWorker.register((appUrl || '') + '/sw.js').catch(() => {});
    }
  }

  window.toggleSidebar = function toggleSidebar() {
    const sidebar = document.getElementById('sidebar');
    if (sidebar) {
      sidebar.classList.toggle('-translate-x-full');
      sidebar.classList.toggle('show');
    }
  };

  bindSpecialtySelector();
  highlightCurrentNavItem();
  bindUserMenuDismiss();
  maybeEnablePushUI();
  registerServiceWorker();

  if (settings.reminder_push_enabled) {
    pollNotifications();
    setInterval(pollNotifications, 90000);
  }
})();
