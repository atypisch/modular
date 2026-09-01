/* ============================================================
   SPA DATA REGISTRY — shaped like the real Modular schema.
   Mirrors: post_types.schema (JSON), component_definitions,
   shared field groups, posts.data / posts.layout.
   Static field/UI registries live here; all CMS entities load from the API via store.js.
   ============================================================ */
(function () {
  window.__mdlr = window.__mdlr || { comps: {} };

  /* ---- Field-type registry (the WHAT layer) ----
     category drives the muted "drawer-label" color. */
  const FIELD_TYPES = [
    // Basic
    { type: 'text',        label: 'Text',         icon: 'type',     cat: 'basic',   catLabel: 'Basic' },
    { type: 'textarea',    label: 'Textarea',     icon: 'align',    cat: 'basic',   catLabel: 'Basic' },
    { type: 'number',      label: 'Number',       icon: 'hash',     cat: 'basic',   catLabel: 'Basic' },
    { type: 'email',       label: 'Email',        icon: 'mail',     cat: 'basic',   catLabel: 'Basic' },
    { type: 'url',         label: 'URL',          icon: 'link',     cat: 'basic',   catLabel: 'Basic' },
    { type: 'color',       label: 'Color',        icon: 'palette',  cat: 'basic',   catLabel: 'Basic' },
    { type: 'password',    label: 'Password',     icon: 'lock',     cat: 'basic',   catLabel: 'Basic' },
    { type: 'range',       label: 'Range',        icon: 'sliders',  cat: 'basic',   catLabel: 'Basic' },
    { type: 'hidden',      label: 'Hidden',       icon: 'eyeOff',   cat: 'basic',   catLabel: 'Basic' },
    // Content
    { type: 'wysiwyg',     label: 'Rich text',    icon: 'text',     cat: 'content', catLabel: 'Content' },
    { type: 'markdown',    label: 'Markdown',     icon: 'file',     cat: 'content', catLabel: 'Content' },
    { type: 'code',        label: 'Code',         icon: 'code',     cat: 'content', catLabel: 'Content' },
    // Choice
    { type: 'select',      label: 'Select',       icon: 'list',     cat: 'choice',  catLabel: 'Choice' },
    { type: 'checkbox',    label: 'Checkbox',     icon: 'check',    cat: 'choice',  catLabel: 'Choice' },
    { type: 'radio',       label: 'Radio',        icon: 'radio',    cat: 'choice',  catLabel: 'Choice' },
    { type: 'button_group',label: 'Button group', icon: 'grid',     cat: 'choice',  catLabel: 'Choice' },
    { type: 'true_false',  label: 'True / False', icon: 'toggle',   cat: 'choice',  catLabel: 'Choice' },
    // Relational
    { type: 'post_object', label: 'Post object',  icon: 'file',     cat: 'rel',     catLabel: 'Relational' },
    { type: 'relationship',label: 'Relationship', icon: 'link',     cat: 'rel',     catLabel: 'Relational' },
    { type: 'taxonomy',    label: 'Taxonomy',     icon: 'pin',      cat: 'rel',     catLabel: 'Relational' },
    { type: 'user',        label: 'User',         icon: 'user',     cat: 'rel',     catLabel: 'Relational' },
    // Media
    { type: 'image',       label: 'Image',        icon: 'image',    cat: 'media',   catLabel: 'Media' },
    { type: 'gallery',     label: 'Gallery',      icon: 'grid',     cat: 'media',   catLabel: 'Media' },
    { type: 'file',        label: 'File',         icon: 'file',     cat: 'media',   catLabel: 'Media' },
    // Date & Time
    { type: 'date',        label: 'Date',         icon: 'calendar', cat: 'date',    catLabel: 'Date & Time' },
    { type: 'datetime',    label: 'Date & time',  icon: 'clock',    cat: 'date',    catLabel: 'Date & Time' },
    { type: 'time',        label: 'Time',         icon: 'clock',    cat: 'date',    catLabel: 'Date & Time' },
    // Layout (HOW — presentation only)
    { type: 'tab',         label: 'Tab',          icon: 'tab',      cat: 'layout',  catLabel: 'Layout', hint: 'Splits the editor into tabs', formsOnly: true },
    { type: 'columns',     label: 'Columns',      icon: 'columns',  cat: 'layout',  catLabel: 'Layout', hint: 'Up to 3 columns, side by side', formsOnly: true },
    { type: 'accordion',   label: 'Accordion',    icon: 'rows',     cat: 'layout',  catLabel: 'Layout', formsOnly: true },
    { type: 'divider',     label: 'Divider',      icon: 'minus',    cat: 'layout',  catLabel: 'Layout' },
    { type: 'message',     label: 'Message',      icon: 'info',     cat: 'layout',  catLabel: 'Layout', formsOnly: true },
    // Components (the new unifying concept)
    { type: 'blocks',      label: 'Blocks',       icon: 'layers',   cat: 'component', catLabel: 'Components', hint: 'Author drops & reorders components' },
    { type: 'repeater',    label: 'Repeater',     icon: 'repeat',   cat: 'component', catLabel: 'Components' },
    { type: 'group',       label: 'Group',        icon: 'box',      cat: 'component', catLabel: 'Components' },
  ];
  const TYPE_MAP = {}; FIELD_TYPES.forEach(t => TYPE_MAP[t.type] = t);
  const CAT_ORDER = [
    { cat: 'basic',     label: 'Basic' },
    { cat: 'content',   label: 'Content' },
    { cat: 'choice',    label: 'Choice' },
    { cat: 'rel',       label: 'Relational' },
    { cat: 'media',     label: 'Media' },
    { cat: 'date',      label: 'Date & Time' },
    { cat: 'layout',    label: 'Layout' },
    { cat: 'component', label: 'Components' },
  ];
  const CAT_VAR = {
    basic: 'var(--cat-basic)', content: 'var(--cat-content)', choice: 'var(--cat-choice)',
    rel: 'var(--cat-rel)', media: 'var(--cat-media)', date: 'var(--cat-date)',
    layout: 'var(--cat-layout)', component: 'var(--accent)',
  };

  let _id = 100;
  const uid = (p) => (p || 'f') + '_' + (++_id);

  const f = (label, name, type, extra) => Object.assign({
    id: uid(), label, name, key: name, type,
    required: false, instructions: '', sharedTo: [], mode: 'fixed',
  }, extra || {});

  /* API-backed collections — populated by store.hydrate(), never seeded with demo rows. */
  const FIELD_GROUPS = Vue.reactive([]);
  const COMPONENTS = Vue.reactive([]);
  const POST_TYPES = Vue.reactive([]);
  const PROJECT_SCHEMA = [];
  const RULES = [];
  const SAMPLE_POST = { id: null, title: '', slug: '', type: '', status: 'draft' };
  const POSTS = Vue.reactive([]);
  const TAXONOMIES = Vue.reactive([]);
  const MEDIA = Vue.reactive([]);
  const MEDIA_FOLDERS = Vue.reactive([]);
  const RENAME_PATTERN = { enabled: true, pattern: '{folder}-{title}-{n}' };
  const NOTIFICATIONS = Vue.reactive([]);
  const IMAGE_PRESETS_DEFAULT = [
    { name:'thumbnail', w:400,  h:400,  ratio:'1:1',    crop:'cover', on:true, hue:32 },
    { name:'card',      w:720,  h:540,  ratio:'4:3',    crop:'cover', on:true, hue:28 },
    { name:'header',    w:1440, h:750,  ratio:'≈2:1',   crop:'cover', on:true, hue:24 },
    { name:'social-og', w:1200, h:630,  ratio:'1.91:1', crop:'cover', on:true, hue:36 },
  ];
  const IMAGE_PRESETS = Vue.reactive(JSON.parse(JSON.stringify(IMAGE_PRESETS_DEFAULT)));
  const FORMS = Vue.reactive([]);
  const SUBMISSIONS = Vue.reactive([]);
  const ACTIONS = Vue.reactive([]);
  const EXPORT_TABLES = Vue.reactive([]);
  const IMPORT_HISTORY = Vue.reactive([]);
  const PROFILE = Vue.reactive({
    username: '', name: '', email: '', role: '', status: '', memberSince: '',
    avatar: '', initials: '', lastActive: '', twoFactor: false, language: 'English',
    bio: '', timezone: 'Europe/Amsterdam',
  });
  const MODULES = Vue.reactive([]);
  const MODULES_AVAILABLE = Vue.reactive([]);
  const ROLES = Vue.reactive([]);
  const SYSTEM = Vue.reactive({
    version: '', latest: '', channel: 'stable', php: '', db: '', lastChecked: '',
    changelog: [], update_available: false,
  });
  const REPORT_ROWS = Vue.reactive([]);
  const THEMES = Vue.reactive([]);

  /* ---- Location-rule registry (ACF-style “where does this group show”) ---- */
  const LOCATION_PARAMS = [
    { id:'post_type',     label:'Post type',     valuesFrom:'post_types' },
    { id:'post',          label:'Post',          valuesFrom:'posts' },
    { id:'post_template', label:'Post template', values:[{id:'default',name:'Default'},{id:'full',name:'Full width'},{id:'landing',name:'Landing'}] },
    { id:'taxonomy',      label:'Taxonomy term',  valuesFrom:'terms' },
    { id:'user_role',     label:'User role',     values:[{id:'administrator',name:'Administrator'},{id:'editor',name:'Editor'},{id:'author',name:'Author'}] },
  ];
  const LOCATION_OPS = [ { id:'==', label:'is equal to' }, { id:'!=', label:'is not equal to' } ];

  const ICON_CHOICES = [
    'file','files','box','grid','zap','image','layers','database','form','star',
    'globe','calendar','clock','pin','users','user','folder','bars','sigma','table',
    'play','code','mail','list','component','dashboard','palette','webhook','repeat','branch',
  ];

  /* ---- Roles & capabilities (what a role can see / do) ---- */
  // Capability catalog — ids are the exact capability names enforced by the
  // backend (capabilities table). Grouped with friendly labels for the matrix.
  const CAP_GROUPS = [
    { group:'Content', icon:'files', caps:[
      { id:'post:create', label:'Create content', desc:'Add new items.' },
      { id:'post:edit:own', label:'Edit own', desc:'Edit items they authored.' },
      { id:'post:edit:any', label:'Edit others’', desc:'Edit anyone’s items.' },
      { id:'post:delete:own', label:'Delete own', desc:'Trash items they authored.' },
      { id:'post:delete:any', label:'Delete others’', desc:'Trash and permanently delete any item.' },
      { id:'post:publish', label:'Publish', desc:'Move items from draft to published.' },
    ]},
    { group:'Media', icon:'image', caps:[
      { id:'media:upload', label:'Upload', desc:'Add files to the library.' },
      { id:'media:edit', label:'Edit & crop', desc:'Rename, crop and re-derive presets.' },
      { id:'media:delete', label:'Delete', desc:'Remove files.' },
    ]},
    { group:'Users', icon:'users', caps:[
      { id:'user:create', label:'Create users', desc:'Add new accounts.' },
      { id:'user:edit', label:'Edit users', desc:'Update account details.' },
      { id:'user:delete', label:'Delete users', desc:'Remove accounts.' },
      { id:'user:promote', label:'Assign roles', desc:'Change a user’s role.' },
    ]},
    { group:'System', icon:'settings', caps:[
      { id:'plugin:activate', label:'Activate modules', desc:'Turn modules on.' },
      { id:'plugin:deactivate', label:'Deactivate modules', desc:'Turn modules off.' },
      { id:'settings:edit', label:'Edit settings', desc:'Change site configuration.' },
      { id:'theme:edit', label:'Edit theme', desc:'Change the design system tokens.' },
    ]},
  ];
  const ALL_CAPS = CAP_GROUPS.flatMap(g => g.caps.map(c => c.id));

  /* ---- Reports (BI) — types must match StatsController report endpoints ---- */
  const REPORTS = [
    { id:'production', name:'Content production', icon:'files', desc:'Items created & published over time, per author and type.' },
    { id:'cadence', name:'Publishing cadence', icon:'calendar', desc:'How regularly each type ships; gaps and streaks.' },
    { id:'forms', name:'Form submissions', icon:'form', desc:'Submission counts and spam stats per form.' },
    { id:'authors', name:'Author leaderboard', icon:'users', desc:'Output per team member in the selected period.' },
    { id:'health', name:'Content health', icon:'alert', desc:'Stale items, missing excerpts, old drafts, unused media.' },
  ];
  /* Populated from settings/API when wired; starts empty. */
  const SPA_META = Vue.reactive({ ready: false, postTypesVersion: 0 });

  window.__mdlr.data = {
    FIELD_TYPES, TYPE_MAP, CAT_ORDER, CAT_VAR,
    FIELD_GROUPS, COMPONENTS, POST_TYPES, PROJECT_SCHEMA, RULES,
    SAMPLE_POST, POSTS, TAXONOMIES, MEDIA, IMAGE_PRESETS, IMAGE_PRESETS_DEFAULT, FORMS, SUBMISSIONS, ACTIONS, uid,
    EXPORT_TABLES, IMPORT_HISTORY, PROFILE, ICON_CHOICES,
    LOCATION_PARAMS, LOCATION_OPS, MODULES, MODULES_AVAILABLE,
    CAP_GROUPS, ALL_CAPS, ROLES, SYSTEM,
    STATS_OVERVIEW: null, LIVE_ANALYTICS: null, LIVE_REPORTS: null, REPORTS, REPORT_ROWS, THEMES,
    MEDIA_FOLDERS, RENAME_PATTERN, NOTIFICATIONS, SPA_META,
  };
})();
