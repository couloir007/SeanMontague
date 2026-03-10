import featuredCard from './featured-card.twig';
import featuredData from './featured-card.yml';

const settings = {
  title: 'Components/Featured Card',
};

export const FeaturedCard = {
  render: (args) => featuredCard(args),
  args: { ...featuredData },
};

export default settings;
