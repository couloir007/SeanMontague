import nav from './surface-nav.twig';
import data from './surface-nav.yml';

const settings = {
  title: 'Components/Nav',
  parameters: { layout: 'fullscreen' },
};

export const Default = {
  render: (args) => nav(args),
  args: { ...data },
};

export const Scrolled = {
  render: (args) => nav(args),
  args: { ...data, modifier: 'surface-nav--scrolled' },
};

export const Article = {
  render: (args) => nav(args),
  args: {
    logo_text: 'Sean Montague <span>/ Burke, VT</span>',
    logo_url: '/',
    back_text: 'All Writing',
    back_url: '/#writing',
  },
};

export default settings;
