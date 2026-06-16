/* ============================================================
   NOTIFICATIONS + DOCS
   - Notifications: full list with read/unread, filters, mark-all.
   - Docs: in-app documentation hub (where the ? button lands),
     including the canonical Fixed-vs-Modular explanation.
   ============================================================ */
(function () {
  window.__mdlr = window.__mdlr || { comps: {} };
  const C = window.__mdlr.comps;
  const D = () => window.__mdlr.data;

  C.ScreenNotifications = {
    name: 'ScreenNotifications',
    inject: ['go'],
    data() { return { filter: 'all' }; },
    computed: {
      all() { return D().NOTIFICATIONS; },
      items() { return this.filter === 'unread' ? this.all.filter(n => !n.read) : this.all; },
      unread() { return this.all.filter(n => !n.read).length; },
    },
    methods: {
      open(n) { n.read = true; if (n.go) this.go(n.go[0], n.go[1]); },
      markAll() { this.all.forEach(n => { n.read = true; }); },
    },
    template: `
    <div class="page page--wide fade-up">
      <div class="page-head">
        <div><h1 class="t-title t-h1">Notifications</h1><p class="page-sub" style="margin-top:6px">{{ unread }} unread · {{ all.length }} total.</p></div>
        <button class="btn btn--ghost" @click="markAll" :disabled="!unread"><Icon name="check"/> Mark all read</button>
      </div>
      <div class="tabs" style="margin-bottom:16px">
        <button :class="{'is-on':filter==='all'}" @click="filter='all'">All <span class="tab-count">{{ all.length }}</span></button>
        <button :class="{'is-on':filter==='unread'}" @click="filter='unread'">Unread <span class="tab-count" v-if="unread">{{ unread }}</span></button>
      </div>
      <div class="card">
        <div class="notif-list">
          <button v-for="n in items" :key="n.id" class="notif-item notif-item--lg" :class="{'is-unread':!n.read}" @click="open(n)">
            <span class="notif-ico"><Icon :name="n.icon" :size="16"/></span>
            <span class="notif-main"><strong>{{ n.title }}</strong><span class="notif-body">{{ n.body }}</span></span>
            <span class="t-meta" style="flex:none">{{ n.when }}</span>
            <span class="notif-unread" v-if="!n.read"></span>
          </button>
          <div v-if="!items.length" class="empty" style="padding:48px"><Icon name="bell"/><div>You're all caught up.</div></div>
        </div>
      </div>
    </div>`,
  };

  const DOC_SECTIONS_UNUSED = null;
})();
