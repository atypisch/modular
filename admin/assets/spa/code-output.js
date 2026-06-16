/* ============================================================
   CODE OUTPUT — the "see the API" layer (Payload-style).
   A reusable modal that shows JSON / PHP / HTML for any item,
   plus serializers used by the visual editor and post editor.
   window.__mdlr.serialize = { layer, component, post, copy }
   Component: <UiCodeModal :tabs="[{id,label,lang,code}]" title=.. />
   ============================================================ */
(function () {
  window.__mdlr = window.__mdlr || { comps: {} };
  const C = window.__mdlr.comps;
  const D = () => window.__mdlr.data;
  const slugify = (s) => (s || '').toLowerCase().replace(/[^a-z0-9]+/g, '-').replace(/^-|-$/g, '');

  /* ---- serializers ---- */
  function layerJson(l) {
    const o = { type: l.kind };
    if (l.kind === 'heading') { o.level = l.level; o.text = l.text; }
    else if (l.kind === 'text') { o.text = l.text; }
    else if (l.kind === 'image') { o.src = 'uploads/' + (l.name || 'image.jpg'); o.alt = l.alt || ''; }
    else if (l.kind === 'button') { o.label = l.label; o.url = l.url || '#'; o.variant = l.variant; }
    else if (l.kind === 'video') { o.url = l.url || ''; o.poster = 'uploads/' + (l.name || 'poster.jpg'); }
    else if (l.kind === 'gallery') { o.cols = l.cols || 3; o.images = (l.images || [1,2,3]).map((x, i) => 'uploads/frame-' + (i+1) + '.jpg'); }
    else if (l.kind === 'stack') { o.height = l.height || 'md'; }
    else if (l.kind === 'repeater') { o.items = (l.items || []).map(it => ({ ...it })); }
    else if (l.kind === 'embed') { o.html = l.code || ''; }
    // overlay placement (only when this layer is itself an overlay)
    if (l._isOverlay) o.align = l.align;
    // responsive
    if (l.hideMobile) o.hideMobile = true;
    // nested overlays for containers
    if (l.overlays && l.overlays.length) {
      if (l.stackMobile) o.stackMobile = true;
      o.overlays = l.overlays.map(ov => layerJson(Object.assign({}, ov, { _isOverlay: true })));
    }
    return o;
  }
  function componentObj(comp, layers) {
    return {
      component: slugify(comp.name),
      version: comp.version,
      layers: (layers || []).map(layerJson),
    };
  }
  function componentPhp(comp, layers) {
    const slug = slugify(comp.name);
    return [
      '<?php',
      '/* Render the "' + comp.name + '" component (v' + comp.version + ') */',
      'echo Modular::component(\'' + slug + '\', [',
      '    \'data\' => $block[\'' + slug + '\'] ?? [],',
      ']);',
      '',
      '// …or in a template:',
      '// <?php the_component(\'' + slug + '\', $data); ?>',
    ].join('\n');
  }
  function leafHtml(l, indent) {
    const p = indent || '  ';
    if (l.kind === 'heading') return p + '<h' + l.level + '>' + (l.text||'') + '</h' + l.level + '>';
    if (l.kind === 'text') return p + '<p>' + (l.text||'') + '</p>';
    if (l.kind === 'image') return p + '<img src="uploads/' + (l.name||'image.jpg') + '" alt="' + (l.alt||'') + '">';
    if (l.kind === 'button') return p + '<a class="btn btn--' + l.variant + '" href="' + (l.url||'#') + '">' + (l.label||'') + '</a>';
    if (l.kind === 'video') return p + '<video src="' + (l.url||'') + '" controls></video>';
    if (l.kind === 'gallery') return p + '<div class="gallery cols-' + (l.cols||3) + '"><!-- ' + ((l.images||[1,2,3]).length) + ' images --></div>';
    if (l.kind === 'repeater') return p + '<ul class="repeater"><!-- ' + ((l.items||[]).length) + ' rows --></ul>';
    if (l.kind === 'embed') return p + (l.code||'');
    if (l.kind === 'divider') return p + '<hr>';
    return '';
  }
  function componentHtml(comp, layers) {
    const lines = ['<section class="mdlr-' + slugify(comp.name) + '">'];
    (layers || []).forEach(l => {
      if (l.overlays && l.overlays.length) {
        lines.push('  <div class="layer layer--' + l.kind + '">');
        lines.push('  ' + leafHtml(l, '  '));
        lines.push('    <div class="overlays">');
        l.overlays.forEach(o => lines.push('    ' + leafHtml(o, '    ').trim().replace(/^/, '      ')));
        lines.push('    </div>');
        lines.push('  </div>');
      } else {
        lines.push(leafHtml(l, '  '));
      }
    });
    lines.push('</section>');
    return lines.join('\n');
  }

  // Post → REST JSON (Payload-style), built from the live editor model
  function fieldValue(f) {
    if (f.type === 'true_false') return !!f.value;
    if (f.type === 'image') return f.value ? { src: 'uploads/' + (f.value.name || 'image.jpg'), alt: f.value.alt || '' } : null;
    if (f.type === 'gallery') return [];
    if (f.type === 'repeater') return [];
    if (['relationship','post_object'].includes(f.type)) return Array.isArray(f.value) ? f.value : [];
    if (f.type === 'number') return f.value != null ? Number(f.value) : null;
    return f.value != null && f.value !== '' ? f.value : null;
  }
  function postObj(post, tabs, blocks) {
    const fields = {};
    (tabs || []).forEach(t => (t.groups || []).forEach(g => (g.fields || []).forEach(f => {
      fields[f.name || slugify(f.label)] = fieldValue(f);
    })));
    const out = {
      id: typeof post.id === 'number' ? post.id : 0,
      type: post.type,
      slug: post.slug || slugify(post.title),
      status: (post.status || 'draft').toLowerCase(),
      title: post.title,
      updatedAt: '2026-06-04T09:12:00Z',
      fields,
    };
    if (blocks && blocks.length) {
      out.blocks = blocks.map(b => {
        const c = D().COMPONENTS.find(x => x.id === b.comp);
        return { type: c ? slugify(c.name) : b.comp, _summary: b.summary };
      });
    }
    return out;
  }
  function postPhp(post) {
    const slug = post.slug || slugify(post.title);
    return [
      '<?php',
      '/* Fetch this ' + post.type + ' */',
      '$post = Modular::query(\'' + post.type + '\')->slug(\'' + slug + '\')->first();',
      '',
      'echo $post->title;',
      'echo $post->field(\'body\');',
      '',
      '// REST:  GET /api/v1/' + post.type + '/' + slug,
      '// GraphQL:  { ' + post.type + '(slug: "' + slug + '") { title } }',
    ].join('\n');
  }

  window.__mdlr.serialize = {
    layer: layerJson,
    component: componentObj,
    componentPhp, componentHtml,
    post: postObj, postPhp,
    pretty: (o) => JSON.stringify(o, null, 2),
  };

  /* ---- the modal ---- */
  C.UiCodeModal = {
    name: 'UiCodeModal',
    props: { tabs: Array, title: { type: String, default: 'API output' }, subtitle: String, endpoint: String },
    emits: ['close'],
    data() { return { active: (this.tabs[0] || {}).id, copied: false }; },
    computed: { cur() { return this.tabs.find(t => t.id === this.active) || this.tabs[0]; } },
    methods: {
      copy() {
        const txt = this.cur.code;
        const done = () => { this.copied = true; setTimeout(() => { this.copied = false; }, 1400); };
        if (navigator.clipboard && navigator.clipboard.writeText) navigator.clipboard.writeText(txt).then(done, done);
        else done();
      },
    },
    template: `
    <teleport to="body">
      <div class="codemodal-wrap" @click.self="$emit('close')">
        <div class="codemodal" role="dialog">
          <div class="codemodal-head">
            <span class="codemodal-ico"><Icon name="code" :size="16"/></span>
            <div style="flex:1;min-width:0"><strong>{{ title }}</strong><div class="t-meta" v-if="subtitle">{{ subtitle }}</div></div>
            <button class="iconbtn" @click="$emit('close')"><Icon name="x"/></button>
          </div>
          <div class="codemodal-bar">
            <div class="codemodal-tabs">
              <button v-for="t in tabs" :key="t.id" :class="{'is-on': active===t.id}" @click="active=t.id">{{ t.label }}</button>
            </div>
            <div class="grow"></div>
            <span class="t-key codemodal-endpoint" v-if="endpoint && cur.id==='json'">{{ endpoint }}</span>
            <button class="btn btn--soft btn--sm" @click="copy"><Icon :name="copied?'check':'copy'" :size="13"/> {{ copied ? 'Copied' : 'Copy' }}</button>
          </div>
          <pre class="codemodal-code" :class="'lang-'+(cur.lang||'json')"><code>{{ cur.code }}</code></pre>
        </div>
      </div>
    </teleport>`,
  };
})();
