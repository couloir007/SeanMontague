// let map;
let trailList = [];
let urlString;
let urlVar;
let win;
let baseUrl;

let theObject;

initmap();

$('#close').click(function (e) {
    e.preventDefault();
    $("#close").appendTo('.profile-wrapper');
    $('.profile-wrapper').hide();
    $('#list-container').show();
});

$('#getProfile').click(function (e) {
    // urlString = '';
    // trailList.forEach(iterateSegments);

    $('.profile-inner').html('');

    // console.log(trailList);

    theObject = {};

    // trailList is populated leafletembed.js click
    trailList.forEach(iterateSegments2);

    console.log(1);

    $.get( "../php_files/loadProfile.php", theObject )
        .done(function( data ) {
            $('.profile-inner').html(data);
            $("#close").appendTo('.profile-inner');
            $('#list-container').hide();
            $('.profile-wrapper').show();
    });

    // baseUrl = document.location.origin;
    // urlString = baseUrl + '/php_files/loadProfile.php?' + urlString;
    //
    // console.log(urlString);
    //
    // win = window.open(urlString, '_blank');
    // if (win) {
    //     //Browser has allowed it to be opened
    //     win.focus();
    // } else {
    //     //Browser has blocked it
    //     alert('Please allow popups for this website');
    // }
});

$('#exportGPX').click(function (e) {
    // console.log(trailList);

    theObject = {};


    urlString = ''

    // trailList is populated leafletembed.js click
    trailList.forEach(iterateSegments);

    let urlString2 = urlString.substring(1, urlString.length);

    // console.log(urlString2);

    window.location.href = '../php_files/exportGPX.php?' + urlString2;

//    $.get( "../../php_files/exportGPX.php", theObject )
//        .done(function( data ) {
//            $('.profile-inner').html(data);
//            $("#close").appendTo('.profile-inner');
//            $('#list-container').hide();
//            $('.profile-wrapper').show();
//    });
});

document.querySelector('#basemaps').addEventListener('change', function (e) {
    basemap = e.target.value;
    setBasemap(basemap);
});

$('#clearSelection').click(function (e) {
    $('#totalDist').html('');
    $('#list').html('');
    trailList = [];
    geoJsonLayer.eachLayer(function (layer) {
        layer.setStyle({
            color: 'midnightblue',
            weight: 1.5
        });
    });
});

function iterateSegments(item, idx) {
    urlVar = '&id' + idx + '=' + item['id'];
    urlString = urlString + urlVar;
};

function iterateSegments2(item, idx) {

    theObject['id' + idx] = item['id'];
    // alert(item['id']);
    // $.get( "../../php_files/loadProfileAjax.php", { theID: item['id'],  } )
    //     .done(function( data ) {
    //         alert( "Data Loaded: " + data );
    //     });
    // mtb389, mtb390, mtb391, mtb248
};
