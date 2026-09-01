/* ============================================================
   MEDIA — library, a real drag-resize ImageCropper, MediaPicker.
   Crops feed every configured preset (md_image_url($id,'preset')).
   ============================================================ */
(function () {
  window.__mdlr = window.__mdlr || { comps: {} };
  const C = window.__mdlr.comps;
  const D = () => window.__mdlr.data;
  const clamp = (v, a, b) => Math.max(a, Math.min(b, v));
  const grad = (hue) => `linear-gradient(135deg, hsl(${hue} 30% 80%), hsl(${hue-8} 24% 58%))`;
  function mapMediaRow(r) {
    const v = (r.variants && typeof r.variants === 'object') ? r.variants : {};
    const thumb = r.thumb_url || (v.thumb && v.thumb.path) || (v.small && v.small.path) || r.url || r.file_path || '';
    return { id: r.id, name: r.filename, title: r.title || r.filename, w: Number(r.width) || null, h: Number(r.height) || null, kb: Math.round((Number(r.file_size) || 0) / 1024), alt: r.alt_text || '', caption: r.caption || '', folder: r.folder_path || '', hue: 30, uploaded: (r.created_at || '').slice(0, 10), usedIn: 0, url: r.url || r.file_path || '', thumb, preview: r.preview_url || r.url || thumb, mime: r.mime_type || '' };
  }
  async function uploadMediaFiles(fileList) {
    const files = Array.from(fileList || []);
    if (!files.length) return [];
    if (!window.__mdlr.api) { alert('Upload failed: API client is not ready.'); return []; }
    const added = [];
    for (const f of files) {
      try {
        const fd = new FormData(); fd.append('file', f);
        const r = await window.__mdlr.api.upload('/media/upload', fd);
        if (r && r.id) { const m = mapMediaRow(r); D().MEDIA.unshift(m); added.push(m); }
        else { alert('Upload failed: ' + ((r && r.error) || 'the server did not return a media id.')); }
      } catch (err) { console.error('[upload] failed', err); alert('Upload failed: ' + (err && err.data && err.data.error || err.message)); }
    }
    if (added.length && window.__mdlr.store && window.__mdlr.store.loadMedia) {
      try { await window.__mdlr.store.loadMedia(); } catch (e) {}
    }
    return added;
  }

  /* ---- real crop tool ---- */
  C.ImageCropper = {
    name: 'ImageCropper',
    props: { img: Object },
    emits: ['close','save'],
    data() {
      const saved = this.img && this.img.crop;
      return {
        crop: (saved && saved.w && saved.h)
          ? { x: Number(saved.x) || 0, y: Number(saved.y) || 0, w: Number(saved.w), h: Number(saved.h) }
          : { x: 12, y: 14, w: 62, h: 60 },
        activeName: (saved && saved.preset) || 'Free',
        activeRatio: (saved && Array.isArray(saved.ratio)) ? saved.ratio : null,   // [w,h] derived from the chosen preset, or null = free
        drag: null,
        presets: D().IMAGE_PRESETS.map(p => ({ ...p, on: p.on })),
      };
    },
    computed: { grad() { return this.img && this.img.url ? ('url("' + this.img.url + '")') : grad(this.img.hue); }, ratio() { return this.activeRatio; } },
    methods: {
      stageRect() { return this.$refs.stage.getBoundingClientRect(); },
      down(mode, e) {
        e.preventDefault(); e.stopPropagation();
        this.drag = { mode, sx: e.clientX, sy: e.clientY, r: this.stageRect(), orig: { ...this.crop } };
        window.addEventListener('pointermove', this.move);
        window.addEventListener('pointerup', this.up);
      },
      move(e) {
        if (!this.drag) return;
        const { mode, sx, sy, r, orig } = this.drag;
        const dx = (e.clientX - sx) / r.width * 100;
        const dy = (e.clientY - sy) / r.height * 100;
        let c = { ...orig };
        if (mode === 'move') {
          c.x = clamp(orig.x + dx, 0, 100 - orig.w);
          c.y = clamp(orig.y + dy, 0, 100 - orig.h);
        } else {
          if (mode.includes('e')) c.w = clamp(orig.w + dx, 8, 100 - orig.x);
          if (mode.includes('s')) c.h = clamp(orig.h + dy, 8, 100 - orig.y);
          if (mode.includes('w')) { const nx = clamp(orig.x + dx, 0, orig.x + orig.w - 8); c.w = orig.w + (orig.x - nx); c.x = nx; }
          if (mode.includes('n')) { const ny = clamp(orig.y + dy, 0, orig.y + orig.h - 8); c.h = orig.h + (orig.y - ny); c.y = ny; }
          if (this.ratio) {
            const [rw, rh] = this.ratio;
            const wpx = c.w / 100 * r.width;
            let nh = (wpx * rh / rw) / r.height * 100;
            if (mode.includes('n')) { c.y = orig.y + orig.h - nh; if (c.y < 0) { c.y = 0; } }
            c.h = nh;
            if (c.y + c.h > 100) c.h = 100 - c.y;
          }
        }
        this.crop = c;
      },
      up() { this.drag = null; window.removeEventListener('pointermove', this.move); window.removeEventListener('pointerup', this.up); },
      pick(name, wh) {
        this.activeName = name; this.activeRatio = wh;
        if (wh) { const rect = this.stageRect(); const wpx = this.crop.w / 100 * rect.width; let nh = (wpx * wh[1] / wh[0]) / rect.height * 100; if (this.crop.y + nh > 100) nh = 100 - this.crop.y; this.crop.h = nh; }
      },
      openSettings() { if (window.__mdlr.go) window.__mdlr.go('settings', { tab: 'media' }); this.$emit('close'); },
      prevStyle(p) {
        const cx = this.crop.x + this.crop.w / 2, cy = this.crop.y + this.crop.h / 2;
        return { aspectRatio: p.w + '/' + p.h, backgroundImage: this.grad, backgroundSize: 'cover', backgroundPosition: cx + '% ' + cy + '%' };
      },
      reset() { this.crop = { x: 8, y: 8, w: 84, h: 84 }; this.pick('Free', null); },
      fmt(n) { return Math.round(n); },
    },
    template: `
    <UiModal lg @close="$emit('close')">
      <div class="drawer-head">
        <Icon name="crop" :size="18"/>
        <div style="flex:1"><strong style="font-size:15px">Crop {{ img.name }}</strong><div class="t-key">{{ img.w }}×{{ img.h }} px</div></div>
        <button class="iconbtn" @click="$emit('close')"><Icon name="x"/></button>
      </div>
      <div class="drawer-body" style="padding:0">
        <div class="crop2-wrap">
          <div class="crop2-main">
            <div class="crop2-ratios">
              <span class="t-meta" style="margin-right:4px">Cropping for</span>
              <strong style="font-size:13px">{{ activeName }}</strong>
            </div>
            <div class="crop2-stage" ref="stage" :style="{backgroundImage: grad}">
              <div class="crop2-box" :style="{left:crop.x+'%', top:crop.y+'%', width:crop.w+'%', height:crop.h+'%'}" @pointerdown="down('move',$event)">
                <span class="crop2-thirds"></span>
                <span class="crop2-h crop2-h--nw" @pointerdown="down('nw',$event)"></span>
                <span class="crop2-h crop2-h--ne" @pointerdown="down('ne',$event)"></span>
                <span class="crop2-h crop2-h--sw" @pointerdown="down('sw',$event)"></span>
                <span class="crop2-h crop2-h--se" @pointerdown="down('se',$event)"></span>
              </div>
            </div>
            <div class="crop2-foot">
              <span class="t-key">x {{ fmt(crop.x/100*img.w) }} · y {{ fmt(crop.y/100*img.h) }} · {{ fmt(crop.w/100*img.w) }}×{{ fmt(crop.h/100*img.h) }}</span>
              <span class="grow"></span>
              <button class="btn btn--text btn--sm" @click="reset"><Icon name="refresh" :size="14"/> Reset</button>
            </div>
          </div>
          <div class="crop2-side">
            <div class="crop2-side-head"><strong>Image presets</strong><span class="t-meta">{{ presets.filter(p=>p.on).length }} on</span></div>
            <p class="field-hint" style="margin:0 14px 10px">Click a preset to crop for its ratio; the toggle decides which sizes are generated. Presets come from <a class="mode-help-link" style="display:inline-flex" @click.prevent="openSettings">Settings → Media</a>.</p>
            <button type="button" class="preset-row preset-row--free" :class="{'is-active':activeName==='Free'}" @click="pick('Free',null)">
              <div class="preset-prev preset-prev--free"><Icon name="crop" :size="16"/></div>
              <div class="preset-meta"><strong>Free</strong><span class="t-key">any ratio</span></div>
            </button>
            <div class="preset-row" v-for="p in presets" :key="p.name" :class="{'is-off':!p.on, 'is-active':activeName===p.name}" @click="pick(p.name,[p.w,p.h])">
              <div class="preset-prev" :style="prevStyle(p)"></div>
              <div class="preset-meta"><strong>{{ p.name }}</strong><span class="t-key">{{ p.w }}×{{ p.h }}</span></div>
              <span @click.stop><UiSwitch v-model="p.on"/></span>
            </div>
          </div>
        </div>
      </div>
      <div class="drawer-foot">
        <div class="flex items-center gap-2" style="margin-right:auto;color:var(--ink-3);font-size:12.5px"><Icon name="refresh" :size="14"/> {{ presets.filter(p=>p.on).length }} formats will regenerate</div>
        <button class="btn btn--ghost" @click="$emit('close')">Cancel</button>
        <button class="btn" @click="$emit('save', Object.assign({}, crop, { preset: activeName, ratio: activeRatio })); $emit('close')"><Icon name="check" :size="15"/> Save crops</button>
      </div>
    </UiModal>`,
  };

  /* ---- media picker (for Replace) ---- */
  C.MediaPicker = {
    name: 'MediaPicker',
    props: { multiple: Boolean, accept: { type: String, default: '' } },
    emits: ['close','pick'],
    data() { return { q:'', picked: [], uploading: false }; },
    computed: { items() { const q=this.q.trim().toLowerCase(); return D().MEDIA.filter(m=>!q||(m.name||'').toLowerCase().includes(q)); } },
    methods: {
      grad,
      isPicked(m) { return this.picked.some(x => x.id === m.id); },
      choose(m) {
        if (this.multiple) { const i = this.picked.findIndex(x => x.id === m.id); if (i >= 0) this.picked.splice(i, 1); else this.picked.push(m); }
        else { this.$emit('pick', m); this.$emit('close'); }
      },
      confirm() { this.$emit('pick', this.picked.slice()); this.$emit('close'); },
      onModalClose() { if (this.uploading) return; this.$emit('close'); },
      async onFiles(e) {
        const files = Array.from((e.target && e.target.files) || (e.dataTransfer && e.dataTransfer.files) || []);
        if (!files.length) return;
        this.uploading = true;
        const added = await uploadMediaFiles(files);
        if (this.multiple) added.forEach(m => this.picked.push(m));
        this.uploading = false;
        if (e.target) e.target.value = '';
        if (!this.multiple && added.length) { this.$emit('pick', added[added.length - 1]); this.$emit('close'); }
      },
    },
    template: `
    <UiModal lg @close="onModalClose">
      <div class="drawer-head"><Icon name="image" :size="18"/><strong style="font-size:15px;flex:1">{{ multiple ? 'Choose images' : 'Choose an image' }}</strong><button class="iconbtn" @click="onModalClose"><Icon name="x"/></button></div>
      <div style="padding:14px 18px 0;display:flex;gap:10px;align-items:center">
        <div class="topbar-search" style="flex:1;max-width:none"><Icon name="search" :size="15"/><input class="fb-lib-input" v-model="q" placeholder="Search media…" style="color:var(--ink)"></div>
        <label class="btn btn--ghost btn--sm" style="cursor:pointer;flex:none;position:relative;overflow:hidden"><Icon name="upload" :size="14"/> {{ uploading ? 'Uploading…' : 'Upload' }}<input type="file" multiple :accept="accept==='image'?'image/*':'image/jpeg,image/png,image/gif,image/webp,image/*,application/pdf,audio/*'" style="position:absolute;inset:0;opacity:0;cursor:pointer;font-size:64px" @change="onFiles"></label>
      </div>
      <div class="drawer-body" style="padding-top:14px" @dragover.prevent @drop.prevent="onFiles">
        <div class="mp-droptip" v-if="!items.length && !uploading"><Icon name="upload" :size="22"/><div>Drag files here or use Upload</div></div>
        <div class="media-grid" style="padding:0">
          <button v-for="m in items" :key="m.id" class="media-card" :class="{'is-picked':isPicked(m)}" @click="choose(m)">
            <span class="media-thumb" :style="{background: (m.thumb||m.url) ? ('center/cover no-repeat url('+(m.thumb||m.url)+')') : grad(m.hue)}">
              <span v-if="multiple && isPicked(m)" class="media-check"><Icon name="check" :size="14"/></span>
            </span>
            <span class="media-name">{{ m.name }}</span>
            <span class="t-meta mono">{{ m.w }}×{{ m.h }}</span>
          </button>
        </div>
      </div>
      <div v-if="multiple" class="drawer-foot"><span class="t-meta" style="margin-right:auto">{{ picked.length }} selected</span><button class="btn btn--ghost" @click="$emit('close')">Cancel</button><button class="btn" :disabled="!picked.length" @click="confirm"><Icon name="check" :size="15"/> Add selected</button></div>
    </UiModal>`,
  };

  C.ScreenMedia = {
    name: 'ScreenMedia',
    inject: ['go', 'route'],
    data() { return { view:'grid', q:'', folder:'all', detail:null, cropping:null, newFolderOpen:false, newFolderName:'', renameOpen:false, rename:null, uploading:false, sel:[], deleting:false }; },
    computed: {
      items() { const q=this.q.trim().toLowerCase(); return D().MEDIA.filter(m => (this.folder==='all'||m.folder===this.folder) && (!q||(m.name||'').toLowerCase().includes(q)||(m.title||'').toLowerCase().includes(q))); },
      folders() { return ['all', ...D().MEDIA_FOLDERS]; },
      presetCount() { return D().IMAGE_PRESETS.filter(p=>p.on).length; },
      renamePattern() { return D().RENAME_PATTERN; },
      allOn() { return this.items.length > 0 && this.items.every(m => this.sel.includes(m.id)); },
    },
    mounted() {
      if (this.route && this.route.params && this.route.params.upload) this.$nextTick(() => this.openFilePicker());
    },
    methods: {
      D(){ return window.__mdlr.data; }, grad,
      src(m){ return (m && (m.thumb || m.preview || m.url)) || ''; },
      isImage(m){ const mime = (m && m.mime) || ''; if (mime.indexOf('image/') === 0) return true; return /\.(jpe?g|png|gif|webp|svg|avif)$/i.test((m && m.name) || ''); },
      isSel(id){ return this.sel.indexOf(id) >= 0; },
      toggle(id){ const i = this.sel.indexOf(id); if (i >= 0) this.sel.splice(i, 1); else this.sel.push(id); },
      toggleAll(){ this.sel = this.allOn ? [] : this.items.map(m => m.id); },
      open(m){ this.detail = m; },
      viewFile(m){ const u = (m && m.url) || this.src(m); if (u) window.open(u, '_blank', 'noopener'); },
      async removeOne(m) {
        if (!m || m.id == null) return;
        if (!confirm('Delete “' + (m.name || 'this file') + '”? This cannot be undone.')) return;
        await this.removeIds([m.id]);
        if (this.detail && this.detail.id === m.id) this.detail = null;
        if (this.cropping && this.cropping.id === m.id) this.cropping = null;
      },
      async bulkDelete() {
        if (!this.sel.length) return;
        const n = this.sel.length;
        if (!confirm('Delete ' + n + ' file' + (n === 1 ? '' : 's') + '? This cannot be undone.')) return;
        await this.removeIds(this.sel.slice());
      },
      async removeIds(ids) {
        const store = window.__mdlr.store;
        if (!store || !store.deleteMedia) {
          alert('Delete is not available. Reload the admin and try again.');
          return;
        }
        this.deleting = true;
        const failed = [];
        for (const id of ids) {
          try { await store.deleteMedia(id); }
          catch (e) {
            console.error('[media] delete failed', id, e);
            failed.push(id);
          }
        }
        this.sel = this.sel.filter(id => failed.indexOf(id) >= 0);
        if (this.detail && ids.indexOf(this.detail.id) >= 0 && failed.indexOf(this.detail.id) < 0) this.detail = null;
        this.deleting = false;
        if (failed.length) {
          alert('Could not delete ' + failed.length + ' file' + (failed.length === 1 ? '' : 's') + '.');
        }
      },
      openFilePicker() { const el = this.$refs.fileInput; if (el) el.click(); },
      async onFiles(e) {
        const files = Array.from((e.target && e.target.files) || (e.dataTransfer && e.dataTransfer.files) || []);
        if (!files.length) return;
        this.uploading = true;
        const added = await uploadMediaFiles(files);
        this.uploading = false;
        if (e.target) e.target.value = '';
        if (added.length) this.detail = added[0];
      },
      slug(s){ return (s||'').toLowerCase().replace(/[^a-z0-9]+/g,'-').replace(/^-|-$/g,''); },
      ext(name){ const i=name.lastIndexOf('.'); return i>=0?name.slice(i):''; },
      autoName(m, n){ const p = this.renamePattern.pattern; return p.replace('{folder}', m.folder).replace('{title}', this.slug(m.title||'image')).replace('{n}', n||1) + this.ext(m.name); },
      applyRename(){
        const list = D().MEDIA.filter(m => this.folder==='all' || m.folder===this.folder);
        const counts = {};
        list.forEach(m => { const base = (this.renamePattern.pattern.replace('{folder}',m.folder).replace('{title}',this.slug(m.title||'image')).replace('{n}','')); counts[base]=(counts[base]||0)+1; const n=counts[base]; m.name=this.autoName(m,n); });
        this.renameOpen=false;
      },
      createFolder(){ const n=this.slug(this.newFolderName); if(n && !D().MEDIA_FOLDERS.includes(n)){ D().MEDIA_FOLDERS.push(n); this.folder=n; } this.newFolderOpen=false; this.newFolderName=''; },
      onCrop(crop){ const m = this.cropping; if (!m || m.id == null) return; window.__mdlr.store.saveMediaCrop(m.id, crop).catch(e => { console.error('[media] save crop failed', e); alert('Saving the crop failed. Check the console.'); }); },
    },
    template: `
    <div class="page page--wide fade-up">
      <div class="page-head">
        <div><h1 class="t-title t-h1">Media</h1><p class="page-sub" style="margin-top:6px">{{ D().MEDIA.length }} files. Crop once — every configured preset re-crops automatically.</p></div>
        <div class="flex gap-2">
          <button class="btn btn--ghost" @click="newFolderOpen=true"><Icon name="folder"/> New folder</button>
          <label class="btn btn--accent" style="cursor:pointer;position:relative;overflow:hidden"><Icon name="upload"/> {{ uploading ? 'Uploading…' : 'Upload' }}<input ref="fileInput" type="file" multiple accept="image/jpeg,image/png,image/gif,image/webp,image/*,application/pdf,audio/*" style="position:absolute;inset:0;opacity:0;cursor:pointer;font-size:64px" @change="onFiles"></label>
        </div>
      </div>
      <div class="card">
        <div class="list-toolbar">
          <div class="topbar-search media-search"><Icon name="search" :size="15"/><input class="fb-lib-input" v-model="q" placeholder="Search media…" style="color:var(--ink)"></div>
          <UiSelect class="media-foldersel" v-model="folder"><option v-for="f in folders" :key="f" :value="f">{{ f==='all'?'All folders':f }}</option></UiSelect>
          <button class="btn btn--ghost btn--sm" @click="renameOpen=true" title="Auto-rename files in this view"><Icon name="refresh" :size="14"/> Auto-rename</button>
          <template v-if="sel.length">
            <span class="badge badge--info">{{ sel.length }} selected</span>
            <button class="btn btn--danger btn--sm" :disabled="deleting" @click="bulkDelete"><Icon name="trash" :size="14"/> {{ deleting ? 'Deleting…' : ('Delete ' + sel.length) }}</button>
          </template>
          <div class="grow"></div>
          <div class="seg"><button :class="{'is-on':view==='grid'}" @click="view='grid'"><Icon name="grid" :size="14"/></button><button :class="{'is-on':view==='list'}" @click="view='list'"><Icon name="list" :size="14"/></button></div>
        </div>
        <div v-if="view==='grid'" class="media-grid">
          <div v-for="m in items" :key="m.id" class="media-card media-card--lib" :class="{'is-picked':isSel(m.id)}" @click="open(m)">
            <span class="media-thumb" :style="{background: src(m) ? ('center/contain no-repeat url('+src(m)+')') : grad(m.hue), backgroundColor:'var(--paper-2)'}">
              <span class="media-lib-check" @click.stop><UiChk :modelValue="isSel(m.id)" @update:modelValue="toggle(m.id)"/></span>
              <span class="media-hover">
                <button type="button" class="btn btn--soft btn--xs" @click.stop="open(m)"><Icon name="pencil" :size="13"/> Edit</button>
                <button type="button" class="btn btn--soft btn--xs" @click.stop="cropping=m"><Icon name="crop" :size="13"/> Crop</button>
                <button type="button" class="btn btn--soft btn--xs" @click.stop="removeOne(m)"><Icon name="trash" :size="13"/> Delete</button>
              </span>
              <span class="media-badge mono">{{ presetCount }}</span>
            </span>
            <span class="media-name">{{ m.name }}</span>
            <span class="t-meta mono">{{ m.w }}×{{ m.h }} · {{ (m.kb/1024).toFixed(1) }} MB</span>
          </div>
        </div>
        <table v-else class="tbl tbl--rows">
          <thead><tr>
            <th style="width:36px"><UiChk :modelValue="allOn" @update:modelValue="toggleAll"/></th>
            <th>File</th><th style="width:130px">Folder</th><th style="width:130px">Dimensions</th><th style="width:90px">Size</th>
          </tr></thead>
          <tbody>
            <tr v-for="m in items" :key="m.id" :class="{'row-sel': isSel(m.id)}">
              <td @click.stop><UiChk :modelValue="isSel(m.id)" @update:modelValue="toggle(m.id)"/></td>
              <td>
                <div class="flex items-center gap-3">
                  <span class="media-thumb-sm" @click="open(m)">
                    <img v-if="isImage(m) && src(m)" :src="src(m)" :alt="m.name || ''">
                    <Icon v-else class="media-thumb-ico" name="file" :size="22"/>
                  </span>
                  <div>
                    <a class="ce-title" @click="open(m)">{{ m.name }}</a>
                    <div class="t-meta">{{ m.title }}</div>
                    <div class="row-actions">
                      <button @click="open(m)">Edit</button>
                      <button @click="cropping=m"><Icon name="crop" :size="12"/> Crop</button>
                      <button @click="viewFile(m)"><Icon name="eye" :size="12"/> View</button>
                      <button type="button" class="is-danger" @click="removeOne(m)">Delete</button>
                    </div>
                  </div>
                </div>
              </td>
              <td class="muted">{{ m.folder || '—' }}</td>
              <td class="mono muted">{{ m.w && m.h ? (m.w + '×' + m.h) : '—' }}</td>
              <td class="mono muted">{{ m.kb ? ((m.kb/1024).toFixed(1) + ' MB') : '—' }}</td>
            </tr>
            <tr v-if="!items.length"><td colspan="5"><div class="empty" style="padding:40px"><Icon name="image"/><div>No media in this view.</div></div></td></tr>
          </tbody>
        </table>
      </div>

      <MediaDetail v-if="detail" :m="detail" @close="detail=null" @crop="cropping=detail" @delete="removeOne"/>
      <ImageCropper v-if="cropping" :img="cropping" @save="onCrop" @close="cropping=null"/>

      <!-- new folder -->
      <UiModal v-if="newFolderOpen" @close="newFolderOpen=false">
        <div class="drawer-head"><Icon name="folder" :size="18"/><strong style="font-size:15px;flex:1">New folder</strong><button class="iconbtn" @click="newFolderOpen=false"><Icon name="x"/></button></div>
        <div class="drawer-body"><div class="field" style="margin:0"><label class="field-label">Folder name</label><input class="inp" v-model="newFolderName" placeholder="e.g. case-studies" @keydown.enter="createFolder"></div></div>
        <div class="drawer-foot"><button class="btn btn--ghost" @click="newFolderOpen=false">Cancel</button><button class="btn btn--accent" @click="createFolder" :disabled="!newFolderName.trim()"><Icon name="plus" :size="15"/> Create folder</button></div>
      </UiModal>

      <!-- auto-rename -->
      <UiModal v-if="renameOpen" @close="renameOpen=false">
        <div class="drawer-head"><Icon name="refresh" :size="18"/><strong style="font-size:15px;flex:1">Auto-rename files</strong><button class="iconbtn" @click="renameOpen=false"><Icon name="x"/></button></div>
        <div class="drawer-body">
          <p class="page-sub" style="margin-bottom:14px">Rename {{ items.length }} file{{ items.length===1?'':'s' }} in <strong style="color:var(--ink-2)">{{ folder==='all'?'all folders':folder }}</strong> using a consistent pattern.</p>
          <div class="field"><label class="field-label">Pattern</label><input class="inp mono" v-model="renamePattern.pattern"></div>
          <div class="field-hint" style="margin:-8px 0 14px">Tokens: <span class="t-key">{folder}</span> <span class="t-key">{title}</span> <span class="t-key">{n}</span></div>
          <label class="field-label">Preview</label>
          <div class="rename-prev">
            <div v-for="(m,i) in items.slice(0,4)" :key="m.id" class="rename-prev-row"><span class="mono muted">{{ m.name }}</span><Icon name="arrowRight" :size="13" class="muted"/><span class="t-key">{{ autoName(m, i+1) }}</span></div>
            <div class="t-meta" v-if="items.length>4">+ {{ items.length-4 }} more</div>
          </div>
        </div>
        <div class="drawer-foot"><button class="btn btn--ghost" @click="renameOpen=false">Cancel</button><button class="btn btn--accent" @click="applyRename"><Icon name="check" :size="15"/> Rename {{ items.length }} files</button></div>
      </UiModal>
    </div>`,
  };

  /* ---- media item detail page (modal-style full editor) ---- */
  C.MediaDetail = {
    name: 'MediaDetail',
    props: { m: Object },
    emits: ['close','crop','delete'],
    methods: { grad, fileType(n){ const e=(n.split('.').pop()||'').toUpperCase(); return e; } },
    template: `
    <UiModal lg @close="$emit('close')">
      <div class="drawer-head">
        <Icon name="image" :size="18"/>
        <div style="flex:1;min-width:0"><strong style="font-size:15px">{{ m.name }}</strong><div class="t-key">{{ m.w }}×{{ m.h }} · {{ (m.kb/1024).toFixed(1) }} MB · {{ fileType(m.name) }}</div></div>
        <button class="iconbtn" @click="$emit('close')"><Icon name="x"/></button>
      </div>
      <div class="drawer-body" style="padding:0">
        <div class="md-wrap">
          <div class="md-preview">
            <div class="md-image" :style="{background: (m.preview||m.url) ? ('center/contain no-repeat url('+(m.preview||m.url)+')') : grad(m.hue), backgroundColor:'var(--paper-2)'}"></div>
            <div class="md-preview-actions">
              <button class="btn btn--soft btn--sm" @click="$emit('crop')"><Icon name="crop" :size="14"/> Crop</button>
              <button class="btn btn--soft btn--sm" @click="m.url && window.open(m.url, '_blank', 'noopener')"><Icon name="external" :size="14"/> Open</button>
            </div>
            <div class="md-usage"><Icon name="git" :size="14"/> Used in {{ m.usedIn }} item{{ m.usedIn===1?'':'s' }}</div>
          </div>
          <div class="md-fields">
            <div class="md-sec">
              <div class="md-sec-h">Details</div>
              <div class="field"><label class="field-label">Title</label><input class="inp" v-model="m.title"></div>
              <div class="field"><label class="field-label">File name</label><input class="inp mono" v-model="m.name"></div>
              <div class="field"><label class="field-label">Alt text</label><input class="inp" v-model="m.alt" placeholder="Describe the image for screen readers"></div>
              <div class="field" style="margin:0"><label class="field-label">Caption</label><textarea class="ta" v-model="m.caption" style="min-height:60px" placeholder="Optional caption"></textarea></div>
            </div>
            <div class="md-sec">
              <div class="md-sec-h">Organisation</div>
              <div class="cfg-2">
                <div class="field" style="margin:0"><label class="field-label">Folder</label><UiSelect v-model="m.folder"><option v-for="f in D().MEDIA_FOLDERS" :key="f">{{ f }}</option></UiSelect></div>
                <div class="field" style="margin:0"><label class="field-label">Uploaded</label><input class="inp" :value="m.uploaded" readonly></div>
              </div>
            </div>
            <div class="md-sec">
              <div class="md-sec-h"><Icon name="globe" :size="14"/> SEO</div>
              <div class="field"><label class="field-label">SEO title</label><input class="inp" v-model="m.seoTitle" :placeholder="m.title"></div>
              <div class="field" style="margin:0"><label class="field-label">SEO description</label><textarea class="ta" v-model="m.seoDesc" style="min-height:60px" placeholder="How this image is described to search engines"></textarea></div>
            </div>
          </div>
        </div>
      </div>
      <div class="drawer-foot">
        <button type="button" class="btn btn--danger" style="margin-right:auto" @click="$emit('delete', m)"><Icon name="trash" :size="15"/> Delete</button>
        <button class="btn btn--ghost" @click="$emit('close')">Cancel</button>
        <button class="btn" @click="$emit('close')"><Icon name="check" :size="15"/> Save</button>
      </div>
    </UiModal>`,
  };
  C.MediaDetail.methods.D = function(){ return window.__mdlr.data; };
})();
