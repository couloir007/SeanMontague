
import typography from './typography.twig';

const settings = {
  title: 'Base/Typography',
};

const Typography = {
  name: 'Typography',
  render: (args) => typography(args),
};

export default settings;
export { Typography };
