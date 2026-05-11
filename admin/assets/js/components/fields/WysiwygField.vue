<template>
  <div class="modular-wysiwyg-field">
    <!-- Text-only mode: plain textarea -->
    <textarea
      v-if="tabs === 'text'"
      class="modular-wysiwyg-textarea"
      :value="localContent"
      :placeholder="placeholder"
      :disabled="disabled"
      :readonly="readonly"
      @input="onTextareaInput"
    ></textarea>

    <!-- Delay mode: show placeholder until user clicks -->
    <div
      v-else-if="delay && !initialized"
      class="modular-wysiwyg-delay-placeholder"
      @click="initialized = true"
    >
      <i class="fas fa-paragraph" style="margin-right:0.5rem;opacity:0.5;"></i>
      Click to load editor
    </div>

    <!-- Visual editor (TinyMCE) -->
    <Editor
      v-else-if="mounted"
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
const BASIC_PLUGINS = BASE_PLUGINS;
const FULL_PLUGINS = BASE_PLUGINS + ' code table charmap hr autoresize';

const BASIC_TOOLBAR = 'blocks | undo redo | bold italic | bullist numlist | link blockquote | removeformat';
const FULL_TOOLBAR = 'blocks | undo redo | bold italic underline | bullist numlist blockquote | alignleft aligncenter alignright | link | table charmap hr | removeformat | code';

const SAFE_INIT = {
  min_height: 220,
  content_style: 'body { padding: 0 10px; min-height: 220px; font-family: inherit; }',
  placeholder: 'Write content...',
  branding: false,
  promotion: false,
  resize: true,
  menubar: false,
  toolbar_mode: 'sliding',
  paste_as_text: false,
  paste_remove_styles_if_webkit: true,
  paste_remove_styles: false,
  paste_merge_formats: true,
  paste_data_images: true,
  valid_elements: '*[*]',
  invalid_elements: 'script,iframe,object,embed,form,input,button',
  extended_valid_elements: 'div[*],p[*],span[*],a[*],strong,em,u,s,ul,ol,li,h1,h2,h3,h4,h5,h6,table,thead,tbody,tr,th,td,hr,blockquote,pre,code,img[*]',
  invalid_styles: {},
  verify_html: true,
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
    media_upload: { type: Boolean, default: true },
    tabs: { type: String, default: 'all' },
    delay: { type: Boolean, default: false },
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
      initialized: !this.delay,
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
      let plugins = mode === 'basic' ? BASIC_PLUGINS : FULL_PLUGINS;
      let toolbar = mode === 'basic' ? BASIC_TOOLBAR : FULL_TOOLBAR;

      // tabs=visual: remove code plugin and button
      if (this.tabs === 'visual') {
        plugins = plugins.replace(/\s*code\s*/g, ' ').trim();
        toolbar = toolbar.replace(/\|\s*code\s*/g, '').replace(/code\s*\|/g, '').replace(/\bcode\b/g, '').trim();
      }

      // media_upload: add image plugin and toolbar button
      if (this.media_upload) {
        plugins = plugins + ' image';
        toolbar = toolbar + ' | image';
      }

      const uploadUrl = (typeof window !== 'undefined' ? (window.MODULAR_BASE_URL || '') : '') +
        '/admin/media/upload-handler.php?_csrf_token=' +
        (typeof window !== 'undefined' ? (window.MODULAR_CSRF_TOKEN || '') : '');

      const init = {
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

      // Add image upload handler when media_upload is enabled
      if (this.media_upload) {
        init.images_upload_handler = (blobInfo) => {
          return new Promise((resolve, reject) => {
            const formData = new FormData();
            formData.append('file', blobInfo.blob(), blobInfo.filename());
            fetch(uploadUrl, { method: 'POST', body: formData, credentials: 'same-origin', headers: { 'X-Requested-With': 'XMLHttpRequest' } })
              .then(r => r.json())
              .then(data => {
                if (data.success && data.url) resolve(data.url);
                else if (data.success && data.file_path) resolve(data.file_path);
                else reject('Upload failed');
              })
              .catch(() => reject('Upload failed'));
          });
        };
        init.automatic_uploads = true;
        init.file_picker_types = 'image';
      }

      return init;
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
    onTextareaInput(e) {
      const val = e.target.value || '';
      this.lastEmitted = val;
      this.localContent = val;
      this.$emit('update:modelValue', val);
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
/* Text-only textarea */
.modular-wysiwyg-textarea {
  width: 100%;
  min-height: 220px;
  padding: 10px;
  border: none;
  border-radius: var(--radius-sm, 4px);
  font-family: monospace;
  font-size: 0.9rem;
  line-height: 1.5;
  resize: vertical;
  outline: none;
  box-sizing: border-box;
}
/* Delay placeholder */
.modular-wysiwyg-delay-placeholder {
  min-height: 220px;
  display: flex;
  align-items: center;
  justify-content: center;
  cursor: pointer;
  color: var(--text-muted, #6b7280);
  font-size: 0.9rem;
  background: var(--surface, #f9fafb);
  border-radius: var(--radius-sm, 4px);
}
.modular-wysiwyg-delay-placeholder:hover {
  background: var(--hover-bg, #f3f4f6);
  color: var(--text, #374151);
}
</style>
