import component from './destination-cards.twig';
import data from '../../pages/trip/trip.yml';

export default {
  title: 'Components/Destination Cards',
};

export const Default = {
  render: (args) => component(args),
  args: {
    items: data.trip.destinations,
  },
};
