import placesCard from './surface-places-card.twig';
import data from './surface-places-card.yml';

const settings = {
  title: 'Components/Places Card',
};

export const Default = {
  render: (args) => placesCard(args),
  args: { ...data },
};

export default settings;
