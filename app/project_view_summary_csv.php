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
fputcsv($output, array('date', 'walk id', 'device', 'photos', 'audios','texts','processed'));

if( $active_project_id ){
	//FIRST GET JUST THE DATES AVAILABLE IN THIS PROJECT

	$photos 	= $ds->getProjectSummaryData($active_project_id);

	//PRINT TO SCREEN
	if(!empty($photos)){
		if(isset($photos[0]["photo"]["timezone"])){
			date_default_timezone_set($photos[0]["photo"]["timezone"]);
		}
	}

	foreach($photos as $item){
		$sesh_id= substr($item["id"], -4);

		$date 	= isset($tag['timestamp']) ? date("F j, Y @ g:i a", floor($tag['timestamp']/1000)) : "N/A";

		if(isset($photo['audios']) && !empty($photo['audios'])){
			$transcript = "";

			foreach($photo['audios'] as $audio_name => $audio_data){
				if(!empty($audio_data) && isset($audio_data["text"])) {
					$transcript .= $audio_data['text'];
				}
			}
		}

		// if(!empty($photo["texts"])){
		// 	$transcript .= "[Text] " . $photo['texts'];
		// };


		// fputcsv($output, array($sesh_id, $photo['name'], 'photo', $lat, $long, $goodbad, $date, $transcript));
		// $transcript = "";
	}

    foreach($walk as $item){
		$sesh_id = substr($item["id"], -4);

		$date = $walk["date"];

        $device = $walk["device"]["platform"] . " (".$walk["device"]["version"].")";

        $photos = $walk["photos"]; 

        $audios = $walk["audios"];

        $texts = $walk["texts"];

        $processed  = isset($walk["data_processed"]) ? $walk["data_processed"] : false;



		// if(isset($photo['audios']) && !empty($photo['audios'])){
		// 	$transcript = "";

		// 	foreach($photo['audios'] as $audio_name => $audio_data){
		// 		if(!empty($audio_data) && isset($audio_data["text"])) {
		// 			$transcript .= $audio_data['text'];
		// 		}
		// 	}
		// }

		// if(!empty($photo["texts"])){
		// 	$transcript .= "[Text] " . $photo['texts'];
		// };


		// fputcsv($output, array($sesh_id, $photo['name'], 'photo', $lat, $long, $goodbad, $date, $transcript));
		// $transcript = "";
	}


    // foreach($response_rows as $i => $walk){
    //     $_id        = $walk["id"];
    //     $date       = $walk["date"];

    //     $device     = $walk["device"]["platform"] . " (".$walk["device"]["version"].")";
    //     $processed  = isset($walk["data_processed"]) ? $walk["data_processed"] : false;

    //     //check for attachment ids existing
    //     //IMPORTANT TO FORMAT THIS RIGHT OR ELSE WILL GET INVALID JSON ERROR

    //     // $partial_files  = count($walk["partial_files"]);
    //     // $uploaded       = array_key_exists("completed_upload", $walk) ? "Y" : "N ($partial_files files)";
    //     $data_processed = $processed ? "data_checked" : "";

    //     $summ_buffer[] = "<tr>";
    //     $summ_buffer[] = "<td>" . $date . "</td>";
    //     $summ_buffer[] = "<td><a href='#".$walk["id"]."'>" . substr($_id, -4) . "</a></td>";
    //     $summ_buffer[] = "<td>" . $device . "</td>";
    //     $summ_buffer[] = "<td>" . $walk["photos"]. "</td>";
    //     $summ_buffer[] = "<td>" . $walk["audios"]. "</td>";
    //     $summ_buffer[] = "<td>" . $walk["texts"]. "</td>";
    //     // $summ_buffer[] = "<td class='".$walk["maps"]."'>" . $walk["maps"]. "</td>";
    //     // $summ_buffer[] = "<td class='$uploaded'>" . $uploaded. "</td>";
    //     $summ_buffer[] = "<td class='$data_processed'>" . ($processed ? "Y" : "") . "</td>";
    //     $summ_buffer[] = "</tr>";

    //     $total_photos += $walk["photos"];
    //     $total_audios += $walk["audios"];
    //     $total_texts  += $walk["texts"];
    // }

}
?>




