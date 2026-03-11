// rollup.config.js
import resolve from '@rollup/plugin-node-resolve';
import { terser } from 'rollup-plugin-terser';
import fs from 'fs';

const config = [];

const sourceDirectories = [
  './'
];

sourceDirectories.forEach(function (directory) {
  const jsFiles = fs.readdirSync(`${directory}source/js`);

  jsFiles.forEach(function (file) {
    // Do whatever you want to do with the file
    config.push({
      input: `${directory}source/js/${file}`,
      external: ['Drupal', 'jQuery', 'drupalSettings'],
      plugins: [
        // Resolve bare module specifiers to relative paths
        resolve(),
      ],
      context: "window",
      output: [
        {
          file: `${directory}build/js/${file}`,
          format: 'iife',
          globals: {
            Drupal: 'Drupal',
            drupalSettings: 'drupalSettings',
            jQuery: '$',
          },
        },
        {
          file: `${directory}build/js/${file.replace(/\.js$/, '.min.js')}`,
          format: 'iife',
          plugins: [
            // Minify
            terser()
          ],
          globals: {
            Drupal: 'Drupal',
            drupalSettings: 'drupalSettings',
            jQuery: '$',
          },
        },
      ]
    });
  });
});

export default config;
