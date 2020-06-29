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
	#coverflow {
		position:absolute;
		left:0;
		width:100%; height:600px;
		z-index:1000;
		box-shadow:0 0 3px #000;
		background:#333;
	}
	#coverflow .cf_close {
		position:absolute;
		top:10px; right:10px;
		width:40px; height:40px; 
		display:inline-block;
		background:url(img/icon_redx.png) 50% no-repeat;
		background-size:contain;
		cursor:pointer;
		opacity:.5;
		transition: opacity .2s ease-in-out;
		z-index:1002;
	}
	#coverflow .cf_close:hover{
		opacity:1;
	}
	#coverflow .cf_nav{
		position:absolute;
		left:20px; top:50%;
		margin-top:-50px;
		cursor:pointer;
		height:100px;
		width:100px; 
		background:url(img/icon_cf_nav.png) 50%  no-repeat;
		background-size:contain;
		opacity:.2;
		transition: opacity .25s ease-in-out;
		z-index:1002;
	}
	#coverflow .cf_nav:hover{
		opacity:1;
	}
	#coverflow .cf_next{
		left:initial;
		right:20px;
		transform:rotate(180deg);
	}

	#coverflow figure{
		margin:0 auto;
		display:block;
		width:100%;
		text-align:center;
		position:relative;
	}
	#coverflow figcaption{
		position:absolute; 
		width:100%;
		bottom:0; 
	}
	#coverflow figcaption li{
		display: inline-block;
	    border: 1px solid #000;
	    background: #f7f7f7 url(img/icon_tag.png) 0 50% no-repeat;
	    background-size: contain;
	    border-radius: 3px;
	    padding: 0 10px 0 22px;
	    margin-right: 10px;
	    box-shadow: 0 0 3px #000;
	    position: relative;
	}

	#coverflow figure img{
		max-width:600px;
		max-height:600px;
	}
	#coverflow figure img[rev='1'] {
		transform: rotate(90deg) translateX(-100%);
		transform-origin: left bottom;
	}
	#coverflow figure img[rev='2'] {
		transform: rotate(180deg) translate(0,0);
	}
	#coverflow figure img[rev='3'] {
		transform: rotate(-90deg) translateY(100%);
		transform-origin: bottom left;
	}
	#coverflow .deletetag{
		top: -2px; right: -2px;

	}
	

	.cursor_spinny {
		cursor:progress;
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
		padding-top:40px;
	}
	li.draghover img,
	img.draghover{
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
	    top: 2px;
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
	    z-index:1001;
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
		var filters 			= [];
		loadThumbs(project_code,filters);

		var project_tags 		= JSON.parse('<?php echo json_encode($project_tags); ?>');
		loadTags(project_tags, project_code);

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
					
					setTagCounts();
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

		// START COVERFLOW
		$(".collapse").on("click", ".preview em", function(e){
			loadCoverFlow($(this).closest("li"));
			e.preventDefault();
		});
		$("body").on("click","#coverflow .cf_close", function(e){
			$("#coverflow").fadeOut("medium",function(){
				$(this).remove();
			});

			// renable thumb drops
			$(".all-photos .ui-widget-drop").droppable({
				disabled: false
			});
			e.preventDefault();
		});
		$("body").on("click","#coverflow .cf_prev", function(e){
			var prev 	= $("#coverflow figure").data("prev");
			var lid 	= "#"+prev;
			if($(lid).length){
				setCoverFlowSlide($(lid));
			}
			e.preventDefault();
		});
		$("body").on("click","#coverflow .cf_next", function(e){
			var next = $("#coverflow figure").data("next");
			var lid 	= "#"+next;
			if($(lid).length){
				setCoverFlowSlide($(lid));
			}
			e.preventDefault();
		});

		// HANDLE DRAG AND DROPPING OF TAGS ONTO PHOTOS
		$( "body" ).on("dropover", ".ui-widget-drop", function( event, ui ) {
	    		$(this).addClass("draghover");
		} );
		$( "body" ).on("dropout", ".ui-widget-drop", function( event, ui ) {
	    		$(this).removeClass("draghover");
		} );
		$( "body" ).on("drop", ".ui-widget-drop", function( event, ui ) {
				$(this).removeClass("draghover"); 

	    		var drag 	= $(ui.draggable[0]).find("b").attr("datakey");  //tag
		      	var drop 	= $(event.target).data("phid");
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
			          	var appendloc 	= $("#"+drop).find("ul"); //for thumbs
			          	var cf_tags 	= $("#coverflow ul"); //for cover flow

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

				            if(cf_tags.length){
				            	cf_tags.prepend(newli.clone());
				            }
			          	}

						setTagCounts();
			          }        
			            //THIS JUST STORES IS 
			          },function(err){
			          console.log("ERRROR");
			          console.log(err);
		        });
		} );

		//DELETE PHOTO TAG
		$("body").on("click",".deletetag",function(){
			// get the tag index/photo index
			var doc_id 	= $(this).data("doc_id");
			var photo_i = $(this).data("photo_i");
			var tagtxt 	= $(this).data("deletetag");
			// console.log(tagtxt);
			var _this 	= $(this);

			_this.addClass("cursor_spinny");
			_this.parent().addClass("cursor_spinny");
			$.ajax({
				method: "POST",
				url: "photo.php",
				data: { doc_id: doc_id, photo_i: photo_i, delete_tag_text: tagtxt},
				success:function(result){
					
				}

			}).done(function( msg ) {
				_this.parent("li").fadeOut("medium",function(){
					_this.parent("li").remove();
				});

				// need to delete from the other one too
				var lid 	= "#" + doc_id + "_photo_" + photo_i;
				tagtxt 		= tagtxt.split(" ").join(".");
				var litag 	= "li."+tagtxt;
				$(lid).find(litag).remove();

				setTagCounts();			
			});
			return false;
		});

		//DELETE PROJECT TAG
		$("#addtags").on("click",".tagphoto b", function(){
			var ele 	= $(this).closest("li");
			var tag 	= $(this).attr("datakey");
			var pcode 	= $(this).attr("datapcode");
			
			ele.addClass("cursor_spinny");
			$(this).addClass("cursor_spinny");
			$.ajax({
	          url:  "aggregate_post.php",
	          type:'POST',
	          data: { deleteTag: tag, project_code: pcode},
	          success:function(result){
				if($("#addtags li").length < 2){
					$("#addtags").removeClass("hastags");
				}

				// remove from UI: tag list, dropdown and tagged photos
             	ele.fadeOut("medium",function(){ $(this).remove(); });
             	$("#filter_tags option[value='"+tag+"']").remove();

             	var tagged = "li."+ tag.split(" ").join(".");
             	$(tagged).remove();
	          },
	          error:function(){
	          	
	          }	
	        });  
			return false;
		});

		// hover preview
		// var hover_zoom_delay = 500;
		// var hoverTimeOutConstant;
		// $("#tags").on("mouseover",".walk_photo", function(e){
		// 	var _this = $(this);
  // 			hoverTimeOutConstant = setTimeout(function() {
		// 		_this.addClass("cursor_spinny");
		// 		var full_img_src 		= _this.data("fullimgsrc");
		// 		var distance_from_top 	= _this.offset().top - $(window).scrollTop();
		// 		var distance_from_left 	= _this.offset().left;
		// 		var right_edge			= $(window).width() - 50;
		// 		var rotation 			= _this.attr("rev");

		// 		var perc_height 		= .9;
		// 		var scale_y 			= Math.round( $(window).height() * perc_height );
		// 		var img_top 			= Math.round( $(window).height() - scale_y ) / 2 ;
		// 		img_top 				= $(window).scrollTop() + img_top;
				
		// 		var img_left = distance_from_left+150;
		// 		var preview_img = $("<img>").attr("src",full_img_src).attr("id","hover_zoom").attr("rev",rotation);
				
		// 		// append body , set initial img_top and img_left need to adjust later for horizontal 
		// 		$("body").append(preview_img);
		// 		preview_img.css({ top: img_top, left: img_left, height: scale_y });

		// 		// ONCE IMAGE IS LOADED , CAN GET THE WIDTH AND HEIGHT AND CAN FINE TUNE POSITIONING
		// 		preview_img.on("load",function(){
		// 			_this.removeClass("cursor_spinny");
		// 			var img_w 	= $(this).width();
		// 			var img_h 	= $(this).height(); 
		// 			var ori_w 	= img_w;
		// 			var ori_h 	= img_h;
		// 			var diff_hw = 0; //when doin css rotations and translations, need to account for orientation change and rotation pivot top, right, bottom, left

		// 			if(rotation == 1 || rotation == 3){
		// 				ori_w 	= img_h;
		// 				ori_h 	= img_w;
		// 				diff_hw = img_h - img_w; //this is fucked
		// 			}
		// 			img_top = (Math.round( $(window).height() - ori_h ) / 2 ) - diff_hw;
		// 			img_top = $(window).scrollTop() + img_top;

		// 			// WILL ALWAYS SHOW IMAGE TO THE LEFT UNLESS ITS GONNA GO OFF THE RIGHT EDGE, UGH
		// 			if( (img_left + ori_w) > right_edge ){
		// 				// REDO it from the left  of the thumb
		// 				img_left = distance_from_left - (ori_w + 10);
		// 			} 

		// 			$(this).css({ top: img_top, left: img_left });
		// 		});
				
		// 	}, hover_zoom_delay);

		// 	e.preventDefault();
		// });
		// $("#tags").on("mouseout",".walk_photo", function(e){
		// 	clearTimeout(hoverTimeOutConstant);
		// 	$("#hover_zoom").remove();
		// });
		// $("body").on("click","#hover_zoom", function(){
		// 	$(this).remove();
		// });

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
							loadTags(project_tags, project_code);

							if(!$("#addtags").hasClass("hastags")){
								$("#addtags").addClass("hastags");
							}
							
							// add to drop down
							$("#filter_tags").append($("<option value='"+tagtxt+"'>"+tagtxt+"</option>"))	;						
							setTagCounts();

							bindTagDragProperties();
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
	function loadTags(project_tags, project_code){
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
			var tag 		= project_tags[i];
			var newli 	= $("<li>").addClass("ui-widget-drag");
			var newa 	= $("<a>").addClass("tagphoto").text(tag);
			var newb 	= $("<b>").attr("datakey",tag).attr("datapcode",project_code);

			newa.prepend(newb);
			newli.append(newa);

			$("#addtags ul").prepend(newli);
		}
	}
	function setTagCounts(){
		$("b[datakey]").each(function(){
			var tag 		= $(this).attr("datakey");
			var class_tag 	= "." + tag.split(" ").join(".");
			var tag_count 	= $(".all-photos").find("li" + class_tag).length;
			var count_span 	= $("<span>").text(" : "+tag_count);

			$(this).parent().find("span").remove();
			$(this).parent().append(count_span);
		});
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
				bindTagDragProperties();

				var n 			= 0;  //know there should be at least 1 starting with 0
				var preloads 	= []; // for storing the preloads
				recursivePreload(n, preloads);

				setTagCounts();
			},
			error: function(response){
				console.log("error",response);
			}
		});
	}
	function loadCoverFlow(_el){
		// INJECT THE COVERFLOW INTO DOM
		// GET TOP OF addtags
		var top_of_tags = $("#addtags .innerbox").offset().top;
		var top_of_cf	= top_of_tags - 600; //place cover flow right on top of tags so can drag tag

		var cf 		= $("<div>").attr("id","coverflow");
		var close 	= $("<a>").addClass("cf_close");
		var prev 	= $("<a>").addClass("cf_prev").addClass("cf_nav");
		var next 	= $("<a>").addClass("cf_next").addClass("cf_nav");

		$("body").append(cf);
		cf.css({top: top_of_cf});
		cf.append(close);
		cf.append(prev);
		cf.append(next);

		// LOAD THE JQUERY OBJECT FOR A THUMB
		setCoverFlowSlide(_el);
		
		return false;
	}
	function setCoverFlowSlide(_el){
		// clear previous
		$("#coverflow figure").remove();

		// TAKES A SINGLE thumb jquery OBj , needs context for PREV NEXT
		//_el needs to inform previous and next somehow
		var phid 		= _el.data("phid");
		var fullimgsrc 	= _el.find(".walk_photo").data("fullimgsrc");
		var rotation 	= _el.find(".walk_photo").attr("rev");
		var tags 		= _el.find("ul").clone();
		var prev 		= _el.prev().length ? _el.prev().attr("id") : null;
		var next 		= _el.next().length ? _el.next().attr("id") : null;

		// maybe take off the recursive preloading of all the images and just do it for the cover flow?
		var prev_imgsrc = $("#"+prev).find(".walk_photo").data("fullimgsrc");
		var temp_prev 	= new Image();
		temp_prev.src 	= prev_imgsrc;

		var next_imgsrc = $("#"+next).find(".walk_photo").data("fullimgsrc");
		temp_next 		= new Image();
		temp_next.src 	= next_imgsrc;

		var figure 	= $("<figure>").attr("data-next",next).attr("data-prev",prev);
		var fig 	= $("<fig>");
		var figimg 	= $("<img>").attr("rev",rotation).attr("src",fullimgsrc).attr("data-phid",phid).addClass("ui-widget-drop");
		var figc 	= $("<figcaption>");
			
		fig.append(figimg);
		figure.append(fig);
		figure.append(figc);
		$("#coverflow").append(figure);

		// ONCE IMAGE IS LOADED , CAN GET THE WIDTH AND HEIGHT AND CAN FINE TUNE POSITIONING
		figimg.on("load",function(){
			figc.append(tags);

			var img_w 	= $(this).width();
			var img_h 	= $(this).height(); 
			var ori_w 	= img_w;
			var ori_h 	= img_h;
			var diff_hw = 0; //when doin css rotations and translations, need to account for orientation change and rotation pivot top, right, bottom, left
		
			var deg 		= 0; 
			var transx 		= 0;
			var transy 		= 0;
			var hor_adjust;

			//This following song and dance is only cause of this weird css rotation pivots
			//first reset the definitions of w/h
			if(rotation == 1 || rotation == 3){
				ori_w 	= img_h;
				ori_h 	= img_w;
				diff_hw = img_h - img_w; 
			}
			vert_adjust = (Math.round( 600 - ori_h)/2 ) - Math.abs(diff_hw);
			
			if(rotation == 1){
				deg 	= "90deg";
				transx 	= (ori_h*-1 + vert_adjust) + "px";
				transy  = (Math.abs(diff_hw)/2)+ "px";
			}

			if(rotation == 2){
				deg 	= "180deg";
			}

			if(rotation == 3){
				deg 	= "-90deg";
				transx 	= -1*vert_adjust + "px";
				transy 	= (ori_h + Math.abs(diff_hw)/2) + "px";
			}

			$(this).css("transform","rotate("+deg+") translateX("+transx+") translateY("+transy+")").css("border","3px solid gree");
		});

		bindCFDroppable();
	}
	function recursivePreload(n, preloads){
		// will use parralell preloading for all fullsize images in a chunk
		// but do the chunks in sequence by recursion
		var _chunk 		= $("#tags .preview_chunk[data-chunk='"+n+"']");
		var perchunk 	= _chunk.data("perchunk");
		if(_chunk.length){
			// console.log("Preloading chunk of ", perchunk , " in preview chunk ", n);
			_chunk.find(".walk_photo").each(function(ndx){
				var _photo 		= $(this);
				var chunkn 		= n*perchunk;
				var x 			= ndx+chunkn;
				var imgsrc 		= _photo.data("fullimgsrc");
				preloads[x] 	= new Image();
				preloads[x].src = imgsrc;
				// preloads[x].onload 	= () => console.log("loaded",$(this).attr("data-fullimgsrc"));
				// preloads[x].onerror 	= err => console.error(err, " not loaded ", $(this).attr("data-fullimgsrc"));

				preloads[x].decode().then(() => {
					// console.log("img preloaded ", imgsrc ); 
				}).catch((encodingError) => {
					// Do something with the error.
					// console.log(encodingError, imgsrc );
				});
			});

			n++;
			recursivePreload(n, preloads);
		}
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
	function bindTagDragProperties(){
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
	      drag: function(event,ui){}
	    });

	    $( ".ui-widget-drop" ).droppable({
	    	disabled : false
	    }); 
	}
	function bindCFDroppable(){
		// temporarily disable thumb drops
		// renenabled in the event for "#coverflow .cf_close"
		$(".all-photos .ui-widget-drop").droppable({
			disabled: true
		});

		$( "img.ui-widget-drop" ).droppable({
			disabled: false
	    }); 
	}
</script>
<?php //markPageLoadTime("Summary Page Loaded") ?>





