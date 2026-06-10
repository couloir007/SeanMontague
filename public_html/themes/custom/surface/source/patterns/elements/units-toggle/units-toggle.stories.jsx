import unitsToggle from './units-toggle.twig';
import data from './units-toggle.yml';

const settings = {
  title: 'Elements/Units Toggle',
  parameters: {
    notes:
      'Two-state metric/imperial control for the site nav. With no localStorage ' +
      'or drupalSettings it resolves via navigator.language (US/LR/MM → imperial, ' +
      'else metric), so the active state reflects that on load. Clicking flips ' +
      'the active state, writes localStorage.elevationUnit, and dispatches a ' +
      'surface-units-change event. The live profile re-render only happens on a ' +
      'page that actually contains an elevation-profile.',
  },
};

export const Default = {
  render: (args) => unitsToggle(args),
  args: { ...data },
};

export default settings;
