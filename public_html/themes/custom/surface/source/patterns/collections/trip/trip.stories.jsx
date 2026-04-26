import trip from './trip.twig';
import data from './trip.yml';

const settings = {
  title: 'Collections/Trip',
  parameters: {
    layout: 'fullscreen',
  },
};

export const IrelandSpring2024 = {
  render: (args) => trip(args),
  args: { ...data },
};

export const NoImage = {
  render: (args) => trip(args),
  args: {
    ...data,
    image_url: null,
  },
};

export default settings;
