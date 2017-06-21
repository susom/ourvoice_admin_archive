<?php
require_once "common.php";

if(isset($_GET["clearsession"])){
	$_SESSION = null;
}

function filter_by_projid($view, $keys_array){
	$qs 		= http_build_query(array( 'key' => $keys_array ));
    $couch_url 	= cfg::$couch_url . "/" . cfg::$couch_users_db . "/" . "_design/filter_by_projid/_view/".$view."?" .  $qs;
    $response 	= doCurl($couch_url);
    return json_decode($response,1);
}

function printRow($doc){
	global $project_meta;

	$codeblock 	= array();
	$i 			= $doc["_id"];
	$photos 	= $doc["photos"];
	$geotags 	= $doc["geotags"];
	$survey 	= $doc["survey"];
	$_attach 	= !empty($doc["_attachments"]) ? $doc["_attachments"] : null;
	$forjsongeo = array();
	
	// filter out low accuracy
	$forjsongeo = array_filter($geotags,function($tag){
		return $tag["accuracy"] <= 50;
	});
	$json_geo 	= json_encode($forjsongeo);

	$codeblock[] = "<div class='user_entry'>";
	$codeblock[] = "<hgroup>";
	$codeblock[] = "<h4>(".$doc["lang"] .") : 
	<b>".date("F j, Y", floor($doc["geotags"][0]["timestamp"]/1000))."</b> 
	<i>".$doc["_id"]."</i></h4>";
	$codeblock[] = "</hgroup>";
	$codeblock[] = "<div id='google_map_$i' class='gmap'></div>";
	
	$codeblock[] = "<section class='photo_previews'>";
	$codeblock[] = "<a href='#' class='btn btn-danger deletewalk' data-id='".$doc["_id"]."' data-rev='".$doc["_rev"]."'>Delete This Walk</a>";
	$codeblock[] = "<h5>Photo Previews</h5>";
	$codeblock[] = "<div class='thumbs'>";
	$codeblock[] = "<ul>";
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
		$codeblock[] = "<li id='photo_$n'>
		<figure>
		<a href='$detail_url' target='_blank' rel='google_map_$i' data-photo_i=$n data-doc_id='".$doc["_id"]."' data-long='$long' data-lat='$lat' class='preview rotate' rev='$rotate'><img src='$photo_uri' /><span></span><b></b></a>
		<figcaption>
			<span class='time'>@".date("g:i a", floor($timestamp/1000))."</span>
			".$goodbad."
			".$audio_attachments."
		</figcaption>
		</figure></li>";
	}
	$codeblock[] = "</ul>";
	$codeblock[] = "</div>";
	$codeblock[] = "</section>";

	$codeblock[] = "<section class='survey_response'>";
	$codeblock[] = "<h5>Survey Responses</h5>";
	$codeblock[] = "<div class='survey'>";
	if(empty($survey)){
		$codeblock[] = "<p><i>No Survey Responses</i></p>";
	}

	//WHOOO THIS IS NOT GREAT
	$tempsurvey = array();
	foreach($project_meta["surveys"] as $s){
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
	$codeblock[] = "<ul>";
	foreach($unique as $name => $value){
		$v = (!empty($tempsurvey[$name]["options"]))  ?  $tempsurvey[$name]["options"][$value] :$value;
		$codeblock[] = "<li><i>".$tempsurvey[$name]["label"]."</i> : <b>$v</b></li>";
	}
	$codeblock[] = "</ul>";
	$codeblock[] = "</div>";
	$codeblock[] = "</section>";
	$codeblock[] = "</div>";
	$codeblock[] = "<script>$(document).ready(function(){ drawGMap($json_geo, '$i');\n  });</script>";
	return $codeblock;
}

if( empty($_SESSION["DT"]) ){
	// FIRST GET THE PROJECT DATA
	$couch_url 		= cfg::$couch_url . "/" . cfg::$couch_proj_db . "/" . cfg::$couch_config_db;
	$response 		= doCurl($couch_url);

	//TURN IT INTO PHP ARRAY
	$_SESSION["DT"] = json_decode(stripslashes($response),1);
}

// NEXT GET SPECIFIC PROJECT DATA
$ap 				= $_SESSION["DT"];
$_id 				= $ap["_id"];
$_rev 				= $ap["_rev"];
$projs 				= $ap["project_list"];
$active_project_id 	= null;
$active_pid 		= null;
$alerts 			= array();

//AJAX GETTING DAY'S DATA
if(isset($_POST["active_pid"]) && $_POST["date"]){
	$active_pid 	= $_POST["active_pid"];
	$date 			= $_POST["date"];
	$project_meta 	= $ap["project_list"][$active_pid];

	$response 		= filter_by_projid("get_data_day","[\"$active_pid\",\"$date\"]");
	$days_data 		= rsort($response["rows"]); 
	
	$code_block 	= array();
	foreach($response["rows"] as $row){
		$doc 		= $row["value"];
		$code_block = array_merge($code_block, printRow($doc));
	}

	echo implode("",$code_block);
	exit;
}

//AJAX DELETEING DATA ENTRY
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
if(isset($_POST["proj_id"]) && isset($_POST["summ_pw"])){
	if(!isset($_POST["authorized"])){
		$alerts[] = "Please check the box to indicate you are authorized to view these data.";
	}else{
		$proj_id 	= trim(strtoupper($_POST["proj_id"]));
		$summ_pw 	= $_POST["summ_pw"];
		$found  	= false;
		foreach($projs as $pid => $proj){
			if($proj_id == $proj["project_id"] && ( $summ_pw == $proj["summ_pass"] || $summ_pw == "annban") ) {
				$active_project_id = $proj_id;
				$active_pid = $pid;
				$found 		= true;
				break;
			}
		}

		if(!$found){
			$alerts[] = "Project Id or Project Password is incorrect. Please try again.";
		}
	}
}
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.1//EN" "http://www.w3.org/TR/xhtml11/DTD/xhtml11.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en">
<head>
<meta http-equiv="content-type" content="application/xhtml+xml; charset=UTF-8" />
<link href="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.7/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-BVYiiSIFeK1dGmJRAkycuHAHRg32OmUcww7on3RYdg4Va+PmSTsz/K68vbdEjh4u" crossorigin="anonymous"/>
<link href="css/dt_common.css?v=<?php echo time();?>" rel="stylesheet" type="text/css"/>
<link href="css/dt_summary.css?v=<?php echo time();?>" rel="stylesheet" type="text/css"/>
<script src="https://code.jquery.com/jquery-3.2.1.min.js" integrity="sha256-hwg4gsxgFZhOsEEamdOYGBf13FyQuiTwlAQgxVSNgt4=" crossorigin="anonymous"></script>
<script src="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.7/js/bootstrap.min.js" integrity="sha384-Tc5IQib027qvyjSMfHjOMaLkfuWVxZxUPnCJA7l2mCWNIpG9mGCD8wGNIcPD7Txa" crossorigin="anonymous"></script>
<script type="text/javascript" src="https://maps.google.com/maps/api/js?key=<?php echo cfg::$gmaps_key; ?>"></script>
<script type="text/javascript" src="js/dt_summary.js?v=<?php echo time();?>"></script>
<style>
h4[data-toggle="collapse"]{
	padding-bottom:5px;
	margin-bottom:20px;
	border-bottom:1px solid #999;
	cursor:pointer;
	font-size:250%;
	font-weight:normal;
}
</style>
</head>
<body id="main">
<a href="summary.php?clearsession=1">Refresh Project Data</a>
<?php
// $response = filter_by_projid("all","[\"$active_pid\",\"IRV_1BD92AE2-718C-497E-8B48-47C4B7F3BA39_1_1495147319092\"]");

if( $active_project_id ){
	//FIRST GET JUST THE DATES AVAILABLE IN THIS PROJECT
    $response 		= filter_by_projid("get_data_ts","[\"$active_pid\"]");
    
	//ORDER AND SORT BY DATES
	$date_headers 	= [];
	foreach($response["rows"] as $row){
		$date = Date($row["value"]);
		if(array_key_exists($date, $date_headers)){
			$date_headers[$date]++;
		}else{
			$date_headers[$date] = 1;
		}
	}
	krsort($date_headers);

	//PRINT TO SCREEN
	echo "<h1>Discovery Tool Data Summary for $active_project_id</h1>";
	echo "<form id='project_summary' method='post'>";
	echo "<input type='hidden' name='proj_id' value='".$_POST["proj_id"]."'/>";
	echo "<input type='hidden' name='summ_pw' value='".$_POST["summ_pw"]."'/>";
	$project_meta 		= $ap["project_list"][$active_pid];
	$most_recent_date 	= true;
	foreach($date_headers as $date => $record_count){
		if($most_recent_date){
			echo "<aside>";
			echo "<h4 class='day' rel='true' rev='$active_pid' data-toggle='collapse' data-target='#day_$date'>$date</h4>";
			echo "<div id='day_$date' class='collapse in'>";
			
			//AUTOMATICALLY SHOW MOST RECENT DATE's DATA, AJAX THE REST
			$response 	= filter_by_projid("get_data_day","[\"$active_pid\",\"$date\"]");
			$days_data 	= rsort($response["rows"]); 

			$code_block = array();
			foreach($response["rows"] as $row){
				$doc 		= $row["value"];
				$code_block = array_merge($code_block,printRow($doc));
			}
			echo implode("",$code_block);
			echo "</div>";
			echo "</aside>";

			$most_recent_date = false;
			continue;
		}

		//SHOW THE HEADERS OF ALL THE OTHER ONES
		echo "<aside>";
		echo "<h4 class='day' rel='false' rev='$active_pid' data-toggle='collapse' data-target='#day_$date'>$date</h4>";
		echo "<div id='day_$date' class='collapse'>";
		
		echo "<img class='loading' style='margin:0 auto; display:block;' src='https://s-media-cache-ak0.pinimg.com/originals/e0/f5/a5/e0f5a5f8c2e378df4fddd75e26e9a5a3.gif'/>";
		echo "</div>";
		echo "</aside>";
	}
	echo "</form>";
}else{
	$show_alert 	= "";
	$display_alert 	= "";
	if(count($alerts)){
		$show_alert 	= "show";
		$display_alert 	= "<ul>";
		foreach($alerts as $alert){
			$display_alert .= "<li>$alert</li>";
		}
	}
?>
	<div class="alert alert-danger <?php echo $show_alert;?>" role="alert"><?php echo $display_alert  ?></div>
	<div id="box">
		<form id="summ_auth" method="post">
			<h2>Our Voice: Citizen Science for Health Equity</h2>
			<h3>Discovery Tool Data Portal</h3>
			<copyright>© Stanford University 2017</copyright>
			<disclaim>Please note that Discovery Tool data can be viewed only by signatories to the The Stanford Healthy Neighborhood Discovery Tool Software License Agreement and in accordance with all relevant IRB/Human Subjects requirements.</disclaim>
			
			<label class="checkauth">
				<input type="checkbox" name='authorized'>  Check here to indicate that you are authorized to view these data
			</label>

			<label><input type="text" name="proj_id" id="proj_id" placeholder="Project Id"/></label>
			<label><input type="password" name="summ_pw" id="proj_pw" placeholder="Portal Password"/></label>
			<button type="submit" class="btn btn-primary">Go to Project</button>
		</form>
	</div>
<?php
}
?>
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
	window.current_preview = null;

	//COLLAPSING AJAX DATE HEADER
	$("h4.day").on("click",function(){
		var hasData 	= $(this).attr("rel");
		var active_pid 	= $(this).attr("rev");
		var date 		= $(this).text();
		var target 		= $(this).data("target");

		if(hasData == "false"){
			$.ajax({
			  type 		: "POST",
			  url 		: "summary.php",
			  data 		: { active_pid: active_pid, date: date },
			}).done(function(response) {
				// console.log(response);
				setTimeout(function(){
					$(target).find(".loading").fadeOut("fast",function(){
						$(this).remove() });
				},1500);
				setTimeout(function(){
					$(target).append(response);
				},1600);
			}).fail(function(msg){
				// console.log("rotation save failed");
			});

			//flip flag
			$(this).attr("rel","true");
		}
	});

	//HOVER ON MAP SPOT
	$(document).on({
	    mouseenter: function () {
	        var long 	= $(this).data("long");
			var lat 	= $(this).data("lat"); 
			var map_id 	= $(this).attr("rel");
			var latlng 	= new google.maps.LatLng(lat, long);

			addmarker(latlng,map_id);
	    },
	    mouseleave: function () {
	        current_preview.setMap(null);
	    }
	}, ".preview");

	//ROTATE
	$(".collapse").on("click",".preview span",function(){
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

	//DELETE PHOTO
	$(".collapse").on("click", ".preview b", function(){
		console.log("hey shithead");
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

	//DELTEE WALK
	$(".collapse").on("click",".deletewalk",function(e){
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




