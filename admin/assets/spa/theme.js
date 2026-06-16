/* ============================================================
   THEME — the real, editable design-system engine.
   Applies tokens to CSS variables, persists to localStorage,
   and exports/imports a JSON file so Modular can be customised
   for real. window.__mdlr.theme = { state, set, apply, save,
   reset, exportJson, importFile, presets }.
   ============================================================ */
(function () {
  window.__mdlr = window.__mdlr || {};
  const KEY = 'mdlr_theme_v1';
  const R = document.documentElement.style;

  const DEFAULTS = {
    accent:'#C0552F',
    paper:'#F4F1EA', surface:'#FCFBF8', ink:'#221F1A', ink2:'#5A534A', line:'#E4DECF',
    btn:'#2A2520',
    ok:'#4F7A52', warn:'#A9802F', danger:'#A8432F', info:'#5B7089',
    catBasic:'#5B7089', catContent:'#647A53', catChoice:'#876089', catRel:'#B0764A', catMedia:'#3E7C78', catDate:'#B0893F', catLayout:'#6A6258',
    fontDisplay:'Bricolage Grotesque', fontUi:'Hanken Grotesk',
    baseSize:14, radius:8,
  };

  const ACCENT_PRESETS = [
    { id:'terracotta', label:'Terracotta', hex:'#C0552F' },
    { id:'indigo', label:'Indigo', hex:'#4C50C0' },
    { id:'forest', label:'Forest', hex:'#2F6B4F' },
    { id:'ochre', label:'Ochre', hex:'#B07A1E' },
    { id:'plum', label:'Plum', hex:'#7A4A6E' },
    { id:'petrol', label:'Petrol', hex:'#2F6B6B' },
  ];
  const FONT_DISPLAY = ['Bricolage Grotesque','Hanken Grotesk','Fraunces','Playfair Display','Space Grotesk','Libre Franklin','Lora','Georgia','system-ui'];
  const FONT_UI = ['Hanken Grotesk','Bricolage Grotesque','Inter','Source Sans 3','IBM Plex Sans','Libre Franklin','Space Grotesk','Georgia','system-ui'];
  const FALLBACK = ", -apple-system, BlinkMacSystemFont, system-ui, sans-serif";
  // Google-Fonts families loaded on demand (Bricolage/Hanken/JetBrains already in styles.css; Georgia/system-ui are native)
  const GF = {
    'Fraunces':'Fraunces:opsz,wght@9..144,400;9..144,600',
    'Playfair Display':'Playfair+Display:wght@400;500;600;700',
    'Space Grotesk':'Space+Grotesk:wght@400;500;600;700',
    'Libre Franklin':'Libre+Franklin:wght@400;500;600;700',
    'Lora':'Lora:wght@400;500;600;700',
    'Inter':'Inter:wght@400;500;600;700',
    'Source Sans 3':'Source+Sans+3:wght@400;500;600;700',
    'IBM Plex Sans':'IBM+Plex+Sans:wght@400;500;600;700',
  };
  // Fonts are vendored locally (zero external dependencies). The design-system font
  // picker no longer fetches Google Fonts at runtime; non-vendored choices fall back
  // to a system font. Vendor a font under admin/assets/vendor/fonts to make it render.
  function ensureFont(fam) { /* no external font loading */ }

  // ---- colour helpers ----
  const clamp = n => Math.max(0, Math.min(255, n));
  function toRgb(h){ h=(h||'').replace('#',''); if(h.length===3) h=h.split('').map(c=>c+c).join(''); return [parseInt(h.slice(0,2),16),parseInt(h.slice(2,4),16),parseInt(h.slice(4,6),16)]; }
  function toHex(r,g,b){ return '#'+[r,g,b].map(x=>clamp(Math.round(x)).toString(16).padStart(2,'0')).join(''); }
  function darken(h,a){ const [r,g,b]=toRgb(h); return toHex(r*(1-a),g*(1-a),b*(1-a)); }
  function lighten(h,a){ const [r,g,b]=toRgb(h); return toHex(r+(255-r)*a,g+(255-g)*a,b+(255-b)*a); }
  function mix(h1,h2,t){ const a=toRgb(h1),b=toRgb(h2); return toHex(a[0]+(b[0]-a[0])*t,a[1]+(b[1]-a[1])*t,a[2]+(b[2]-a[2])*t); }
  function rgba(h,al){ const [r,g,b]=toRgb(h); return `rgba(${r},${g},${b},${al})`; }

  let stored = {};
  try { stored = JSON.parse(localStorage.getItem(KEY) || '{}') || {}; } catch (e) { stored = {}; }
  const state = (window.Vue ? Vue.reactive : (x=>x))(Object.assign({}, DEFAULTS, stored));

  function apply() {
    const t = state;
    // accent family (derived)
    R.setProperty('--accent', t.accent);
    R.setProperty('--accent-deep', darken(t.accent, 0.18));
    R.setProperty('--accent-soft', mix(t.accent, t.paper, 0.80));
    R.setProperty('--accent-tint', mix(t.accent, t.paper, 0.92));
    R.setProperty('--ring', rgba(t.accent, 0.32));
    // neutrals
    R.setProperty('--paper', t.paper);
    R.setProperty('--paper-2', mix(t.paper, t.ink, 0.05));
    R.setProperty('--paper-3', mix(t.paper, t.ink, 0.09));
    R.setProperty('--surface', t.surface);
    R.setProperty('--surface-2', lighten(t.surface, 0.5));
    R.setProperty('--ink', t.ink);
    R.setProperty('--ink-2', t.ink2);
    R.setProperty('--ink-3', mix(t.ink, t.paper, 0.42));
    R.setProperty('--ink-4', mix(t.ink, t.paper, 0.62));
    R.setProperty('--line', t.line);
    R.setProperty('--line-2', darken(t.line, 0.07));
    R.setProperty('--line-3', darken(t.line, 0.15));
    // action
    R.setProperty('--btn', t.btn);
    R.setProperty('--btn-hover', darken(t.btn, 0.25));
    R.setProperty('--on-btn', lighten(t.btn, 0.92));
    // status (+ soft tints)
    ['ok','warn','danger','info'].forEach(k => { R.setProperty('--' + k, t[k]); R.setProperty('--' + k + '-soft', mix(t[k], t.paper, 0.82)); });
    // field-category hues
    const catMap = { catBasic:'--cat-basic', catContent:'--cat-content', catChoice:'--cat-choice', catRel:'--cat-rel', catMedia:'--cat-media', catDate:'--cat-date', catLayout:'--cat-layout' };
    Object.keys(catMap).forEach(k => { if (t[k]) R.setProperty(catMap[k], t[k]); });
    // type
    ensureFont(t.fontDisplay); ensureFont(t.fontUi);
    R.setProperty('--font-display', "'" + t.fontDisplay + "'" + FALLBACK);
    R.setProperty('--font-ui', "'" + t.fontUi + "'" + FALLBACK);
    if (document.body) document.body.style.fontSize = t.baseSize + 'px';
    // shape
    R.setProperty('--r-md', t.radius + 'px');
    R.setProperty('--r-sm', Math.max(2, t.radius - 2) + 'px');
    R.setProperty('--r-lg', (t.radius + 4) + 'px');
    R.setProperty('--r-xs', Math.max(2, t.radius - 4) + 'px');
  }
  function save() { try { localStorage.setItem(KEY, JSON.stringify(state)); } catch (e) {} }
  function set(key, val) { state[key] = val; apply(); save(); }
  function setMany(obj) { Object.assign(state, obj); apply(); save(); }
  function reset() { Object.assign(state, DEFAULTS); apply(); try { localStorage.removeItem(KEY); } catch (e) {} }
  function exportJson() {
    const blob = new Blob([JSON.stringify(state, null, 2)], { type:'application/json' });
    const a = document.createElement('a');
    a.href = URL.createObjectURL(blob); a.download = 'modular-theme.json';
    document.body.appendChild(a); a.click(); a.remove();
    setTimeout(() => URL.revokeObjectURL(a.href), 1000);
  }
  function importFile(file) {
    return file.text().then(txt => { const obj = JSON.parse(txt); setMany(obj); return true; });
  }
  function isDefault() { return Object.keys(DEFAULTS).every(k => state[k] === DEFAULTS[k]); }

  window.__mdlr.theme = { state, DEFAULTS, ACCENT_PRESETS, FONT_DISPLAY, FONT_UI, apply, save, set, setMany, reset, exportJson, importFile, isDefault };

  // apply immediately on load (before mount) and again after DOM ready for body font-size
  apply();
  document.addEventListener('DOMContentLoaded', apply);
})();
