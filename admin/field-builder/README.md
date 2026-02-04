# Field Builder (Vue + PrimeVue)

Per `docs/MODULAR_CUSTOM_FIELDS_SPEC.md`:

- **Layout:** Links = Field Library (search, accordion categories, drag handle). Rechts = Canvas (vertical sortable, drop indicators, nesting).
- **Field settings:** Inline panel, tabs: General, Validation, Display, Logic. Geen modal chaos.
- **UI:** Alleen Vue 3 + PrimeVue (Input, Select, MultiSelect, Dialog, Tabs, Accordion). Geen theming/kleurkeuzes.

Huidige implementatie: `admin/post-types/field-builder.php` (PHP + SortableJS). Migratie naar Vue + PrimeVue in deze folder is gepland.
