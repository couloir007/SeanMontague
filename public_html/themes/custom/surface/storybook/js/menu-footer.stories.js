
import menu from './menu-footer.twig';
import data from './menu-footer.yml';

const settings = {
  title: 'Components/Menu footer',
};

export const menuFooter = {
  name: 'Menu footer',
  render: (args) => parse(menu(args)),
  args: { ...data }
};

export default settings;
