<?php
require_once "common.php";

// CHECK IF SIGNED IN TO A PROJECT
if(!isset($_SESSION["proj_id"]) && !isset($_SESSION["summ_pw"])){
	$_SESSION = null;
    header("location:summary.php");
}

// NEXT GET SPECIFIC PROJECT DATA
$ap 				= $_SESSION["DT"];
$_id 				= $ap["_id"];
$_rev 				= $ap["_rev"];
$projs 				= $ap["project_list"];
$active_project_id 	= null;
$active_pid 		= null;
$alerts 			= array();

// AJAX HANDLING
if(array_key_exists("ajax",$_POST)){
	if($_POST["ajax"] == "addProjectTag"){
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

	if($_POST["ajax"] == "loadThumbs"){
		$pcode 			= $_POST["pcode"];
		$pfilters 		= $_POST["filters"] ?? array();
		$data 			= getFilteredDataGeos($pcode, $pfilters);
		$data["code_block"] = printAllDataThumbs($data["code_block"]);
		$reload 		= json_encode($data);
		echo $reload;
		exit;
	}
}

$active_project_id 	= $_SESSION["proj_id"];
$active_pid 		= $_SESSION["pid"] ?? null;
if(is_null($active_pid)){
	foreach($projs as $pid => $proj){
		if($_SESSION["proj_id"] == $proj["project_id"] ) {
			$active_pid = $_SESSION["pid"] = $pid;
			break;
		}
	}
}

// PROJECT TAGS
$project_tags = $_SESSION["DT"]["project_list"][$active_pid]["tags"] ?? array();
$page = "allwalks";
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.1//EN" "http://www.w3.org/TR/xhtml11/DTD/xhtml11.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en">
<head>
<meta http-equiv="content-type" content="application/xhtml+xml; charset=UTF-8" />
<link href="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.7/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-BVYiiSIFeK1dGmJRAkycuHAHRg32OmUcww7on3RYdg4Va+PmSTsz/K68vbdEjh4u" crossorigin="anonymous"/>
<link href="css/dt_common.css?v=<?php echo time();?>" rel="stylesheet" type="text/css"/>
<link href="css/dt_summary.css?v=<?php echo time();?>" rel="stylesheet" type="text/css"/>
<script src="js/jquery-3.3.1.min.js"></script>
<script src="js/jquery-ui.js"></script>
<script src="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.7/js/bootstrap.min.js" integrity="sha384-Tc5IQib027qvyjSMfHjOMaLkfuWVxZxUPnCJA7l2mCWNIpG9mGCD8wGNIcPD7Txa" crossorigin="anonymous"></script>
<script type="text/javascript" src="https://maps.google.com/maps/api/js?key=<?php echo cfg::$gmaps_key; ?>"></script>
<script type="text/javascript" src="js/dt_summary.js?v=<?php echo time();?>"></script>
</head>
<body id="main" class="<?php echo $page ?>">
<div id="content">
	<?php include("inc/gl_nav.php"); ?>
	<div id="main_box">
		<h1 id="viewsumm">All Walk Data for : <?php echo $active_project_id ?> <a href='#' class="showhidemap"><span>Hide</span> Map</a></h1>
		<div id='google_map_photos' class='gmap'></div>
		<div class='thumbs all-photos'>
			<hgroup>
				<h4 class="photo_count pull-left">Photos <span></span></h4>
				<ul id="filters" class="pull-left" data-filtertags=[] data-filtermood=[]>
					<li><b>Applied Filters :</b></li>		
				</ul>

				<a href="#" class="btn btn-sm btn-primary pull-right export_view">Export View as PDF</a>
				<form  id="choose_filter" class="pull-right">
					<label>Add Filter(s) :</label>
					<select>
						<option value="-1">-- Mood or Tags --</option>
						<optgroup label="Moods">
							<option value="good">Good</option>
							<option value="bad">Bad</option>
							<option value="neutral">Neutral</option>
						</optgroup>

						<optgroup id='filter_tags' label="Project Tags">
							<option value="un-tagged">un-tagged</option>
							<?php
							foreach($project_tags as $idx => $tag){
								echo "<option value='$tag'>$tag</option>";
							}
							?>
						</optgroup>
					</select>
				</form>
			</hgroup>
			<div class="innerbox">
				<ul class='collapse' id='tags'></ul>
			</div>
		</div>
		<div id='addtags' class="<?php if(!empty($project_tags)) echo "hastags" ?>">
			<h4>Project Tags</h4>
			<div class="innerbox">
				<p class='notags'>There are currently no tags in this project. Add Some</p>
				<p class='dragtags'>Drag tags onto a photo to tag.</p>
				<ul>
					<li class="addnewtag nobor">
						<form id="addnewtag" name="newtag" method='post'>
							<input type='text' id='newtag_txt' data-proj_idx='<?php echo $active_pid; ?>' placeholder="+ Add a New Tag"> <input class="savetag" type='submit' value='Save'/>
						</form>
					</li>
				</ul>
			</div>
		</div>
	</div>
    <?php include("inc/gl_footer.php"); ?>
<div>
</body>
</html>
<style>
	#filters {
		border-left:1px solid #999;
		height:23px;
		margin:0 0 0 15px;
		padding:0 0 0 15px;
		width:50%;
	}
	#filters li {
		display:inline-block;
		margin:0 10px 0 0;
		padding:0; 
		font-size:85%;
		line-height:170%;
		color:#747573;
	}
	#filters i {
		display:inline-block;
		width:20px;
		height:20px;
		font-style:normal;
		position:relative;
	}
	#filters li:hover .delete_filter{
		display:block;
	}
	.delete_filter {
		position:absolute;
		top: 0;
	    right: -3px;
	    width: 10px;
	    height: 10px;
	    background: url(img/icon_redx.png) 50% no-repeat;
	    background-size: 115%;
	    display:none;
	    cursor:pointer;
	}

	i.good {
		background:url(img/marker_green.png) 50% no-repeat;
		background-size:contain;
	}
	i.bad { 
		background:url(img/marker_red.png) 50% no-repeat;
		background-size:contain;
	}
	i.neutral {
		background:url(img/marker_orange.png) 50% no-repeat;
		background-size:contain;
	}
	#filters i.filter_tag {  
		width:auto;
		min-width:20px;
		padding-left:22px;
		background:url(img/icon_tag.png) 0 0  no-repeat;
		background-size:contain;
	}
	.dragtag{
		display:inline-block;
		height:60px;
		background:url(img/icon_tag.png) 50% 0 no-repeat;
		background-size:28%;
		text-align:center;
		line-height:750%;
	}
	.draghover{
		box-shadow: 0 0 15px green;
	}
	
	.export_view{
		margin-left:10px;
		margin-top:-7px;
	}

	#choose_filter {
		color:#666;
		margin:0; padding:0;
	}
	#choose_filter label,
	#choose_filter select{
		margin:0;
		display:inline-block;
	}

	.gmap{ float:none !important; }
	.all-photos .innerbox{
		background: url(img/icon_doublearrow.png) 50% 96% no-repeat;
    	background-size: 8%;
	}
	#tags{
		height:360px;
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

	#tags > div {
		width: 1200px;
		float:left;
	}
	#tags .deletetag{
	    top: 1px;
	    right: 2px;
	}
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
<script>
	$(document).ready(function(){
		// STORE MAP VIEW PREFERENCE FOR DURATION OF SESSION
		if(sessionStorage.getItem("showmap_pref")){
			$("#google_map_photos").hide();
			$(".showhidemap span").text("Show");
		}

		// GET INITIAL PAGE PROJECT PHOTOS
		var pid 				= '<?php echo $active_pid ?>';
		var project_code 		= '<?php echo $active_project_id; ?>';
		var project_tags 		= JSON.parse('<?php echo json_encode($project_tags); ?>');
		loadTags(project_tags, pid);

		var filters 			= [];
		loadThumbs(project_code,filters);

		// SOME INITIAL BS
		window.current_preview 	= null;
		var pins 				= null;

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

		//VIEW PHOTO TAGS
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
			var ele 	= $(this).closest("li");
			var tag 	= $(this).attr("datakey");
			var pcode 	= $(this).attr("datapcode");
	
			$.ajax({
	          url:  "aggregate_post.php",
	          type:'POST',
	          data: { deleteTag: tag, project_code: pcode},
	          success:function(result){
				if($("#addtags li").length < 2){
					$("#addtags").removeClass("hastags");
				}
             	$("#filter_tags option[value='"+tag+"']").remove();
	          }
	        });  
			
			// REMOVE immedietely, cause who cares if it succeeds, they can easily delete again next time
			ele.remove();
			return false;
		});
		
		//ADD PHOTO TAG
		$("#addtags").on("click",".tagphoto", function(){
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

		//ADD PROJECT TAG FORM
		$("#newtag_txt").focus(function(){
			$(".savetag").fadeIn();
		}).blur(function(){
			$(".savetag").fadeOut();
		});
		$("#addtags form").submit(function(){
			var proj_idx 	= $("#newtag_txt").data("proj_idx");
			var tagtxt 		= $("#newtag_txt").val();

			if(tagtxt){
				// add tag to project's tags and update disc_project
				// ADD new tag to UI
				var data = { proj_idx: proj_idx, tag_text: tagtxt, ajax: "addProjectTag" };
				$.ajax({
					method: "POST",
					url: "project_agg_photos.php",
					data: data,
					dataType : "JSON",
					success: function(response){
						if(response["new_project_tag"]){
							project_tags.push(tagtxt);
							loadTags(project_tags, pid);

							if(!$("#addtags").hasClass("hastags")){
								$("#addtags").addClass("hastags");
							}
							
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

		//ADD SHOWHIDE MAP VIEW
		$(".showhidemap").click(function(){
			if($("#google_map_photos").is(":visible")){
				$("#google_map_photos").slideUp("medium");
				$(this).find("span").text("Show");
				sessionStorage.setItem("showmap_pref",1);
			}else{
				$("#google_map_photos").slideDown("fast");
				$(this).find("span").text("Hide");
				sessionStorage.removeItem("showmap_pref");
			}
			return false;
		});

		//DELETE FILTER (redraws content)
		$("#filters").on("click",".delete_filter", function(){
			var parli = $(this).closest("li");
			parli.remove();

			var new_filters = $(".filter").toArray();
			var filter_ar 	= [];
			for(var i in new_filters){
				filter_ar.push($(new_filters[i]).data("filter") );
			}
			loadThumbs(project_code, filter_ar);
			return false;
		});
		//ADD A FILTER (redraws content)
		$("#choose_filter select").change(function(){
			var filter_tag = $(this).val();
			if(filter_tag == -1){
				return;
			}

			var newli 	= $("<li>");
			var newi 	= $("<i>");
			var newdel 	= $("<a>").addClass("delete_filter");
			if(["good","bad","neutral"].indexOf(filter_tag) > -1){
				newi.addClass("filter").addClass("filter_mood").addClass(filter_tag).attr("data-filter",filter_tag);
			}else{
				newi.addClass("filter").addClass("filter_tag").addClass(filter_tag).attr("data-filter",filter_tag).text(filter_tag);
			}
			newi.append(newdel);
			newli.append(newi);
			$("#filters").append(newli);

			var new_filters = $(".filter").toArray();
			var filter_ar 	= [];
			for(var i in new_filters){
				filter_ar.push($(new_filters[i]).data("filter") );
			}
			loadThumbs(project_code, filter_ar);
		});

		//EXPORT VIEW AS PDF
		$(".export_view").click(function(e){
			var new_filters = $(".filter").toArray();
			var filter_ar 	= [];
			for(var i in new_filters){
				filter_ar.push($(new_filters[i]).data("filter") );
			}

			var pdf_url = "pdf_export_filtered_view.php?pcode=" + project_code + "&pid=" + pid + "&filters=" + encodeURIComponent(JSON.stringify(filter_ar));
			window.open(pdf_url, '_blank');
			return false;
		});
	});
	function loadTags(project_tags, active_project_id){
		// SORT alphabetically reverse
		project_tags.sort(function SortByName(b,a){
		  var aName = a.toLowerCase();
		  var bName = b.toLowerCase(); 
		  return ((aName < bName) ? -1 : ((aName > bName) ? 1 : 0));
		});
		
		// REMOVE ERRTHING
		$("#addtags .ui-widget-drag").remove();

		// REBUILD ERRTHING
		for(var i in project_tags){
			var tag 	= project_tags[i];
			var newli 	= $("<li>").addClass("ui-widget-drag");
			var newa 	= $("<a href='#'>").addClass("tagphoto").text(tag);
			var newb 	= $("<b>").attr("datakey",tag).attr("datapcode",active_project_id);

			newa.prepend(newb);
			newli.append(newa);

			$("#addtags ul").prepend(newli);
		}
	}
	function loadThumbs(pcode, filters){
		var data = { pcode: pcode, filters: filters, ajax:"loadThumbs" };
		$.ajax({
			method: "POST",
			url: "project_agg_photos.php",
			data: data,
			dataType : "json",
			success: function(response){
				// console.log(response);
				// why the fuck was this container id with "tags"?
				$("#tags").empty();
				$("#tags").html(response.code_block);

				pins 			= response.photo_geos;
				var gmarkers 	= drawGMap(response.photo_geos, 'photos', 16);
				bindMapFunctionality(gmarkers);

				$(".photo_count span").text("("+$(".walk_photo").length+")");
				bindProperties();
			},
			error: function(response){
				console.log("error",response);
			}
		});
	}
	function bindMapFunctionality(gmarkers){
		$.each(gmarkers, function(){
			var el 				= this;
			var starting_icon 	= el.getIcon();
			$("#" + this.extras["photo_id"]).hover(function(){
				 el.setIcon({url: 'img/marker_purple.png'});
			},function(){
				 el.setIcon({url:starting_icon});
			});
		});

		for(var i in gmarkers){
			// add event to the images
			google.maps.event.addListener(gmarkers[i], 'mouseover', function(event) {
				this.starting_icon = this.getIcon();
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
				var starting_icon = this.starting_icon.hasOwnProperty("url") ? this.starting_icon.url : this.starting_icon;
				this.setIcon({url:starting_icon});
				$("#" + photo_id).removeClass("photoOn");
	        });
		}
	}
	function bindProperties(){
	    $( ".ui-widget-drag").draggable({
	      cursor: "move",
	      //containment:$('#addtags'),
	      helper: 'clone',
	      start: function(event,ui){
	      	$(ui.helper).addClass("ui-draggable-helper");
	      },
	      helper: function(event,ui){
	      	var tag 	= $(this).find("b").attr("datakey");
	      	var pcode 	= $(this).find("b").attr("datapcode");
	      	return($("<i>").addClass("dragtag").text(tag));
	      },
	      drag: function(event,ui){
	      	// console.log("dropping");
	      }
	    });

	    $( ".ui-widget-drop" ).droppable({
	    	over: function(){
	    		$(this).find("img").addClass("draghover");
	    	},
	    	out: function(){
	    		$(this).find("img").removeClass("draghover");
	    	},
	    	drop: function( event, ui ) {
		      	var drag 	= (ui.draggable[0].innerText.trim());
		      	var drop 	= event.target.id;
		      	var temp 	= drop.split("_");
		      	var proj 	= temp[0];
		      	var p_ref 	= temp[0] +"_"+ temp[1] +"_"+ temp[2] +"_"+ temp[3];
		      	var exists 	= false;
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
</script>
<?php //markPageLoadTime("Summary Page Loaded") ?>





