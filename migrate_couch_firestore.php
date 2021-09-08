<?php
 ini_set('display_errors', 1);
 ini_set('display_startup_errors', 1);
 error_reporting(E_ALL);

require("common.php");
require("vendor/autoload.php");
require("_datastore.php");

$ds = new Datastore();

//GET all_projects DATA FROM COUCH
$couch_url 		= cfg::$couch_url . "/" . cfg::$couch_proj_db . "/" . cfg::$couch_config_db;
$response 		= doCurl($couch_url);

$ap             = json_decode($response, 1);
$project_list   = $ap["project_list"];
$app_text       = $ap["app_text"];
$consent_text   = $ap["consent_text"];
$survey_text    = $ap["survey_text"];

$firestore      = $ds->getFireStore();

//loop through app_text and add to firestore document key
$fs_texts = array();
foreach($survey_text as $id=> $item){
    $fs_texts["slide_".$id] = $item;
}

try {
    // CREATE THE PARENT DOC
    $docRef = $firestore->collection("ov_meta")->document("app_data");
    $docRef->set([
        'survey_text' => $fs_texts
    ], array("merge" => true));
} catch (exception $e) {
    echo "bad opperation";
}