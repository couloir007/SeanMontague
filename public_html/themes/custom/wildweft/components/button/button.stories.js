import buttonTemplate from './button.twig';

export default {
  title: 'Components/Button',
};
export const Default = () => buttonTemplate({ label: 'Click me' });
