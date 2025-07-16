/* jshint esversion: 6 */
/* eslint-disable no-param-reassign */
/* eslint-disable func-names */
// eslint-disable-next-line no-redeclare
/* global mapItems, jQuery, Drupal, drupalSettings, once */

'use strict';

/**
 * A map object used as a key-value store.
 * The variable is a plain JavaScript object, commonly utilized for storing and retrieving data.
 * Keys are typically strings, with associated values potentially of any data type.
 */
const theMap = {};
/**
 * An object used for mapping resize-related data. This variable acts as a container
 * to store and manage associations or configurations required for resize operations.
 *
 * The structure and purpose of this mapping depend on the specific use case. It may
 * include properties or key-value pairs representing elements, dimensions, or
 * configurations linked to resizing functionality.
 */
const resizeMap = {};
/**
 * Represents a GeoJSON layer for mapping and visualizing geographical data.
 * This variable is used to handle and display GeoJSON features on a map.
 *
 * The GeoJSON layer supports dynamic rendering and can be utilized to
 * overlay shapes, points, and other geometries in GeoJSON format.
 *
 * Features of the GeoJSON may include styling, events, and interaction
 * capabilities to customize the map behavior as per the application's needs.
 *
 * The data model for GeoJSON should adhere to the GeoJSON format specifications,
 * encapsulating geometry objects and their associated properties.
 *
 * Common use cases may involve rendering regions, points of interest,
 * or other geographical features derived from GeoJSON datasets.
 */
let geoJsonLayer;

(function ($, Drupal, drupalSettings, once) {

  $(document).on('leaflet.map', function (event, map, lMap) {
    theMap.map = map;
    theMap.lMap = lMap;
    theMap.this = this;

    // Load KingdomTrails.geojson via an AJAX request
    $.getJSON('/sites/default/files/KingdomTrails.geojson', function (data) {
      // Add the GeoJSON layer to the map
      geoJsonLayer = L.geoJSON(data, {
        // Optional: add styling to the geojson features
        style: function (feature) {
          return {
            color: 'green',
            weight: 2,
            fillOpacity: 0.2,
          };
        },
        // Optional: bind a popup for each feature
        onEachFeature: function (feature, layer) {
          if (feature.properties && feature.properties.name) {
            layer.bindPopup(feature.properties.name);
          }
        },
      }).addTo(lMap);
    });
  });

  function filterItems() {
    const favorite = [];

    if (favorite.length === 0) {
      $('li.map-item-list').show();
    } else {
      $('li.map-item-list').hide();

      $.each(favorite, function (key, item) {
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
    const currentDisplay = drupalSettings.leaflet_full_page?.currentDisplay || 'default_view_name';
    console.log('Fetching content for:', contentID);

    fetch(`/${currentDisplay}_mapitems?contentID=${encodeURIComponent(contentID)}&which=${encodeURIComponent(which)}`)  // Add query parameter here
      .then(res => {
        // Get contentID from the request URL
        const url = new URL(res.url);
        const contentID = url.searchParams.get('contentID');
        const which = url.searchParams.get('which');
        console.log('contentID from URL:', contentID);
        return res.json().then(data => {
          // Return both the data and the contentID
          return {
            data: data,
            contentID: contentID,
            which: which,
          };
        });
      })
      .then(data => {
        let theData = data.data;
        let contentID = data.contentID;  // Use contentID from the response
        let which = data.which;  // Use which from the response

        const item = theData.find(obj => Number(obj.id) === Number(contentID));

        if (item) {
          const itemsList = [];
          let itemTitle = '';
          if (which === 'map') {
            // do something with `item`
            console.log(contentID + ': ' + item.id);

            itemsList.push(contentID);
            itemTitle = item.label;
          }

          if (itemsList.length === 1) {
            [contentID] = itemsList;
          } else {
            contentID = null;

            document.querySelectorAll('li.map-item-list').forEach(el => el.style.display = 'none');

            itemsList.forEach(item => {
              document.querySelector('.leaflet__list-container').classList.remove('open');
              document.querySelector('.leaflet__list-container').classList.remove('container-up');
              document.querySelector('.leaflet__content').style.display = 'none';
              document.getElementById(item).style.display = 'block';
              document.querySelector('.leaflet__top h1').style.display = 'none';
              document.querySelector('.leaflet__top h3').innerHTML = itemTitle;
              document.querySelector('.leaflet__top h3').style.display = 'block';
            });
          }

          console.log(contentID);

          if (contentID != null) {
            document.querySelector('.leaflet__content-title').textContent = item.label;
            document.querySelector('.leaflet__content-textarea').innerHTML = item.field_body;
            // document.querySelector('.leaflet__content-textarea').append(mapItems[contentID].content);

            document.querySelectorAll('.leaflet__content-textarea a').forEach(link => {
              link.setAttribute('target', '_blank');
            });

            const theWidth = getTheWidth();

            if (theWidth === '100%') {
              document.querySelector('.leaflet__list-container').classList.add('container-up');
              if (item.image_url_mobile) {
                document.querySelector('.leaflet__content-main-image').src = item.image_url_mobile;
              }
            } else {
              document.querySelector('.leaflet__list-container').classList.remove('container-up');
              if (item.image_url_big) {
                document.querySelector('.leaflet__content-main-image').src = item.image_url_big;
              }
            }

            if (item.credit) {
              const figcaption = document.querySelector('.leaflet__content-main-figure figcaption');
              figcaption.innerHTML = `<strong>Credit: </strong>${item.credit}`;
              figcaption.style.display = 'block';
            }

            document.querySelector('.leaflet__content').setAttribute('maps-nid', contentID);
            document.querySelector('.leaflet__content').style.display = 'block';
            document.querySelector('.leaflet__list-container').classList.add('open');
            document.querySelector('.leaflet__content-scrollable').scrollTo({ top: 0, behavior: 'smooth' });

            setTimeout(() => {
              if (document.querySelector('.leaflet__list-container').classList.contains('container-up')) {
                const headerHeight = document.querySelector('.leaflet__content-header').offsetHeight + 7 + 30;
                document.querySelector('.leaflet__content-scrollable').style.height =
                  `${document.querySelector('.leaflet__content').offsetHeight - headerHeight - 20}px`;
              }
            }, 300);
          }
        }
      })
      .catch(err => console.error(err));
  }

  $(document).bind('leaflet.feature', function (event, lFeature, feature) {
    $(lFeature).click(function (e) {
      const newLatLng = {};
      newLatLng.lng = e.originalEvent.latlng.lng + 7;
      newLatLng.lat = e.originalEvent.latlng.lat;

      theMap.lMap.setView(newLatLng, 9);
      theMap.lMap.invalidateSize();

      const contentID = feature.entity_id;

      getContent(contentID, 'map');

      const headerHeight = $('.leaflet__content-header').height() + 7 + 30;
      $('.leaflet__content-scrollable').height($('.leaflet__content').height() - headerHeight - 20);
    });
  });

  Drupal.behaviors.surfaceMapItems = {
    attach(context) {
      $(once('setMapItems', '.leaflet__list-container', context)).each(function () {
        const filters = {};
        const currentDisplay = drupalSettings.leaflet_full_page?.currentDisplay || 'default_view_name';

        // Load the JSON data from the provided endpoint.
        $.getJSON('/' + currentDisplay + '_mapitems', function (data) {
          console.log('Loaded map items:', data);
          // Process each data item as needed.
          $.each(data, function (index, item) {
            // For demonstration, simply log the id and label.
            console.log('Item:', item.id, item.label, item.field_media_image);

            $('.leaflet__list').append(`
                <li tabindex="-1" id="${item.id}" class="map-item-list">
                  <div class="thumb" style="background-image: url(${item.field_media_image});">
                  </div>
                  <div class="info">
                    <h1 style="font-size: 16px; font-weight: bold">${item.label}</h1>
                    <div class="teaser">${item.field_subtitle}</div>
                    <date class="pub-date">${item.field_publication_date}</date>
                  </div>
                </li>
              `);
          });
        })
          .fail(function (jqXHR, textStatus, errorThrown) {
            console.error('Failed to load map items:', textStatus, errorThrown);
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

        document.querySelectorAll('.leaflet__content a.x-button').forEach(button => {
          button.addEventListener('click', function (e) {
            e.preventDefault();

            theMap.lMap.setView(theMap.map.settings.center, theMap.map.settings.zoom);
            theMap.lMap.invalidateSize();

            document.querySelector('.leaflet__list-container').classList.remove('open');
            document.querySelector('.leaflet__list-container').classList.remove('container-up');
            document.querySelector('.leaflet__content').style.display = 'none';
            document.querySelectorAll('li.map-item-list').forEach(item => item.style.display = 'block');
            document.querySelector('.leaflet__top h1').style.display = 'block';
            document.querySelector('.leaflet__top h3').style.display = 'none';

            filterItems();

            return null;
          });
        });

        $('.leaflet__content a.arrow-left', context).on('click', function (e) {
          e.preventDefault();
          const nid = $('.leaflet__content').attr('maps-nid');

          let contentID = 0;

          if ($(`#${nid}`).is(':first-child')) {
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

          if ($(`#${nid}`).is(':last-child')) {
            contentID = $('.leaflet__list li').first().attr('id');
          } else {
            contentID = $(`#${nid}`).next().attr('id');
          }

          getNewLatLng(contentID);
          getContent(contentID, 'container');

          return null;
        });
      });
    },
  };

  Drupal.behaviors.surfaceMapFilterCallback = {
    attach() {
      $(document).on('ajaxComplete', function () {
        if (drupalSettings && drupalSettings.views && drupalSettings.views.ajaxViews) {
          const { ajaxViews } = drupalSettings.views;
          Object.keys(ajaxViews || {}).forEach(function (i) {
            if (ajaxViews[i].view_name === 'leaflet_map_media' && ajaxViews[i].view_display_id === 'ocean_map') {
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
    if (theWidth === '70%' && $('.leaflet__list-container').hasClass('container-up')) {
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

  $(window).on('load', function () {
    resizeMap.adjust('Load', theMap.map.id);

    const headerHeight = $('.leaflet__content-header').height() + 7 + 30;
    $('.leaflet__content-scrollable').height($('.leaflet__content').height() - headerHeight - 20);
  });
})(jQuery, Drupal, drupalSettings, once);
