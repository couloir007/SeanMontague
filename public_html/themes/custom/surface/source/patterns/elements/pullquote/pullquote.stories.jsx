import pullquote from './pullquote.twig';
import data from './pullquote.yml';

const settings = {
  title: 'Elements/Pullquote',
};

export const Pullquote = {
  render: (args) => pullquote(args),
  args: { ...data },
};

export default settings;
