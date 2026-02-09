# Field Definition Schema (canoniek)

**Dit document is leidend. Geen afwijking.**

## Uitgangspunten

- **Field definition ≠ field rendering.** Configuratie bepaalt alles.
- **PrimeVue = UI-only.** Geen data-structuur, validatie of opslag in PrimeVue.
- **Elke field heeft exact 3 lagen:** Definition (Post Type Config) → Renderer (Editor UI) → Output (PHP + JSON).
- **Geen externe dependencies.** Alles lokaal; PrimeVue reeds geïntegreerd.

## Canoniek veldschema (per field)

Elke field in de builder volgt exact dit schema:

```json
{
  "type": "wysiwyg",
  "key": "content",
  "label": "Content",
  "instructions": "",
  "required": false,
  "default": null,
  "ui": {
    "component": "Editor",
    "props": {
      "toolbar": "full",
      "placeholder": "Write content..."
    }
  },
  "storage": {
    "format": "html",
    "column": "content"
  },
  "output": {
    "php": "$post->content",
    "json": "content"
  }
}
```

### Verplichte top-level keys

| Key | Type | Beschrijving |
|-----|------|--------------|
| `type` | string | CMS field type (text, wysiwyg, select, …). |
| `key` | string | Unieke sleutel (slug). Gebruikt als `name` in forms en als JSON key. |
| `label` | string | Weergavenaam in UI. |
| `instructions` | string | Hulptekst onder het veld. |
| `required` | boolean | Verplicht veld. |
| `default` | mixed | Standaardwaarde (type-afhankelijk). |
| `ui` | object | Zie hieronder. |
| `storage` | object | Zie hieronder. |
| `output` | object | Zie hieronder. |

### `ui` (alleen configuratie voor renderer)

| Key | Type | Beschrijving |
|-----|------|--------------|
| `ui.component` | string | PrimeVue componentnaam: InputText, Textarea, Editor, Dropdown, MultiSelect, Calendar, FileUpload, AutoComplete, Checkbox, RadioButton, SelectButton, TreeSelect, … |
| `ui.props` | object | Props doorgegeven aan het PrimeVue-component (placeholder, toolbar, optionLabel, multiple, timeOnly, etc.). |

De **Field Builder** bewerkt alleen dit schema. Geen live data-rendering in de builder.

### `storage`

| Key | Type | Beschrijving |
|-----|------|--------------|
| `storage.format` | string | Waardeformaat: string, html, json, media_id, array, post_id, term_ids, user_id, … |
| `storage.column` | string | Logische kolomnaam (voor documentatie/API). Meestal gelijk aan `key`. |

### `output`

| Key | Type | Beschrijving |
|-----|------|--------------|
| `output.php` | string | Beschrijving voor PHP-gebruik (bijv. `$post->content`). |
| `output.json` | string | JSON-key in API-response (meestal gelijk aan `key`). |

## Backward compatibility

- Bestaande schema's gebruiken vaak `name` i.p.v. `key`. Renderer en opslag accepteren beide; bij opslaan vanuit de builder wordt `key` leidend en `name` gelijkgetrokken.
- Ontbrekende `ui`, `storage` of `output` worden door de renderer ingevuld via de mapping (zie FIELD_MAPPING.md).
