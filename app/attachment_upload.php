<?php
require_once("common.php");

header("Access-Control-Allow-Origin: *");

// AJAX UPLOAD WALK DATA
//THIS WORKS ON DEV!
if(isset($_POST["doc"]) && isset($_POST["doc_id"])){
	$_id = filter_var($_POST["doc_id"], FILTER_SANITIZE_STRING);
	$doc = json_decode($_POST["doc"],1);

    // IF THE DOC WAS PROPERLY PASSED IN
    if(isset($doc["_id"])){
        $local_folder = "temp/$_id";
        if( !file_exists($local_folder) ){
            mkdir($local_folder, 0777, true);
        }

        // CHECK IF WALK DATA ALREADY EXISTS, NEED TO DELETE IT TO WRITE IT AGAIN, NO OVERWRITE FEATURE?
        $walk_data = $local_folder."/".$_id.".json";
        if( file_exists($walk_data) ){
            unlink($walk_data);
        }

        // CREATE NEW WALK DATA, THEN RETURN EXPECTED LIST OF FILE ATTACHMENTS?
        $fp = fopen($walk_data,'w');
        if(fwrite($fp, json_encode($doc))){
            $nice_return = array();
            foreach($doc["photos"] as $photo){
                array_push($nice_return, array("_id" => $_id . "_" . $photo["name"], "name" => $photo["name"])); 
                foreach($photo["audios"] as $audioname){
                    array_push($nice_return, array("_id" => $_id . "_" . $audioname, "name" => $audioname));
                }
            }
            print_r(json_encode($nice_return));
        }else{
            //what to do if it fails?
            //nothing i guess
        }
        fclose($fp);
    }
    exit;
}

// AJAX UPLOAD ATTACHMENTS
if( isset($_REQUEST["walk_id"]) ){
    $walk_id        = filter_var($_REQUEST["walk_id"], FILTER_SANITIZE_STRING);
    $local_folder   = "temp/$walk_id/";

    require('UploadHandler.php');
    $options = array('overwrite' => true);
    $upload_handler = new UploadHandler($options,true,null,$local_folder);

    exit;
}

exit;
