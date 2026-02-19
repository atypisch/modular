import { defineConfig } from 'vite';
import vue from '@vitejs/plugin-vue';
import { resolve } from 'path';

export default defineConfig({
  plugins: [vue()],
  build: {
    lib: {
      entry: resolve(__dirname, 'assets/js/wysiwyg-field-entry.js'),
      name: 'ModularWysiwygField',
      fileName: () => 'wysiwyg-field-bundle.js',
      formats: ['iife'],
    },
    outDir: resolve(__dirname, 'assets/js'),
    emptyOutDir: false,
    rollupOptions: {
      external: ['vue', 'tinymce'],
      output: {
        globals: {
          vue: 'Vue',
          tinymce: 'tinymce',
        },
        assetFileNames: (assetInfo) => {
          if (assetInfo.name && assetInfo.name.endsWith('.css')) return 'wysiwyg-field-bundle.css';
          return assetInfo.name || 'asset';
        },
      },
    },
    minify: true,
    sourcemap: false,
  },
  resolve: {
    alias: {
      '@': resolve(__dirname, 'assets/js'),
    },
  },
});
