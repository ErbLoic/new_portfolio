/**
 * ╔══════════════════════════════════════════════════════════════════════╗
 * ║  EASTER EGGS — Portfolio                                             ║
 * ║                                                                      ║
 * ║  Tu lis le code source ? Respect. Voici la liste (chut 🤫) :           ║
 * ║   sudo / `      → terminal interactif (mobile : 5 taps sur le badge)  ║
 * ║   jedi / sith   → sabre laser au curseur                             ║
 * ║   luffy         → page élastique                                     ║
 * ║   zoro          → triple coupe… et il se perd                        ║
 * ║   party         → confettis                                          ║
 * ╚══════════════════════════════════════════════════════════════════════╝
 */
(function () {
  'use strict';

  const DATA = () => window.PORTFOLIO_DATA || {};
  const REDUCED = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
  const $ = (s, r = document) => r.querySelector(s);
  const $$ = (s, r = document) => [...r.querySelectorAll(s)];
  const esc = (v) => String(v ?? '').replace(/[&<>"']/g, (c) => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]));
  const rand = (a, b) => a + Math.random() * (b - a);
  const sleep = (ms) => new Promise((r) => setTimeout(r, ms));

  // ════════════════════════════════════════════════════════════════════
  //  TROPHÉES
  // ════════════════════════════════════════════════════════════════════
  const TROPHIES = {
    terminal: { icon: '⌨️', name: 'Root access', hint: 'Les vrais devs parlent en sudo.' },
    saber: { icon: '⚔️', name: 'Que la Force…', hint: 'Choisis ton camp : lumineux ou obscur.' },
    luffy: { icon: '🍖', name: 'Gomu Gomu', hint: 'Un capitaine au chapeau de paille.' },
    zoro: { icon: '🧭', name: 'Sens de l’orientation', hint: 'Un sabreur qui se perd toujours.' },
    party: { icon: '🎉', name: 'Fêtard', hint: 'Tape le mot de la fête.' },
  };
  const STORE_KEY = 'portfolio_eggs_v2';
  const store = {
    get() { try { return JSON.parse(localStorage.getItem(STORE_KEY)) || {}; } catch { return {}; } },
    set(v) { try { localStorage.setItem(STORE_KEY, JSON.stringify(v)); } catch { /* navigation privée */ } },
  };
  /** Ne compte que les trophées existants (ignore ceux d'anciennes versions). */
  const countFound = (found = {}) => Object.keys(found || {}).filter((id) => id in TROPHIES).length;
  function unlock(id) {
    const s = store.get();
    s.found = s.found || {};
    if (s.found[id]) return;
    s.found[id] = Date.now();
    store.set(s);
    const n = countFound(s.found), total = Object.keys(TROPHIES).length;
    const t = TROPHIES[id];
    toast(`${t.icon} Trophée débloqué : ${t.name}`, n === total ? '🏆 100 % — tu as tout trouvé. Recrute-moi ?' : `${n}/${total} easter eggs trouvés`, n === total ? 'gold' : 'trophy');
    if (n === total) setTimeout(() => party(true), 600);
  }

  // ════════════════════════════════════════════════════════════════════
  //  STYLES (injectés une seule fois)
  // ════════════════════════════════════════════════════════════════════
  const css = document.createElement('style');
  css.id = 'eggs-css';
  css.textContent = `
  .egg-toasts{position:fixed;right:20px;bottom:20px;z-index:2147483600;display:flex;flex-direction:column;gap:10px;pointer-events:none;max-width:min(360px,calc(100vw - 40px))}
  .egg-toast{pointer-events:auto;background:rgba(15,15,30,.92);backdrop-filter:blur(12px);color:#fff;border-radius:14px;padding:12px 16px;font:500 14px/1.4 'DM Sans',system-ui,sans-serif;box-shadow:0 12px 40px rgba(0,0,0,.35);border:1px solid rgba(255,255,255,.08);animation:egg-in .5s cubic-bezier(.2,1.4,.4,1) both}
  .egg-toast b{display:block;font-size:15px;margin-bottom:2px}
  .egg-toast small{opacity:.75}
  .egg-toast.trophy{border-color:rgba(124,58,237,.6);box-shadow:0 0 0 1px rgba(124,58,237,.3),0 12px 40px rgba(124,58,237,.35)}
  .egg-toast.gold{background:linear-gradient(135deg,#7c5a00,#d4a106);border-color:#ffe28a}
  .egg-toast.out{animation:egg-out .35s ease forwards}
  @keyframes egg-in{from{opacity:0;transform:translateY(20px) scale(.9)}}
  @keyframes egg-out{to{opacity:0;transform:translateX(40px)}}
  .egg-layer{position:fixed;inset:0;z-index:2147483500}
  .egg-canvas{position:fixed;inset:0;pointer-events:none;z-index:2147483400}

  /* Terminal */
  .egg-term-wrap{background:rgba(5,8,15,.55);backdrop-filter:blur(4px);display:flex;align-items:center;justify-content:center;padding:16px;animation:egg-fade .25s ease}
  @keyframes egg-fade{from{opacity:0}}
  .egg-term{--c:#39ff88;width:min(820px,100%);height:min(520px,82vh);background:#0b0f14;border-radius:12px;box-shadow:0 30px 80px rgba(0,0,0,.6),0 0 0 1px rgba(255,255,255,.06),0 0 60px color-mix(in srgb,var(--c) 18%,transparent);display:flex;flex-direction:column;overflow:hidden;font:14px/1.55 'JetBrains Mono','Fira Code',Consolas,monospace;color:#d7e0ea;animation:egg-pop .35s cubic-bezier(.2,1.3,.4,1)}
  @keyframes egg-pop{from{transform:scale(.92) translateY(12px);opacity:0}}
  .egg-term.amber{--c:#ffb000}.egg-term.blue{--c:#4fc3ff}.egg-term.pink{--c:#ff5fd2}
  .egg-term__bar{display:flex;align-items:center;gap:8px;padding:10px 14px;background:#121821;border-bottom:1px solid #1d2632;user-select:none}
  .egg-term__bar i{width:12px;height:12px;border-radius:50%;display:inline-block;cursor:pointer}
  .egg-term__bar span{margin-left:8px;font-size:12px;color:#7b8794}
  .egg-term__body{flex:1;overflow-y:auto;padding:14px 16px;scrollbar-width:thin;position:relative}
  .egg-term__body::after{content:'';position:absolute;inset:0;pointer-events:none;background:repeating-linear-gradient(0deg,rgba(255,255,255,.025) 0 1px,transparent 1px 3px)}
  .egg-term pre{margin:0;white-space:pre-wrap;word-break:break-word;font:inherit}
  .egg-term .c{color:var(--c)}.egg-term .m{color:#7b8794}.egg-term .w{color:#fff;font-weight:700}.egg-term .e{color:#ff6b6b}.egg-term .y{color:#ffd166}
  .egg-term a{color:var(--c)}
  .egg-term__line{display:flex;gap:8px;align-items:center}
  .egg-term__line input{flex:1;background:none;border:0;outline:0;color:#fff;font:inherit;caret-color:var(--c);min-width:0}
  .egg-term .ps{color:var(--c);white-space:nowrap}


  /* Sabre */
  body.egg-saber, body.egg-saber *{cursor:none!important}
  /* Luffy */
  .egg-rubber{cursor:grab!important;will-change:transform;user-select:none}
  .egg-rubber:active{cursor:grabbing!important}
  /* Zoro */
  .egg-slash{position:fixed;left:-10vw;width:120vw;height:4px;z-index:2147483450;pointer-events:none;background:linear-gradient(90deg,transparent,#d9fff0 30%,#fff 50%,#d9fff0 70%,transparent);box-shadow:0 0 18px #6dffb0,0 0 40px #2bd17e;transform-origin:center;animation:egg-slash .45s cubic-bezier(.7,0,.3,1) forwards}
  @keyframes egg-slash{0%{clip-path:inset(0 100% 0 0)}60%{clip-path:inset(0 0 0 0);opacity:1}100%{clip-path:inset(0 0 0 100%);opacity:0}}
  .egg-shake{animation:egg-shake .4s}
  @keyframes egg-shake{20%{transform:translate(-6px,3px)}40%{transform:translate(5px,-4px)}60%{transform:translate(-3px,5px)}80%{transform:translate(3px,-2px)}}
  .egg-banner{position:fixed;left:50%;top:42%;z-index:2147483560;pointer-events:none;transform:translate(-50%,-50%);font:900 clamp(28px,6vw,64px)/1 'Cormorant Garamond',Georgia,serif;font-style:italic;color:#fff;text-shadow:0 4px 30px rgba(0,0,0,.5);white-space:nowrap;animation:egg-banner 2.4s cubic-bezier(.2,1.2,.3,1) forwards}
  @keyframes egg-banner{0%{opacity:0;transform:translate(-50%,-50%) scale(.6)}15%{opacity:1;transform:translate(-50%,-50%) scale(1.05)}25%{transform:translate(-50%,-50%) scale(1)}80%{opacity:1}100%{opacity:0;transform:translate(-50%,-60%)}}
  `;
  document.head.appendChild(css);

  // ════════════════════════════════════════════════════════════════════
  //  TOASTS
  // ════════════════════════════════════════════════════════════════════
  function toast(title, sub = '', type = '') {
    let box = $('.egg-toasts');
    if (!box) { box = document.createElement('div'); box.className = 'egg-toasts'; document.body.appendChild(box); }
    const t = document.createElement('div');
    t.className = 'egg-toast ' + type;
    t.innerHTML = `<b>${esc(title)}</b>${sub ? `<small>${esc(sub)}</small>` : ''}`;
    box.appendChild(t);
    setTimeout(() => { t.classList.add('out'); setTimeout(() => t.remove(), 400); }, 4200);
  }

  function banner(text, color = '#fff') {
    const b = document.createElement('div');
    b.className = 'egg-banner';
    b.style.color = color;
    b.textContent = text;
    document.body.appendChild(b);
    setTimeout(() => b.remove(), 2500);
  }

  // ════════════════════════════════════════════════════════════════════
  //  AUDIO (synthèse Web Audio, aucun fichier)
  // ════════════════════════════════════════════════════════════════════
  let actx = null;
  const ac = () => (actx ??= new (window.AudioContext || window.webkitAudioContext)());
  function tone(freq, dur = 0.12, type = 'square', vol = 0.06, slide = 0, delay = 0) {
    try {
      const c = ac(), t = c.currentTime + delay, o = c.createOscillator(), g = c.createGain();
      o.type = type; o.frequency.setValueAtTime(freq, t);
      if (slide) o.frequency.exponentialRampToValueAtTime(Math.max(30, freq + slide), t + dur);
      g.gain.setValueAtTime(vol, t); g.gain.exponentialRampToValueAtTime(0.0001, t + dur);
      o.connect(g).connect(c.destination); o.start(t); o.stop(t + dur + 0.02);
    } catch { /* audio indisponible */ }
  }
  function noise(dur = 0.2, freq = 1200, vol = 0.2, delay = 0) {
    try {
      const c = ac(), t = c.currentTime + delay, b = c.createBuffer(1, c.sampleRate * dur, c.sampleRate), d = b.getChannelData(0);
      for (let i = 0; i < d.length; i++) d[i] = (Math.random() * 2 - 1) * (1 - i / d.length) ** 2;
      const s = c.createBufferSource(), f = c.createBiquadFilter(), g = c.createGain();
      s.buffer = b; f.type = 'bandpass'; f.frequency.value = freq; g.gain.value = vol;
      s.connect(f).connect(g).connect(c.destination); s.start(t);
    } catch { /* audio indisponible */ }
  }
  const sfx = {
    blip: () => tone(880, 0.05, 'square', 0.03),
    squash: (combo) => { noise(0.12, 500, 0.25); tone(220 + combo * 40, 0.1, 'square', 0.05, 300); },
    gold: () => [0, 0.08, 0.16].forEach((d, i) => tone(660 * (1 + i * 0.25), 0.12, 'triangle', 0.06, 0, d)),
    win: () => [523, 659, 784, 1046].forEach((f, i) => tone(f, 0.18, 'triangle', 0.07, 0, i * 0.12)),
    boing: () => tone(160, 0.35, 'sine', 0.12, 260),
    slash: (d = 0) => { noise(0.18, 3500, 0.3, d); tone(1800, 0.15, 'sawtooth', 0.02, -1500, d); },
    thud: () => { noise(0.15, 180, 0.35); tone(90, 0.15, 'sine', 0.15, -40); },
    pop: () => tone(rand(500, 900), 0.06, 'sine', 0.05, 200),
  };

  // ════════════════════════════════════════════════════════════════════
  //  CLAVIER : mots tapés
  // ════════════════════════════════════════════════════════════════════
  const WORDS = {
    sudo: () => terminal.open(),
    jedi: () => saber.toggle('jedi'),
    sith: () => saber.toggle('sith'),
    luffy: () => rubber.toggle(),
    zoro: () => zoro(),
    party: () => party(),
  };
  let buffer = '', bufTimer;

  document.addEventListener('keydown', (e) => {
    const el = document.activeElement;
    const typing = el && (['INPUT', 'TEXTAREA', 'SELECT'].includes(el.tagName) || el.isContentEditable);
    if (e.key === 'Escape') { stopAll(); return; }
    if (typing || e.ctrlKey || e.metaKey || e.altKey) return;

    const k = e.key.toLowerCase();
    if (e.key === '`' || e.key === '²') { e.preventDefault(); terminal.open(); return; }
    if (k.length !== 1) return;
    clearTimeout(bufTimer);
    bufTimer = setTimeout(() => (buffer = ''), 1800);
    buffer = (buffer + k).slice(-12);
    for (const word in WORDS) {
      if (buffer.endsWith(word)) { buffer = ''; WORDS[word](); break; }
    }
  });

  function stopAll() {
    terminal.close(); saber.stop(); rubber.stop();
  }

  // ════════════════════════════════════════════════════════════════════
  //  1. TERMINAL INTERACTIF
  // ════════════════════════════════════════════════════════════════════
  const terminal = (() => {
    let wrap, body, input, hist = [], hIdx = 0, theme = '';
    const user = () => (DATA().portfolio?.first_name || 'guest').toLowerCase().normalize('NFD').replace(/[^a-z]/g, '') || 'guest';
    const ps = () => `<span class="ps">${esc(user())}@portfolio:~$</span>`;

    function print(html, cls = '') {
      const p = document.createElement('pre');
      if (cls) p.className = cls;
      p.innerHTML = html;
      body.insertBefore(p, body.lastElementChild);
      body.scrollTop = body.scrollHeight;
    }
    async function type(text, cls = 'c', speed = 12) {
      const p = document.createElement('pre'); p.className = cls;
      body.insertBefore(p, body.lastElementChild);
      for (const ch of text) { p.textContent += ch; body.scrollTop = body.scrollHeight; if (!REDUCED) await sleep(speed); }
    }
    const list = (items, fmt) => items.length ? items.map(fmt).join('\n') : '<span class="m">(vide)</span>';

    const CMDS = {
      help: () => print(
        `<span class="w">Commandes disponibles</span>\n` +
        [['whoami', 'qui suis-je'], ['ls [projets|perso|xp|formations]', 'lister le contenu'], ['open &lt;n°&gt;', 'ouvrir un projet de la dernière liste'],
          ['cv', 'télécharger mon CV'], ['contact', 'me joindre'], ['socials', 'mes réseaux'], ['stack', 'technos que j’utilise'],
          ['eggs', 'tes trophées'], ['theme [green|amber|blue|pink]', 'changer de couleur'],
          ['party', 'surprise'], ['clear', 'effacer'], ['exit', 'fermer (ou Échap)']]
          .map(([c, d]) => `  <span class="c">${c.padEnd(34)}</span><span class="m">${d}</span>`).join('\n')),
      whoami: () => {
        const p = DATA().portfolio || {};
        print(`<span class="w">${esc(p.first_name)} ${esc(p.last_name)}</span>\n${esc(p.bio || 'Étudiant·e BTS SIO')}\n<span class="m">📍 ${esc(p.location || '—')} · promo ${esc(p.year_start)}–${esc(p.year_end)}</span>`);
      },
      ls: (arg = '') => {
        const d = DATA();
        const a = arg.toLowerCase();
        if (['projets', 'projects', 'p'].includes(a) || !a) {
          last = (d.projects || []).map((p) => ({ url: `/projet/${p.id}`, title: p.title }));
          print(`<span class="w">projets/</span>\n` + list(d.projects || [], (p, i) => `  <span class="y">[${i + 1}]</span> ${esc(p.title)} <span class="m">· ${esc(p.type)} · ${esc(p.year)}</span>`));
          if (!a) print(`<span class="m">Aussi : ls perso · ls xp · ls formations</span>`);
        } else if (['perso', 'side'].includes(a)) {
          last = (d.personalProjects || []).map((p) => ({ url: `/projet-perso/${p.id}`, title: p.title }));
          print(`<span class="w">perso/</span>\n` + list(d.personalProjects || [], (p, i) => `  <span class="y">[${i + 1}]</span> ${esc(p.title)}${p.is_featured ? ' ★' : ''} <span class="m">· ${esc(p.category || '')}</span>`));
        } else if (['xp', 'experiences', 'stages'].includes(a)) {
          print(`<span class="w">experiences/</span>\n` + list(d.stages || [], (s) => `  ${esc(s.role)} <span class="m">@ ${esc(s.company?.name)} · ${esc(s.type || 'stage')}${s.is_current ? ' · <span class="c">en cours</span>' : ''}</span>`));
        } else if (['formations', 'school'].includes(a)) {
          print(`<span class="w">formations/</span>\n` + list(d.formations || [], (f) => `  ${esc(f.title)} <span class="m">· ${esc(f.school)}${f.is_current ? ' · en cours' : ''}</span>`));
        } else print(`<span class="e">ls: ${esc(arg)}: dossier introuvable</span>`);
      },
      open: (n) => {
        const item = last[parseInt(n, 10) - 1];
        if (!item) return print(`<span class="e">open: fais d'abord « ls » puis « open 1 »</span>`);
        print(`<span class="m">→ ouverture de ${esc(item.title)}…</span>`);
        setTimeout(() => (location.href = item.url), 450);
      },
      cv: () => {
        const url = DATA().portfolio?.cv_url;
        if (!url) return print('<span class="m">CV bientôt disponible.</span>');
        print(`<span class="c">✔ CV prêt :</span> <a href="${esc(url)}" target="_blank" rel="noopener">${esc(url.split('/').pop())}</a>`);
        window.open(url, '_blank', 'noopener');
      },
      contact: () => {
        const p = DATA().portfolio || {};
        print(`📧 ${p.email ? `<a href="mailto:${esc(p.email)}">${esc(p.email)}</a>` : '—'}\n<span class="m">ou utilise le formulaire en bas de page</span>`);
      },
      socials: () => {
        const p = DATA().portfolio || {};
        print([p.github_url && `GitHub   <a href="${esc(p.github_url)}" target="_blank" rel="noopener">${esc(p.github_url)}</a>`,
          p.linkedin_url && `LinkedIn <a href="${esc(p.linkedin_url)}" target="_blank" rel="noopener">${esc(p.linkedin_url)}</a>`].filter(Boolean).join('\n') || '<span class="m">—</span>');
      },
      stack: () => {
        const d = DATA(), count = {};
        [...(d.projects || []), ...(d.realisations || []), ...(d.personalProjects || [])].forEach((p) => (p.tags || []).forEach((t) => (count[t.tag] = (count[t.tag] || 0) + 1)));
        const top = Object.entries(count).sort((a, b) => b[1] - a[1]).slice(0, 10);
        if (!top.length) return print('<span class="m">(aucune techno renseignée)</span>');
        const max = top[0][1];
        print(top.map(([t, n]) => `  ${esc(t).padEnd(16)} <span class="c">${'█'.repeat(Math.max(1, Math.round((n / max) * 20)))}</span> <span class="m">${n}</span>`).join('\n'));
      },
      eggs: () => {
        const f = store.get().found || {}, ids = Object.keys(TROPHIES);
        print(`<span class="w">Trophées ${countFound(f)}/${ids.length}</span>\n` + ids.map((id) => f[id]
          ? `  ${TROPHIES[id].icon} <span class="c">${esc(TROPHIES[id].name)}</span>`
          : `  🔒 <span class="m">??? — ${esc(TROPHIES[id].hint)}</span>`).join('\n'));
      },
      theme: (t = '') => {
        const term = $('.egg-term');
        term.classList.remove('amber', 'blue', 'pink');
        theme = ['amber', 'blue', 'pink'].includes(t) ? t : '';
        if (theme) term.classList.add(theme);
        print(`<span class="c">thème : ${theme || 'green'}</span>`);
      },
      date: () => print(new Date().toLocaleString('fr-FR', { dateStyle: 'full', timeStyle: 'short' })),
      echo: (...a) => print(esc(a.join(' '))),
      clear: () => { $$('pre', body).forEach((p) => p.remove()); },
      exit: () => close(),
      party: () => { close(); party(); },
      coffee: () => print('<span class="y">☕ Erreur 418 : je suis une théière.</span>\n<span class="m">(RFC 2324 — oui, ça existe vraiment)</span>'),
      hire: () => print('<span class="c">✔ Excellente décision.</span> Tape <span class="w">contact</span> 😉'),
      sudo: (...a) => {
        const c = a.join(' ');
        if (/rm\s+-rf/.test(c)) return nuke();
        if (c === 'hire me' || c === 'hire') return CMDS.hire();
        print(`<span class="e">[sudo] mot de passe pour ${esc(user())} :</span> <span class="m">bien essayé 😏 — tu es déjà root ici.</span>`);
      },
      rm: () => print('<span class="e">rm: permission refusée. Essaie avec sudo… si tu oses.</span>'),
      vim: () => print('<span class="m">Tu es entré dans vim. Personne n’en est jamais ressorti.\nTape :q! … non, je plaisante, tape exit.</span>'),
    };
    let last = [];

    async function nuke() {
      await type('Suppression de / en cours…', 'e', 18);
      for (const f of ['/bin', '/etc', '/home/portfolio', '/var/www/projets', '/usr/share/motivation']) {
        print(`<span class="e">rm -rf ${f}</span> <span class="m">... ok</span>`);
        await sleep(REDUCED ? 0 : 160);
      }
      const root = document.documentElement;
      root.style.transition = 'filter .6s';
      root.style.filter = 'grayscale(1) blur(1px)';
      // Toujours restauré, même si le terminal est fermé entre-temps
      setTimeout(() => { root.style.filter = ''; setTimeout(() => (root.style.transition = ''), 700); }, 900);
      await sleep(900);
      await type('…mais non. Tout est sauvegardé. Les vrais devs font des backups 😎', 'c', 14);
    }

    function run(line) {
      const [cmd, ...args] = line.trim().split(/\s+/);
      if (!cmd) return;
      const fn = CMDS[cmd.toLowerCase()];
      if (fn) fn(...args);
      else print(`<span class="e">${esc(cmd)}: commande introuvable</span> <span class="m">— tape « help »</span>`);
    }

    async function open() {
      if (wrap) { input?.focus(); return; }
      unlock('terminal');
      wrap = document.createElement('div');
      wrap.className = 'egg-layer egg-term-wrap';
      wrap.innerHTML = `<div class="egg-term ${theme}" role="dialog" aria-label="Terminal">
          <div class="egg-term__bar"><i style="background:#ff5f57" data-x></i><i style="background:#febc2e"></i><i style="background:#28c840"></i><span>portfolio — zsh — 80×24</span></div>
          <div class="egg-term__body"><div class="egg-term__line">${ps()}<input aria-label="commande" autocomplete="off" autocapitalize="off" spellcheck="false"></div></div>
        </div>`;
      document.body.appendChild(wrap);
      body = $('.egg-term__body', wrap);
      input = $('input', wrap);
      wrap.addEventListener('mousedown', (e) => { if (e.target === wrap) close(); });
      $('[data-x]', wrap).addEventListener('click', close);
      $('.egg-term', wrap).addEventListener('click', () => input.focus());
      input.addEventListener('keydown', (e) => {
        if (e.key === 'Enter') {
          const v = input.value; input.value = '';
          print(`${ps()} ${esc(v)}`);
          if (v.trim()) { hist.push(v); hIdx = hist.length; }
          run(v);
        } else if (e.key === 'ArrowUp') { e.preventDefault(); if (hIdx > 0) input.value = hist[--hIdx]; }
        else if (e.key === 'ArrowDown') { e.preventDefault(); hIdx = Math.min(hist.length, hIdx + 1); input.value = hist[hIdx] || ''; }
        else if (e.key === 'Tab') {
          e.preventDefault();
          const m = Object.keys(CMDS).filter((c) => c.startsWith(input.value));
          if (m.length === 1) input.value = m[0] + ' ';
          else if (m.length) print(`<span class="m">${m.join('  ')}</span>`);
        } else if (e.key === 'l' && e.ctrlKey) { e.preventDefault(); CMDS.clear(); }
        e.stopPropagation();
      });
      // Saisie bloquée pendant l'animation d'accueil (sinon les lignes se mélangent)
      input.disabled = true;
      const p = DATA().portfolio || {};
      await type(`Last login: ${new Date().toLocaleString('fr-FR')} on ttys001`, 'm', 4);
      await type(`Bienvenue sur le portfolio de ${p.first_name || ''} ${p.last_name || ''} 👋`.replace(/\s+/g, ' '), 'w', 14);
      print('<span class="m">Tape</span> <span class="c">help</span> <span class="m">pour commencer. Tab = complétion, ↑↓ = historique.</span>');
      if (input.isConnected) { input.disabled = false; input.focus(); }
    }
    function close() { wrap?.remove(); wrap = null; }
    return { open, close };
  })();

  // ════════════════════════════════════════════════════════════════════
  //  5. SABRE LASER (jedi / sith)
  // ════════════════════════════════════════════════════════════════════
  const saber = (() => {
    let cv, ctx, raf, side = null, hum, humGain, mx = innerWidth / 2, my = innerHeight / 2, px = mx, py = my, ang = -Math.PI / 4, trail = [], sparks = [];
    const COL = { jedi: ['#4fc3ff', '#e8f8ff'], sith: ['#ff2a2a', '#ffe0e0'] };
    const move = (e) => { mx = e.clientX; my = e.clientY; };
    const clickSpark = (e) => {
      for (let i = 0; i < 26; i++) sparks.push({ x: e.clientX, y: e.clientY, vx: rand(-7, 7), vy: rand(-9, 3), life: 1 });
      noise(0.25, side === 'sith' ? 600 : 1100, 0.3); tone(side === 'sith' ? 90 : 140, 0.3, 'sawtooth', 0.08, -40);
    };
    function toggle(s) {
      if (side === s) return stop();
      if (side) stop();
      side = s; unlock('saber');
      cv = document.createElement('canvas'); cv.className = 'egg-canvas'; document.body.appendChild(cv);
      ctx = cv.getContext('2d'); cv.width = innerWidth; cv.height = innerHeight;
      document.body.classList.add('egg-saber');
      addEventListener('pointermove', move); addEventListener('pointerdown', clickSpark);
      try {
        const c = ac(); hum = c.createOscillator(); humGain = c.createGain(); const f = c.createBiquadFilter();
        hum.type = 'sawtooth'; hum.frequency.value = s === 'sith' ? 70 : 92; f.type = 'lowpass'; f.frequency.value = 380; humGain.gain.value = 0.0;
        hum.connect(f).connect(humGain).connect(c.destination); hum.start();
        humGain.gain.linearRampToValueAtTime(0.05, c.currentTime + 0.4);
      } catch { /* audio indisponible */ }
      tone(s === 'sith' ? 300 : 420, 0.6, 'sawtooth', 0.08, s === 'sith' ? -220 : -270); noise(0.8, s === 'sith' ? 450 : 720, 0.35);
      banner(s === 'sith' ? 'Le côté obscur t’appelle…' : 'Que la Force soit avec toi', COL[s][0]);
      toast(s === 'sith' ? '🔴 Côté obscur activé' : '🔵 Mode Jedi activé', 'Bouge et clique pour trancher · retape le mot ou Échap pour ranger le sabre');
      let len = 0;
      const draw = () => {
        cv.width !== innerWidth && (cv.width = innerWidth, cv.height = innerHeight);
        const dx = mx - px, dy = my - py, speed = Math.hypot(dx, dy);
        px += dx * 0.35; py += dy * 0.35;
        const target = speed > 2 ? Math.atan2(dy, dx) - Math.PI / 2 : ang;
        ang += (Math.atan2(Math.sin(target - ang), Math.cos(target - ang))) * 0.12;
        len = Math.min(140, len + 9);
        const tx = px + Math.cos(ang - Math.PI / 2) * len, ty = py + Math.sin(ang - Math.PI / 2) * len;
        trail.push({ x1: px, y1: py, x2: tx, y2: ty, life: 1 }); if (trail.length > 14) trail.shift();
        try { humGain && humGain.gain.setTargetAtTime(0.04 + Math.min(speed, 60) / 600, ac().currentTime, 0.05); hum && (hum.frequency.value = (side === 'sith' ? 70 : 92) + Math.min(speed, 80) * 0.6); } catch { }
        ctx.clearRect(0, 0, cv.width, cv.height);
        const [c, core] = COL[side];
        trail.forEach((t, i) => { t.life *= 0.82; ctx.strokeStyle = c; ctx.globalAlpha = t.life * 0.25 * (i / trail.length); ctx.lineWidth = 14; ctx.lineCap = 'round'; ctx.beginPath(); ctx.moveTo(t.x1, t.y1); ctx.lineTo(t.x2, t.y2); ctx.stroke(); });
        ctx.globalAlpha = 1;
        ctx.shadowColor = c; ctx.shadowBlur = 30 + Math.sin(performance.now() / 60) * 4;
        ctx.strokeStyle = c; ctx.lineWidth = 10; ctx.beginPath(); ctx.moveTo(px, py); ctx.lineTo(tx, ty); ctx.stroke();
        ctx.shadowBlur = 10; ctx.strokeStyle = core; ctx.lineWidth = 4; ctx.beginPath(); ctx.moveTo(px, py); ctx.lineTo(tx, ty); ctx.stroke();
        ctx.shadowBlur = 0;
        // poignée
        ctx.save(); ctx.translate(px, py); ctx.rotate(ang);
        const g = ctx.createLinearGradient(-5, 0, 5, 0); g.addColorStop(0, '#555'); g.addColorStop(0.5, '#ddd'); g.addColorStop(1, '#444');
        ctx.fillStyle = g; ctx.fillRect(-5, 0, 10, 34); ctx.fillStyle = '#111'; [8, 14, 20].forEach((y) => ctx.fillRect(-5, y, 10, 2)); ctx.fillStyle = side === 'sith' ? '#a00' : '#06c'; ctx.fillRect(-2, 26, 4, 4);
        ctx.restore();
        sparks = sparks.filter((s) => (s.life -= 0.03) > 0);
        sparks.forEach((s) => { s.vy += 0.35; s.x += s.vx; s.y += s.vy; ctx.fillStyle = s.life > 0.5 ? '#fff' : c; ctx.globalAlpha = s.life; ctx.fillRect(s.x, s.y, 3, 3); });
        ctx.globalAlpha = 1;
        raf = requestAnimationFrame(draw);
      };
      raf = requestAnimationFrame(draw);
    }
    function stop() {
      if (!side) return;
      cancelAnimationFrame(raf); cv?.remove();
      try { humGain.gain.setTargetAtTime(0, ac().currentTime, 0.08); hum.stop(ac().currentTime + 0.4); } catch { }
      tone(side === 'sith' ? 160 : 220, 0.4, 'sawtooth', 0.06, -140);
      removeEventListener('pointermove', move); removeEventListener('pointerdown', clickSpark);
      document.body.classList.remove('egg-saber');
      side = null; trail = []; sparks = [];
    }
    return { toggle, stop };
  })();

  // ════════════════════════════════════════════════════════════════════
  //  6. LUFFY — tout devient élastique
  // ════════════════════════════════════════════════════════════════════
  const rubber = (() => {
    let on = false, items = [], raf, endT;
    function toggle() { on ? stop() : start(); }
    function start() {
      on = true; unlock('luffy'); sfx.boing();
      banner('GOMU GOMU NO…', '#ffd400');
      setTimeout(() => sfx.boing(), 250);
      toast('🍖 Fruit du Démon avalé !', 'Attrape un titre, un bouton ou une carte et étire-le · Échap pour arrêter');
      items = $$('h1,h2,h3,.stage-card,.realisation-card,.btn-primary,.btn-white,.stat-item,#logo,.hero-badge,.formation-card-v2').map((el) => {
        const it = { el, x: 0, y: 0, vx: 0, vy: 0, drag: false, ox: 0, oy: 0, sx: 0, sy: 0, prev: el.style.cssText };
        el.classList.add('egg-rubber');
        it.down = (e) => {
          e.preventDefault(); it.drag = true; it.sx = e.clientX - it.x; it.sy = e.clientY - it.y;
          el.setPointerCapture?.(e.pointerId);
        };
        it.move = (e) => { if (it.drag) { it.x = e.clientX - it.sx; it.y = e.clientY - it.sy; } };
        it.up = () => { if (it.drag) { it.drag = false; if (Math.hypot(it.x, it.y) > 30) sfx.boing(); } };
        it.click = (e) => { if (Math.hypot(it.x, it.y) > 3 || it.drag) { e.preventDefault(); e.stopPropagation(); } };
        el.addEventListener('pointerdown', it.down); el.addEventListener('pointermove', it.move);
        el.addEventListener('pointerup', it.up); el.addEventListener('pointercancel', it.up); el.addEventListener('click', it.click, true);
        return it;
      });
      const step = () => {
        for (const it of items) {
          if (!it.drag) { it.vx += -it.x * 0.12; it.vy += -it.y * 0.12; it.vx *= 0.82; it.vy *= 0.82; it.x += it.vx; it.y += it.vy; }
          const d = Math.hypot(it.x, it.y), a = Math.atan2(it.y, it.x);
          const stretch = 1 + Math.min(d / 260, 1.2);
          it.el.style.transform = d < 0.2 && !it.drag ? '' :
            `translate(${it.x}px,${it.y}px) rotate(${a}rad) scale(${stretch},${1 / Math.sqrt(stretch)}) rotate(${-a}rad)`;
        }
        raf = requestAnimationFrame(step);
      };
      raf = requestAnimationFrame(step);
      endT = setTimeout(stop, 60000);
    }
    function stop() {
      if (!on) return;
      on = false; cancelAnimationFrame(raf); clearTimeout(endT);
      items.forEach((it) => {
        it.el.classList.remove('egg-rubber');
        ['pointerdown', 'pointermove', 'pointerup', 'pointercancel'].forEach((ev, i) => it.el.removeEventListener(ev, [it.down, it.move, it.up, it.up][i]));
        it.el.removeEventListener('click', it.click, true);
        it.el.style.cssText = it.prev;
      });
      items = [];
    }
    return { toggle, stop };
  })();

  // ════════════════════════════════════════════════════════════════════
  //  7. ZORO — santoryu… puis il se perd
  // ════════════════════════════════════════════════════════════════════
  async function zoro() {
    unlock('zoro');
    const angles = [-14, 9, -3];
    for (const [i, a] of angles.entries()) {
      const s = document.createElement('div');
      s.className = 'egg-slash';
      s.style.top = `${30 + i * 18}%`; s.style.transform = `rotate(${a}deg)`;
      document.body.appendChild(s); setTimeout(() => s.remove(), 500);
      sfx.slash();
      document.body.classList.add('egg-shake'); setTimeout(() => document.body.classList.remove('egg-shake'), 400);
      await sleep(REDUCED ? 0 : 230);
    }
    banner('SANTŌRYŪ !', '#4ade80');
    await sleep(1400);
    const sections = $$('section[id]').filter((s) => s.offsetHeight > 0);
    const target = sections.find((s) => s.id === 'contact') || sections.at(-1);
    const wrong = sections.filter((s) => s !== target);
    toast('🧭 Zoro part vers #' + (target?.id || 'contact') + '…', 'Ne t’inquiète pas, il connaît le chemin.');
    for (let i = 0; i < 3 && wrong.length; i++) {
      const w = wrong[Math.floor(Math.random() * wrong.length)];
      w.scrollIntoView({ behavior: REDUCED ? 'auto' : 'smooth' });
      await sleep(1100);
    }
    toast('🗺️ « C’est ici, non ? »', 'Après 3 détours, Nami l’a ramené au bon endroit.');
    target?.scrollIntoView({ behavior: REDUCED ? 'auto' : 'smooth' });
  }

  // ════════════════════════════════════════════════════════════════════
  //  8. CONFETTIS
  // ════════════════════════════════════════════════════════════════════
  function party(silent = false) {
    if (!silent) unlock('party');
    const cv = document.createElement('canvas'); cv.className = 'egg-canvas'; document.body.appendChild(cv);
    cv.width = innerWidth; cv.height = innerHeight;
    const ctx = cv.getContext('2d');
    const colors = ['#7c3aed', '#ec4899', '#0ea5e9', '#14b8a6', '#f59e0b', '#ef4444', '#22c55e', '#ffd700'];
    const parts = [];
    for (const side of [0, 1]) for (let i = 0; i < (REDUCED ? 40 : 160); i++) parts.push({
      x: side ? cv.width : 0, y: cv.height * 0.85, vx: (side ? -1 : 1) * rand(6, 17), vy: -rand(12, 26),
      w: rand(6, 12), h: rand(8, 16), r: rand(0, 6), vr: rand(-0.3, 0.3), c: colors[i % colors.length], tilt: rand(0, 6),
    });
    [0, 0.1, 0.2, 0.3].forEach((d) => tone(rand(600, 1200), 0.1, 'triangle', 0.05, 300, d));
    const t0 = performance.now();
    (function step(t) {
      ctx.clearRect(0, 0, cv.width, cv.height);
      for (const p of parts) {
        p.vy += 0.45; p.vx *= 0.985; p.vy *= 0.985; p.x += p.vx; p.y += p.vy; p.r += p.vr; p.tilt += 0.1;
        ctx.save(); ctx.translate(p.x, p.y); ctx.rotate(p.r); ctx.scale(1, Math.cos(p.tilt));
        ctx.fillStyle = p.c; ctx.fillRect(-p.w / 2, -p.h / 2, p.w, p.h); ctx.restore();
      }
      if (t - t0 < 4500) requestAnimationFrame(step); else cv.remove();
    })(t0);
    if (!silent) banner('🎉 Let’s gooo !', '#fff');
  }

  // Mobile : 5 taps sur le badge du hero → terminal
  (() => {
    let n = 0, t;
    document.addEventListener('click', (e) => {
      if (!e.target.closest('.hero-badge')) return;
      n++; clearTimeout(t); t = setTimeout(() => (n = 0), 1500);
      if (n === 5) { n = 0; terminal.open(); }
    });
  })();

  // ════════════════════════════════════════════════════════════════════
  //  CONSOLE (pour les curieux qui ouvrent les DevTools)
  // ════════════════════════════════════════════════════════════════════
  const found = countFound(store.get().found);
  console.log('%c<dev/> curieux·se ?', 'font:900 28px monospace;color:#7c3aed;text-shadow:2px 2px 0 #ec4899');
  console.log(`%c${Object.keys(TROPHIES).length} easter eggs sont cachés sur ce site — tu en as trouvé ${found}.\nIndice : les vrais devs parlent en sudo.`, 'font:13px monospace;color:#888');
  window.eggs = Object.freeze({ help: () => 'Tape « sudo » sur la page 😉' });
})();
