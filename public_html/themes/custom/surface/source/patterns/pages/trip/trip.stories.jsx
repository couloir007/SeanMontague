import page from './trip.twig';
import data from './trip.yml';

const settings = {
  title: 'Pages/Trip',
  parameters: { layout: 'fullscreen' },
};

export const Default = {
  render: (args) => page(args),
  args: { ...data },
};

export const NoImage = {
  render: (args) => page(args),
  args: {
    ...data,
    trip: {
      ...data.trip,
      image_url: null,
    },
  },
};

export default settings;
