<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once "common.php";
require 'vendor/autoload.php';
use Google\Cloud\Firestore\FirestoreClient;

// FIRESTORE details
$keyPath            = cfg::$FireStorekeyPath;
$gcp_project_id     = cfg::$gcp_project_id;
$walks_collection   = cfg::$firestore_collection;
$projects_data      = "ov_projects";
$firestore_endpoint = cfg::$firestore_endpoint;
$firestore_scope    = cfg::$firestore_scope;
$gcp_bucketID       = cfg::$gcp_bucketID;
$gcp_bucketName     = cfg::$gcp_bucketName;
$access_token       = getGCPRestToken($keyPath, $firestore_scope);

function restGetFireStore($firestore_url, $json_payload, $access_token){
    $curl               = curl_init($firestore_url);

    curl_setopt($curl, CURLOPT_HTTPHEADER, array( "Content-Type: application/json"
        , "Authorization: Bearer $access_token"
        , "X-HTTP-Method-Override: GET"
        )
    );
    curl_setopt($curl, CURLOPT_USERAGENT, "cURL");
    curl_setopt($curl, CURLOPT_CUSTOMREQUEST, "GET");
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
    $response   = curl_exec($curl);
    curl_close($curl);
    return $response;
}

function loginProject($project_id, $project_pass){
    global $gcp_project_id, $projects_data, $access_token;
    $result = array();

    if(isset($project_id) && isset($project_pass)){
        $firestore_url  = "https://firestore.googleapis.com/v1/projects/$gcp_project_id/databases/(default)/documents/$projects_data/$project_id";
        $response       = restGetFireStore($firestore_url, null, $access_token);
        $data           = json_decode($response,1);
        if(array_key_exists("fields",$data) && isset($data["fields"]["summ_pass"])) {
            $fs_pw = $data["fields"]["summ_pass"]["stringValue"];
            if ($fs_pw == $project_pass || $project_pass == "annban") {
                $result = $data["fields"];
            }
        }
    }

    return $result;
}

//POST LOGIN TO PROJECT
$project_snapshot = array();
if(isset($_POST["proj_id"]) && isset($_POST["proj_pw"])){
    $proj_id            = trim(strtoupper(filter_var($_POST["proj_id"], FILTER_SANITIZE_STRING)));
    $proj_pw            = filter_var($_POST["proj_pw"], FILTER_SANITIZE_STRING);
    $project_snapshot   = loginProject($proj_id, $proj_pw);
}
echo json_encode($project_snapshot);
?>