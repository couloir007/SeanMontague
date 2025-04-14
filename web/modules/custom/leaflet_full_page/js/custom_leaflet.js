/* jshint esversion: 6 */
/* eslint-disable no-param-reassign */
/* eslint-disable func-names */
// eslint-disable-next-line no-redeclare
/* global mapItems, jQuery, Drupal, drupalSettings, once */

'use strict';

const theMap = {};
const resizeMap = {};
let geoJsonLayer;


(function ($, Drupal, drupalSettings, once) {

  $(document).bind('leaflet.map', function (event, map, lMap) {
    theMap.map = map;
    theMap.lMap = lMap;
    theMap.this = this;

    // Load KingdomTrails.geojson via an AJAX request
    $.getJSON('/sites/default/files/KingdomTrails.geojson', function(data) {
      // Add the GeoJSON layer to the map
      geoJsonLayer = L.geoJSON(data, {
        // Optional: add styling to the geojson features
        style: function(feature) {
          return {
            color: 'green',
            weight: 2,
            fillOpacity: 0.2
          };
        },
        // Optional: bind a popup for each feature
        onEachFeature: function(feature, layer) {
          if (feature.properties && feature.properties.name) {
            layer.bindPopup(feature.properties.name);
          }
        }
      }).addTo(lMap);
    });
  });



  function filterItems() {
    const favorite = [];

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

    $('.leaflet').hide();

    const theGetWidth = $('.leaflet__list-container').css('width');

    $('.leaflet').show();

    return theGetWidth;
  }

  function getNewLatLng(contentID) {
    const { latlng } = mapItems[contentID];
    const newLatLng = {};
    newLatLng.lng = parseFloat(latlng.lng) + 7;
    newLatLng.lat = latlng.lat;

    theMap.lMap.setView(newLatLng, 9);
    theMap.lMap.invalidateSize();
  }

  function getContent(contentID, which) {
    // if(which === 'map') {
    //   const itemsList = [];
    //   let itemTitle = '';
    //
    //   $.each(mapItems, function (key, item) {
    //     if(contentID === item.item_id) {
    //       itemsList.push(key);
    //       itemTitle = item.item_title;
    //     }
    //   });
    //
    //   if(itemsList.length === 1) {
    //     [contentID] = itemsList;
    //   } else {
    //     contentID = null;
    //
    //     $('li.map-item-list').hide();
    //
    //     $.each(itemsList, function (key, item) {
    //       $('.leaflet__list-container').removeClass('open');
    //       $('.leaflet__list-container').removeClass('container-up');
    //       $('.leaflet__content').hide();
    //       $(`#${item}`).show();
    //       $('.leaflet__top h1').hide();
    //       $('.leaflet__top h3').html(itemTitle).show();
    //     });
    //   }
    // }
    //
    // if(contentID != null) {
    //   $('.leaflet__content-title').html(mapItems[contentID].title);
    //   $('.leaflet__content-textarea').html(mapItems[contentID].body);
    //   $('.leaflet__content-textarea').append(mapItems[contentID].content);
    //
    //   $('.leaflet__content-textarea a').attr('target', '_blank');
    //
    //   const theWidth = getTheWidth();
    //
    //   if (theWidth === '100%') {
    //     $('.leaflet__content-main-image').attr('src', mapItems[contentID].image_url_mobile);
    //     $('.leaflet__list-container').addClass('container-up');
    //   } else {
    //     $('.leaflet__content-main-image').attr('src', mapItems[contentID].image_url_big);
    //     $('.leaflet__list-container').removeClass('container-up');
    //   }
    //
    //   if(mapItems[contentID].credit !== '') {
    //     $('.leaflet__content-main-figure figcaption').html(`<strong>Credit: </strong>${mapItems[contentID].credit}`);
    //     $('.leaflet__content-main-figure figcaption').show();
    //   }
    //
    //   $('.leaflet__content').attr('maps-nid', contentID);
    //
    //   $('.leaflet__content').show();
    //
    //   $('.leaflet__list-container').addClass('open');
    //
    //   $('.leaflet__content-scrollable').animate({ scrollTop: (0) }, 'fast');
    //
    //   $(this).delay(900).queue(function() {
    //     if($('.leaflet__list-container').hasClass('container-up')) {
    //       const headerHeight = $('.leaflet__content-header').height() + 7 + 30;
    //       $('.leaflet__content-scrollable').height($('.leaflet__content').height() - headerHeight - 20);
    //     }
    //   });
    // }
  }

  $(document).bind('leaflet.feature', function (event, lFeature, feature) {
    $(lFeature).click(function (e) {
      const newLatLng = {};
      // newLatLng.lng = e.originalEvent.latlng.lng + 7;
      // newLatLng.lat = e.originalEvent.latlng.lat;
      //
      // theMap.lMap.setView(newLatLng, 9);
      // theMap.lMap.invalidateSize();

      const contentID = feature.entity_id;

      ray(contentID);

      getContent(contentID, 'map');

      const headerHeight = $('.leaflet__content-header').height() + 7 + 30;
      $('.leaflet__content-scrollable').height($('.leaflet__content').height() - headerHeight - 20);
    });
  });

  Drupal.behaviors.surfaceMapItems = {
    attach (context) {
      $(once('setMapItems', '.leaflet__list-container', context)).each(function () {
        const filters = {};

        // Load the JSON data from the provided endpoint.
        $.getJSON('https://roundybrookfarm.com/mapitems', function(data) {
          console.log('Loaded map items:', data);
          // Process each data item as needed.
          $.each(data, function(index, item) {
            // For demonstration, simply log the id and label.
            console.log('Item:', item.id, item.label, item.field_media_image);

              $('.leaflet__list').append(`
                <li tabindex="-1" id="${item.id}" class="map-item-list">
                  <div class="thumb" style="background-image: url(${item.field_media_image});">
                  </div>
                  <div class="info">
                    <h2>${item.label}</h2>
                    <div class="teaser">${item.label}</div>
                    <div class="pub-date">2025</div>
                  </div>
                </li>
              `);
          });
        })
        .fail(function(jqXHR, textStatus, errorThrown) {
          console.error("Failed to load map items:", textStatus, errorThrown);
        });


        $('.leaflet-control.resetzoom div').empty();

        $('.custom-reset').prependTo('.leaflet-control.resetzoom div');

        $('.leaflet__list .map-item-list', context).on('click', function () {
          const contentID = $(this).attr('id');
          getContent(contentID, 'container');

          const headerHeight = $('.leaflet__content-header').height() + 7 + 30;
          $('.leaflet__content-scrollable').height($('.leaflet__content').height() - headerHeight - 20);

          getNewLatLng(contentID);

          return null;
        });

        $('.leaflet__content a.x-button', context).on('click', function (e) {
          e.preventDefault();

          theMap.lMap.setView(theMap.map.settings.center, theMap.map.settings.zoom);
          theMap.lMap.invalidateSize();

          $('.leaflet__list-container').removeClass('open');
          $('.leaflet__list-container').removeClass('container-up');
          $('.leaflet__content').hide();
          $('li.map-item-list').show();
          $('.leaflet__top h1').show();
          $('.leaflet__top h3').hide();

          filterItems();

          return null;
        });

        $('.leaflet__content a.arrow-left', context).on('click', function (e) {
          e.preventDefault();
          const nid = $('.leaflet__content').attr('maps-nid');

          let contentID = 0;

          if($(`#${nid}`).is(':first-child')) {
            contentID = $('.leaflet__list li').last().attr('id');
          } else {
            contentID = $(`#${nid}`).prev().attr('id');
          }

          getNewLatLng(contentID);
          getContent(contentID, 'container');

          return null;
        });

        $('.leaflet__content a.arrow-right', context).on('click', function (e) {
          e.preventDefault();
          const nid = $('.leaflet__content').attr('maps-nid');

          let contentID = 0;

          if($(`#${nid}`).is(':last-child')) {
            contentID = $('.leaflet__list li').first().attr('id');
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
    if(theWidth === '70%' && $('.leaflet__list-container').hasClass('container-up')) {
      $('.leaflet__list-container').removeClass('container-up');
    }

    const headerHeight = $('.leaflet__content-header').height() + 7 + 30;
    $('.leaflet__content-scrollable').height($('.leaflet__content').height() - headerHeight - 20);
  });

  resizeMap.adjust = function (which, mapid) {
    const windowHeight = $(window).height();
    const elementOffset = $('.region--content').offset().top;
    const mapHeight = windowHeight - elementOffset;

    $(`#${mapid}`, theMap.this).css('height', mapHeight);
    theMap.lMap.invalidateSize();
  };

  $(window).on('load', function() {
    resizeMap.adjust('Load', theMap.map.id);

    const headerHeight = $('.leaflet__content-header').height() + 7 + 30;
    $('.leaflet__content-scrollable').height($('.leaflet__content').height() - headerHeight - 20);
  });
})(jQuery, Drupal, drupalSettings, once);
