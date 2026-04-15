import article from './article.twig';
import data from './article.yml';

const settings = {
  title: 'Collections/Article',
};

export const Default = {
  render: (args) => article(args),
  args: { ...data },
};

export default settings;
