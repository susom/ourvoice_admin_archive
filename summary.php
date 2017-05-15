<?php
require_once "common.php";

//session_start();
//session_destroy();

date_default_timezone_set('America/Los_Angeles');

$couch_url 	    	= cfg::$couch_url . "/" . cfg::$couch_proj_db . "/" . cfg::$couch_config_db;

// FIRST GET THE PROJECT DATA
if( empty($_SESSION["DT"]) ){
	$response 	= doCurl($couch_url);

	//TURN IT INTO PHP ARRAY
	$_SESSION["DT"] = json_decode(stripslashes($response),1);
}

// NEXT GET SPECIFIC PROJECT DATA
$ap 				= $_SESSION["DT"];
$_id 				= $ap["_id"];
$_rev 				= $ap["_rev"];
$projs 				= $ap["project_list"];
$projects 			= [];
$active_project_id 	= null;

if(isset($_POST["for_delete"]) && $_POST["for_delete"]){
	$_id 	= $_POST["doc_id"];
	$_rev 	= $_POST["rev"];

	$fordelete      = [];
	array_push($fordelete, array(
	         "_id"          => $_id
	        ,"_rev"         => $_rev
	        ,"_deleted" => true
		));

	// Bulk update docs
    $couch_url 	= cfg::$couch_url . "/" . cfg::$couch_users_db . "/_bulk_docs";
    $response 	= doCurl($couch_url, json_encode(array("docs" => $fordelete)), "POST");
	exit;
}

//NOW LOGIN TO YOUR PROJECT
if(isset($_POST["proj_id"]) && isset($_POST["proj_pw"])){
	$proj_id = trim(strtoupper($_POST["proj_id"]));
	$proj_pw = $_POST["proj_pw"];

	foreach($projs as $pid => $proj){
		array_push($projects, $proj["project_id"]);
		if($proj_id == $proj["project_id"] && $proj_pw == $proj["project_pass"]){
			$active_project_id = $proj_id;
		}
	}
}


?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.1//EN" "http://www.w3.org/TR/xhtml11/DTD/xhtml11.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en">
<head>
<meta http-equiv="content-type" content="application/xhtml+xml; charset=UTF-8" />
    <link href="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.7/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-BVYiiSIFeK1dGmJRAkycuHAHRg32OmUcww7on3RYdg4Va+PmSTsz/K68vbdEjh4u" crossorigin="anonymous"/>
    <link href="css/dt_summary.css?v=<?php echo time();?>" rel="stylesheet" type="text/css"/>
</head>
<body id="main">
<?php
if( $active_project_id ){
	// Build query string
	$qs = http_build_query(array(
	        'include_docs' => 'true'
    ));
    $couch_url = cfg::$couch_url . "/" . cfg::$couch_users_db . "/" . cfg::$couch_all_db . "?" . $qs;
    $response = doCurl($couch_url);


	//TURN IT INTO PHP ARRAY
	$all_projects 	= json_decode($response,1);
	// $tot_rows 		= $all_projects["total_rows"];
	// $tot_pages 		= ceil($tot_rows/$limit); 

	echo "<h1>Discovery Tool Data Summary for $active_project_id</h1>";
	$gmaps 			= array();
	$proj 			= array();
	foreach($ap["project_list"] as $p){
		$proj[$p["project_id"]] = $p;
	}

	$active_project = array();
	foreach($all_projects["rows"] as  $i => $row){
		if(strpos($row["id"],$active_project_id) > -1){
			$doc 	= $row["doc"];
			$temp 	= explode("_",$row["doc"]["_id"]);
			$active_project[array_pop($temp)] = $doc;
		}
	}
	krsort($active_project);

	echo "<form id='project_summary' method='post'>";
	echo "<input type='hidden' name='proj_id' value='".$_POST["proj_id"]."'/>";
	echo "<input type='hidden' name='proj_pw' value='".$_POST["proj_pw"]."'/>";

	foreach($active_project  as $i =>  $doc){
		$parent 	= $proj[$active_project_id];
		$photos 	= $doc["photos"];
		$geotags 	= $doc["geotags"];
		$survey 	= $doc["survey"];
		$_attach 	= !empty($doc["_attachments"]) ? $doc["_attachments"] : null;
		$forjsongeo = array();
		
		// filter low accuracy
		foreach($geotags as $tag){
			if($tag["accuracy"] <= 50){
				array_push($forjsongeo,$tag);
			}
		}
		$json_geo 	= json_encode($forjsongeo);

		if(empty($photos) || strpos($doc["_id"], "_design") > -1){
			continue;
		}

		echo "<div class='user_entry'>";
		echo "<hgroup>";
		echo "<h4>Project ". $active_project_id ." (".$doc["lang"] .") : 
		<b>".date("F j, Y", floor($doc["geotags"][0]["timestamp"]/1000))."</b> 
		<i>".$doc["_id"]."</i></h4>";
		echo "</hgroup>";
		echo "<div id='google_map_$i' class='gmap'></div>";
		
		echo "<section class='photo_previews'>";
		echo "<a href='#' class='btn btn-danger deletewalk' data-id='".$doc["_id"]."' data-rev='".$doc["_rev"]."'/>Delete This Walk</a>";
		echo "<h5>Photo Previews</h5>";
		echo "<div class='thumbs'>";
		echo "<ul>";
		foreach($photos as $n => $photo){
			if(is_null($photo)){
				continue;
			}
			$hasaudio 	= !empty($photo["audio"]) ? "has" : "";
			$long 		= $photo["geotag"]["longitude"];
			$lat 		= $photo["geotag"]["latitude"];
			$timestamp  = $photo["geotag"]["timestamp"];

			$goodbad 	= "";
			if($photo["goodbad"] > 1){
				$goodbad  .= "<span class='goodbad good'></span>";
			}

			if($photo["goodbad"] == 1 || $photo["goodbad"] == 3){
				$goodbad  .= "<span class='goodbad bad'></span>";
			}

			$rotate 	= isset($photo["rotate"]) ? $photo["rotate"] : 0;
			$photo_name = "photo_".$n.".jpg";

			// $photo_uri 	= $couch_base . "/" . $couch_proj . "/" . $doc["_id"] . "/" . $photo_name;
			$photo_uri 	= "passthru.php?_id=".$doc["_id"]."&_file=$photo_name";
			$detail_url = "photo.php?_id=".$doc["_id"]."&_file=$photo_name";

			$attach_url = "#";
			$audio_attachments = "";
			if(!empty($photo["audio"])){
				$num_audios = intval($photo["audio"]);
				$num 		= $num_audios > 1 ? "<span>x$num_audios</span>" :"";
				$audio_attachments .= "<a class='audio $hasaudio'></a> $num";
			}
			echo "<li id='photo_$n'>
			<figure>
			<a href='$detail_url' target='_blank' rel='google_map_$i' data-photo_i=$n data-doc_id='".$doc["_id"]."' data-long='$long' data-lat='$lat' class='preview rotate' rev='$rotate'><img src='$photo_uri' /><span></span><b></b></a>
			<figcaption>
				<span class='time'>@".date("g:i a", floor($timestamp/1000))."</span>
				".$goodbad."
				".$audio_attachments."
			</figcaption>
			</figure></li>";
		// <span class='on_map'>$lat $long</span>
		}
		echo "</ul>";
		echo "</div>";
		echo "</section>";

		echo "<section class='survey_response'>";
		echo "<h5>Survey Responses</h5>";
		echo "<div class='survey'>";
		if(empty($survey)){
			echo "<p><i>No Survey Responses</i></p>";
		}

		//WHOOO THIS IS NOT GREAT
		$tempsurvey = array();
		foreach($parent["surveys"] as $s){
			$tempoptions = array();
			if(isset($s["options"])){
				foreach($s["options"] as $o){
					$tempoptions[$o["value"]] = $o["en"]; 
				}
			}else{
				$tempoptions = null;
			}
			$tempsurvey[$s["name"]] = array(
					"label" => $s["label"]["en"]
					,"options" =>  $tempoptions
				);
		}

		$unique = array();
		foreach($survey as $q){
			$unique[$q["name"]] = $q["value"];
		}
		echo "<ul>";
		foreach($unique as $name => $value){
			$v = (!empty($tempsurvey[$name]["options"]))  ?  $tempsurvey[$name]["options"][$value] :$value;
			echo "<li><i>".$tempsurvey[$name]["label"]."</i> : <b>$v</b></li>";
		}
		echo "</ul>";
		echo "</div>";
		echo "</section>";
		echo "</div>";
		$gmaps[] = "drawGMap($json_geo, $i);\n";
	}
	echo "</form>";
}else{
	?>
	<form method="post">
		<h2>Admin Login to view Project Data</h2>
		<label><input type="text" name="proj_id" id="proj_id" placeholder="Project Id"/></label>
		<label><input type="password" name="proj_pw" id="proj_pw" placeholder="Project Password"/></label>
		<button type="submit" class="btn btn-primary">Go to Project</button>
	</form>
	<?php
}
?>
<script src="https://code.jquery.com/jquery-3.2.1.min.js" integrity="sha256-hwg4gsxgFZhOsEEamdOYGBf13FyQuiTwlAQgxVSNgt4=" crossorigin="anonymous"></script>
<script src="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.7/js/bootstrap.min.js" integrity="sha384-Tc5IQib027qvyjSMfHjOMaLkfuWVxZxUPnCJA7l2mCWNIpG9mGCD8wGNIcPD7Txa" crossorigin="anonymous"></script>
<script type="text/javascript" src="https://maps.google.com/maps/api/js?key=<?php echo cfg::$gmaps_key; ?>"></script>
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
		  type 		: "POST",
		  url 		: "photo.php",
		  data 		: { doc_id: doc_id, photo_i: photo_i, rotate: rotate },
		}).done(function(response) {
			// console.log("rotation saved");
		}).fail(function(msg){
			// console.log("rotation save failed");
		});
		return false;
	});

	$(".preview b").click(function(){
		var doc_id 	= $(this).parent().data("doc_id");
		var photo_i = $(this).parent().data("photo_i"); 
		
		var deleteyes = confirm("Please, confirm you are deleting this photo and its associated audio.");
		if(deleteyes){
			$.ajax({
			  type 		: "POST",
			  url 		: "photo.php",
			  data 		: { doc_id: doc_id, photo_i: photo_i, delete: true }
			}).done(function(response) {
				$("#photo_"+photo_i).fadeOut("fast",function(){
					$(this).remove();
				});
			}).fail(function(response){
				// console.log("delete failed");
			});
		}
		return false;
	});

	$(".deletewalk").click(function(e){
		e.preventDefault();
		var _id 	= $(this).data("id");
		var last4 	= _id.substr(_id.length - 4);
		var _rev 	= $(this).data("rev");

		var _parent	= $(this).closest(".user_entry");

		var confirm = prompt("Deleting this walk will also delete all photos, audio, maps and survey data attached to it.  To confirm deletion type in the last 4 digits of the walk ID");
		if(confirm == last4){
			//AJAX DELETE IT
			$.ajax({
			  type 		: "POST",
			  url 		: "summary.php",
			  data 		: { doc_id: _id, rev: _rev , for_delete: true},
			}).done(function(response) {
				_parent.slideUp("medium");
			}).fail(function(msg){
				// console.log("rotation save failed");
			});
		}
		return false;
	});
});
</script>
</body>
</html>




