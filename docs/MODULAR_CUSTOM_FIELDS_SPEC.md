# MODULAR CMS — ACF-waardige Custom Fields (definitieve specificatie)

**Dit document is leidend. Afwijking is een bug.**

## Context

- Bestaande PHP-backend + Vue (admin). Geen greenfield.
- Post Types, Users, Modules, Settings bestaan. Architectuur: `admin/`, `core/`, `public/`.
- Bestaande functies en componenten herordenen; niet herschrijven zonder noodzaak.

## Doel

- Volledig ACF-waardig Custom Field-systeem voor Post Types.
- Admin definieert velden per Post Type; velden type-specifiek en volledig configureerbaar.
- Rendering: correct in Admin (edit post) en Frontend (theming/modules).
- UX: slick, intuïtief, drag & drop, lichtgewicht. Geen willekeurige styling.

## Fundamentale keuzes (vast)

| Onderdeel | Keuze |
|-----------|--------|
| UI | Vue 3 + **PrimeVue alleen** als UI-primitieve (Input, Select, MultiSelect, Dialog, Tabs, Accordion) |
| UI beperking | Geen PrimeVue theming, geen kleurkeuzes door Cursor, geen layout-experimenten |
| Icons | FontAwesome (solid), alleen functioneel |

## Post Type-modes

| Mode | Velden | Volgorde | Door editors |
|------|--------|----------|--------------|
| **FIXED** | Vast | Vast | Niet wijzigbaar |
| **MODULAR** | Admin bepaalt toegestane types | — | Velden toevoegen, volgorde aanpassen. Admin kan "all fields allowed" instellen |

## Field catalog (geen afwijking)

- **Basic:** Text, Textarea, Number, Email, URL, Password, Range, Search
- **Content:** WYSIWYG, Markdown, Code Editor
- **Choice:** Select (single), Select (multiple), Checkbox, Radio, Button Group, True/False (toggle)
- **Media:** Image, Gallery, File, Audio, Video, oEmbed
- **Relational:** Post Object (single/multiple), Relationship, User, Taxonomy
- **Layout/Structure:** Group, Repeater, Flexible Content, Accordion, Tabs, Clone
- **Special:** Date, DateTime, Time, Color Picker, Icon Picker, Google Map, Hidden, Message/Info

## Verplichte field settings (alle types)

- Label, Name (slug/key), Instructions, Required (bool), Default value, Conditional logic (show/hide)

## Relationship field (geen vereenvoudiging)

- Allowed Post Types (multi), Allowed Taxonomies (multi), Allowed Status (draft/published/private)
- Min/Max items, Return format (ID/Object/Custom array), Search enabled, Filters (post type, taxonomy)
- UI mode: List / Cards / Compact, Drag reorder (bool)
- UX: search input, live results, keyboard navigable, drag reorder bij meerdere

## Repeater field (zeer strikt)

- Min/Max rows, Layout (Table/Block/Cards), Collapsed row label (subfield), Button label, Subfields (Field Builder)
- UX: Add row, drag rows, collapse/expand, duplicate row, delete row (confirm)

## Flexible Content field (modular content)

- Allowed layouts (checkbox list), Layout label, Layout icon (FontAwesome), Min/Max layouts, Default layouts
- UX: "Add section" modal, searchable layout list, drag layouts, collapse, duplicate

## Field Builder UX (admin) — verplicht

- **Layout:** Links = Field Library (search, categories accordion, field item: icon, label, type, drag handle). Rechts = Canvas (vertical sortable list, drop indicators, nesting zichtbaar).
- **Field item UI:** Icon, label, type badge, required indicator, collapse toggle, drag handle, delete (confirm).
- **Field settings UI:** Inline panel (geen modal chaos). Tabs: General, Validation, Display, Logic.

## Rendering-architectuur (verplicht)

```
admin/
  field-builder/    # Field Builder UI (Vue + PrimeVue)
  post-types/
  api/
  ui/

core/
  fields/
    registry.php
    types/
      text.php, textarea.php, wysiwyg.php, image.php, gallery.php,
      relationship.php, repeater.php, flexible.php, ...
  validation/
  storage/
  api/

public/
  themes/
  modules/
  uploads/
```

## Field contract (elk type)

Elk field type implementeert:

- `renderAdmin($field, $value)` — admin edit UI
- `renderPublic($field, $value)` — frontend/theming
- `sanitize($value)` — voor opslag
- `validate($value)` — validatie

**Niet toegestaan:** conditionals in templates, fallback naar text/textarea.

## API & datastructuur

- Post Type schema: fields, order, settings, mode (fixed/modular).
- Post content: gestructureerd, type-aware, Repeater/Flexible als geneste arrays. Geen HTML-blobs.

## UX & styling

- Geen random kleuren, geen custom spacing, geen creatieve layouts.
- Functioneel > visueel. ACF-achtige helderheid. Intuïtief zonder uitleg.

## Definitie van “klaar”

- Elk field type rendert visueel correct.
- Elk field heeft volledige instellingen.
- Relationship, Repeater, Flexible Content productie-waardig.
- Admin UX op ACF-niveau of beter.
- Frontend rendering type-aware.

---

## Implementatiestatus (mapping bestaande code)

| Spec-element | Huidige staat |
|--------------|----------------|
| Field contract | Huidig: `adminConfig`, `adminInput`, `sanitize`, `normalize`, `frontend`. Spec: `renderAdmin`, `renderPublic`, `sanitize`, `validate`. Mapping: adapter of interface-uitbreiding. |
| Field Builder | `admin/post-types/field-builder.php` (PHP + SortableJS). Spec: Vue + PrimeVue, `admin/field-builder/`. |
| PrimeVue | Nog niet toegevoegd. |
| Folder structuur | `core/fields/` plat (types als bestanden). Spec: `core/fields/registry.php` + `core/fields/types/*.php`. |
| Ontbrekende types | Range, Search, Hidden toegevoegd. Nog niet: Clone, Color Picker, Icon Picker, Google Map (oEmbed = embed). |
| Post type mode | Bestaat (`fixed`/`modular` in DB). Modular UX (editors kunnen velden toevoegen/herschikken) nog uit te werken. |

---

## Fase 1 (uitgevoerd)

- `docs/MODULAR_CUSTOM_FIELDS_SPEC.md` — dit document.
- `.cursor/rules/modular-custom-fields.mdc` — Cursor-regel: spec leidend bij field/field-builder wijzigingen.
- **PrimeVue:** Vue 3 + PrimeVue (UMD + primevue.min.css, primeicons) in admin header; geen theming.
- **Structuur:** `admin/field-builder/` (README), `core/Validation/FieldValidator.php`, `core/storage/`, `core/api/`, `core/fields/FieldContractInterface.php`.
- **Field contract:** `FieldContractInterface`: `renderAdmin($field, $value)`, `renderPublic($field, $value)`, `sanitize($value)`, `validate($value, $field)`. Bestaande types gebruiken nog `FieldInterface`; migratie type-voor-type. **FieldContractAdapter** implementeert het contract rond bestaande FieldInterface; `FieldRegistry::getContract($type, $schema)` retourneert adapter of het veld zelf als het het contract al implementeert.

- **Database:** Queries die `u.name` / `u1.name` gebruikten zijn aangepast naar `COALESCE(u.username, u.email, 'Unknown')` (users-tabel heeft geen `name`-kolom). Forms-tabel komt uit migratie 001_core_schema; bij ontbrekende tabellen: `admin/install-tables-direct.php` of `admin/sql/add_users_deleted_at.php` uitvoeren.

Dit bestand wordt bijgewerkt bij elke fase.
