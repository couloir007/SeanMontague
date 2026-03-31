import about from './surface-about.twig';
import data from './surface-about.yml';

const settings = {
  title: 'Components/About',
};

export const Default = {
  render: (args) => about(args),
  args: { ...data },
};

export default settings;
