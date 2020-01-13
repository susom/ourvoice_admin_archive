<?php
require_once "common.php";

// NEXT GET SPECIFIC PROJECT DATA
$ap 				= $_SESSION["DT"];
$_id 				= $ap["_id"];
$_rev 				= $ap["_rev"];
$projs 				= $ap["project_list"];
$active_project_id 	= null;
$active_pid 		= null;
$alerts 			= array();

// AJAX HANDLING
if(isset($_POST["proj_idx"])){
	//POSSIBLE NEW PROJECT TAG, SAVE TO disc_projects
	$json_response 	= array("new_project_tag" => false);
	$proj_idx 		= $_POST["proj_idx"];
	$project_tag 	= $_POST["tag_text"];
	
	$p_url 			= cfg::$couch_url . "/" . cfg::$couch_proj_db . "/" . cfg::$couch_config_db;
	$p_response 	= doCurl($p_url);
	$p_doc 	 		= json_decode(stripslashes($p_response),1);
	$p_payload 		= $p_doc;

	if(!isset($p_payload["project_list"][$proj_idx]["tags"])){
		$p_payload["project_list"][$proj_idx]["tags"] = array();
	}
	if(!in_array($project_tag,$p_payload["project_list"][$proj_idx]["tags"])){
		array_push($p_payload["project_list"][$proj_idx]["tags"], $project_tag);
		$json_response["new_project_tag"] = true;
		$_SESSION["DT"]["project_list"][$proj_idx] = $p_payload["project_list"][$proj_idx]; 
	}
	doCurl($p_url, json_encode($p_payload), "PUT");

	echo json_encode($json_response);
	exit;
}

//NOW AUTO-LOGIN TO THIS PROJECT
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
			if($proj_id == $proj["project_id"] && ( (isset($proj["summ_pass"]) && $summ_pw == $proj["summ_pass"]) || $summ_pw == $masterblaster) ) {
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
<link href="css/dt_common.css?v=<?php echo time();?>" rel="stylesheet" type="text/css"/>
<link href="css/dt_summary.css?v=<?php echo time();?>" rel="stylesheet" type="text/css"/>
<script src="js/jquery-3.3.1.min.js"></script>
<script src="js/jquery-ui.js"></script>
<script src="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.7/js/bootstrap.min.js" integrity="sha384-Tc5IQib027qvyjSMfHjOMaLkfuWVxZxUPnCJA7l2mCWNIpG9mGCD8wGNIcPD7Txa" crossorigin="anonymous"></script>
<link href="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.7/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-BVYiiSIFeK1dGmJRAkycuHAHRg32OmUcww7on3RYdg4Va+PmSTsz/K68vbdEjh4u" crossorigin="anonymous"/>
<script type="text/javascript" src="https://maps.google.com/maps/api/js?key=<?php echo cfg::$gmaps_key; ?>"></script>
<script type="text/javascript" src="js/dt_summary.js?v=<?php echo time();?>"></script>
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
	echo "<h1 class = 'title'>Discovery Tool Data Summary for $active_project_id</h1>";
	echo "<div id = 'main-container'>";
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
			        $photo_uri 		= $file_uri;
			        $photo_uri  	= "thumbnail.php?file=".urlencode($file_uri)."&maxw=140&maxh=140";
			        $photo["geotag"]["photo_src"] = $photo_uri;
			        $photo["geotag"]["photo_id"]  = $doc["_id"]. "_" . "photo_".$n;
					
					array_push($photo_geos, $photo["geotag"]);
				}
			}
			$code_block = array_merge($code_block,printPhotos($doc));
		}
	}
	echo "<div class='thumbs all-photos'><ul class='collapse' id='tags'>";
	echo implode("\r",$code_block); //join elements with the "" string separating.
	echo "</ul></div></div>";


	$project_tags = $_SESSION["DT"]["project_list"][$active_pid]["tags"];
	include("inc/fixed_tags.php");
}
?>
</body>
</html>
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
#main-container{
	position:absolute;
}
#google_map_photos {
	box-shadow:0 0 3px  #888; 
	width:930px;
	height:670px;
	float:left;
	margin:20px auto;
	right:10px;
}
#tags ul {
	width: 85%;
	position:absolute;
	top:5px;
	left:0;
	z-index:5;
	display:none;
}
#tags ul.tagon{
	display:block;
}
#tags ul.tagon + a:after{
	content: "";
    position: absolute;
    z-index: 0;
    background: #000;
    opacity: .35;
    width: 140px;
    height: 140px;
    top: 0;
    left: 0;
}
#tags ul li{
	margin: 0 5px 5px;
    padding: 0px 22px 0px 5px;
}
#tags .deletetag{
    top: 1px;
    right: 2px;
}
</style>
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
    url: 'img/marker_blue.png'
  };
}
function highlightedIcon() {
  return {
    url: 'img/marker_purple.png'
  };
}
function bindMapFunctionality(gmarkers){
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
			var icon = {
			    url: this.extras["photo_src"], // url
			    scaledSize: new google.maps.Size(100, 100), // scaled size
			};
			this.setIcon(icon);
          	$("#" + photo_id).addClass("photoOn");
        });
        google.maps.event.addListener(gmarkers[i], 'mouseout', function(event) {
			var photo_id = this.extras["photo_id"];
			this.setIcon(normalIcon());
			$("#" + photo_id).removeClass("photoOn");
        });
	}
}
function removeEmptyPhotos(){
	var totals = $(".ui-widget-drop").find("img");
	for(var v = 0 ; v < totals.length; v++){
		if($(totals[v]).height() < 30)
			$(totals[v]).parents("li").remove();
	}
}

$(window).on('load', function(){ //on photo load remove the empty ones
	removeEmptyPhotos();
	appendProjectCount();

});

$(document).ready(function(){
	window.current_preview = null;
	bindProperties();
	var pins = <?php echo json_encode($photo_geos) ?>;
	var gmarkers = drawGMap(<?php echo json_encode($photo_geos) ?>, 'photos', 16);
	bindMapFunctionality(gmarkers);

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
			console.log("rotation saved");
		}).fail(function(msg){
			console.log("rotation save failed");
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

	//VIEW TAG
	$(".collapse").on("click", ".preview i", function(){
		var doc_id 	= $(this).parent().data("doc_id");
		var photo_i = $(this).parent().data("photo_i"); 
		// console.log(doc_id);
		
		if($(this).parent().prev("ul.tagon").length){
			$(this).parent().prev("ul.tagon").removeClass("tagon");
		}else{
			$(this).parent().prev("ul").addClass("tagon");
		}
		return false;
	});

	//OPEN CLOSE TAG MENU
	$("#close_addtags").click(function(){
		if($("#addtags.closed").length){
			$("#addtags").removeClass("closed");
		}else{
			$("#addtags").addClass("closed");
		}
		return false;
	});

	//DELETE PHOTO TAG
	$("#tags").on("click",".deletetag",function(){
		// get the tag index/photo index
		var doc_id 	= $(this).data("doc_id");
		var photo_i = $(this).data("photo_i");
		var tagtxt 	= $(this).data("deletetag");
		// console.log(tagtxt);
		var _this 	= $(this);
		$.ajax({
			method: "POST",
			url: "photo.php",
			data: { doc_id: doc_id, photo_i: photo_i, delete_tag_text: tagtxt},
			success:function(result){
				// console.log(result);
			}

		}).done(function( msg ) {
			_this.parent("li").fadeOut("medium",function(){
				_this.parent("li").remove();
			});
		});
		return false;
	});

	//DELETE PROJECT TAG
	$("#addtags").on("click",".tagphoto b", function(){
		//TODO
		//DELETE TAG FROM BOTH disc_projects and each individual photo that is tagged with it disc_users
		var ele = $(this).closest("li");
		var tag = ($(this).attr("datakey"));
		var pics = $(".ui-widget-drop");
		var p_data = [];
		for(var i = 0 ; i < pics.length ; i++ ){
			p_data.push(pics[i].id)
		}

		$.ajax({
          url:  "aggregate_post.php",
          type:'POST',
          data: { deleteTag: tag, pictures: p_data },
          success:function(result){
            //remove dom elements here
             $("."+tag).remove();
          }
        });  
		ele.remove();
		// console.log("clicking on the trashcan");
		return false;
	});
	
	//ADD PHOTO TAG
	$("#addtags").on("click",".tagphoto", function(){
		// console.log("inside here");
		// console.log(this);
		// console.log($(this).children("b").attr("datakey"));
		//console.log(this.childNodes[0].attributes[0].value);
		var tag = $(this).children("b").attr("datakey");
		var photo_selection = $("."+tag);
		var all_photos = $(".ui-widget-drop");
		var selected_tag = false;
		var pic_ids = [];
		var marker_tags = [];
		var retpins = [];
		$(".tagphoto").each(function(index){ //for the tag boxes on the left side of the screen
			if($(this).children("b").attr("datakey") == tag){
				if($(this).parent().hasClass("selected")){
					$(this).parent().removeClass("selected");
					selected_tag = false;
				}else{
					$(this).parent().addClass("selected");
					selected_tag = true;
				}
			}
		});
		if(selected_tag){ //if trying to hide pictures
			photo_selection.each(function(index){
				$(this).closest(".ui-widget-drop").addClass(tag+"_photo"); //add display to each of the matching tag pics
				pic_ids.push($(this).closest(".ui-widget-drop").attr("id"));
			});

			all_photos.each(function(index){ //add hide to each of the others
				if($(this).hasClass(tag+"_photo"))
					console.log("nothing");
				else
					$(this).addClass("hide_"+tag);
			});
			//trying to hide the map markers now
			$.each(pins, function(){ //loop through all map markers defined globally onReady()
				for(var i = 0 ; i < pic_ids.length ; i++) //loop through all currently visible pictures on page
					if($(this).attr("photo_id") == pic_ids[i]){ //identify which markers to add tags to
						retpins.push(this);
						// console.log("adding to retpins");
						// console.log($(this).attr("photo_id") + "--- " + pic_ids[i]);
					}
			});
			// console.log(retpins);
			var gmarkers = drawGMap(retpins, 'photos', 14);
			bindMapFunctionality(gmarkers);
		
		}else{	//trying to reveal pictures

			// console.log(pic_ids);
			all_photos.each(function(index){
				if($(this).hasClass(tag+"_photo")){
					$(this).removeClass(tag+"_photo");
				}
				
				if($(this).hasClass("hide_"+tag)){
					$(this).removeClass("hide_"+tag);
				}
			});

			$(".ui-widget-drop").not("[class*=hide]").each(function(index){ //find all pics that are displayed
				pic_ids.push($(this).closest(".ui-widget-drop").attr("id")); //store for comparison loop
			});
			// console.log(pic_ids);
			
	
			$.each(pins, function(){ //loop through all map markers defined globally onReady()
				for(var i = 0 ; i < pic_ids.length ; i++) //loop through all previously stored visible pictures
					if($(this).attr("photo_id") == pic_ids[i]){ //identify which markers to redraw
						retpins.push(this);
					}
			});
			// console.log(retpins);
			var gmarkers = drawGMap(retpins, 'photos', 14);
			bindMapFunctionality(gmarkers);

		}
		return false;
	});

	//ADD PROJECT TAG
	$("#addtags form").submit(function(){
		var proj_idx 	= $("#newtag_txt").data("proj_idx");
		var tagtxt 		= $("#newtag_txt").val();

		if(tagtxt){
			// add tag to project's tags and update disc_project
			// ADD new tag to UI
			var data = { proj_idx: proj_idx, tag_text: tagtxt };
			$.ajax({
				method: "POST",
				url: "project_agg_photos.php",
				data: data,
				dataType : "JSON",
				success: function(response){
					if(response["new_project_tag"]){
						//ADD TAG to modal tags list
						var newli 	= $("<li>").addClass("ui-widget-drag");
						var newb 	= $("<b>").attr("datakey",tagtxt);
						var newa 	= $("<a href='#'>").text(tagtxt).addClass("tagphoto");
						newa.append(newb);
						newli.append(newa);
						$("#addtags ul").prepend(newli);
						$("#addtags .notags").remove();
						bindProperties();

					}
				},
				error: function(){
					console.log("error");
				}
			}).done(function( msg ) {
				$("#newtag_txt").val("");
			});
		}
		return false;
	});
});

function bindProperties(){
      $( ".ui-widget-drag").draggable({
      cursor: "move",
      //containment:$('#addtags'),
      helper: 'clone',
      start: function(event,ui){
      	$(ui.helper).addClass("ui-draggable-helper");
      },
      //helper: function(event,ui){return($("<p>").text("DRAG"));}
      drag: function(event,ui){

      //  ui.css("z-index", "-1"); //fix frontal input
      }

    });

    $( ".ui-widget-drop" ).droppable({
      drop: function( event, ui ) {
      	var drag = (ui.draggable[0].innerText.trim());
      	var drop = event.target.id;
      	var temp = drop.split("_");
      	var proj = temp[0];
      	var p_ref = temp[0] +"_"+ temp[1] +"_"+ temp[2] +"_"+ temp[3];
      	var exists = false;
      	var datakey = temp[(temp.length-1)];
         $.ajax({
          url:  "aggregate_post.php",
          type:'POST',
          data: { DragTag: drag, DropTag: drop, Project: proj, Key: datakey },
          success:function(result){
          	// console.log(result);
          	var appendloc = $("#"+drop).find("ul");
          	for(var i = 0 ; i < appendloc[0].childNodes.length; i++){
          		//console.log(appendloc[0].childNodes[i].childNodes);
          		if(appendloc[0].childNodes[i].childNodes[0].data == drag) //X is appended had to find a work around to get name
          			exists = true;
          	}
          	if(!exists){
	            var newli 	= $("<li>").text(drag).addClass(drag);
	            var newa 	= $("<a href='#'>").attr("data-deletetag",drag).attr("data-doc_id",p_ref).attr("data-photo_i",datakey).text("x").addClass("deletetag");
				newli.append(newa);
	            appendloc.prepend(newli);
          	}
          }        
            //THIS JUST STORES IS 
          },function(err){
          console.log("ERRROR");
          console.log(err);
          });
       // ui.draggable.hide(350);

		}
    }); //ui-widget-drop
  
}
function appendProjectCount(){
	$(".title").append(" ("+$("#tags").children().length+")");
}
</script>
<style>
.ui-widget-drag{
	overflow: visible;
}
#addtags{
	overflow: visible;
}
.ui-draggable-helper{
    width:150px;
}

li[class*="hide_"]{
	display: none;
}

.selected{
	background-color: azure;
}
</style>


