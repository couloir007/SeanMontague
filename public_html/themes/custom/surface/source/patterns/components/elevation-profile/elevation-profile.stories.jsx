import elevationProfile from './elevation-profile.twig';
import data from './elevation-profile.yml';

const settings = {
  title: 'Components/Elevation Profile',
  parameters: {
    notes:
      'Renders from the elev_data attribute (JSON array of [lon, lat, ft]). ' +
      'At runtime the profile is mode-aware: only walking/hiking/cycling tracks ' +
      'get a chart. With multiple tracks, selection is driven by map clicks — ' +
      'clicking a track dispatches surface-track-select and the profile swaps to ' +
      'that track (or hides via .elevation-profile--hidden for driving/ferry). ' +
      'That interaction needs a live map, so it is not exercised in Storybook.',
  },
};

// (a) Eligible single-track — renders the chart from elev_data, as today.
export const Default = {
  render: (args) => elevationProfile(args),
  args: { ...data },
};

// The hidden state the JS toggles when there is no eligible track or an
// ineligible track is selected. Renders nothing visible by design.
export const Hidden = {
  render: (args) => elevationProfile(args),
  args: { ...data, modifier: 'elevation-profile--hidden' },
};

export default settings;
