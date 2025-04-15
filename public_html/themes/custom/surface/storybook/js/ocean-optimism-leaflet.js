/* jshint esversion: 6 */
/* eslint-disable no-param-reassign */
/* eslint-disable func-names */
// eslint-disable-next-line no-redeclare
/* global mapItems, jQuery, Drupal, drupalSettings, once */

'use strict';

const theMap = {};
const resizeMap = {};

(function ($, Drupal, drupalSettings, once) {

  $(document).bind('leaflet.map', function (event, map, lMap) {
    theMap.map = map;
    theMap.lMap = lMap;
    theMap.this = this;
  });

  function filterItems() {
    const favorite = [];
    $.each($('#views-exposed-form-leaflet-map-media-ocean-map input:checked'), function(){
      favorite.push($(this).val());
    });

    if (favorite.length === 0) {
      $('li.map-item-list').show();
    } else {
      $('li.map-item-list').hide();

      $.each(favorite, function( key, item ) {
        $(`li.map-item-list.filter_${item}`).show();
      });
    }

    resizeMap.adjust('Ajax Load', theMap.map.id);
  }

  function getTheWidth() {
    $('.op-leaflet').hide();

    const theGetWidth = $('.op-leaflet__list-container').css('width');

    $('.op-leaflet').show();

    return theGetWidth;
  }

  function getNewLatLng(contentID) {
    const { latlng } = mapItems[contentID];
    const newLatLng = {};
    newLatLng.lng = parseFloat(latlng.lng) + 7;
    newLatLng.lat = latlng.lat;

    theMap.lMap.setView(newLatLng, 6);
    theMap.lMap.invalidateSize();
  }

  function getContent(contentID, which) {
    if(which === 'map') {
      const itemsList = [];
      let itemTitle = '';

      $.each(mapItems, function (key, item) {
        if(contentID === item.item_id) {
          itemsList.push(key);
          itemTitle = item.item_title;
        }
      });

      if(itemsList.length === 1) {
        [contentID] = itemsList;
      } else {
        contentID = null;

        $('li.map-item-list').hide();

        $.each(itemsList, function (key, item) {
          $('.op-leaflet__list-container').removeClass('open');
          $('.op-leaflet__list-container').removeClass('container-up');
          $('.op-leaflet__content').hide();
          $(`#${item}`).show();
          $('.op-leaflet__top h1').hide();
          $('.op-leaflet__top h3').html(itemTitle).show();
        });
      }
    }

    if(contentID != null) {
      $('.op-leaflet__content-title').html(mapItems[contentID].title);
      $('.op-leaflet__content-textarea').html(mapItems[contentID].body);
      $('.op-leaflet__content-textarea').append(mapItems[contentID].content);

      $('.op-leaflet__content-textarea a').attr('target', '_blank');

      const theWidth = getTheWidth();

      if (theWidth === '100%') {
        $('.op-leaflet__content-main-image').attr('src', mapItems[contentID].image_url_mobile);
        $('.op-leaflet__list-container').addClass('container-up');
      } else {
        $('.op-leaflet__content-main-image').attr('src', mapItems[contentID].image_url_big);
        $('.op-leaflet__list-container').removeClass('container-up');
      }

      if(mapItems[contentID].credit !== '') {
        $('.op-leaflet__content-main-figure figcaption').html(`<strong>Credit: </strong>${mapItems[contentID].credit}`);
        $('.op-leaflet__content-main-figure figcaption').show();
      }

      $('.op-leaflet__content').attr('maps-nid', contentID);

      $('.op-leaflet__content').show();

      $('.op-leaflet__list-container').addClass('open');

      $('.op-leaflet__content-scrollable').animate({ scrollTop: (0) }, 'fast');

      $(this).delay(900).queue(function() {
        if($('.op-leaflet__list-container').hasClass('container-up')) {
          const headerHeight = $('.op-leaflet__content-header').height() + 7 + 30;
          $('.op-leaflet__content-scrollable').height($('.op-leaflet__content').height() - headerHeight - 20);
        }
      });
    }
  }

  $(document).bind('leaflet.feature', function (event, lFeature, feature) {
    $(lFeature).click(function (e) {
      const newLatLng = {};
      newLatLng.lng = e.originalEvent.latlng.lng + 7;
      newLatLng.lat = e.originalEvent.latlng.lat;

      theMap.lMap.setView(newLatLng, 6);
      theMap.lMap.invalidateSize();

      const contentID = feature.entity_id;

      getContent(contentID, 'map');

      const headerHeight = $('.op-leaflet__content-header').height() + 7 + 30;
      $('.op-leaflet__content-scrollable').height($('.op-leaflet__content').height() - headerHeight - 20);
    });
  });

  Drupal.behaviors.surfaceMapItems = {
    attach (context) {
      $(once('setMapItems', '.op-leaflet__list-container', context)).each(function () {
        const filters = {};

        $.each(mapItems, function( key, item ) {
          let filter = '';
          if(item.filter !== 'null') {
            // alert(item.filter);
            filter = ` filter_${item.filter}`;
            filters[item.filter] = item.filter;
          }

          const theWidth = getTheWidth();
          const preImage = new Image();
          if (theWidth === '100%') {
            preImage.src = item.image_url_mobile;
          } else {
            preImage.src = item.image_url_big;
          }

          $('.op-leaflet__list').append(`
            <li tabindex="-1" id="${key}" class="map-item-list${filter}">
              <div class="thumb" style="background-image: url(${item.image_url});">
              </div>
              <div class="info">
                <h2>${item.title}</h2>
                <div class="teaser">${item.sub_title}</div>
                <div class="pub-date">${item.date}</div>
              </div>
            </li>
          `);
        });

        $('.leaflet-control.resetzoom div').empty();

        $('.custom-reset').prependTo('.leaflet-control.resetzoom div');

        $('.op-leaflet__list .map-item-list', context).on('click', function () {
          const contentID = $(this).attr('id');
          getContent(contentID, 'container');

          const headerHeight = $('.op-leaflet__content-header').height() + 7 + 30;
          $('.op-leaflet__content-scrollable').height($('.op-leaflet__content').height() - headerHeight - 20);

          getNewLatLng(contentID);

          return null;
        });

        $('.op-leaflet__content a.x-button', context).on('click', function (e) {
          e.preventDefault();

          theMap.lMap.setView(theMap.map.settings.center, theMap.map.settings.zoom);
          theMap.lMap.invalidateSize();

          $('.op-leaflet__list-container').removeClass('open');
          $('.op-leaflet__list-container').removeClass('container-up');
          $('.op-leaflet__content').hide();
          $('li.map-item-list').show();
          $('.op-leaflet__top h1').show();
          $('.op-leaflet__top h3').hide();

          filterItems();

          return null;
        });

        $('.op-leaflet__content a.arrow-left', context).on('click', function (e) {
          e.preventDefault();
          const nid = $('.op-leaflet__content').attr('maps-nid');

          let contentID = 0;

          if($(`#${nid}`).is(':first-child')) {
            contentID = $('.op-leaflet__list li').last().attr('id');
          } else {
            contentID = $(`#${nid}`).prev().attr('id');
          }

          getNewLatLng(contentID);
          getContent(contentID, 'container');

          return null;
        });

        $('.op-leaflet__content a.arrow-right', context).on('click', function (e) {
          e.preventDefault();
          const nid = $('.op-leaflet__content').attr('maps-nid');

          let contentID = 0;

          if($(`#${nid}`).is(':last-child')) {
            contentID = $('.op-leaflet__list li').first().attr('id');
          } else {
            contentID = $(`#${nid}`).next().attr('id');
          }

          getNewLatLng(contentID);
          getContent(contentID, 'container');

          return null;
        });
      });
    }
  };

  Drupal.behaviors.surfaceMapFilterCallback = {
    attach () {
      $(document).on('ajaxComplete', function () {
        if (drupalSettings && drupalSettings.views && drupalSettings.views.ajaxViews) {
          const { ajaxViews } = drupalSettings.views;
          Object.keys(ajaxViews || {}).forEach(function (i) {
            if (ajaxViews[i].view_name === "leaflet_map_media" && ajaxViews[i].view_display_id === "ocean_map") {
              filterItems();
            }
          });
        }
      });
    },
  };

  $(window).resize(function () {
    resizeMap.adjust('Resize', theMap.map.id);

    const theWidth = getTheWidth();
    if(theWidth === '70%' && $('.op-leaflet__list-container').hasClass('container-up')) {
      $('.op-leaflet__list-container').removeClass('container-up');
    }

    const headerHeight = $('.op-leaflet__content-header').height() + 7 + 30;
    $('.op-leaflet__content-scrollable').height($('.op-leaflet__content').height() - headerHeight - 20);
  });

  resizeMap.adjust = function (which, mapid) {
    const windowHeight = $(window).height();
    const elementOffset = $('.region--content').offset().top;
    const mapHeight = windowHeight - elementOffset;

    $(`#${mapid}`, theMap.this).css('height', mapHeight);
    theMap.lMap.invalidateSize();
  };

  $(window).on('load', function() {
    // $('.custom-reset').prependTo('.views-exposed-form.bef-exposed-form .fieldset-wrapper');
    //
    // $('.svg-inline--fa').on('click', function (e) {
    //   e.preventDefault();
    //   theMap.lMap.setView(theMap.map.settings.center, theMap.map.settings.zoom);
    //   theMap.lMap.invalidateSize();
    // });

    resizeMap.adjust('Load', theMap.map.id);

    const headerHeight = $('.op-leaflet__content-header').height() + 7 + 30;
    $('.op-leaflet__content-scrollable').height($('.op-leaflet__content').height() - headerHeight - 20);
  });
})(jQuery, Drupal, drupalSettings, once);
