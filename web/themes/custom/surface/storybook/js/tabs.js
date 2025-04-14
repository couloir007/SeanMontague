/* eslint-disable */

Drupal.behaviors.primaryTabs = {
  attach(context, settings) {
    once('primaryTabs', '.tabs__link', context).forEach((tab) => {
      Drupal.primaryTabs.init(tab.closest('.tabs'));
    });
  }
};

Drupal.primaryTabs = {
  init: function (tabsContainer) {
    const tabs = tabsContainer.querySelectorAll('.tabs__link');
    const tabsContent = tabsContainer.querySelectorAll('.tabs__content');

    tabs.forEach((tab, i) => {
      tab.addEventListener('click', function (e) {
        e.preventDefault();
        tabs.forEach(tab => tab.classList.remove('is-active'));
        this.classList.add('is-active');
        tabsContent.forEach(content => content.classList.add('hidden'));
        tabsContent[i].classList.remove('hidden');
      });
    });
  },
};
