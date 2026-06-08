import destHybrid from './dest-hybrid.twig';
import data from './dest-hybrid.yml';

const settings = {
  title: 'Components/Dest Hybrid',
  parameters: {
    layout: 'fullscreen',
  },
};

const Default = () => destHybrid(data);

const FewStops = () => destHybrid({
  ...data,
  header_title: '<em>Three</em> stops, <em>one</em> coast',
  map_caption_meta: '3 stops · ~120km by road',
  destinations: data.destinations.slice(0, 3),
});

export default settings;
export { Default, FewStops };
