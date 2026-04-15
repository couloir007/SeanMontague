import mapSection from './map-section.twig';
import data from './map-section.yml';

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
