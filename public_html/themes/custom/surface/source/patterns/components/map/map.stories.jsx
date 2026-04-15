import map from './map.twig';
import data from './map.yml';

const settings = {
  title: 'Components/Map',
  parameters: {
    layout: 'fullscreen',
    notes: 'Requires Leaflet CSS/JS loaded in the page. Configure entirely via data-attributes — no JS changes needed per use.',
  },
};

export const Default = {
  render: (args) => map(args),
  args: { ...data, modifier: 'map--standalone' },
};

export const HeroBackground = {
  render: (args) => map(args),
  args: {
    ...data,
    map_id: 'hero-map',
    zoom: 11,
    interactive: 'false',
  },
};

export const FeaturedMap = {
  render: (args) => map(args),
  args: {
    ...data,
    map_id: 'featured-map',
    zoom: 14,
    center: '44.594,-71.913',
    modifier: 'map--standalone',
  },
};

export default settings;
