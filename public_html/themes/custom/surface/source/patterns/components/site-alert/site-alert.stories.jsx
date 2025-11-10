
import siteAlert from './site-alert.twig';
import data from './site-alert.yml';

const settings = {
  title: 'Components/Site Alert',
};

export const SiteAlert = {
  name: 'Site Alert',
  render: (args) => siteAlert(args),
  args: { ...data},
};

export default settings;
