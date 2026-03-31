import contact from './surface-contact.twig';
import data from './surface-contact.yml';

const settings = {
  title: 'Components/Contact',
};

export const Default = {
  render: (args) => contact(args),
  args: { ...data },
};

export default settings;
