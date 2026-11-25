/** Small, progressively enhanced navigation. Native details works without JS. */
export function initSiteNavigation(root = document) {
  const doc = root.ownerDocument || root;
  const win = doc.defaultView;
  if (!win) return;
  root.querySelectorAll('.site-mobile-menu').forEach(menu => {
    if (menu.dataset.navigationReady) return;
    menu.dataset.navigationReady = '1';
    const trigger = menu.querySelector('summary');
    const nav = menu.querySelector('nav');
    if (!trigger || !nav) return;
    const sync = () => {
      trigger.setAttribute('aria-expanded', String(menu.open));
      trigger.setAttribute('aria-label', menu.open ? 'Fechar menu de navegação' : 'Abrir menu de navegação');
    };
    const close = (returnFocus = false) => {
      if (!menu.open) return;
      menu.open = false;
      sync();
      if (returnFocus) trigger.focus({ preventScroll: true });
    };
    menu.addEventListener('toggle', sync);
    nav.addEventListener('click', event => {
      const link = event.target.closest('a[href]');
      if (!link || !nav.contains(link)) return;
      if (event.button > 0 || event.ctrlKey || event.metaKey || event.shiftKey || event.altKey) return;
      const href = link.getAttribute('href');
      close();
      if (href && href.startsWith('#')) {
        let target;
        try { target = doc.getElementById(decodeURIComponent(href.slice(1))); } catch (_) { return; }
        if (target) {
          // Move the keyboard reading position out of the now hidden menu.
          const focusTarget = target.querySelector('h1,h2') || target;
          if (!focusTarget.hasAttribute('tabindex')) {
            focusTarget.setAttribute('tabindex', '-1');
            focusTarget.addEventListener('blur', () => focusTarget.removeAttribute('tabindex'), { once: true });
          }
          focusTarget.focus({ preventScroll: true });
        }
      }
    });
    doc.addEventListener('click', event => {
      if (menu.open && !menu.contains(event.target)) close();
    });
    doc.addEventListener('keydown', event => {
      if (event.key === 'Escape' && menu.open) {
        event.preventDefault();
        close(true);
      }
    });
    doc.addEventListener('focusin', event => {
      if (menu.open && !menu.contains(event.target)) close();
    });
    if (typeof win.matchMedia === 'function') {
      const desktop = win.matchMedia('(min-width: 961px)');
      const onChange = event => { if (event.matches) close(); };
      if (desktop.addEventListener) desktop.addEventListener('change', onChange);
      else if (desktop.addListener) desktop.addListener(onChange);
    }
    sync();
  });
}
