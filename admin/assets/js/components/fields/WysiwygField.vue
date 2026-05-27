<template>
  <div class="modular-wysiwyg-field">
    <Editor
      v-if="mounted"
      :id="editorId"
      :model-value="localContent"
      :init="editorInit"
      :tinymce-script-src="tinymceScriptSrc"
      model-events="change"
      output-format="html"
      @update:model-value="onEditorInput"
    />
  </div>
</template>

<script>
import Editor from '@tinymce/tinymce-vue';

const BASE_PLUGINS = 'lists link paste';
// Basic: echt minimale set
const BASIC_PLUGINS = BASE_PLUGINS;
// Full: compact maar met wat extra opties, nog steeds icon-only
const FULL_PLUGINS = BASE_PLUGINS + ' code table charmap hr autoresize';

// Toolbar: blocks (Heading/Paragraph) eerst, dan iconen; extra items in overflow (toolbar_mode: sliding)
const BASIC_TOOLBAR = 'blocks | undo redo | bold italic | bullist numlist | link blockquote | removeformat';
const FULL_TOOLBAR = 'blocks | undo redo | bold italic underline | bullist numlist blockquote | alignleft aligncenter alignright | link | table charmap hr | removeformat | code';

const SAFE_INIT = {
  min_height: 220,
  content_style: 'body { padding: 0 10px; min-height: 220px; font-family: inherit; }',
  placeholder: 'Write content...',
  branding: false,
  promotion: false,
  // Handgreep onderaan om de editor verticaal te resizen
  resize: true,
  // Alleen icon-toolbar, geen tekst-menubalk
  menubar: false,
  // Overtollige iconen schuiven in een \"»\" overflow, vergelijkbaar met WordPress
  toolbar_mode: 'sliding',
  // Paste: clean Word junk, optional strip inline styles, safe HTML
  paste_as_text: false,
  paste_remove_styles_if_webkit: true,
  paste_remove_styles: false,
  paste_merge_formats: true,
  paste_data_images: true,
  // Security: allow semantic HTML but block script/events
  valid_elements: '*[*]',
  invalid_elements: 'script,iframe,object,embed,form,input,button',
  extended_valid_elements: 'div[*],p[*],span[*],a[*],strong,em,u,s,ul,ol,li,h1,h2,h3,h4,h5,h6,table,thead,tbody,tr,th,td,hr,blockquote,pre,code,img[*]',
  invalid_styles: {},
  verify_html: true,
  // No inline JS or event attributes
  allow_script_urls: false,
  allow_conditional_comments: false,
};

export default {
  name: 'WysiwygField',
  components: { Editor },
  props: {
    modelValue: { type: String, default: '' },
    field: { type: Object, default: () => ({}) },
    editorMode: { type: String, default: 'full' },
    placeholder: { type: String, default: 'Write content...' },
    disabled: { type: Boolean, default: false },
    readonly: { type: Boolean, default: false },
  },
  emits: ['update:modelValue'],
  data() {
    const id = (this.field && (this.field.name || this.field.key)) || 'wysiwyg';
    const uid = typeof Math.random === 'function' ? Math.random().toString(36).slice(2, 10) : 'ed';
    const initial = this.normalizeValue(this.modelValue);
    return {
      editorId: `wysiwyg-${id}-${uid}`,
      localContent: initial,
      mounted: false,
      lastEmitted: initial,
    };
  },
  computed: {
    baseUrl() {
      return typeof window !== 'undefined' && window.MODULAR_TINYMCE_BASE_URL
        ? window.MODULAR_TINYMCE_BASE_URL
        : '/modular/admin/assets/vendor/tinymce';
    },
    tinymceScriptSrc() {
      return this.baseUrl.replace(/\/$/, '') + '/tinymce.min.js';
    },
    editorInit() {
      const mode = this.editorMode === 'basic' ? 'basic' : 'full';
      const plugins = mode === 'basic' ? BASIC_PLUGINS : FULL_PLUGINS;
      const toolbar = mode === 'basic' ? BASIC_TOOLBAR : FULL_TOOLBAR;
      return {
        ...SAFE_INIT,
        base_url: this.baseUrl,
        suffix: '.min',
        placeholder: this.placeholder,
        plugins,
        toolbar,
        setup: (editor) => {
          editor.on('init', () => {
            const val = this.normalizeValue(this.modelValue);
            if (val !== editor.getContent()) {
              editor.setContent(val);
            }
          });
        },
      };
    },
  },
  watch: {
    modelValue(next) {
      const normalized = this.normalizeValue(next);
      if (normalized === this.lastEmitted) return;
      this.localContent = normalized;
    },
  },
  mounted() {
    this.localContent = this.normalizeValue(this.modelValue);
    this.lastEmitted = this.localContent;
    this.mounted = true;
  },
  beforeUnmount() {
    this.mounted = false;
  },
  methods: {
    normalizeValue(v) {
      if (v === undefined || v === null) return '';
      const s = String(v).trim();
      return s;
    },
    onEditorInput(content) {
      const normalized = content === undefined || content === null ? '' : String(content).trim();
      if (normalized === this.lastEmitted) return;
      this.lastEmitted = normalized;
      this.localContent = normalized;
      this.$emit('update:modelValue', normalized);
    },
  },
};
</script>

<style scoped>
.modular-wysiwyg-field {
  width: 100%;
  border: 1px solid var(--border-color, #e5e7eb);
  border-radius: var(--radius-sm, 4px);
  outline: none;
}
/* Alleen bij focus: rand donkerder grijs, geen blauw */
.modular-wysiwyg-field:focus-within {
  border-color: #6b7280;
  box-shadow: none;
}
.modular-wysiwyg-field :deep(.tox-tinymce) {
  border-radius: var(--radius-sm, 4px);
  border: none !important;
}
/* Buitenste TinyMCE-container: geen rand */
.modular-wysiwyg-field :deep(.tox-tinymce:focus-within) {
  border: none !important;
  outline: none;
  box-shadow: none;
}
/* Binnenste focusrand (rond het typveld): grijs i.p.v. blauw */
.modular-wysiwyg-field :deep(.tox .tox-edit-area),
.modular-wysiwyg-field :deep(.tox .tox-edit-area:focus),
.modular-wysiwyg-field :deep(.tox .tox-edit-area:focus-within),
.modular-wysiwyg-field :deep(.tox .tox-edit-area.tox-edit-area--focused) {
  border-color: #6b7280 !important;
  box-shadow: none !important;
}
.modular-wysiwyg-field :deep(.tox .tox-edit-area__iframe) {
  min-height: 200px;
  outline: none !important;
}
.modular-wysiwyg-field :deep(.tox .tox-editor-container) {
  min-height: 200px;
}
</style>
