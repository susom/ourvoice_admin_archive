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

$gmaps_key 		= $_ENV["gmaps_key"];

// FIRST GET THE PROJECT DATA
if( empty($_SESSION["DT"]) ){
	$couch_proj = $_ENV["couch_proj_proj"]; 
	$couch_db 	= $_ENV["couch_db_proj"]; 
	$couch_url 	= $_ENV["couch_url"] . "/$couch_proj" . "/$couch_db";
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

	//TURN IT INTO PHP ARRAY
	$_SESSION["DT"] = json_decode(stripslashes($response),1);
}
$ap 				= $_SESSION["DT"];
$_id 				= $ap["_id"];
$_rev 				= $ap["_rev"];
$projs 				= $ap["project_list"];
$active_project_id 	= null;

if(isset($_POST["proj_id"]) && isset($_POST["proj_pw"])){
	$proj_id = trim(strtoupper($_POST["proj_id"]));
	$proj_pw = $_POST["proj_pw"];
	foreach($projs as $proj){
		if($proj_id == $proj["project_id"] && $proj_pw == $proj["project_pass"]){
			$active_project_id = $proj_id;
			break;
		}else{
			continue;
		}
	}
}
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.1//EN" "http://www.w3.org/TR/xhtml11/DTD/xhtml11.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en">
<head>
<meta http-equiv="content-type" content="application/xhtml+xml; charset=UTF-8" />
<link href="css/dt_summary.css" rel="stylesheet" type="text/css"/>
</head>
<body id="main" class="photo_detail">
<?php
if( $active_project_id ){
	$couch_proj = $_ENV["couch_proj_users"];
	$couch_db 	= $_ENV["couch_db_all"];
	$qs 		= "?include_docs=true";

	$couch_base = $_ENV["couch_url"];
	$couch_url 	= $couch_base. "/$couch_proj" ."/$couch_db" .$qs;
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

	//TURN IT INTO PHP ARRAY
	$all_projects 	= json_decode(stripslashes($response),1);

	$projects 	 	= ["GTT","GNT","CPT"];
	$gmaps 		   	= array();
	$proj 		    = array();
	foreach($ap["project_list"] as $p){
		$proj[$p["project_id"]] = $p;
	}

	$active_project = array();
	foreach($all_projects["rows"] as $i=> $row){
		if(strpos($row["id"],$active_project_id) > -1){
			$doc 	= $row["doc"];
			$temp 	= explode("_",$row["doc"]["_id"]);
			$active_project[array_pop($temp)] = $doc;
		}
	}
	krsort($active_project);

	foreach($active_project  as $i =>  $doc){
		$parent 	= $proj[$projects[$doc["project_id"]]];
		$photos 	= $doc["photos"];
		$geotags 	= $doc["geotags"];
		$survey 	= $doc["survey"];
		$_attach 	= !empty($doc["_attachments"]) ? $doc["_attachments"] : null;
		$json_geo 	= json_encode($geotags);

		if(empty($photos)){
			continue;
		}

		foreach($photos as $n => $photo){
			$hasaudio 	= !empty($photo["audio"]) ? "has" : "";
			$isgood 	= !empty($photo["goodbad"]) ? ($photo["goodbad"] > 1 ? "good" : "bad" ): "";
			$long 		= $photo["geotag"]["longitude"];
			$lat 		= $photo["geotag"]["latitude"];
			$timestamp  = $photo["geotag"]["timestamp"];

			$photo_name = "photo_".$n.".jpg";
			$photo_uri 	= $couch_base . "/" . $couch_proj . "/" . $doc["_id"] . "/" . $photo_name;
			$photo_uri 	= "passthru.php?_id=".$doc["_id"]."&_file=$photo_name";
			$attach_url = "#";
			$audio_attachments = "";
			if(!empty($photo["audio"])){
				$num_audios = intval($photo["audio"]);
				for($a = 1; $a <= $num_audios; $a++){
					$audio_name = "audio_".$n."_".$a.".wav";
					$attach_url = $couch_base . "/" . $couch_proj . "/" . $doc["_id"] . "/" . $audio_name;
					$attach_url = "passthru.php?_id=".$doc["_id"]."&_file=$audio_name";
					$audio_attachments .= "<a href='$attach_url' class='audio $hasaudio'></a> <textarea  type='text' name='".$doc["_id"]. $audio_name ."' value='' placeholder='Click the icon and transcribe what you hear'></textarea>";
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
			<div id='google_map_$i' class='gmap'></div>
			<br clear=all/>
			<span class='goodbad $isgood'></span>
			</aside>";
		echo "<section class='photo_previews'>";
		echo "<div>";
		

		echo "
			<figure>
			<a class='preview' rel='google_map_$i' data-long='$long' data-lat='$lat'><img src='$photo_uri' /></a>
			<figcaption>
				".$audio_attachments."
			</figcaption>
			</figure>";
			
			$geotags   = array();
			$geotags[] = array("lat" => $lat, "lng" => $long);
			$json_geo  = json_encode($geotags);
			$gmaps[]   = "drawGMap($json_geo, $i);\n";

		echo "</div>";
		echo "</section>";
		echo "</div>";
		break;
	}
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



<style>
.photo_detail .user_entry {
	min-width:1200px;
}	

.photo_detail figure{
	margin:0;
}

.photo_detail figure img{
	max-width:740px;
}

.photo_detail figure figcaption{
	margin:10px 0;
}

.photo_detail aside{
	float:right;
	overflow:hidden;
	text-align:center;
}

.photo_detail aside span {
	clear: both;
	display:inline-block;
	width:200px;
	height:200px;
	background-size:200%;
	margin:20px auto;
}

.photo_detail .audio {
	width:50px;
	height:50px;
	vertical-align: middle;
	margin-right:10px;
}

.photo_detail textarea{
	display:inline-block;
	width:660px;
	height:80px;
	vertical-align: middle
}
</style>