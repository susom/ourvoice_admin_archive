<?php
require_once("common.php");
ini_set('memory_limit','1024M'); //necessary for picture processing.

if(isset($_POST['start']) && $_POST['start'] == 1){
	initializeData();
	clearstatcache();
	exit();
}

?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.1//EN" "http://www.w3.org/TR/xhtml11/DTD/xhtml11.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en">
	<head>
		<meta http-equiv="content-type" content="application/xhtml+xml; charset=UTF-8" />
	  	<meta charset="utf-8">
	  	<script src="js/Chart.bundle.min.js"></script>
	  	<script src="js/jquery-3.3.1.min.js"></script>
  		<script src="js/jquery-ui.js"></script>
	    <link href="css/dt_common.css?v=<?php echo time();?>" rel="stylesheet" type="text/css"/>
	    <link href="css/dt_index.css?v=<?php echo time();?>" rel="stylesheet" type="text/css"/>
	    <link href="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.7/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-BVYiiSIFeK1dGmJRAkycuHAHRg32OmUcww7on3RYdg4Va+PmSTsz/K68vbdEjh4u" crossorigin="anonymous">
		<script src="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.7/js/bootstrap.min.js" integrity="sha384-Tc5IQib027qvyjSMfHjOMaLkfuWVxZxUPnCJA7l2mCWNIpG9mGCD8wGNIcPD7Txa" crossorigin="anonymous"></script>
		<link rel = "stylesheet" type = "text/css" href = "css/dt_project_configuration.css">
		<script src="js/common.js"></script>
	</head>
	<div id = "nav">
		<ul>
			<li><a href = "index.php">Home</a></li>
			<li><a href = "project_configuration.php">Project Configuration</a></li>
			<li><a href = "organization.php">Organization</a></li>
			<li><a href = "recent_activity.php">All Data</a></li>
			<li><a href = "visualization.php">Visualization</a></li>
			<li style="float:right"><a href="index.php?clearsession=1">Refresh Project Data</a></li>
			<li style="float:right"><img id = "magnifying_glass" src = "img/Magnifying_glass_icon.svg"></li>
			<li style="float:right"><input type = "text" id = "search" placeholder="TAG"></li>
			<li style="float:right"><a href = "">Search: </a></li>
		</ul>
	</div>
	<div id = "load">Loading</div>
	<div id = "Pictures">
		<div id = "content">
			<canvas id = "myChart2" height="500" width="500"></canvas>	
		</div>
		<div id = "content2">
			<canvas id = "myChart" height="500" width="500"></canvas>	
		</div>	
	</div>
	
	
<script>
$(document).ready(function(){
	$.ajax({
	        url:"visualization.php",
	        type: 'POST',
	        data: "&start=1" ,
	        success:function(result){ //on completion of data parsing, remove loading text on page.
	        	// console.log(result);
	        	var res = JSON.parse(result);
	        	console.log(res);
	  			$("#load").remove();
	  			drawChart(res);
			}
	    }); //ajax
});

function drawChart(data){
	 var names = [];
	 var num = [];
	 var ct = 0;
	 var colors = [];
	 var names2 = [];
	 var num2 = [];
	 var colors2 = [];
	 for(var name in data){
	 	if(ct < 29){
	 		names.push(name);
	 		num.push(data[name].total_pics);
	 		colors.push(random_rgba());
	 	}else{
	 		names2.push(name);
	 		num2.push(data[name].total_pics);
	 		colors2.push(random_rgba());
	 	}
	 	ct++;
	 }
	 console.log(num);
	 var a = $("#myChart");
	 var b = $("#myChart2");

	 var myChart = new Chart(a, {
	 		   		type: 'bar',
	 		   		data: {
	 		   			labels: names,
	 		   			datasets: [{
		 		   			label: '# Pictures',
				            data: num,
				            backgroundColor: colors
				        }]
	 		   		}
	 			});
	var myChart2 = new Chart(b, {
	 		   		type: 'bar',
	 		   		data: {
	 		   			labels: names2,
	 		   			datasets: [{
		 		   			label: '# Pictures',
				            data: num2,
				            backgroundColor: colors2
				        }]
	 		   		}
	 			});
}

function random_rgba() {
    var o = Math.round, r = Math.random, s = 255;
    return 'rgba(' + o(r()*s) + ',' + o(r()*s) + ',' + o(r()*s) + ',' + r().toFixed(1) + ')';
}

</script>




<?php 

function initializeDataF(){
	$data = array();
	$url 			= cfg::$couch_url . "/" . cfg::$couch_attach_db . "/" . cfg::$couch_all_db;
	$response 		= $ds->doCurl($url);
	$present_attachments = json_decode($response,1);
	// print_rr($present_attachments);
	$pic_count = 0;
	$audio_count = 0;
	$prev_id = explode("_",$present_attachments['rows'][0]['id'])[0];
	echo $prev_id;
	foreach($present_attachments['rows'] as $entry){
		$id = explode("_",$entry['id'])[0];
		$filetype = explode(".", $entry['id'])[1];
		if($prev_id == $id){
			// echo "$prev_id" . "\n";
			// echo $id . " " .$filetype;
			if($filetype == 'jpg' || $filetype == 'png'){
				$pic_count++;
				// echo 'pic';
			}
			else{
				// echo 'audio';
				$audio_count++;
			}

			$prev_id = $id;
		}else{
			echo 'entering ' . "$id" . " into \n" ;
			$data[$prev_id]['uploaded_pics'] = $pic_count;
			$data[$prev_id]['uploaded_audio'] = $audio_count;
			$pic_count = 0;
			$audio_count = 0;
			$prev_id = $id;
		}
	}
	print_rr($data);

}
function initializeData(){
	unset($_SESSION['visualization']);
	if(isset($_SESSION["visualization"]))
		$visual_data = $_SESSION['visualization'];

	else{
		$url 			= cfg::$couch_url . "/" . cfg::$couch_users_db . "/" . cfg::$couch_all_db . "?include_docs=true";
		$response 		= $ds->doCurl($url);
		$attachments 	= json_decode($response,1);
		$resolve 		= countData($attachments);
		$_SESSION['visualization'] = $resolve;
		$visual_data = $resolve;
	}
	
	echo json_encode($visual_data);

}

function countData($attachments){
	// echo getcwd() . "\n";
	if(!isset($attachments))
		return;
	$pic_count = 0;
	$audio_count = 0;
	$empty_count = 0;
	$data = array();
	//here we are counting the total
	foreach($attachments['rows'] as $index => $entry){ //for each project
		$id = explode("_",$entry['id'])[0];
		if(!isset($data[$id])){ //if the id isnt already stored
			$pic_count = 0; 
			$audio_count = 0;
			$empty_count = 0;
		}
		// foreach($entry['doc']['photos'] as $photos){ //each photos array
		// 	if(isset($photos['audio']))
		// 		$audio_count = $audio_count + $photos['audio'];
		// 	$pic_count++;

		// 	if(searchEmpty($entry['id'] . "_" . $photos['name']))
		// 		$empty_count++;

		// }
		$audio_count+=count($entry['doc']['photos']['audios']);
		$pic_count+=count($entry['doc']['photos']);

		$data[$id]['total_pics'] = $pic_count;
		$data[$id]['total_audio'] = $audio_count;
	}

	$url 			= cfg::$couch_url . "/" . cfg::$couch_attach_db . "/" . cfg::$couch_all_db;
	$response 		= $ds->doCurl($url);
	$present_attachments = json_decode($response,1);
	// print_rr($present_attachments);
	$pic_count = 0;
	$audio_count = 0;
	$prev_id = explode("_",$present_attachments['rows'][0]['id'])[0];
	// echo $prev_id;
	foreach($present_attachments['rows'] as $entry){
		$id = explode("_",$entry['id'])[0];
		$filetype = explode(".", $entry['id'])[1];
		if($prev_id == $id){
			// echo "$prev_id" . "\n";
			// echo $id . " " .$filetype;
			if($filetype == 'jpg' || $filetype == 'png'){
				$pic_count++;
				// echo 'pic';
			}
			else{
				// echo 'audio';
				$audio_count++;
			}

			$prev_id = $id;
		}else{
			// echo 'entering ' . "$id" . " into \n" ;
			$data[$prev_id]['uploaded_pics'] = $pic_count;
			$data[$prev_id]['uploaded_audio'] = $audio_count;
			$pic_count = 0;
			$audio_count = 0;
			$prev_id = $id;
		}
	}

	return $data;
}

function searchEmpty($ph_id){
//	img/thumbs/BIBP_9b06585bca0d569b_11_1534326675243_photo_0.jpg

	$localthumb = "img/thumbs/$ph_id";
	echo $localthumb . "\n";
	// print_rr($localthumb);
	if(file_exists($localthumb)){
		// echo 'yes';
	    return 0; //if empty return false
	}
	else{
		// echo "no";
	    return 1;
	}
}

?>

</div>
<!-- 
Array
(
    [id] => AAAA_6e6eb3df09e4d688_1_1530634916879
    [key] => AAAA_6e6eb3df09e4d688_1_1530634916879
    [value] => Array
        (
            [rev] => 6-55529bcd82748046e0aa86ab99b39d99
        )

    [doc] => Array
        (
            [_id] => AAAA_6e6eb3df09e4d688_1_1530634916879
            [_rev] => 6-55529bcd82748046e0aa86ab99b39d99
            [project_id] => 136
            [user_id] => 1
            [lang] => en
            [photos] => Array
                (
                    [0] => Array
                        (
                            [audio] => 1
                            [geotag] => Array
                                (
                                    [lat] => 37.7131411
                                    [lng] => -122.4694794
                                    [accuracy] => 18.0230007172
                                    [altitude] => 
                                    [heading] => 
                                    [speed] => 
                                    [timestamp] => 1530634927015
                                )

                            [goodbad] => 3
                            [name] => photo_0.jpg
                            [audios] => Array
                                (
                                    [0] => audio_0_1.amr
                                )

                        )

                    [1] => Array
                        (
                            [audio] => 1
                            [geotag] => Array
                                (
                                    [lat] => 37.7131397
                                    [lng] => -122.4694793
                                    [accuracy] => 15.138999939
                                    [altitude] => 
                                    [heading] => 
                                    [speed] => 
                                    [timestamp] => 1530634965940
                                )

                            [goodbad] => 2
                            [name] => photo_1.jpg
                            [audios] => Array
                                (
                                    [0] => audio_1_1.amr
                                )

                        )

                )

            [geotags] => Array
                (
                    [0] => Array
                        (
                            [lat] => 37.7131392
                            [lng] => -122.4694787
                            [accuracy] => 18.1079998016
                            [altitude] => 
                            [heading] => 
                            [speed] => 
                            [timestamp] => 1530634915501
                        )

                    [1] => Array
                        (
                            [lat] => 37.7131411
                            [lng] => -122.4694794
                            [accuracy] => 18.0230007172
                            [altitude] => 
                            [heading] => 
                            [speed] => 
                            [timestamp] => 1530634927015
                        )

                    [2] => Array
                        (
                            [lat] => 37.7131375
                            [lng] => -122.4694782
                            [accuracy] => 18.1149997711
                            [altitude] => 
                            [heading] => 
                            [speed] => 
                            [timestamp] => 1530634947016
                        )

                    [3] => Array
                        (
                            [lat] => 37.7131397
                            [lng] => -122.4694793
                            [accuracy] => 15.138999939
                            [altitude] => 
                            [heading] => 
                            [speed] => 
                            [timestamp] => 1530634965940
                        )

                    [4] => Array
                        (
                            [lat] => 37.7131482
                            [lng] => -122.4694749
                            [accuracy] => 18.5659999847
                            [altitude] => 
                            [heading] => 
                            [speed] => 
                            [timestamp] => 1530634971379
                        )

                )

            [survey] => Array
                (
                    [0] => Array
                        (
                            [name] => app_rating
                            [value] => 2
                        )

                )

            [device] => Array
                (
                    [cordova] => 7.0.0
                    [manufacturer] => motorola
                    [model] => MotoG3
                    [platform] => Android
                    [version] => 6.0
                )

            [currentDistance] => 0.00119207291964
        )

)

 -->
