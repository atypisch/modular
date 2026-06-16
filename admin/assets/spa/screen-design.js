/* ============================================================
   DESIGN SYSTEM — "Atelier", now a real, live editor.
   Every token edits the running admin and saves to your browser;
   export/import a .json file to version or share the theme.
   ============================================================ */
(function () {
  window.__mdlr = window.__mdlr || { comps: {} };
  const C = window.__mdlr.comps;
  const TH = () => window.__mdlr.theme;

  C.ScreenDesignSystem = {
    name: 'ScreenDesignSystem',
    data() {
      return {
        t: TH().state,
        presets: TH().ACCENT_PRESETS,
        fontsDisplay: TH().FONT_DISPLAY,
        fontsUi: TH().FONT_UI,
        neutrals: [ ['paper','Canvas'], ['surface','Card'], ['ink','Text'], ['ink2','Text 2'], ['line','Hairline'] ],
        statusTokens: [ ['ok','Success'], ['warn','Warning'], ['danger','Danger'], ['info','Info'] ],
        catTokens: [ ['catBasic','Basic'], ['catContent','Content'], ['catChoice','Choice'], ['catRel','Relational'], ['catMedia','Media'], ['catDate','Date'], ['catLayout','Layout'] ],
        saved: false, _t: null,
      };
    },
    computed: {
      dirty() { return !TH().isDefault(); },
    },
    methods: {
      set(k, v) { TH().set(k, v); this.flash(); },
      pickAccent(hex) { this.set('accent', hex); },
      flash() { this.saved = true; clearTimeout(this._t); this._t = setTimeout(() => { this.saved = false; }, 1400); },
      exportJson() { TH().exportJson(); },
      triggerImport() { this.$refs.file.click(); },
      onImport(e) { const f = e.target.files[0]; if (f) TH().importFile(f).then(() => this.flash()).catch(() => alert('Could not read that theme file.')); e.target.value = ''; },
      reset() { TH().reset(); this.flash(); },
    },
    template: `
    <div class="page page--wide fade-up">
      <div class="page-head">
        <div>
          <div class="t-eyebrow" style="margin-bottom:8px">Design system · this admin</div>
          <h1 class="t-title t-h1">Atelier</h1>
          <p class="page-sub" style="margin-top:6px">The live design system for the <strong style="color:var(--ink-2)">Modular admin</strong>. Every value below edits the running interface and is saved to this browser. Export a <span class="t-key">.json</span> file to version or share it.</p>
        </div>
        <div class="flex gap-2 items-center">
          <span class="ds-saved" v-if="saved"><Icon name="check" :size="14"/> Saved</span>
          <input ref="file" type="file" accept="application/json,.json" hidden @change="onImport">
          <button class="btn btn--ghost btn--sm" @click="triggerImport"><Icon name="upload" :size="14"/> Import</button>
          <button class="btn btn--ghost btn--sm" @click="exportJson"><Icon name="files" :size="14"/> Export JSON</button>
          <button class="btn btn--ghost btn--sm" :disabled="!dirty" @click="reset"><Icon name="refresh" :size="14"/> Reset</button>
        </div>
      </div>

      <div class="ds-edit">
        <!-- BRAND / COLOR -->
        <section class="ds-card card">
          <div class="ds-card-h"><Icon name="palette" :size="16"/><strong>Brand &amp; colour</strong></div>
          <div class="ds-card-b">
            <label class="field-label">Accent</label>
            <div class="ds-accent-row">
              <button v-for="p in presets" :key="p.id" class="ds-sw" :class="{'is-on': t.accent.toLowerCase()===p.hex.toLowerCase()}" :style="{background:p.hex}" :title="p.label" @click="pickAccent(p.hex)"><Icon v-if="t.accent.toLowerCase()===p.hex.toLowerCase()" name="check" :size="14"/></button>
              <label class="ds-custom"><input type="color" :value="t.accent" @input="set('accent',$event.target.value)"><span class="t-key">{{ t.accent }}</span></label>
            </div>

            <label class="field-label" style="margin-top:18px">Neutrals</label>
            <div class="ds-tokens">
              <div class="ds-token" v-for="n in neutrals" :key="n[0]">
                <label class="ds-token-sw" :style="{background:t[n[0]]}"><input type="color" :value="t[n[0]]" @input="set(n[0],$event.target.value)"></label>
                <div class="ds-token-meta"><strong>{{ n[1] }}</strong><input class="inp mono ds-hex" :value="t[n[0]]" @change="set(n[0],$event.target.value)"></div>
              </div>
            </div>

            <label class="field-label" style="margin-top:18px">Primary action</label>
            <div class="ds-tokens">
              <div class="ds-token">
                <label class="ds-token-sw" :style="{background:t.btn}"><input type="color" :value="t.btn" @input="set('btn',$event.target.value)"></label>
                <div class="ds-token-meta"><strong>Button</strong><input class="inp mono ds-hex" :value="t.btn" @change="set('btn',$event.target.value)"></div>
              </div>
            </div>
          </div>
        </section>

        <!-- TYPOGRAPHY -->
        <section class="ds-card card">
          <div class="ds-card-h"><Icon name="type" :size="16"/><strong>Typography</strong></div>
          <div class="ds-card-b">
            <div class="cfg-2">
              <div class="field"><label class="field-label">Display font</label><UiSelect :modelValue="t.fontDisplay" @update:modelValue="v=>set('fontDisplay',v)"><option v-for="f in fontsDisplay" :key="f">{{ f }}</option></UiSelect></div>
              <div class="field"><label class="field-label">Body font</label><UiSelect :modelValue="t.fontUi" @update:modelValue="v=>set('fontUi',v)"><option v-for="f in fontsUi" :key="f">{{ f }}</option></UiSelect></div>
            </div>
            <label class="field-label" style="margin-top:6px">Base size · {{ t.baseSize }}px</label>
            <input type="range" min="13" max="17" step="1" :value="t.baseSize" @input="set('baseSize',+$event.target.value)" style="width:100%;accent-color:var(--accent)">
            <div class="ds-type-spec">
              <span class="t-display" style="font-size:40px">Modeling, made calm</span>
              <span class="t-title t-h3" style="margin-top:10px;display:block">Section heading</span>
              <span style="display:block;margin-top:6px">Body — the resolved, effective fieldset for this item, with provenance.</span>
            </div>
          </div>
        </section>

        <!-- SHAPE -->
        <section class="ds-card card">
          <div class="ds-card-h"><Icon name="box" :size="16"/><strong>Shape</strong></div>
          <div class="ds-card-b">
            <label class="field-label">Corner radius · {{ t.radius }}px</label>
            <input type="range" min="0" max="16" step="1" :value="t.radius" @input="set('radius',+$event.target.value)" style="width:100%;accent-color:var(--accent)">
            <div class="flex gap-3 wrap" style="margin-top:16px">
              <button class="btn btn--accent">Accent</button>
              <button class="btn">Primary</button>
              <button class="btn btn--ghost">Ghost</button>
              <span class="badge badge--ok">published</span>
              <span class="chk is-on"><Icon name="check"/></span>
            </div>
            <div class="flex gap-3 wrap" style="margin-top:14px;align-items:flex-end">
              <div style="width:200px"><input class="inp" placeholder="Text input"></div>
              <div class="radii-box" style="width:48px;height:48px;background:var(--accent-soft);border:1px solid var(--line-2)"></div>
            </div>
          </div>
        </section>

        <!-- STATUS COLOURS -->
        <section class="ds-card card">
          <div class="ds-card-h"><Icon name="info" :size="16"/><strong>Status colours</strong></div>
          <div class="ds-card-b">
            <div class="ds-tokens">
              <div class="ds-token" v-for="s in statusTokens" :key="s[0]">
                <label class="ds-token-sw" :style="{background:t[s[0]]}"><input type="color" :value="t[s[0]]" @input="set(s[0],$event.target.value)"></label>
                <div class="ds-token-meta"><strong>{{ s[1] }}</strong><input class="inp mono ds-hex" :value="t[s[0]]" @change="set(s[0],$event.target.value)"></div>
                <span class="badge" :class="'badge--'+s[0]">{{ s[1].toLowerCase() }}</span>
              </div>
            </div>
          </div>
        </section>

        <!-- FIELD CATEGORY HUES -->
        <section class="ds-card card">
          <div class="ds-card-h"><Icon name="type" :size="16"/><strong>Field categories</strong></div>
          <div class="ds-card-b">
            <p class="field-hint" style="margin:0 0 12px">The muted drawer-label hues used across the field builder.</p>
            <div class="ds-tokens">
              <div class="ds-token" v-for="c in catTokens" :key="c[0]">
                <label class="ds-token-sw" :style="{background:t[c[0]]}"><input type="color" :value="t[c[0]]" @input="set(c[0],$event.target.value)"></label>
                <div class="ds-token-meta"><strong>{{ c[1] }}</strong><input class="inp mono ds-hex" :value="t[c[0]]" @change="set(c[0],$event.target.value)"></div>
              </div>
            </div>
          </div>
        </section>
      </div>

      <!-- LIVE PREVIEW: a real card UI built from the tokens -->
      <section class="ds-sec">
        <h2 class="t-title t-h3">Live preview</h2>
        <div class="card card-pad">
          <div class="flex items-center justify-between" style="margin-bottom:14px">
            <div><div class="t-eyebrow">Project</div><strong style="font-family:var(--font-display);font-size:18px">Digital Twins Helpdesk</strong></div>
            <span class="badge badge--ok"><span class="badge--dot"></span>published</span>
          </div>
          <p style="color:var(--ink-2);max-width:60ch;margin:0 0 16px">A grounded helpdesk built on living digital twins — written for the people who run the building. This whole card recolours and reshapes as you edit the tokens above.</p>
          <div class="flex gap-2">
            <button class="btn btn--accent"><Icon name="plus" :size="15"/> New</button>
            <button class="btn btn--ghost">Preview</button>
            <span class="prov prov--type"><Icon name="lock" :size="11"/> From post type</span>
            <span class="modepill modepill--modular"><Icon name="layers" :size="11"/> Modular</span>
          </div>
        </div>
      </section>
    </div>`,
  };
})();
