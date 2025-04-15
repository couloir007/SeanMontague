/* jshint esversion: 6 */
/* eslint-disable no-param-reassign */
/* eslint-disable func-names */
// eslint-disable-next-line no-redeclare
/* global jQuery, Drupal, drupalSettings, once */

'use strict';

(function ($, Drupal, drupalSettings, once) {

  let slidePos = 0;
  let slideLen = 0;
  let slideInt;
  let restart;

  function changeIt() {
    $('.slideshow_item').fadeOut(1000);
    $(`.slideshow_item:eq(${slidePos})`).fadeIn(1000);
  }

  function setAuto() {
    slidePos = (slidePos === slideLen ? 0 : (slidePos + 1));
    changeIt();
  }

  Drupal.behaviors.surfaceSlideShow = {
    attach (context) {
      $(once('surfaceSlideShow', '.open-popup-link', context)).each(function () {
        $('.open-popup-link').magnificPopup({
          type: 'inline',
          gallery: {
            enabled: true,
            navigateByImgClick: true,
            preload: [0, 2], // Will preload 0 - before current, and 2 after
          },
          callbacks: {
            change () {
              // Removed unused variable: isLogged
              // const isLogged = $('body').hasClass('user-logged-in');
            },
            open () {
              // Removed unused variable: selectorID
              // const selectorID = this.content.selector;
              // checkSizeSlide(selectorID);
            },
            afterChange () {
              // Removed unused variable: selectorID
              // const selectorID = this.content.selector;
              // checkSizeSlide(selectorID);
            },
            resize () {
              // Removed unused variable: selectorID
              // const selectorID = this.content.selector;
              // checkSizeSlide(selectorID);
            },
          }
        });
      });
    }
  };

  $(document).ready(function () {
    const $next = $('.next');
    const $prev = $('.prev');

    slideLen = $('.slideshow_item').length - 1;

    $('.slideshow_item:gt(0)').hide();

    slideInt = setInterval(function () {
      setAuto();
    }, 5000);

    $next.click(function () {
      if (slidePos < slideLen) {
        clearInterval(slideInt);
        clearTimeout(restart);
        slidePos += 1; // Using operator assignment (+=)
        changeIt();
        restart = setTimeout(function () {
          slideInt = setInterval(function () {
            setAuto();
          }, 5000);
        }, 500);
      }
      return false;
    });

    $prev.click(function () {
      if (slidePos > 0) {
        clearInterval(slideInt);
        clearTimeout(restart);
        slidePos -= 1; // Replaced slidePos-- with explicit subtraction
        changeIt();
        restart = setTimeout(function () {
          slideInt = setInterval(function () {
            setAuto();
          }, 5000);
        }, 500);
      }
      return false;
    });
  });
})(jQuery, Drupal, drupalSettings, once);
