function UER() {

    var userEditableRectangle =  null;
    var userEditableRectangleBounds =  null;


    var USER_EDITABLE_RECTANGLE_LAT_MAX = 0.08;
    var USER_EDITABLE_RECTANGLE_LON_MAX = 0.16;

    this.createUserEditableRectangle = function() {
        if(!map) {
            console.log("nelze vytvorit - neexistuje mapa");
            return;
        }
        var center = map.getCenter(); // stred mapy
        var bounds = map.getBounds(); // okraje mapy

        var deltaLat = Math.abs(bounds.getNorthEast().lat() - bounds.getSouthWest().lat());
        var deltaLon = Math.abs(bounds.getNorthEast().lng() - bounds.getSouthWest().lng());

        // vypocet velikosti obdelniku
        if(deltaLat > USER_EDITABLE_RECTANGLE_LAT_MAX/2) {
            deltaLat = USER_EDITABLE_RECTANGLE_LAT_MAX/2;
        }
        if(deltaLon > USER_EDITABLE_RECTANGLE_LON_MAX/2) {
            deltaLon = USER_EDITABLE_RECTANGLE_LON_MAX/2;
        }

        var bounds = new google.maps.LatLngBounds(
            new google.maps.LatLng(center.lat() - deltaLat/6, center.lng() - deltaLon/6),
            new google.maps.LatLng(center.lat() + deltaLat/6, center.lng() + deltaLon/6)
        );

        // remove old rectangle
        if(userEditableRectangle) {
            userEditableRectangle.setMap(null);
            userEditableRectangle = null;
        }

        userEditableRectangle = new google.maps.Rectangle({
            map: map,
            bounds: bounds,
            draggable: true,
            editable: true
        });
        userEditableRectangleBounds = userEditableRectangle.getBounds();
    };

    this.createListener = function() {

        userEditableRectangle.addListener("bounds_changed", function () {

            var bounds = userEditableRectangle.getBounds();

            var deltaLat = Math.abs(bounds.getNorthEast().lat() - bounds.getSouthWest().lat());
            var deltaLon = Math.abs(bounds.getNorthEast().lng() - bounds.getSouthWest().lng());

            var deltaNElat = Math.abs(bounds.getNorthEast().lat() - userEditableRectangleBounds.getNorthEast().lat());
            var deltaNElon = Math.abs(bounds.getNorthEast().lng() - userEditableRectangleBounds.getNorthEast().lng());

            var deltaSWlat = Math.abs(bounds.getSouthWest().lat() - userEditableRectangleBounds.getSouthWest().lat());
            var deltaSWlon = Math.abs(bounds.getSouthWest().lng() - userEditableRectangleBounds.getSouthWest().lng());

            var nBounds = {
                lat1: bounds.getSouthWest().lat(),
                lat2: bounds.getNorthEast().lat(),
                lon1: bounds.getSouthWest().lng(),
                lon2: bounds.getNorthEast().lng()
            };

            var tooBig = false;
            if (deltaLat > USER_EDITABLE_RECTANGLE_LAT_MAX/4+0.001) {

                if (deltaNElat > 0) {
                    nBounds.lat2 = nBounds.lat1 + USER_EDITABLE_RECTANGLE_LAT_MAX/4;
                }
                if (deltaSWlat > 0) {
                    nBounds.lat1 = nBounds.lat2 - USER_EDITABLE_RECTANGLE_LAT_MAX/4;
                }
                tooBig = true;
            }
            if (deltaLon > USER_EDITABLE_RECTANGLE_LON_MAX/4+0.001) {

                if (deltaNElon > 0) {
                    nBounds.lon2 = nBounds.lon1 + USER_EDITABLE_RECTANGLE_LON_MAX/4;
                }
                if (deltaSWlon > 0) {
                    nBounds.lon1 = nBounds.lon2 - USER_EDITABLE_RECTANGLE_LON_MAX/4;
                }
                tooBig = true;
            }

            // southWest, northEast
            bounds = new google.maps.LatLngBounds(
                new google.maps.LatLng(nBounds.lat1, nBounds.lon1),
                new google.maps.LatLng(nBounds.lat2, nBounds.lon2)
            );

            if (tooBig) {
                userEditableRectangle.setBounds(bounds);
            }
            userEditableRectangleBounds = userEditableRectangle.getBounds();
        });
    };

    this.getUserEditableRectangle = function() {
        return userEditableRectangle;
    };

    this.setUserEditableRectangle = function(uer) {
        userEditableRectangle = uer;
    };

    this.setLatMax = function(latmax) {
        USER_EDITABLE_RECTANGLE_LAT_MAX = latmax;
    }

    this.setLngMax = function(lngmax) {
        USER_EDITABLE_RECTANGLE_LON_MAX = lngmax;
    }




}
