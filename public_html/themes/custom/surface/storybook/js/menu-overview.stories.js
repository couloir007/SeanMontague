
// import {
//   withBackgroundDark,
// } from '../../../../.storybook/decorators';
// decorators: [withBackgroundDark],

import menu from './menu-overview.twig';
import data from './menu-overview.yml';

const settings = {
  title: 'Components/Menu overview',
};

export const menuOverview = {
  name: 'Menu Overview',
  render: (args) => parse(menu(args)),
  args: { ...data },
};

export default settings;
