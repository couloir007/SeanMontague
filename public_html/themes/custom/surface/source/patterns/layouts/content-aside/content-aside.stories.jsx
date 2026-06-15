import contentAside from './content-aside.twig';
import data from './content-aside.yml';

const settings = {
  title: 'Layouts/Content Aside',
};

export const Default = {
  render: (args) => contentAside(args),
  args: { ...data },
};

export default settings;
