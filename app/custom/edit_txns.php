<?php
require_once "../common.php";
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

$pcode      	= "RASI1";
$pfilters   	= array();
$goodbad_filter = array();
$response       = $ds->loadAllProjectThumbs($pcode, $pfilters, $goodbad_filter);

$walks          = array();
$photo_geos     = array();
foreach($response as $row){
    $_id    = $row["id"];
    $ph_i   = $row["photo_i"];
    $photo  = $row["photo"];

    $hasrotate = !empty($photo["rotate"]) ? $photo["rotate"] : 0;

    // GATHER EVERY GEO TAG FOR EVERY PHOTO IN THIS WALK
    if(!empty($photo["geotag"])){
        $filename   = empty($photo["name"]) ? "photo_".$ph_i.".jpg" : $photo["name"];
        $ph_id      = $_id;
        if(array_key_exists("name",$photo)){
            // new style file pointer
            $ph_id  .= "_" .$filename;
        }

        $transform  = array("transform" => "custom_rasi", "rotate" => $hasrotate);
        $photo_uri 	= $ds->getStorageFile(cfg::$gcp_bucketName, $_id, $filename, $transform);

        $photo["geotag"]["photo_src"]   = $photo_uri;
        $photo["geotag"]["goodbad"]     = $photo["goodbad"];
        $photo["geotag"]["photo_id"]    = $_id. "_" . "photo_".$ph_i;

        $audios = array_keys($photo["audios"]);
        $temp   = array();
        foreach($photo["audios"] as $audiofile =>  $audio){
            if(!empty($audio["text"])){
                $temp[] = $audio["text"];
            }
        }
        $photo["geotag"]["audio_txn"]	= !empty($temp) ?  implode("\r\n\r\n",$temp) : "na";

        unset($photo["geotag"]["accuracy"]);
        unset($photo["geotag"]["altitude"]);
        unset($photo["geotag"]["heading"]);
        unset($photo["geotag"]["speed"]);

        $walks[$photo["geotag"]["photo_id"]] = $_id;
        array_push($photo_geos, $photo["geotag"]);
    }
}

if(isset($_POST["action"]) && $_POST["action"] == "makejson"){
    $updated_txns = $_POST["audio_txn"];
    foreach($updated_txns as $idx => $txn){
        if($photo_geos[$idx]["audio_txn"] != $txn){
            $photo_geos[$idx]["audio_txn"] = $txn;
        }
    }

    file_put_contents("rasi1.json", json_encode($photo_geos));
    echo "<p>JSON File Saved</p> <p><a href='#' onclick='location.href=location.href'>Refresh Page to Edit Again</a></p>";
    exit;
}

?>
<style>
form { margin:40px 0;}
form div { display:inline-block; margin:0 10px 20px 10px; }
form img { max-width:365px; max-height:365px; width:365px; height:auto;  display:block; margin-bottom:10px; }
form textarea { width:365px; height:200px; }
</style>
<?php
$used_walk_ids = array();
echo "<form method='POST'>";
echo "<input type='hidden' name='action' value='makejson'/>";
foreach($photo_geos as $idx => $pg){

    $walk_id = $walks[$pg["photo_id"]];
    if( !in_array($walk_id, $used_walk_ids) ){
        array_push($used_walk_ids, $walk_id);
        echo "<h4>$walk_id</h4>";
    }

    echo "<div>";
    echo "<img src='".$pg["photo_src"]."'/>";
    echo "<textarea name='audio_txn[$idx]'>".$pg["audio_txn"]."</textarea>";
    echo "</div>";
}
echo "<p><input type='submit' value='save as JSON now'/></p>"; 
echo "</form>";
exit;
?>



















<script src="https://code.jquery.com/jquery-2.1.3.min.js"></script>
<div id="google_map_photos"></div>
<script type="text/javascript" src="https://maps.google.com/maps/api/js?key=AIzaSyB7bJMYfQLt_xOhecW4RnHRNhdUCv8zE4M"></script>
<style> 
    #google_map_photos { box-shadow:0 0 3px  #888;  width:100%; height:670px; margin: 0px auto 20px; position:absolute; } 
    #sections section:first-child .content-wrapper{ cursor:pointer; } 
    #bodyContent { color:#000;  }
    .gm-style-iw { max-width:405px !important; max-height:800px !important; overflow: auto !important;}
    .gm-style-iw-d{ max-height:inherit !important; min-height:500px;}
    .page-section.section-height--large:not(.content-collection):not(.gallery-section) {
         min-height: 870px; 
    }
    .content-wrapper {
        transition:opacity .25s;
    }
    .content-wrapper.hideit {
        opacity:0;
        z-index:-1;
    }
</style>
<script>
$(document).ready(function(){
    //will this change?
    $("#sections section:first-child .section-background").remove();
    $("#sections section:first-child").prepend($("<div>").attr("id","google_map_photos"));

    $(document).on('click', function (e) {
        if($(e.target).hasClass("gm-ui-hover-effect")){
            return;
        }

        $("#sections section:first-child .content-wrapper").removeClass("hideit");
        $("#sections section:first-child .content-wrapper").height($("#sections section:first-child .content-wrapper").height() - 300);
        $(".gm-style-iw").parent().remove();
        window["google_map_photos"].setOptions({disableDefaultUI:true});
    });

    $("#sections section:first-child").on("click",".content-wrapper", function(e){
        e.stopPropagation();
        
        var map_parent = $(this).parent();
        var par_height = map_parent.outerHeight();

        window["google_map_photos"].setOptions({disableDefaultUI:false});
        $(this).addClass("hideit");
        $(this).height($(this).height() + 300);
        matchMapHeight();
    });

    $("#sections section:first-child").on("click","#google_map_photos" , function(e){
        e.stopPropagation();
    });

    $( window ).resize(function() {
        matchMapHeight();
    });

    matchMapHeight();


    // GET HARDCODED JSON MAP DATA 
    var rasi_json_url = "https://ourvoice-projects.med.stanford.edu/custom/photo_geos.php?file=rasi1";
    $.getJSON( rasi_json_url, function( data ) {
        var gmarkers = drawGMap(data, 'photos', 16);
    });
});
function matchMapHeight() {
    // match height with content wrapper
    var content_wrapper = $("#google_map_photos").next();
    $("#google_map_photos").height( content_wrapper.outerHeight() );
}
function drawGMap(geotags, i_uniquemap, zoom_level){
    var map_id          = "google_map_" + i_uniquemap;
    
    // bound the map to encompass all the photos
    var LatLngBounds    = new google.maps.LatLngBounds();
    for(var i in geotags) {
        var lat = geotags[i].hasOwnProperty("lat") ? geotags[i]["lat"] : null;
        var lng = geotags[i].hasOwnProperty("lng") ? geotags[i]["lng"] : null;
        if(lat && lng){
            var ltlnpt  = new google.maps.LatLng(lat, lng);
            LatLngBounds.extend(ltlnpt);
        }
    }

    const infowindow    = new google.maps.InfoWindow(); // Only one InfoWindow
    
    // Create the map
    window[map_id]      = new google.maps.Map(document.getElementById(map_id), {
        zoom        : 16,
        scrollwheel : false,
        mapTypeId   : google.maps.MapTypeId.SATELLITE,
        disableDefaultUI: true
    });

    function placeMarker( loc ) {
        switch(parseInt(loc["goodbad"])){
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
        var icon    = "https://ourvoice-projects.med.stanford.edu/img/marker_"+good_bad_neutral+".png";

        const marker = new google.maps.Marker({
          position : new google.maps.LatLng( loc.lat, loc.lng ),
          map : window[map_id],
          icon: icon
        });
        google.maps.event.addListener(marker, 'click', function(){

            infowindow.close(); // Close previously opened infowindow 
            var contentString =
            '<div id="content">' +
            '<div id="bodyContent">' +
            '<p><img src="'+loc.photo_src+'" style="display:block; width:100%; height:auto; max-width:365px; max-height:365px; margin: 0 auto;"/></p>' +
            '<p>'+loc.audio_txn+'</p>' +
            '</div>' +
            '</div>';
            infowindow.setContent(contentString);
            infowindow.open(window[map_id], marker);

            var infowindow_height = $("#bodyContent").height();
            console.log("infoheight = ", infowindow_height);


        });
    }

    // // ITERATE ALL LOCATIONS. Pass every location to placeMarker
    geotags.forEach( placeMarker );
    window[map_id].fitBounds(LatLngBounds); 
}   
</script>