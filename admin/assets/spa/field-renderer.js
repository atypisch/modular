/* ============================================================
   FieldRenderer (§3) — ONE recursive renderer that walks a schema
   tree, resolves each leaf to its registered component, recurses
   through container nodes (tab/columns/accordion/group/repeater/
   blocks), and centrally handles provenance badges + conditional
   visibility. Used by the content editor, forms and the field demo.

   Props:
     schema  : array of field nodes
     model   : reactive object of values keyed by field.name
     blocks  : map of componentId -> { name, icon, fields } (for `blocks`)
   ============================================================ */
(function () {
  const M = window.__mdlr = window.__mdlr || { comps: {} };
  const C = M.comps;
  const F = () => M.fields;

  /* provenance helpers (match the prototype's .prov badges) */
  const provLabel = (p) => p === 'post' ? 'Added here' : (p === 'comp' || p === 'shared') ? 'Shared group' : 'From post type';
  const provIcon = (p) => p === 'post' ? 'plus' : (p === 'comp' || p === 'shared') ? 'git' : 'lock';
  const provClass = (p) => p === 'post' ? 'prov--post' : (p === 'comp' || p === 'shared') ? 'prov--comp' : 'prov--type';

  /* evaluate a node's conditional visibility against the model */
  function visible(node, model) {
    if (!node) return false;
    if (node._conditional) { return !!model[node._conditional]; }
    const r = node.rules;
    if (r && Array.isArray(r.conds) && r.conds.length) {
      const test = (c) => {
        const v = model[c.field];
        switch (c.op) {
          case 'is': case '==': return String(v) === String(c.value);
          case 'isnot': case '!=': return String(v) !== String(c.value);
          case 'set': case 'is set': return v != null && v !== '' && !(Array.isArray(v) && !v.length);
          case 'empty': return v == null || v === '' || (Array.isArray(v) && !v.length);
          default: return true;
        }
      };
      return r.logic === 'any' ? r.conds.some(test) : r.conds.every(test);
    }
    return true;
  }

  const widthClass = (w) => w === 'half' ? 'fr-col-6' : w === 'third' ? 'fr-col-4' : 'fr-col-12';

  C.FieldRenderer = {
    name: 'FieldRenderer',
    props: {
      schema: { type: Array, default: () => [] },
      model: { type: Object, default: () => ({}) },
      blocks: { type: Object, default: () => ({}) },
      nested: Boolean,
      // when true, leaf/child fields don't render a provenance badge — the
      // parent container (group/repeater/blocks) already shows it once.
      hideProv: Boolean,
    },
    computed: {
      // split top-level schema into tab sections when `tab` nodes are present
      tabs() {
        const hasTabs = this.schema.some(n => F().canonical(n.type) === 'tab');
        if (!hasTabs || this.nested) return null;
        const sections = []; let cur = { label: 'General', name: '_default', items: [] };
        this.schema.forEach(n => {
          if (F().canonical(n.type) === 'tab') { if (cur.items.length || sections.length) sections.push(cur); cur = { label: n.label, name: n.name || n.label, items: [] }; }
          else cur.items.push(n);
        });
        sections.push(cur);
        return sections.filter(s => s.items.length);
      },
      flat() { return this.schema.filter(n => F().canonical(n.type) !== 'tab'); },
    },
    data() { return { activeTab: 0 }; },
    methods: {
      visible(n) { return visible(n, this.model); },
      provLabel, provIcon, provClass,
      isContainer(n) { return F().isContainer(n.type); },
      ensure(n) {
        // make sure the model has a sensible default for this field
        if (n.name && this.model[n.name] === undefined && F().get(n.type).stored !== false) {
          this.model[n.name] = F().defaultValue(n);
        }
        return this.model[n.name];
      },
      compFor(n) { return F().component(n.type); },
      // repeater helpers
      repItems(n) { if (!Array.isArray(this.model[n.name])) this.model[n.name] = []; return this.model[n.name]; },
      repAdd(n) { const row = {}; (n.sub_fields || []).forEach(sf => { row[sf.name] = F().defaultValue(sf); }); this.repItems(n).push(row); },
      repDel(n, i) { this.repItems(n).splice(i, 1); },
      repReorder(n, arr) { this.model[n.name] = arr; },
      // blocks helpers
      blkItems(n) { if (!Array.isArray(this.model[n.name])) this.model[n.name] = []; return this.model[n.name]; },
      blkDef(b) { return this.blocks[b.comp] || (window.__mdlr.data && (window.__mdlr.data.COMPONENTS || []).find(c => c.id === b.comp)) || { name: b.comp, fields: [] }; },
      groupModel(n) { if (typeof this.model[n.name] !== 'object' || this.model[n.name] == null || Array.isArray(this.model[n.name])) this.model[n.name] = {}; return this.model[n.name]; },
    },
    template: `
    <div class="fr">
      <!-- tabbed top level -->
      <template v-if="tabs">
        <div class="tabs" style="margin-bottom:18px">
          <button v-for="(t,ti) in tabs" :key="t.name" :class="{'is-on':activeTab===ti}" @click="activeTab=ti">{{ t.label }}</button>
        </div>
        <FieldRenderer :schema="tabs[activeTab].items" :model="model" :blocks="blocks" nested/>
      </template>

      <!-- flat / nested list -->
      <div v-else class="fr-grid">
        <template v-for="n in flat" :key="n.id || n.name">
          <div v-if="visible(n)" class="fr-cell" :class="widthClass(n.w)">

            <!-- divider / message (layout-only) -->
            <hr v-if="n.type==='divider'" class="fr-divider">
            <div v-else-if="n.type==='message'" class="fr-message">{{ n.instructions || n.label }}</div>

            <!-- GROUP -->
            <section v-else-if="n.type==='group'" class="fr-group">
              <div class="fr-field-head"><span class="ed-field-label">{{ n.label }}</span><span class="prov" :class="provClass(n.prov)" v-if="n.prov && !hideProv"><Icon :name="provIcon(n.prov)" :size="11"/> {{ provLabel(n.prov) }}</span></div>
              <div class="fr-group-body"><FieldRenderer :schema="n.fields||n.sub_fields||[]" :model="groupModel(n)" :blocks="blocks" nested hideProv/></div>
            </section>

            <!-- COLUMNS -->
            <section v-else-if="n.type==='columns'" class="fr-columns">
              <div v-for="(col,ci) in (n.cols||[])" :key="ci" class="fr-column"><FieldRenderer :schema="col" :model="model" :blocks="blocks" nested :hideProv="hideProv"/></div>
            </section>

            <!-- REPEATER -->
            <section v-else-if="n.type==='repeater'" class="fr-field">
              <div class="fr-field-head"><span class="ed-field-label">{{ n.label }}<span class="field-req" v-if="n.required">*</span></span><span class="prov" :class="provClass(n.prov)" v-if="n.prov && !hideProv"><Icon :name="provIcon(n.prov)" :size="11"/> {{ provLabel(n.prov) }}</span></div>
              <p class="field-hint" v-if="n.instructions" style="margin-top:0">{{ n.instructions }}</p>
              <SortableList :modelValue="repItems(n)" @update:modelValue="a=>repReorder(n,a)" itemClass="rep-row-wrap">
                <template #default="{ item, index, startDrag }">
                  <div class="rep-item">
                    <div class="rep-item-bar"><span class="drag-handle" draggable="true" @dragstart="startDrag" title="Drag to reorder"><Icon name="drag" :size="14"/></span><span class="t-meta">{{ (n.itemLabel||'Item') }} {{ index+1 }}</span><button class="iconbtn iconbtn--sm is-danger" @click="repDel(n,index)"><Icon name="x" :size="13"/></button></div>
                    <FieldRenderer :schema="n.sub_fields||[]" :model="item" :blocks="blocks" nested hideProv/>
                  </div>
                </template>
              </SortableList>
              <button class="btn btn--ghost btn--sm" @click="repAdd(n)"><Icon name="plus" :size="14"/> Add {{ (n.itemLabel||'row').toLowerCase() }}</button>
            </section>

            <!-- BLOCKS (basic; visual editor in Fase 5) -->
            <section v-else-if="n.type==='blocks'" class="ed-blocks-field">
              <div class="fr-field-head"><span class="ed-field-label">{{ n.label }}</span><span class="prov prov--post" v-if="n.prov==='post'"><Icon name="plus" :size="11"/> Added here</span></div>
              <p class="field-hint" v-if="n.instructions" style="margin-top:0">{{ n.instructions }}</p>
              <div v-for="(b,bi) in blkItems(n)" :key="b.id||bi" class="block">
                <div class="block-head"><span class="block-ico"><Icon :name="blkDef(b).icon||'layers'" :size="15"/></span><div class="block-head-main"><strong>{{ blkDef(b).name }}</strong></div><button class="iconbtn iconbtn--sm is-danger" @click="blkItems(n).splice(bi,1)"><Icon name="x" :size="13"/></button></div>
                <div class="block-body"><FieldRenderer :schema="blkDef(b).fields||[]" :model="b.data || (b.data={})" :blocks="blocks" nested hideProv/></div>
              </div>
              <div class="t-meta" v-if="!blkItems(n).length" style="padding:10px 0">No blocks yet.</div>
            </section>

            <!-- LEAF FIELD -->
            <div v-else class="fr-field">
              <div class="fr-field-head">
                <span class="ed-field-label">{{ n.label }}<span class="field-req" v-if="n.required">*</span></span>
                <span class="prov" :class="provClass(n.prov)" v-if="n.prov && !hideProv"><Icon :name="provIcon(n.prov)" :size="11"/> {{ provLabel(n.prov) }}</span>
              </div>
              <component :is="compFor(n)" :definition="n" :modelValue="ensure(n)" @update:modelValue="v=>{ model[n.name]=v }"/>
              <p class="field-hint" v-if="n.instructions">{{ n.instructions }}</p>
            </div>

          </div>
        </template>
      </div>
    </div>`,
    setup() { return { widthClass }; },
  };
})();
