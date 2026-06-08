import section from './section.twig';
import data from './section.yml';

const settings = {
  title: 'Components/Section',
};

// Default — uses the `content` slot fallback from the yml fixture.
export const Default = {
  render: (args) => section(args),
  args: { ...data },
};

// Writing — 3-up cards grid.
export const Writing = {
  render: (args) => section(args),
  args: {
    ...data,
    id: 'writing',
    eyebrow: 'Writing',
    grid_modifier: 'section__grid--cards',
  },
};

// Places — surface-tinted variant, 2-up grid.
export const Places = {
  render: (args) => section(args),
  args: {
    ...data,
    id: 'places',
    eyebrow: 'Places & Projects',
    modifier: 'section--alt',
    grid_modifier: 'section__grid--places',
    content:
      '<div class="places-card"><span class="places-card__name">Demo place</span></div>',
  },
};

export default settings;
