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
	// FIRST GET A FRESH COPY OF THE WALK DATA
	$_id  		= $_POST["doc_id"];
    $url 		= cfg::$couch_url . "/" . cfg::$couch_users_db . "/" . $_id;
    $response   = doCurl($url);
	$doc 	 	= json_decode(stripslashes($response),1);
	$payload 	= $doc;

	if(isset($_POST["photo_i"])){
		$photo_i = $_POST["photo_i"];

		if(isset($_POST["rotate"])){
			//SAVE ROTATION
			$rotate = $_POST["rotate"]; 
			$payload["photos"][$photo_i]["rotate"] = $rotate;
		}elseif(isset($_POST["delete"])){
			$photo_name 	= "photo_".$photo_i.".jpg";
			$audio_match 	= "audio_".$photo_i."_";

			$payload["photos"][$photo_i]["deleted"] = true;
			// unset ($payload["photos"][$photo_i]);

			if(isset($payload["_attachments"])){
				foreach($payload["_attachments"] as $name => $val){
					if($name == $photo_name){
						unset($payload["_attachments"][$name]);
					}
					if(strpos($name,$audio_match) > -1){
						unset($payload["_attachments"][$name]);
					}
				}
			}

			if(isset($payload["transcriptions"])){
				foreach($payload["transcriptions"] as $name => $val){
					if(strpos($name,$audio_match) > -1){
						unset($payload["transcriptions"][$name]);
					}
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

        $response = doCurl($url, json_encode($payload),"PUT");
		exit;
	}else{
		//SAVE TRANSCRIPTIONS
		foreach($_POST["transcriptions"] as $audio_name => $transcription){
			$txns = str_replace('"','&#34;', $transcription);
			$payload["transcriptions"][$audio_name]["text"] = $txns;
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

if(isset($_POST["pic_id"]) && isset($_POST['photo_num'])&& isset($_POST['coordinates'])){ 	//ajax response to pixelation via portal tool
	$face_coord = json_decode($_POST["coordinates"],1);
	$id = ($_POST["pic_id"]);
	$photo_num = ($_POST["photo_num"]);
	$rotationOffset = $_POST["rotation"];
	$photo_num = 'photo_'.$photo_num . '.jpg';
	$id = $id."_".$photo_num;
	//find rev by curling to couch
	$url = cfg::$couch_url . "/". cfg::$couch_attach_db . "/" .$id;
	$result = doCurl($url);
	echo $url;
	$result = json_decode($result,1);
	$rev = ($result['_rev']);
	$id = ($_POST["pic_id"]);
	//find the offset so canvas can be specified for each image based on portal rotation
	// $rOffset = findRotationOffset(cfg::$couch_url . "/" . cfg::$couch_users_db . "/" . $id);
	// 0 = none, 1 = base, 2 = 90 degree rotation

	$picture = doCurl($url . '/' . $photo_num); //returns the actual image in string format
	$new = imagecreatefromstring($picture); //set the actual picture for editing
	$altered_image = filterFaces($face_coord, $new, $id, $pixel_count, $rotationOffset);
	if(isset($altered_image) && $altered_image){
		echo "./temp/$id.jpg";
		$filepath = "./temp/$id.jpg";
		if(file_exists($filepath))
			unlink("./temp/$id.jpg");
			// echo "exists";
		
		// if(file_exists($filepath))
		// 	unset($filepath);

		imagejpeg($altered_image, $filepath); //save it 
		imagedestroy($altered_image);
		$content_type   = 'image/jpeg';
	 	$attach_url = cfg::$couch_url . "/" . cfg::$couch_attach_db;
	    $couchurl       = $attach_url."/".$id."_$photo_num/".$photo_num."?rev=".$rev;
	    $content_type   = 'image/jpeg';
		$response       = uploadAttach($couchurl, $filepath, $content_type);
		echo $response;
		//refresh page
	}
	exit();
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
	// $rotate 	= isset($doc['photos'][0]['rotate']) ? $doc['photos'][0]['rotate'] : 0;
	$prevnext 	= [];
	foreach($photos as $i => $photo){
		if($i !== intval($photo_i)){
			continue;
		}

		if(!$old && !isset($photo["audios"])){
			$old = "&_old=2";
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

    	$photo_name = $old ? "photo_" . $i . ".jpg" : $photo["name"];
		$ph_id 		= $old ? $_id : $_id . "_" . $photo_name;
		$photo_uri 	= "passthru.php?_id=".$ph_id."&_file=$photo_name" . $old;
		// detectFaces($ph_id,$old, $photo_name);

		$attach_url = "#";
		$audio_attachments = "";
		
		$photo_tags = isset($photo["tags"]) ? $photo["tags"] : array();
		if(isset($photo["audios"])){
			foreach($photo["audios"] as $filename){
				//WONT NEED THIS FOR IOS, BUT FOR NOW CANT TELL DIFF

				$aud_id			= $doc["_id"] . "_" . $filename;
                $attach_url 	= "passthru.php?_id=".$aud_id."&_file=$filename" . $old;
				$audio_src 		= getConvertedAudio($attach_url);
				$confidence 	= appendConfidence($attach_url);
				$script 		= !empty($confidence) ? "This audio was transcribed using Google's API at ".round($confidence*100,2)."% confidence" : "";
				$download 		= cfg::$couch_url . "/".$couch_attach_db."/" . $aud_id . "/". $filename;
				//Works for archaic saving scheme as well as the new one : 
				if(isset($doc["transcriptions"][$filename]["text"])){
					$txns = str_replace('&#34;','"', $doc["transcriptions"][$filename]["text"]);
					$transcription = str_replace('&#34;','"', $doc["transcriptions"][$filename]["text"]);
				}else if(isset($doc["transcriptions"][$filename])){
					$txns = str_replace('&#34;','"', $doc["transcriptions"][$filename]["text"]);
					$transcription = str_replace('&#34;','"', $doc["transcriptions"][$filename]);
				}else{
					$transcription = "";
				}
				$audio_attachments .=   "<div class='audio_clip'>
											<audio controls>
												<source src='$audio_src'/>
											</audio> 
											<a class='download' href='$download' title='right click and save as link to download'>&#8676;</a> 
											<div class='forprint'>$transcription</div>
											<textarea name='transcriptions[$filename]' placeholder='Click the icon and transcribe what you hear'>$transcription</textarea>
											<p id = 'confidence_exerpt'>$script</p>
										</div>";
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
	// rotate= '$doc['photos'][0]['rotate'])'
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
		<a class='preview rotate' rev='$hasrotate' data-photo_i=$photo_i data-doc_id='".$doc["_id"]."' rel='google_map_0' data-long='$long' data-lat='$lat'>
				<canvas class='covering_canvas'></canvas>
				<img id = 'main_photo' src='$photo_uri'/><span></span>

		</a>

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
	echo "<button type = 'button' id = 'pixelateSubmit' style='float:right'>Submit Area</button>";
	echo "<button type = 'button' id = 'pixelate' style='float:right'>Select Area for Pixelation</button>";

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

	$("#pixelate").on("click", function(){
		var doc_id 	= $(".preview span").parent().data("doc_id"); //AFUM23894572093482093.. etc
		var photo_i = $(".preview span").parent().data("photo_i"); //Photo1.jpg
		var rotationOffset = $(".preview").attr("rev"); // rotation

		console.log(rotationOffset);
		$("#pixelateSubmit").off(); //on reclick, turn off events
		$(".covering_canvas").off();
		if($("#pixelate").css("background-color") == 'rgb(255, 0, 0)'){
			$("#pixelate").css("background-color","#4CAF50");
			$(".covering_canvas").css("cursor", "");

		}else{
			$("#pixelate").css("background-color","red");
			drawPixelation(doc_id, photo_i,rotationOffset);		
		}

	});
	

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
function drawPixelation(doc_id = 0, photo_i = 0, rotationOffset){
	var canvas = $(".covering_canvas")[0];
	var width_pic = $("#main_photo")[0].getBoundingClientRect().width;
	var height_pic = $("#main_photo")[0].getBoundingClientRect().height;
	// console.log($("#main_photo")[0].getBoundingClientRect().width);
	setCanvas(canvas, rotationOffset, width_pic, height_pic);
	//css and pixel count set
	
	var ctx = canvas.getContext('2d');
	var canvasx = $(canvas).offset().left;
	var canvasy = $(canvas).offset().top;
	var last_mousex = last_mousey = 0;
	var mousex = mousey = 0;
	var mousedown = false;
	var coord_array = {};
	var data = {};
	var scrollOffset = 0; //necessary for scaling pixels from top of page for Y coordinate

	$("#pixelateSubmit").on("click",function(){
			if(confirm('Are you sure you want to pixelate this area?')){
				$.ajax({
			 		method: "POST",
			  	 	url: "photo.php",
			  	 	data: { pic_id: doc_id, photo_num: photo_i, coordinates: data, rotation: rotationOffset},
			  	 	success:function(response){
			  			console.log(response);
			 	 	}
				});
				$("#pixelate").css("background-color", "#4CAF50"); //change color back to reg
				ctx.clearRect(0,0,canvas.width,canvas.height); //clear rect
				data = {};
				$(canvas).off();	//turn off events 
				$(".covering_canvas").css("cursor", "");
			}

	});

	$(".covering_canvas").on("mousedown", function(e){
		console.log(scrollOffset);
		scrollOffset = window.scrollY;
		last_mousex = parseInt(e.clientX-canvasx);
		last_mousey = parseInt(e.clientY-canvasy+scrollOffset);
		mousedown = true; 
	});

	$(canvas).on('mouseup', function(e) {
			mousedown = false;
			coord_array.width = (mousex-last_mousex);
			coord_array.height = (mousey-last_mousey);
			coord_array.x = last_mousex;
			coord_array.y = last_mousey;
			coord_array.width_pic = width_pic;
			coord_array.height_pic = height_pic;
			if(coord_array.width && coord_array.height){ 
				data = JSON.stringify(coord_array);
			}
	});

	$(canvas).on('mousemove', function(e) {
		scrollOffset = window.scrollY;
	    mousex = parseInt(e.clientX-canvasx);
		mousey = parseInt(e.clientY-canvasy+scrollOffset);
	    if(mousedown) {
	        ctx.clearRect(0,0,canvas.width,canvas.height); //clear canvas
	        ctx.beginPath();
	        var width = mousex-last_mousex;
	        var height = mousey-last_mousey;
	        ctx.rect(last_mousex,last_mousey,width,height);
	        ctx.strokeStyle = 'red';
	        ctx.lineWidth = 2;
	        ctx.stroke();
    	}
	});

}

function setCanvas(canvas, rotationOffset, width_pic, height_pic){
	// $(".covering_canvas").css("width",width_pic).css("height", height_pic);
	$(".covering_canvas").css("cursor", "crosshair");

	//set the canvas for drawing to be the same dimensions as the photo
	canvas.width = width_pic;
	canvas.height = height_pic;
	canvas.style.position = "absolute";
	$(".covering_canvas").css("left",$("#main_photo").position().left);
	$(".covering_canvas").css("top",$("#main_photo").position().top);

	switch(rotationOffset){
		case 0,1,3: 
			canvas.width = width_pic;
			canvas.height = height_pic;
			break;
		case 2,4:
			canvas.width = height_pic;
			canvas.height = width_pic;
		default:
			break;
	}
}

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
		$split 				= explode("=",$attach_url);
		$filename_or_old 	= array_pop($split);

		if($filename_or_old == 1 || $filename_or_old == 2){
			$old_ 			= explode("&",array_pop($split));
			$filename 		= $old_[0];
			$full_proj_code = explode("&",array_pop($split));
		}else{
			$filename 		= $filename_or_old;
			$full_proj_code = explode("_audio",array_pop($split));
		}

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
	}else{
		//if the mp3 already exists just link it 
		$newfile 	= "./temp/".$full_proj_code."_".$noext.".mp3";
	}

	//check if transcription exists on database
	$url            = cfg::$couch_url . "/" . cfg::$couch_users_db . "/" . $full_proj_code;
    $response       = doCurl($url);
	$storage 		= json_decode($response,1);

	if(!isset($storage["transcriptions"]) || !isset($storage["transcriptions"][$filename])){
		$trans = transcribeAudio($cFile,$filename);
		// print_rr($trans);
		if(!empty($trans["transcript"])){
			$storage["transcriptions"][$filename]["text"] = $trans["transcript"];
			$storage["transcriptions"][$filename]["confidence"] = $trans["confidence"];
			$response 	= doCurl($url, json_encode($storage), 'PUT');
	        $resp 		= json_decode($response,1);
	        header("Refresh:0");
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
	// print_rr($resp);
	$count = 0;
	$transcript = '';
	$confidence = 0;
	if(!empty($resp["results"])){
	    foreach($resp["results"] as $results){
	        $transcript = $transcript . $results["alternatives"][0]["transcript"];
	        $confidence = $confidence + $results["alternatives"][0]["confidence"];
	        $count++;
	    }
	}
	if(isset($confidence) && $count != 0){
		$confidence = $confidence / $count;
		$data["transcript"] = $transcript;
		$data["confidence"] = $confidence;
		if($confidence > 0.7)
			return $data;
		
	}
		return "";
}	

function appendConfidence($attach_url){
	$split 			= explode("=",$attach_url);
	$filename 		= $split[count($split) -1];
	$full_proj_code = explode("_audio",$split[1]);
	
	$url            = cfg::$couch_url . "/" . cfg::$couch_users_db . "/" . $full_proj_code[0];
    $response       = doCurl($url);
	$storage 		= json_decode($response,1);
	if(isset($storage["transcriptions"][$filename]["confidence"]))
		return $storage["transcriptions"][$filename]["confidence"];
	else
		return "";
}

function detectFaces($id, $old, $photo_name, $rev){
	if($old){
		if($old == 2)
			$url = cfg::$couch_url . "/disc_attachments/$id";
		else
			$url = cfg::$couch_url . "/".cfg::$couch_users_db."/" . $id;
	}else{
		$url = cfg::$couch_url . "/". cfg::$couch_attach_db . "/" . $id; 
	}
	$result = doCurl($url);
	$meta = json_decode($result,true);
	// echo $url . '/' . $photo_name;
	$picture = doCurl($url . '/' . $photo_name); //returns the actual image in string format
	// $picture = file_get_contents('5faces_landscape.jpg');
	$new = imagecreatefromstring($picture); //set the actual picture for editing
	$pixel_count = imagesy($new)*imagesx($new);
	$picture = base64_encode($picture); //encode so we can send it to API 
	$data = array(
	    "requests" => array(
	        "image" => array(
	        	"content" => $picture
	        ),
	    	"features" => array(
	        	"type" => "FACE_DETECTION",
	        	"maxResults" => 10
	    	)    
	    )
	);

	$vertices = array();
	 //POST to google's service
	$resp = postData('https://vision.googleapis.com/v1/images:annotate?key='.cfg::$gvoice_key,$data);
	//parse response into useable format : XY coordinates per face / IF 
	print_rr ($resp);
	if(!empty($resp['responses'][0])){
		print_rr('detected face '. $id);
		foreach($resp['responses'][0]['faceAnnotations'] as $index => $entry){
	 		$coord = ($entry['boundingPoly']['vertices']);
		 	$put = array();
		 	foreach($coord as $vtx){
				isset($vtx['x']) ? array_push($put, $vtx['x']) : array_push($put, -1);
			 	isset($vtx['y']) ? array_push($put, $vtx['y']) : array_push($put, -1);
			}
			array_push($vertices,$put);
		}
	// print_rr($vertices);
		// $new = imagecreatefromstring(base64_decode($picture)); //set the actual picture for editing

		$altered_image = filterFaces($vertices, $new, $id, $pixel_count);
		if(isset($altered_image) && $altered_image){
			// echo 'inside save';
			$filepath = "./temp/$id";
			imagejpeg($altered_image, $filepath); //save it 
			imagedestroy($altered_image);
			// $attach_url = cfg::$couch_url . "/" . cfg::$couch_attach_db;

		 //    $couchurl       = $attach_url."/".$id."/".$photo_name."?rev=".$rev;
		 //    $content_type   = 'image/jpeg';
			// $response       = uploadAttach($couchurl, $filepath, $content_type);
			// if(isset("./temp/$id"))
			// 	unset("./temp/$id");
// 'https://ourvoice-cdb.med.stanford.edu/disc_attachment/XYZ_1ADF98E2-BFD3-4E64-83FE-D9D39BE12978_1_1512073591362_photo_9.jpg/photo_9.jpg?rev=1-a3b731515e579f5cbe6922a492ead622'

		}

	}

}


function filterFaces($vertices,$image,$id, $pixel_count, $rotationOffset = 0){
	$passed = false;
				echo '<pre>';
				echo(imagesx($image) . " " . imagesy($image));
	if($rotationOffset){ //rotate back
		if($rotationOffset == 1){
			$image = imagerotate($image,-90,0);
		}elseif($rotationOffset ==2){
			$image = imagerotate($image,-180,0);
		}elseif($rotationOffset ==3){
			$image = imagerotate($image,-270,0);
		}
	}
	// imagedestroy($image);
	
	if(count($vertices) == 6){ //from the portal tool
		$scale_factor_x = imagesx($image) / $vertices['width_pic']; //width_pic is the thumbnail size on the portal , imagesx returns FULL res
		$scale_factor_y = imagesy($image) / $vertices['height_pic'];
		// echo $scale_factor_x . " " . $scale_factor_y;
		$width = isset($vertices['width']) ? $vertices['width'] : -1;
		$height = isset($vertices['height']) ? $vertices['height'] : -1;
		if($width != -1 && $height != -1){
			$crop = imagecrop($image,['x'=>$vertices['x']*$scale_factor_x,'y'=>$vertices['y']*$scale_factor_y,'width'=>$width*$scale_factor_x, 'height'=>$height*$scale_factor_y]);
			// pixelate($crop, $scale_pixels,$scale_pixels);
			pixelate($crop);
			//put faces back on the original image
			imagecopymerge($image, $crop, $vertices['x']*$scale_factor_x, $vertices['y']*$scale_factor_y, 0, 0, $width*$scale_factor_x, $height*$scale_factor_y, 100);
			$passed = true;
			imagedestroy($crop);

		}
	}else{
		foreach($vertices as $faces){
			$width = isset($faces[0]) && isset($faces[2]) ? $faces[2] - $faces[0] : 0;
			$height = isset($faces[1]) && isset($faces[7]) ? $faces[7] - $faces[1] : 0;
			$scale_pixels = isset($pixel_count)? ($pixel_count/(50000)) : 15;
			if($width != 0 && $height != 0){
				//have to crop out the faces first then apply filter
				$crop = imagecrop($image,['x'=>$faces[0],'y'=>$faces[1],'width'=>$width, 'height'=>$height]);
				// pixelate($crop, $scale_pixels,$scale_pixels);
				pixelate($crop);
				//put faces back on the original image
				imagecopymerge($image, $crop, $faces[0], $faces[1], 0, 0, $width, $height, 100);
				$passed = true;
				imagedestroy($crop);
			}
			// $gaussian = array(array(1.0, 3.0, 1.0), array(3.0, 4.0, 3.0), array(1.0, 3.0, 1.0));
			// $divisor = array_sum(array_map('array_sum',$gaussian));
			// 	$col = imagecolorallocate($new, 255, 255, 255);
			// 	imagepolygon($new, $faces, 4, $col);
			// 	//imagecrop($new,$faces);
			// for($i = 0 ; $i < $itr ; $i++)
			// 	imageconvolution($crop, $gaussian, $divisor, 0);
		}
	}

	if($rotationOffset){ //rotate back so uploaded image will have the same format
		if($rotationOffset == 1){
			$image = imagerotate($image,90,0);
		}elseif($rotationOffset ==2){
			$image = imagerotate($image,180,0);
		}elseif($rotationOffset ==3){
			$image = imagerotate($image,270,0);
		}
	}
		// imagedestroy($image_r);
	
	//save image locally
	if($passed){
		echo 'yes';
		return $image;

	}else{
		echo 'no';
		return false;
	}
}

function pixelate($image, $pixel_width = 20, $pixel_height = 20){
    if(isset($image)){
	    $height = imagesy($image);
	    $width = imagesx($image);
	    // start from the top-left pixel and keep looping until we have the desired effect
	    for($y = 0; $y < $height; $y += $pixel_height+1){
	        for($x = 0; $x < $width; $x += $pixel_width+1){
	            // get the color for current pixel, make it legible 
	            $rgb = imagecolorsforindex($image, imagecolorat($image, $x, $y));

	            // get the closest color from palette
	            $color = imagecolorclosest($image, $rgb['red'], $rgb['green'], $rgb['blue']);
	            // fill squares with specified width/height
	            imagefilledrectangle($image, $x, $y, $x+$pixel_width, $y+$pixel_height, $color);
	        }       
	    }
	}
}



?>