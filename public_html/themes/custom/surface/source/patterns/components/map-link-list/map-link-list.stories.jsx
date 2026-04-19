import mapLinkList from './map-link-list.twig';
import data from './map-link-list.yml';

const settings = {
  title: 'Components/Map Link List',
};

export const Default = {
  render: (args) => mapLinkList(args),
  args: { ...data },
};

export const WithExternal = {
  render: (args) => mapLinkList(args),
  args: {
    items: [
      { label: 'Trail Map PDF', url: '#', external: true },
      { label: 'KT Trail Conditions', url: '#', external: true },
      { label: 'Burke Mountain', url: '#', external: true },
    ],
  },
};

export default settings;
