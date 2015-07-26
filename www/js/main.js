/* global google */

var map; // google mapa

// pocatecni nastaveni - konstanty
var INFOWINDOW_MIN_ZOOM = 10; // priblizeni nutne pro zobrazeni info okna
var INIT_ZOOM = 8; // priblizeni mapy bez zjistene geolokace
var INIT_CENTER = {lat:49.8,lng:15.5}; // vystredeni mapy bez zjistene geolokace
var GEOLOCATION_ZOOM = 12; // zoom pri zjistene geolokaci


var markers = [];

/**
 * zjisteni hodnot z URL (kvuli moznosti sdileni filtru)
 */
var urlVars = getUrlVars();
console.log(urlVars);

var ssid = (urlVars["ssid"]) ? urlVars["ssid"] : "";
console.log(ssid);

    function initialize() {

        var mapOptions = {center:INIT_CENTER,zoom:INIT_ZOOM, draggableCursor: 'default'};
	    map = new google.maps.Map(document.getElementById('mapa'), mapOptions);

        // get user location by HTML5
        if(navigator.geolocation) {
            navigator.geolocation.getCurrentPosition(function(position) {
                map.setCenter(new google.maps.LatLng(position.coords.latitude,position.coords.longitude));
                map.setZoom(GEOLOCATION_ZOOM);
            });
        }

        // prekryvna vrstva
        map.overlayMapTypes.insertAt(0, new CoordMapType(new google.maps.Size(256, 256)));

        // odchytnuti kliknuti do mapy -> zobrazeni info okna
        google.maps.event.addListener(map, 'click', function(event) {
            console.log(map.getZoom());
            if(map.getZoom() >= INFOWINDOW_MIN_ZOOM) {
                var url = location.protocol + "//" + location.host + location.pathname + "wifi/processClick";

                var bounds = map.getBounds();

                $.ajax({
                    url: url, data: {
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

        })

   

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


    google.maps.event.addDomListener(window, 'load', initialize);
    
    

function CoordMapType(tileSize) {
    this.tileSize = tileSize;
}

CoordMapType.prototype.getTile = function(coord, zoom, ownerDocument) {


    var bod = MERCATOR.getTileBounds({x: coord.x, y: coord.y, z: zoom});
    var img = ownerDocument.createElement('img');
    var url = location.protocol + "//" + location.host + location.pathname + "wifi/image";
    img.src = url+'?lat1='+bod.sw.lat+'&lat2='+bod.ne.lat+'&lon1='+bod.sw.lng + '&lon2='+bod.ne.lng + '&zoom='+zoom;
    if(ssid != "") {
        img.src += "&ssid="+ssid;
    }
    img.alt = "wifimap";
    return img;
  };
  
MERCATOR={

  fromLatLngToPoint:function(latLng){
     var siny =  Math.min(Math.max(Math.sin(latLng.lat* (Math.PI / 180)),-.9999),.9999);
     return {
      x: 128 + latLng.lng * (256/360),
      y: 128 + 0.5 * Math.log((1 + siny) / (1 - siny)) * -(256 / (2 * Math.PI))
     };
  },

  fromPointToLatLng: function(point){
     return {
      lat: (2 * Math.atan(Math.exp((point.y - 128) / -(256 / (2 * Math.PI)))) - Math.PI / 2)/ (Math.PI / 180),
      lng:  (point.x - 128) / (256 / 360)
     };
  },
  getTileAtLatLng:function(latLng,zoom){
    var t=Math.pow(2,zoom),
        s=256/t,
        p=this.fromLatLngToPoint(latLng);
        return {x:Math.floor(p.x/s),y:Math.floor(p.y/s),z:zoom};
  },

  getTileBounds:function(tile){
    tile=this.normalizeTile(tile);
    var t=Math.pow(2,tile.z),
        s=256/t,
        sw={x:tile.x*s,
            y:(tile.y*s)+s},
        ne={x:tile.x*s+s,
            y:(tile.y*s)};
        return{sw:this.fromPointToLatLng(sw),
               ne:this.fromPointToLatLng(ne)
              }
  },
  normalizeTile:function(tile){
    var t=Math.pow(2,tile.z);
    tile.x=((tile.x%t)+t)%t;
    tile.y=((tile.y%t)+t)%t;
    return tile;
  }

}

function form() {
    console.log("submit");
    ssid = $("#form-ssid").val();
    window.location.hash = "ssid=" + ssid;
    map.overlayMapTypes.removeAt(0);
    map.overlayMapTypes.insertAt(0, new CoordMapType(new google.maps.Size(256, 256)));
    return false;
}

function getUrlVars() {
    var vars = {};
    var parts = window.location.href.replace(/[#&]+([^=&]+)=([^&#]*)/gi, function(m,key,value) {
        vars[key] = value;
    });
    return vars;
}