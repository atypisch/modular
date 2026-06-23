/* ============================================================
   MOCK DATA — shaped like the real Modular schema.
   Mirrors: post_types.schema (JSON), component_definitions,
   shared field groups, posts.data / posts.layout.
   The PHP/MySQL backend is mocked here in JS with the same shapes.
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

  /* ---- Shared field groups (the WHERE layer — reusable, bindable) ----
     Reactive so the list can reorder / duplicate / trash live. */
  const FIELD_GROUPS = Vue.reactive([
    {
      id: 'fg_seo', name: 'SEO', icon: 'globe', shared: true, active: true,
      order: 1, position: 'normal', style: 'default', labelPlacement: 'top',
      desc: 'Search & social metadata. Shared across content types.',
      boundTo: ['page', 'post', 'product', 'project'],
      location: [
        [{ param:'post_type', op:'==', value:'page' }],
        [{ param:'post_type', op:'==', value:'post' }],
        [{ param:'post_type', op:'==', value:'product' }],
        [{ param:'post_type', op:'==', value:'project' }],
      ],
      fields: [
        f('Meta title', 'seo_title', 'text'),
        f('Meta description', 'seo_description', 'textarea'),
        f('Social image', 'og_image', 'image'),
        f('No-index', 'noindex', 'true_false'),
      ],
    },
    {
      id: 'fg_audit', name: 'Publication', icon: 'clock', shared: true, active: true,
      order: 2, position: 'side', style: 'default', labelPlacement: 'top',
      desc: 'Scheduling & visibility used by every post type.',
      boundTo: ['page', 'post', 'product', 'product_release', 'project'],
      location: [ [{ param:'post_type', op:'!=', value:'' }] ],
      fields: [
        f('Publish date', 'publish_at', 'datetime'),
        f('Visibility', 'visibility', 'select'),
      ],
    },
    {
      id: 'fg_project', name: 'Project details', icon: 'grid', shared: false, active: true,
      order: 3, position: 'normal', style: 'default', labelPlacement: 'top',
      desc: 'Facts shown on a project: client, year, the brief.',
      boundTo: ['project'],
      location: [ [{ param:'post_type', op:'==', value:'project' }] ],
      fields: [
        f('Client', 'client', 'text'),
        f('Year', 'year', 'text'),
        f('Project URL', 'project_url', 'url'),
        f('Related projects', 'related', 'relationship', { mode:'modular' }),
      ],
    },
    {
      id: 'fg_product', name: 'Product specs', icon: 'box', shared: false, active: false,
      order: 4, position: 'high', style: 'seamless', labelPlacement: 'left',
      desc: 'Pricing & SKU. Only on products.',
      boundTo: ['product'],
      location: [ [{ param:'post_type', op:'==', value:'product' }] ],
      fields: [
        f('Price', 'price', 'number'),
        f('SKU', 'sku', 'text'),
        f('In stock', 'in_stock', 'true_false'),
      ],
    },
  ]);

  /* ---- Location-rule registry (ACF-style “where does this group show”) ---- */
  const LOCATION_PARAMS = [
    { id:'post_type',     label:'Post type',     valuesFrom:'post_types' },
    { id:'post',          label:'Post',          valuesFrom:'posts' },
    { id:'post_template', label:'Post template', values:[{id:'default',name:'Default'},{id:'full',name:'Full width'},{id:'landing',name:'Landing'}] },
    { id:'taxonomy',      label:'Taxonomy term',  valuesFrom:'terms' },
    { id:'user_role',     label:'User role',     values:[{id:'administrator',name:'Administrator'},{id:'editor',name:'Editor'},{id:'author',name:'Author'}] },
  ];
  const LOCATION_OPS = [ { id:'==', label:'is equal to' }, { id:'!=', label:'is not equal to' } ];

  /* ---- Components (blocks) — versioned, reusable, droppable ---- */
  const COMPONENTS = [
    { id: 'cmp_hero', name: 'Hero', icon: 'star', version: 4, usedIn: 12, updated: '3d ago',
      desc: 'Full-width intro with eyebrow, title, lead and two CTAs.',
      fields: [ f('Eyebrow','eyebrow','text'), f('Title','title','text',{required:true}), f('Lead','lead','textarea'),
        f('Primary CTA label','cta_label','text'), f('Primary CTA url','cta_url','url'), f('Background','bg','image') ] },
    { id: 'cmp_richtext', name: 'Rich text', icon: 'text', version: 2, usedIn: 41, updated: '1w ago',
      desc: 'A single formatted prose block.',
      fields: [ f('Body','body','wysiwyg',{required:true}) ] },
    { id: 'cmp_imgtext', name: 'Image + text', icon: 'columns', version: 3, usedIn: 22, updated: '2d ago',
      desc: 'Split layout: media on one side, prose on the other.',
      fields: [ f('Image','image','image',{required:true}), f('Heading','heading','text'),
        f('Body','body','wysiwyg'), f('Media side','side','button_group') ] },
    { id: 'cmp_features', name: 'Feature grid', icon: 'grid', version: 5, usedIn: 9, updated: '5d ago',
      desc: 'A repeatable grid of icon + title + text.',
      fields: [ f('Heading','heading','text'), f('Features','items','repeater') ] },
    { id: 'cmp_quote', name: 'Pull quote', icon: 'sigma', version: 1, usedIn: 14, updated: '2w ago',
      desc: 'An emphasised quotation with attribution.',
      fields: [ f('Quote','quote','textarea',{required:true}), f('Author','author','text'), f('Role','role','text') ] },
    { id: 'cmp_cta', name: 'CTA banner', icon: 'zap', version: 2, usedIn: 7, updated: '4d ago',
      desc: 'A boxed call to action with heading and button.',
      fields: [ f('Heading','heading','text',{required:true}), f('Button label','btn','text'), f('Button url','url','url') ] },
    { id: 'cmp_gallery', name: 'Gallery', icon: 'image', version: 3, usedIn: 18, updated: '6d ago',
      desc: 'A set of images shown as a grid or carousel.',
      fields: [ f('Images','images','gallery',{required:true}), f('Layout','layout','select') ] },
    { id: 'cmp_logos', name: 'Logo strip', icon: 'bars', version: 1, usedIn: 5, updated: '3w ago',
      desc: 'A row of partner / client logos.',
      fields: [ f('Logos','logos','gallery'), f('Caption','caption','text') ] },
    { id: 'cmp_stats', name: 'Stats', icon: 'sigma', version: 2, usedIn: 6, updated: '1w ago',
      desc: 'A row of numeric facts with labels.',
      fields: [ f('Stats','stats','repeater',{required:true}) ] },
    { id: 'cmp_embed', name: 'Embed', icon: 'code', version: 1, usedIn: 3, updated: '1mo ago',
      desc: 'Raw embed code (video, map, iframe).',
      fields: [ f('Embed code','embed','code',{required:true}) ] },
  ];

  /* ---- Post types (with mode) ----
     Reactive so creating a new type updates the sidebar menu live. */
  const POST_TYPES = Vue.reactive([
    { id: 'page',    name: 'Page',    slug: 'page',    icon: 'file',  desc: 'Static page', mode: 'modular', hierarchical: true,  public: true, inMenu: true, count: 84, fieldCount: 9, taxonomies: [] },
    { id: 'post',    name: 'Post',    slug: 'post',    icon: 'files', desc: 'Blog post', mode: 'fixed', hierarchical: false, public: true, inMenu: true, count: 161, fieldCount: 6, taxonomies: ['category','tag'] },
    { id: 'product', name: 'Products',slug: 'product', icon: 'box',   desc: 'Digital infrastructure products', mode: 'modular', hierarchical: false, public: true, inMenu: true, count: 12, fieldCount: 11, taxonomies: ['product_cat'] },
    { id: 'product_release', name: 'Product release', slug: 'product_release', icon: 'zap', desc: 'Product updates and releases', mode: 'fixed', hierarchical: false, public: true, inMenu: true, count: 28, fieldCount: 7, taxonomies: [] },
    { id: 'project', name: 'Projects',slug: 'project', icon: 'grid',  desc: 'Atypisch projecten', mode: 'modular', hierarchical: false, public: true, inMenu: true, count: 19, fieldCount: 11, taxonomies: ['project_type'] },
  ]);

  /* ---- Project post type schema (the canvas in Field Builder) ----
     A "tab" item splits sections; "columns" is presentation. */
  const PROJECT_SCHEMA = [
    { ...f('Content','tab_content','tab'), _section: true },
    f('Tagline','tagline','text',{ instructions:'One line shown under the title.' }),
    f('Body','body','wysiwyg',{ required:true }),
    f('Blocks','blocks','blocks',{ mode:'modular', instructions:'Drop & reorder components to build the page.', allowed:['cmp_hero','cmp_richtext','cmp_imgtext','cmp_features','cmp_quote','cmp_gallery'] }),
    { ...f('Media','tab_media','tab'), _section: true },
    f('Cover image','cover','image',{ required:true }),
    f('Project gallery','gallery','gallery',{ mode:'modular' }),
    f('Cover gradient','gradient_class','select'),
    { ...f('Details','tab_details','tab'), _section: true },
    f('Client','client','text'),
    f('Year','year','text'),
    f('Launch date','launch_date','date'),
    f('Topics','topics','taxonomy',{ mode:'modular', instructions:'Pick one or more terms.' }),
    f('Project URL','project_url','url'),
    f('Project details','meta_rows','repeater',{ instructions:'Label / value rows.' }),
    f('Featured on homepage','featured','true_false',{ logicSource: true }),
    f('Press quote','press_quote','textarea',{ mode:'modular', _conditional: 'featured' }),
  ];

  /* ---- Conditional rules (readable rule layer) ---- */
  const RULES = [
    { id: 'r1', target: { kind:'field', name:'press_quote', label:'Press quote' }, action:'show',
      logic:'all', conds: [ { field:'featured', label:'Featured on homepage', op:'is', value:'true' } ] },
    { id: 'r2', target: { kind:'tab', name:'tab_media', label:'Media' }, action:'show',
      logic:'any', conds: [ { field:'cover', label:'Cover image', op:'is set', value:'' } ] },
  ];

  /* ---- Resolved fieldset for one post (Content editor) ----
     Each entry carries provenance. */
  const SAMPLE_POST = {
    id: 'p_990', title: 'Digital Twins Helpdesk', slug: 'digital-twins-helpdesk',
    type: 'project', status: 'published', author: 'marten@atypisch.nl', updated: 'May 21, 2026',
    tabs: [
      { id:'tab_content', label:'Content', icon:'text' },
      { id:'tab_media',   label:'Media',   icon:'image' },
      { id:'tab_details', label:'Details', icon:'list' },
      { id:'tab_seo',     label:'SEO',     icon:'globe' },
    ],
  };

  /* ---- Posts list ---- */
  const POSTS = [
    { id:1, title:'Toolbx-eu / Outline', type:'project', status:'published', author:'marten', date:'May 21, 2026' },
    { id:2, title:'Digital Twins Helpdesk', type:'project', status:'published', author:'marten', date:'May 21, 2026' },
    { id:3, title:'Kunsthal archive', type:'project', status:'draft', author:'marten', date:'May 21, 2026' },
    { id:4, title:'Living-in.EU', type:'project', status:'published', author:'marten', date:'May 18, 2026' },
    { id:21, title:'Rozuro brand system', type:'project', status:'published', author:'lieke', date:'May 09, 2026' },
    { id:22, title:'Maximus Brouwerij', type:'project', status:'published', author:'marten', date:'Mar 23, 2026' },
    { id:5, title:'MydayPrice', type:'product', status:'published', author:'marten', date:'May 12, 2026' },
    { id:23, title:'Simezu Hosting', type:'product', status:'published', author:'marten', date:'Apr 30, 2026' },
    { id:24, title:'Toolbx API', type:'product', status:'draft', author:'sam', date:'Apr 18, 2026' },
    { id:6, title:'Why I built my own CMS in 2025', type:'post', status:'published', author:'marten', date:'Apr 12, 2026' },
    { id:7, title:'EU-hosted by default: what it actually costs', type:'post', status:'published', author:'marten', date:'Mar 28, 2026' },
    { id:9, title:'Rozuro is launching in public beta', type:'post', status:'draft', author:'marten', date:'Feb 22, 2026' },
    { id:25, title:'Headless without the headache', type:'post', status:'scheduled', author:'lieke', date:'Jun 04, 2026' },
    { id:8, title:'Simezu 1.1 — Mollie integration shipped', type:'product_release', status:'scheduled', author:'marten', date:'Mar 14, 2026' },
    { id:26, title:'Modular 1.2 — the field builder rewrite', type:'product_release', status:'draft', author:'marten', date:'Jun 01, 2026' },
    { id:10, title:'About Atypisch', type:'page', status:'published', author:'marten', date:'May 12, 2026' },
    { id:11, title:'Services', type:'page', status:'published', author:'marten', date:'May 07, 2026' },
    { id:12, title:'Privacy policy', type:'page', status:'published', author:'marten', date:'May 07, 2026' },
    { id:13, title:'Contact', type:'page', status:'published', author:'lieke', date:'Apr 21, 2026' },
  ];

  /* ---- Taxonomies & terms ---- */
  const TAXONOMIES = [
    { id:'category', name:'Category', slug:'category', hierarchical:true, postTypes:['post'], terms:[
      { id:'t1', name:'Engineering', slug:'engineering', count:24, parent:null },
      { id:'t2', name:'Backend', slug:'backend', count:9, parent:'t1' },
      { id:'t3', name:'Product', slug:'product', count:31, parent:null },
      { id:'t4', name:'Company', slug:'company', count:12, parent:null },
    ]},
    { id:'tag', name:'Tag', slug:'tag', hierarchical:false, postTypes:['post'], terms:[
      { id:'t5', name:'php', slug:'php', count:18, parent:null },
      { id:'t6', name:'vue', slug:'vue', count:22, parent:null },
      { id:'t7', name:'headless', slug:'headless', count:7, parent:null },
      { id:'t8', name:'hosting', slug:'hosting', count:5, parent:null },
    ]},
    { id:'project_type', name:'Project type', slug:'project_type', hierarchical:true, postTypes:['project'], terms:[
      { id:'t9', name:'Web app', slug:'web-app', count:11, parent:null },
      { id:'t10', name:'Brand & site', slug:'brand-site', count:6, parent:null },
      { id:'t11', name:'Platform', slug:'platform', count:4, parent:null },
    ]},
    { id:'product_cat', name:'Product category', slug:'product_cat', hierarchical:true, postTypes:['product'], terms:[
      { id:'t12', name:'Infrastructure', slug:'infra', count:5, parent:null },
      { id:'t13', name:'Tools', slug:'tools', count:7, parent:null },
    ]},
  ];

  /* ---- Media ---- */
  const MEDIA = Vue.reactive([
    { id:'m1', name:'studio-light.jpg', title:'Studio light', w:2400, h:1600, kb:842, alt:'Light across a studio floor', caption:'', folder:'projects', hue:34, uploaded:'May 21, 2026', usedIn:3, seoTitle:'', seoDesc:'' },
    { id:'m2', name:'concrete-wall.jpg', title:'Concrete wall', w:2000, h:2500, kb:1180, alt:'Concrete wall detail', caption:'', folder:'textures', hue:28, uploaded:'May 19, 2026', usedIn:1, seoTitle:'', seoDesc:'' },
    { id:'m3', name:'desk-overhead.jpg', title:'Desk overhead', w:3000, h:2000, kb:1540, alt:'Desk from above', caption:'', folder:'projects', hue:36, uploaded:'May 18, 2026', usedIn:2, seoTitle:'', seoDesc:'' },
    { id:'m4', name:'window-line.jpg', title:'Window line', w:1800, h:1800, kb:690, alt:'Window frame line', caption:'', folder:'projects', hue:30, uploaded:'May 12, 2026', usedIn:0, seoTitle:'', seoDesc:'' },
    { id:'m5', name:'product-main.png', title:'Product hero', w:1440, h:900, kb:312, alt:'Product hero', caption:'', folder:'products', hue:18, uploaded:'May 10, 2026', usedIn:5, seoTitle:'', seoDesc:'' },
    { id:'m6', name:'team-portrait.jpg', title:'Team portrait', w:1600, h:1200, kb:540, alt:'Team at work', caption:'', folder:'about', hue:25, uploaded:'May 04, 2026', usedIn:1, seoTitle:'', seoDesc:'' },
    { id:'m7', name:'map-tiles.jpg', title:'Map tiles', w:2200, h:1400, kb:980, alt:'Map tiles', caption:'', folder:'projects', hue:200, uploaded:'Apr 28, 2026', usedIn:0, seoTitle:'', seoDesc:'' },
    { id:'m8', name:'paper-grain.jpg', title:'Paper grain', w:1500, h:1500, kb:420, alt:'Paper grain', caption:'', folder:'textures', hue:38, uploaded:'Apr 22, 2026', usedIn:4, seoTitle:'', seoDesc:'' },
    { id:'m9', name:'glass-facade.jpg', title:'Glass facade', w:2600, h:1700, kb:1320, alt:'Glass facade', caption:'', folder:'projects', hue:210, uploaded:'Apr 20, 2026', usedIn:2, seoTitle:'', seoDesc:'' },
    { id:'m10', name:'keyboard-detail.jpg', title:'Keyboard detail', w:1900, h:1268, kb:760, alt:'Keyboard detail', caption:'', folder:'products', hue:20, uploaded:'Apr 18, 2026', usedIn:1, seoTitle:'', seoDesc:'' },
    { id:'m11', name:'staircase.jpg', title:'Staircase', w:1700, h:2200, kb:1040, alt:'Staircase', caption:'', folder:'architecture', hue:32, uploaded:'Apr 11, 2026', usedIn:0, seoTitle:'', seoDesc:'' },
    { id:'m12', name:'plant-shadow.jpg', title:'Plant shadow', w:2000, h:1500, kb:880, alt:'Plant shadow on wall', caption:'', folder:'about', hue:90, uploaded:'Apr 02, 2026', usedIn:1, seoTitle:'', seoDesc:'' },
  ]);
  const MEDIA_FOLDERS = Vue.reactive(['projects','textures','products','about','architecture']);
  const RENAME_PATTERN = { enabled:true, pattern:'{folder}-{title}-{n}' };

  /* ---- Notifications ---- */
  const NOTIFICATIONS = Vue.reactive([
    { id:'n1', icon:'refresh', kind:'update', title:'Modular 1.3.1 is available', body:'A new core release is ready to install.', when:'10 min ago', read:false, go:['dashboard',{tab:'updates'}] },
    { id:'n2', icon:'form', kind:'form', title:'New form submission', body:'Contact form — from j.devries@kpn.nl.', when:'1 hour ago', read:false, go:['forms',{}] },
    { id:'n3', icon:'users', kind:'user', title:'Lieke joined as Editor', body:'Invitation accepted.', when:'3 hours ago', read:false, go:['users',{}] },
    { id:'n4', icon:'zap', kind:'action', title:'“Notify on publish” ran', body:'Triggered by Digital Twins Helpdesk.', when:'Yesterday', read:true, go:['actions',{}] },
    { id:'n5', icon:'database', kind:'backup', title:'Nightly backup complete', body:'1,284 rows across 19 tables.', when:'Yesterday', read:true, go:['exportImport',{tab:'history'}] },
  ]);

  /* ---- Image presets (drive the auto-cropper). Reactive + restorable. ---- */
  const IMAGE_PRESETS_DEFAULT = [
    { name:'thumbnail', w:400,  h:400,  ratio:'1:1',    crop:'cover', on:true, hue:32 },
    { name:'card',      w:720,  h:540,  ratio:'4:3',    crop:'cover', on:true, hue:28 },
    { name:'header',    w:1440, h:750,  ratio:'≈2:1',   crop:'cover', on:true, hue:24 },
    { name:'social-og', w:1200, h:630,  ratio:'1.91:1', crop:'cover', on:true, hue:36 },
  ];
  const IMAGE_PRESETS = Vue.reactive(JSON.parse(JSON.stringify(IMAGE_PRESETS_DEFAULT)));

  /* ---- Forms ---- */
  const FORMS = [
    { id:'frm_login', name:'Sign in', slug:'login', fields:3, submissions:0, updated:'Jun 03, 2026', system:true, preview:'auth.html', desc:'Account sign-in screen.' },
    { id:'frm_register', name:'Register', slug:'register', fields:4, submissions:0, updated:'Jun 03, 2026', system:true, preview:'auth.html', desc:'New-account sign-up screen.' },
    { id:'frm_forgot', name:'Forgot password', slug:'forgot-password', fields:1, submissions:0, updated:'Jun 03, 2026', system:true, preview:'auth.html', desc:'Password reset request.' },
    { id:'frm1', name:'Contact', slug:'contactform', fields:5, submissions:128, updated:'Apr 21, 2026' },
    { id:'frm2', name:'Newsletter', slug:'newsletter', fields:2, submissions:642, updated:'Apr 21, 2026' },
    { id:'frm3', name:'Project intake', slug:'intake', fields:9, submissions:34, updated:'May 02, 2026' },
  ];

  /* ---- Form submissions (for the Contact form) ---- */
  const SUBMISSIONS = Vue.reactive([
    { id:1042, name:'Lieke de Vries', email:'lieke@studio-mara.nl', subject:'New project', message:'We are opening a new studio in Utrecht and would love a quote for a platform.', date:'2026-06-03 14:21', read:false },
    { id:1041, name:'Tomás赫', email:'tomas@herrera.io', subject:'Partnership', message:'Interested in integrating Modular with our booking flow. Who do I speak to?', date:'2026-06-03 09:05', read:false },
    { id:1040, name:'Noor Bakker', email:'noor@bakker-yoga.nl', subject:'Support', message:'How do I export my submissions to a spreadsheet?', date:'2026-06-02 18:40', read:false },
    { id:1039, name:'Sam Okafor', email:'sam@okaforhq.com', subject:'New project', message:'Budget is flexible. Timeline is six weeks. Can we talk this week?', date:'2026-06-02 11:12', read:true },
    { id:1038, name:'Marit Jansen', email:'marit@jansen.co', subject:'Feedback', message:'Love the calm aesthetic. One request: dark mode on the public site.', date:'2026-06-01 16:55', read:true },
    { id:1037, name:'Daan Visser', email:'daan@visserlab.nl', subject:'Support', message:'The contact form confirmation email never arrived. Is that configurable?', date:'2026-06-01 08:30', read:true },
    { id:1036, name:'Èmilie Laurent', email:'emilie@laurent.fr', subject:'Partnership', message:'Bonjour — we run 12 wellness spaces across Lyon. Let us collaborate.', date:'2026-05-31 13:02', read:true },
    { id:1035, name:'Joost Pol', email:'joost@polmedia.nl', subject:'New project', message:'Need a headless site with a Vue front-end. Modular looks perfect.', date:'2026-05-30 19:48', read:true },
  ]);

  /* ---- Actions (Directus-style flow) ---- */
  const ACTIONS = [
    { id:'a1', name:'Notify on publish', trigger:'Post published', enabled:true, runs:412,
      ops:[ {type:'condition', icon:'branch', label:'Only if type is Project'}, {type:'email', icon:'mail', label:'Email the team'}, {type:'webhook', icon:'webhook', label:'Ping deploy hook'} ] },
    { id:'a2', name:'Welcome new user', trigger:'User registered', enabled:true, runs:88,
      ops:[ {type:'email', icon:'mail', label:'Send welcome email'}, {type:'tag', icon:'pin', label:'Add tag “new”'} ] },
    { id:'a3', name:'Form to CRM', trigger:'Form submitted', enabled:false, runs:0,
      ops:[ {type:'condition', icon:'branch', label:'If form is Intake'}, {type:'webhook', icon:'webhook', label:'POST to CRM'}, {type:'create', icon:'plus', label:'Create Lead post'} ] },
  ];

  /* ---- Export / Import ---- */
  const EXPORT_TABLES = [
    { name:'roles',                table:'roles',                source:'core',  key:'name', rows:6 },
    { name:'capabilities',         table:'capabilities',         source:'core',  key:'name', rows:38 },
    { name:'users',                table:'users',                source:'core',  key:'email', rows:4 },
    { name:'role_capabilities',    table:'role_capabilities',    source:'core',  key:'role_id, capability_id', rows:82 },
    { name:'user_roles',           table:'user_roles',           source:'core',  key:'user_id, role_id', rows:4 },
    { name:'post_types',           table:'post_types',           source:'core',  key:'slug', rows:5 },
    { name:'taxonomies',           table:'taxonomies',           source:'core',  key:'slug', rows:4 },
    { name:'terms',                table:'terms',                source:'core',  key:'taxonomy_id, slug', rows:13 },
    { name:'post_type_taxonomies', table:'post_type_taxonomies', source:'core',  key:'post_type_id, taxonomy_id', rows:4 },
    { name:'fields',               table:'fields',               source:'core',  key:'post_type_id, slug', rows:48 },
    { name:'field_groups',         table:'field_groups',         source:'core',  key:'name', rows:2 },
    { name:'components',           table:'components',            source:'core',  key:'slug', rows:10 },
    { name:'posts',                table:'posts',                source:'core',  key:'type, slug', rows:304 },
    { name:'post_meta',            table:'post_meta',            source:'core',  key:'post_id, meta_key', rows:1842 },
    { name:'media',                table:'media',                source:'media', key:'filename', rows:236 },
    { name:'forms',                table:'forms',                source:'forms', key:'slug', rows:3 },
    { name:'form_submissions',     table:'form_submissions',     source:'forms', key:'id', rows:804 },
    { name:'actions',              table:'actions',              source:'actions',key:'name', rows:3 },
    { name:'action_runs',          table:'action_runs',          source:'actions',key:'id', rows:500 },
  ];
  const IMPORT_HISTORY = [
    { id:1, when:'2026-06-01 13:41:09', bundle:'modular-import-21e769310278.zip', source:'http://localhost/simuze', mode:'overwrite', by:'marten', rows:1284 },
    { id:2, when:'2026-05-19 09:12:44', bundle:'rozuro-content-9f02.zip', source:'https://rozuro.com', mode:'skip', by:'marten', rows:96 },
    { id:3, when:'2026-04-30 16:58:02', bundle:'modular-seed-base.zip', source:'bundled seed', mode:'skip', by:'marten', rows:412 },
  ];

  /* ---- Current user / profile ---- */
  const PROFILE = {
    username:'marten', name:'Marten Timan', email:'marten@atypisch.nl',
    role:'Administrator', status:'Active', memberSince:'March 1, 2026',
    avatar:'MT', initials:'MT', lastActive:'2 min ago', twoFactor:true, language:'English',
    bio:'Builder of Modular. Runs Atypisch — a small studio shipping EU-hosted web platforms.',
    timezone:'Europe/Amsterdam',
  };

  /* ---- Icon choices for the post-type / menu picker ---- */
  const ICON_CHOICES = [
    'file','files','box','grid','zap','image','layers','database','form','star',
    'globe','calendar','clock','pin','users','user','folder','bars','sigma','table',
    'play','code','mail','list','component','dashboard','palette','webhook','repeat','branch',
  ];

  /* ---- Modules (WordPress-plugin style, but compact) ---- */
  const MODULES = Vue.reactive([
    { id:'i18n', name:'Atypisch i18n', author:'Atypisch', version:'1.0.0', icon:'globe', active:true, autoUpdate:true, desc:'Lightweight multilingual routing and translation linking.' },
    { id:'mollie', name:'Mollie Payments', author:'Mollie', version:'2.3.1', icon:'zap', active:true, autoUpdate:true, desc:'Take payments with iDEAL, cards and more via Mollie.' },
    { id:'backups', name:'Scheduled Backups', author:'Modular', version:'1.4.0', icon:'database', active:true, autoUpdate:true, desc:'Nightly database + media backups to off-site storage.' },
    { id:'sitemaps', name:'SEO Sitemaps', author:'Modular', version:'1.0.5', icon:'globe', active:true, autoUpdate:false, desc:'Automatic XML sitemaps for every public post type.' },
    { id:'community', name:'Simuze Community', author:'Atypisch · Simuze', version:'1.0.0', icon:'users', active:false, autoUpdate:false, desc:'Users, groups, activity, messages and reviews; optional commerce.' },
    { id:'redirects', name:'Redirects', author:'Modular', version:'1.1.2', icon:'refresh', active:false, autoUpdate:false, desc:'301 / 302 manager with a 404 log and bulk import.' },
    { id:'example', name:'Example Module', author:'Modular CMS', version:'1.0.0', icon:'box', active:false, autoUpdate:false, desc:'A reference module that demonstrates the module API.' },
  ]);
  const MODULES_AVAILABLE = [
    { id:'algolia', name:'Algolia Search', author:'Algolia', icon:'search', desc:'Instant hosted search across all content.', installs:'12k' },
    { id:'stripe', name:'Stripe Checkout', author:'Stripe', icon:'zap', desc:'Subscriptions and one-off payments.', installs:'40k' },
    { id:'cloudinary', name:'Cloudinary Media', author:'Cloudinary', icon:'image', desc:'Offload and transform media in the cloud.', installs:'8k' },
    { id:'webhooks', name:'Outgoing Webhooks', author:'Modular', icon:'webhook', desc:'Fire events to external systems on any change.', installs:'5k' },
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
  // Seeded as a fallback; replaced by the API (store.loadRoles) at boot.
  const ROLES = Vue.reactive([
    { id:'admin', name:'Administrator', desc:'Full access to everything.', users:0, builtin:true, caps:['*'] },
    { id:'editor', name:'Editor', desc:'Publishes and manages all content.', users:0, builtin:true, caps:[] },
    { id:'author', name:'Author', desc:'Writes and publishes their own content.', users:0, builtin:true, caps:[] },
    { id:'user', name:'User', desc:'Basic access.', users:0, builtin:true, caps:[] },
  ]);

  /* ---- System / CMS version + updates ---- */
  const SYSTEM = {
    version: '1.2.0', latest: '1.3.1', channel: 'stable',
    php: '8.3', db: 'MySQL 8.0', lastChecked: 'Today, 09:12',
    changelog: [
      { v:'1.3.1', date:'May 28, 2026', notes:'Field-group location rules, faster media cropper, security fixes.' },
      { v:'1.3.0', date:'May 04, 2026', notes:'Visual block editor, ACF-style relationship field.' },
    ],
  };
  // module homepages + a couple pending updates with release notes
  (function(){
    const urls = { i18n:'https://atypisch.nl/modules/i18n', mollie:'https://www.mollie.com/developers', backups:'https://modular.dev/modules/backups', sitemaps:'https://modular.dev/modules/sitemaps', community:'https://simuze.nl', redirects:'https://modular.dev/modules/redirects', example:'https://modular.dev/modules/example' };
    MODULES.forEach(m => { m.url = urls[m.id] || 'https://modular.dev'; });
    const a = MODULES.find(m=>m.id==='mollie'); if (a) { a.update='2.4.0'; a.updateNotes='Adds Apple Pay & Bancontact, faster webhook delivery, and fixes a refund edge case on partial orders.'; }
    const b = MODULES.find(m=>m.id==='backups'); if (b) { b.update='1.5.0'; b.updateNotes='Incremental backups, S3-compatible targets, and one-click restore from any snapshot.'; }
  })();

  /* ---- Analytics (mock series for Chart.js) ---- */
  const ANALYTICS = {
    range: 'Last 14 days',
    kpis: [
      { label:'Visitors', value:'18.4k', delta:'+12%', up:true },
      { label:'Pageviews', value:'52.1k', delta:'+8%', up:true },
      { label:'Avg. time', value:'2m 41s', delta:'+5%', up:true },
      { label:'Bounce rate', value:'38%', delta:'-3%', up:true },
    ],
    days: ['May 21','22','23','24','25','26','27','28','29','30','31','Jun 1','2','3'],
    visitors: [820,910,880,1240,1180,760,690,1320,1410,1290,1380,1460,1520,1610],
    pageviews: [2400,2700,2600,3500,3300,2100,1900,3700,3900,3600,3850,4100,4300,4600],
    sources: { labels:['Direct','Search','Social','Referral','Email'], data:[42,31,12,9,6] },
    byType: { labels:['Pages','Posts','Products','Projects','Releases'], data:[84,161,12,19,28] },
    topPages: [
      { path:'/work/digital-twins-helpdesk', views:4120, change:'+18%' },
      { path:'/', views:3880, change:'+4%' },
      { path:'/blog/headless-without-the-headache', views:2410, change:'+62%' },
      { path:'/products/mydayprice', views:1980, change:'-7%' },
      { path:'/work/living-in-eu', views:1640, change:'+11%' },
    ],
    // ---- extra series for the full analytics page ----
    devices: { labels:['Desktop','Mobile','Tablet'], data:[58,37,5] },
    hourly: [120,90,60,40,30,28,45,160,420,610,720,680,640,700,760,820,910,880,760,640,520,420,300,200],
    countries: [
      { name:'Netherlands', code:'NL', visitors:9820, pct:53 },
      { name:'Belgium', code:'BE', visitors:2410, pct:13 },
      { name:'Germany', code:'DE', visitors:1980, pct:11 },
      { name:'United Kingdom', code:'GB', visitors:1320, pct:7 },
      { name:'France', code:'FR', visitors:960, pct:5 },
      { name:'United States', code:'US', visitors:740, pct:4 },
    ],
    referrers: [
      { name:'google.com', visitors:7240, kind:'Search' },
      { name:'linkedin.com', visitors:1120, kind:'Social' },
      { name:'nu.nl', visitors:680, kind:'Referral' },
      { name:'newsletter', visitors:540, kind:'Email' },
      { name:'github.com', visitors:310, kind:'Referral' },
    ],
    events: [
      { name:'Contact form submit', count:128, change:'+12%' },
      { name:'Newsletter signup', count:642, change:'+28%' },
      { name:'Outbound: demo link', count:96, change:'-4%' },
      { name:'File download (PDF)', count:74, change:'+9%' },
    ],
    realtime: { active:42, spark:[12,18,9,22,30,26,42,38,44,40,42] },
    // ---- deeper breakdowns for the full analytics page ----
    engagement: [
      { label:'Sessions', value:'24.7k', delta:'+9%', up:true },
      { label:'Pages / session', value:'3.1', delta:'+0.3', up:true },
      { label:'New visitors', value:'61%', delta:'+4%', up:true },
      { label:'Returning', value:'39%', delta:'-4%', up:false },
      { label:'Engaged sessions', value:'68%', delta:'+6%', up:true },
      { label:'Goal conversions', value:'4.2%', delta:'+0.8%', up:true },
    ],
    funnel: [
      { step:'Landed on site', count:18400, pct:100 },
      { step:'Viewed a project', count:9120, pct:50 },
      { step:'Opened contact', count:3240, pct:18 },
      { step:'Started form', count:1180, pct:6 },
      { step:'Submitted', count:128, pct:0.7 },
    ],
    browsers: { labels:['Chrome','Safari','Firefox','Edge','Other'], data:[54,28,8,7,3] },
    os: { labels:['macOS','Windows','iOS','Android','Linux'], data:[38,29,18,12,3] },
    entryPages: [
      { path:'/', pct:34 },
      { path:'/work/digital-twins-helpdesk', pct:18 },
      { path:'/blog/headless-without-the-headache', pct:12 },
      { path:'/products/mydayprice', pct:9 },
    ],
    exitPages: [
      { path:'/contact', pct:22 },
      { path:'/', pct:16 },
      { path:'/blog/headless-without-the-headache', pct:14 },
      { path:'/work/living-in-eu', pct:8 },
    ],
    searchQueries: [
      { q:'eu hosting', count:312, results:true },
      { q:'digital twin cms', count:188, results:true },
      { q:'headless modular', count:142, results:true },
      { q:'prijzen', count:96, results:false },
      { q:'simuze login', count:74, results:false },
    ],
    goals: [
      { name:'Contact submitted', target:150, value:128, unit:'' },
      { name:'Newsletter signups', target:600, value:642, unit:'' },
      { name:'Demo requests', target:40, value:31, unit:'' },
    ],
    liveFeed: [
      { page:'/work/digital-twins-helpdesk', country:'NL', city:'Amsterdam', ago:'just now' },
      { page:'/', country:'BE', city:'Gent', ago:'12s ago' },
      { page:'/blog/headless-without-the-headache', country:'DE', city:'Berlin', ago:'30s ago' },
      { page:'/products/mydayprice', country:'NL', city:'Utrecht', ago:'48s ago' },
      { page:'/contact', country:'GB', city:'London', ago:'1m ago' },
    ],
    weekday: { labels:['Mon','Tue','Wed','Thu','Fri','Sat','Sun'], data:[3100,3400,3600,3500,3200,1800,1500] },
  };

  /* ---- Reports (BI) — types must match StatsController report endpoints ---- */
  const REPORTS = [
    { id:'production', name:'Content production', icon:'files', desc:'Items created & published over time, per author and type.' },
    { id:'cadence', name:'Publishing cadence', icon:'calendar', desc:'How regularly each type ships; gaps and streaks.' },
    { id:'forms', name:'Form submissions', icon:'form', desc:'Submission counts and spam stats per form.' },
    { id:'authors', name:'Author leaderboard', icon:'users', desc:'Output per team member in the selected period.' },
    { id:'health', name:'Content health', icon:'alert', desc:'Stale items, missing excerpts, old drafts, unused media.' },
  ];
  const REPORT_ROWS = [];

  /* ---- Front-end (public site) themes — for Appearance gallery ---- */
  const THEMES = Vue.reactive([
    { id:'atypisch', name:'Atypisch 2026', author:'Atypisch', desc:'Warm paper, terracotta accent. The current site.', active:true, dark:false, palette:['#F4F1EA','#FCFBF8','#221F1A','#C0552F'] },
    { id:'editorial', name:'Editorial', author:'Modular', desc:'Crisp white, serif headlines, indigo links.', active:false, dark:false, palette:['#FFFFFF','#F2F4F8','#1A1A2E','#2A4D8F'] },
    { id:'studio-dark', name:'Studio Dark', author:'Modular', desc:'Low-light theme for galleries & portfolios.', active:false, dark:true, palette:['#16140F','#221F1A','#F4F1EA','#C0552F'] },
    { id:'minimal', name:'Minimal', author:'Modular', desc:'Stark black & white, maximum restraint.', active:false, dark:false, palette:['#FFFFFF','#F6F6F6','#111111','#111111'] },
    { id:'meadow', name:'Meadow', author:'Atypisch', desc:'Soft green, generous spacing, rounded.', active:false, dark:false, palette:['#F2F4EE','#FBFCF9','#1F2A1C','#2F6B4F'] },
    { id:'press', name:'Press', author:'Modular', desc:'Dense news layout with strong type scale.', active:false, dark:false, palette:['#FFFDF7','#F3EEE2','#1B1B1B','#B0421F'] },
  ]);

  window.__mdlr.data = {
    FIELD_TYPES, TYPE_MAP, CAT_ORDER, CAT_VAR,
    FIELD_GROUPS, COMPONENTS, POST_TYPES, PROJECT_SCHEMA, RULES,
    SAMPLE_POST, POSTS, TAXONOMIES, MEDIA, IMAGE_PRESETS, IMAGE_PRESETS_DEFAULT, FORMS, SUBMISSIONS, ACTIONS, uid,
    EXPORT_TABLES, IMPORT_HISTORY, PROFILE, ICON_CHOICES,
    LOCATION_PARAMS, LOCATION_OPS, MODULES, MODULES_AVAILABLE,
    CAP_GROUPS, ALL_CAPS, ROLES, SYSTEM, ANALYTICS, REPORTS, REPORT_ROWS, THEMES,
    MEDIA_FOLDERS, RENAME_PATTERN, NOTIFICATIONS,
  };
})();
