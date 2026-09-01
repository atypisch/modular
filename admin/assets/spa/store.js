/* ============================================================
   STORE — bridge between SPA screens and the JSON API.
   All CMS entity lists start empty in data.js and are filled here.
   ============================================================ */
(function () {
  const M = window.__mdlr = window.__mdlr || { comps: {} };
  const D = () => M.data;
  const api = () => M.api;

  const boundToFromLocation = (location) => {
    const out = new Set();
    (location || []).forEach(group => (group || []).forEach(r => { if (r.param === 'post_type' && r.value) out.add(r.value); }));
    return Array.from(out);
  };

  const fgFromApi = (r) => ({
    id: r.id, name: r.name, slug: r.slug, icon: r.icon || 'box', desc: r.description || '',
    active: !!Number(r.active), order: Number(r.order) || 0, position: r.position || 'normal',
    style: r.style || 'default', labelPlacement: r.label_placement || 'top',
    boundTo: r.bound_to || [], location: r.location || [], fields: r.fields || [],
    shared: Array.isArray(r.bound_to) && r.bound_to.length > 1,
  });
  const fgToApi = (g) => ({
    name: g.name, slug: g.slug || undefined, icon: g.icon || 'box', description: g.desc || '',
    location: g.location || [], position: g.position || 'normal', style: g.style || 'default',
    label_placement: g.labelPlacement || 'top', bound_to: g.boundTo && g.boundTo.length ? g.boundTo : boundToFromLocation(g.location),
    fields: g.fields || [], active: g.active ? 1 : 0, order: g.order || 0,
  });

  const cmpFromApi = (r) => ({
    id: r.id, name: r.name, slug: r.slug, icon: r.icon || 'layers', version: Number(r.current_version) || 1,
    usedIn: Number(r.used_in) || 0, updated: '', desc: r.description || '', fields: r.schema || [],
  });
  const cmpToApi = (c) => ({ name: c.name, slug: c.slug || undefined, icon: c.icon || 'layers', description: c.desc || '', schema: c.fields || [] });

  const toBool = (v, defaultVal = true) => {
    if (v === undefined || v === null) return defaultVal;
    if (typeof v === 'boolean') return v;
    return Number(v) !== 0;
  };

  const ptFromApi = (r) => ({
    id: r.slug, _dbId: r.id, name: r.name, slug: r.slug, icon: r.icon || 'file', desc: r.description || '',
    mode: r.mode || 'fixed', hierarchical: toBool(r.hierarchical, false), public: toBool(r.public, true),
    inMenu: toBool(r.show_in_menu, true), count: Number(r.post_count) || 0, fieldCount: Array.isArray(r.schema && r.schema.fields) ? r.schema.fields.length : (Array.isArray(r.schema) ? r.schema.length : 0),
    taxonomies: [], _schema: r.schema || null,
  });
  const ptToApi = (pt) => {
    const out = {
      name: pt.name, slug: pt.slug || undefined, description: pt.desc || '', icon: pt.icon || 'file',
      mode: pt.mode || 'fixed', hierarchical: pt.hierarchical ? 1 : 0,
      public: pt.public === false ? 0 : 1, show_in_menu: pt.inMenu === false ? 0 : 1,
    };
    if (pt.schema != null) out.schema = pt.schema;
    return out;
  };

  const postFromApiLight = (r) => ({
    id: r.id,
    title: r.title || '(untitled)',
    slug: r.slug || '',
    type: r.post_type_slug || '',
    status: r.status || 'draft',
    author: '',
    date: (r.updated_at || r.created_at || '').slice(0, 10),
  });

  const replaceInPlace = (arr, items) => { if (arr) arr.splice(0, arr.length, ...(items || [])); };

  const store = {
    ready: false,

    async loadFieldGroups() {
      try {
        const rows = await api().get('/field-groups');
        const mapped = (rows || []).map(fgFromApi);
        replaceInPlace(D().FIELD_GROUPS, mapped);
        return mapped;
      } catch (e) { console.warn('[store] loadFieldGroups failed', e && e.status); return null; }
    },
    async saveFieldGroup(g) {
      const payload = fgToApi(g);
      const isNew = !g.id || g.id === 'new' || String(g.id).startsWith('type_') || String(g.id).startsWith('fg_') || isNaN(Number(g.id));
      const saved = isNew ? await api().post('/field-groups', payload) : await api().put('/field-groups/' + g.id, payload);
      await this.loadFieldGroups();
      return saved ? fgFromApi(saved) : null;
    },
    async deleteFieldGroup(id) {
      if (!isNaN(Number(id))) await api().del('/field-groups/' + id);
      await this.loadFieldGroups();
    },
    async reorderFieldGroups(groups) {
      await Promise.all((groups || []).map((g, i) => (!isNaN(Number(g.id))) ? api().put('/field-groups/' + g.id, { order: i + 1 }) : Promise.resolve()));
    },

    async loadComponents() {
      try {
        const rows = await api().get('/components');
        const mapped = (rows || []).map(cmpFromApi);
        replaceInPlace(D().COMPONENTS, mapped);
        return mapped;
      } catch (e) { console.warn('[store] loadComponents failed', e && e.status); return null; }
    },
    async saveComponent(c) {
      const payload = cmpToApi(c);
      const isNew = !c.id || String(c.id).startsWith('cmp_') || isNaN(Number(c.id));
      const saved = isNew ? await api().post('/components', payload) : await api().put('/components/' + c.id, payload);
      await this.loadComponents();
      return saved ? cmpFromApi(saved) : null;
    },
    async deleteComponent(id) {
      if (!isNaN(Number(id))) await api().del('/components/' + id);
      await this.loadComponents();
    },

    async loadMedia() {
      try {
        const rows = await api().get('/media');
        const mapped = (rows || []).map(r => {
          const v = (r.variants && typeof r.variants === 'object') ? r.variants : {};
          const thumb = r.thumb_url || (v.thumb && v.thumb.path) || (v.small && v.small.path) || r.url || r.file_path || '';
          const preview = r.preview_url || (v.large && v.large.path) || (v.medium && v.medium.path) || thumb;
          const meta = (r.metadata && typeof r.metadata === 'object') ? r.metadata : {};
          return {
            id: r.id, name: r.filename, title: r.title || r.filename || '',
            w: Number(r.width) || null, h: Number(r.height) || null,
            kb: Math.round((Number(r.file_size) || 0) / 1024),
            alt: r.alt_text || '', caption: r.caption || '', folder: r.folder_path || '',
            hue: 30, uploaded: (r.created_at || '').slice(0, 10), usedIn: 0,
            seoTitle: '', seoDesc: '', url: r.url || r.file_path || '', thumb, preview, crop: meta.crop || null, mime: r.mime_type || '',
          };
        });
        replaceInPlace(D().MEDIA, mapped);
        const folders = [...new Set(mapped.map(m => m.folder).filter(Boolean))].sort();
        replaceInPlace(D().MEDIA_FOLDERS, folders);
        return mapped;
      } catch (e) { console.warn('[store] loadMedia failed', e && e.status); return null; }
    },
    async saveMediaCrop(id, crop) {
      if (id == null) throw new Error('saveMediaCrop: missing media id');
      const saved = await api().put('/media/' + id, { crop });
      await this.loadMedia();
      return saved || null;
    },
    async deleteMedia(id) {
      const nid = Number(id);
      if (isNaN(nid) || nid <= 0) throw new Error('Invalid media id');
      await api().post('/media/' + nid + '/delete', {});
      const list = D().MEDIA;
      const i = list.findIndex(m => Number(m.id) === nid);
      if (i >= 0) list.splice(i, 1);
    },

    async loadTaxonomies() {
      try {
        const rows = await api().get('/taxonomies');
        const mapped = await Promise.all((rows || []).map(async t => {
          let terms = [];
          try {
            const tr = await api().get('/taxonomies/' + t.id + '/terms');
            terms = (tr || []).map((x, i) => ({ id: x.id, name: x.name, slug: x.slug, count: 0, parent: x.parent_id, order: i }));
          } catch (e) {}
          return { id: t.slug || t.id, _dbId: t.id, name: t.name, slug: t.slug, hierarchical: !!Number(t.hierarchical), postTypes: [], terms };
        }));
        replaceInPlace(D().TAXONOMIES, mapped);
        return mapped;
      } catch (e) { console.warn('[store] loadTaxonomies failed', e && e.status); return null; }
    },
    async loadModules() {
      try {
        const rows = await api().get('/modules');
        const mapped = (rows || []).map(m => ({ id: m.id, name: m.name, slug: m.slug, author: m.author || '', version: m.version || '', icon: m.icon || 'box', active: !!Number(m.active), autoUpdate: false, desc: m.description || '' }));
        replaceInPlace(D().MODULES, mapped);
        return mapped;
      } catch (e) { console.warn('[store] loadModules failed', e && e.status); return null; }
    },
    toggleModule(m) { if (m && m.id != null && !isNaN(Number(m.id))) api().put('/modules/' + m.id, { active: m.active ? 1 : 0 }).catch(e => console.warn('[store] toggleModule failed', e && e.status)); },
    async loadForms() {
      try {
        const rows = await api().get('/forms');
        const mapped = (rows || []).map(f => ({ id: f.id, name: f.name, slug: f.slug, fields: Array.isArray(f.fields) ? f.fields.length : 0, submissions: Number(f.submission_count) || 0, updated: (f.updated_at || '').slice(0, 10), system: false, preview: '', desc: f.description || '' }));
        replaceInPlace(D().FORMS, mapped);
        return mapped;
      } catch (e) { console.warn('[store] loadForms failed', e && e.status); return null; }
    },
    async loadActions() {
      try {
        const rows = await api().get('/actions');
        const mapped = (rows || []).map(a => ({ id: a.id, name: a.name, trigger: a.trigger_type || '', enabled: !!Number(a.enabled), runs: Number(a.run_count) || 0, ops: (a.action_config && Array.isArray(a.action_config.ops)) ? a.action_config.ops : [{ type: a.action_type || 'action', icon: 'zap', label: a.action_type || 'Action' }] }));
        replaceInPlace(D().ACTIONS, mapped);
        return mapped;
      } catch (e) { console.warn('[store] loadActions failed', e && e.status); return null; }
    },
    async loadRoles() {
      try {
        const rows = await api().get('/roles');
        const builtin = ['admin', 'editor', 'author', 'user'];
        const mapped = (rows || []).map(r => {
          const isAdmin = (r.name || '').toLowerCase() === 'admin';
          return {
            id: r.name, _dbId: r.id, name: r.display_name || r.name, desc: r.description || '',
            users: Number(r.user_count) || 0, builtin: builtin.includes((r.name || '').toLowerCase()),
            caps: isAdmin ? ['*'] : (Array.isArray(r.capabilities) ? r.capabilities : []),
          };
        });
        replaceInPlace(D().ROLES, mapped);
        return mapped;
      } catch (e) { console.warn('[store] loadRoles failed', e && e.status); return null; }
    },
    async saveRoleCaps(role) {
      if (!role || role._dbId == null || (role.caps || []).includes('*')) return;
      try { await api().put('/roles/' + role._dbId + '/capabilities', { capabilities: role.caps || [] }); }
      catch (e) { console.warn('[store] saveRoleCaps failed', e && e.status); }
    },
    async createRole({ name, desc }) {
      const saved = await api().post('/roles', { display_name: name, description: desc || '' });
      await this.loadRoles();
      return saved;
    },
    async deleteRole(role) {
      if (!role || role._dbId == null) return;
      await api().del('/roles/' + role._dbId);
      await this.loadRoles();
    },
    async assignUserRole(userId, roleName) {
      try { await api().put('/users/' + userId, { role: roleName }); await this.loadRoles(); }
      catch (e) { console.warn('[store] assignUserRole failed', e && e.status); }
    },

    async loadPostTypes() {
      try {
        const rows = await api().get('/post-types');
        const list = Array.isArray(rows) ? rows : [];
        const mapped = list.map(ptFromApi);
        replaceInPlace(D().POST_TYPES, mapped);
        if (D().SPA_META) D().SPA_META.postTypesVersion = (D().SPA_META.postTypesVersion || 0) + 1;
        return mapped;
      } catch (e) { console.warn('[store] loadPostTypes failed', e && e.status); return null; }
    },
    async savePostType(pt) {
      const payload = ptToApi(pt);
      const saved = (pt._dbId == null) ? await api().post('/post-types', payload) : await api().put('/post-types/' + pt._dbId, payload);
      await this.loadPostTypes();
      return saved ? ptFromApi(saved) : null;
    },
    async deletePostType(pt) {
      if (!pt || pt._dbId == null) return;
      await api().del('/post-types/' + pt._dbId);
      await this.loadPostTypes();
    },
    async savePostTypeSchema(dbId, schema) {
      if (dbId == null) throw new Error('savePostTypeSchema: missing post type id');
      const saved = await api().put('/post-types/' + dbId, { schema });
      await this.loadPostTypes();
      return saved ? ptFromApi(saved) : null;
    },

    async loadPosts() {
      try {
        const rows = await api().get('/posts?fields=light&limit=500');
        const mapped = (rows || []).map(postFromApiLight);
        replaceInPlace(D().POSTS, mapped);
        return mapped;
      } catch (e) { console.warn('[store] loadPosts failed', e && e.status); return null; }
    },
    async deletePost(id) {
      if (id == null || isNaN(Number(id))) throw new Error('deletePost: invalid id');
      await api().del('/posts/' + id);
      await this.loadPosts();
    },
    async restorePost(id) {
      if (id == null || isNaN(Number(id))) throw new Error('restorePost: invalid id');
      const saved = await api().post('/posts/' + id + '/restore', {});
      await this.loadPosts();
      return saved;
    },
    async permanentlyDeletePost(id) {
      if (id == null || isNaN(Number(id))) throw new Error('permanentlyDeletePost: invalid id');
      await api().del('/posts/' + id + '?permanent=1');
      await this.loadPosts();
    },
    async updatePost(id, payload) {
      if (id == null || isNaN(Number(id))) throw new Error('updatePost: invalid id');
      const saved = await api().put('/posts/' + id, payload);
      await this.loadPosts();
      return saved;
    },

    async loadTheme() {
      try {
        const r = await api().get('/settings/theme');
        const t = r && r.tokens;
        if (t && M.theme && typeof t === 'object' && Object.keys(t).length) { Object.assign(M.theme.state, t); M.theme.apply(); }
      } catch (e) { console.warn('[store] loadTheme failed', e && e.status); }
    },
    saveTheme() {
      if (!M.theme) return;
      if (this._themeTimer) clearTimeout(this._themeTimer);
      this._themeTimer = setTimeout(() => { api().put('/settings/theme', { tokens: M.theme.state }).catch(e => console.warn('[store] saveTheme failed', e && e.status)); }, 600);
    },

    async loadProfile() {
      try {
        const me = await api().get('/me');
        if (!me || me.error) return null;
        const full = [me.first_name, me.last_name].filter(Boolean).join(' ').trim();
        const src = (full || me.username || me.email || '').trim();
        Object.assign(D().PROFILE, {
          username: me.username || '',
          name: full || me.username || '',
          email: me.email || '',
          role: me.role ? me.role.charAt(0).toUpperCase() + me.role.slice(1) : '',
          status: me.status ? me.status.charAt(0).toUpperCase() + me.status.slice(1) : '',
          memberSince: me.created_at ? String(me.created_at).slice(0, 10) : '',
          lastActive: me.last_login ? String(me.last_login).slice(0, 10) : '',
          bio: me.bio || '',
          initials: src ? src.split(/\s+/).map(s => s[0]).join('').slice(0, 2).toUpperCase() : '',
        });
        return D().PROFILE;
      } catch (e) { console.warn('[store] loadProfile failed', e && e.status); return null; }
    },

    async loadSystem() {
      try {
        const data = await api().get('/system/update');
        if (!data) return null;
        const cur = data.current || {};
        const lat = data.latest || {};
        Object.assign(D().SYSTEM, {
          version: cur.version || '',
          latest: (lat && lat.version) || cur.version || '',
          channel: data.mode || 'stable',
          php: cur.php || '',
          db: cur.db || '',
          lastChecked: new Date().toLocaleString(),
          update_available: !!data.update_available,
          changelog: lat && lat.version ? [{ v: lat.version, date: lat.published_at || '', notes: lat.body || lat.notes || '' }] : [],
          raw: data,
        });
        return D().SYSTEM;
      } catch (e) { console.warn('[store] loadSystem failed', e && e.status); return null; }
    },

    async loadStatsOverview() {
      try {
        const data = await api().get('/stats/overview');
        D().STATS_OVERVIEW = data;
        return data;
      } catch (e) { console.warn('[store] loadStatsOverview failed', e && e.status); return null; }
    },
    async loadAnalytics(range) {
      try {
        const data = await api().get('/stats/analytics' + (range ? '?range=' + range : ''));
        D().LIVE_ANALYTICS = data;
        return data;
      } catch (e) { console.warn('[store] loadAnalytics failed', e && e.status); return null; }
    },
    async loadReports(type, range) {
      try {
        const qs = '?type=' + (type || 'production') + '&range=' + (range || 30);
        const data = await api().get('/stats/reports' + qs);
        D().LIVE_REPORTS = data;
        return data;
      } catch (e) { console.warn('[store] loadReports failed', e && e.status); return null; }
    },

    async hydrate() {
      if (this._hydrateP) return this._hydrateP;
      this._hydrateP = this._hydrate().catch((e) => { this._hydrateP = null; throw e; });
      return this._hydrateP;
    },
    async _hydrate() {
      await Promise.all([
        this.loadPostTypes(),
        this.loadFieldGroups(),
        this.loadComponents(),
        this.loadMedia(),
        this.loadTaxonomies(),
        this.loadModules(),
        this.loadForms(),
        this.loadActions(),
        this.loadRoles(),
        this.loadPosts(),
        this.loadStatsOverview(),
        this.loadSystem(),
        this.loadProfile(),
      ]);
      if (D().SPA_META) D().SPA_META.ready = true;
      this.ready = true;
    },
  };

  M.store = store;
})();
