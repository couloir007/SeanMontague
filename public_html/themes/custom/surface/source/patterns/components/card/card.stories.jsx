import card from './card.twig';
import data from './card.yml';

const settings = {
  title: 'Components/Card',
};

export const EventCard = {
  name: 'Event card',
  render: (args) => card(args),
  args: { ...data },
};

export const ArticleCard = {
  ...EventCard,
  name: 'Article card',
  args: {
    ...data,
    date: 'October 31, 2024',
    date_short: '',
    event_type: '',
    more_dates: false,
    short_date_month: '',
    meta_description: 'Integer posuere erat a ante venenatis dapibus posuere velit aliquet.',
    organization: '',
    readtime: '5 min read',
  },
};

export default settings;
