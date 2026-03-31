
import footer from './site-footer.twig';
import data from './site-footer.yml';

const settings = {
  title: 'Layouts/Site Footer',
};

export const SiteFooter = {
  name: 'Site footer',
  render: (args) => footer(args),
  args: { ...data },
};

export default settings;
