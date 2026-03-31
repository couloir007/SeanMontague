import footer from './surface-footer.twig';
import data from './surface-footer.yml';

const settings = {
  title: 'Components/Footer',
};

export const Default = {
  render: (args) => footer(args),
  args: { ...data },
};

export default settings;
