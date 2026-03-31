import statsBar from './surface-stats-bar.twig';
import data from './surface-stats-bar.yml';

const settings = {
  title: 'Components/Stats Bar',
};

export const Default = {
  render: (args) => statsBar(args),
  args: { ...data },
};

export const ThreeStat = {
  render: (args) => statsBar(args),
  args: {
    stats: [
      { label: 'Distance', value: '8.4', unit: 'miles' },
      { label: 'Gain', value: '1,200', unit: 'ft' },
      { label: 'Difficulty', value: 'Easy', unit: 'green' },
    ],
  },
};

export default settings;
