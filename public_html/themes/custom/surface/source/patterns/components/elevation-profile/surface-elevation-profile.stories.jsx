import elevationProfile from './surface-elevation-profile.twig';
import data from './surface-elevation-profile.yml';

const settings = {
  title: 'Components/Elevation Profile',
  parameters: {
    notes: 'Requires elev_data attribute (JSON array of [lon, lat, ft] from GPX) to render the chart. Stats header renders from yml data.',
  },
};

export const Default = {
  render: (args) => elevationProfile(args),
  args: { ...data },
};

export default settings;
