/**
 * Build entry for Modular WYSIWYG field (TinyMCE).
 * Output: wysiwyg-field-bundle.js (IIFE). Expects Vue and TinyMCE on window.
 * Register in field-renderer: app.component('wysiwyg-field', window.ModularWysiwygField);
 */
import WysiwygField from './components/fields/WysiwygField.vue';

if (typeof window !== 'undefined') {
  window.ModularWysiwygField = WysiwygField;
}
