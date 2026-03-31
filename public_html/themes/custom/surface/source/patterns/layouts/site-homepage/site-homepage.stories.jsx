import site_homepage from './site-homepage.twig';
import data from './site-homepage.yml';

const settings = {
  title: 'Layouts/Site Homepage',
};

export const Default = {
  render: (args) => site_homepage(args),
  args: { ...data },
};

export default settings;
