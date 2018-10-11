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
$active_project_id 	= $_GET["active_project_id"];
$active_pid 		= $_GET["pid"];

// output headers so that the file is downloaded rather than displayed
header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename=transpcripts_'.$active_project_id.'.csv');

// create a file pointer connected to the output stream
$output = fopen('php://output', 'w');

// output the column headings
fputcsv($output, array('walk id', 'photo name', 'good/bad', 'date', 'transcription'));

if( $active_project_id ){
	//FIRST GET JUST THE DATES AVAILABLE IN THIS PROJECT
    $response 		= filter_by_projid("transcriptions","[\"$active_pid\"]");
	$project_meta 	= $ap["project_list"][$active_pid];

	$csv_buffer  	= array();
	foreach($response["rows"] as $sesh){
		$walk_id 	= substr($sesh['id'], -4);
		$last_ 		= strrpos($sesh["id"],"_");
		$walk_ts 	= substr($sesh["id"], $last_+1);
		$csv_buffer[$walk_ts] = array();

		foreach($sesh["value"]["photos"] as $photo){
			$tmp_buffer = array();

			//  must be in this order ; 'walk id', 'photo name', 'good/bad', 'date', 'transcription'
			$tmp_buffer[] 	= $walk_id;
			$tmp_buffer[] 	= $photo["name"];
			
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
					$goodbad 	= "N/A";
					break;
			}
			$tmp_buffer[] 	= $goodbad;
			$tmp_buffer[] 	= isset($photo["geotag"]['timestamp']) ? date("F j, Y @ g:i a", floor($photo["geotag"]['timestamp']/1000)) : "N/A";

			if(isset($photo["audios"]) && !empty($photo['audios'])){
				$transcript = "";
				foreach($photo["audios"] as $key => $audio_key){
					$transcript .= isset($sesh["value"]["transcriptions"]) && isset($sesh["value"]["transcriptions"][$audio_key]) ? $sesh["value"]["transcriptions"][$audio_key]["text"] : "";
				}
				if(empty(trim($transcript))){
					continue;
				}
				$tmp_buffer[] = $transcript;
			}

			$csv_buffer[$walk_ts][] = $tmp_buffer;
		}
	}
	krsort($csv_buffer);

	foreach($csv_buffer as $walk){
		foreach($walk as $photos_transcripts){
			fputcsv($output, $photos_transcripts);
		}
	}
}
?>




