import about from './about.twig';
import data from './about.yml';

const settings = {
  title: 'Components/About',
};

export const Default = {
  render: (args) => about(args),
  args: { ...data },
};

export default settings;
