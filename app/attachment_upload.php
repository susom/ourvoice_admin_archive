<?php
require_once("common.php");

header("Access-Control-Allow-Origin: *");

// AJAX UPLOAD WALK DATA
$_POST["doc_id"]    = "KMUT_81759259-16E1-4AE1-A450-2954D396CB66_1_1651876110285";
$_POST["doc"]       = json_encode(array (
    'project_id' => 'KMUT',
    'user_id' => 1,
    'lang' => 'en',
    'photos' =>
        array (
            0 =>
                array (
                    'audio' => 2,
                    'geotag' =>
                        array (
                            'lat' => 37.71293934587335,
                            'lng' => -122.4696796586169,
                            'accuracy' => 35,
                            'altitude' => 65.45918846130371,
                            'heading' => -1,
                            'speed' => -1,
                            'timestamp' => 1651876105769.809,
                        ),
                    'goodbad' => 1,
                    'name' => 'photo_0.jpg',
                    'tags' =>
                        array (
                        ),
                    'audios' =>
                        array (
                            0 => 'audio_0_1.wav',
                            1 => 'audio_0_2.wav',
                        ),
                ),
        ),
    'geotags' =>
        array (
            0 =>
                array (
                    'lat' => 37.71293934587335,
                    'lng' => -122.4696796586169,
                    'accuracy' => 35,
                    'altitude' => 65.45918846130371,
                    'heading' => -1,
                    'speed' => -1,
                    'timestamp' => 1651876105769.809,
                ),
            1 =>
                array (
                    'lat' => 37.71291223396912,
                    'lng' => -122.46971128491035,
                    'accuracy' => 35,
                    'altitude' => 65.40262603759766,
                    'heading' => -1,
                    'speed' => -1,
                    'timestamp' => 1651876126061.636,
                ),
            2 =>
                array (
                    'lat' => 37.71292857226132,
                    'lng' => -122.46968059098386,
                    'accuracy' => 37,
                    'altitude' => 65.34757995605469,
                    'heading' => -1,
                    'speed' => -1,
                    'timestamp' => 1651876132522.9412,
                ),
            3 =>
                array (
                    'lat' => 37.71290242872129,
                    'lng' => -122.46972904433446,
                    'accuracy' => 35.504926935049646,
                    'altitude' => 65.35850524902344,
                    'heading' => -1,
                    'speed' => -1,
                    'timestamp' => 1651876139152.487,
                ),
        ),
    'survey' =>
        array (
        ),
    'device' =>
        array (
            'cordova' => '6.2.0',
            'manufacturer' => 'Apple',
            'model' => 'iPhone12,1',
            'platform' => 'iOS',
            'version' => '15.4.1',
        ),
    'currentDistance' => 0.007776886578624317,
    '_id' => 'KMUT_81759259-16E1-4AE1-A450-2954D396CB66_1_1651876110285',
    '_rev' => '2-45c7bf31278180e86fc24c84a190511c',
));

//THIS WORKS ON DEV!
if(isset($_POST["doc"]) && isset($_POST["doc_id"])){
	$_id = filter_var($_POST["doc_id"], FILTER_SANITIZE_STRING);
	$doc = json_decode($_POST["doc"],1);

    // IF THE DOC WAS PROPERLY PASSED IN
    if(isset($doc["_id"])){
        $local_folder = "temp/$_id";
        if( !file_exists($local_folder) ){
            mkdir($local_folder, 0777, true);
        }else{
            print_r(json_encode(array("fuck you" => "shitpiece", "local_folder" => $local_folder, "folder exists" => file_exists($local_folder))));
            exit;
        }

        // CHECK IF WALK DATA ALREADY EXISTS, NEED TO DELETE IT TO WRITE IT AGAIN, NO OVERWRITE FEATURE?
        $walk_data = $local_folder."/".$_id.".json";
        if( file_exists($walk_data) ){
            unlink($walk_data);
        }

        print_r(json_encode(array("fuck you" => "fuckface", "local_folder" => $local_folder, "folder exists" => file_exists($local_folder))));
        exit;

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
