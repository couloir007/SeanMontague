
// Imports decorators for background colors.

import accordion from './accordion.twig';
import data from './accordion.yml';
import './accordion';

const settings = {
  title: 'Components/Accordion',
};

export const Accordion = {
  name: 'Accordion',
  render: (args) => accordion(args),
  args: { ...data },
};

export default settings;
