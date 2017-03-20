<?php
session_start();
session_destroy();

$_ENV['couch_url'   	] 	='https://ourvoice-cdb.med.stanford.edu'			;	
$_ENV['couch_proj_proj' ] 	='disc_projects';
$_ENV['couch_db_proj'  	] 	='all_projects';
$_ENV['couch_proj_users']  	='disc_users';
$_ENV['couch_db_all' 	] 	='_all_docs';
$_ENV['couch_adm'   	] 	='disc_user_general';
$_ENV['couch_pw'    	] 	="rQaKibbDx7rP";
$_ENV['gmaps_key'		] 	="AIzaSyCn-w3xVV38nZZcuRtrjrgy4MUAW35iBOo";

$gmaps_key 					= $_ENV["gmaps_key"];
$projects 					= ["GTT","GNT","CPT"];
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.1//EN" "http://www.w3.org/TR/xhtml11/DTD/xhtml11.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en">
<head>
<meta http-equiv="content-type" content="application/xhtml+xml; charset=UTF-8" />
<link href="css/dt_summary.css" rel="stylesheet" type="text/css"/>
</head>
<body id="main" class="photo_detail">
<?php
if(isset($_GET["_id"]) && isset($_GET["_file"])){
	$_id 	= trim(strtoupper($_GET["_id"]));
	$_file 	= $_GET["_file"];

	$couch_proj = $_ENV["couch_proj_users"];
	$couch_db 	= $_ENV["couch_db_all"];
	$qs 		= "?include_docs=true";

	$couch_base = $_ENV["couch_url"];
	$couch_url 	= $couch_base. "/$couch_proj" ."/$_id";

	$couch_adm 	= $_ENV["couch_adm"]; 
	$couch_pw 	= $_ENV["couch_pw"]; 

	//CURL OPTIONS
	$ch 		= curl_init($couch_url);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
	curl_setopt($ch, CURLOPT_HTTPHEADER, array(
		"Content-type: application/json",
		"Accept: */*"
	));
	curl_setopt($ch, CURLOPT_USERPWD, "$couch_adm:$couch_pw");
	curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "GET"); //JUST FETCH DATA

	$response 	= curl_exec($ch);
	curl_close($ch);

	$doc 		= json_decode(stripslashes($response),1);
	$photos 	= $doc["photos"];
	$_attach 	= !empty($doc["_attachments"]) ? $doc["_attachments"] : null;

	$temp_1 	= explode("_",$_file);
	$temp_2 	= explode(".",$temp_1[1]);
	$photo_i 	= $temp_2[0];

	foreach($photos as $i => $photo){
		if($i !== intval($photo_i)){
			continue;
		}

		$hasaudio 	= !empty($photo["audio"]) ? "has" : "";

		$goodbad 	= "";
		if($photo["goodbad"] > 1){
			$goodbad  .= "<span class='goodbad good'></span>";
		}

		if($photo["goodbad"] == 1 || $photo["goodbad"] == 3){
			$goodbad  .= "<span class='goodbad bad'></span>";
		}

		if(!$photo["goodbad"]){
			$goodbad = "N/A";
		}


		$long 		= $photo["geotag"]["longitude"];
		$lat 		= $photo["geotag"]["latitude"];
		$timestamp  = $photo["geotag"]["timestamp"];

		$photo_name = "photo_".$i.".jpg";
		$photo_uri 	= $couch_base . "/" . $couch_proj . "/" . $doc["_id"] . "/" . $photo_name;
		$photo_uri 	= "passthru.php?_id=".$doc["_id"]."&_file=$photo_name";
		$attach_url = "#";
		$audio_attachments = "";
		if(!empty($photo["audio"])){
			$num_audios = intval($photo["audio"]);
			for($a = 1; $a <= $num_audios; $a++){
				$audio_name = "audio_".$i."_".$a.".wav";
				$attach_url = $couch_base . "/" . $couch_proj . "/" . $doc["_id"] . "/" . $audio_name;
				$attach_url = "passthru.php?_id=".$doc["_id"]."&_file=$audio_name";
				$audio_attachments .= "<div><a href='$attach_url' class='audio $hasaudio'></a> <input  type='text' name='".$doc["_id"]. $audio_name ."' value='' placeholder='Click the icon and transcribe what you hear'></input></div>";
			}
		}
		break;
	}

	echo "<div class='user_entry'>";
	echo "<hgroup>";
	echo "<h4>Photo Detail ". $projects[$doc["project_id"]] ." (".$doc["lang"] .") : 
	<b>".date("F j, Y", floor($doc["geotags"][0]["timestamp"]/1000))." <span class='time'>@".date("g:i a", floor($timestamp/1000))."</span></b> 
	<i>".$doc["_id"]."</i></h4>";
	echo "</hgroup>";

	echo "<aside>
		<div id='google_map_0' class='gmap'></div>
				</aside>";
	echo "<aside>
			<h4>Good or Bad for the community</h4>
			$goodbad
		</aside>";

	echo "<aside>
			<h4>Transcribe Audio</h4>
			$audio_attachments
		</aside>";

	echo "<section class='photo_previews'>";
	echo "<div>";	
	echo "
		<figure>
		<a class='preview' rel='google_map_0' data-long='$long' data-lat='$lat'><img src='$photo_uri' /></a>
		</figure>";
		
		$geotags   = array();
		$geotags[] = array("lat" => $lat, "lng" => $long);
		$json_geo  = json_encode($geotags);
		$gmaps[]   = "drawGMap($json_geo, 0);\n";

	echo "</div>";
	echo "</section>";
	echo "</div>";
}
?>
<script type="text/javascript" src="https://code.jquery.com/jquery-3.1.1.slim.min.js" integrity="sha256-/SIrNqv8h6QGKDuNoLGA4iret+kyesCkHGzVUUV0shc=" crossorigin="anonymous"></script>
<script type="text/javascript" src="https://maps.google.com/maps/api/js?key=<?php echo $gmaps_key; ?>"></script>
<script type="text/javascript" src="js/dt_summary.js"></script>
<script>
function addmarker(latilongi,map_id) {
    var marker = new google.maps.Marker({
        position  : latilongi,
        map       : window[map_id],
        icon      : {
			    path        : google.maps.SymbolPath.CIRCLE,
			    scale       : 8,
			    fillColor   : "#ffffff",
			    fillOpacity : 1
			},
    });
    window[map_id].setCenter(marker.getPosition());
    window.current_preview = marker;
}

$(document).ready(function(){
	<?php
		echo implode($gmaps);
	?>

	window.current_preview = null;
	$(".preview").hover(function(){
		var long 	= $(this).data("long");
		var lat 	= $(this).data("lat"); 
		var map_id 	= $(this).attr("rel");
		var latlng 	= new google.maps.LatLng(lat, long);

		addmarker(latlng,map_id);
	},function(){
		current_preview.setMap(null);
	});

	$(".audio").click(function(){
		var soundclip 	= $(this).attr("href");
		var snd 		= new Audio(soundclip);
		snd.play();

		snd = null;
		return false;
	});
});
</script>
</body>
</html>
