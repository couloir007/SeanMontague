import destTimeline from './dest-timeline.twig';
import data from './dest-timeline.yml';

const settings = {
  title: 'Components/Dest Timeline',
};

export const Ireland = {
  render: (args) => destTimeline(args),
  args: { ...data },
};

export default settings;
