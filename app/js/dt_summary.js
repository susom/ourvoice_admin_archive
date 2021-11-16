function drawGMap(o_geotags, i_uniquemap, zoom_level, o_walk_geos){
	var map_id         = "google_map_" + i_uniquemap;
	var geotags        = o_geotags;
	var walkMap        = [];
    for(var i in geotags) {
        var lat = geotags[i].hasOwnProperty("lat") ? geotags[i]["lat"] : geotags[i]["latitude"];
        var lng = geotags[i].hasOwnProperty("lng") ? geotags[i]["lng"] : geotags[i]["longitude"];
    	var ltlnpt     = new google.maps.LatLng(lat, lng);
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

    var good_bad_neutral    = "";
    var gmarkers            = [];
    if(geotags){
    	for(var i in geotags) {
            switch(parseInt(geotags[i]["goodbad"])){
                case 3:
                    good_bad_neutral  = "orange";
                    break;
                case 2:
                    good_bad_neutral  = "green";
                    break;
                case 1:
                    good_bad_neutral  = "red";
                    break;
                default:
                    good_bad_neutral  = "gray";
                    break;
            }
            if(map_id == "google_map_photos"){
                var icon    = "img/marker_"+good_bad_neutral+".png";
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
    	}
        
    	// Creates the polyline object (connecting the dots)
        var polyline = new google.maps.Polyline({
          map: window[map_id],
          path: walkMap,
          strokeColor: '#0000FF',
          strokeOpacity: 0.7,
          strokeWeight: 0
        });
    }
    var LatLngBounds = new google.maps.LatLngBounds();
    if(o_walk_geos){
        for(var i in o_walk_geos) {
            if(o_walk_geos[i]){
                var ltlnpt = new google.maps.LatLng(o_walk_geos[i]["lat"], o_walk_geos[i]["lng"]);
                LatLngBounds.extend(ltlnpt);
            }
        }
    }else{
        for(var i in geotags) {
            LatLngBounds.extend(walkMap[i]);
        }
    }

    window[map_id].fitBounds(LatLngBounds); 
    
//NEW
     // infoWindow = new google.maps.InfoWindow();
    // var test = document.getElementById(map_id);
    // var legend = document.createElement('div');
    // legend.id = "legend";
    // var nt = document.createElement("div");
    // nt.appendChild(document.createTextNode("nice"));
    // nt.id = 'mySlider';
    // legend.appendChild(nt);
    // legend.innerHTML = "<div id = 'mySlider'>YO</div>";


    // window[map_id].controls[google.maps.ControlPosition.TOP_CENTER].push(legend);
    // window[map_id].addListener("click", function(){
    //     alert('yes');
    //     $("#mySlider").slider({
    //         value:100,
    //         min: 0,
    //         max: 500,
    //         step: 50
    //     });
    // });
    // test.html = ('<div id = "mySlider" style = "width:450px;height:150px;"></div>');

    
    // google.maps.event.addListener(window[map_id], 'domready', function(){
    //     $('#Slider').slider({
    //         value:100,
    //         min:0,
    //         max:500,
    //         step:50
    //     });
    // })    

	return gmarkers;
}