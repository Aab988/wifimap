/* global google */
var map; // google map object

// initial configuration - constants
var INFOWINDOW_MIN_ZOOM = 10; // zoom needed for showing infowindow
var INIT_ZOOM = 8; // initial zoom without user geolocation
var INIT_CENTER = {lat: 49.8, lng: 15.5}; // initial map center without user geolocation
var GEOLOCATION_ZOOM = 12; // zoom with user geolocation

var BASE_URL = location.protocol + "//" + location.host + location.pathname;

var PROCESS_CLICK_URL = BASE_URL + "wifi/processClick";
var IMAGE_URL = BASE_URL + "wifi/image";
var GOOGLE_DOWNLOAD_URL = BASE_URL + "download/creategooglerequest";

var ACTUAL_MODE_URL = BASE_URL + "wifi/actualmode";

var API_DOWNLOAD_URL = BASE_URL + "api/download";

// google maps search markers
var markers = [];

var mainInfoWindow;

var uer = new UER();

function getUrlVars() {
    var vars = {};
    window.location.href.replace(/[#&]+([^=&]+)=([^&#]*)/gi, function(m,key,value) {
        vars[key] = value;
    });
    return vars;
}


// params in URL
var hashParams = {};
// get URL params and values - share feature
hashParams = getUrlVars();
init();

if (hashParams.gm) {
    var res = hashParams.gm.split("%2C");
    INIT_CENTER = {lat: parseFloat(res[0]), lng: parseFloat(res[1])};
    INIT_ZOOM = parseInt(res[2]);
}

// set some params - share feature
function init() {
    switch (hashParams.mode) {
        case MODE_SEARCH:
            if (hashParams.ssidmac) $("#frm-searchForm-ssidmac").val(decodeURIComponent(hashParams.ssidmac));
            if (hashParams.channel) $("#frm-searchForm-channel").val(hashParams.channel);
            if (hashParams.security) $("#frm-searchForm-security").val(hashParams.security);
            if (hashParams.source) $("#frm-searchForm-source").val(hashParams.source);
            break;
    }
    hashParams.mode = (hashParams.mode) ? hashParams.mode : MODE_ALL;
}

function createInfoWindow(content, position) {
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
    params = $.extend(params, hashParams);
    delete params.gm;

    $.getJSON(PROCESS_CLICK_URL, params, function (data) {
        if(data.success) {
            mainInfoWindow.setContent(data.iw);
            mainInfoWindow.setPosition(new google.maps.LatLng(data.lat, data.lng));
        }
    });
    return false;
}

function initializeMap() {

    var mapOptions = {center: INIT_CENTER, zoom: INIT_ZOOM, draggableCursor: 'default', tilt: 0};
    map = new google.maps.Map(document.getElementById('mapa'), mapOptions);

    // get user location by HTML5
    if (navigator.geolocation && !hashParams.gm) {
        navigator.geolocation.getCurrentPosition(function (position) {
                map.setCenter(new google.maps.LatLng(position.coords.latitude, position.coords.longitude));
                map.setZoom(GEOLOCATION_ZOOM);
                map.overlayMapTypes.insertAt(0, new CoordMapType(new google.maps.Size(256, 256)));
            },
            function () {
                map.overlayMapTypes.insertAt(0, new CoordMapType(new google.maps.Size(256, 256)));
            });
    }
    else {
        map.overlayMapTypes.insertAt(0, new CoordMapType(new google.maps.Size(256, 256)));
    }

    // odchytnuti kliknuti do mapy -> zobrazeni info okna
    google.maps.event.addListener(map, 'click', function (event) {
        if (map.getZoom() >= INFOWINDOW_MIN_ZOOM) {
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
            params = $.extend(params, hashParams);
            delete params.gm;

            $.getJSON(PROCESS_CLICK_URL, params, function (data) {
                if (data.success) { createInfoWindow(data.iw, new google.maps.LatLng(data.lat, data.lng)); }
            });
        }
    });

    google.maps.event.addListener(map, 'idle', function () {
        hashParams.gm = map.getCenter().lat() + "," + map.getCenter().lng() + "," + map.getZoom();
        if(hashParams.ssidmac) {
            hashParams.ssidmac = decodeURIComponent(hashParams.ssidmac);
        }
        window.location.hash = $.param(hashParams);
    });

    // google maps search
    var input = (document.getElementById('pac-input'));
    map.controls[google.maps.ControlPosition.TOP_LEFT].push(input);

    var searchBox = new google.maps.places.SearchBox((input));

    google.maps.event.addListener(searchBox, 'places_changed', function () {
        var places = searchBox.getPlaces();

        if (places.length == 0) {
            return;
        }
        for (var i = 0; i < markers.length; i++) {markers[i].setMap(null); }
        markers = [];
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

CoordMapType.prototype.getTile = function (coord, zoom, ownerDocument) {

    var bod = MERCATOR.getTileBounds({x: coord.x, y: coord.y, z: zoom});
    var img = ownerDocument.createElement('img');
    var params = {
        lat1: bod.sw.lat,
        lat2: bod.ne.lat,
        lon1: bod.sw.lng,
        lon2: bod.ne.lng,
        zoom: zoom
    };
    params = $.extend(params, hashParams);
    delete params.gm;
    img.src = IMAGE_URL + "?" + $.param(params);
    img.alt = "wifimap";
    return img;
};

/** prekreslit prekryvnou vrstvu */
function redrawOverlay() {
    map.overlayMapTypes.removeAt(0);
    map.overlayMapTypes.insertAt(0, new CoordMapType(new google.maps.Size(256, 256)));
}


function removeAllParams() {
    for(var propertyName in hashParams) {
        if(propertyName != 'gm') {
            delete hashParams[propertyName];
        }
    }
}

/** prenacist data */
function modeChanged() {
    window.location.hash = $.param(hashParams);
    sendRequestAjax(ACTUAL_MODE_URL,hashParams,$(".actualModeInfoContent"));
    redrawOverlay();
    return false;
}

function highlightFormSubmit(form) {
    removeAllParams();
    var by = form["highlight-by"].value;
    var val = form[by].value;
    hashParams.mode = MODE_HIGHLIGHT;
    hashParams.by = by;
    hashParams.val = val;
    modeChanged();
    return false;
}

function showOnlyOneNet(ssid) {
    removeAllParams();
    hashParams.mode = MODE_ONE;
    hashParams.ssid = ssid;
    modeChanged();
}

function useAsFilter(form) {
    removeAllParams();

    $("#frm-searchForm-ssidmac").val("");
    $("#frm-searchForm-channel").val("");
    $("#frm-searchForm-security").val("");
    $("#frm-searchForm-source").val("");

    var by = $(form).find('.highlightBy').val();
    var val = $(form).find("."+by).val();
    if(by == 'ssid' || by == 'mac') {
        by = 'ssidmac';
        hashParams[by] = val;
        $("#frm-searchForm-ssidmac").val(decodeURIComponent(val));
    }
    if(by == "channel") {
        $("#frm-searchForm-channel").val(decodeURIComponent(val));
    }
    hashParams.mode = MODE_SEARCH;
    modeChanged();
}

function calculate(id) {
    removeAllParams();
    hashParams.mode = MODE_CALCULATED;
    hashParams.a = id;
    modeChanged();
}

    $(document).ajaxStart(function () {
        $(".loader").show();
    }).ajaxStop(function () {
        $(".loader").hide();
    });


/**
 * zruseni vsech nastaveni - modu a jeho parametru
 */
function resetAllFilters() {
    $("#frm-searchForm-ssidmac").val(null);
    $("#frm-searchForm-channel").val(null);
    $("#frm-searchForm-security").val(null);
    $("#frm-searchForm-source").val(null);
    removeAllParams();
    modeChanged();
}

function sendRequestAjax(dataurl, requestData, infodiv, callback) {
    if (!callback) callback = function () {
    };
    $.ajax(dataurl, {
        data: requestData,
        beforeSend: function () {
            $(infodiv).html(null);
        }
    }).done(function (data) {
        $(infodiv).html(data);
        callback();
    });
}

/**
 * TODO: mohlo by jit refactorovat -> klidne to i sjednotit a latmax a lonmax vyresit tim ze budu mit dva UER
 */
$("#beginGoogleRequest").click(function () {
    cancelDownloadRequest("#requestInfo", "#beginWigleRequest");
    uer.setLatMax(0.04);
    uer.setLngMax(0.08);
    uer.createUserEditableRectangle();
    uer.createListener();
    $(this).hide();
    sendRequestAjax($(this).attr('data-url'), {}, "#requestInfo",function() { uer.getTime(); });
});

$('#beginWigleRequest').click(function () {
    cancelDownloadRequest("#requestInfo", "#beginGoogleRequest");
    uer.setLatMax(0.08);
    uer.setLngMax(0.16);
    uer.createUserEditableRectangle();
    uer.createListener();
    $(this).hide();
    sendRequestAjax($(this).attr('data-url'), {}, "#requestInfo",function() { uer.getTime(); });
});

/**
 * vytvoreni pozdavaku stahovani
 * @param url
 */
function createDownloadRequest(url, infodiv) {
    // vzit souradnice
    var bounds = uer.getUserEditableRectangle().getBounds();
    var data = {
        lat1: bounds.getSouthWest().lat(),
        lat2: bounds.getNorthEast().lat(),
        lon1: bounds.getSouthWest().lng(),
        lon2: bounds.getNorthEast().lng()
    };
    sendRequestAjax(url, data, infodiv, function () {
        uer.getUserEditableRectangle().setMap(null);
        uer.setUserEditableRectangle(null);
    });
}

/**
 * zrusi pozadavek stahovani a infodiv vyprazdni a begin button zobrazi
 * @param infodiv
 * @param beginbutton
 */
function cancelDownloadRequest(infodiv, beginbutton) {
    if (uer.getUserEditableRectangle()) {
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
function endDownloadRequest(infodiv, beginbutton) {
    $(infodiv).html(null);
    $(beginbutton).show();
}


$(document).ready(function () {

    sendRequestAjax(ACTUAL_MODE_URL,hashParams,$(".actualModeInfoContent"));

    $("#frm-searchForm").submit(function (e) {
        e.preventDefault();
        removeAllParams();
        ssidmac = decodeURIComponent($(this).find("#frm-searchForm-ssidmac").val());
        channel = $(this).find("#frm-searchForm-channel").val();
        security = $(this).find("#frm-searchForm-security").val();
        source = $(this).find("#frm-searchForm-source").val();
        hashParams.mode = MODE_SEARCH;
        if (ssidmac) hashParams.ssidmac = ssidmac;
        if (channel) hashParams.channel = channel;
        if (security) hashParams.security = security;
        if (source) hashParams.source = source;
        modeChanged();
    });

    $("#exportBtn").click(function(e) {
        e.preventDefault();
        ssidmac = decodeURIComponent($("#frm-searchForm-ssidmac").val());
        channel = $("#frm-searchForm-channel").val();
        security = $("#frm-searchForm-security").val();
        source = $("#frm-searchForm-source").val();

        hashParams.mode = MODE_SEARCH;
        if (ssidmac) hashParams.ssidmac = ssidmac;
        if (channel) hashParams.channel = channel;
        if (security) hashParams.security = security;
        if (source) hashParams.source = source;

        $.ajax(API_DOWNLOAD_URL, {
            data: hashParams
        }).done(function(data) {
            $("#downloadFile").attr('href',data.file);
            document.getElementById("downloadFile").click();
        });
    });

    $(".hide-btn").click(function() {
        if($(this).parent("div").css("margin-right") == "-200px") { $(this).parent("div").animate({ "margin-right": "0px" },500); }
        else { $(this).parent("div").animate({ "margin-right": "-200px" },500); }
    });
});

/**
 * create google request by wifi id
 * @param wid
 */
function googleDownloadRequest1(wid) {
    $.ajax(GOOGLE_DOWNLOAD_URL, { data: { wid: wid } }).done(function () {});
    return false;
}

