<?php
require_once "common.php";

// NEXT GET SPECIFIC PROJECT DATA
$active_project_id 	= filter_var($_GET["active_project_id"], FILTER_SANITIZE_STRING);

// output headers so that the file is downloaded rather than displayed
header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename=walk_summary_'.$active_project_id.'.csv');

// create a file pointer connected to the output stream
$output = fopen('php://output', 'w');

// output the column headings
fputcsv($output, array('date', 'walk id', 'device', 'photos', 'audios','texts'));

if( $active_project_id ){
	//FIRST GET JUST THE DATES AVAILABLE IN THIS PROJECT

	$response_rows  = $ds->getProjectSummaryData($active_project_id);

	foreach($response_rows as $i => $walk){
		$_id        = $walk["id"];
		$date       = $walk["date"];
		$device     = $walk["device"]["platform"] . " (".$walk["device"]["version"].")";

		$sesh_id 	= substr($_id, -4);
		$date 		= $walk["date"];
		$photos 	= $walk["photos"];

		$audios 	= $walk["audios"];

		$texts 		= $walk["texts"];


		fputcsv($output, array($date, $sesh_id, $device, $photos, $audios, $texts));
	}
}
?>




