import contact from './contact.twig';
import data from './contact.yml';

const settings = {
  title: 'Components/Contact',
};

export const Default = {
  render: (args) => contact(args),
  args: { ...data },
};

export default settings;
