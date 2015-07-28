/* global google */

var map; // google mapa

// pocatecni nastaveni - konstanty
var INFOWINDOW_MIN_ZOOM = 10; // priblizeni nutne pro zobrazeni info okna
var INIT_ZOOM = 8; // priblizeni mapy bez zjistene geolokace
var INIT_CENTER = {lat:49.8,lng:15.5}; // vystredeni mapy bez zjistene geolokace
var GEOLOCATION_ZOOM = 12; // zoom pri zjistene geolokaci

var PROCESS_CLICK_URL = location.protocol + "//" + location.host + location.pathname + "wifi/processClick";
var IMAGE_URL = location.protocol + "//" + location.host + location.pathname + "wifi/image";

var markers = [];

// url parametry -> sem pridavat vsechny
var hashParams = {};
/**
 * zjisteni hodnot z URL (kvuli moznosti sdileni filtru)
 */
var urlVars = getUrlVars();
console.log(urlVars);
hashParams = getUrlVars();
var ssid = (urlVars["ssid"]) ? urlVars["ssid"] : "";
init();

if(hashParams.gm) {
    var res = hashParams.gm.split("%2C");
    INIT_CENTER = {lat: parseFloat(res[0]), lng: parseFloat(res[1])};
    INIT_ZOOM = parseInt(res[2]);
}

    function init() {
        $("#form-ssid").val(ssid);
        hashParams.ssid = ssid;

    }
    function initializeMap() {

        var mapOptions = {center:INIT_CENTER,zoom:INIT_ZOOM, draggableCursor: 'default'};
	    map = new google.maps.Map(document.getElementById('mapa'), mapOptions);

        // get user location by HTML5
        if(navigator.geolocation && !hashParams.gm) {
            navigator.geolocation.getCurrentPosition(function(position) {
                map.setCenter(new google.maps.LatLng(position.coords.latitude,position.coords.longitude));
                map.setZoom(GEOLOCATION_ZOOM);
            });
        }

        // prekryvna vrstva
        map.overlayMapTypes.insertAt(0, new CoordMapType(new google.maps.Size(256, 256)));

        // odchytnuti kliknuti do mapy -> zobrazeni info okna
        google.maps.event.addListener(map, 'click', function(event) {
            if(map.getZoom() >= INFOWINDOW_MIN_ZOOM) {

                var bounds = map.getBounds();
                $.ajax({
                    url: PROCESS_CLICK_URL, data: {
                        click_lat: event.latLng.lat(),
                        click_lon: event.latLng.lng(),
                        map_lat1: bounds.getSouthWest().lat(),
                        map_lat2: bounds.getNorthEast().lat(),
                        map_lon1: bounds.getSouthWest().lng(),
                        map_lon2: bounds.getNorthEast().lng(),
                        zoom: map.getZoom()
                    }
                }).done(function (data) {
                    var infoWindow = new google.maps.InfoWindow();

                    infoWindow.setContent(data);
                    infoWindow.setPosition(new google.maps.LatLng(event.latLng.lat(), event.latLng.lng()));
                    infoWindow.open(map);
                });
            }
        });

        google.maps.event.addListener(map,'idle', function() {
            var gmString = map.getCenter().lat()+","+map.getCenter().lng()+","+map.getZoom();
            hashParams.gm = gmString;
            window.location.hash = $.param(hashParams);
        });


        // google maps search

        var input = (document.getElementById('pac-input'));
        map.controls[google.maps.ControlPosition.TOP_LEFT].push(input);

        var searchBox = new google.maps.places.SearchBox((input));

        google.maps.event.addListener(searchBox, 'places_changed', function() {
            var places = searchBox.getPlaces();

            if (places.length == 0) {
                return;
            }
            /*for (var i = 0, marker; marker = markers[i]; i++) {
                marker.setMap(null);
            }

            // For each place, get the icon, place name, and location.
            markers = [];*/
            var bounds = new google.maps.LatLngBounds();
            for (var i = 0, place; place = places[i]; i++) {
                var image = {
                    url: place.icon,
                    size: new google.maps.Size(71, 71),
                    origin: new google.maps.Point(0, 0),
                    anchor: new google.maps.Point(17, 34),
                    scaledSize: new google.maps.Size(25, 25)
                };

                // Create a marker for each place.
                var marker = new google.maps.Marker({
                    map: map,
                    icon: image,
                    title: place.name,
                    position: place.geometry.location
                });

                markers.push(marker);

                bounds.extend(place.geometry.location);
            }

            map.fitBounds(bounds);
        });
    }


    google.maps.event.addDomListener(window, 'load', initializeMap);

function CoordMapType(tileSize) {
    this.tileSize = tileSize;
}

CoordMapType.prototype.getTile = function(coord, zoom, ownerDocument) {

    var bod = MERCATOR.getTileBounds({x: coord.x, y: coord.y, z: zoom});
    var img = ownerDocument.createElement('img');
    var params = {
        lat1: bod.sw.lat,
        lat2: bod.ne.lat,
        lon1: bod.sw.lng,
        lon2: bod.ne.lng,
        zoom:zoom
    };
   /* if(ssid != "") {
        params.ssid = ssid;
    }*/

    img.src = IMAGE_URL +"?" + $.param($.extend(params,hashParams));
    img.alt = "wifimap";
    return img;
};

function redrawOverlay() {
    map.overlayMapTypes.removeAt(0);
    map.overlayMapTypes.insertAt(0, new CoordMapType(new google.maps.Size(256, 256)));
}

function searchFormSubmit() {
    ssid = $("#form-ssid").val();
    hashParams.mode = 'SEARCH';
    hashParams.ssid = ssid;
    window.location.hash = $.param(hashParams);
    redrawOverlay();
    return false;
}

function getUrlVars() {
    var vars = {};
    window.location.href.replace(/[#&]+([^=&]+)=([^&#]*)/gi, function(m,key,value) {
        vars[key] = value;
    });
    return vars;
}

/**
 * zruseni vsech nastaveni - modu a jeho parametru
 */
function resetAllFilters() {
    delete hashParams.ssid;
    delete hashParams.mode;
    console.log(hashParams);
    window.location.hash = $.param(hashParams);


    redrawOverlay();
}