function drawGMap(o_geotags, i_uniquemap, zoom_level){
	var map_id         = "google_map_" + i_uniquemap;
	var geotags        = o_geotags;
	var walkMap        = [];

    for(var i in geotags) {
		var ltlnpt      = new google.maps.LatLng(geotags[i]["lat"], geotags[i]["lng"]);
		walkMap.push(ltlnpt);
	}

    if(!zoom_level){
        zoom_level = 16;
    }

	var myOptions = {
	    zoom        : zoom_level,
	    center      : walkMap[0],
        scrollwheel : false,
	    mapTypeId   : google.maps.MapTypeId.ROADMAP
	}
	// Create the map
	window[map_id] = new google.maps.Map(document.getElementById(map_id), myOptions);
	
    if(map_id != "google_map_photos"){
        new google.maps.Marker({
            map      : window[map_id],
            position : walkMap[0],
            icon     : {
                path        : google.maps.SymbolPath.CIRCLE,
                scale       : 5,
                fillColor   : "#ffffff",
                strokeColor : "#0000FF",
                fillOpacity : 1
            },
            title: "Starting Point"
        });
    }

    var LatLngBounds   = new google.maps.LatLngBounds();
    var gmarkers       = [];
    if(geotags){
    	for(var i in geotags) {
    		LatLngBounds.extend(walkMap[i]);

            if(map_id == "google_map_photos"){
                var icon    = "http://icons.veryicon.com/48/System/Kameleon/Polaroid.png";
                var scale   = 5;
            }else{
                var scale   = 1
                var icon    = {
                    path: google.maps.SymbolPath.CIRCLE,
                    scale: scale,
                    fillColor: "#008800",
                    strokeColor: "#0000FF",
                    fillOpacity: 0.5
                };
            }

            var marker  = new google.maps.Marker({
                map: window[map_id],
                position: walkMap[i],
                icon: icon,
                title: "Point " + (i + 1)
            });
            marker.extras = {
                 "photo_id"     : geotags[i]["photo_id"]
                ,"photo_src"    : geotags[i]["photo_src"]
            };
            gmarkers.push(marker);

            if(map_id == "google_map_photos") {
                // google.maps.event.addListener(marker, 'mouseover', function(event) {
                //   var photo_id = geotags[i]["photo_id"];
                //   console.log($("#" + photo_id).length);
                //   console.log(photo_id);
                //   $("#" + photo_id).addClass("photoOn");
                // });
                // google.maps.event.addListener(marker, 'mouseout', function(event) {
                //   var photo_id = geotags[i]["photo_id"];
                //   $("#" + photo_id).removeClass("photoOn");
                //   // this.setIcon(icon);
                // });
            }
    	}

    	// Creates the polyline object (connecting the dots)
        var polyline = new google.maps.Polyline({
          map: window[map_id],
          path: walkMap,
          strokeColor: '#0000FF',
          strokeOpacity: 0.7,
          strokeWeight: 0
        });

        window[map_id].fitBounds(LatLngBounds); 
    }
	return gmarkers;
}