import marquee from './marquee.twig';
import data from './marquee.yml';

const settings = {
  title: 'Components/Marquee',
};

export const Default = {
  render: (args) => marquee(args),
  args: { ...data },
};

export default settings;
