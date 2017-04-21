<?php
require_once "common.php";

//session_start();
//session_destroy();

date_default_timezone_set('America/Los_Angeles');

//$couch_proj     	= cfg::$couch_proj_db;
//$couch_db 	    = cfg::$couch_config_db;
$couch_url 	    	= cfg::$couch_url . "/" . cfg::$couch_proj_db . "/" . cfg::$couch_config_db;
//$couch_user 		= cfg::$couch_user;
//$couch_pw 	    = cfg::$couch_pw;

//$_ENV['couch_url'   	] 	='https://ourvoice-cdb.med.stanford.edu'			;
//$_ENV['couch_proj_proj' ] 	='disc_projects';
//$_ENV['couch_config_db'  	] 	='all_projects';
//$_ENV['couch_users_db']  	='disc_users';
//$_ENV['couch_all_db' 	] 	='_all_docs';
//$_ENV['couch_user'   	] 	='disc_user_general';
//$_ENV['couch_pw'    	] 	="rQaKibbDx7rP";
//$_ENV['gmaps_key'		] 	="AIzaSyCn-w3xVV38nZZcuRtrjrgy4MUAW35iBOo";

// FIRST GET THE PROJECT DATA
if( empty($_SESSION["DT"]) ){
//	$couch_proj = cfg::$couch_proj_db;
//	$couch_db 	= $_ENV["couch_config_db"];
//	$couch_url 	= $_ENV["couch_url"] . "/$couch_proj" . "/$couch_db";
//	$couch_user 	= $_ENV["couch_user"];
//	$couch_pw 	= $_ENV["couch_pw"];

	//CURL OPTIONS
//	$ch 		= curl_init($couch_url);
//	curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
//	curl_setopt($ch, CURLOPT_HTTPHEADER, array(
//		"Content-type: application/json",
//		"Accept: */*"
//	));
//	curl_setopt($ch, CURLOPT_USERPWD, "$couch_user:$couch_pw");
//	curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "GET"); //JUST FETCH DATA

	$response 	= doCurl($couch_url);
//	curl_exec($ch);
//	curl_close($ch);

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

if(isset($_POST["for_delete"])){
	$fordelete 	= [];
	foreach($_POST["for_delete"] as $i=> $row){
		$temp 	= explode("|",$row);
		array_push($fordelete,array(
			 "_id" 		=> $temp[0]
			,"_rev" 	=> $temp[1]
			,"_deleted" => true
		));
	}

	// Bulk update docs
    $couch_url 	= cfg::$couch_url . "/" . cfg::$couch_users_db . "/_bulk_docs";
    $response 	= doCurl($couch_url, json_encode(array("docs" => $fordelete)), "POST");
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
//
//
//	$couch_proj = $_ENV["couch_users_db"];
//	$couch_db 	= $_ENV["couch_all_db"];
//	$limit 		= 10;
//	$offset 	= 0; //offset
//	$qs 		= "?include_docs=true";//&limit=$limit&skip=$offset
//
//	$couch_base = $_ENV["couch_url"];
//	$couch_url 	= $couch_base. "/$couch_proj" ."/$couch_db" .$qs;
//	$couch_user 	= $_ENV["couch_user"];
//	$couch_pw 	= $_ENV["couch_pw"];
//	// $couch_url  = "https://ourvoice-cdb.med.stanford.edu/disc_users/_design/hasphotos/_view/all";
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
	echo "<input id='delete_all' type='submit' value='Delete Checked Data Entries'></input>";
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
			// if($tag["accuracy"] <= 50){
				array_push($forjsongeo,$tag);
			// }
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
		echo "<label><input type='checkbox' name='for_delete[]' value='".$doc["_id"]."|".$doc["_rev"]."'/>Delete This Walk</label>";
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
		<button type="submit" class="btn btn-primary">Chose Project</button>
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

	$("#project_summary").submit(function(){
		var yn = confirm("You will delete this entry and all photos/audio/survey answers associated with it.  Click OK to continue.");
		return yn;
	});
});
</script>
</body>
</html>




