import links from './links.twig';
import data from './links.yml';

const settings = {
  title: 'Elements/Links',
};

export const Default = {
  render: (args) => links(args),
  args: { ...data },
};

export default settings;
