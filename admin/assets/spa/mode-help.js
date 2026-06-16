/* ============================================================
   CONTENT MODEL — the single, canonical Fixed vs Modular story.
   One explanation, referenced everywhere via <ModeHelp/>.
   - ModeExplainerBody : the reusable content block
   - ModeHelp          : a compact inline trigger -> opens the modal
   ============================================================ */
(function () {
  window.__mdlr = window.__mdlr || { comps: {} };
  const C = window.__mdlr.comps;

  /* The canonical explanation, used in the Settings page and the modal. */
  C.ModeExplainerBody = {
    name: 'ModeExplainerBody',
    template: `
    <div class="me">
      <p class="me-lead">Every item in Modular is <strong>composable</strong>. Whether a single field is locked in or offered optionally is a property of <strong>the field</strong>, set once in the field builder — not of the post type.</p>

      <div class="mode-explain">
        <div class="mode-card">
          <div class="mode-card-h"><span class="mode-ico mode-ico--fixed"><Icon name="lock" :size="16"/></span><strong>Fixed fields</strong></div>
          <p>Locked into every item of a type. Always in the editor — editors fill them in but can't add, reorder or remove them. The structured backbone.</p>
        </div>
        <div class="mode-arrow"><Icon name="plus" :size="18"/></div>
        <div class="mode-card">
          <div class="mode-card-h"><span class="mode-ico mode-ico--modular"><Icon name="layers" :size="16"/></span><strong>Modular fields</strong></div>
          <p>Offered per item. Editors add, reorder and remove them themselves — including dropping in components. The composable layer on top.</p>
        </div>
      </div>

      <div class="me-prec">
        <div class="me-prec-h"><Icon name="branch" :size="15"/> How a field's availability is decided</div>
        <div class="prec" style="margin:0">
          <div class="prec-step"><span class="prec-num">1</span><div><strong>Field registry</strong><div class="t-meta">Typed definitions — the WHAT.</div></div></div>
          <Icon name="arrowRight" :size="16" class="prec-arrow"/>
          <div class="prec-step"><span class="prec-num">2</span><div><strong>Post-type default</strong><div class="t-meta">Bound as fixed or modular.</div></div></div>
          <Icon name="arrowRight" :size="16" class="prec-arrow"/>
          <div class="prec-step"><span class="prec-num">3</span><div><strong>Per-item override</strong><div class="t-meta">Editors add modular fields in the editor.</div></div></div>
        </div>
      </div>

      <div class="me-prov">
        <div class="flex items-center gap-2"><span class="prov prov--type"><Icon name="lock" :size="11"/> From post type</span> fixed, always shown</div>
        <div class="flex items-center gap-2"><span class="prov prov--post"><Icon name="plus" :size="11"/> Added here</span> modular, this item only</div>
        <div class="flex items-center gap-2"><span class="prov prov--comp"><Icon name="git" :size="11"/> Shared group</span> reused across types</div>
      </div>
    </div>`,
  };

  /* Compact inline trigger — drop this anywhere instead of repeating the explanation. */
  C.ModeHelp = {
    name: 'ModeHelp',
    props: { label: { type: String, default: 'What\u2019s Fixed vs Modular?' } },
    data() { return { open: false }; },
    template: `
    <span style="display:inline-flex">
      <button type="button" class="mode-help-link" @click="open=true">
        <Icon name="helpCircle" :size="14"/> {{ label }}
      </button>
      <UiModal v-if="open" lg @close="open=false">
        <div class="drawer-head">
          <span class="mode-ico mode-ico--modular"><Icon name="layers" :size="16"/></span>
          <div style="flex:1"><strong style="font-size:15px">Fixed vs Modular</strong><div class="t-meta">How Modular models content</div></div>
          <button class="iconbtn" @click="open=false"><Icon name="x"/></button>
        </div>
        <div class="drawer-body"><ModeExplainerBody/></div>
        <div class="drawer-foot">
          <button class="btn btn--ghost" @click="open=false">Close</button>
        </div>
      </UiModal>
    </span>`,
  };
})();
