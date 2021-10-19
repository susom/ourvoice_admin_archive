<?php
require_once "common.php";

header("Access-Control-Allow-Origin: *");

//POST LOGIN TO PROJECT
$project_snapshot = array();

if(isset($_POST["proj_id"]) && isset($_POST["proj_pw"]) ){
		$proj_id            = trim(strtoupper(filter_var($_POST["proj_id"], FILTER_SANITIZE_STRING)));
        $proj_pw            = filter_var($_POST["proj_pw"], FILTER_SANITIZE_STRING);

        $project_snapshot   = $ds->loginProject($proj_id, $proj_pw);
}

echo json_encode($project_snapshot);
?>