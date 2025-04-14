let map;
let ajaxRequest;
let geoJsonLayer;
let baseLayer;
let basemap;
let highlight;

function initmap(basemap) {
    // set up AJAX request
    ajaxRequest = getXmlHttpObject();
    if (ajaxRequest==null) {
        alert ("This browser does not support HTTP Request");
        return;
    }

    // set up the map
    map = new L.map('map', {
        zoomDelta: 0.25,
        zoomSnap: 0
    });

    // create the tile layer with correct attribution
    let osmUrl = 'https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png';
    let osmAttrib = 'Map data © <a href="https://openstreetmap.org">OpenStreetMap</a> contributors';
    let osm = new L.TileLayer(osmUrl, {attribution: osmAttrib});
    // let osm = new L.esri.basemapLayer('Topographic');
    baseLayer = L.esri.basemapLayer('Topographic');

    let OpenTopoMap2 = L.tileLayer('https://gis.pinkbike.org/tiles/heatlines/{z}/{x}/{y}.png', {
        maxZoom: 22,
        attribution: 'Map data: &copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors, <a href="http://viewfinderpanoramas.org">SRTM</a> | Map style: &copy; <a href="https://opentopomap.org">OpenTopoMap</a> (<a href="https://creativecommons.org/licenses/by-sa/3.0/">CC-BY-SA</a>)'
    });

    let OpenTopoMap = L.tileLayer('https://{s}.tile.opentopomap.org/{z}/{x}/{y}.png', {
        maxZoom: 17,
        attribution: 'Map data: &copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors, <a href="http://viewfinderpanoramas.org">SRTM</a> | Map style: &copy; <a href="https://opentopomap.org">OpenTopoMap</a> (<a href="https://creativecommons.org/licenses/by-sa/3.0/">CC-BY-SA</a>)'
    });

    let Thunderforest_Landscape = L.tileLayer('https://{s}.tile.thunderforest.com/landscape/{z}/{x}/{y}.png?apikey={apikey}', {
        attribution: '&copy; <a href="http://www.thunderforest.com/">Thunderforest</a>, &copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors',
        apikey: '5251c443e30c4d3580f156118c729852',
        maxZoom: 22
    });

    let Thunderforest_Outdoors = L.tileLayer('https://{s}.tile.thunderforest.com/outdoors/{z}/{x}/{y}.png?apikey={apikey}', {
        attribution: '&copy; <a href="http://www.thunderforest.com/">Thunderforest</a>, &copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors',
        apikey: '5251c443e30c4d3580f156118c729852',
        maxZoom: 22
    });

    let Stadia_AlidadeSmooth = L.tileLayer('https://tiles.stadiamaps.com/tiles/alidade_smooth/{z}/{x}/{y}{r}.png', {
    	maxZoom: 20,
    	attribution: '&copy; <a href="https://stadiamaps.com/">Stadia Maps</a>, &copy; <a href="https://openmaptiles.org/">OpenMapTiles</a> &copy; <a href="http://openstreetmap.org">OpenStreetMap</a> contributors'
    });

    let Stadia_Outdoors = L.tileLayer('https://tiles.stadiamaps.com/tiles/outdoors/{z}/{x}/{y}{r}.png', {
    	maxZoom: 20,
    	attribution: '&copy; <a href="https://stadiamaps.com/">Stadia Maps</a>, &copy; <a href="https://openmaptiles.org/">OpenMapTiles</a> &copy; <a href="http://openstreetmap.org">OpenStreetMap</a> contributors'
    });

    let Jawg_Terrain = L.tileLayer('https://{s}.tile.jawg.io/jawg-terrain/{z}/{x}/{y}{r}.png?access-token={accessToken}', {
        attribution: '<a href="http://jawg.io" title="Tiles Courtesy of Jawg Maps" target="_blank">&copy; <b>Jawg</b>Maps</a> &copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors',
        minZoom: 0,
        maxZoom: 22,
        subdomains: 'abcd',
        accessToken: '728rVSzSzybYFohOVKowvx1nkCInHYSstgcFGolOhn0pXWvkUNxUCpoFwDOcoTWQ'
    });

    let Esri_WorldTopoMap = L.tileLayer('https://server.arcgisonline.com/ArcGIS/rest/services/World_Topo_Map/MapServer/tile/{z}/{y}/{x}', {
        attribution: 'Tiles &copy; Esri &mdash; Esri, DeLorme, NAVTEQ, TomTom, Intermap, iPC, USGS, FAO, NPS, NRCAN, GeoBase, Kadaster NL, Ordnance Survey, Esri Japan, METI, Esri China (Hong Kong), and the GIS User Community'
    });

    let USGS_USTopo = L.tileLayer('https://basemap.nationalmap.gov/arcgis/rest/services/USGSTopo/MapServer/tile/{z}/{y}/{x}', {
        maxZoom: 20,
        attribution: 'Tiles courtesy of the <a href="https://usgs.gov/">U.S. Geological Survey</a>'
    });

    // start the map in near Burke, VT
    map.setView(new L.LatLng(44.59, -71.85), 12);
    map.addLayer(Jawg_Terrain);

    // basemap.addTo(map);

    geoJsonLayer = new L.GeoJSON.AJAX("/mapping/GeoJSON/KingdomTrails.geojson", {
        color: 'midnightblue',
        weight: 1.5,

        onEachFeature: function (feature, featureLayer) {
            featureLayer.bindPopup(
                feature.properties.name + ': ' + ((feature.properties.length * 3.28084) / 5280).toFixed(2) + ' miles',
                {offset: [0, -3]}
            );

            featureLayer.on('mouseover', function (e) {
                // this.setLatLng(e.latlng);

                highlight = this.options['color'];

                // console.log({'lat': e.latlng.lat + 0.5, 'lng': e.latlng.lng});
                this.openPopup({'lat': e.latlng.lat, 'lng': e.latlng.lng});
                this.setStyle({
                    'color': '#ff0000',
                    'weight': 5
                });
            });
            featureLayer.on('mouseout', function (e) {
                this.closePopup();
                let tempTrail = this;
                let isSelected = null;

                if(trailList.length > 0) {

                    trailList.map(function (trail) {

                        // alert(trail.id + ' == ' + feature.properties.id);

                        if (trail.id == feature.properties.id) {
                            tempTrail.setStyle({
                                color: highlight,
                                weight: 3
                            });

                            isSelected = true;
                        } else {
                            if(!isSelected) {
                                tempTrail.setStyle({
                                    color: 'midnightblue',
                                    weight: 1.5
                                });
                            }
                        }
                    });

                } else {
                    this.setStyle({
                        color: 'midnightblue',
                        weight: 1.5
                    });
                }

            });
        }
    });


    map.on('zoomend', function () {
        let currentZoom = map.getZoom();
        if (currentZoom >= 14) {
            geoJsonLayer.setStyle({weight: currentZoom * 0.16});
            console.log('1: ' + (currentZoom * 0.20));
        } else if (currentZoom >= 12 && currentZoom < 14) {
            geoJsonLayer.setStyle({weight: currentZoom * 0.15});
            console.log('2: ' + (currentZoom * 0.19));
        } else if (currentZoom < 12) {
            geoJsonLayer.setStyle({weight: currentZoom * 0.12});
            console.log('3: ' + (currentZoom * 0.18));
        }

        console.log(currentZoom);
    });

    geoJsonLayer.on('click', function(e) {
        let id = e.layer.feature.properties.id;
        let name = e.layer.feature.properties.name;
        let dist = e.layer.feature.properties.length;
        let touches = e.layer.feature.properties.touches;
        let segment = {'id': id, 'name': name, 'dist': dist};


        console.log(name);

        if(trailList[trailList.length - 1] && trailList[trailList.length - 1].id == e.layer.feature.properties.id) {
            //do something
            let yesNo = confirm('You just selected ' + e.layer.feature.properties.name + ', are you adding ' + e.layer.feature.properties.name + ' to your selection?');

            // If a segment is selected twice in a row.
            if(yesNo) {
                trailList.push(segment);

                let theClone = $('#cloneListWrapper li').clone(false);

                $('#list').append(theClone);

                updateDist(theClone, e);
            } else {



                trailList.pop();

                $('#list li').last().remove();

                updateDist(null, e);

                e.layer.setStyle({
                    'color': 'midnightblue',
                    'weight': 1.5
                });
            }
        } else {

            if (trailList[trailList.length - 1]) {
                let prevTrail = trailList[trailList.length - 1]
                console.log(touches);
                console.log(',' + prevTrail.id) + ',';

                // let text = "Hello world, welcome to the universe.";
                let result = touches.includes(',' + prevTrail.id + ',');

                if(!result) {
                    console.log(result);

                    let isContinuous = confirm('Your selection ' + e.layer.feature.properties.name + ' is not contiguous, you may need to zoom in further. Continue?');

                    if(!isContinuous) {
                        return '';
                    }
                }
            }
            // Just add normally

            // console.log(trailList)


            let newColor = null;

            highlight = '#ff0000';
            for (let i = 0; i < trailList.length; ++i) {
                if(trailList[i].id == id) {
                    // console.log('New Color:' + trailList[i].id);
                    highlight = 'lemonchiffon';
                }
            }
            trailList.push(segment);


            e.layer.setStyle({
                'color': highlight
            });


            let theClone = $('#cloneListWrapper li').clone(false);
            $('#list').append(theClone);

            updateDist(theClone, e);
        }

    });

    // let baseLayers = {
    //     "Mapbox": mapbox,
    //     "OpenStreetMap": osm
    // };
    // let overlays = {
    //     "Marker": marker,
    //     "Roads": roadsLayer
    // };
    // L.control.layers(baseLayers, overlays, geoJsonLayer).addTo(map);

    geoJsonLayer.addTo(map);
}



function updateDist(theClone, e) {
    if(theClone) {
        let theDist = e.layer.feature.properties.length;
        let miles = ((theDist * 3.280839895) / 5280).toFixed(2);
        let name = e.layer.feature.properties.name;
        theClone.find('.length').html(miles + ' miles');
        theClone.find('.length').attr('value', theDist);
        theClone.find('.name').html(name);
    }

    let totalDist = 0;
    $( "#list li" ).each(function( index ) {
        totalDist = totalDist + parseFloat($(this).find('.length').attr('value'));
        miles = ((totalDist * 3.280839895) / 5280).toFixed(2);
        console.log(miles + ' miles');

        $('#totalDist').html(miles + ' Miles');
    });
}

function addClone(theClone, e) {
    theClone.find('.name').html(e.layer.feature.properties.name);
    // let theDist = ((e.layer.feature.properties.length * 3.280839895) / 5280).toFixed(4);

    let theDist = e.layer.feature.properties.length;
    let miles = ((theDist * 3.280839895) / 5280).toFixed(2);
    theClone.find('.length').html(miles + ' miles');
    theClone.find('.length').attr('value', theDist);

    $('#list').append(theClone);

    let totalDist = 0;
    $( "#list li" ).each(function( index ) {
        totalDist = totalDist + parseFloat($(this).find('.length').attr('value'));
        miles = ((totalDist * 3.280839895) / 5280).toFixed(2);
        console.log(miles + ' miles');

        $('#totalDist').html(miles + ' Miles');
    });
}


function setBasemap (basemap) {

    let OpenTopoMap = L.tileLayer('https://{s}.tile.opentopomap.org/{z}/{x}/{y}.png', {
        maxZoom: 17,
        attribution: 'Map data: &copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors, <a href="http://viewfinderpanoramas.org">SRTM</a> | Map style: &copy; <a href="https://opentopomap.org">OpenTopoMap</a> (<a href="https://creativecommons.org/licenses/by-sa/3.0/">CC-BY-SA</a>)'
    });

    // if (baseLayer) {
    //     map.removeLayer(baseLayer);
    // }
    //
    // baseLayer = L.esri.basemapLayer(basemap);
    //
    // map.addLayer(baseLayer);
    //
    // if (layerLabels) {
    //     map.removeLayer(layerLabels);
    // }
    //
    // if (
    //     basemap === 'ShadedRelief' ||
    //     basemap === 'Oceans' ||
    //     basemap === 'Gray' ||
    //     basemap === 'DarkGray' ||
    //     basemap === 'Terrain'
    // ) {
    //     layerLabels = L.esri.basemapLayer(basemap + 'Labels');
    //     map.addLayer(layerLabels);
    // } else if (basemap.includes('Imagery')) {
    //     layerLabels = L.esri.basemapLayer('ImageryLabels');
    //     map.addLayer(layerLabels);
    // }
}


//


// then add this as a new function...
function onMapMove(e) {  }

function getXmlHttpObject() {
    if (window.XMLHttpRequest) { return new XMLHttpRequest(); }
    if (window.ActiveXObject)  { return new ActiveXObject("Microsoft.XMLHTTP"); }
    return null;
}


