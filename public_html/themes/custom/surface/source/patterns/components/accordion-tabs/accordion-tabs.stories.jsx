
// Imports decorators for background colors.

import tabs from './accordion-tabs.twig';
import data from './accordion-tabs.yml';

const settings = {
  title: 'Components/Accordion tabs',
};

export const AccordionTabs = {
  name: 'Accordion tabs',
  render: (args) => tabs(args),
  args: { ...data },
  loaders: [
    async () => ({
      ...(await import('./accordion-tabs')).default,
    })
  ],
};

export default settings;
