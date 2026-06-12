import page from './article.twig';
import data from './article.yml';
import articleData from '../../collections/article/article.yml';

const settings = {
  title: 'Pages/Article',
  parameters: { layout: 'fullscreen' },
};

export const Default = {
  render: (args) => page(args),
  args: { ...data, article: { ...articleData } },
};

export default settings;
