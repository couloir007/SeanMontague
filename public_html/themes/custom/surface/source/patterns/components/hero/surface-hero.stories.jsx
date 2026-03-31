import hero from './surface-hero.twig';
import data from './surface-hero.yml';

const settings = {
  title: 'Components/Hero',
  parameters: {
    layout: 'fullscreen',
  },
};

export const Default = {
  render: (args) => hero(args),
  args: { ...data },
};

export const NoMap = {
  render: (args) => hero(args),
  args: {
    ...data,
    map_markers: [],
    map_lines: [],
  },
};

export default settings;
