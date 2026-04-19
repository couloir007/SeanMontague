import destIndex from './dest-index.twig';
import data from './dest-index.yml';

const settings = {
  title: 'Components/Dest Index',
};

export const Ireland = {
  render: (args) => destIndex(args),
  args: { ...data },
};

export const Short = {
  render: (args) => destIndex(args),
  args: {
    label: 'Route',
    title: 'NEK',
    title_em: 'highlights',
    destinations: [
      { name: 'Kingdom Trails', region: 'Burke, VT', lat: 44.593, lon: -71.918 },
      { name: 'Burke Mountain', region: 'East Burke, VT', lat: 44.578, lon: -71.877 },
      { name: 'Willoughby Lake', region: 'Westmore, VT', lat: 44.747, lon: -72.044 },
    ],
  },
};

export default settings;
