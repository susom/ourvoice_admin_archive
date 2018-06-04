<?php     
require_once "common.php";
require_once "inc/class.mail.php";

// ini_set('display_errors', 1);
// ini_set('display_startup_errors', 1);
// error_reporting(E_ALL);

// GET WALK ID , FROM THE PING
$uploaded_walk_id = isset($_POST["uploaded_walk_id"]) ? $_POST["uploaded_walk_id"] : null;                         
// $proccesed_thumb_ids    = isset($_POST["proccesed_thumb_ids"]) ?  $_POST["proccesed_thumb_ids"] : null;                  
                                                                                                                   
if(!empty($uploaded_walk_id)){                                                                                           
    $post   = json_decode($_POST,1);                                                                                 
    $_id    = $uploaded_walk_id;                                                                                     
    $doc    = json_decode($_POST["doc"],1);    
    
    // GET PROJECT EMAIL 
    $email 	= isset($_POST["project_email"]) ? $_POST["project_email"] : false;                                                       
    $photos = $doc["photos"];

    // SLEEP GIVE IT TIME TO UPLOAD ATTACHMENTS 
    // 11 seconds (only?) same amount of time as it takes to get to 83% on phone
    sleep(11); 

    // CHECK IF ATTACHMENT IDS ARE ALL EXISTING WITHIN disc_attachments AFTER SLEEP PERIOD
    $check_attach_urls 	= array();
    $couch_url = "http://".cfg::$couch_user.":".cfg::$couch_pw."@couchdb:5984";
    foreach($photos as $i => $photo){
		$ph_id 			= $_id . "_" . $photo["name"];
		$photo_attach 	= $couch_url . "/".$couch_attach_db."/" . $ph_id ;
		$check_attach_urls[$photo_attach] = get_head($photo_attach);
		if(isset($photo["audios"])){
			foreach($photo["audios"] as $filename){
				$aud_id			= $_id . "_" . $filename;
				$audio_attach 	= $couch_url . "/".$couch_attach_db."/" . $aud_id ;
				$check_attach_urls[$audio_attach] = get_head($audio_attach);
			}
		}
	}
    $meta = array("uploaded_walk_id" 	=> $_id  
    			 ,"email" 				=> $email                                                                       
                 ,"attachments" 		=> $check_attach_urls
             );   

    echo json_encode(array("uploaded_walk" => $meta)); 

    // EITHER YES OR NO ON PARTIAL UPLOADS
    $failed_uploads = array();
    foreach($check_attach_urls as $attach_id => $head_result){
    	if(array_key_exists("error", $head_result)){
    		 $failed_uploads[] = $attach_id;
    	}
    }

    // TODO , CREATE EXTERNAL MODULE IN REDCAP TO ACT AS API TO RELAY EMAILS
    
    // $temp           = explode("_",$_id);
    // $project_id     = $temp[0];
    // $from_name      = "Stanford Our Voice";
    // $reply_email    = "irvins@stanford.edu";
    // EMAIL TO PROJECT ADMIN EMAIL, CC ANN,IRVIN, JORDAN 
	// $msg 		= "Hi Admin,\r\n\r\n";
	// $msg  	   .= count($failed_uploads) ? "Please check the portal for the following attachments; " . implode(", ", $failed_uploads) . ".  If you find them missing on the portal, you can try to upload again from the app.  Please remember to be on a wifi connection and leave the app open through out the upload process.\r\n" : "\r\n";
	// $subject 	= count($failed_uploads) ? "Notification: [$project_id] New walk uploaded, possibly missing attachments" : "Notification: [$project_id] New walk uploaded!";
	// // send email
	// mail("irvins@stanford.edu", $subject, $msg);                                                              
}else{
    phpinfo();
    // mail("irvins@stanford.edu", "TEST", "TESTING TESTING");    

    // $from_name      = "Stanford Our Voice";
    // $reply_email    = "irvins@stanford.edu";
    // $email          = "irvins@stanford.edu";
    // $email_subject  = "testing testing";
    // $email_msg      = "msg msg";
    // emailNotification($from_name, $reply_email, $email, $email_subject, $email_msg);
}

// if get head = succesful
// {"_id":"IRV_7B6D2189-3F1E-4290-91DB-E7DCBD0E42A0_4_1528047306210_photo_0.jpg","_rev":"15-2b55e7600c989813497f81a114eb23a7
// ","upload_try":15,"_attachments":{"photo_0.jpg":{"content_type":"image/jpeg","revpos":1,"digest":"md5-cK9299gcwakNPwrbYcu
// NfA==","length":1525030,"stub":true}}}

// if get head = fail
// {"error":"not_found","reason":"missing"}