# Modular CMS → PrimeVue Field Renderer – Full Mapping

This document is the single source of truth for the Vue/PrimeVue field rendering system. Every Modular CMS field type maps to a PrimeVue component with specific props, events, and styling.

## Factory

- **`resolveFieldComponent(fieldType, fieldOptions, styledOptions?)`**  
  Returns `{ component, tag, props, events, slots, styledOptions, isUtility, isLayout, options, label, name, instructions, required, default }`.
- **`ModularFieldRenderer.normalizeChoices(choices)`**  
  Converts schema choices (object, array, or "value : Label" lines) to `[{ label, value }]`.

## Field type → PrimeVue component and options

| Modular CMS type   | PrimeVue component | Tag           | Main props / options |
|--------------------|--------------------|---------------|----------------------|
| **Text inputs**    |                    |               |                      |
| text               | InputText          | p-inputtext   | placeholder, maxlength, readonly, disabled |
| email              | InputText          | p-inputtext   | placeholder (email@example.com) |
| url                | InputText          | p-inputtext   | placeholder (https://) |
| search             | InputText          | p-inputtext   | placeholder (Search) |
| embed              | InputText          | p-inputtext   | placeholder (URL) |
| textarea           | Textarea           | p-textarea    | rows, placeholder, maxlength |
| wysiwyg            | Editor             | p-editor      | placeholder, editorStyle.minHeight, toolbar, tabs |
| code               | Textarea           | p-textarea    | rows, class p-monospace |
| markdown           | Textarea           | p-textarea    | rows |
| **Numeric**        |                    |               |                      |
| number             | InputNumber        | p-inputnumber | min, max, step, showButtons, minFractionDigits, maxFractionDigits |
| range              | Slider             | p-slider      | min, max, step |
| **Boolean / choice** |                 |               |                      |
| password           | Password           | p-password    | placeholder, feedback, toggleMask |
| hidden             | (hidden input)     | input         | value only |
| select             | Dropdown           | p-dropdown    | options, optionLabel, optionValue, placeholder, showClear, filter, filterPlaceholder |
| select (multiple)  | MultiSelect        | p-multiselect | options, display (chip), maxSelectedLabels |
| radio              | RadioButton        | p-radiobutton | options, optionLabel, optionValue |
| button_group       | SelectButton       | p-selectbutton | options, allowEmpty |
| checkbox           | Checkbox or MultiSelect | p-checkbox / p-multiselect | binary (single) or options (multi) |
| true_false         | Checkbox or ToggleSwitch | p-checkbox / p-toggleswitch | binary; use `ui.component: 'ToggleSwitch'` for switch |
| **Date / time**    |                    |               |                      |
| date_picker        | Calendar           | p-calendar    | dateFormat (yy-mm-dd), showTime false, showButtonBar, hourFormat |
| time_picker        | Calendar           | p-calendar    | timeOnly true, dateFormat HH:mm, hourFormat |
| date_time_picker   | Calendar           | p-calendar    | showTime true, dateFormat yy-mm-dd HH:mm |
| **Relation**       |                    |               |                      |
| post_object        | Dropdown           | p-dropdown    | options (API), optionLabel, optionValue, showClear, filter |
| relationship       | MultiSelect        | p-multiselect | options (API), filter, display chip |
| relation           | MultiSelect        | p-multiselect | (same as relationship) |
| user               | Dropdown           | p-dropdown    | options (API), showClear, filter |
| taxonomy           | TreeSelect         | p-treeselect  | options (tree), selectionMode (single/multiple), filter |
| **Media**          |                    |               |                      |
| image              | FileUpload         | p-fileupload  | mode advanced, accept image/*, customUpload |
| file               | FileUpload         | p-fileupload  | mode advanced, accept */*, customUpload |
| gallery            | FileUpload         | p-fileupload  | multiple true, accept image/* |
| audio              | FileUpload         | p-fileupload  | accept audio/* |
| video              | FileUpload         | p-fileupload  | accept video/* |
| **Utility (no storage)** |              |               |                      |
| message            | Message            | p-message     | severity (info) |
| accordion          | (custom header)    | —             | label = section title |
| tab                | (custom label)     | —             | label = tab label |
| divider            | Divider            | p-divider     | type (solid) |
| **Layout**         |                    |               |                      |
| group              | (nested fields)    | div           | sub_fields |
| repeater           | (repeating rows)   | div           | sub_fields |
| flexible_content   | (layout blocks)    | div           | layouts |

## Schema options → PrimeVue props

| Schema option       | Used by types        | PrimeVue prop / behavior |
|---------------------|----------------------|---------------------------|
| label               | all                  | result.label, label element |
| name / key          | all (storage)        | result.name, field_data key |
| instructions        | all                  | result.instructions, help text |
| required            | all                  | required asterisk, aria |
| default_value / default | all               | initial model value |
| placeholder         | text, textarea, select, etc. | placeholder |
| disabled, readonly  | all                  | disabled, readonly |
| min, max, step      | number, range        | min, max, step |
| rows                | textarea, wysiwyg, code | rows, editorStyle.minHeight |
| choices             | select, radio, button_group, checkbox | options (normalized to [{label, value}]) |
| allow_null          | select, button_group | showClear, allowEmpty |
| searchable          | select               | filter true |
| post_type           | post_object, relationship | (backend: filter options) |
| options             | post_object, relationship, user, taxonomy | options (API-driven) |
| toolbar, tabs       | wysiwyg              | toolbar, tabs |
| inputClass, wrapper_class, inputStyle | all (optional) | styledOptions.class, .wrapperClass, .style |
| validationMessage / validation_message | all | result.validationMessage; shown below field when invalid |
| tooltip / title   | all                  | result.tooltip; label title + aria-label |
| invalid           | all                  | aria-invalid, .is-invalid wrapper, validation styling |

## Events

All data-binding fields emit:

- **update:modelValue** – primary two-way binding
- **change**, **focus**, **blur** – supported where applicable

The Vue app syncs `fieldData` to a hidden input `field_data_json` on form submit.

## Styled options

- **styledOptions** (per field): `{ class, style, wrapperClass }`  
  Applied to the wrapper and/or the component so Modular CMS theme conventions and custom CSS can override appearance.

## Usage (Vue template)

```html
<div id="post-edit-fields-vue-app"></div>
<input type="hidden" name="field_data_json" id="field_data_json" value="">
<script>
window.MODULAR_FIELD_SCHEMA = { fields: [ /* schema */ ] };
window.MODULAR_FIELD_DATA = { /* initial values */ };
window.MODULAR_FIELD_OPTIONS = { /* optional: post_object/relationship/user options by field name */ };
</script>
<script src=".../resolveFieldComponent.js"></script>
<script src=".../field-renderer-app.js"></script>
```

The app mounts automatically when `MODULAR_FIELD_SCHEMA` is set. On submit, it serializes reactive `fieldData` to `#field_data_json` so the backend can read `$_POST['field_data_json']`.

## Backend

- **save.php** accepts either `$_POST['field_data']` (PHP-rendered form) or `$_POST['field_data_json']` (JSON string from Vue). When `field_data_json` is present, it is decoded and used as raw field data before sanitization.

## Test page

- **Admin → Field Renderer Test**: `/modular/admin/field-renderer-test.php`  
  Renders every field type with sample options and a live TODO list of detected issues (missing components, wrong mapping).

## Extending

- Add a new field type in **resolveFieldComponent.js** (new `case 'new_type':`), set `result.component`, `result.props`, and ensure `COMPONENT_TAG_MAP` has the tag.
- Register the PrimeVue component in **field-renderer-app.js** in `COMPONENT_REGISTRY` and in the app’s `registerAllComponents()` if needed.
- Add the type to **FieldSchemaMap.php** and **FieldRegistry** (PHP) for schema normalization and save/sanitize.
