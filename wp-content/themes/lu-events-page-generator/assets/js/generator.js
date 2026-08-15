(() => {
  const form = document.querySelector('#event-builder');
  if (!form || !window.LUBuilder) return;

  const $ = (selector, scope = document) => scope.querySelector(selector);
  const $$ = (selector, scope = document) => [...scope.querySelectorAll(selector)];
  const preview = $('[data-preview-canvas]');
  const result = $('[data-launch-result]');
  const editors = $('[data-location-editors]');
  const slugInput = form.elements.site_slug;
  let slugWasEdited = false;

  const slugify = (value) => value.toLowerCase().trim().replace(/[^a-z0-9]+/g, '-').replace(/(^-|-$)/g, '');
  const field = (name) => form.elements[name];
  const value = (name) => field(name)?.value?.trim() || '';
  const setText = (selector, text) => { const node = $(selector); if (node) node.textContent = text; };
  const readLocations = () => $$('.location-editor', editors).map((editor) => {
    const data = {};
    $$('[data-location-field]', editor).forEach((input) => { data[input.dataset.locationField] = input.value.trim(); });
    return data;
  }).filter((location) => location.name);

  const settings = () => ({
    restaurant_name: value('restaurant_name'),
    event_name: value('event_name'),
    eyebrow: value('eyebrow'),
    headline: value('headline'),
    intro: value('intro'),
    schedule_day: value('schedule_day'),
    schedule_time: value('schedule_time'),
    team_size: value('team_size'),
    primary_color: value('primary_color'),
    accent_color: value('accent_color'),
    highlight_color: value('highlight_color'),
    default_theme: value('default_theme'),
    theme_toggle: field('theme_toggle').checked,
    locations: readLocations(),
    custom_domain: value('custom_domain')
  });

  const updatePreview = () => {
    const data = settings();
    preview.style.setProperty('--p-primary', data.primary_color);
    preview.style.setProperty('--p-accent', data.accent_color);
    preview.style.setProperty('--p-highlight', data.highlight_color);
    preview.dataset.previewTheme = data.default_theme;
    setText('[data-preview-event]', data.event_name || 'Event night');
    setText('[data-preview-restaurant]', data.restaurant_name || 'Restaurant');
    setText('[data-preview-eyebrow]', data.eyebrow);
    setText('[data-preview-headline]', data.headline);
    setText('[data-preview-intro]', data.intro);
    setText('[data-preview-schedule]', `Live every ${data.schedule_day} · ${data.schedule_time} · Teams of ${data.team_size}`);
    setText('[data-preview-url]', `${location.host}/${value('site_slug') || 'event-night'}/`);
    $$('input[type="color"]', form).forEach((input) => { const output = input.parentElement.querySelector('output'); if (output) output.value = input.value; });
  };

  form.addEventListener('input', (event) => {
    if (event.target === slugInput) slugWasEdited = true;
    if (event.target === field('event_name') && !slugWasEdited) slugInput.value = slugify(event.target.value);
    updatePreview();
  });
  form.addEventListener('change', updatePreview);

  $$('input[type="file"]', form).forEach((input) => input.addEventListener('change', () => {
    const file = input.files?.[0];
    if (!file) return;
    const url = URL.createObjectURL(file);
    if (input.name === 'hero_image') $('[data-preview-hero]').style.backgroundImage = `url('${url}')`;
    if (input.name === 'app_screenshot') $('[data-preview-app]').src = url;
    if (input.name === 'logo') {
      const brand = $('[data-preview-logo]');
      brand.innerHTML = `<img src="${url}" alt=""><span><strong>${value('event_name')}</strong><small>${value('restaurant_name')}</small></span>`;
    }
  }));

  $('[data-preview-theme-toggle]')?.addEventListener('click', () => {
    preview.dataset.previewTheme = preview.dataset.previewTheme === 'dark' ? 'light' : 'dark';
  });

  $$('[data-preview-size]').forEach((button) => button.addEventListener('click', () => {
    $$('[data-preview-size]').forEach((item) => item.classList.toggle('is-active', item === button));
    $('[data-preview-stage]').classList.toggle('is-mobile', button.dataset.previewSize === 'mobile');
  }));

  const wireLocation = (editor) => {
    $('[data-remove-location]', editor)?.addEventListener('click', () => { editor.remove(); updateRemoveButtons(); updatePreview(); });
    $$('input', editor).forEach((input) => input.addEventListener('input', updatePreview));
  };
  const updateRemoveButtons = () => {
    const all = $$('.location-editor', editors);
    all.forEach((editor) => { $('[data-remove-location]', editor).hidden = all.length === 1; });
  };
  $$('.location-editor', editors).forEach(wireLocation);
  $('[data-add-location]')?.addEventListener('click', () => {
    const editor = $('.location-editor', editors).cloneNode(true);
    $$('input', editor).forEach((input) => { input.value = ''; });
    editors.append(editor);
    wireLocation(editor);
    updateRemoveButtons();
  });

  const loadSites = async () => {
    const target = $('[data-recent-sites]');
    try {
      const response = await fetch(`${LUBuilder.restUrl}/sites`, { headers: { 'X-WP-Nonce': LUBuilder.nonce } });
      const sites = await response.json();
      if (!response.ok) throw new Error(sites.message || 'Could not load sites.');
      target.innerHTML = sites.length ? sites.map((site) => `<article class="recent-site"><strong>${site.name}</strong><small>${site.url}</small><div class="recent-site__links"><a href="${site.url}" target="_blank" rel="noopener">View</a><a href="${site.edit_url}">Edit</a></div></article>`).join('') : '<p class="loading-line">Your first event site will appear here.</p>';
    } catch (error) {
      target.innerHTML = `<p class="launch-result__error">${error.message}</p>`;
    }
  };

  form.addEventListener('submit', async (event) => {
    event.preventDefault();
    const button = form.querySelector('[type="submit"]');
    const original = button.innerHTML;
    button.disabled = true;
    button.innerHTML = '<span>Building the site…</span>';
    result.innerHTML = '';
    const payload = new FormData();
    payload.append('site_slug', value('site_slug'));
    payload.append('settings', JSON.stringify(settings()));
    ['logo', 'hero_image', 'app_screenshot', 'location_image'].forEach((name) => {
      const file = field(name)?.files?.[0];
      if (file) payload.append(name, file, file.name);
    });
    try {
      const response = await fetch(`${LUBuilder.restUrl}/sites`, { method: 'POST', headers: { 'X-WP-Nonce': LUBuilder.nonce }, body: payload });
      const site = await response.json();
      if (!response.ok) throw new Error(site.message || 'The event site could not be created.');
      result.innerHTML = `<div class="launch-result__success"><strong>${site.name} is live.</strong> <a href="${site.url}" target="_blank" rel="noopener">Open the event site</a> or <a href="${site.edit_url}">edit its ACF settings</a>.</div>`;
      loadSites();
    } catch (error) {
      result.innerHTML = `<div class="launch-result__error"><strong>Not launched yet.</strong> ${error.message}</div>`;
    } finally {
      button.disabled = false;
      button.innerHTML = original;
    }
  });

  updateRemoveButtons();
  updatePreview();
  loadSites();
})();
