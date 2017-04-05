function drawGMap(o_geotags, i_uniquemap, zoom_level){
	var map_id 			= "google_map_" + i_uniquemap;
	var geotags 		= o_geotags;
	var walkMap 		= [];

	var latLngBounds    = new google.maps.LatLngBounds();
	for(var i = 0; i < geotags.length; i++) {
		var ltlnpt 	= new google.maps.LatLng(geotags[i]["lat"], geotags[i]["lng"]);
		walkMap.push(ltlnpt);
	}

    if(!zoom_level){
        zoom_level = 32;
    }

	var myOptions = {
	    zoom        : zoom_level,
	    center      : walkMap[0],
	    mapTypeId   : google.maps.MapTypeId.ROADMAP
	}

	// Create the map
	window[map_id] = new google.maps.Map(document.getElementById(map_id), myOptions);

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

    if(geotags.length > 1){
    	for(var i = 1; i < geotags.length; i++) {
    		latLngBounds.extend(walkMap[i]);

    		new google.maps.Marker({
                map: window[map_id],
                position: walkMap[i],
                icon: {
                    path: google.maps.SymbolPath.CIRCLE,
                    scale: 1,
                    fillColor: "#008800",
                    strokeColor: "#0000FF",
                    fillOpacity: 0.5

                },
                title: "Point " + (i + 1)
            });
    	}

    	// // Creates the polyline object
     //    var polyline = new google.maps.Polyline({
     //      map: window[map_id],
     //      path: walkMap,
     //      strokeColor: '#0000FF',
     //      strokeOpacity: 0.7,
     //      strokeWeight: 1
     //    });

        window[map_id].fitBounds(latLngBounds); 
    }
    
	return;
}