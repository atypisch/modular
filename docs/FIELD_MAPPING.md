# Definitieve mapping: CMS Field → PrimeVue → Opslag → Output

**Cursor moet deze mapping afdwingen. Geen random component-keuzes.**

## Content fields

| CMS Field   | PrimeVue Component     | Opslag  | PHP output   | JSON key |
|-------------|-------------------------|---------|--------------|----------|
| text        | InputText               | string  | $post->key   | key      |
| textarea    | Textarea                | string  | $post->key   | key      |
| wysiwyg     | Editor                  | html    | $post->key   | key      |
| code        | Textarea (+ syntax flag)| string  | $post->key   | key      |
| markdown    | Textarea                | string  | $post->key   | key      |
| embed       | InputText + preview     | url     | $post->key   | key      |

## Media fields

| CMS Field   | PrimeVue    | Opslag     | PHP output   | JSON key |
|-------------|-------------|------------|--------------|----------|
| image       | FileUpload  | media_id   | $post->key   | key      |
| file        | FileUpload  | media_id   | $post->key   | key      |
| gallery     | FileUpload (multiple) | array of ids | $post->key | key (array) |
| audio       | FileUpload  | media_id   | $post->key   | key      |
| video       | FileUpload  | media_id   | $post->key   | key      |

## Choice / relation

| CMS Field      | PrimeVue           | Opslag    | PHP output   | JSON key |
|----------------|--------------------|-----------|--------------|----------|
| select         | Dropdown           | string    | $post->key   | key      |
| multi select   | MultiSelect        | array     | $post->key   | key      |
| checkbox       | Checkbox           | bool      | $post->key   | key      |
| radio          | RadioButton        | string    | $post->key   | key      |
| button_group   | SelectButton       | string    | $post->key   | key      |
| true_false     | Checkbox / ToggleSwitch | bool  | $post->key   | key      |
| post_object    | AutoComplete       | post_id   | $post->key   | key      |
| relationship   | AutoComplete (multiple) | array  | $post->key   | key      |
| taxonomy       | TreeSelect         | term_ids  | $post->key   | key      |
| user           | Dropdown / AutoComplete | user_id | $post->key | key      |

## Basic (tekst/nummer)

| CMS Field   | PrimeVue   | Opslag  | PHP output   | JSON key |
|-------------|------------|---------|--------------|----------|
| email       | InputText  | string  | $post->key   | key      |
| url         | InputText  | string  | $post->key   | key      |
| number      | InputNumber| number  | $post->key   | key      |
| range       | Slider     | number  | $post->key   | key      |
| password    | Password   | string  | $post->key   | key      |
| search      | InputText  | string  | $post->key   | key      |
| hidden      | (geen UI)  | string  | $post->key   | key      |

## Date / time

| CMS Field        | PrimeVue              | Opslag  | PHP output   | JSON key |
|------------------|------------------------|---------|--------------|----------|
| date_picker      | Calendar               | string (Y-m-d) | $post->key | key   |
| time_picker      | Calendar (timeOnly)    | string (H:i)  | $post->key | key   |
| date_time_picker | Calendar               | string (Y-m-d H:i) | $post->key | key |

## Layout / structure (geen 1:1 PrimeVue; custom op PrimeVue-design)

| CMS Field        | Functie                    | Opslag / output |
|------------------|----------------------------|------------------|
| group            | Field nesting              | object met child keys |
| repeater         | Array of field schemas     | array of objects |
| flexible_content | Component/layout switcher  | array of blocks  |
| message          | UI only (geen opslag)      | —                |
| accordion        | UI only                    | —                |
| tab              | UI only                    | —                |
| divider          | UI only                    | —                |

## Default ui.props per component (waar van toepassing)

- **Editor:** toolbar (full | basic), placeholder, readonly. *ACF equivalent: Visual/Text tabs + toolbar.*
- **Dropdown / MultiSelect:** optionLabel, optionValue, placeholder, showClear (Dropdown), display (chip | comma).
- **Calendar:** dateFormat, timeOnly, showTime, hourFormat.
- **FileUpload:** multiple (gallery), accept (image/*, etc.), mode (advanced | basic). *ACF: Image/File/Gallery use media picker; we store media_id(s).*
- **AutoComplete:** (post_object/relationship) dropdown, multiple, minLength, suggestions from API. *ACF: Post Object / Relationship with search + selected list.*
- **InputText / Textarea:** placeholder, maxlength, readonly.

## Layout / utility (ACF-style, geen PrimeVue 1:1)

- **Accordion:** Collapsible section header in the editor (like ACF Accordion). No storage; label = section title.
- **Tab:** Section divider with tab label (like ACF Tab). No storage.
- **Message / Divider:** Instructions or horizontal rule; no storage.
