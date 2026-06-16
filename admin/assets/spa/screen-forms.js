/* ============================================================
   FORMS — list + builder (palette → canvas → settings).
   ============================================================ */
(function () {
  window.__mdlr = window.__mdlr || { comps: {} };
  const C = window.__mdlr.comps;
  const D = () => window.__mdlr.data;

  const PALETTE = [
    { type:'text', label:'Text', icon:'type' },
    { type:'email', label:'Email', icon:'mail' },
    { type:'textarea', label:'Textarea', icon:'align' },
    { type:'select', label:'Select', icon:'list' },
    { type:'checkbox', label:'Checkbox', icon:'check' },
    { type:'radio', label:'Radio', icon:'radio' },
    { type:'number', label:'Number', icon:'hash' },
    { type:'date', label:'Date', icon:'calendar' },
    { type:'file', label:'File upload', icon:'upload' },
    { type:'heading', label:'Heading', icon:'text' },
  ];

  C.ScreenForms = {
    name: 'ScreenForms',
    data() {
      return {
        editing: null, sel: null, drag:null, dropAt:null, view:'build', entryOpen:null, nameToken:'{{name}}',
        fields: [],
        settings: { name:'Contact', slug:'contactform', submit:'Send message', success:"Thanks — we'll reply within a day.", notify:true, spam:true, store:true,
          confirm:false, confirmFromName:'Atypisch', confirmReplyTo:'hello@atypisch.nl', confirmSubject:'We received your message', confirmBody:"Hi {{name}},\n\nThanks for reaching out — we've received your message and will reply within one working day.\n\nWarm regards,\nThe Atypisch team" },
      };
    },
    computed: {
      forms() { return D().FORMS; }, palette() { return PALETTE; },
      submissions() { return D().SUBMISSIONS; },
      unread() { return this.submissions.filter(s => !s.read).length; },
    },
    methods: {
      preview(frm) { window.open((frm && frm.preview) || 'auth.html', '_blank'); },
      open(frm, view) {
        if (frm && frm.system) { this.preview(frm); return; }
        this.editing = frm;
        this.view = view || 'build';
        this.settings.name = frm.name; this.settings.slug = frm.slug;
        this.fields = [
          { id:D().uid(), type:'text', label:'Your name', required:true, ph:'Jane Doe', w:'half' },
          { id:D().uid(), type:'email', label:'Email', required:true, ph:'you@company.com', w:'half' },
          { id:D().uid(), type:'select', label:'Subject', required:false, ph:'', w:'full' },
          { id:D().uid(), type:'textarea', label:'Message', required:true, ph:'How can we help?', w:'full' },
        ];
      },
      meta(t){ return PALETTE.find(p=>p.type===t) || {icon:'type',label:t}; },
      add(t){ const m=this.meta(t); this.fields.push({ id:D().uid(), type:t, label:m.label, required:false, ph:'', w:'full' }); },
      del(i){ this.fields.splice(i,1); if(this.sel&&this.fields.indexOf(this.sel)<0) this.sel=null; },
      libStart(t,e){ this.drag={kind:'lib',type:t.type}; e.dataTransfer.effectAllowed='copy'; },
      rowStart(i,e){ this.drag={kind:'move',i}; e.dataTransfer.effectAllowed='move'; },
      over(i,e){ e.preventDefault(); const r=e.currentTarget.getBoundingClientRect(); this.dropAt=(e.clientY-r.top)>r.height/2?i+1:i; },
      drop(e){ e.preventDefault(); let at=this.dropAt==null?this.fields.length:this.dropAt; if(this.drag.kind==='lib'){ const m=this.meta(this.drag.type); this.fields.splice(at,0,{id:D().uid(),type:this.drag.type,label:m.label,required:false,ph:'',w:'full'}); } else { const it=this.fields[this.drag.i]; this.fields.splice(this.drag.i,1); if(this.drag.i<at)at-=1; this.fields.splice(at,0,it); } this.drag=null; this.dropAt=null; },
      markRead(s){ s.read = true; },
      exportCsv(){
        const rows = this.submissions;
        const cols = ['id','name','email','subject','message','date'];
        const esc = (v) => '"' + String(v == null ? '' : v).replace(/"/g, '""') + '"';
        const csv = [cols.join(',')].concat(rows.map(r => cols.map(c => esc(r[c])).join(','))).join('\n');
        const blob = new Blob([csv], { type:'text/csv' });
        const a = document.createElement('a'); a.href = URL.createObjectURL(blob); a.download = this.settings.slug + '-submissions.csv';
        document.body.appendChild(a); a.click(); a.remove(); setTimeout(() => URL.revokeObjectURL(a.href), 1000);
      },
    },
    template: `
    <!-- LIST -->
    <div v-if="!editing" class="page page--wide fade-up">
      <div class="page-head">
        <div><h1 class="t-title t-h1">Forms</h1><p class="page-sub" style="margin-top:6px">Build forms, route submissions, and trigger Actions.</p></div>
        <button class="btn btn--accent" @click="open({name:'Untitled form',slug:'untitled',fields:0,submissions:0})"><Icon name="plus"/> New form</button>
      </div>
      <div class="card">
        <table class="tbl tbl--rows">
          <thead><tr><th>Form</th><th>API key</th><th>Fields</th><th>Submissions</th><th>Updated</th></tr></thead>
          <tbody>
            <tr v-for="frm in forms" :key="frm.id">
              <td>
                <div class="flex items-center gap-3">
                  <span class="dash-row-ico"><Icon :name="frm.system ? 'lock' : 'form'" :size="16"/></span>
                  <div>
                    <a class="ce-title" @click="open(frm)">{{ frm.name }}</a>
                    <span v-if="frm.system" class="badge badge--info" style="margin-left:8px"><Icon name="lock" :size="10"/> system</span>
                    <div class="t-meta" v-if="frm.desc">{{ frm.desc }}</div>
                    <div class="row-actions">
                      <button @click="open(frm)">Edit</button>
                      <template v-if="frm.system">
                        <button @click="preview(frm)"><Icon name="external" :size="12"/> Open screen</button>
                      </template>
                      <template v-else>
                        <button @click="open(frm,'entries')">Entries</button>
                        <button><Icon name="copy" :size="12"/> Duplicate</button>
                        <button class="is-danger">Trash</button>
                        <button><Icon name="eye" :size="12"/> View</button>
                      </template>
                    </div>
                  </div>
                </div>
              </td>
              <td><span class="t-key">{{ frm.slug }}</span></td>
              <td class="mono muted">{{ frm.fields }}</td>
              <td><span class="badge" v-if="!frm.system">{{ frm.submissions }} received</span><span class="t-meta" v-else>auth flow</span></td>
              <td class="muted">{{ frm.updated }}</td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>

    <!-- BUILDER -->
    <div v-else class="fb">
      <div class="fb-top">
        <div class="fb-top-l">
          <button class="iconbtn" @click="editing=null"><Icon name="chevLeft"/></button>
          <span class="dash-row-ico"><Icon name="form" :size="16"/></span>
          <div><div class="t-eyebrow">Form</div><strong style="font-size:16px;font-family:var(--font-display)">{{ settings.name }}</strong></div>
        </div>
        <div class="fb-top-c">
          <div class="fb-vtabs">
            <button :class="{'is-on':view==='build'}" @click="view='build'"><Icon name="sliders" :size="15"/> Build</button>
            <button :class="{'is-on':view==='entries'}" @click="view='entries'"><Icon name="files" :size="15"/> Submissions <span class="tab-count">{{ submissions.length }}</span></button>
          </div>
        </div>
        <div class="fb-top-r">
          <button class="btn btn--ghost btn--sm" @click="preview({preview:'auth.html'})"><Icon name="eye" :size="15"/> Preview</button>
          <button class="btn"><Icon name="save" :size="15"/> Save form</button>
        </div>
      </div>
      <div class="fb-body" style="grid-template-columns: 248px 1fr 300px" v-if="view==='build'">
        <!-- palette -->
        <aside class="fb-lib">
          <div class="fb-lib-scroll">
            <div class="fb-lib-hint">Drag onto the form, or click to append.</div>
            <div class="fb-cat-items">
              <div v-for="t in palette" :key="t.type" class="fb-lib-item" draggable="true" @dragstart="libStart(t,$event)" @dragend="drag=null;dropAt=null" @click="add(t.type)">
                <Icon name="drag" :size="14" class="fb-lib-grip"/>
                <span class="lib-ico"><Icon :name="t.icon" :size="15"/></span>
                <span class="fb-lib-label">{{ t.label }}</span>
              </div>
            </div>
          </div>
        </aside>

        <!-- form canvas -->
        <section class="fb-canvas" @dragover.prevent @drop="drop">
          <div class="form-preview">
            <div class="form-prev-head"><h2 class="t-title t-h3" style="margin:0">{{ settings.name }}</h2><span class="t-key">/{{ settings.slug }}</span></div>
            <div class="form-grid">
              <div v-for="(f,i) in fields" :key="f.id" class="form-fieldwrap" :class="['w-'+f.w, {'is-sel': sel===f}]"
                   draggable="true" @dragstart="rowStart(i,$event)" @dragend="drag=null;dropAt=null" @dragover="over(i,$event)" @drop="drop" @click="sel=f">
                <div class="drop-line" v-if="dropAt===i"></div>
                <div class="form-field-top"><Icon name="drag" :size="14" class="drag-handle"/><label class="field-label" style="margin:0">{{ f.label }}<span class="field-req" v-if="f.required">*</span></label><div class="grow"></div><button class="iconbtn iconbtn--sm is-danger" @click.stop="del(i)"><Icon name="x" :size="13"/></button></div>
                <input v-if="['text','email','number','date'].includes(f.type)" class="inp" :placeholder="f.ph" disabled>
                <textarea v-else-if="f.type==='textarea'" class="ta" :placeholder="f.ph" disabled style="min-height:64px"></textarea>
                <UiSelect v-else-if="f.type==='select'" class="sel" disabled><option>Choose…</option></UiSelect>
                <div v-else-if="f.type==='checkbox'" class="flex items-center gap-2"><span class="chk"></span><span class="t-meta">Option</span></div>
                <div v-else-if="f.type==='radio'" class="flex items-center gap-2"><Icon name="radio" :size="16" class="muted"/><span class="t-meta">Option</span></div>
                <div v-else-if="f.type==='file'" class="form-file"><Icon name="upload" :size="15"/> Choose file…</div>
                <div v-else-if="f.type==='heading'" class="t-title t-h3">{{ f.label }}</div>
              </div>
              <div class="drop-line" v-if="dropAt===fields.length"></div>
            </div>
            <button class="btn btn--accent" style="margin-top:18px" disabled>{{ settings.submit }}</button>
          </div>
        </section>

        <!-- settings / field config -->
        <aside class="ed-rail">
          <template v-if="sel">
            <div class="rail-card">
              <div class="rail-head">Field · {{ meta(sel.type).label }}</div>
              <div class="rail-body">
                <div class="field"><label class="field-label">Label</label><input class="inp" v-model="sel.label"></div>
                <div class="field"><label class="field-label">Placeholder</label><input class="inp" v-model="sel.ph"></div>
                <div class="field"><label class="field-label">Width</label><div class="seg"><button :class="{'is-on':sel.w==='full'}" @click="sel.w='full'">Full</button><button :class="{'is-on':sel.w==='half'}" @click="sel.w='half'">Half</button></div></div>
                <div class="opt-row" style="border:0;padding:8px 0 0"><div><strong>Required</strong></div><UiSwitch v-model="sel.required"/></div>
              </div>
            </div>
          </template>
          <div class="rail-card">
            <div class="rail-head">Form settings</div>
            <div class="rail-body">
              <div class="field"><label class="field-label">Name</label><input class="inp" v-model="settings.name"></div>
              <div class="field"><label class="field-label">Submit label</label><input class="inp" v-model="settings.submit"></div>
              <div class="field" style="margin:0"><label class="field-label">Success message</label><textarea class="ta" v-model="settings.success" style="min-height:56px"></textarea></div>
            </div>
          </div>
          <div class="rail-card">
            <div class="rail-head">On submit</div>
            <div class="rail-body">
              <div class="opt-row" style="border:0;padding:6px 0"><div><strong>Store submission</strong></div><UiSwitch v-model="settings.store"/></div>
              <div class="opt-row" style="padding:10px 0"><div><strong>Email notification</strong><div class="field-hint">Notify the team of a new entry.</div></div><UiSwitch v-model="settings.notify"/></div>
              <div class="opt-row" style="padding:10px 0"><div><strong>Spam protection</strong></div><UiSwitch v-model="settings.spam"/></div>
              <button class="btn btn--ghost btn--sm" style="width:100%;margin-top:10px"><Icon name="zap" :size="14"/> Trigger an Action</button>
            </div>
          </div>
          <div class="rail-card">
            <div class="rail-head"><Icon name="mail" :size="14"/> Confirmation reply <span class="badge" style="margin-left:auto">optional</span></div>
            <div class="rail-body">
              <div class="opt-row" style="border:0;padding:2px 0 6px"><div><strong>Send confirmation email</strong><div class="field-hint">Auto-reply to whoever submitted the form. Off by default.</div></div><UiSwitch v-model="settings.confirm"/></div>
              <template v-if="settings.confirm">
                <div class="conf-note"><Icon name="info" :size="13"/> The reply goes to the form's <span class="t-key">email</span> field. Use <span class="t-key">{{ nameToken }}</span> to greet the sender.</div>
                <div class="field"><label class="field-label">From name</label><input class="inp" v-model="settings.confirmFromName"></div>
                <div class="field"><label class="field-label">Reply-to</label><input class="inp mono" v-model="settings.confirmReplyTo"></div>
                <div class="field"><label class="field-label">Subject</label><input class="inp" v-model="settings.confirmSubject"></div>
                <div class="field" style="margin:0"><label class="field-label">Message</label><textarea class="ta" v-model="settings.confirmBody" style="min-height:120px"></textarea></div>
              </template>
            </div>
          </div>
        </aside>
      </div>

      <!-- SUBMISSIONS -->
      <div class="fb-entries" v-else>
        <div class="entries-bar">
          <div><strong style="font-size:14px">{{ submissions.length }} submissions</strong><span class="t-meta" v-if="unread"> · {{ unread }} unread</span></div>
          <div class="grow"></div>
          <button class="btn btn--ghost btn--sm"><Icon name="filter" :size="14"/> Filter</button>
          <button class="btn btn--soft btn--sm" @click="exportCsv"><Icon name="files" :size="14"/> Export CSV</button>
        </div>
        <div class="card">
          <table class="tbl tbl--rows">
            <thead><tr><th style="width:170px">Name</th><th>Email</th><th>Subject</th><th style="width:160px">Received</th></tr></thead>
            <tbody>
              <tr v-for="s in submissions" :key="s.id" :class="{'is-unread-row':!s.read}" @click="entryOpen=s; markRead(s)" style="cursor:pointer">
                <td><span class="entry-dot" v-if="!s.read"></span><strong>{{ s.name }}</strong></td>
                <td><span class="t-key">{{ s.email }}</span></td>
                <td class="muted">{{ s.subject }}</td>
                <td class="muted mono">{{ s.date }}</td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>
    </div>

    <!-- single submission drawer -->
    <UiDrawer v-if="entryOpen" @close="entryOpen=null">
      <div class="drawer-head">
        <span class="dash-row-ico"><Icon name="mail" :size="16"/></span>
        <div style="flex:1"><strong style="font-size:15px">{{ entryOpen.subject }}</strong><div class="t-meta">{{ entryOpen.date }}</div></div>
        <button class="iconbtn" @click="entryOpen=null"><Icon name="x"/></button>
      </div>
      <div class="drawer-body">
        <div class="entry-field"><span class="field-label" style="margin:0">Name</span><span>{{ entryOpen.name }}</span></div>
        <div class="entry-field"><span class="field-label" style="margin:0">Email</span><a class="mode-help-link" :href="'mailto:'+entryOpen.email" style="display:inline-flex">{{ entryOpen.email }}</a></div>
        <div class="entry-field"><span class="field-label" style="margin:0">Subject</span><span>{{ entryOpen.subject }}</span></div>
        <div class="md-sec-h" style="margin-top:16px"><Icon name="align" :size="13"/> Message</div>
        <p style="font-size:13.5px;line-height:1.6;color:var(--ink-2);margin:0">{{ entryOpen.message }}</p>
      </div>
      <div class="drawer-foot">
        <button class="btn btn--danger" style="margin-right:auto"><Icon name="trash" :size="15"/> Delete</button>
        <a class="btn btn--ghost" :href="'mailto:'+entryOpen.email"><Icon name="mail" :size="15"/> Reply</a>
      </div>
    </UiDrawer>`,
  };
})();
