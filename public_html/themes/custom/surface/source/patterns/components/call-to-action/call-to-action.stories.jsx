
import cta from './call-to-action.twig';
import data from './call-to-action.yml';

const settings = {
  title: 'Components/Call to action',
};

export const CTA = {
  name: 'Call to action',
  render: (args) => cta(args),
  args: { ...data },
};

export default settings;
