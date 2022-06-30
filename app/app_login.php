<?php
require_once "common.php";

header("Access-Control-Allow-Origin: *");

//POST LOGIN TO PROJECT
$project_snapshot = array();

if(isset($_POST["proj_id"]) && isset($_POST["proj_pw"]) ){
		$proj_id            = trim(strtoupper(filter_var($_POST["proj_id"], FILTER_SANITIZE_STRING)));
        $proj_pw            = filter_var($_POST["proj_pw"], FILTER_SANITIZE_STRING);

        $project_snapshot   = $ds->loginProject($proj_id, $proj_pw);
}elseif(isset($_POST["version_check"])){
    $meta       = $ds->getAppVersion();
    $versions   = array("ios" => null, "android" => null);

    if(array_key_exists("version_ios", $meta) && array_key_exists("version_android", $meta)){
        $versions["ios"]        = $meta["version_ios"];
        $versions["android"]    = $meta["version_android"];
    }

    $project_snapshot = $versions;
}

echo json_encode($project_snapshot);
?>