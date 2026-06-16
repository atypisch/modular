/* ============================================================
   ACTIONS — Directus-style automation flows.
   Trigger → operations chain, configurable per step.
   ============================================================ */
(function () {
  window.__mdlr = window.__mdlr || { comps: {} };
  const C = window.__mdlr.comps;
  const D = () => window.__mdlr.data;

  const TRIGGERS = [
    { id:'post_published', label:'Post published', icon:'file' },
    { id:'post_created', label:'Post created', icon:'plus' },
    { id:'form_submitted', label:'Form submitted', icon:'form' },
    { id:'user_registered', label:'User registered', icon:'user' },
    { id:'media_uploaded', label:'Media uploaded', icon:'image' },
    { id:'schedule', label:'On a schedule', icon:'clock' },
    { id:'webhook_in', label:'Incoming webhook', icon:'webhook' },
  ];
  const OPS = [
    { type:'condition', label:'Condition', icon:'branch', desc:'Branch on a value' },
    { type:'email', label:'Send email', icon:'mail', desc:'Notify a person or team' },
    { type:'webhook', label:'Call webhook', icon:'webhook', desc:'POST to an external URL' },
    { type:'create', label:'Create item', icon:'plus', desc:'Make a new post' },
    { type:'update', label:'Update item', icon:'pencil', desc:'Change fields or status' },
    { type:'tag', label:'Add tag', icon:'pin', desc:'Attach a taxonomy term' },
    { type:'transform', label:'Transform', icon:'code', desc:'Run a small script' },
    { type:'delay', label:'Wait', icon:'clock', desc:'Pause the flow' },
    { type:'hook', label:'Run hook', icon:'zap', desc:'Fire a PHP do_action' },
  ];

  C.ScreenActions = {
    name: 'ScreenActions',
    data() {
      return { editing:null, trigger:'post_published', steps:[], sel:null, addAt:null, picker:false };
    },
    computed: { actions() { return D().ACTIONS; }, triggers() { return TRIGGERS; }, ops() { return OPS; },
      triggerMeta() { return TRIGGERS.find(t=>t.id===this.trigger)||TRIGGERS[0]; } },
    methods: {
      open(a) {
        this.editing = a;
        this.steps = a.ops.map(o => ({ id:D().uid(), ...o }));
      },
      opMeta(t){ return OPS.find(o=>o.type===t)||{icon:'zap',label:t}; },
      openPicker(at){ this.addAt = at; this.picker = true; },
      addOp(t){ const m=this.opMeta(t); const step={ id:D().uid(), type:t, icon:m.icon, label:m.label }; const at=this.addAt==null?this.steps.length:this.addAt; this.steps.splice(at,0,step); this.picker=false; this.sel=step; },
      del(i){ this.steps.splice(i,1); },
    },
    template: `
    <!-- LIST -->
    <div v-if="!editing" class="page page--wide fade-up">
      <div class="page-head">
        <div><h1 class="t-title t-h1">Actions</h1><p class="page-sub" style="margin-top:6px">Automation flows: a trigger, then a chain of operations. The relational backbone of Directus, made readable.</p></div>
        <button class="btn btn--accent" @click="open({name:'Untitled flow',trigger:'Post published',enabled:true,runs:0,ops:[]})"><Icon name="plus"/> New action</button>
      </div>
      <div class="act-list">
        <div v-for="a in actions" :key="a.id" class="act-card" @click="open(a)">
          <div class="act-card-l">
            <span class="act-trig"><Icon name="zap" :size="16"/></span>
            <div>
              <strong>{{ a.name }}</strong>
              <div class="t-meta">when <strong style="color:var(--ink-2)">{{ a.trigger }}</strong></div>
            </div>
          </div>
          <div class="act-chain">
            <span v-for="(o,i) in a.ops" :key="i" class="act-chip" :title="o.label"><Icon :name="o.icon" :size="13"/></span>
          </div>
          <div class="act-card-r">
            <span class="t-meta mono">{{ a.runs }} runs</span>
            <span class="badge" :class="a.enabled?'badge--ok':''">{{ a.enabled?'on':'off' }}</span>
            <UiSwitch :modelValue="a.enabled"/>
          </div>
        </div>
      </div>
    </div>

    <!-- FLOW EDITOR -->
    <div v-else class="fb">
      <div class="fb-top">
        <div class="fb-top-l">
          <button class="iconbtn" @click="editing=null"><Icon name="chevLeft"/></button>
          <span class="act-trig" style="width:30px;height:30px"><Icon name="zap" :size="15"/></span>
          <div><div class="t-eyebrow">Action flow</div><strong style="font-size:16px;font-family:var(--font-display)">{{ editing.name }}</strong></div>
        </div>
        <div class="fb-top-r">
          <span class="flex items-center gap-2"><span class="t-meta">Enabled</span><UiSwitch :modelValue="editing.enabled"/></span>
          <button class="btn btn--ghost btn--sm"><Icon name="play" :size="15"/> Test run</button>
          <button class="btn"><Icon name="save" :size="15"/> Save flow</button>
        </div>
      </div>
      <div class="fb-body" style="grid-template-columns: 1fr 320px">
        <!-- flow canvas -->
        <section class="flow-canvas">
          <div class="flow">
            <!-- trigger -->
            <div class="flow-node flow-node--trigger" @click="sel={trigger:true}">
              <div class="flow-node-ico"><Icon :name="triggerMeta.icon" :size="17"/></div>
              <div class="flow-node-main"><div class="t-eyebrow" style="color:var(--accent-deep)">Trigger</div><strong>{{ triggerMeta.label }}</strong></div>
              <Icon name="settings" :size="16" class="muted"/>
            </div>

            <template v-for="(s,i) in steps" :key="s.id">
              <div class="flow-conn"><button class="flow-add" @click="openPicker(i)"><Icon name="plus" :size="13"/></button></div>
              <div class="flow-node" :class="{'is-sel': sel===s, 'flow-node--cond': s.type==='condition'}" @click="sel=s">
                <div class="flow-node-ico"><Icon :name="s.icon" :size="17"/></div>
                <div class="flow-node-main"><div class="t-eyebrow">{{ opMeta(s.type).label }}</div><strong>{{ s.label }}</strong></div>
                <div class="flow-node-actions" @click.stop><button class="iconbtn iconbtn--sm is-danger" @click="del(i)"><Icon name="trash" :size="14"/></button></div>
              </div>
            </template>

            <div class="flow-conn"><button class="flow-add" @click="openPicker(steps.length)"><Icon name="plus" :size="13"/></button></div>
            <button class="flow-end" @click="openPicker(steps.length)"><Icon name="plus" :size="15"/> Add operation</button>
          </div>
        </section>

        <!-- config rail -->
        <aside class="ed-rail">
          <div class="rail-card" v-if="sel && sel.trigger">
            <div class="rail-head">Trigger</div>
            <div class="rail-body">
              <div class="field"><label class="field-label">Event</label><UiSelect v-model="trigger"><option v-for="t in triggers" :key="t.id" :value="t.id">{{ t.label }}</option></UiSelect></div>
              <div class="field" style="margin:0"><label class="field-label">Scope</label><UiSelect><option>Any post type</option><option>Projects only</option><option>Products only</option></UiSelect></div>
            </div>
          </div>
          <div class="rail-card" v-else-if="sel">
            <div class="rail-head">{{ opMeta(sel.type).label }}</div>
            <div class="rail-body">
              <div class="field"><label class="field-label">Label</label><input class="inp" v-model="sel.label"></div>
              <template v-if="sel.type==='email'">
                <div class="field"><label class="field-label">To</label><input class="inp mono" value="team@atypisch.nl"></div>
                <div class="field" style="margin:0"><label class="field-label">Template</label><UiSelect><option>Publish notice</option><option>Custom…</option></UiSelect></div>
              </template>
              <template v-else-if="sel.type==='webhook'">
                <div class="field"><label class="field-label">Method</label><div class="seg"><button class="is-on">POST</button><button>GET</button><button>PUT</button></div></div>
                <div class="field" style="margin:0"><label class="field-label">URL</label><input class="inp mono" placeholder="https://…"></div>
              </template>
              <template v-else-if="sel.type==='condition'">
                <div class="field" style="margin:0"><label class="field-label">Continue only if</label>
                  <div class="cond-row" style="margin-top:8px"><UiSelect><option>Post type</option><option>Status</option></UiSelect><UiSelect style="max-width:80px"><option>is</option></UiSelect><input class="inp" value="Project"></div>
                </div>
              </template>
              <template v-else>
                <div class="field" style="margin:0"><label class="field-label">Configuration</label><textarea class="ta mono" placeholder="key: value" style="min-height:80px"></textarea></div>
              </template>
            </div>
          </div>
          <div class="rail-card rail-card--quiet" v-else>
            <div class="rail-body center" style="color:var(--ink-3);padding:28px 16px"><Icon name="branch" :size="26" style="color:var(--ink-4);margin-bottom:8px"/><div style="font-size:13px">Select the trigger or any step to configure it.</div></div>
          </div>
          <div class="rail-card rail-card--quiet">
            <div class="rail-head"><Icon name="info" :size="14"/> Data available</div>
            <div class="rail-body" style="font-size:12.5px;color:var(--ink-2);line-height:1.7">
              Each step can read <span class="t-key" v-pre>{{ trigger.post }}</span>, <span class="t-key" v-pre>{{ steps.* }}</span> and site settings — same token model as templates.
            </div>
          </div>
        </aside>
      </div>

      <!-- op picker -->
      <UiModal v-if="picker" @close="picker=false">
        <div class="drawer-head"><Icon name="plus" :size="18"/><strong style="font-size:15px;flex:1">Add operation</strong><button class="iconbtn" @click="picker=false"><Icon name="x"/></button></div>
        <div class="drawer-body">
          <div class="op-grid">
            <button v-for="o in ops" :key="o.type" class="op-pick" @click="addOp(o.type)">
              <span class="op-ico"><Icon :name="o.icon" :size="17"/></span>
              <div><strong>{{ o.label }}</strong><div class="t-meta">{{ o.desc }}</div></div>
            </button>
          </div>
        </div>
      </UiModal>
    </div>`,
  };
})();
