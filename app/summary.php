<?php
require_once "common.php";

//adhoc https redirect
include("inc/https_redirect.php");

if( empty($_SESSION["DT"]) ){
	// FIRST GET THE PROJECT DATA
    $ap = $ds->getProjectsMeta();
	$_SESSION["DT"] = $ap;
}

// NEXT GET SPECIFIC PROJECT DATA
$ap 				= $_SESSION["DT"];
$active_project_id 	= null;
$active_pid 		= null;
$alerts 			= array();

//IF COMING FROM CONFIGURATOR, THEN MIMIC A POST TO FLOW DOWN TO LOGIN FUNC
if( ( (!empty($_SESSION["proj_id"]) OR !empty($_GET["id"]) )  && !empty($_SESSION["summ_pw"])  && !empty($_SESSION["authorized"]) )
    || ( isset($_SESSION["discpw"])  && $_SESSION["discpw"] == cfg::$master_pw )
){
    // FIRST CHECK IF LOGIN IS IN SESSION, _GET FOR DIRECT LINKING TO SUMMARY PAGE FROM INDEX PAGES
    if(empty($_POST["proj_id"])){
        $_POST["proj_id"]   = !empty($_GET["id"])  ? filter_var($_GET["id"], FILTER_SANITIZE_STRING) :  (!empty($_SESSION["proj_id"]) ? $_SESSION["proj_id"] : null);
    }
    $_POST["summ_pw"]       = isset($_SESSION["summ_pw"]) ? $_SESSION["summ_pw"] : $_SESSION["discpw"];
    $_POST["authorized"]    = $_SESSION["authorized"];
}

//POST LOGIN TO PROJECT
if(isset($_POST["proj_id"]) && isset($_POST["summ_pw"])){
    if(!isset($_POST["authorized"])){
		$alerts[] = "Please check the box to indicate you are authorized to view these data.";
	}else{
		$proj_id            = trim(strtoupper(filter_var($_POST["proj_id"], FILTER_SANITIZE_STRING)));
		$summ_pw            = filter_var($_POST["summ_pw"], FILTER_SANITIZE_STRING);
		$found              = false;

        $project_snapshot   = $ds->loginProject($proj_id, $summ_pw);

        if(!empty($project_snapshot["active_project"])){
            $active_project_id          = $proj_id;
            $_SESSION["proj_id"]        = $proj_id;
            $_SESSION["summ_pw"]        = $summ_pw;
            $_SESSION["authorized"]     = $_POST["authorized"];
            $found                      = true;
        }

		if(!$found){
			$alerts[] = "Project Id or Project Password is incorrect. Please try again.";
		}
	}
}

$page = "summary";
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
        <?php
        if( $active_project_id ){
        	//PRINT TO SCREEN
        	echo "<h1 id='viewsumm' data-pcode='$active_project_id'>Discovery Tool Data Summary for $active_project_id</h1>";
            echo "<div id='summary'><div class='loading'></div></div>";

        	echo "<form id='project_summary' method='post'>";
        	echo "<input type='hidden' name='proj_id' value='".filter_var($_POST["proj_id"], FILTER_SANITIZE_STRING)."'/>";
        	echo "<input type='hidden' name='summ_pw' value='".filter_var($_POST["summ_pw"], FILTER_SANITIZE_STRING)."'/>";

            $date_headers       = $ds->getProjectDateBuckets($active_project_id);
        	$most_recent_date 	= true;
        	foreach($date_headers as $date => $walk_ids){
                $record_count   = count($walk_ids);
                $plural         = $record_count == 1 ? "" : "s";

        		if($most_recent_date){
        			echo "<aside>";
        			echo "<h4 class='day' rel='true' data-walkids='".json_encode($walk_ids)."' rev='$active_project_id' data-toggle='collapse' data-target='#day_$date'>$date <span>$record_count walks</span></h4>";
        			echo "<div id='day_$date' class='collapse in'>";

        			//AUTOMATICALLY SHOW MOST RECENT DATE's DATA, AJAX THE REST
//        			$response 	= $ds->filter_by_projid($active_project_id, $date);
                    $response 	= $ds->getWalksByIds($walk_ids);
        			foreach($response as $i => $row){
                        $doc = $row;
                        echo "<a name='".$doc["project_id"]."'></a>";
                        echo implode("",printRow($doc, $i));
                    }
        			echo "</div>";
        			echo "</aside>";

        			$most_recent_date = false;
        			continue;
        		}

        		//SHOW THE HEADERS OF ALL THE OTHER ONES
        		echo "<aside>";
        		echo "<h4 class='day' rel='false' data-walkids='".json_encode($walk_ids)."' rev='$active_project_id' data-toggle='collapse' data-target='#day_$date'>$date <span>$record_count walk$plural</span></h4>";
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
        			<h3><span>Welcome to the Discovery Tool Data Portal</span></h3>
        			<label><input type="text" name="proj_id" id="proj_id" placeholder="Project Id"/></label>
        			<label><input type="password" name="summ_pw" id="proj_pw" placeholder="Portal Password"/></label>
                    <disclaim>*Please note that Discovery Tool data can be viewed only by signatories to the The Stanford Healthy Neighborhood Discovery Tool Software License Agreement and in accordance with all relevant IRB/Human Subjects requirements.</disclaim>
        			<label class="checkauth">
                        <input type="checkbox" name='authorized'>  Check here to indicate that you are authorized to view these data
                    </label>
                    <button type="submit" id="gotoproj" class="btn btn-primary">Go to Project</button>
        		</form>
        	</div>
            <?php
        }
        ?>
    </div>

    <?php include("inc/gl_footer.php"); ?>
<div>
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
    return;
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
    $(".user_entry .reload_map").each(function(){
        if( $(this).data("mapgeo").length < 1 ){
            var cover = $(this).parent(".gmap").next(".location_alert_summary"); //closest cover for each summary 
            if(!cover.hasClass("cover_appended")){
                cover.append("<p>Location data is missing on at least one walk photo. Please enable location services on future walks</p>");
                cover.css("background-color","rgba(248,247,216,0.7)").css("text-align","center");
                cover.css("z-index","2");
                cover.addClass("cover_appended");
            }
        }
    });
}
$(document).ready(function(){
    var ajax_handler = "ajaxHandler.php";
	window.current_preview = null;
	var timer;
	// checkLocationData();
	// bindHover();

	$("#viewsumm").click(function(){
	    if($("#summary").is(":visible")){
            $("#summary").slideUp("fast");
            $(this).removeClass("open");
        }else{
            $("#summary").slideDown("medium");
            $(this).addClass("open");

            if($("#summary .loading").length){
                var active_pid 	= $(this).data("pcode");
                $.ajax({
                    type 		: "POST",
                    url 		: ajax_handler,
                    data 		: { active_project_id: active_pid, action : "project_summary"},
                }).done(function(response) {
                    setTimeout(function(){
                        $("#summary .loading").fadeOut("fast",function(){
                            $(this).remove()
                        });
                    },1500);

                    setTimeout(function(){
                        $("#summary").append(response);
                    },1600);
                }).fail(function(msg){
                    console.log("summary fetch failed");
                });
            }
        }
    });

	//COLLAPSING AJAX DATE HEADER
	$("h4.day").on("click",function(){
		var hasData 	= $(this).attr("rel");
		var active_pid 	= $(this).attr("rev");
		var date 		= $(this).text();
		var target 		= $(this).data("target");
        var walkids     = $(this).data("walkids");
		if(hasData == "false"){
			$.ajax({
			  type 		: "POST",
			  url 		: ajax_handler,
			  data 		: { active_pid: active_pid, date: date , walkids : walkids, action : "day_walks"},
			}).done(function(response) {
				setTimeout(function(){
					$(target).find(".loading").fadeOut("fast",function(){
						$(this).remove() });
				},1500);

                console.log("wtf", response);
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
        $(this).parent().addClass("temp_rotate");
		var rotate = $(this).parent().attr("rev");
		if(rotate < 3){
			rotate++;
		}else{
			rotate = 0;
		}
		$(this).parent().attr("rev",rotate);

		var doc_id 	    = $(this).parent().data("doc_id");
		var photo_i     = $(this).parent().data("photo_i");
        var filename    = $(this).parent().data("filename");

        $.ajax({
		  type 		: "POST",
		  url 		: ajax_handler, //photo
		  data 		: { doc_id: doc_id, _filename : filename, photo_i: photo_i, rotate: rotate, action : "rotation" },
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
			  url 		: ajax_handler, //photo
			  data 		: { doc_id: doc_id, photo_i: photo_i, delete: true , action: "delete_photo"}
			}).done(function(response) {
				var phid    = doc_id+"_photo_"+photo_i+".jpg";
                var ulwrap  = $("li[data-phid='"+phid+"']").closest("ul");
				$("li[data-phid='"+phid+"']").fadeOut("fast",function(){
					$(this).remove();

					//if last photo in walk , delete walk
					if(!ulwrap.find("li").length){
					    //last photo in walk , delete walk
                        $.ajax({
                            type 		: "POST",
                            url 		: ajax_handler,
                            data 		: { doc_id: doc_id, for_delete: true, action : "delete_walk"},
                        }).done(function(response) {
                            var _parent	= ulwrap.closest(".user_entry");

                            _parent.slideUp("medium", function(){
                                var day_cont = $(this).parent();
                                $(this).remove();
                                //if last walk in the day , delete day
                                if( !day_cont.find(".user_entry").length ){
                                    day_cont.closest("aside").slideUp("medium", function(){
                                        $(this).remove();
                                    });
                                }
                            });
                        }).fail(function(msg){
                            // console.log("rotation save failed");
                        });
                    }
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

		var _parent	= $(this).closest(".user_entry");

		var confirm = prompt("Deleting this walk will also delete all photos, audio, maps and survey data attached to it.  To confirm deletion type in the last 4 digits of the walk ID");
		if(confirm == last4){
			//AJAX DELETE IT
			$.ajax({
			  type 		: "POST",
			  url 		: ajax_handler,
			  data 		: { doc_id: _id, for_delete: true, action : "delete_walk"},
			}).done(function(response) {
				_parent.slideUp("medium", function(){
				    var day_cont = $(this).parent();
                    $(this).remove();
				    if( !day_cont.find(".user_entry").length ){
                        day_cont.closest("aside").slideUp("medium", function(){
                            $(this).remove();
                        });
                    }
                });
            }).fail(function(msg){
				// console.log("rotation save failed");
			});
		}
		return false;
	});

	//DATA PROCESSED?
    $(".collapse").on("change",".data_processed input",function(e){
    	console.log("data processed clicked");
        var el = $(this);
        el.prop("checked",true);

        var doc_id = el.data("id");
        $.ajax({
            type 		: "POST",
            url 		: ajax_handler,
            data 		: { doc_id: doc_id, data_procesed: 1, action: "data_processed" },
        }).done(function(response) {
            console.log(response);
            setTimeout(function(){
                el.parent().fadeOut(function(){
                    $(this).next().fadeIn();
                });
            },1000);
        }).fail(function(msg){
            // console.log("rotation save failed");
        });

        return false;
    });

    //EXPORT AS PDF
	$(".collapse").on("click",".export-pdf",function(e){
		
		var _id             =  $(this).data("id");
        var active_pid      = $(this).data("active_pid");
        var pcode           = $(this).data("pcode");

        var pdf_url = "print_walk_view.php?_id=" + _id + "&pcode=" + pcode + "&active_pid=" + active_pid;
        window.open(pdf_url, '_blank');
	});

	//reload live map
	$(".collapse").on("click",".reload_map",function(e){
		var json_geo 	= $(this).data("mapgeo");
		var i 			= $(this).data("mapi");
		drawGMap(json_geo, i, 16);
		return false;
	});
});
</script>
</body>
</html>
<?php //markPageLoadTime("Summary Page Loaded") ?>



