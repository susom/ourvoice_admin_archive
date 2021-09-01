<?php
require_once "common.php";

//POST LOGIN TO PROJECT
$project_snapshot = array();
if(isset($_POST["proj_id"]) && isset($_POST["summ_pw"])){
		$proj_id            = trim(strtoupper(filter_var($_POST["proj_id"], FILTER_SANITIZE_STRING)));
		$summ_pw            = filter_var($_POST["summ_pw"], FILTER_SANITIZE_STRING);
        $project_snapshot   = $ds->loginProject($proj_id, $summ_pw);
}
echo json_encode($project_snapshot);

?>