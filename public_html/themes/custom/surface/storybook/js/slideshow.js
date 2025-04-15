/* eslint-disable */

(function (Drupal) {
  Drupal.behaviors.newSlideshow = {
    attach(context) {
      const sliderNew = new Splide( '.slideshow__main', {
        type: 'fade',
        rewind: true,
        pagination: false,
        arrows: true,
      }, context );

      const thumbnails = new Splide( '.slideshow__thumbnails', {
        fixedWidth: 200,
        fixedHeight: 86,
        gap: 1,
        rewind: true,
        pagination: false,
        isNavigation: true,
        breakpoints : {
          1024: {
            fixedWidth: 140,
            fixedHeight: 60,
          },
        },
      } );

      sliderNew.sync( thumbnails );
      sliderNew.mount();
      thumbnails.mount();
    }
  };
}(Drupal));
