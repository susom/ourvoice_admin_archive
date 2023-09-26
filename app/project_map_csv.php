<?php
require_once "common.php";

// NEXT GET SPECIFIC PROJECT DATA
$active_project_id 	= filter_var($_GET["active_project_id"], FILTER_SANITIZE_STRING);

// output headers so that the file is downloaded rather than displayed
header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename=maps_'.$active_project_id.'.csv');

// create a file pointer connected to the output stream
$output = fopen('php://output', 'w');

// output the column headings
fputcsv($output, array('walk id', 'photo name', 'type', 'latitude', 'longitude','good/bad','date', 'tags', 'transcription/text'));

if( $active_project_id ){
	//FIRST GET JUST THE DATES AVAILABLE IN THIS PROJECT

	$photos 	= $ds->filterProjectPhotos($active_project_id);

	//PRINT TO SCREEN
	if(!empty($photos)){
		if(isset($photos[0]["photo"]["timezone"])){
			date_default_timezone_set($photos[0]["photo"]["timezone"]);
		}
	}

	$transcript = "";
	foreach($photos as $item){
		$sesh_id= substr($item["id"], -4);

		$photo 	= $item["photo"];
		$tag 	= $photo["geotag"];
		$date 	= isset($tag['timestamp']) ? date("F j, Y @ g:i a", floor($tag['timestamp']/1000)) : "N/A";
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

		$long 	= !empty($tag['lng']) ? $tag['lng'] : (!empty($tag['longitude']) ? $tag['longitude'] : null);
		$lat 	= !empty($tag['lat']) ? $tag['lat'] : (!empty($tag['latitude']) ? $tag['latitude'] : null);

		if(isset($photo['audios']) && !empty($photo['audios'])){
			$transcript = "";

			foreach($photo['audios'] as $audio_name => $audio_data){
				if(!empty($audio_data) && isset($audio_data["text"])) {
					$transcript .= $audio_data['text'];
				}
			}
		}

		$tags = "";
		if(isset($photo["tags"])){
			$tags = implode(", ", $photo["tags"]);
		}


		if(!empty($photo["text_comment"])){
			$transcript .= "[Text] " . $photo['text_comment'];
		};

		fputcsv($output, array($sesh_id, $photo['name'], 'photo', $lat, $long, $goodbad, $date, $tags, $transcript));
		$transcript = "";
	}
}
?>




