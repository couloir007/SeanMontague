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
    modifier: 'map--standalone',
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

// All marker types
export const MarkerTypes = {
  render: (args) => map(args),
  args: {
    map_id: 'marker-types-map',
    center: '53.3,-7.5',
    zoom: 7,
    interactive: 'true',
    modifier: 'map--standalone',
    markers: [
      { lat: 53.3445, lon: -6.2573, type: 'poi',         label: '<strong>Trinity College</strong><br>Point of Interest' },
      { lat: 53.2709, lon: -9.0522, type: 'destination',  label: '<strong>Galway</strong><br>Destination' },
      { lat: 52.0493, lon: -9.5065, type: 'lodging',      label: '<strong>AirBnB Killarney</strong><br>Lodging' },
      { lat: 53.1256, lon: -9.7668, type: 'trail',        label: '<strong>Dún Aonghasa</strong><br>Trail article' },
      { lat: 54.3071, lon: -9.4576, type: 'place',        label: '<strong>Céide Fields</strong><br>Place hub' },
    ],
  },
};

export default settings;
