<?php
require_once "common.php";

header("Access-Control-Allow-Origin: *");

$meta       = $ds->getAppVersion(); 

$versions   = array("ios" => null, "android" => null);

if(array_key_exists("version_ios", $meta) && array_key_exists("version_android", $meta)){
    $versions["ios"]        = $meta["version_ios"];
    $versions["android"]    = $meta["version_android"];
}

echo json_encode($versions);
?>