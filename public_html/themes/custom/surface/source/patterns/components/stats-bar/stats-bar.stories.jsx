/* jshint esversion: 9 */
import statsBar from './stats-bar.twig';
import data from './stats-bar.yml';

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
    modifier: 'three-col',
    stats: [
      { label: 'Distance', value: '8.4', unit: 'miles' },
      { label: 'Gain', value: '1,200', unit: 'ft' },
      { label: 'Difficulty', value: 'Easy', unit: 'green' },
    ],
  },
};

export default settings;
