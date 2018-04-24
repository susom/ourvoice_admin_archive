<?php
require_once("common.php");

// AJAX HANDLE walk data
if(isset($_POST["doc"]) && isset($_POST["doc_id"])){
	$_id = $_POST["doc_id"];
	$doc = json_decode($_POST["doc"],1);
	// $url = cfg::$couch_url . "/" . cfg::$couch_users_db . "/" . $_id;

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

if( isset($_REQUEST["attach_id"]) ){
    $_id = $_REQUEST["attach_id"];

    $split      = strpos($_id, "_photo_") > -1 ? "_photo_" : "_audio_";
    $temp       = explode($split, $_id);
    $walk_id    = $temp[0]; 
    $local_folder   = "temp/$walk_id";

    // CHECK IF WALK DATA ALREADY EXISTS, NEED TO DELETE IT TO WRITE IT AGAIN, NO OVERWRITE FEATURE?
    $attachment     = $local_folder."/".$_id;
    if( file_exists($attachment) ){
        unlink($attachment);
    }

    if ($_FILES["attachment"]["error"] == UPLOAD_ERR_OK){
        $file = $_FILES["attachment"]["tmp_name"];
        // now you have access to the file being uploaded
        //perform the upload operation.
        move_uploaded_file( $file, $attachment );
        print_r(json_encode(array($attachment . " saved to disk?!!!")));
    }else{
        print_r(json_encode(array($attachment . "upload failed")));
    }
    exit;
}


exit;
