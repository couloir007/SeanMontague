import metaStrip from './meta-strip.twig';
import data from './meta-strip.yml';

const settings = {
  title: 'Components/Meta Strip',
  parameters: {
    layout: 'fullscreen',
  },
};

export const Default = {
  render: (args) => metaStrip(args),
  args: { ...data },
};

export const NoBadge = {
  render: (args) => metaStrip(args),
  args: {
    ...data,
    badge: null,
  },
};

export const Minimal = {
  render: (args) => metaStrip(args),
  args: {
    category: 'Writing',
    date: 'June 5, 2026',
  },
};

export default settings;
