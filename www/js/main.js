/* global google */


var map; // google map object

// initial configuration - constants
var INFOWINDOW_MIN_ZOOM = 10; // zoom needed for showing infowindow
var INIT_ZOOM = 8; // initial zoom without user geolocation
var INIT_CENTER = {lat:49.8,lng:15.5}; // initial map center without user geolocation
var GEOLOCATION_ZOOM = 12; // zoom with user geolocation

var BASE_URL = location.protocol + "//" + location.host + location.pathname;

var PROCESS_CLICK_URL = BASE_URL + "wifi/processClick";
var IMAGE_URL = BASE_URL + "wifi/image";
var GOOGLE_DOWNLOAD_URL = BASE_URL + "download/creategooglerequest";

// google maps search markers
var markers = [];

var mainInfoWindow;

var uer = new UER();

// params in URL
var hashParams = {};
// get URL params and values - share feature
hashParams = getUrlVars();
init();

    if(hashParams.gm) {
        var res = hashParams.gm.split("%2C");
        INIT_CENTER = {lat: parseFloat(res[0]), lng: parseFloat(res[1])};
        INIT_ZOOM = parseInt(res[2]);
    }

    // set some params - share feature
    function init() {
        switch (hashParams.mode) {
            case MODE_SEARCH:
                if(hashParams.ssid) $("#form-ssid").val(hashParams.ssid);
                break;
            case MODE_ONE_SOURCE:
                $("#ul-one-source").show(100);
                if(hashParams.source) $("#" + hashParams.source).addClass("active");
                break;
        }
        hashParams.mode = (hashParams.mode) ? hashParams.mode : MODE_ALL;
    }

    function createInfoWindow(content,position) {
        var infoWindow = new google.maps.InfoWindow();
        infoWindow.setContent(content);
        infoWindow.setPosition(position);
        mainInfoWindow = infoWindow;
        infoWindow.open(map);
    }

    function changeIW(id) {
        var bounds = map.getBounds();
        var params = {
            net: id,
            map_lat1: bounds.getSouthWest().lat(),
            map_lat2: bounds.getNorthEast().lat(),
            map_lon1: bounds.getSouthWest().lng(),
            map_lon2: bounds.getNorthEast().lng()
        };
        params = $.extend(params,hashParams);
        delete params.gm;

        $.getJSON(PROCESS_CLICK_URL, params, function(data) {
            mainInfoWindow.setContent(data.iw);
            mainInfoWindow.setPosition(new google.maps.LatLng(data.latitude, data.longitude));
        });
        return false;
    }



    function initializeMap() {


        var mapOptions = {center:INIT_CENTER,zoom:INIT_ZOOM, draggableCursor: 'default',tilt:0};
        map = new google.maps.Map(document.getElementById('mapa'), mapOptions);

        // get user location by HTML5
        if(navigator.geolocation && !hashParams.gm) {
            navigator.geolocation.getCurrentPosition(function(position) {
                map.setCenter(new google.maps.LatLng(position.coords.latitude,position.coords.longitude));
                map.setZoom(GEOLOCATION_ZOOM);
                map.overlayMapTypes.insertAt(0, new CoordMapType(new google.maps.Size(256, 256)));
            },
            function() {
                map.overlayMapTypes.insertAt(0, new CoordMapType(new google.maps.Size(256, 256)));
            });
        }
        else {
            map.overlayMapTypes.insertAt(0, new CoordMapType(new google.maps.Size(256, 256)));
        }

        // odchytnuti kliknuti do mapy -> zobrazeni info okna
        google.maps.event.addListener(map, 'click', function(event) {
            if(map.getZoom() >= INFOWINDOW_MIN_ZOOM) {
                var bounds = map.getBounds();
                var params = {
                    click_lat: event.latLng.lat(),
                    click_lon: event.latLng.lng(),
                    map_lat1: bounds.getSouthWest().lat(),
                    map_lat2: bounds.getNorthEast().lat(),
                    map_lon1: bounds.getSouthWest().lng(),
                    map_lon2: bounds.getNorthEast().lng(),
                    zoom: map.getZoom()
                };
                params = $.extend(params,hashParams);
                delete params.gm;

                $.getJSON(PROCESS_CLICK_URL,params,function(data){
                    if(data) {
                        createInfoWindow(data.iw,new google.maps.LatLng(data.lat, data.lng));
                    }
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



    params = $.extend(params,hashParams);
    delete params.gm;
    img.src = IMAGE_URL +"?" + $.param(params);
    img.alt = "wifimap";
    return img;
};


function redrawOverlay() {

    map.overlayMapTypes.removeAt(0);
    map.overlayMapTypes.insertAt(0, new CoordMapType(new google.maps.Size(256, 256)));
}

function searchFormSubmit() {
    ssid = $("#form-ssid").val();
    hashParams.mode = MODE_SEARCH;
    hashParams.ssid = ssid;
    window.location.hash = $.param(hashParams);
    redrawOverlay();
    return false;
}

function highlightFormSubmit(form) {
    console.log("asdf");
    var by = form["highlight-by"].value;
    var val = form[by].value;
    //var ssid = form["ssid"].value;
    hashParams.mode = MODE_HIGHLIGHT;
    hashParams.by = by;
    hashParams.val = val;

    //hashParams.ssid = ssid;
    window.location.hash = $.param(hashParams);
    redrawOverlay();

    return false;
}


$(document)
    .ajaxStart(function () {
        $(".loader").show();
    })
    .ajaxStop(function () {
        $(".loader").hide();
    });

/**
 * zruseni vsech nastaveni - modu a jeho parametru
 */
function resetAllFilters() {
    $(".mi-source").removeClass("active");
    $("#ul-one-source").hide(100);

    delete hashParams.ssidmac;
    delete hashParams.channel;
    delete hashParams.security;
    delete hashParams.ssid;
    delete hashParams.mode;
    delete hashParams.source;
    //console.log(hashParams);
    window.location.hash = $.param(hashParams);

    redrawOverlay();
}

function sendRequestAjax(dataurl,requestData,infodiv,callback) {
    if(!callback) callback = function(){};
    $.ajax(dataurl, {
        data: requestData,
        beforeSend: function(){
            $(infodiv).html(null);
        }
    }).done(function(data) {
        $(infodiv).html(data);
        callback();
    });
}


/**
 * TODO: mohlo by jit refactorovat -> klidne to i sjednotit a latmax a lonmax vyresit tim ze budu mit dva UER
 */
$("#beginGoogleRequest").click(function() {
    cancelDownloadRequest("#requestInfo","#beginWigleRequest");
    uer.setLatMax(0.04);
    uer.setLngMax(0.08);
    uer.createUserEditableRectangle();
    uer.createListener();
    $(this).hide();
    sendRequestAjax($(this).attr('data-url'),{},"#requestInfo");
});

$('#beginWigleRequest').click(function(){
    cancelDownloadRequest("#requestInfo","#beginGoogleRequest");
    uer.setLatMax(0.08);
    uer.setLngMax(0.16);
    uer.createUserEditableRectangle();
    uer.createListener();
    $(this).hide();
    sendRequestAjax($(this).attr('data-url'),{},"#requestInfo");
});

/**
 * vytvoreni pozdavaku stahovani
 * @param url
 */
function createDownloadRequest(url,infodiv) {
    // vzit souradnice
    var bounds = uer.getUserEditableRectangle().getBounds();
    var data = {
        lat1: bounds.getSouthWest().lat(),
        lat2: bounds.getNorthEast().lat(),
        lon1: bounds.getSouthWest().lng(),
        lon2: bounds.getNorthEast().lng()
    };
    sendRequestAjax(url,data,infodiv,function() {
        uer.getUserEditableRectangle().setMap(null);
        uer.setUserEditableRectangle(null);
    });
}

/**
 * zrusi pozadavek stahovani a infodiv vyprazdni a begin button zobrazi
 * @param infodiv
 * @param beginbutton
 */
function cancelDownloadRequest(infodiv,beginbutton) {
    if(uer.getUserEditableRectangle()) {
        uer.getUserEditableRectangle().setMap(null);
        uer.setUserEditableRectangle(null);
    }
    $(infodiv).html(null);
    $(beginbutton).show();
}

/**
 * ukonceni pozadavku stahovani (po pridani)
 * @param infodiv
 * @param beginbutton
 */
function endDownloadRequest(infodiv,beginbutton) {
    $(infodiv).html(null);
    $(beginbutton).show();
}






$(document).ready(function(){
    $("#mi-one-source").click(function(){
        $("#ul-one-source").toggle(100);
    });

    $(".mi-source").click(function() {
        if($(this).hasClass("active")) {
            return;
        }
        $(".mi-source").each(function() {
            $(this).removeClass("active");
        });
        // nastavit prekryvnou vrstvu
        hashParams.mode = MODE_ONE_SOURCE;
        hashParams.source = this.id;
        window.location.hash = $.param(hashParams);
        redrawOverlay();

        $(this).addClass("active");
    });

    $("#mi-free-nets").click(function(){
        hashParams.mode = MODE_FREE;
        window.location.hash = $.param(hashParams);
        redrawOverlay();
    });


    $("#frm-searchForm").submit(function(e) {
        e.preventDefault();
        delete hashParams.ssidmac;
        delete hashParams.channel;
        delete hashParams.security;

        ssidmac = $(this).find("#frm-searchForm-ssidmac").val();
        channel = $(this).find("#frm-searchForm-channel").val();
        security = $(this).find("#frm-searchForm-security").val();
        hashParams.mode = MODE_SEARCH;

        if(ssidmac) hashParams.ssidmac = ssidmac;
        if(channel) hashParams.channel = channel;
        if(security) hashParams.security = security;

        window.location.hash = $.param(hashParams);
        redrawOverlay();

    });
});





/**
 * create google request by wifi id
 *
 * @param wid
 */
function googleDownloadRequest1(wid) {

    $.ajax(GOOGLE_DOWNLOAD_URL,{
        data: {
            wid: wid
        }
    }).done(function() {
    });
    return false;
}

