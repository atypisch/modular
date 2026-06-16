/* ============================================================
   COMMAND PALETTE — the "Search or jump to" / ⌘K launcher.
   Filters destinations, content and actions; arrow-key + enter.
   ============================================================ */
(function () {
  window.__mdlr = window.__mdlr || { comps: {} };
  const C = window.__mdlr.comps;
  const D = () => window.__mdlr.data;
  const plural = (n) => /s$/i.test(n) ? n : n + 's';

  C.UiCmdK = {
    name: 'UiCmdK',
    inject: ['go', 'ui'],
    data() { return { q: '', sel: 0 }; },
    computed: {
      commands() {
        const go = this.go, list = [];
        const nav = [
          ['Dashboard','dashboard',()=>go('dashboard',{})],
          ['Updates','refresh',()=>go('dashboard',{tab:'updates'})],
          ['Analytics','sigma',()=>go('dashboard',{tab:'analytics'})],
          ['Reports','table',()=>go('dashboard',{tab:'reports'})],
          ['Media library','image',()=>go('media',{})],
          ['Field groups','git',()=>go('binding',{})],
          ['Post types','database',()=>go('postTypes',{})],
          ['Taxonomies','pin',()=>go('taxonomies',{})],
          ['Components','layers',()=>go('components',{})],
          ['Forms','form',()=>go('forms',{})],
          ['Actions','zap',()=>go('actions',{})],
          ['Modules','layers',()=>go('modules',{})],
          ['Users','users',()=>go('users',{})],
          ['Roles & capabilities','lock',()=>go('roles',{})],
          ['Export / Import','refresh',()=>go('exportImport',{})],
          ['Profile','user',()=>go('profile',{})],
          ['Settings','settings',()=>go('settings',{})],
          ['Design system','palette',()=>go('settings',{tab:'design'})],
          ['Appearance · themes','palette',()=>go('settings',{tab:'appearance'})],
          ['Permalinks','link',()=>go('settings',{tab:'permalinks'})],
        ];
        nav.forEach(n => list.push({ group:'Go to', label:n[0], icon:n[1], run:n[2] }));
        D().POST_TYPES.forEach(pt => list.push({ group:'Content', label:'All ' + plural(pt.name).toLowerCase(), icon:pt.icon, hint:'list', run:()=>go('content',{type:pt.id}) }));
        list.push({ group:'Create', label:'New content', icon:'plus', run:()=>go('editor',{postId:'new',type:'project'}) });
        list.push({ group:'Create', label:'New field group', icon:'plus', run:()=>go('fieldBuilder',{group:'new'}) });
        list.push({ group:'Create', label:'New post type', icon:'plus', run:()=>go('postTypes',{}) });
        const tn = (t) => { const p = D().POST_TYPES.find(x=>x.id===t); return p?p.name:t; };
        D().POSTS.forEach(p => list.push({ group:'Content', label:p.title, icon:'file', hint:tn(p.type), run:()=>go('editor',{postId:p.id,type:p.type}) }));
        return list;
      },
      filtered() {
        const q = this.q.trim().toLowerCase();
        if (!q) return this.commands.filter(c => c.group === 'Go to' || c.group === 'Create');
        return this.commands.filter(c => c.label.toLowerCase().includes(q) || (c.hint && c.hint.toLowerCase().includes(q)) || c.group.toLowerCase().includes(q));
      },
      grouped() {
        const order = ['Go to','Create','Content']; const map = {};
        this.filtered.forEach(c => { (map[c.group] = map[c.group] || []).push(c); });
        return order.filter(g => map[g]).map(g => ({ group:g, items:map[g] }));
      },
      flat() { return this.grouped.flatMap(g => g.items); },
    },
    watch: { q() { this.sel = 0; } },
    mounted() { this.$nextTick(() => this.$refs.q && this.$refs.q.focus()); },
    methods: {
      close() { this.ui.cmdk = false; },
      run(c) { c.run(); this.close(); },
      onKey(e) {
        if (e.key === 'ArrowDown') { e.preventDefault(); this.sel = (this.sel + 1) % Math.max(1, this.flat.length); this.scroll(); }
        else if (e.key === 'ArrowUp') { e.preventDefault(); this.sel = (this.sel - 1 + this.flat.length) % Math.max(1, this.flat.length); this.scroll(); }
        else if (e.key === 'Enter') { e.preventDefault(); const c = this.flat[this.sel]; if (c) this.run(c); }
        else if (e.key === 'Escape') { e.preventDefault(); this.close(); }
      },
      scroll() { this.$nextTick(() => { const el = this.$el.querySelector('.cmdk-item.is-sel'); if (el) el.scrollIntoView({ block:'nearest' }); }); },
      idx(c) { return this.flat.indexOf(c); },
    },
    template: `
    <teleport to="body">
      <div class="scrim" @click="close"></div>
      <div class="cmdk-wrap" @click.self="close">
        <div class="cmdk" role="dialog">
          <div class="cmdk-input">
            <Icon name="search" :size="17"/>
            <input ref="q" v-model="q" placeholder="Search or jump to…" @keydown="onKey">
            <span class="kbd">esc</span>
          </div>
          <div class="cmdk-results">
            <template v-for="g in grouped" :key="g.group">
              <div class="cmdk-group">{{ g.group }}</div>
              <button v-for="c in g.items" :key="c.group+c.label" class="cmdk-item" :class="{'is-sel': idx(c)===sel}" @click="run(c)" @mousemove="sel=idx(c)">
                <span class="cmdk-ico"><Icon :name="c.icon" :size="15"/></span>
                <span class="cmdk-label">{{ c.label }}</span>
                <span class="t-meta" v-if="c.hint">{{ c.hint }}</span>
                <Icon name="arrowRight" :size="14" class="cmdk-go"/>
              </button>
            </template>
            <div v-if="!flat.length" class="cmdk-empty"><Icon name="search" :size="22"/><div>No matches for “{{ q }}”.</div></div>
          </div>
          <div class="cmdk-foot">
            <span><span class="kbd">↑</span><span class="kbd">↓</span> navigate</span>
            <span><span class="kbd">↵</span> open</span>
            <div class="grow"></div>
            <span>{{ flat.length }} result{{ flat.length===1?'':'s' }}</span>
          </div>
        </div>
      </div>
    </teleport>`,
  };
})();
