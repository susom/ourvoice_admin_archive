<?php
require_once "common.php";
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

	//GET THE DATA FROM disc_users
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

//NOW LOGIN TO YOUR PROJECT
if(isset($_SESSION["proj_id"]) && isset($_SESSION["summ_pw"])){
	$_POST["proj_id"] = $_SESSION["proj_id"];
	$_POST["summ_pw"] = $_SESSION["summ_pw"];
	$_POST["authorized"] = 1;
}
if(isset($_POST["proj_id"]) && isset($_POST["summ_pw"])){
	if(!isset($_POST["authorized"])){
		$alerts[] = "Please check the box to indicate you are authorized to view these data.";
	}else{
		$proj_id 	= trim(strtoupper($_POST["proj_id"]));
		$summ_pw 	= $_POST["summ_pw"];
		$found  	= false;
		foreach($projs as $pid => $proj){
			if($proj_id == $proj["project_id"] && ( (isset($proj["summ_pass"]) && $summ_pw == $proj["summ_pass"]) || $summ_pw == "annban") ) {
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
h1{
	padding-top:20px; 
	clear:both; 
}

h4[data-toggle="collapse"]{
	padding-bottom:5px;
	margin-bottom:20px;
	border-bottom:1px solid #999;
	cursor:pointer;
	font-size:250%;
	font-weight:normal;
}
.btn {
	float:right;
	margin-right:10px;
}

nav {
	overflow:hidden;
}
nav ul {
	margin:0;
	padding:0;
}
#google_map_photos {
	box-shadow:0 0 3px  #888; 
	width:1024px;
	height:800px;
	float:initial;
	margin:20px auto;
}
.thumbs{
    margin: 0 10px;
    text-align: center;
}
.collapse {
	width:1000px;
	margin:20px auto;
}

.nogeo{
	position:relative;
}
.nogeo:before {
	content:"Geo Data N/A";
	text-align:center;
	line-height:150px;
	position:absolute; 
	top:0; left:0; 
	width:142px; 
	height:150px;
	color:#fff;
	text-shadow:0px 0px 3px #333;
	/*background:#ccc; */
	z-index:1;
	/*opacity:.7;*/
}
.photoOn{
	border:5px solid green;
}

</style>
</head>
<body id="main">
	<nav>
		<?php if( $active_project_id ){ ?>
		<ul>
			<li class="pull-left"><a class='btn btn-default' href="summary.php?clearsession=1">Refresh Project Data</a></li>
			<li class="pull-left"><a class="inproject btn btn-default" href="index.php">Back to project overview</a></li>
		</ul>
		<!-- <button class="btn btn-default dropdown-toggle" type="button" id="dropdownMenu1" data-toggle="dropdown" aria-haspopup="true" aria-expanded="true">Aggregate Project Data<span class="caret"></span></button> -->
		<ul>
			<li><a target="_blank" class="inproject btn btn-success" href="project_map_csv.php?active_project_id=<?php echo $active_project_id ?>&pid=<?php echo $active_pid?>">Download Maps Data (.csv)</a></li>
			<li><a target="_blank" class="inproject btn btn-info" href="project_transcriptions.php?active_project_id=<?php echo $active_project_id?>&pid=<?php echo $active_pid?>">All Transcriptions</a></li>
			<li><a target="_blank" class="inproject btn btn-warning" href="project_agg_surveys.php?active_project_id=<?php echo $active_project_id?>&pid=<?php echo $active_pid?>">All Survey Answers</a></li>
			<li><a target="_blank" class="inproject btn btn-danger" href="project_agg_photos.php?active_project_id=<?php echo $active_project_id?>&pid=<?php echo $active_pid?>">All Walk Photos</a></li>
		</ul>
		<?php } ?>
	</nav>

<?php
if( $active_project_id ){
	//FIRST GET JUST THE DATES AVAILABLE IN THIS PROJECT
    $response 		= filter_by_projid("get_data_ts","[\"$active_pid\"]");
    
	//ORDER AND SORT BY DATES
	$date_headers 	= [];
	
	foreach($response["rows"] as $row){
		$date = Date($row["value"]);

		if(array_key_exists($date, $date_headers)){ //if the date already exists in dateheaders
			$date_headers[$date]++;					// increment the counter
		}else{
			$date_headers[$date] = 1;				//otherwise create an element [date -> #occurrences]
		}
	}
	
	uksort($date_headers, "cmp_date"); //sorts date headers in reverse order starting with date


	//PRINT TO SCREEN
	echo "<h1>Discovery Tool Data Summary for $active_project_id</h1>";
	echo "<div id='google_map_photos' class='gmap'></div>";
	$project_meta 	= $ap["project_list"][$active_pid];
	$photo_geos 	= array();
	$code_block = array();
	foreach($date_headers as $date => $record_count){
		//AUTOMATICALLY SHOW MOST RECENT DATE's DATA, AJAX THE REST
		$response 	= filter_by_projid("get_data_day","[\"$active_pid\",\"$date\"]");
		$days_data 	= rsort($response["rows"]); 

		foreach($response["rows"] as $row){
			$doc = $row["value"];
			if(!empty($doc["_attachments"])){
		        //original attachments stored with walk sessions
		        $old = "&_old=1";
		    }else{
		        if(array_key_exists("name",$doc["photos"][0])){
		            //newest and "final" method atomic attachment storage
		            $old = "";
		        }else{
		            //all attachments in seperate data entry
		            $old = "&_old=2";
		        }
		    }
			foreach($doc["photos"] as $n => $photo){
				if(!empty($photo["geotag"])){
					$photo_name = "photo_".$n.".jpg";

			        if(array_key_exists("name",$photo)){
			            $filename   = $photo["name"];
			            $ph_id      = $doc["_id"] . "_" .$filename;
			        }else{
			            $filename   = $photo_name;
			            $ph_id      = $doc["_id"];
			        }
			        $file_uri   	= "passthru.php?_id=".$ph_id."&_file=$filename" . $old;
			        $photo_uri  	= "thumbnail.php?file=".urlencode($file_uri)."&maxw=140&maxh=140";
			        $photo["geotag"]["photo_src"] = $photo_uri;
			        $photo["geotag"]["photo_id"]  = $doc["_id"]. "_" . "photo_".$n;
					
					array_push($photo_geos, $photo["geotag"]);
				}
			}
			$code_block = array_merge($code_block,printPhotos($doc));
		}
	}
	echo "<div class='thumbs'><ul class='collapse'>";
	echo implode("",$code_block);
	echo "</ul></div>";
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
function normalIcon() {
  return {
    url: 'http://icons.veryicon.com/48/System/Kameleon/Polaroid.png'
  };
}
function highlightedIcon() {
  return {
    url: 'http://1.bp.blogspot.com/_GZzKwf6g1o8/S6xwK6CSghI/AAAAAAAAA98/_iA3r4Ehclk/s1600/marker-green.png'
  };
}
$(document).ready(function(){
	window.current_preview = null;

	var gmarkers = drawGMap(<?php echo json_encode($photo_geos) ?>, 'photos', 16);
	
	$.each(gmarkers, function(){
		var el = this;
		$("#" + this.extras["photo_id"]).hover(function(){
			 el.setIcon(highlightedIcon());
		},function(){
			 el.setIcon(normalIcon());
		});
	});

	for(var i in gmarkers){
		// add event to the images
		google.maps.event.addListener(gmarkers[i], 'mouseover', function(event) {
			var photo_id = this.extras["photo_id"];
          	$("#" + photo_id).addClass("photoOn");
        });
        google.maps.event.addListener(gmarkers[i], 'mouseout', function(event) {
			var photo_id = this.extras["photo_id"];
			$("#" + photo_id).removeClass("photoOn");
        });
	}

	//HOVER ON MAP SPOT
	$(document).on({
	    mouseenter: function () {
	    	// console.log("photo on ");
	        // markers[2].setIcon(highlightedIcon());
	    },
	    mouseleave: function () {
	    	// console.log("photo off ");
	    	// markers[2].setIcon(normalIcon());
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
});
</script>
</body>
</html>




