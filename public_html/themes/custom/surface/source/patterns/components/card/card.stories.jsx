import card from './card.twig';
import data from './card.yml';

const settings = {
  title: 'Components/Card',
};

export const Trails = {
  render: (args) => card(args),
  args: { ...data },
};

export const Garden = {
  render: (args) => card(args),
  args: {
    category: 'Permaculture',
    category_key: 'garden',
    title: 'Components/Card',
    excerpt: 'Planting a food forest in Zone 5 Vermont is an exercise in patience and humility.',
    date: 'October 2024',
    readtime: '10 min read',
  },
};

export const Maps = {
  render: (args) => card(args),
  args: {
    category: 'Leaflet + Drupal',
    category_key: 'maps',
    title: 'Components/Card',
    excerpt: 'Notes from embedding interactive Leaflet maps using custom GeoJSON fields and Twig.',
    date: 'September 2024',
    readtime: '12 min read',
  },
};

export const Tech = {
  render: (args) => card(args),
  args: {
    category: 'Drupal',
    category_key: 'tech',
    title: 'Components/Card',
    excerpt: 'Notes from building a Drupal module for storing and rendering GeoJSON geometries.',
    date: 'March 2024',
    readtime: '9 min read',
  },
};

export default settings;
