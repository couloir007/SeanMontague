
import site_navigation from './site-navigation.twig';
import data from './site-navigation.yml';

const settings = {
  title: 'Layouts/Site navigation',
};

export const Stacked = {
  name: 'Site navigation',
  render: (args) => site_navigation(args),
  args: { ...data },
};

export default settings;
