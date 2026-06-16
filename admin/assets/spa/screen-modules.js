/* ============================================================
   MODULES — extend the CMS (WordPress-plugin idea, compact).
   Installed list with a single active toggle + tidy menu, and
   a Browse tab to add new modules. No cluttered button rows.
   ============================================================ */
(function () {
  window.__mdlr = window.__mdlr || { comps: {} };
  const C = window.__mdlr.comps;
  const D = () => window.__mdlr.data;

  C.ScreenModules = {
    name: 'ScreenModules',
    data() { return { tab: 'installed', q: '', bq: '', preview: null }; },
    computed: {
      modules() { const q = this.q.trim().toLowerCase(); return D().MODULES.filter(m => !q || m.name.toLowerCase().includes(q) || m.desc.toLowerCase().includes(q)); },
      available() { const q = this.bq.trim().toLowerCase(); return D().MODULES_AVAILABLE.filter(m => !q || m.name.toLowerCase().includes(q) || m.desc.toLowerCase().includes(q) || m.author.toLowerCase().includes(q)); },
      activeCount() { return D().MODULES.filter(m => m.active).length; },
      updatesAvailable() { return D().MODULES.filter(m => m.update).length; },
    },
    methods: {
      remove(m) { const a = D().MODULES; const i = a.indexOf(m); if (i >= 0) a.splice(i, 1); },
      updateMod(m) { m.version = m.update; m.update = null; m.updateNotes = null; },
      updateAll() { D().MODULES.filter(m => m.update).forEach(m => this.updateMod(m)); },
      menuFor(m) {
        const items = [
          { label:'Settings', icon:'settings' },
          { label:'View details', icon:'arrowUpRight', onClick: () => window.open(m.url, '_blank') },
          { label: m.autoUpdate ? 'Disable auto-updates' : 'Enable auto-updates', icon:'refresh', onClick: () => { m.autoUpdate = !m.autoUpdate; } },
          { sep:true },
          { label:'Remove', icon:'trash', danger:true, onClick: () => this.remove(m) },
        ];
        if (m.update) items.splice(1, 0, { label:'What\u2019s new', icon:'info', onClick: () => { this.preview = m; } });
        return items;
      },
    },
    template: `
    <div class="page page--wide fade-up">
      <div class="page-head">
        <div>
          <h1 class="t-title t-h1">Modules</h1>
          <p class="page-sub" style="margin-top:6px">Extend Modular with optional features. {{ modules.length }} installed · {{ activeCount }} active.</p>
        </div>
        <button class="btn btn--accent" @click="tab='browse'"><Icon name="plus"/> Add module</button>
      </div>

      <div class="tabs" style="margin-bottom:18px">
        <button :class="{'is-on':tab==='installed'}" @click="tab='installed'"><Icon name="layers" :size="15"/> Installed <span class="tab-count">{{ D().MODULES.length }}</span></button>
        <button :class="{'is-on':tab==='browse'}" @click="tab='browse'"><Icon name="search" :size="15"/> Browse</button>
      </div>

      <!-- INSTALLED -->
      <template v-if="tab==='installed'">
        <div class="mod-updbanner" v-if="updatesAvailable">
          <span class="upd-badge is-due" style="width:34px;height:34px"><Icon name="refresh" :size="16"/></span>
          <span><strong>{{ updatesAvailable }} update{{ updatesAvailable>1?'s':'' }} available</strong><span class="t-meta"> · keep your modules current.</span></span>
          <div class="grow"></div>
          <button class="btn btn--accent btn--sm" @click="updateAll"><Icon name="upload" :size="14"/> Update all</button>
        </div>
        <div class="list-toolbar" style="border:1px solid var(--line);border-bottom:0;border-radius:var(--r-md) var(--r-md) 0 0;background:var(--surface)">
          <div class="topbar-search" style="max-width:280px;flex:0 1 280px;cursor:text"><Icon name="search" :size="15"/><input class="fb-lib-input" v-model="q" placeholder="Search modules…" style="color:var(--ink)"></div>
        </div>
        <div class="mod-list card" style="border-radius:0 0 var(--r-md) var(--r-md)">
          <div class="mod-row" v-for="m in modules" :key="m.id" :class="{'is-active': m.active}">
            <span class="mod-ico" :class="{'is-on': m.active}"><Icon :name="m.icon" :size="18"/></span>
            <div class="mod-main">
              <div class="mod-name">
                <strong>{{ m.name }}</strong>
                <span class="badge" :class="m.active?'badge--ok':''"><span class="badge--dot" v-if="m.active"></span>{{ m.active ? 'active' : 'inactive' }}</span>
                <span class="mod-autoupd" v-if="m.autoUpdate" title="Auto-updates on"><Icon name="refresh" :size="11"/> auto</span>
              </div>
              <div class="t-meta mod-desc">{{ m.desc }}</div>
              <div class="mod-foot"><span class="t-key">v{{ m.version }} · by {{ m.author }}</span><a class="mod-link" :href="m.url" target="_blank" rel="noopener">Details <Icon name="arrowUpRight" :size="11"/></a></div>
            </div>
            <div class="mod-actions">
              <div class="mod-upd" v-if="m.update">
                <button class="mod-whatsnew" @click="preview=m" title="What’s new">v{{ m.update }} <Icon name="info" :size="12"/></button>
                <button class="btn btn--soft btn--sm" @click="updateMod(m)"><Icon name="upload" :size="13"/> Update</button>
              </div>
              <label class="mod-toggle"><span class="t-meta">{{ m.active ? 'On' : 'Off' }}</span><UiSwitch :modelValue="m.active" @update:modelValue="v=>{ m.active=v; window.__mdlr.store.toggleModule(m); }"/></label>
              <UiMenu :items="menuFor(m)"><button class="iconbtn"><Icon name="drag" :size="15"/></button></UiMenu>
            </div>
          </div>
          <div v-if="!modules.length" class="empty" style="padding:40px"><Icon name="layers"/><div>No modules match “{{ q }}”.</div></div>
        </div>
      </template>

      <!-- BROWSE -->
      <template v-else>
        <div class="list-toolbar" style="border:1px solid var(--line);border-radius:var(--r-md);background:var(--surface);margin-bottom:14px">
          <div class="topbar-search" style="max-width:320px;flex:0 1 320px;cursor:text"><Icon name="search" :size="15"/><input class="fb-lib-input" v-model="bq" placeholder="Search the module directory…" style="color:var(--ink)"></div>
          <span class="t-meta">{{ available.length }} module{{ available.length===1?'':'s' }}</span>
          <div class="grow"></div>
          <button class="btn btn--ghost btn--sm"><Icon name="upload" :size="14"/> Upload .zip</button>
        </div>
        <div class="mod-browse">
          <div class="mod-card" v-for="m in available" :key="m.id">
            <div class="mod-card-top">
              <span class="mod-ico"><Icon :name="m.icon" :size="20"/></span>
              <UiMenu :items="[{label:'View details',icon:'info'}]"><button class="iconbtn iconbtn--sm"><Icon name="drag" :size="15"/></button></UiMenu>
            </div>
            <strong class="mod-card-name">{{ m.name }}</strong>
            <p class="mod-card-desc">{{ m.desc }}</p>
            <div class="mod-card-foot">
              <span class="t-meta">by {{ m.author }} · {{ m.installs }} installs</span>
              <button class="btn btn--soft btn--sm"><Icon name="upload" :size="14"/> Install</button>
            </div>
          </div>
          <div v-if="!available.length" class="empty card" style="padding:40px;grid-column:1/-1"><Icon name="search"/><div>No modules match “{{ bq }}”.</div></div>
        </div>
      </template>

      <!-- update preview modal -->
      <UiModal v-if="preview" @close="preview=null">
        <div class="drawer-head">
          <span class="mod-ico is-on"><Icon :name="preview.icon" :size="18"/></span>
          <div style="flex:1"><strong style="font-size:15px">{{ preview.name }}</strong><div class="t-meta">Update available</div></div>
          <button class="iconbtn" @click="preview=null"><Icon name="x"/></button>
        </div>
        <div class="drawer-body">
          <div class="upd-ver" style="margin-bottom:14px"><span class="t-key">v{{ preview.version }}</span><Icon name="arrowRight" :size="13" class="muted"/><span class="t-key" style="color:var(--accent-deep)">v{{ preview.update }}</span></div>
          <label class="field-label">What’s new</label>
          <p style="font-size:13.5px;color:var(--ink-2);line-height:1.6;margin:0 0 16px">{{ preview.updateNotes }}</p>
          <a class="mode-help-link" :href="preview.url" target="_blank" rel="noopener">Full changelog &amp; details <Icon name="arrowUpRight" :size="13"/></a>
        </div>
        <div class="drawer-foot">
          <button class="btn btn--ghost" @click="preview=null">Close</button>
          <button class="btn btn--accent" @click="updateMod(preview); preview=null"><Icon name="upload" :size="15"/> Update now</button>
        </div>
      </UiModal>
    </div>`,
  };
  C.ScreenModules.methods.D = function () { return window.__mdlr.data; };
})();
