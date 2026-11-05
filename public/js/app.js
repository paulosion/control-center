(function () {
  const $ = (s, r = document) => r.querySelector(s);
  const store = {
    get(k) { try { return localStorage.getItem(k); } catch (e) { return null; } },
    set(k, v) { try { localStorage.setItem(k, v); } catch (e) {} },
  };

  /* theme: remembered per device */
  const saved = store.get('cc-theme');
  if (saved) document.documentElement.dataset.theme = saved;
  const tb = $('#theme-btn');
  if (tb) tb.addEventListener('click', () => {
    const r = document.documentElement;
    const cur = r.dataset.theme || (matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light');
    r.dataset.theme = cur === 'dark' ? 'light' : 'dark';
    store.set('cc-theme', r.dataset.theme);
  });

  /* clock */
  const clock = $('#clock');
  const tick = () => { if (clock) { const n = new Date(); clock.textContent = n.toLocaleDateString('en-US', { weekday: 'short', month: 'short', day: 'numeric' }) + ' · ' + n.toLocaleTimeString('en-US', { hour: '2-digit', minute: '2-digit' }); } };
  tick(); setInterval(tick, 30000);

  /* phone: keep the current tab in view in the sliding tab strip */
  const act = $('.rail a[aria-current]');
  const rail = $('.rail');
  if (act && rail && matchMedia('(max-width:860px)').matches) {
    rail.scrollLeft = act.offsetLeft - (rail.clientWidth - act.offsetWidth) / 2;
  }

  /* list filters: <div class="seg" data-filter-for="#list"> + li[data-bad] */
  document.querySelectorAll('.seg[data-filter-for]').forEach(seg => {
    const list = $(seg.dataset.filterFor);
    seg.addEventListener('click', e => {
      const b = e.target.closest('button'); if (!b) return;
      seg.querySelectorAll('button').forEach(x => x.setAttribute('aria-pressed', x === b));
      list.querySelectorAll('li').forEach(li => { li.hidden = b.dataset.f === 'bad' && li.dataset.bad !== '1'; });
    });
  });

  /* to-do: forms post with fetch and swap in the re-rendered panel */
  const csrf = $('meta[name="csrf-token"]')?.content;
  document.addEventListener('submit', async e => {
    const f = e.target.closest('form[data-todo]');
    if (!f) return;
    e.preventDefault();
    const target = f.closest('[data-todo-panel]');
    const body = new FormData(f);
    body.append('panel', target ? target.dataset.todoPanel : 'full');
    try {
      const res = await fetch(f.action, { method: 'POST', body, headers: { 'X-CSRF-TOKEN': csrf, 'X-Requested-With': 'XMLHttpRequest', Accept: 'text/html' } });
      if (res.status === 419 || res.redirected) { location.reload(); return; }
      if (!res.ok) throw new Error(res.status);
      const html = await res.text();
      const tmp = document.createElement('div'); tmp.innerHTML = html.trim();
      const fresh = tmp.firstElementChild;
      target.replaceWith(fresh);
      document.querySelectorAll('[data-todo-count]').forEach(el => { el.textContent = fresh.dataset.openCount; });
      const input = fresh.querySelector('input[name="title"]');
      if (f.matches('.quick, .add') && input) input.focus();
    } catch (err) { f.submit(); }
  });
  // checkbox toggles submit their tiny form right away
  document.addEventListener('change', e => {
    if (e.target.matches('form[data-todo] input[type=checkbox]')) e.target.form.requestSubmit();
  });

  /* refresh every 60 s while the tab is visible and you aren't typing */
  if (document.body.dataset.autorefresh) {
    setInterval(() => {
      const typing = document.activeElement && /INPUT|SELECT|TEXTAREA/.test(document.activeElement.tagName);
      const opened = document.querySelector('details[open]');
      if (document.visibilityState === 'visible' && !typing && !opened) location.reload();
    }, 60000);
  }
})();
