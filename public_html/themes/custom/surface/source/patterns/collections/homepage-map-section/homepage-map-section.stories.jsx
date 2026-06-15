import mapSection from './homepage-map-section.twig';
import data from './homepage-map-section.yml';

const settings = {
  title: 'Collections/Homepage Map Section',
  parameters: {
    layout: 'fullscreen',
  },
};

export const Default = {
  render: (args) => mapSection(args),
  args: { ...data },
};

export default settings;
