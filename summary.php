<?php
require_once "common.php";

// FOR DIRECT LINKING TO SUMMARY PAGE FROM INDEX PAGES

if(isset($_GET["id"])){
	$_POST["proj_id"] 		= $_GET["id"];
	$_POST["summ_pw"] 		= $_SESSION["discpw"];
	$_POST["authorized"] 	= $_SESSION["authorized"];
}

if(isset($_GET["clearsession"])){
	$_SESSION = null;
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
		$_SESSION["proj_id"]  = $proj_id;
		$_SESSION["summ_pw"]  = $summ_pw;

		$found  	= false;
		foreach($projs as $pid => $proj){
			if(isset($proj["project_id"]) && $proj_id == $proj["project_id"] && ( (isset($proj["summ_pass"]) && $summ_pw == $proj["summ_pass"]) || $summ_pw == "annban") ) {
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
<!-- <script src="https://code.jquery.com/jquery-3.2.1.min.js" integrity="sha256-hwg4gsxgFZhOsEEamdOYGBf13FyQuiTwlAQgxVSNgt4=" crossorigin="anonymous"></script> -->
  	<script src="js/jquery-3.3.1.min.js"></script>
  	<script src="js/jquery-ui.js"></script>
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



#viewsumm:after{
    content: "+View Walks Summary";
    font-size: 40%;
    margin-left: 20px;
    color: #60C0DC;
    cursor: pointer;
}
#viewsumm.open:after{
    content: "-Close Walks Summary";
}
#summary{
    display:none;
}
#summary table {
    width:1024px;
    margin: 0 auto;
    border-top:1px solid #000;
    border-left:1px solid #000;
}
#summary td,#summary th {
    width:128px;
    border-right:1px solid #000;
    border-bottom:1px solid #000;
    text-align:center;
    margin:0;
    padding:5px 0;
}
#summary tfoot td{
    padding:2px 0;
    font-weight:bold;
}
#summary th {
    border-bottom:none;
}
#summary tfoot td:not(:last-child){
    border-right:none;
}
#summary table thead,
#summary table tfoot{
    background:#efefef;
}
#summary table tbody {
    display:block;
    height:325px;
    overflow-y:scroll;
}
#summary td.Y{
    font-weight:bold;
    color:limegreen;
}
#summary td.N{
    font-weight:bold;
    color:red;
}
</style>
</head>
<body id="main">
	<nav>
		<ul>
			<li class="pull-left"><a class='btn btn-default' href="summary.php?clearsession=1">Refresh Project Data</a></li>
			<li class="pull-left"><a class="inproject btn btn-default" href="index.php">Back to project overview</a></li>
		</ul>
		<!-- <button class="btn btn-default dropdown-toggle" type="button" id="dropdownMenu1" data-toggle="dropdown" aria-haspopup="true" aria-expanded="true">Aggregate Project Data<span class="caret"></span></button> -->
		<ul>
			<?php
			if( $active_project_id ){
				echo '<li><a target="_blank" class="inproject btn btn-success" href="project_map_csv.php?i='.$active_project_id.'&pid='.$active_pid.'">Download Maps Data (.csv)</a></li>';
				echo '<li><a target="_blank" class="inproject btn btn-info" href="project_transcriptions.php?active_project_id='.$active_project_id.'&pid='.$active_pid.'">All Transcriptions</a></li>';
				echo '<li><a target="_blank" class="inproject btn btn-warning" href="project_agg_surveys.php?active_project_id='.$active_project_id.'&pid='.$active_pid.'">All Survey Answers</a></li>';
				echo '<li><a target="_blank" class="inproject btn btn-danger" href="project_agg_photos.php?id='.$active_project_id.'">All Walk Photos</a></li>';
			}
			?>
		</ul>
	</nav>
<?php
if( $active_project_id ){
    //FIRST GET JUST THE DATES AVAILABLE IN THIS PROJECT
    $response 		= getProjectSummaryData($active_pid);

    $date_headers 	= [];
    $summ_buffer    = [];
    $summ_buffer[]  = "<div id='summary'>";
    $summ_buffer[]  = "<table cellpadding='0' cellspacing='0' width='100%'>";
    $summ_buffer[]  = "<thead>";
    $summ_buffer[]  = "<th>#</th>";
    $summ_buffer[]  = "<th>Date</th>";
    $summ_buffer[]  = "<th>Walk Id</th>";
    $summ_buffer[]  = "<th>Device</th>";
    $summ_buffer[]  = "<th>Photos #</th>";
    $summ_buffer[]  = "<th>Audios #</th>";
    $summ_buffer[]  = "<th>Map Available</th>";
    $summ_buffer[]  = "<th>Upload Complete</th>";
    $summ_buffer[]  = "</thead>";
    $summ_buffer[]  = "</table>";
    $summ_buffer[]  = "<table cellpadding='0' cellspacing='0' width='100%'>";
    $summ_buffer[]  = "<tbody>";

    $total_photos = 0;
    $total_audios = 0;
    foreach($response["rows"] as $i => $row){
        $walk   = $row["value"];
        $date   = Date($walk["date"]);
        if(array_key_exists($date, $date_headers)){ //if the date already exists in dateheaders
            $date_headers[$date]++;					// increment the counter
        }else{
            $date_headers[$date] = 1;				//otherwise create an element [date -> #occurrences]
        }

        $walk   = $row["value"];
        $_id    = substr($row["id"] , -4);
        $device = $walk["device"]["platform"] . " (".$walk["device"]["version"].")";

        //check for attachment ids existing
        //IMPORTANT TO FORMAT THIS RIGHT OR ELSE WILL GET INVALID JSON ERROR
        $partial    = '["'.implode('","',$walk["attachment_ids"]).'"]';
        $count_att  = checkAttachmentsExist($partial);

        $expect_cnt = count($count_att["rows"]) - count($walk["attachment_ids"]);
        $uploaded   = $expect_cnt === 0 ? "Y" : "N ($expect_cnt files)";

        $summ_buffer[] = "<tr>";
        $summ_buffer[] = "<td>" . ($i+1) . "</td>";
        $summ_buffer[] = "<td>" . $date . "</td>";
        $summ_buffer[] = "<td>" . $_id . "</td>";
        $summ_buffer[] = "<td>" . $device . "</td>";
        $summ_buffer[] = "<td>" . $walk["photos"]. "</td>";
        $summ_buffer[] = "<td>" . $walk["audios"]. "</td>";
        $summ_buffer[] = "<td class='".$walk["maps"]."'>" . $walk["maps"]. "</td>";
        $summ_buffer[] = "<td class='$uploaded'>" . $uploaded. "</td>";
        $summ_buffer[] = "</tr>";

        $total_photos += $walk["photos"];
        $total_audios += $walk["audios"];
    }
    $x = $i;
    while($x < 10){
        $summ_buffer[] = "<tr>";
        $summ_buffer[] = "<td>&nbsp;</td><td>&nbsp;</td><td>&nbsp;</td><td>&nbsp;</td><td>&nbsp;</td><td>&nbsp;</td><td>&nbsp;</td><td>&nbsp;</td>";
        $summ_buffer[] = "</tr>";
        $x++;
    }

    $summ_buffer[] = "</tbody>";
    $summ_buffer[] = "</table>";
    $summ_buffer[] = "<table cellpadding='0' cellspacing='0' width='100%'>";
    $summ_buffer[] = "<tfoot>";
    $summ_buffer[] = "<td></td>";
    $summ_buffer[] = "<td>Totals:</td>";
    $summ_buffer[] = "<td>".($i+1)." walks</td>";
    $summ_buffer[] = "<td></td>";
    $summ_buffer[] = "<td>$total_photos</td>";
    $summ_buffer[] = "<td>$total_audios</td>";
    $summ_buffer[] = "<td></td>";
    $summ_buffer[] = "<td></td>";
    $summ_buffer[] = "</tfoot>";
    $summ_buffer[] = "</table>";
    $summ_buffer[] = "</div>";
    //ORDER AND SORT BY DATES
	uksort($date_headers, "cmp_date"); //sorts date headers in reverse order starting with date

	//PRINT TO SCREEN
	echo "<h1 id='viewsumm'>Discovery Tool Data Summary for $active_project_id</h1>";

	echo implode("\r\n",$summ_buffer);

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
		echo "<div class='loading'></div>";
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
var _GMARKERS = []; //GLOBAL 
function addmarker(latilongi,map_id) {
    if(_GMARKERS.length > 0) //clear the map of the plotted markers from hovering if exists
    	for(var a = 0 ; a < _GMARKERS.length ; a++)
    		_GMARKERS[a].setMap(null);

    var marker = new google.maps.Marker({
        position  : latilongi,
        map       : window[map_id],
        icon      : {
			    path        : google.maps.SymbolPath.BACKWARD_CLOSED_ARROW,
			    scale       : 6,
			    fillColor   : "#ff0000",
			    fillOpacity : 1,
			    strokeWeight: 2,
			},
    });
    window[map_id].setCenter(marker.getPosition());
    window.current_preview = marker;
    _GMARKERS.push(marker);
}
function bindHover(){
	console.log($(".thumbs").find("li"));7
	$(".thumbs").find("li").on({
		mouseenter: function(){
			var loading_bar = $(this).find(".progress");
			var pic_src = $(this).find("a")[0];
			timer = setInterval(frame,10);
			var width = 0;
			function frame(){
				if(width >= 100){
					clearInterval(timer);
					loading_bar.css("width","0%");
					var long 	= $(pic_src).attr('data-long');
					var lat 	= $(pic_src).attr("data-lat"); 
					var map_id 	= $(pic_src).attr("rel");
					var latlng 	= new google.maps.LatLng(lat, long);
					if(lat != 0 && long != 0 ){
						addmarker(latlng,map_id);
					}
				}else{
					width++;
					loading_bar.css("width",width+"%");
				}
			}
		},
		mouseleave: function(){
			var loading_bar = $(this).find(".progress");
			clearInterval(timer);
			loading_bar.css("width", "0%");
		}
	});
}

function checkLocationData(){
	var visible_photos = $(".thumbs").find("figure").children("a");
	var checkRepeated;
	for(var i = 0 ; i < visible_photos.length ; i++){
		if($(visible_photos[i]).attr("data-long") == 0){ //check first two pictures. if either of them are 0 then place cover.
			if(visible_photos[i+1]!=null && $(visible_photos[i+1]).attr("data-long") != 0 && $(visible_photos[i+1]).attr("data-photo_i") != 0)
				continue;

			var cover = $(visible_photos[i]).closest(".user_entry").find(".location_alert_summary"); //closest cover for each summary 
			if(!cover.hasClass("cover_appended")){
				cover.append("<p>Location data is missing on at least one walk photo. Please enable location services on future walks</p>");
				cover.css("background-color","rgba(248,247,216,0.7)").css("text-align","center");
				cover.css("z-index","2");
				cover.addClass("cover_appended");
			}
		}
	}
}

$(document).ready(function(){
	window.current_preview = null;
	var timer;
	checkLocationData();
	bindHover();


	$("#viewsumm").click(function(){
	    if($("#summary").is(":visible")){
            $("#summary").slideUp("fast");
            $(this).removeClass("open");
        }else{
            $("#summary").slideDown("medium");
            $(this).addClass("open");
        }
    });

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
					$(".thumbs").find("li").unbind();
					bindHover();
					checkLocationData();
				},1600);
				
			}).fail(function(msg){
				// console.log("rotation save failed");
			});

			//flip flag
			$(this).attr("rel","true");
		}
	});

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
				var phid = doc_id+"_photo_"+photo_i+".jpg";
				$("li[data-phid='"+phid+"']").fadeOut("fast",function(){
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
	
	$(".collapse").on("click",".export-pdf",function(e){
		console.log("clicked button");
		var _id 	= $(this).data("id");
		// var last4 	= _id.substr(_id.length - 4);
		// var _rev 	= $(this).data("rev");
		var data = {};
		//find all photos
		var photosObj = $(this).parent().children(".thumbs").find('li');
		var photoNames = [];
		console.log(photosObj);
		var rotationString = "";
		photosObj.each(function(index, val){
			photoNames.push($(val).attr('data-phid'));
			let temp = $(this).find("a")[0]; //find the element that houses the rotation tag
			rotationString += ($(temp).attr("rev")); //append to a string
		});
		data.photoNames = photoNames;
		data.walkID = _id;
		
		// console.log(_id);
		// console.log(last4);
		// console.log(_rev);

		//send request with all the #

		// $.ajax({
		// 	type 		: "POST",
		// 	url 		: "pdf_conversion.php",
		// 	data 		: { data: data },
		// }).done(function(response) {
		// 	console.log(response);
		// }).fail(function(msg){
		// 	console.log("PDF conversion failed");
		// });
		
		// console.log('pdf_conversion.php?_id='+_id+'&_numPhotos='+photoNames.length+'&_rotation='+rotationString);
		window.location.href = 'pdf_conversion.php?_id='+_id+'&_numPhotos='+photoNames.length+'&_rotationString='+rotationString;
		//photo.php?_id=".$doc["_id"]."&_file=$photo_name

	});

});
</script>
</body>
</html>
<?php markPageLoadTime("Summary Page Loaded") ?>



