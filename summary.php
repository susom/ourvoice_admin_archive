<?php
require_once "common.php";

//markPageLoadTime("Summary Page Start Loading");
require 'vendor/autoload.php';

// FIRESTORE details
$keyPath 			= cfg::$FireStorekeyPath;
$gcp_project_id 	= cfg::$gcp_project_id; 
$walks_collection 	= cfg::$firestore_collection; 
$firestore_endpoint	= cfg::$firestore_endpoint; 
$firestore_scope 	= cfg::$firestore_scope; 

if(isset($_GET["clearsession"])){
	$_SESSION = null;
    header("location:summary.php");
}

if( empty($_SESSION["DT"]) ){
	// FIRST GET THE PROJECT DATA
	$couch_url 		= cfg::$couch_url . "/" . cfg::$couch_proj_db . "/" . cfg::$couch_config_db;
	$response 		= doCurl($couch_url);

	//TURN IT INTO PHP ARRAY
	$ap 			= json_decode(stripslashes($response),1);
	$_SESSION["DT"] = $ap;
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
	$active_pid 	= filter_var($_POST["active_pid"], FILTER_SANITIZE_NUMBER_INT);
	$date 			= filter_var($_POST["date"], FILTER_SANITIZE_STRING);
	$project_meta 	= $ap["project_list"][$active_pid];

	//GET THE DATA FROM disc_users
	$response 		= filter_by_projid("get_data_day","[\"$active_pid\",\"$date\"]");
	$days_data 		= rsort($response["rows"]); 
	
	$code_block 	= array();
	foreach($response["rows"] as $row){
		$doc 		= $row["value"];
		$code_block = array_merge($code_block, printRow($doc,$active_pid));
	}
	echo implode("",$code_block);
	exit;
}

//AJAX DELETEING DATA ENTRY
if(isset($_POST["for_delete"]) && $_POST["for_delete"]){
	$_id 	= filter_var($_POST["doc_id"], FILTER_SANITIZE_STRING);
	$_rev 	= filter_var($_POST["rev"], FILTER_SANITIZE_STRING);

	$fordelete      = [];
	array_push($fordelete, array(
	         "_id"          => $_id
	        ,"_rev"         => $_rev
	        ,"_deleted" => true
		));

	// Bulk update docs
    $couch_url 	= cfg::$couch_url . "/" . cfg::$couch_users_db . "/_bulk_docs";
    $response 	= doCurl($couch_url, json_encode(array("docs" => $fordelete)), "POST");

    $access_token 		= getGCPRestToken($keyPath, $firestore_scope);
	$object_unique_id 	= convertFSwalkId($_id);
	$firestore_url 		= $firestore_endpoint . "projects/".$gcp_project_id."/databases/(default)/documents/".$walks_collection."/".$object_unique_id;
    $deleted 			= restDeleteFireStore($firestore_url ,$access_token);
	exit;
}

//AJAX FOR MARKING DATA_PROCESSED
if(isset($_POST["data_procesed"]) && isset($_POST["doc_id"])){
    // FIRST GET A FRESH COPY OF THE WALK DATA
    $_id  		= filter_var($_POST["doc_id"], FILTER_SANITIZE_STRING);
    $url 		= cfg::$couch_url . "/" . cfg::$couch_users_db . "/" . $_id;
    $response   = doCurl($url);
    $doc 	 	= json_decode(stripslashes($response),1);
    $payload 	= $doc;
    $payload["data_processed"] = true;
    $response = doCurl($url, json_encode($payload), "PUT");

    $access_token 		= getGCPRestToken($keyPath, $firestore_scope);
	$object_unique_id 	= convertFSwalkId($_id);
	$firestore_url 		= $firestore_endpoint . "projects/".$gcp_project_id."/databases/(default)/documents/".$walks_collection."/".$object_unique_id."?updateMask.fieldPaths=data_processed";

	$firestore_data 	= ["data_processed" => array("integerValue" => 1)];
	$data           	= ["fields" => (object)$firestore_data];
	$json           	= json_encode($data);
	$response       	= restPushFireStore($firestore_url, $json, $access_token);
    exit;
}

//NOW LOGIN TO YOUR PROJECT 
if( ( (!empty($_SESSION["proj_id"]) OR !empty($_GET["id"]) )  && !empty($_SESSION["summ_pw"])  && !empty($_SESSION["authorized"]) )
    || ( isset($_SESSION["discpw"])  && $_SESSION["discpw"] == cfg::$master_pw )
){
    // FIRST CHECK IF LOGIN IS IN SESSION, _GET FOR DIRECT LINKING TO SUMMARY PAGE FROM INDEX PAGES
    if(empty($_POST["proj_id"])){
        $_POST["proj_id"]       = !empty($_GET["id"])  ? filter_var($_GET["id"], FILTER_SANITIZE_STRING) :  $_SESSION["proj_id"];
    }
    $_POST["summ_pw"]       = isset($_SESSION["summ_pw"]) ? $_SESSION["summ_pw"] : $_SESSION["discpw"];
    $_POST["authorized"]    = $_SESSION["authorized"];
}

if(isset($_POST["proj_id"]) && isset($_POST["summ_pw"])){
	if(!isset($_POST["authorized"])){
		$alerts[] = "Please check the box to indicate you are authorized to view these data.";
	}else{
		$proj_id  = trim(strtoupper(filter_var($_POST["proj_id"], FILTER_SANITIZE_STRING)));
		$summ_pw  = filter_var($_POST["summ_pw"], FILTER_SANITIZE_STRING);
		$found    = false;
		foreach($projs as $pid => $proj){
			if(isset($proj["project_id"]) && $proj_id == $proj["project_id"] && ( (isset($proj["summ_pass"]) && $summ_pw == $proj["summ_pass"]) || $summ_pw == $masterblaster) ) {
				$active_project_id      = $proj_id;
				$_SESSION["pid"]        = $active_pid = $pid;

                $_SESSION["proj_id"]        = $proj_id;
                $_SESSION["summ_pw"]        = $summ_pw;
                $_SESSION["authorized"]     = $_POST["authorized"];
				
                $found                      = true;
				break;
			}
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
            //FIRST GET JUST THE DATES AVAILABLE IN THIS PROJECT
            $response 		= getProjectSummaryData($active_project_id);
            $response_rows  = $response["rows"];

            $date_headers 	= [];
            $summ_buffer    = [];
            $summ_buffer[]  = "<div id='summary'>";
            $summ_buffer[]  = "<table cellpadding='0' cellspacing='0' width='100%'>";
            $summ_buffer[]  = "<thead>";
            $summ_buffer[]  = "<th>Date</th>";
            $summ_buffer[]  = "<th>Walk Id</th>";
            $summ_buffer[]  = "<th>Device</th>";
            $summ_buffer[]  = "<th>Photos #</th>";
            $summ_buffer[]  = "<th>Audios #</th>";
            $summ_buffer[]  = "<th>Texts #</th>";
            $summ_buffer[]  = "<th>Map Available</th>";
            $summ_buffer[]  = "<th>Upload Complete</th>";
            $summ_buffer[]  = "<th>Processed</th>";
            $summ_buffer[]  = "</thead>";
            $summ_buffer[]  = "</table>";
            $summ_buffer[]  = "<table cellpadding='0' cellspacing='0' width='100%'>";
            $summ_buffer[]  = "<tbody>";

            $total_photos = 0;
            $total_audios = 0;
            $total_texts  = 0;

            $dates      = array();
            $sum_row    = array();
            foreach($response_rows as $i => $row){
                $walk   = $row["value"];

                $date    = $walk["date"];
                $temp    = explode("-",$date);
                $dateB   = $temp[2]."-".$temp[0]."-".$temp[1];

                if(array_key_exists($date, $date_headers)){ //if the date already exists in dateheaders
                    $date_headers[$date]++;					// increment the counter
                }else{
                    $date_headers[$date] = 1;				//otherwise create an element [date -> #occurrences]
                }

                $walk   = $row["value"];
                $_id    = substr($row["id"] , -4);
                $uuid   = substr($row["id"], strpos($row["id"],"_")+1,5);

                // $device     = "uuid $uuid ...<br>" . $walk["device"]["platform"] . " (".$walk["device"]["version"].")";
                $device     = $walk["device"]["platform"] . " (".$walk["device"]["version"].")";
                $processed  = isset($walk["data_processed"]) ? $walk["data_processed"] : false;

                //check for attachment ids existing
                //IMPORTANT TO FORMAT THIS RIGHT OR ELSE WILL GET INVALID JSON ERROR
                $partial    = '["'.implode('","',$walk["attachment_ids"]).'"]';

                if(isset($walk["complete_upload"]) && $walk["complete_upload"]){
                    $expect_cnt = 0;
                }else{
                    $count_att  = checkAttachmentsExist($partial);
                    $expect_cnt = count($count_att["rows"]) - count($walk["attachment_ids"]);
                    if($expect_cnt === 0){
                        //PUSH Y flag TO THE COUCH SO WE DONT HAVE TO RUN THIS CHECK NEXT TIME
                        $url        = cfg::$couch_url . "/" . cfg::$couch_users_db . "/" . $row["id"];
                        $keyvalues  = array("complete_upload" => true);
                        $resp       = updateDoc($url,$keyvalues);
                    }
                }
                $uploaded       = $expect_cnt === 0 ? "Y" : "N ($expect_cnt files)";
                $data_processed = $processed ? "data_checked" : "";

                $sum_buffer_item = array();
                $sum_buffer_item[] = "<tr>";
                $sum_buffer_item[] = "<td>" . $date . "</td>";
                $sum_buffer_item[] = "<td><a href='#".$row["id"]."'>" . $_id . "</a></td>";
                $sum_buffer_item[] = "<td>" . $device . "</td>";
                $sum_buffer_item[] = "<td>" . $walk["photos"]. "</td>";
                $sum_buffer_item[] = "<td>" . $walk["audios"]. "</td>";
                $sum_buffer_item[] = "<td>" . $walk["texts"]. "</td>";
                $sum_buffer_item[] = "<td class='".$walk["maps"]."'>" . $walk["maps"]. "</td>";
                $sum_buffer_item[] = "<td class='$uploaded'>" . $uploaded. "</td>";
                $sum_buffer_item[] = "<td class='$data_processed'>" . ($processed ? "Y" : "") . "</td>";
                $sum_buffer_item[] = "</tr>";


                array_push($dates, $dateB);
                array_push($sum_row,$sum_buffer_item);

                $total_photos += $walk["photos"];
                $total_audios += $walk["audios"];
                $total_texts  += $walk["texts"];
            }
            arsort($dates);
            foreach($dates as $idx => $date){
                $summ_buffer = array_merge($summ_buffer, $sum_row[$idx]);
            }

            // FILL OUT REST OF TABLE EMPTY SPACE
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
            $summ_buffer[] = "<td>Totals:</td>";
            $summ_buffer[] = "<td>".($i+1)." walks</td>";
            $summ_buffer[] = "<td></td>";
            $summ_buffer[] = "<td>$total_photos</td>";
            $summ_buffer[] = "<td>$total_audios</td>";
            $summ_buffer[] = "<td>$total_texts</td>";
            $summ_buffer[] = "<td></td>";
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
        	echo "<input type='hidden' name='proj_id' value='".filter_var($_POST["proj_id"], FILTER_SANITIZE_STRING)."'/>";
        	echo "<input type='hidden' name='summ_pw' value='".filter_var($_POST["summ_pw"], FILTER_SANITIZE_STRING)."'/>";
        	$project_meta 		= $ap["project_list"][$active_pid];
        	$most_recent_date 	= true;
        	foreach($date_headers as $date => $record_count){
        		if($most_recent_date){
        			echo "<aside>";
        			echo "<h4 class='day' rel='true' rev='$active_pid' data-toggle='collapse' data-target='#day_$date'>$date</h4>";
        			echo "<div id='day_$date' class='collapse in'>";

        			//AUTOMATICALLY SHOW MOST RECENT DATE's DATA, AJAX THE REST
        			$response 	= filter_by_projid("get_data_day","[\"$active_pid\",\"$date\"]");

        			$days_data 	    = rsort($response["rows"]);
        			foreach($response["rows"] as $row){
        				$doc        = $row["value"];
                        echo "<a name='".$doc["_id"]."'></a>";
                        echo implode("",printRow($doc,$active_pid));
                    }
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
			console.log("rotation saved", response);
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

	//DATA PROCESSED?
    $(".collapse").on("change",".data_processed input",function(e){
    	console.log("data processed clicked");
        var el = $(this);
        el.prop("checked",true);

        var doc_id = el.data("id");
        $.ajax({
            type 		: "POST",
            url 		: "summary.php",
            data 		: { doc_id: doc_id, data_procesed: 1 },
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



