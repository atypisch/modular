/* ============================================================
   PROFILE — the current user's own account.
   View + inline edit. Account info, security, preferences.
   ============================================================ */
(function () {
  window.__mdlr = window.__mdlr || { comps: {} };
  const C = window.__mdlr.comps;
  const D = () => window.__mdlr.data;

  C.ScreenProfile = {
    name: 'ScreenProfile',
    data() {
      const p = D().PROFILE;
      return { editing: false, draft: null, p };
    },
    created() {
      const u = (window.__MDLR && window.__MDLR.user) || {};
      if (u.name) this.p.name = u.name;
      if (u.email) this.p.email = u.email;
      if (u.role) this.p.role = u.role.charAt(0).toUpperCase() + u.role.slice(1);
      // Pull the real account from the API so username/initials/bio/dates aren't mock.
      const api = window.__mdlr && window.__mdlr.api;
      if (!api) return;
      api.get('/me').then(me => {
        if (!me || me.error) return;
        const full = [me.first_name, me.last_name].filter(Boolean).join(' ').trim();
        if (me.username) this.p.username = me.username;
        if (full) this.p.name = full; else if (me.username) this.p.name = me.username;
        if (me.email) this.p.email = me.email;
        if (me.role) this.p.role = me.role.charAt(0).toUpperCase() + me.role.slice(1);
        if (me.status) this.p.status = me.status.charAt(0).toUpperCase() + me.status.slice(1);
        if ('bio' in me) this.p.bio = me.bio || '';
        if (me.created_at) this.p.memberSince = String(me.created_at).slice(0, 10);
        if (me.last_login) this.p.lastActive = String(me.last_login).slice(0, 10);
        const src = (full || me.username || me.email || '').trim();
        if (src) this.p.initials = src.split(/\s+/).map(s => s[0]).join('').slice(0, 2).toUpperCase();
      }).catch(() => {});
    },
    methods: {
      edit() { this.draft = JSON.parse(JSON.stringify(this.p)); this.editing = true; },
      save() { Object.assign(this.p, this.draft); this.editing = false; },
      cancel() { this.editing = false; },
      logout() { window.location.href = ((window.__MDLR && window.__MDLR.baseUrl) || '') + '/admin/auth/logout.php'; },
    },
    template: `
    <div class="page page--wide fade-up">
      <div class="page-head">
        <div>
          <div class="t-eyebrow" style="margin-bottom:8px">Your account</div>
          <h1 class="t-title t-h1">My profile</h1>
          <p class="page-sub" style="margin-top:6px">Your account details, security and editor preferences.</p>
        </div>
        <div class="flex gap-2" v-if="!editing">
          <button class="btn btn--ghost" @click="logout"><Icon name="logout" :size="15"/> Log out</button>
          <button class="btn btn--accent" @click="edit"><Icon name="pencil" :size="15"/> Edit profile</button>
        </div>
        <div class="flex gap-2" v-else>
          <button class="btn btn--ghost" @click="cancel">Cancel</button>
          <button class="btn" @click="save"><Icon name="check" :size="15"/> Save changes</button>
        </div>
      </div>

      <!-- identity banner -->
      <div class="card card-pad prof-banner">
        <span class="prof-avatar">{{ p.initials }}</span>
        <div class="prof-banner-main">
          <strong class="t-title t-h2" style="display:block">{{ p.name }}</strong>
          <div class="flex items-center gap-2" style="margin-top:6px">
            <span class="t-key">{{ p.email }}</span>
            <span class="badge badge--info">{{ p.role }}</span>
            <span class="badge badge--ok"><span class="badge--dot"></span>{{ p.status }}</span>
          </div>
        </div>
        <button class="btn btn--soft btn--sm" v-if="editing"><Icon name="image" :size="14"/> Change avatar</button>
        <div class="prof-banner-meta" v-else>
          <div><span class="t-meta">Member since</span><strong>{{ p.memberSince }}</strong></div>
          <div><span class="t-meta">Last active</span><strong>{{ p.lastActive }}</strong></div>
        </div>
      </div>

      <div class="prof-grid">
        <!-- Account information -->
        <section class="card card-pad">
          <h2 class="t-title t-h3" style="margin:0 0 16px">Account information</h2>
          <div class="field"><label class="field-label">Username</label>
            <input v-if="editing" class="inp" v-model="draft.username">
            <div v-else class="prof-readonly">{{ p.username }}</div>
          </div>
          <div class="field"><label class="field-label">Full name</label>
            <input v-if="editing" class="inp" v-model="draft.name">
            <div v-else class="prof-readonly">{{ p.name }}</div>
          </div>
          <div class="field"><label class="field-label">Email</label>
            <input v-if="editing" class="inp" v-model="draft.email">
            <div v-else class="prof-readonly">{{ p.email }}</div>
          </div>
          <div class="field" style="margin:0"><label class="field-label">Bio</label>
            <textarea v-if="editing" class="ta" v-model="draft.bio" style="min-height:72px"></textarea>
            <div v-else class="prof-readonly" style="line-height:1.55">{{ p.bio }}</div>
          </div>
        </section>

        <!-- Account details -->
        <section class="card card-pad">
          <h2 class="t-title t-h3" style="margin:0 0 16px">Account details</h2>
          <div class="prof-detail"><span class="field-label" style="margin:0">Role</span><span class="badge badge--info">{{ p.role }}</span></div>
          <div class="prof-detail"><span class="field-label" style="margin:0">Status</span><span class="badge badge--ok"><span class="badge--dot"></span>{{ p.status }}</span></div>
          <div class="prof-detail"><span class="field-label" style="margin:0">Member since</span><span>{{ p.memberSince }}</span></div>
          <div class="prof-detail">
            <span class="field-label" style="margin:0">Language</span>
            <UiSelect v-if="editing" class="sel" style="width:auto" v-model="draft.language"><option>English</option><option>Nederlands</option></UiSelect>
            <span v-else>{{ p.language }}</span>
          </div>
          <div class="prof-detail" style="border:0">
            <span class="field-label" style="margin:0">Timezone</span>
            <UiSelect v-if="editing" class="sel" style="width:auto" v-model="draft.timezone"><option>Europe/Amsterdam</option><option>UTC</option><option>Europe/London</option></UiSelect>
            <span v-else>{{ p.timezone }}</span>
          </div>
        </section>

        <!-- Security -->
        <section class="card card-pad">
          <h2 class="t-title t-h3" style="margin:0 0 16px">Security</h2>
          <div class="opt-row" style="border:0;padding-top:0"><div><strong>Password</strong><div class="field-hint">Last changed 2 months ago.</div></div><button class="btn btn--ghost btn--sm"><Icon name="lock" :size="14"/> Change</button></div>
          <div class="opt-row"><div><strong>Two-factor authentication</strong><div class="field-hint">Authenticator app.</div></div><UiSwitch :modelValue="editing ? draft.twoFactor : p.twoFactor" @update:modelValue="v=>{ (editing?draft:p).twoFactor=v }"/></div>
          <div class="opt-row" style="padding-bottom:0"><div><strong>Active sessions</strong><div class="field-hint">2 devices signed in.</div></div><button class="btn btn--ghost btn--sm">Manage</button></div>
        </section>

        <!-- Danger / sign out -->
        <section class="card card-pad">
          <h2 class="t-title t-h3" style="margin:0 0 16px">Session</h2>
          <p class="page-sub" style="margin-bottom:14px">Signed in as <strong style="color:var(--ink-2)">{{ p.username }}</strong> on this device.</p>
          <button class="btn btn--ghost" style="width:100%" onclick="window.open('auth.html','_blank')"><Icon name="logout" :size="15"/> Log out everywhere</button>
        </section>
      </div>
    </div>`,
  };
})();
