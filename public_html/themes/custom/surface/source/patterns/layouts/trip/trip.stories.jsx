import layout from './trip.twig';
import data from '../../pages/trip/trip.yml';

const settings = {
  title: 'Layouts/Trip',
};

export const Default = {
  render: (args) => layout(args),
  args: { ...data.trip },
};

export default settings;
