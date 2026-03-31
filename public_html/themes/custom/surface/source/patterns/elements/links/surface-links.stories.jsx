import links from './surface-links.twig';
import data from './surface-links.yml';

const settings = {
  title: 'Elements/Links',
};

export const Default = {
  render: (args) => links(args),
  args: { ...data },
};

export default settings;
