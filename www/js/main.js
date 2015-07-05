/* global google */

    var map;
    
    function initialize() {
        var mapOptions = {center:{lat:49.5,lng:15.78},zoom:10, draggableCursor: 'default'};
	    map = new google.maps.Map(document.getElementById('map-canvas'), mapOptions);

        map.overlayMapTypes.insertAt(0, new CoordMapType(new google.maps.Size(256, 256)));

        google.maps.event.addListener(map, 'click', function(event) {
            var url = location.protocol + "//" + location.host + location.pathname + "wifi/processClick";

            var bounds = map.getBounds();

            $.ajax({url: url,data: {
                click_lat: event.latLng.lat(),
                click_lon: event.latLng.lng(),
                map_lat1: bounds.getSouthWest().lat(),
                map_lat2: bounds.getNorthEast().lat(),
                map_lon1: bounds.getSouthWest().lng(),
                map_lon2: bounds.getNorthEast().lng(),
                zoom: map.getZoom()
            }}).done(function(data){
                var infoWindow = new google.maps.InfoWindow();

                infoWindow.setContent(data);
                infoWindow.setPosition(new google.maps.LatLng(event.latLng.lat(),event.latLng.lng()));
                infoWindow.open(map);
            });
        })
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