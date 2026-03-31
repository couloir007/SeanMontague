import marquee from './surface-marquee.twig';
import data from './surface-marquee.yml';

const settings = {
  title: 'Components/Marquee',
};

export const Default = {
  render: (args) => marquee(args),
  args: { ...data },
};

export default settings;
