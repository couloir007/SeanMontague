import articleHeader from './article-header.twig';
import data from './article-header.yml';

const settings = {
  title: 'Collections/Article Header',
};

export const Default = {
  render: (args) => articleHeader(args),
  args: { ...data },
};

export const TrailCategory = {
  render: (args) => articleHeader(args),
  args: {
    ...data,
    category: 'Burke Mountain',
    category_key: 'trail',
    difficulty: undefined,
    title: 'Components/Article Header',
    subtitle: 'Components/Article Header',
  },
};

export const Drupal = {
  render: (args) => articleHeader(args),
  args: {
    ...data,
    category: 'Drupal',
    category_key: 'tech',
    difficulty: undefined,
    date: 'September 2024',
    title: 'Components/Article Header',
    subtitle: 'Components/Article Header',
  },
};

export default settings;
