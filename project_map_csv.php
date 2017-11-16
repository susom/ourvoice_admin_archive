<?php
require_once "common.php";

// output headers so that the file is downloaded rather than displayed
header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename=data.csv');

// create a file pointer connected to the output stream
$output = fopen('php://output', 'w');

// output the column headings
fputcsv($output, array('session_id', 'type', 'latitude', 'longitude', 'timestamp'));

function filter_by_projid($view, $keys_array){
	$qs 		= http_build_query(array( 'key' => $keys_array ));
    $couch_url 	= cfg::$couch_url . "/" . cfg::$couch_users_db . "/" . "_design/filter_by_projid/_view/".$view."?" .  $qs;
    $response 	= doCurl($couch_url);
    return json_decode($response,1);
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
$active_project_id 	= $_GET["active_project_id"];
$active_pid 		= $_GET["pid"];

if( $active_project_id ){
	//FIRST GET JUST THE DATES AVAILABLE IN THIS PROJECT
    $response 		= filter_by_projid("transcriptions","[\"$active_pid\"]");
	$project_meta 	= $ap["project_list"][$active_pid];

	//PRINT TO SCREEN
	foreach($response["rows"] as $sesh){
		foreach($sesh["value"]["geotags"] as $tag){
			if($tag["accuracy"] <= 50 ){
				fputcsv($output, array($sesh['id'], 'walk', $tag['lat'], $tag['lng'], $tag['timestamp']));
			}

		}
		
		foreach($sesh["value"]["photos"] as $photo){
			$tag = $photo["geotag"];
			fputcsv($output, array($sesh['id'], 'photo', $tag['latitude'], $tag['longitude'], $tag['timestamp']));
		}
	}
}
?>




