# Audit: Field System & PrimeVue

## 1. PrimeVue-integratie

### Bronnen
- **Header:** `/modular/admin/templates/header.php` laadt lokaal:
  - Vue 3: `admin/assets/vendor/vue/vue.global.prod.js`
  - Quill: `admin/assets/vendor/quill/quill.min.js` + `quill.snow.css`
  - PrimeVue: `admin/assets/vendor/primevue/primevue.min.js` + `primevue.min.css`
  - PrimeIcons: `primeicons.css`
- **Geen CDN** in de pagina zelf; assets komen uit `admin/assets/vendor/` (eenmalig downloaden via `download-vendor.sh`).

### Componenten (UMD-bundle)
- De bundle `primevue@4/umd/primevue.min.js` bevat alle PrimeVue-componenten. Gecontroleerd/verwacht:
  - **InputText, Textarea, Editor** (Editor vereist Quill, apart geladen): OK
  - **Dropdown, MultiSelect, AutoComplete, Calendar, FileUpload**: in UMD aanwezig
  - **Checkbox, RadioButton, SelectButton, Password, InputNumber, Slider**: in UMD aanwezig
  - **TreeSelect**: in UMD aanwezig
- **Quill:** lokaal in `vendor/quill/`; nodig voor PrimeVue Editor. Script wordt vóór PrimeVue geladen: OK.

### Ontbrekend / actie
- **PrimeVue CSS:** Het bestand `primevue.min.css` bevat een regel "Not found: /primevue@4.5.4/..." – dit is een bronverwijzing in de CSS; de bestandsnaam zelf is correct. Geen actie indien styling werkt.
- **Expliciete componentregistratie:** In `primevue-fields.js` worden alleen Editor, MultiSelect en Dropdown geregistreerd. Voor schema-driven rendering moeten alle gebruikte componenten (InputText, Textarea, Calendar, FileUpload, AutoComplete, etc.) expliciet worden geregistreerd in de Vue-app.

---

## 2. Field Builder (bestaand)

### Beschikbare draggable fields (uit FieldRegistry)
- **Basic:** text, textarea, number, email, url, password, range, search, hidden
- **Content:** wysiwyg, code, markdown
- **Choice:** select, checkbox, radio, button_group, true_false
- **Relational:** post_object, relationship, taxonomy, user
- **Media:** image, file, gallery, audio, video, embed
- **Date/Time:** date_picker, time_picker, date_time_picker
- **Layout:** group, repeater, flexible_content
- **Utility:** message, accordion, tab, divider

### Configuratie-opties (huidige builder)
- **TAB_KEYS:** general (label, name, key, instructions, default_value, sub_fields, layouts, button_label), validation (required, min, max, step, maxlength, pattern, allow_null, multiple), display (placeholder, prepend, append, rows, wrapper_class, input_class, ui, return_format, choices, layout, toolbar, media_upload, tabs, delay), logic (conditional_logic).
- **Specifiek:** post_type en taxonomy voor relation-velden als checkboxes (uit DB). return_format, toolbar, tabs voor WYSIWYG.

### Problemen
- **Geen canoniek schema:** Er is geen `ui.component`, `ui.props`, `storage`, `output` in het opgeslagen schema; de builder slaat een ad-hoc structuur op (type, label, name, required, choices, post_type, etc.).
- **Hardcoded UI in post editor:** `field-form.php` en `primevue-fields.js` bepalen het component per type in code in plaats van uit schema.
- **Field Builder rendert geen PrimeVue:** De builder zelf gebruikt geen PrimeVue voor de settings-panelen (alleen HTML inputs in een slide-out). De builder toont wel een “preview” van veldtypen in de library, maar geen live PrimeVue-preview van het gekozen component.

---

## 3. Uitgevoerde refactor

1. **Canoniek schema + mapping:** `docs/FIELD_DEFINITION_SCHEMA.md`, `docs/FIELD_MAPPING.md`.
2. **Schema-normalisatie in PHP:** `core/fields/FieldSchemaMap.php` – `normalizeField` / `normalizeSchema` vullen `ui`, `storage`, `output` aan.
3. **Field Builder:** `admin/post-types/field-schema-save.php` – bij opslaan wordt schema genormaliseerd (merge defaults uit mapping).
4. **Post Editor:** `admin/posts/edit.php` – genormaliseerd schema + opties voor relation-velden; mount `#post-edit-fields-app`; `window.MODULAR_FIELD_SCHEMA` en `MODULAR_FIELD_DATA`. `admin/assets/js/post-edit-fields-app.js` – één Vue-app die alleen uit schema rendert (ui.component, ui.props), PrimeVue-componenten, waarden naar hidden inputs voor submit.
5. **Output:** PostTypeResolver gebruikt veldnaam als content-key; PHP/JSON zijn daarmee consistent met schema (output.json = key).

---

## 4. Validatie-checklist

- [ ] **WYSIWYG:** Editor (Quill) rendert correct; geen dubbele toolbars of lege blokken.
- [ ] **Gallery:** Ondersteunt meerdere afbeeldingen; opslag als array van IDs.
- [ ] **Relationship/Post Object:** IDs worden correct opgeslagen en opgehaald; relaties worden in frontend (resolvePost) opgelost.
- [ ] **Geen veld rendert als plain textarea** tenzij type = textarea of code/markdown (Textarea-component).
- [ ] **Select/Radio/Button group:** Keuzes uit schema.choices of schema.options (relation) correct getoond.
- [ ] **Form submit:** Alle waarden in `field_data[key]` of `field_data[key][]`; save.php verwerkt ongewijzigd.

---

## 5. TODO (prioriteit)

| Prioriteit | Item |
|------------|------|
| P1 | Field Builder UI omzetten naar PrimeVue (config-only): settings-panel met p-dropdown, p-inputtext, etc. voor ui.props. |
| P1 | Layout-velden (group, repeater, flexible_content) in post-edit-fields-app: custom wrappers of sub-schema render. |
| P2 | FileUpload in editor: upload-URL en media-lijst koppelen (nu mogelijk nog fallback). |
| P2 | Calendar: datum/tijd-formaat en timeOnly correct doorgeven in ui.props. |
| P3 | Validatie (required, min/max) in Vue-laag tonen; backend blijft leidend. |
