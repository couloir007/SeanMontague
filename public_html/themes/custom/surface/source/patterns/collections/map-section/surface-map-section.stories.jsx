import mapSection from './surface-map-section.twig';
import data from './surface-map-section.yml';

const settings = {
  title: 'Collections/Map Section',
  parameters: {
    layout: 'fullscreen',
  },
};

export const Default = {
  render: (args) => mapSection(args),
  args: { ...data },
};

export default settings;
