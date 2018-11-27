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
$active_pid 		= $_GET["pid"];

// output headers so that the file is downloaded rather than displayed
header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename=maps_'.$active_pid.'.csv');

// create a file pointer connected to the output stream
$output = fopen('php://output', 'w');

// output the column headings
fputcsv($output, array('walk id', 'photo name', 'type', 'latitude', 'longitude','good/bad','date', 'transcription'));

if( $active_pid ){
	//FIRST GET JUST THE DATES AVAILABLE IN THIS PROJECT
    $response 		= getAggMaps("[\"$active_pid\"]");
	$project_meta 	= $ap["project_list"][$active_pid];

	//PRINT TO SCREEN
	foreach($response["rows"] as $sesh){
		$sesh_id = substr($sesh['id'], -4);
		// print_rr($sesh);
		// foreach($sesh["value"]["geotags"] as $tag){
		// 	if($tag["accuracy"] <= 50 ){
		// 		if(isset($tag['lat']))
		// 			fputcsv($output, array($sesh_id, 'walk', $tag['lat'], $tag['lng'], '', date("F j, Y @ g:i a", floor($tag['timestamp']/1000)), ''));
		// 		else
		// 			fputcsv($output, array($sesh_id, 'walk', $tag['latitude'], $tag['longitude'], '', date("F j, Y @ g:i a", floor($tag['timestamp']/1000)), ''));
		// 	}

		// }
		
		foreach($sesh["value"]["photos"] as $photo){
			$tag = $photo["geotag"];
			$date = isset($tag['timestamp']) ? date("F j, Y @ g:i a", floor($tag['timestamp']/1000)) : "N/A";
			switch($photo['goodbad']){
				case 0:
					$goodbad = "None";
					break;
				case 1:
					$goodbad = "bad";
					break;
				case 2:
					$goodbad = "good";
					break;
				case 3:
					$goodbad = "both";
					break;
				default:
					$goodbad = "N/A";
					break;
			}

			if(isset($tag['lat']) && isset($tag['lng'])){
				$long = $tag['lng']; 
				$lat = $tag['lat'];
			}else{
				$long = $tag['longitude'];
				$lat = $tag['latitude'];
			}
			if(isset($photo['audios']) && !empty($photo['audios'])){
						$transcript = "";

				foreach($photo['audios'] as $audios){
					if(isset($sesh['value']['transcriptions']["$audios"]['text']))
						$transcript .= $sesh['value']['transcriptions']["$audios"]['text'];
					else if(isset($sesh['value']['transcriptions']["$audios"]))
						$transcript .= $sesh['value']['transcriptions']["$audios"];
				}
			}

			fputcsv($output, array($sesh_id, $photo['name'], 'photo', $lat, $long, $goodbad, $date, $transcript));
			$transcript = "";

		}

		// foreach($sesh["value"]["transcriptions"] as $transcript){
		// 	$date = isset($tag['timestamp']) ? date("F j, Y @ g:i a", floor($tag['timestamp']/1000)) : "N/A";
		// 	if(!empty($transcript["text"])){
		// 		fputcsv($output, array($sesh_id, 'transcript', '', '','', $date, $transcript["text"]));
		// 	}
		// }
	}
}
?>




