'use strict';

((Drupal, once) => {
  Drupal.behaviors.surfaceLightbox = {
    attach: function attach(context) {
      Drupal.surfaceLightbox.init(context);
    },
  };

  Drupal.surfaceLightbox = {
    init: function (context) {
      once('surfaceLightboxInit', '.glightbox', context).forEach(() => {
        const lightbox = GLightbox({});
      });
    }
  };
})(Drupal, once);
