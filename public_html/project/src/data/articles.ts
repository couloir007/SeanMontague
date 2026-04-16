export interface Article {
  slug: string;
  category: string;
  categoryColor: 'trail' | 'forest' | 'sky' | 'stone';
  title: string;
  excerpt: string;
  date: string;
  readTime: string;
}

export const articles: Article[] = [
  {
    slug: 'kingdom-trails-hidden-singletrack',
    category: 'Kingdom Trails',
    categoryColor: 'trail',
    title: 'The Best Singletrack in Vermont Nobody Talks About',
    excerpt: 'Kingdom Trails has 100+ miles but most visitors ride the same ten. Here\'s what\'s hiding in the back of the map.',
    date: 'March 2025',
    readTime: '8 min read',
  },
  {
    slug: 'first-ride-kingdom-trails',
    category: 'Burke Mountain',
    categoryColor: 'trail',
    title: 'A Season of Skiing Burke: Notes from a Local',
    excerpt: 'Burke doesn\'t have the terrain of Stowe but it has something better — solitude, character, and a vertical that earns it.',
    date: 'February 2025',
    readTime: '6 min read',
  },
  {
    slug: 'food-forest-year-three',
    category: 'Permaculture',
    categoryColor: 'forest',
    title: 'Year Three of the Food Forest: What Actually Worked',
    excerpt: 'Planting a food forest in Zone 5 Vermont is an exercise in patience and humility. Three years in, here\'s the honest report.',
    date: 'October 2024',
    readTime: '10 min read',
  },
  {
    slug: 'leaflet-drupal-trail-map',
    category: 'Leaflet + Drupal',
    categoryColor: 'sky',
    title: 'Building a Trail Map with Leaflet and Drupal',
    excerpt: 'Notes from embedding interactive Leaflet maps in Drupal using custom GeoJSON fields and Twig — the approach behind this site\'s maps.',
    date: 'September 2024',
    readTime: '12 min read',
  },
  {
    slug: 'sheet-mulching-nek',
    category: 'Permaculture',
    categoryColor: 'forest',
    title: 'Sheet Mulching on a Slope: Lessons from the NEK',
    excerpt: 'Sheet mulching works. Sheet mulching on a 20% grade in Vermont mud season is a different problem entirely.',
    date: 'May 2024',
    readTime: '7 min read',
  },
  {
    slug: 'geojson-drupal-field',
    category: 'Drupal',
    categoryColor: 'stone',
    title: 'GeoJSON as a Drupal Field Type: A Case Study',
    excerpt: 'Notes from building a Drupal module for storing and rendering GeoJSON geometries as interactive Leaflet maps.',
    date: 'March 2024',
    readTime: '9 min read',
  },
];

export const categoryColors: Record<Article['categoryColor'], string> = {
  trail:  'var(--trail)',
  forest: 'var(--forest)',
  sky:    'var(--sky)',
  stone:  'var(--stone)',
};
