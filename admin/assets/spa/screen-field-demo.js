/* Field demo (Fase 1 acceptance): proves the recursive FieldRenderer
   against a test schema that exercises every container (tab/columns/
   group/repeater/blocks), provenance badges, and conditional visibility.
   This is a development harness; it is replaced by the real field builder
   (Fase 4) and content editor (Fase 5). Reachable at #fieldDemo. */
(function () {
  const M = window.__mdlr = window.__mdlr || { comps: {} };
  const C = M.comps;
  const { reactive } = Vue;

  const SCHEMA = [
    { id: 't1', type: 'tab', name: 'tab_content', label: 'Content', _section: true },
    { id: 'f_title', type: 'text', name: 'title', label: 'Title', required: true, prov: 'type', placeholder: 'Project title' },
    { id: 'f_tag', type: 'text', name: 'tagline', label: 'Tagline', prov: 'type', instructions: 'One line shown under the title.' },
    { id: 'f_body', type: 'wysiwyg', name: 'body', label: 'Body', prov: 'type' },
    {
      id: 'f_cols', type: 'columns', name: 'cols1', cols: [
        [{ id: 'f_client', type: 'text', name: 'client', label: 'Client', w: 'full', prov: 'type' }],
        [{ id: 'f_year', type: 'number', name: 'year', label: 'Year', w: 'full', prov: 'type' }],
      ],
    },
    {
      id: 'f_grp', type: 'group', name: 'links', label: 'Links', prov: 'shared',
      fields: [
        { id: 'f_url', type: 'url', name: 'project_url', label: 'Project URL', prov: 'shared' },
        { id: 'f_repo', type: 'url', name: 'repo_url', label: 'Repository', prov: 'shared' },
      ],
    },
    {
      id: 'f_rep', type: 'repeater', name: 'meta_rows', label: 'Project details', itemLabel: 'Row', prov: 'post',
      instructions: 'Label / value rows.',
      sub_fields: [
        { id: 'r_l', type: 'text', name: 'label', label: 'Label', w: 'half' },
        { id: 'r_v', type: 'text', name: 'value', label: 'Value', w: 'half' },
      ],
    },
    { id: 't2', type: 'tab', name: 'tab_meta', label: 'Meta & logic', _section: true },
    { id: 'f_date', type: 'date', name: 'launch_date', label: 'Launch date', prov: 'type' },
    { id: 'f_when', type: 'datetime', name: 'publish_at', label: 'Publish at', prov: 'shared', w: 'half' },
    { id: 'f_vis', type: 'select', name: 'visibility', label: 'Visibility', prov: 'shared', w: 'half', choices: [{ value: 'public', label: 'Public' }, { value: 'private', label: 'Private' }] },
    { id: 'f_mode', type: 'button_group', name: 'side', label: 'Media side', prov: 'type', choices: [{ value: 'left', label: 'Left' }, { value: 'right', label: 'Right' }] },
    { id: 'f_tags', type: 'checkbox', name: 'channels', label: 'Channels', prov: 'type', choices: [{ value: 'email', label: 'Email' }, { value: 'sms', label: 'SMS' }, { value: 'push', label: 'Push' }] },
    { id: 'f_cover', type: 'image', name: 'cover', label: 'Cover image', prov: 'type', w: 'half' },
    { id: 'f_accent', type: 'color', name: 'accent', label: 'Accent', prov: 'type', w: 'half' },
    { id: 'f_code', type: 'code', name: 'snippet', label: 'Code (CodeMirror)', prov: 'type', instructions: 'Loads CodeMirror 6 from CDN; falls back to a textarea.' },
    { id: 'f_embed', type: 'embed', name: 'video', label: 'Embed (YouTube/Vimeo)', prov: 'type', instructions: 'Paste a YouTube/Vimeo URL for a live preview.' },
    { id: 'f_rel2', type: 'relationship', name: 'related_posts', label: 'Related posts', prov: 'type', instructions: 'Search, filter by post type, and select — drag to reorder.' },
    { id: 'f_latest', type: 'latest_posts', name: 'related_latest', label: 'Latest posts (query)', prov: 'type' },
    { id: 'f_audio', type: 'audio', name: 'track', label: 'Audio', prov: 'type', w: 'half' },
    { id: 'f_feat', type: 'true_false', name: 'featured', label: 'Featured on homepage', prov: 'type' },
    { id: 'f_press', type: 'textarea', name: 'press_quote', label: 'Press quote', prov: 'post', _conditional: 'featured', instructions: 'Shown only when “Featured” is on (conditional visibility).' },
    {
      id: 'f_blocks', type: 'blocks', name: 'blocks', label: 'Blocks', prov: 'post',
      instructions: 'Composed from components (visual editor in Fase 5).',
      allowed: ['cmp_hero', 'cmp_richtext'],
    },
  ];

  C.ScreenFieldDemo = {
    name: 'ScreenFieldDemo',
    data() {
      const model = reactive({
        title: 'Digital Twins Helpdesk', tagline: '', body: '<p>Intro paragraph…</p>',
        client: 'Atypisch', year: 2026, links: {}, meta_rows: [{ label: 'Role', value: 'Design & build' }],
        featured: false,
        blocks: [{ id: 'b1', comp: 'cmp_richtext', data: { body: '<p>A block.</p>' } }],
      });
      return { schema: SCHEMA, model, blocksMap: {} };
    },
    mounted() {
      // make sure the PHP field list is mirrored onto the registry
      if (M.fields && !M._fieldsHydrated) M.fields.hydrateFromApi();
      const comps = (M.data && M.data.COMPONENTS) || [];
      const map = {}; comps.forEach(c => { map[c.id] = c; }); this.blocksMap = map;
    },
    computed: {
      json() { return JSON.stringify(this.model, null, 2); },
    },
    template: `
    <div class="page fade-up">
      <div class="page-head">
        <div>
          <div class="t-eyebrow" style="margin-bottom:8px">Fundament · Fase 1</div>
          <h1 class="t-title t-h1">FieldRenderer demo</h1>
          <p class="page-sub" style="margin-top:6px">Eén recursieve renderer: tab · columns · group · repeater · blocks, met provenance-badges en conditional visibility.</p>
        </div>
      </div>
      <div class="dash-grid" style="grid-template-columns: 1.6fr 1fr; align-items:start">
        <section class="panel"><div class="panel-head"><h2 class="t-title t-h3">Resolved fieldset</h2></div>
          <div style="padding:18px"><FieldRenderer :schema="schema" :model="model" :blocks="blocksMap"/></div>
        </section>
        <section class="panel" style="position:sticky;top:18px"><div class="panel-head"><h2 class="t-title t-h3">Live model</h2><span class="t-meta">posts.data shape</span></div>
          <pre class="mono" style="padding:16px;font-size:12px;overflow:auto;max-height:70vh;white-space:pre-wrap">{{ json }}</pre>
        </section>
      </div>
    </div>`,
  };
})();
