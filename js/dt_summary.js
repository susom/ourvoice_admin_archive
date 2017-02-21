function drawMarker(map_id){
	var map 			= new google.maps.Map(document.getElementById(map_id), myOptions);
	for(var i = 0; i < geotags.length; i++) {
		new google.maps.Marker({
			map: map,
			position: walkMap[0],
			icon: {
			    path: google.maps.SymbolPath.CIRCLE,
			    scale: 3,
			    fillColor: "#008800",
			    strokeColor: "#0000FF",
			    fillOpacity: 1

			},
			title: "Point " + (i + 1)
		});
	}

	return;
}

function drawGMap(o_geotags, i_uniquemap){
	var latLngBounds    = new google.maps.LatLngBounds();
	var map_id 			= "google_map_" + i_uniquemap;
	var geotags 		= o_geotags;
	var walkMap 		= [];

	for(var i = 0; i < geotags.length; i++) {
		var ltlnpt 	= new google.maps.LatLng(geotags[i]["lat"], geotags[i]["lng"]);
		walkMap.push(ltlnpt);
	  	latLngBounds.extend(ltlnpt);
	}

	var myOptions = {
	    zoom        : 18,
	    center      : walkMap[0],
	    mapTypeId   : google.maps.MapTypeId.ROADMAP
	}

	// Create the map
	var map 			= new google.maps.Map(document.getElementById(map_id), myOptions);

	for(var i = 0; i < geotags.length; i++) {
		new google.maps.Marker({
			map: map,
			position: walkMap[0],
			icon: {
			    path: google.maps.SymbolPath.CIRCLE,
			    scale: 3,
			    fillColor: "#008800",
			    strokeColor: "#0000FF",
			    fillOpacity: 1

			},
			title: "Point " + (i + 1)
		});
	}

	// Creates the polyline object
	var polyline = new google.maps.Polyline({
	  map: map,
	  path: walkMap,
	  strokeColor: '#0000FF',
	  strokeOpacity: 0.7,
	  strokeWeight: 1
	});
	return;
}