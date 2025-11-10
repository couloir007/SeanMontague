
// Imports decorators for background colors.

import video from './video.twig';
import data from './video.yml';

const settings = {
  title: 'Components/Video',
};

export const Video = {
  name: 'Video',
  render: (args) => video(args),
  args: { ...data },
};

export default settings;
