<?php     
require_once "common.php";
require_once "inc/class.mail.php";

// ini_set('display_errors', 1);
// ini_set('display_startup_errors', 1);
// error_reporting(E_ALL);

// GET WALK ID , FROM THE PING
$uploaded_walk_id   = isset($_POST["uploaded_walk_id"]) ? $_POST["uploaded_walk_id"] : null;
// $proccesed_thumb_ids    = isset($_POST["proccesed_thumb_ids"]) ?  $_POST["proccesed_thumb_ids"] : null;                  
                                                                                                                   
if(!empty($uploaded_walk_id)){ 
    $_id                = $uploaded_walk_id;  
    $email              = isset($_POST["project_email"]) ? $_POST["project_email"] : false;   
    $temp               = explode("_",$_id);
    $project_id         = $temp[0];

    $emergency_uploaded = false;
    $check_attach_urls  = array();
    if(isset($_POST["doc"])){
        $doc    = json_decode($_POST["doc"],1);   
        
        // GET PROJECT EMAIL 
        $photos = $doc["photos"];

        // SLEEP GIVE IT TIME TO UPLOAD ATTACHMENTS 
        // 11 seconds (only?) same amount of time as it takes to get to 83% on phone
        sleep(11); 

        // CHECK IF ATTACHMENT IDS ARE ALL EXISTING WITHIN disc_attachments AFTER SLEEP PERIOD
        $couch_url = "http://".cfg::$couch_user.":".cfg::$couch_pw."@couchdb:5984";
        foreach($photos as $i => $photo){
            $ph_id          = $_id . "_" . $photo["name"];
            $photo_attach   = $couch_url . "/".$couch_attach_db."/" . $ph_id ;
            $check_attach_urls[$photo_attach] = get_head($photo_attach);
            if(isset($photo["audios"])){
                foreach($photo["audios"] as $filename){
                    $aud_id         = $_id . "_" . $filename;
                    $audio_attach   = $couch_url . "/".$couch_attach_db."/" . $aud_id ;
                    $check_attach_urls[$audio_attach] = get_head($audio_attach);
                }
            }
        }

        $meta = array("uploaded_walk_id"    => $_id  
                     ,"email"               => $email                                                                       
                     ,"attachments"         => $check_attach_urls
                 );   
        echo json_encode(array("uploaded_walk" => $meta)); 
    }else{
        // EMERGENCY UPLOAD SUCCESS
        $emergency_uploaded = "Notification: [$project_id] Emergency Backup Uploaded";

        // SCAN ./temp FOLDER FOR NEW FOLDERS!
        $backup_folder      = "temp/$_id";
        $backup_files       = scanBackUpFolder($backup_folder);

        foreach($backup_files as $file){
            $path = $backup_folder . "/" . $file;
            if(strpos($file,".json") > 0){
                $walks_url  = cfg::$couch_url . "/" . cfg::$couch_users_db ;
                $payload    = file_get_contents($path);
                $response   = doCurl($walks_url, $payload, 'POST');
            }else{
                $attach_url = cfg::$couch_url . "/" . cfg::$couch_attach_db; 
                // TWO STEPS
                
                // first , create the data entry
                $payload    = json_encode(array("_id" => $file));
                $response   = doCurl($attach_url, $payload, 'POST');
                $response   = json_decode($response,1);
                $rev        = $response["rev"];

                // TODO
                // IN CASE WHERE THE data entry is created, but the attachment is empty, 
                // just get the REV somehow and do the PREP attachment as USUAL
                // so only need to do the second step

                // next upload the attach
                $response   = prepareAttachment($file,$rev,$_id,$attach_url); 
            }
        }

        // DELETE THE DIRECTORY , NOT YET?
        // deleteDirectory($backup_folder);

        // RECURSIVELY GO THROUGH THOSE AND UPLOAD THOSE MAFUHS
        echo json_encode(array(  "ebackup"      => $backup_files
                                ,"backupfolder" => $backup_folder
                                ,"email"        => $email
                        ));
    }                                                                                

    // EITHER YES OR NO ON PARTIAL UPLOADS
    $failed_uploads = array();
    foreach($check_attach_urls as $attach_id => $head_result){
    	if(array_key_exists("error", $head_result)){
    		 $failed_uploads[] = $attach_id;
    	}
    }

    $from_name      = "Stanford Our Voice";
    $reply_email    = "irvins@stanford.edu";
    $email_subject  = count($failed_uploads) ? "Notification: [$project_id] New walk uploaded, possibly missing attachments" : "Notification: [$project_id] New walk uploaded!";
    $email_subject  = !$emergency_uploaded ? $subject : $emergency_uploaded;

    // EMAIL TO PROJECT ADMIN EMAIL, CC ANN,IRVIN, JORDAN 
    $msg_arr        = array();
    $msg_arr[]      = "Hi $project_id  Administrator,<br>";
    $msg_arr[]      = "This email is letting you know that a walk with id $_id has been uploaded to your project.<br>";
    $msg_arr[]      = "Please check the <a href='http://ourvoice-projects.med.stanford.edu/summary.php' target='blank' >Our Voice Data Portal</a> to view the walk data.<br>";
    if(count($failed_uploads)){
        $msg_arr[]  = "There may have been some technical issues uploading the data resulting in the following attachment files not being fully uploaded:";
        $msg_arr[]  = "<ul>";
        $msg_arr[]  = "<li>" . implode("</li>\r\n<li>", $failed_uploads) . "</li>";
        $msg_arr[]  = "</ul>";
        $msg_arr[]  = "If you find that some or all of them are missing from the portal, you can try to reset and upload that walk again from the device.";
        $msg_arr[]  = "Please remember to be on a stable wifi connection and leave the app open through out the upload process.<br>";
    }
    $msg_arr[]      = "Cheers!";
    $msg_arr[]      = "The Our Voice Team";
    $msg_arr[]      = "<i style='font-size:77%;'>Participant rights: contact our IRB at 1-866-680-2906</i>";
    $email_msg      = implode("<br>",$msg_arr);

    // TODO , CREATE EXTERNAL MODULE IN REDCAP TO ACT AS API TO RELAY EMAILS
	// // send email
	// mail("irvins@stanford.edu", $subject, $msg); 
    // emailNotification($from_name, $reply_email, $email, $email_subject, $email_msg);                                                              
}else{
    echo "why are you here?";
}

// if get head = succesful
// {"_id":"IRV_7B6D2189-3F1E-4290-91DB-E7DCBD0E42A0_4_1528047306210_photo_0.jpg","_rev":"15-2b55e7600c989813497f81a114eb23a7
// ","upload_try":15,"_attachments":{"photo_0.jpg":{"content_type":"image/jpeg","revpos":1,"digest":"md5-cK9299gcwakNPwrbYcu
// NfA==","length":1525030,"stub":true}}}

// if get head = fail
// {"error":"not_found","reason":"missing"}