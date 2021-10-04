<?php
 ini_set('display_errors', 1);
 ini_set('display_startup_errors', 1);
 error_reporting(E_ALL);

require("common.php");
require("vendor/autoload.php");
require("_datastore.php");

use Google\Cloud\Storage\StorageClient;

$ds                 = new Datastore();
$keyPath            = cfg::$FireStorekeyPath;
$firestore_scope    = cfg::$firestore_scope;
$access_token       = getGCPRestToken($keyPath, $firestore_scope);

$action             = "sync_walk_attachments";

function getLastXWeeksActivty($xweeks="-4 Week"){
    $turl   = cfg::$couch_url . "/" . cfg::$couch_users_db . "/"  . "_design/filter_by_projid/_view/get_data_ts";
    $tm     = urlToJson($turl);
    $stor   = $listid = array();
    $stor   = parseTime($tm,$stor);
    $listid = array();
    foreach ($stor as $key => $value){
        array_push($listid, $key);
    }

    $checkWeek      = strtotime($xweeks);
    $project_list   = array();
    for($i = 0 ; $i < count($stor) ; $i++){
        $iter = 0;
        rsort($stor[$listid[$i]]); //sort each element's timestamps
        while(!empty($stor[$listid[$i]][$iter]) && $iter < 1) //display only the most recent update per proj
        {
            if(($stor[$listid[$i]][$iter]/1000) > $checkWeek){
                array_push($project_list, array("project_id" => $listid[$i]));
            }
            $iter++;
        }
    }

    return $project_list;
}

switch($action){
    case "sync_walk_attachments":


        $gcp_bucketName     = cfg::$gcp_bucketName;
        $keyPath            = cfg::$FireStorekeyPath;
        $gcp_bucketID       = cfg::$gcp_bucketID;

        $project_list       = getLastXWeeksActivty();

        $storageCLient      = new StorageClient([
            'keyFilePath'   => $keyPath,
            'projectId'     => $gcp_bucketID
        ]);

        $attach_url         = cfg::$couch_url . "/" . cfg::$couch_attach_db;

        foreach($project_list as $project){
            if(!array_key_exists("project_id", $project)){
                continue;
            }

            $code           = $project["project_id"];
            $startkey       = $endkey = $code;

            $couch_url 		= cfg::$couch_url . "/" . cfg::$couch_users_db . "/_all_docs?startkey=%22$startkey%22&endkey=%22$endkey%EF%BF%B0%22" . "&include_docs=true&inclusive_end=false";
            $response 		= doCurl($couch_url);
            $all_docs       = json_decode($response, 1);

            foreach($all_docs["rows"] as $walk){
                $couch_url  = "https://".cfg::$couch_user.":".cfg::$couch_pw."@ourvoice-cdb.med.stanford.edu";
                $doc        = $walk["doc"];
                $walk_id    = $doc["_id"];
                if(empty($doc["photos"]) || array_key_exists("_attachments", $doc)){
                    continue;
                }


                $photos = $doc["photos"];
                foreach($photos as $photo){
                    $has_audio  = $photo["audio"];
                    $audios     = $photo["audios"];
                    $ph_name    = $photo["name"];

                    $file       = $walk_id. "_" . $ph_name ;
                    $attach_id  = $couch_url . "/".cfg::$couch_attach_db."/" . $file ;

                    $get_head   = get_head($attach_id);
                    $check_ETag = !empty($get_head) ? current($get_head) : array();

                    if(!empty($check_ETag)){
                        $uploaded   = uploadCloudStorage($file ,$walk_id , $gcp_bucketName, $storageCLient, $attach_id."/".$ph_name);
                        echo $attach_id."\r\n";
                    }

                    if($has_audio){
                        foreach($audios as $audio){
                            $file       = $walk_id. "_" . $audio ;
                            $attach_id  = $couch_url . "/".cfg::$couch_attach_db."/" . $file ;

                            $get_head   = get_head($attach_id);
                            $check_ETag = !empty($get_head) ? current($get_head) : array();

                            if(!empty($check_ETag)){
                                $uploaded   = uploadCloudStorage($file ,$walk_id , $gcp_bucketName, $storageCLient, $attach_id."/".$audio);
                                echo $attach_id."\r\n";
                            }
                        }
                    }
                }
            }
        }
    break;

    case "sync_walk_data":
//        JUST SYNC LAST 4 WEEKS OF UPDATED DATA
        $project_list   = getLastXWeeksActivty();

        /********************** COPY INTO FIRESTORE : WALK DATA FROM couch:disc_users **********************/
//        ALL DATA
//        $couch_url 		= cfg::$couch_url . "/" . cfg::$couch_proj_db . "/" . cfg::$couch_config_db;
//        $response 		= doCurl($couch_url);
//        $ap             = json_decode($response, 1);
//        $project_list   = $ap["project_list"];

        $firestore      = $ds->getFireStore();
        $docRef         = $firestore->collection("ov_walks");

        foreach($project_list as $project){
            if(!array_key_exists("project_id", $project)){
                continue;
            }

            $code           = $project["project_id"];
            $startkey       = $endkey = $code;

            $couch_url 		= cfg::$couch_url . "/" . cfg::$couch_users_db . "/_all_docs?startkey=%22$startkey%22&endkey=%22$endkey%EF%BF%B0%22" . "&include_docs=true&inclusive_end=false";
            $response 		= doCurl($couch_url);
            $all_docs       = json_decode($response, 1);

            foreach($all_docs["rows"] as $walk){
                $doc                = $walk["doc"];
                if(empty($doc["photos"]) || array_key_exists("_attachments", $doc)){
                    continue;
                }

                $old_id             = $doc["_id"];
                $fs_walk_id         = setWalkFireStore($old_id, $doc, $access_token);
                echo $fs_walk_id . "\r\n";
            }
        }
    break;

    case "sync_project_lists":
        /********************** COPY INTO FIRESTORE : PROJECT DATA FROM couch:ALL_PROJECTS **********************/
        //GET all_projects DATA FROM COUCH
        $couch_url 		= cfg::$couch_url . "/" . cfg::$couch_proj_db . "/" . cfg::$couch_config_db;
        $response 		= doCurl($couch_url);

        $ap             = json_decode($response, 1);
        $project_list   = $ap["project_list"];

        $firestore      = $ds->getFireStore();
        $docRef         = $firestore->collection("ov_projects");

        foreach($project_list as $project){
            $temp = $project;
            if(!array_key_exists("project_id", $project)){
                continue;
            }

            $code           = $project["project_id"];
            $temp["code"]   = $code;
            if(!array_key_exists("project_name", $project)){
                $temp["name"] = $code;
            }else{
                $temp["name"] = $project["project_name"];
            }


            if(!empty($project["app_lang"])){
                $temp["languages"]  = $project["app_lang"];
            }else{
                $temp["languages"]  = array(array("lang"=>"en", "language"=>"English"));
            }

            if(!array_key_exists("project_email", $project)){
                $temp["project_email"] = "banchoff@stanford.edu";
            }
            if(!array_key_exists("audio_comments", $temp)){
                $temp["audio_comments"] = "0";
            }
            if(!array_key_exists("text_comments", $temp)){
                $temp["text_comments"] = "1";
            }
            if(!array_key_exists("custom_takephoto_text", $temp)){
                $temp["custom_takephoto_text"] = "";
            }
            if(!array_key_exists("thumbs", $temp)){
                $temp["thumbs"] = "0";
            }
            if(!array_key_exists("expire_date", $temp)){
                $temp["expire_date"] = "";
            }

            unset($temp["project_id"]);
            unset($temp["project_name"]);
            unset($temp["app_lang"]);
            unset($temp["project_lead"]);
            unset($temp["institution"]);
            unset($temp["dropTag"]);

            try {
                // CREATE THE PARENT DOC
                $tempDoc = $docRef->document($code);
                $tempDoc->set($temp);
            } catch (exception $e) {
                echo "bad opperation";
            }

        }

    break;

    case "sync_translation_meta":
        /********************** COPY INTO FIRESTORE : APP/PORTAL SPECIFIC DATA FROM couch:ALL_PROJECTS **********************/
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
    break;
}





