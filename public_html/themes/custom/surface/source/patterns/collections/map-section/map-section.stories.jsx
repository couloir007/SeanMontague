import articleMapSection from './map-section.twig';
import data from './map-section.yml';

const settings = {
  title: 'Collections/Map Section',
};

export const Default = {
  render: (args) => articleMapSection(args),
  args: { ...data },
};

export default settings;
