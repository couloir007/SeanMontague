import { defineConfig } from 'vite';
import twigDrupal from 'vite-plugin-twig-drupal';

export default defineConfig({
  plugins: [twigDrupal()],
  root: '.',
  base: './',
  build: {
    outDir: 'dist',
    emptyOutDir: true,
    rollupOptions: {
      input: {
        main: './js/main.js',
        styles: './src/styles/styles.css',
      },
      output: {
        entryFileNames: '[name].js',
        assetFileNames: '[name].css',
      },
    },
  },
});
