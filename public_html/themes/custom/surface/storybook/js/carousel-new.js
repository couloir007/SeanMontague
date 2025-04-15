/* eslint-disable */
(function (Drupal) {
  Drupal.behaviors.carouselNew = {
    attach(context) {

      const carousels = document.querySelectorAll( '.carousel-new', context );
      for ( let i = 0; i <= carousels.length; i++ ) {
        new Splide( carousels[i], {
          type: 'loop',
          gap: '20px',
          perPage: 3,
          pagination: false,
          arrows: true,
          rewind: true,
          breakpoints: {
            540: {
              perPage: 1,
            },
            1024: {
              perPage: 2,
            },
          },
        } ).mount();
      }
    }
  };
}(Drupal));
