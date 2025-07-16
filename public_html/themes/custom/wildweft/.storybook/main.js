module.exports = {
  stories: ['../components/**/*.stories.@(js|ts)'],
  framework: {
    name: '@storybook/html-vite',
    options: {}
  },
  addons: [
    '@storybook/addon-essentials',
    '@storybook/addon-links',
    '@storybook/addon-themes',
    '@storybook/addon-interactions',
    '@storybook/blocks'
  ]
};
