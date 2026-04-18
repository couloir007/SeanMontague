import footer from './footer.twig';
import data from './footer.yml';

const settings = {
  title: 'Components/Footer',
};

export const Default = {
  render: (args) => footer(args),
  args: { ...data },
};

export default settings;
