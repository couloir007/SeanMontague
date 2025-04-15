/* eslint-disable */
(function (Drupal) {
  Drupal.behaviors.Gallery = {
    attach(context, settings) {
      const lightbox = GLightbox({
        touchNavigation: true,
        loop: true,
        autoplayVideos: true,
        openEffect: 'zoom',
        closeEffect: 'zoom',
        keyboardNavigation: true
      }, context);
    }
  };
}(Drupal));
