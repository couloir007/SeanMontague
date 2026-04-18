import articleMapSection from './article-map-section.twig';
import data from './article-map-section.yml';

const settings = {
  title: 'Collections/Article Map Section',
};

export const Default = {
  render: (args) => articleMapSection(args),
  args: { ...data },
};

export default settings;
