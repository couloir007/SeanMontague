import sideCard from './side-card.twig';
import data from './side-card.yml';

const settings = {
  title: 'Components/Side Card',
};

export const Default = {
  render: (args) => sideCard(args),
  args: { ...data.default },
};

export const Conditions = {
  render: (args) => sideCard(args),
  args: { ...data.conditions },
};

export const Trails = {
  render: (args) => sideCard(args),
  args: { ...data.trails },
};

export const POI = {
  render: (args) => sideCard(args),
  args: { ...data.poi },
};

export default settings;
