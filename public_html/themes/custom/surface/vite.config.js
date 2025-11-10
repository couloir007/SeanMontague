import { defineConfig } from 'vite';
import yml from '@modyfi/vite-plugin-yaml';
import twig from 'vite-plugin-twig-drupal';
import { join } from 'node:path';
import path from 'node:path';
import { glob } from 'glob';

export default defineConfig({
  plugins: [
    // Twig namespaces for nesting components.
    twig({
      namespaces: {
        assets: join(__dirname, './source/assets'),
        base: join(__dirname, './source/patterns/base'),
        components: join(__dirname, './source/patterns/components'),
        layouts: join(__dirname, './source/patterns/layouts'),
        theme: join(__dirname, './source/patterns/theme'),
      },
    }),
    // Allows Storybook to read data from YAML files.
    yml(),
  ],
  build: {
    emptyOutDir: true,
    outDir: 'dist',
    rollupOptions: {
      input: glob.sync(path.resolve(__dirname, './source/patterns/**/*.{css,js}')),
      output: {
        assetFileNames: 'css/[name].css',
        entryFileNames: 'js/[name].js',
      },
    },
  },
  publicDir: 'source/assets',
  css: {
    devSourcemap: true,
  },
});
