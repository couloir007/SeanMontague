import article from './surface-article.twig';
import data from './surface-article.yml';

const settings = {
  title: 'Collections/Article',
};

export const Default = {
  render: (args) => article(args),
  args: { ...data },
};

export default settings;
