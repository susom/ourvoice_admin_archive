<?php
// ini_set('display_errors', 1);
// ini_set('display_startup_errors', 1);
// error_reporting(E_ALL);
require_once "common.php";
$gmaps_key 	= cfg::$gmaps_key;
$projlist 	= $_SESSION["DT"]["project_list"]; 

// AJAX HANDLING
if( isset($_POST["doc_id"]) ){
	// FOR PHOTOS
	$_id  		= $_POST["doc_id"];
    $url 		= cfg::$couch_url . "/" . cfg::$couch_users_db . "/" . $_id;
	$response 	= doCurl($url);

	$doc 	 	= json_decode(stripslashes($response),1);
	$payload 	= $doc;

	if(isset($_POST["photo_i"])){
		$photo_i = $_POST["photo_i"];
		if(isset($_POST["rotate"])){
			//SAVE ROTATION
			$rotate = $_POST["rotate"]; 
			$payload["photos"][$photo_i]["rotate"] = $rotate;
		}elseif(isset($_POST["delete"])){
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
		}elseif(isset($_POST["tag_text"])){
			//SAVE TAG
			$photo_tag 		= $_POST["tag_text"];
			$json_response 	= array("new_photo_tag" => false, "new_project_tag" => false);
			if(!isset($payload["photos"][$photo_i]["tags"])){
				$payload["photos"][$photo_i]["tags"] = array();
			}
			if(!in_array($photo_tag,$payload["photos"][$photo_i]["tags"])){
				array_push($payload["photos"][$photo_i]["tags"], $photo_tag);
				$json_response["new_photo_tag"] = true;
			}

			if(isset($_POST["proj_idx"])){
				//POSSIBLE NEW PROJECT TAG, SAVE TO disc_projects
				$proj_idx 		= $_POST["proj_idx"];
				$p_url 			= cfg::$couch_url . "/" . cfg::$couch_proj_db . "/" . cfg::$couch_config_db;
				$p_response 	= doCurl($p_url);
				$p_doc 	 		= json_decode(stripslashes($p_response),1);
				$p_payload 		= $p_doc;

				if(!isset($p_payload["project_list"][$proj_idx]["tags"])){
					$p_payload["project_list"][$proj_idx]["tags"] = array();
				}
				if(!in_array($photo_tag,$p_payload["project_list"][$proj_idx]["tags"])){
					array_push($p_payload["project_list"][$proj_idx]["tags"], $photo_tag);
					$json_response["new_project_tag"] = true;
					$_SESSION["DT"]["project_list"][$proj_idx] = $p_payload["project_list"][$proj_idx]; 
				}
				doCurl($p_url, json_encode($p_payload), "PUT");
			}
			echo json_encode($json_response);
		}elseif(isset($_POST["delete_tag_text"])){
			//SAVE TAG

			$photo_tag = $_POST["delete_tag_text"];

			print_r($payload["photos"][$photo_i]["tags"]);
			if(isset($payload["photos"][$photo_i]["tags"])){
				if (($key = array_search($photo_tag, $payload["photos"][$photo_i]["tags"])) !== false) {
				    // print_r($payload["photos"][$photo_i]["tags"]);
				    unset($payload["photos"][$photo_i]["tags"][$key]);
					// print_r($payload["photos"][$photo_i]["tags"]);				
				}
			}
		}
        $tes = doCurl($url, json_encode($payload),"PUT");
		exit;
	}else{
		//SAVE TRANSCRIPTIONS
		foreach($_POST["transcriptions"] as $audio_name => $transcription){
			$txns = str_replace('"','&#34;', $transcription);
			$payload["transcriptions"][$audio_name] = $txns;
		}
	}
	$response 	= doCurl($url, json_encode($payload),"PUT");
	$resp 		= json_decode($response,1);
	if(isset($resp["ok"])){
		$payload["_rev"] = $resp["rev"];
	}else{
		echo "something went wrong:";
	}
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
	$_id 		= trim($_GET["_id"]);
	$_file 		= $_GET["_file"];

    $url        = cfg::$couch_url . "/" . cfg::$couch_users_db . "/" . $_id;
    $response   = doCurl($url);

	$doc 		= json_decode(stripslashes($response),1); //wtf this breaking certain ones? 
	$_rev 		= $doc["_rev"];
	$proj_idx 	= $doc["project_id"];

	if(!isset($_SESSION["DT"]["project_list"][$proj_idx]["tags"])){
		$_SESSION["DT"]["project_list"][$proj_idx]["tags"] = array();
	}

	// filter out low accuracy
    $forjsongeo = array_filter($doc["geotags"],function($tag){
        return $tag["accuracy"] <= 50;
    });

    if(empty($forjsongeo)){
        $forjsongeo = $doc["geotags"]; 
    }

    $walk_geo 	= json_encode($forjsongeo);

	$photos 	= $doc["photos"];
	$device 	= $doc["device"]["platform"];
	$old 		= isset($doc["_attachments"]) ? "&_old=1" : "";
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

		$long 		= isset($photo["geotag"]["lng"]) ? $photo["geotag"]["lng"] : $photo["geotag"]["longitude"];
		$lat 		= isset($photo["geotag"]["lat"]) ? $photo["geotag"]["lat"] : $photo["geotag"]["latitude"];

		$timestamp  = $photo["geotag"]["timestamp"];
		if($lat != 0 | $long != 0){
            $time = time();
            $url = "https://maps.googleapis.com/maps/api/timezone/json?location=$lat,$long&timestamp=$time&key=AIzaSyDCH4l8Q6dVpYgCUyO_LROnCuSE1W9cwak";
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
            $responseJson = curl_exec($ch);
            curl_close($ch);
             
            $response = json_decode($responseJson);
            date_default_timezone_set($response->timeZoneId); 
        }
		$photo_name = $photo["name"];
		$ph_id 		= $_id . "_" . $photo_name;
		$photo_uri 	= "passthru.php?_id=".$ph_id."&_file=$photo_name" . $old;
		
		$attach_url = "#";
		$audio_attachments = "";
		
		$photo_tags = isset($photo["tags"]) ? $photo["tags"] : array();
		if(isset($photo["audios"])){
			foreach($photo["audios"] as $filename){
				//WONT NEED THIS FOR IOS, BUT FOR NOW CANT TELL DIFF
				$aud_id			= $doc["_id"] . "_" . $filename;
                $attach_url 	= "passthru.php?_id=".$aud_id."&_file=$filename" . $old;
				$audio_src 		= getConvertedAudio($attach_url);

				$download 		= cfg::$couch_url . "/".$couch_attach_db."/" . $aud_id . "/". $filename;
				$transcription 	= isset($doc["transcriptions"][$filename]) ? $txns = str_replace('&#34;','"', $doc["transcriptions"][$filename]) : "";
				$audio_attachments .= "<div class='audio_clip'><audio controls><source src='$audio_src'/></audio> <a class='download' href='$download' title='right click and save as link to download'>&#8676;</a> 
				<div class='forprint'>$transcription</div><textarea name='transcriptions[$filename]' placeholder='Click the icon and transcribe what you hear'>$transcription</textarea></div>";
			}
		}else{
			if(!empty($photo["audio"])){
				$ext   = $device == "iOS" ? "wav" : "amr";
				for($j = 1 ; $j <= $photo["audio"]; $j++ ){
					$filename = "audio_".$i."_".$j . "." .$ext;

					//WONT NEED THIS FOR IOS, BUT FOR NOW CANT TELL DIFF
	                $attach_url 	= "passthru.php?_id=".$doc["_id"]."&_file=$filename" . $old;
					$audio_src 		= getConvertedAudio($attach_url);

					$download 		= cfg::$couch_url . "/".$couch_attach_db."/" . $doc["_id"] . "/". $filename;
					$transcription 	= isset($doc["transcriptions"][$filename]) ? $txns = str_replace('&#34;','"', $doc["transcriptions"][$audio_name]) : "";
					$audio_attachments .= "<div class='audio_clip'><audio controls><source src='$audio_src'/></audio> <a class='download' href='$download' title='right click and save as link to download'>&#8676;</a> 
					<div class='forprint'>$transcription</div><textarea name='transcriptions[$filename]' placeholder='Click the icon and transcribe what you hear'>$transcription</textarea></div>";
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
	echo 	"<section class='photo_previews'>";
	echo 		"<div>";	
	echo "
		<figure>
		<a class='preview rotate' rev='$hasrotate' data-photo_i=$photo_i data-doc_id='".$doc["_id"]."' rel='google_map_0' data-long='$long' data-lat='$lat'><img src='$photo_uri' /><span></span></a>
		</figure>";
		
		$geotags   = array();
		$geotags[] = array("lat" => $lat, "lng" => $long);
		$json_geo  = json_encode($geotags);
		$gmaps[]   = "drawGMap($json_geo, 0, 16, $walk_geo);\n";

	echo 		"</div>";
	
	echo 		"<div id='tags'>";
	echo 			"<h4>Photo Tags:</h4>";
	echo 			"<ul class='photopage'>";
					foreach($photo_tags as $idx => $tag){
						echo "<li>$tag<a href='#' class='deletetag' data-deletetag='$tag' data-doc_id='$_id' data-photo_i='$photo_i'>x</a></li>";
					}
					echo "<li class='noback'><a href='#' class='opentag' data-photo_i='$photo_i'>+ Add New Tag</a></li>";
	echo 			"</ul>";
	echo		"</div>";
	echo 	"</section>";

	echo "<section class='side'>";
	echo "<aside>
			<b>lat: $lat long: $long</b>
			<div id='google_map_0' class='gmap'></div>
		</aside>";
	echo "<aside class='forcommunity'>
			<h4>Good or bad for the community?</h4>
			$goodbad
		</aside>";

	echo "<aside>
			<h4>Why did you take this picture?</h4>
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
	echo "<i class='print_only'>Data gathered using the Stanford Healthy Neighborhood Discovery Tool, © Stanford University 2017</i>";
	echo "</section>";
	echo "</div>";
	echo "</div>";
	echo "</form>";
}

$project_tags = $_SESSION["DT"]["project_list"][$proj_idx]["tags"];
include("inc/modal_tag.php");
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

function saveTag(doc_id,photo_i,tagtxt,proj_idx){
	var data = { doc_id: doc_id, photo_i: photo_i, tag_text: tagtxt};
	if(proj_idx){
		data["proj_idx"] = proj_idx
	}

	$.ajax({
		method: "POST",
		url: "photo.php",
		data: data,
		dataType : "JSON",
		success: function(response){
			if(response["new_photo_tag"]){
				//ADD to UI
				var anewtag = $("<li>").text(tagtxt);
				var deletex = $("<a href='#'>").attr("data-deletetag",tagtxt).attr("data-doc_id",doc_id).attr("data-photo_i",photo_i).text("x").addClass("deletetag");
				anewtag.append(deletex);
				$("#tags ul").prepend(anewtag);
			}

			if(response["new_project_tag"]){
				//ADD TAG to modal tags list
				var newli 	= $("<li>");
				var newa 	= $("<a href='#'>").attr("data-doc_id",doc_id).attr("data-photo_i",photo_i).text(tagtxt).addClass("tagphoto");
				newli.append(newa);
				$("#newtag ul").prepend(newli);
				$("#newtag .notags").remove();
			}
		},
		error: function(){
			console.log("error");
		}
	}).done(function( msg ) {
		// no need here
	});
	return;
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

	$("#tags").on("click",".deletetag",function(){
		// get the tag index/photo index
		var doc_id 	= $(this).data("doc_id");
		var photo_i = $(this).data("photo_i");
		var tagtxt 	= $(this).data("deletetag");
		
		var _this 	= $(this);
		$.ajax({
			method: "POST",
			url: "photo.php",
			data: { doc_id: doc_id, photo_i: photo_i, delete_tag_text: tagtxt}
		}).done(function( msg ) {
			_this.parent("li").fadeOut("medium",function(){
				_this.remove();
			});
		});
		return false;
	});
	
	$("#newtag").on("click",".tagphoto",function(){
		// get the tag index/photo index
		var doc_id 	= $(this).data("doc_id");
		var photo_i = $(this).data("photo_i");
		var tagtxt	= $(this).text();

		saveTag(doc_id,photo_i,tagtxt);

		//close tag picker
		$(document).click();
		return false;
	});

	$("#newtag form").submit(function(){
		var doc_id 		= $("#newtag_txt").data("doc_id");
		var photo_i 	= $("#newtag_txt").data("photo_i");
		var proj_idx 	= $("#newtag_txt").data("proj_idx");
		var tagtxt 		= $("#newtag_txt").val();

		if(tagtxt){
			$("#newtag_txt").val("");
			
			// add tag to project's tags and update disc_project
			// ADD new tag to UI
			saveTag(doc_id,photo_i,tagtxt,proj_idx);

			//close tag picker?
			setTimeout(function(){
				$(document).click();
			},750);
		}
		return false
	});

	$(".opentag").click(function(){
		//opens up the tag picker modal
		$("#newtag").fadeIn("fast");
	
		return false;
	});
});

$(document).on('click', function(event) {
	if (!$(event.target).closest('#newtag').length ) {
		$("#newtag").fadeOut("fast",function(){});
	}
});
</script>
</body>
</html>
<?php 
//GET FILE


function getFullUrl($partialUrl){
	$paths = explode("/",$_SERVER["SCRIPT_NAME"]);
	array_unshift($paths,$_SERVER["HTTP_HOST"]);
	array_pop($paths);

	$fullpath = "";
	foreach($paths as $part){
		if($part == ""){
			continue;
		}
		$fullpath .= $part;
		$fullpath .= "/";
	}
	return $fullpath . $partialUrl;
}

function getConvertedAudio($attach_url){
	//FIRST DOWNLOAD THE AUDIO FILE

	$fullURL 	= getFullUrl($attach_url);
	$ch 		= curl_init($fullURL);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
	$data 		= curl_exec($ch);
	$errors 	= curl_error($ch);
	curl_close ($ch);
	$newAudioPath = "";
	if(empty($errors)){
		//THEN EXTRACT THE FILE NAME
		$split 		= explode("=",$attach_url);
		print_rr($split);
		$filename 	= $split[count($split) -1];
		//returns something like  audio_3_1.wav
		$full_proj_code = explode("_audio",$split[1]);
		//returns something akin to : GNT_CBD64DEE-423E-40D1-8CAB-A282031BCCD8_3_1524753518048    _3_1.wav
		
		//save to server as audio_x_x.wav/AMR
		//if(file_exists)
		$localfile 	= "./temp/$filename";
		$file 		= fopen($localfile, "w+");
		
		fputs($file, $data);
		fclose($file);

		//THEN CONVERT THE AUDIO
		$newAudioPath = convertAudio($filename, $full_proj_code[0]); 

	}
	return $newAudioPath;
}

function convertAudio($filename, $full_proj_code){
	// echo 'inside convertAudio';
	// print_rr($filename);
	// print_rr($full_proj_code);
	$split = explode("." , $filename);
	$noext = $split[0];
	
	if (function_exists('curl_file_create')) { // php 5.5+
		  $cFile = curl_file_create("./temp/".$filename);
		} else { // 
		  $cFile = '@' . realpath("./temp/".$filename);
		}

	if(!file_exists("./temp/".$full_proj_code."_".$noext.".mp3")){
	// MAKE THE MP3 FROM locally saved .wav or .amr

		$ffmpeg_url = cfg::$ffmpeg_url; 
		$postfields = array(
				 "file" 	=> $cFile
				,"format" 	=> "mp3"
				,"rate" 	=> 16000
			);

		// CURL OPTIONS
		// POST IT TO FFMPEG SERVICE
		$ch = curl_init($ffmpeg_url);
		curl_setopt($ch, CURLOPT_POST, 'POST'); //PUT to UPDATE/CREATE IF NOT EXIST
		curl_setopt($ch, CURLOPT_POSTFIELDS, $postfields);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		$response = curl_exec($ch);
		curl_close($ch);

		// REPLACE ATTACHMENT
		$newfile 	= "./temp/".$full_proj_code."_".$noext.".mp3";
		$handle 	= fopen($newfile, 'w');
		fwrite($handle, $response); 
	}
	//check if transcription exists on database
	$url            = cfg::$couch_url . "/" . cfg::$couch_users_db . "/" . $full_proj_code;
    $response       = doCurl($url);
	$storage 		= json_decode($response,1);


	if(!isset($storage["transcriptions"]) || !isset($storage["transcriptions"][$filename])){
		$trans = transcribeAudio($cFile,$filename);
		if(!empty($trans)){
			$storage["transcriptions"][$filename] = $trans;
			$response 	= doCurl($url, json_encode($storage), 'PUT');
	        $resp 		= json_decode($response,1);
		}
	}


	//remove extraneous files from server
	$flac = explode(".",$filename);
	if(file_exists('./temp/'.$filename)){
		unlink('./temp/'.$filename);
		// echo 'removing ' . './temp/'.$filename;

	if(file_exists('./temp/'.$flac[0].'.flac'))
		unlink('./temp/'.$flac[0].'.flac');
		// echo 'removing ' . './temp/'.$flac[0].'.flac';

	}

	return $newfile;
}

function transcribeAudio($cFile,$filename){
	$split = explode("." , $filename);
	$noext = $split[0];

	$ffmpeg_url = cfg::$ffmpeg_url; 
	$postfields = array(
			 "file" 	=> $cFile
			,"format" 	=> "flac"
		);

	// CURL OPTIONS
	// POST IT TO FFMPEG SERVICE, Convert to FLAC
	$ch = curl_init($ffmpeg_url);
	curl_setopt($ch, CURLOPT_POST, 'POST'); //PUT to UPDATE/CREATE IF NOT EXIST
	curl_setopt($ch, CURLOPT_POSTFIELDS, $postfields);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
	$response = curl_exec($ch);
	curl_close($ch);

	// REPLACE ATTACHMENT
	$newfile 	= "./temp/".$noext.".flac";
	$handle 	= fopen($newfile, 'w');
	fwrite($handle, $response); 

	//Convert to base 64 for google's API
	$flac = file_get_contents($newfile);
	$flac = base64_encode($flac);

	// WE NEED TO json_encode the base64 of the flac file
	// Set some options 
	$data = array(
	    "config" => array(
	        "encoding" => "FLAC",
	        "languageCode" => "en-US"
	    ),
	   "audio" => array(
	        "content" => $flac
	    )
	);
	$data_string = json_encode($data);                                                              

	//POST to google's service
	$ch = curl_init('https://speech.googleapis.com/v1/speech:recognize?key='.cfg::$gvoice_key);                                                                      
	curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");                                                                     
	curl_setopt($ch, CURLOPT_POSTFIELDS, $data_string);                                                                  
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);                                                                      
	curl_setopt($ch, CURLOPT_HTTPHEADER, array(                                                                          
	   'Content-Type: application/json',                                                                                
	   'Content-Length: ' . strlen($data_string))                                                                       
	);                                
	$resp = curl_exec($ch);
	curl_close($ch);
	$resp = json_decode($resp,1);
	print_rr($resp);
	$count = 0;
	if(!empty($resp["results"])){
	    foreach($resp["results"] as $results){
	        $transcript = $transcript . $results["alternatives"][0]["transcript"];
	        $confidence = $confidence + $confidence["alternatives"][0]["confidence"];
	        $count++;
	    }
	}
	$confidence = $confidence / $count;
	print_rr($confidence);
	if($confidence > 0.7)
		return $transcript;
	else
		return "";
}	

?>