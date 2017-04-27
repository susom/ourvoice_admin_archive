<?php

require_once "common.php";

//session_start();
//session_destroy();

date_default_timezone_set('America/Los_Angeles');

//$_ENV['couch_url'   	] 	='https://ourvoice-cdb.med.stanford.edu'			;
//$_ENV['couch_proj_db'   ] 	='disc_projects';
//$_ENV['couch_config_db'  	] 	='all_projects';
//$_ENV['couch_users_db']  	='disc_users';
//$_ENV['couch_all_db' 	] 	='_all_docs';
//$_ENV['couch_user'   	] 	='disc_user_general';
//$_ENV['couch_pw'    	] 	="rQaKibbDx7rP";
//$_ENV['gmaps_key'		] 	="AIzaSyCn-w3xVV38nZZcuRtrjrgy4MUAW35iBOo";

$gmaps_key 					= cfg::$gmaps_key;
$projects 					= [];

if( isset($_POST["doc_id"]) ){
	$_id  	= $_POST["doc_id"];

//	$couch_base = $_ENV["couch_url"];
//	$couch_proj = $_ENV["couch_users_db"];
//	$couch_url 	= $couch_base. "/$couch_proj" ."/$_id";
//	$couch_user 	= $_ENV["couch_user"];
//	$couch_pw 	= $_ENV["couch_pw"];
//
//	//CURL OPTIONS
//	$ch 		= curl_init($couch_url);
//	curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
//	curl_setopt($ch, CURLOPT_HTTPHEADER, array(
//		"Content-type: application/json",
//		"Accept: */*"
//	));
//	curl_setopt($ch, CURLOPT_USERPWD, "$couch_user:$couch_pw");
//	curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "GET"); //JUST FETCH DATA
//
//	$response 	= curl_exec($ch);
//	curl_close($ch);

    $url = cfg::$couch_url . "/" . cfg::$couch_users_db . "/" . $_id;
	$response = doCurl($url);

	$doc 	 = json_decode(stripslashes($response),1);
	$payload = $doc;

	if(isset($_POST["photo_i"])){
		$photo_i = $_POST["photo_i"];
		if(isset($_POST["rotate"])){
			//SAVE ROTATION
			$rotate = $_POST["rotate"]; 
			$payload["photos"][$photo_i]["rotate"] = $rotate;
		}
		
		if(isset($_POST["delete"])){
			unset($payload["photos"][$photo_i]);
			$photo_name 	= "photo_".$photo_i.".jpg";
			$audio_match 	= "audio_".$photo_i."_";
			foreach($payload["_attachments"] as $name => $val){
				if($name == $photo_name){
					unset($payload["_attachments"][$name]);
				}
				if(strpos($name,$audio_match) > -1){
					unset($payload["_attachments"][$name]);
				}
			}
			foreach($payload["transcriptions"] as $name => $val){
				if(strpos($name,$audio_match) > -1){
					unset($payload["transcriptions"][$name]);
				}
			}
		}
//		putDoc($_id, $payload);
        doCurl($url, json_encode($payload),"PUT");
		exit;
	}else{
		//SAVE TRANSCRIPTIONS
		foreach($_POST["transcriptions"] as $audio_name => $transcription){
			$payload["transcriptions"][$audio_name] = $transcription;
		}
	}
//    putDoc($_id, $payload);
	doCurl($url, json_encode($payload),"PUT");



}
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.1//EN" "http://www.w3.org/TR/xhtml11/DTD/xhtml11.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en">
<head>
<meta http-equiv="content-type" content="application/xhtml+xml; charset=UTF-8" />
<link href="css/dt_summary.css?v=<?php echo time();?>" rel="stylesheet" type="text/css"/>
<link href="css/dt_photo_print.css?v=<?php echo time();?>"  rel="stylesheet" type="text/css" media="print" />
</head>
<body id="main" class="photo_detail">
<?php
if(isset($_GET["_id"]) && isset($_GET["_file"])){
	$_id 	= trim($_GET["_id"]);
	$_file 	= $_GET["_file"];

    $url        = cfg::$couch_url . "/" . cfg::$couch_users_db . "/" . $_id;
    $response   = doCurl($url);

	$doc 		= json_decode(stripslashes($response),1);
	$_rev 		= $doc["_rev"];

	$photos 	= $doc["photos"];
	$_attach 	= !empty($doc["_attachments"]) ? $doc["_attachments"] : null;

	$temp_1 	= explode("_",$_file);
	$temp_2 	= explode(".",$temp_1[1]);
	$photo_i 	= $temp_2[0];

	$prevnext 	= [];
	foreach($photos as $i => $photo){
		if($i !== intval($photo_i)){
			continue;
		}

		//PREV NEXT
		if(isset($photos[$i-1])){
			$prevnext[0] = "photo.php?_id=" . $doc["_id"] . "&_file=photo_" . ($i - 1) . ".jpg";
		}
		if(isset($photos[$i+1])){
			$prevnext[1] = "photo.php?_id=" . $doc["_id"] . "&_file=photo_" . ($i + 1) . ".jpg";
		}

		$hasaudio 	= !empty($photo["audio"]) ? "has" : "";
		$hasrotate 	= isset($photo["rotate"]) ? $photo["rotate"] : 0;
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
		$photo_uri 	= "passthru.php?_id=".$doc["_id"]."&_file=$photo_name";
		
		$attach_url = "#";
		$audio_attachments = "";
		
		if(!empty($photo["audio"])){
			foreach($doc["_attachments"] as $filename => $file){
				$audio_name = "audio_".$i."_";
				if(strpos($filename,$audio_name) > -1){
	                $attach_url = "passthru.php?_id=".$doc["_id"]."&_file=$filename";
					$transcription 		= isset($doc["transcriptions"][$audio_name]) ? $doc["transcriptions"][$audio_name] : "";
					$audio_attachments .= "<div class='audio_clip'><audio controls><source src='$attach_url'/></audio> <a class='download' href='$attach_url' title='right click and save as link to download'>&#8676;</a> 
					<textarea name='transcriptions[$audio_name]' placeholder='Click the icon and transcribe what you hear'>$transcription</textarea></div>";
				}
			}
		}
		break;
	}

	echo "<form id='photo_detail' method='POST'>";
	echo "<input type='hidden' name='doc_id' value='".$doc["_id"]."'/>";
	echo "<div class='user_entry'>";
	echo "<hgroup>";
	echo "<h4>Photo Detail : 
	<b>".date("F j, Y", floor($doc["geotags"][0]["timestamp"]/1000))." <span class='time'>@".date("g:i a", floor($timestamp/1000))."</span></b> 
	<i>".$doc["_id"]."</i></h4>";
	echo "</hgroup>";

	echo "<div class='photobox'>";
	echo "<section class='photo_previews'>";
	echo "<div>";	
	echo "
		<figure>
		<a class='preview rotate' rev='$hasrotate' data-photo_i=$photo_i data-doc_id='".$doc["_id"]."' rel='google_map_0' data-long='$long' data-lat='$lat'><img src='$photo_uri' /><span></span></a>
		</figure>";
		
		$geotags   = array();
		$geotags[] = array("lat" => $lat, "lng" => $long);
		$json_geo  = json_encode($geotags);
		$gmaps[]   = "drawGMap($json_geo, 0, 16);\n";

	echo "</div>";
	echo "</section>";

	echo "<section class='side'>";
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
			<input type='submit' value='Save Transcriptions'/>
		</aside>";

	if(count($prevnext)> 0){
		echo "<aside>";
		if(isset($prevnext[0])){
			echo "<a href='".$prevnext[0]."' class='prev'>Previous Photo</a>";
		}	
		if(isset($prevnext[1])){
			echo "<a href='".$prevnext[1]."' class='next'>Next Photo</a>";
		}
		echo "</aside>";
	}
	echo "</section>";
	echo "</div>";
	echo "</div>";
	echo "</form>";
}
?>
<script type="text/javascript" src="https://cdnjs.cloudflare.com/ajax/libs/autosize.js/3.0.20/autosize.js"></script>
<script src="https://code.jquery.com/jquery-3.2.1.min.js" integrity="sha256-hwg4gsxgFZhOsEEamdOYGBf13FyQuiTwlAQgxVSNgt4=" crossorigin="anonymous"></script>
<script type="text/javascript" src="https://maps.google.com/maps/api/js?key=<?php echo $gmaps_key; ?>"></script>
<script type="text/javascript" src="js/dt_summary.js?v=<?php echo time();?>"></script>
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

	window.snd_o           = null;
	$(".audio").click(function(){
		var soundclip 	= $(this).attr("href");
		window.snd_o	= new Audio(soundclip);
		window.snd_o.play();
		return false;
	});

	$(".preview span").click(function(){
		var rotate = $(this).parent().attr("rev");
		if(rotate < 3){
			rotate++;
		}else{
			rotate = 0;
		}
		$(this).parent().attr("rev",rotate);

		var doc_id 	= $(this).parent().data("doc_id");
		var photo_i = $(this).parent().data("photo_i");
		$.ajax({
		  method: "POST",
		  url: "photo.php",
		  data: { doc_id: doc_id, photo_i: photo_i, rotate: rotate },
		  dataType: "JSON"
		}).done(function( msg ) {
			alert( "Data Saved: " + msg );
		});

		
		return false;
	});

	autosize($('textarea'));
});
</script>
</body>
</html>
<?php 
//function putDoc($_id, $payload){
//	$couch_proj = $_ENV["couch_users_db"];
//	$couch_url 	= $_ENV["couch_url"] . "/$couch_proj" . "/$_id";
//	$couch_user 	= $_ENV["couch_user"];
//	$couch_pw 	= $_ENV["couch_pw"];
//
//	// CURL OPTIONS
//	$ch 		= curl_init($couch_url);
//	curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
//	curl_setopt($ch, CURLOPT_HTTPHEADER, array(
//		"Content-type: application/json",
//		"Accept: */*"
//	));
//	curl_setopt($ch, CURLOPT_USERPWD, "$couch_user:$couch_pw");
//	curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'PUT'); //PUT to UPDATE/CREATE IF NOT EXIST
//	curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
//
//	$response 	= curl_exec($ch);
//	curl_close($ch);
//}
?>