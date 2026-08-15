(() => {
  const root = document.documentElement;
  const reducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)');
  const themeButton = document.querySelector('[data-theme-toggle]');

  const setTheme = (theme, persist = true) => {
    root.dataset.theme = theme === 'light' ? 'light' : 'dark';
    if (themeButton) {
      themeButton.setAttribute('aria-pressed', String(root.dataset.theme === 'light'));
    }
    if (persist) {
      try { localStorage.setItem('lu-event-theme', root.dataset.theme); } catch (error) {}
    }
  };

  setTheme(root.dataset.theme || window.LUEvent?.defaultTheme || 'dark', false);
  themeButton?.addEventListener('click', () => setTheme(root.dataset.theme === 'dark' ? 'light' : 'dark'));

  const search = document.querySelector('[data-location-search]');
  const rows = [...document.querySelectorAll('[data-location]')];
  const empty = document.querySelector('[data-location-empty]');
  search?.addEventListener('input', () => {
    const query = search.value.trim().toLowerCase();
    let matches = 0;
    rows.forEach((row) => {
      const visible = !query || row.dataset.location.includes(query);
      row.hidden = !visible;
      if (visible) matches += 1;
    });
    if (empty) empty.hidden = matches > 0;
  });

  const stage = document.querySelector('[data-phone-stage]');
  const viewport = document.querySelector('[data-phone-viewport]');
  const screen = document.querySelector('[data-phone-screen]');
  let ticking = false;

  const updatePhoneScroll = () => {
    ticking = false;
    if (!stage || !viewport || !screen || reducedMotion.matches || window.innerWidth < 761) return;
    const rect = stage.getBoundingClientRect();
    const travel = Math.max(1, stage.offsetHeight - window.innerHeight);
    const progress = Math.min(1, Math.max(0, -rect.top / travel));
    const max = Math.max(0, screen.scrollHeight - viewport.clientHeight);
    screen.style.transform = `translate3d(0, ${(-max * progress).toFixed(2)}px, 0)`;
  };
  const requestPhoneScroll = () => {
    if (!ticking) {
      ticking = true;
      requestAnimationFrame(updatePhoneScroll);
    }
  };
  screen?.addEventListener('load', requestPhoneScroll);
  window.addEventListener('scroll', requestPhoneScroll, { passive: true });
  window.addEventListener('resize', requestPhoneScroll);
  requestPhoneScroll();

  const scene = document.querySelector('[data-parallax-scene]');
  if (scene && !reducedMotion.matches) {
    scene.addEventListener('pointermove', (event) => {
      const rect = scene.getBoundingClientRect();
      const x = ((event.clientX - rect.left) / rect.width - 0.5) * 8;
      const y = ((event.clientY - rect.top) / rect.height - 0.5) * -8;
      scene.style.transform = `translate3d(${x}px, ${y}px, 0)`;
    });
    scene.addEventListener('pointerleave', () => { scene.style.transform = ''; });
  }
})();
