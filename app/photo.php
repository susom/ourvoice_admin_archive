<?php
//ini_set('display_errors', 1);
//ini_set('dis/*play_startup_errors', 1);
//error_reporting(E_ALL);*/
ini_set('memory_limit','256M'); //necessary for picture processing.
require_once "common.php";

$page = "photo_detail";
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.1//EN" "http://www.w3.org/TR/xhtml11/DTD/xhtml11.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en">
<head>
<meta http-equiv="content-type" content="application/xhtml+xml; charset=UTF-8" />
<link href="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.7/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-BVYiiSIFeK1dGmJRAkycuHAHRg32OmUcww7on3RYdg4Va+PmSTsz/K68vbdEjh4u" crossorigin="anonymous"/>
<link href="css/dt_common.css?v=<?php echo time();?>" rel="stylesheet" type="text/css"/>
<link href="css/dt_summary.css?v=<?php echo time();?>" rel="stylesheet" type="text/css"/>
<link href="css/dt_photo_print.css?v=<?php echo time();?>"  rel="stylesheet" type="text/css" media="print" />
</head>
<body id="main" class="<?php echo $page ?>">
<div id="content">
	<?php include("inc/gl_nav.php"); ?>

	<div id="main_box">
		<div class='print_logo'></div>
		<?php
		if(isset($_GET["_id"]) && isset($_GET["_file"])){
			$_id 		    = trim(filter_var($_GET["_id"], FILTER_SANITIZE_STRING) );
			$_file 		    = trim(filter_var($_GET["_file"], FILTER_SANITIZE_STRING) );

		    $doc 		    = $ds->getPhotoData($_id, $_file);
			$proj_idx 	    = $doc["project_id"];

			$project_fs     = $ds->getProject($proj_idx);
            $snapshot       = $project_fs->snapshot();
			$project_data   = $snapshot->data();
            $project_tags   = array_key_exists("tags", $project_data) ? $project_data["tags"] : array();

			if(!isset($_SESSION["DT"]["project_list"][$proj_idx]["tags"])){
				$_SESSION["DT"]["project_list"][$proj_idx]["tags"] = array();
			}

            //need for forward compatability?
            $bounding_geos = [];
            $count = 1;
            foreach ($doc["bounding_geos"] as $item) {
                if(isset($item["lat"])){
                    $bounding_geos[$count] = [
                        "geotag" => [
                            "latitude" => $item["lat"],
                            "longitude" => $item["lng"]
                        ]
                    ];
                    $count++;
                }
            }
            $walk_bounding_geos = json_encode($bounding_geos);

            $photo 		= $doc["photo"];
			$device 	= $doc["device"]["platform"] ?? null;
			$prevnext 	= [];

			$lang       = $doc["lang"];
			$photo_i 	= $photo["i"];
			//PREV NEX
			 if(!empty($photo["prev"]) || $photo["prev"] > -1){
			 	$prevnext[0] = "photo.php?_id=" . $_id . "&_file=photo_" . $photo["prev"] . ".jpg";
			 }
			 if(!empty($photo["next"])){
			 	$prevnext[1] = "photo.php?_id=" . $_id . "&_file=photo_" . $photo["next"] . ".jpg";
			 }

			$hasaudio 	= !empty($photo["audio"]) ? "has" : "";
			$hasrotate 	= isset($photo["rotate"]) ? $photo["rotate"] : 0;
			$goodbad 	= "";
			if($photo["goodbad"] > 1){
				$goodbad  .= "<span class='goodbad good'></span>";
			}

			if($photo["goodbad"] == 1 || $photo["goodbad"] == 3){
				$goodbad  .= "<span class='goodbad bad'></span>";
			}

			if(!$photo["goodbad"]){
				$goodbad = "N/A";
			}

	        $timestamp = $long = $lat = "";
            list($project_id, $device_uuid, $walk_timestamp) = explode("_", $_id);

			if(array_key_exists("geotag", $photo) && !empty($photo["geotag"])){
	            $long 		= isset($photo["geotag"]["lng"])?  $photo["geotag"]["lng"] : $photo["geotag"]["longitude"];
	            $lat 		= isset($photo["geotag"]["lat"]) ? $photo["geotag"]["lat"] : $photo["geotag"]["latitude"];
	            $timestamp  = array_key_exists("timestamp", $photo["geotag"]) ? $photo["geotag"]["timestamp"] :  $walk_timestamp;
	        }


//			if($lat != 0 | $long != 0){
//	            $time = time();
//	            $url = "https://maps.googleapis.com/maps/api/timezone/json?location=$lat,$long&timestamp=$time&key=" .cfg::$gmaps_key;
//	            $ch = curl_init();
//	            curl_setopt($ch, CURLOPT_URL, $url);
//	            curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
//	            $responseJson = curl_exec($ch);
//	            curl_close($ch);
//
//	            $response = json_decode($responseJson);
//	            date_default_timezone_set($response->timeZoneId);
//	        }

            $transform  = array("transform" => "photo_detail", "rotate" => $hasrotate);
			$photo_uri 	= $ds->getStorageFile(cfg::$gcp_bucketName, $_id, $_file, $transform);
			// detectFaces($ph_id,$old, $photo_name);

			$attach_url = "#";
			$audio_attachments = "";
			
			$photo_tags     = isset($photo["tags"]) ? $photo["tags"] : array();
			$photo_comment  = str_replace("rnrn", "\r\n\r\n",$photo['text_comment']);
	        $text_comment   = "<div class='audio_clip'><textarea id='text_comment' name='text_comment' class='keyboard'>".  $photo_comment  ."</textarea></div>";

			if(isset($photo["audios"])){
				foreach($photo["audios"] as $filename => $txn){
					//WONT NEED THIS FOR IOS, BUT FOR NOW CANT TELL DIFF
                    //NEED TO ASSUME THE MP3 will bE THERE I GUESS

                    $filename_mp3   = str_replace(".wav", ".mp3", $filename);
                    $attach_url 	= $ds->getStorageFile(cfg::$gcp_bucketName, $_id, $filename_mp3);

                    //CHECK IF MP3 is THERE BEFORE SHOWING ANYTHING SHEEET
                    $file_check     = get_head($attach_url);
                    $file_check     = current($file_check);
                    $file_exists    = (!empty($file_check["Status"]) && strpos($file_check["Status"],"OK") > 0 );

                    if(!$file_exists){
                        continue;
                    }

                    $audio_src 		= $attach_url;
					$just_file 		= $attach_url;
					$script 		= "";

                    //ADD AUTO TRANSCRIBE BACK HERE
	    //             $audio_src 		= getConvertedAudio($attach_url, $lang);
	    //             $just_file 		= str_replace("./temp/","",$audio_src);
					// $confidence 	= appendConfidence($attach_url);
					// $script 		= !empty($confidence) ? "This audio was transcribed using Google's API at ".round($confidence*100,2)."% confidence" : "";

					//Works for archaic saving scheme as well as the new one :
                    $start_text     = "";
                    $transcription  = "";
                    if(!empty($txn)){
                        $start_text = $txn;
                        if(is_array($txn) && array_key_exists("text", $txn)){
                            $start_text = $txn["text"];
                        }
                        $transcription  = str_replace('&#34;','"', $start_text);
                        $transcription  = str_replace('&#34;','"', $transcription);
                        $transcription  = str_replace("rnrn", "\r\n\r\n",$transcription);
                    }

					$audio_attachments .=   "<div class='audio_clip mic'>
												<audio controls>
													<source src='$audio_src'/>
												</audio> 
												<a class='refresh_audio' href='$just_file' title='Audio not working?  Click to refresh.'>&#8635;</a> 
												<div class='forprint'>$transcription</div>
												<textarea class='audio_txn' name='$filename' placeholder='Click the icon and transcribe what you hear'>$transcription</textarea>
												<p id = 'confidence_exerpt'>$script</p>
					 						</div>";
				}
			}

			echo "<form id='photo_detail' method='POST'>";
			echo "<input type='hidden' name='doc_id' value='".$doc["id"]."'/>";
		    echo "<input type='hidden' name='photo_i' value='$photo_i'/>";
			
			echo "<div class='user_entry'>";
			echo "<hgroup>";
            $photo_date = "N/A";
            $photo_ts   = "N/A";

            if(!empty($timestamp)){
                $photo_date = date("F j, Y", floor($timestamp / 1000));
                $photo_ts = date("g:i a", floor($timestamp/1000));
            }
			echo "<h4>Photo Detail : 
			<b>".$photo_date." <span class='time'>@".$photo_ts."</span></b> 
			<i>".substr($doc["id"],-4)."</i></h4>";
			echo "</hgroup>";

			echo "<div class='photobox'>";
			echo 	"<section class='photo_previews'>";
			echo 		"<div>";	
			echo "
				<figure>
				<a class='preview rotate' rev='$hasrotate' data-photo_i=$photo_i data-doc_id='".$doc["id"]."' rel='google_map_0' data-long='$long' data-lat='$lat'>
						<canvas class='covering_canvas'></canvas>
						<img id = 'main_photo' src='$photo_uri' data-lang='$lang'/><span></span>
				</a>

				</figure>";
				$geotags   = array();
				$geotags[] = array("lat" => $lat, "lng" => $long);
				$json_geo  = json_encode($geotags);

				$gmaps[]   = "drawGMap($json_geo, 0, 16, $walk_bounding_geos);\n";

			echo 		"</div>";
			
			echo 		"<div id='tags'>";
			echo 			"<h4>Photo Tags:</h4>";
			echo 			"<ul class='photopage'>";
							foreach($photo_tags as $idx => $tag){
								echo "<li>$tag<a href='#' class='deletetag' data-deletetag='$tag' data-doc_id='$_id' data-photo_i='$photo_i'>x</a></li>";
							}
							echo "<li class='noback'><a href='#' class='opentag' data-photo_i='$photo_i'>+ Add New Tag</a></li>";
			echo 			"</ul>";
			echo		"</div>";
			echo 	"</section>";

			echo "<section class='side'>";
			echo "<button type = 'button' id = 'pixelateSubmit' data-loading class = 'btn btn-success hidden' style='float:right'>Submit</button>";
			echo "<button type = 'button' id = 'pixelate' class='btn btn-primary' style='float:right'>Select Area for Pixelation</button>";
            echo "<div id='pixelateLoading' class='alert alert-info hidden' role='alert'>Submitting pixelation... your page will automatically refresh</div>";
			echo "<aside>
					<b id = 'lat' value = '$lat'>lat: $lat</b>
					<b id = 'long' value = '$long'>long: $long</b>
					<div id ='cover' class = 'gmap location_alert'></div>
					<div id='google_map_0' class='gmap'></div>
				</aside>";
			echo "<aside class='forcommunity'>
					<h4>Good or bad for the community?</h4>
					$goodbad
				</aside>";

			echo "<aside>
					<h4>Why did you take this picture?</h4>
					$text_comment
					
					$audio_attachments
					<input type='submit' id='save_txns' value='Save Transcriptions'/>
				</aside>";

			if(count($prevnext)> 0){
				echo "<aside>";
				if(isset($prevnext[0])){
					echo "<a href='".$prevnext[0]."' class='prev'>Previous Photo</a>";
				}	
				if(isset($prevnext[1])){
					echo "<a href='".$prevnext[1]."' class='next'>Next Photo</a>";
				}
				echo "</aside>";
			}
			echo "<i class='print_only'>Data gathered using the Stanford Healthy Neighborhood Discovery Tool, © Stanford University 2017</i>";
			echo "</section>";
			echo "</div>";
			echo "</div>";
			echo "</form>";
		}

		include("inc/modal_tag.php");
		?>
	</div>
	<?php include("inc/gl_footer.php"); ?>
</div>
<script type="text/javascript" src="https://cdnjs.cloudflare.com/ajax/libs/autosize.js/3.0.20/autosize.js"></script>
<script src="https://code.jquery.com/jquery-3.2.1.min.js" integrity="sha256-hwg4gsxgFZhOsEEamdOYGBf13FyQuiTwlAQgxVSNgt4=" crossorigin="anonymous"></script>
<script type="text/javascript" src="js/dt_summary.js?v=<?php echo time();?>"></script>
<script type="text/javascript" src="https://maps.google.com/maps/api/js?key=<?php echo cfg::$gmaps_key; ?>&callback=emtpy_cb"></script>
<script src="js/jquery-ui.js"></script>
<script>
var ajax_handler = "ajaxHandler.php";
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

function saveTag(doc_id,photo_i,tagtxt,proj_idx){
	var data = { doc_id: doc_id, photo_i: photo_i, DragTag: tagtxt, action:"tag_text"};
	if(proj_idx){
		data["proj_idx"] = proj_idx
	}

	$.ajax({
		method: "POST",
		url: ajax_handler,
		data: data,
		dataType : "JSON",
		success: function(response){
			if(response["new_photo_tag"]){
				//ADD to UI
				var anewtag = $("<li>").text(tagtxt);
				var deletex = $("<a href='#'>").attr("data-deletetag",tagtxt).attr("data-doc_id",doc_id).attr("data-photo_i",photo_i).text("x").addClass("deletetag");
				anewtag.append(deletex);
				$("#tags ul").prepend(anewtag);
			}

			if(response["new_project_tag"]){
				//ADD TAG to modal tags list
				var newli 	= $("<li>");
				var newa 	= $("<a href='#'>").attr("data-doc_id",doc_id).attr("data-photo_i",photo_i).text(tagtxt).addClass("tagphoto");
				newli.append(newa);
				$("#newtag ul").prepend(newli);
				$("#newtag .notags").remove();
			}
		},
		error: function(e){
			console.log("error",e.responseText);
		}
	}).done(function( msg ) {
		// no need here
	});
	return;
}

function bindPixleationEvents(){
    var doc_id 	= $(".preview span").parent().data("doc_id"); //AFUM23894572093482093.. etc
    var photo_i = $(".preview span").parent().data("photo_i"); //Photo1.jpg
    var rotationOffset = $(".preview").attr("rev"); // rotation
    drawPixelation(doc_id, photo_i, rotationOffset);

}

function drawPixelation(doc_id = 0, photo_i = 0, rotationOffset){
    let canvas = $(".covering_canvas")[0];
    let ctx = canvas.getContext('2d');
    let canvasx = $(canvas).offset().left;
    let canvasy = $(canvas).offset().top;
    let last_mousex = last_mousey = 0;
    let mousex = mousey = 0;
    let mousedown = false;
    let coord_array = {};
    let data = {};
    let scrollOffset = 0; //necessary for scaling pixels from top of page for Y coordinate

    $("#pixelateSubmit").on("click",function(){
        if(!jQuery.isEmptyObject(data)){
            if(confirm('Are you sure you want to pixelate this area? This action cannot be undone.')){
                $(this).addClass("disabled hidden");
                $('#pixelate').addClass("disabled hidden");
                $('#pixelateLoading').removeClass("hidden");
                $.ajax({
                    method: "POST",
                    url: ajax_handler,
                    data: {
                        pic_id: doc_id,
                        photo_num: photo_i,
                        coordinates: data,
                        rotation: rotationOffset,
                        action: "pixelation"
                    }
                })
                .done(res => {
                    console.log(res);
                    window.location.reload();
                })
                .fail((e) => {
                    console.log('failed', e)
                    $('#pixelateLoading').text('Something went wrong, pixelation failed')
                });
            }
        }
    })


    $(".covering_canvas").on("mousedown", function(e){
        scrollOffset = window.scrollY;
        last_mousex = parseInt(e.clientX-canvasx);
        last_mousey = parseInt(e.clientY-canvasy+scrollOffset);
        mousedown = true;
    });

    $(canvas).on('mouseup', function(e) {
        mousedown = false;
        coord_array.width = (mousex-last_mousex);
        coord_array.height = (mousey-last_mousey);
        coord_array.x = last_mousex;
        coord_array.y = last_mousey;
        coord_array.width_pic = $("#main_photo")[0].getBoundingClientRect().width;;
        coord_array.height_pic = $("#main_photo")[0].getBoundingClientRect().height;;
        if(coord_array.width && coord_array.height){
            data = JSON.stringify(coord_array);
        }
    });

    $(canvas).on('mousemove', function(e) {
        scrollOffset = window.scrollY;
        mousex = parseInt(e.clientX-canvasx);
        mousey = parseInt(e.clientY-canvasy+scrollOffset);
        if(mousedown) {
            ctx.clearRect(0,0,canvas.width,canvas.height); //clear canvas
            ctx.beginPath();
            var width = mousex-last_mousex;
            var height = mousey-last_mousey;
            ctx.rect(last_mousex,last_mousey,width,height);
            ctx.strokeStyle = 'red';
            ctx.lineWidth = 2;
            ctx.stroke();
        }
    });
}

$(document).ready(function(){
	<?php
		echo implode($gmaps);
	?>
	if(!$("#long").attr('value') && !$("#lat").attr('value')){
		$("#cover").append("<p>No location data was found. Please enable location services on future walks</p>");
		$("#cover").css("background-color","rgba(248,247,216,0.7)").css("z-index","2");

	}

	$("#pixelate").on("click", function(){
		let doc_id 	= $(".preview span").parent().data("doc_id"); //AFUM23894572093482093.. etc
		let photo_i = $(".preview span").parent().data("photo_i"); //Photo1.jpg
		let rotationOffset = $(".preview").attr("rev"); // rotation
        let canvas = $(".covering_canvas")[0];
        let width_pic = $("#main_photo")[0].getBoundingClientRect().width;
        let height_pic = $("#main_photo")[0].getBoundingClientRect().height;

        setCanvas(canvas, rotationOffset, width_pic, height_pic);

        if($(this).hasClass("btn-primary")) { //Clicked pixelate
            $(this).removeClass("btn-primary")
                .addClass("btn-danger")
                .text("Cancel");
            $("#pixelateSubmit")
                .off()
                .removeClass("hidden");
            drawPixelation(doc_id, photo_i,rotationOffset);
        } else {
            $(this).removeClass("btn-danger")
                .addClass("btn-primary")
                .text("Select Area for Pixelation");
            $("#pixelateSubmit").addClass("hidden");
            $(".covering_canvas")
                .off()
                .css("cursor", "");
        }
	});

	window.snd_o           = null;
	$(".audio").click(function(){
		var soundclip 	= $(this).attr("href");
		window.snd_o	= new Audio(soundclip);
		window.snd_o.play();
		return false;
	});

	$(".preview span").click(function(){
        var _el = $(this);
        $(this).parent().addClass("temp_rotate");

        setTimeout(function(){
            _el.removeClass("temp_rotate");
        }, 1500);

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
		  method: "POST",
		  url: ajax_handler,
		  data: { doc_id: doc_id, photo_i: photo_i, rotate: rotate, action:"rotation" },
		  dataType: "text",
		  success:function(result){
              if(!_el.hasClass("temp_rotate")){
                  location.href=location.href;
              }else{
                  console.log("still clicking");
              }
	      },
	      error:function(e){
	      	console.log(e);
	      }
		}).done(function( msg ) {
			// alert( "Data Saved: " + msg );
		});

		return false;
	});

	autosize($('textarea'));

	$("#tags").on("click",".deletetag",function(){
		// get the tag index/photo index
		var doc_id 	= $(this).data("doc_id");
		var photo_i = $(this).data("photo_i");
		var tagtxt 	= $(this).data("deletetag");

		var _this 	= $(this);
		$.ajax({
			method: "POST",
			url: ajax_handler,
			data: { doc_id: doc_id, photo_i: photo_i, delete_tag_text: tagtxt, action:"delete_tag_text"}
		}).done(function( msg ) {
            _this.parent("li").fadeOut("medium",function(){
				_this.remove();
			});
		});
		return false;
	});

	$("#newtag").on("click",".tagphoto",function(){
		// get the tag index/photo index
		var doc_id 	= $(this).data("doc_id");
		var photo_i = $(this).data("photo_i");
		var tagtxt	= $(this).text();

		saveTag(doc_id,photo_i,tagtxt);

		//close tag picker
		$(document).click();
		return false;
	});

	$("#newtag form").submit(function(){
		var doc_id 		= $("#newtag_txt").data("doc_id");
		var photo_i 	= $("#newtag_txt").data("photo_i");
		var proj_idx 	= $("#newtag_txt").data("proj_idx");
		var tagtxt 		= $("#newtag_txt").val();

		if(tagtxt){
			$("#newtag_txt").val("");

			// add tag to project's tags and update disc_project
			// ADD new tag to UI
			saveTag(doc_id,photo_i,tagtxt,proj_idx);

			//close tag picker?
			setTimeout(function(){
				$(document).click();
			},750);
		}
		return false
	});

	$(".opentag").click(function(){
		//opens up the tag picker modal
		$("#newtag").fadeIn("fast");

		return false;
	});

	$("#photo_detail").submit(function(e){
	    e.preventDefault();
        var _id     = $('input[name=doc_id]').val();
        var _i      = $('input[name=photo_i]').val();
        var data    = {doc_id : _id, photo_i : _i };

        var changed = $("textarea[data-dirty='true']");
        if(changed.length){
            $("#save_txns").addClass("waiting", function(){
                var _el = $(this);
                setTimeout(function(){
                    _el.removeClass("waiting");
                },2000);
            });
            changed.each(function(){
                var prop    = $(this).attr("name");
                var val     = $(this).val();

                data["prop"]    = prop;
                data["text"]    = val;
                data["action"]  = prop == "text_comment" ? "save_text_comment" : "save_audio_txn";
                $.ajax({
                    method: "POST",
                    url: ajax_handler,
                    data: data,
                    success:function(response){
                        // window.location.reload(true);
                        $("#save_txns").removeClass("waiting");
                    }
                });
            });
        }
    });

	$("#text_comment, .audio_txn").change(function(){
	    $(this).attr("data-dirty",true);
    });
});

function setCanvas(canvas, rotationOffset, width_pic, height_pic){
	$(".covering_canvas").css("cursor", "crosshair");

	canvas.width = width_pic;
	canvas.height = height_pic;

	switch(rotationOffset){
		case 0,1,3: 
			canvas.width = width_pic;
			canvas.height = height_pic;
			break;
		case 2,4:
			canvas.width = height_pic;
			canvas.height = width_pic;
		default:
			break;
	}
}

$(document).on('click', function(event) {
	if (!$(event.target).closest('#newtag').length ) {
		$("#newtag").fadeOut("fast",function(){});
	}
});
</script>
</body>
</html>

