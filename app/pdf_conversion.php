<?php 
require_once "common.php";
require_once "vendor/tcpdf/tcpdf.php";


if(isset($_GET["_id"]) && isset($_GET["_numPhotos"]) && isset($_GET["_rotationString"])){
	$pdf = new TCPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);
	setup($pdf, $_GET["_id"]);

	// INCLUDE WALK AT THE TOP MAP
	generateWalkMap($pdf, $_GET["_id"]);

	for($i = 0 ; $i < $_GET["_numPhotos"]; $i++){ //not iterating right now. adding pages to wrong obj
		$rotation = str_split($_GET["_rotationString"])[$i]; //get current rotation of picture
		generatePhotoPage($pdf,$_GET["_id"], $i, $rotation);
	}
	$pdf->Output('example_001.pdf', 'I');
}

function generatePhotoPage($pdf, $id, $pic, $rotation){
	/* Parameters: 
		pdf = PDF object 
		id = full walk ID 
		pic = number from [0,x) where x is the picture # on the portal 
	*/
	$_id 		= $id;
	$_file		= "photo_".$pic.".jpg";

    $url        = cfg::$couch_url . "/" . cfg::$couch_users_db . "/" . $_id;
    $response   = $ds->doCurl($url);

	$doc 		= json_decode(stripslashes($response),1); //wtf this breaking certain ones? 

	$_rev 		= $doc["_rev"];
	$proj_idx 	= $doc["project_id"];

	if(!isset($_SESSION["DT"]["project_list"][$proj_idx]["tags"])){
		$_SESSION["DT"]["project_list"][$proj_idx]["tags"] = array();
	}

	// filter out low accuracy
    $forjsongeo = array_filter($doc["geotags"],function($tag){
        return $tag["accuracy"] <= 50;
    });

    if(empty($forjsongeo)){
        $forjsongeo = $doc["geotags"]; 
    }

    $walk_geo 	= json_encode($forjsongeo);

	$photos 	= $doc["photos"];
	$device 	= $doc["device"]["platform"];
	$old 		= isset($doc["_attachments"]) ? "&_old=1" : "";
	$temp_1 	= explode("_",$_file);
	$temp_2 	= explode(".",$temp_1[1]);
	$photo_i 	= $temp_2[0];
	$prevnext 	= [];
	foreach($photos as $i => $photo){
		if($i !== intval($photo_i)){
			continue;
		}

		if(!$old && !isset($photo["audios"])){
			$old = "&_old=2";
		}

		//PREV NEXT
		if(isset($photos[$i-1])){
			$prevnext[0] = "photo.php?_id=" . $doc["_id"] . "&_file=photo_" . ($i - 1) . ".jpg";
		}
		if(isset($photos[$i+1])){
			$prevnext[1] = "photo.php?_id=" . $doc["_id"] . "&_file=photo_" . ($i + 1) . ".jpg";
		}

		$hasaudio 	= !empty($photo["audio"]) ? "has" : "";
		$hasrotate 	= isset($photo["rotate"]) ? $photo["rotate"] : 0;
		$goodbad = "";
		if($photo["goodbad"] == 2){
			$goodbad = "/img/icon_smile.png";	
			// $goodbad  .= "<span class='goodbad good'></span>";
		}elseif($photo["goodbad"] == 1){
			$goodbad = "/img/icon_frown.png";
		}else{
			$goodbad = "/img/icon_none.png";
		}

		$long 		= isset($photo["geotag"]["lng"]) ? $photo["geotag"]["lng"] : $photo["geotag"]["longitude"];
		$lat 		= isset($photo["geotag"]["lat"]) ? $photo["geotag"]["lat"] : $photo["geotag"]["latitude"];



		$timestamp  = $photo["geotag"]["timestamp"];
		if($lat != 0 | $long != 0){
            $time = time();
            $url = "https://maps.googleapis.com/maps/api/timezone/json?location=$lat,$long&timestamp=$time&key=AIzaSyDCH4l8Q6dVpYgCUyO_LROnCuSE1W9cwak";
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
            $responseJson = curl_exec($ch);
            curl_close($ch);
             
            $response = json_decode($responseJson);
            date_default_timezone_set($response->timeZoneId); 
        }

    	$photo_name = $old ? "photo_" . $i . ".jpg" : $photo["name"];
		$ph_id 		= $old ? $_id : $_id . "_" . $photo_name;
		$photo_uri 	= "passthru.php?_id=".$ph_id."&_file=$photo_name" . $old;
		// detectFaces($ph_id,$old, $photo_name);

		////////////////GET MAIN PHOTO DEF/////////////////
		$id 	= isset($ph_id) ? $ph_id : NULL ;
		$file 	= isset($photo_name) ? $photo_name : NULL ;

		if (empty($id) || empty($file)) {
		    exit ("Invalid id or file");
		}

		// Do initial query to get metadata from couchdb
		
		if($old == "&_old=2"){
			$url = cfg::$couch_url . "/disc_attachments/$id";
		}else if($old == "&_old=1"){
			$url = cfg::$couch_url . "/".cfg::$couch_users_db."/" . $id;
		}else{
			$url = cfg::$couch_url . "/". cfg::$couch_attach_db."/" . $id;
		}
		

		$result = $ds->doCurl($url);
		$result = json_decode($result,true);

		// print_rr( $result);
		$htmlphoto = $ds->doCurl($url ."/" . $file); //the string representation htmlphoto is the WALK photo

		///////////////////////////// GET MAIN PHOTO END ///////////////////////////// 

		///////////////////////////// GET TRANSCRIPTIONS START /////////////////////////////		
		$attach_url = "#";
		$retTranscript = [];
		$photo_tags = isset($photo["tags"]) ? $photo["tags"] : array();
		if(isset($photo["audios"])){

			foreach($photo["audios"] as $filename){
				//WONT NEED THIS FOR IOS, BUT FOR NOW CANT TELL DIFF

				$aud_id			= $doc["_id"] . "_" . $filename;
                $attach_url 	= "passthru.php?_id=".$aud_id."&_file=$filename" . $old;
				//$audio_src 		= getConvertedAudio($attach_url);
				$confidence 	= appendConfidence($attach_url);
				$script 		= !empty($confidence) ? "This audio was transcribed using Google's API at ".round($confidence*100,2)."% confidence" : "";
				$download 		= cfg::$couch_url . "/".cfg::$couch_attach_db."/" . $aud_id . "/". $filename;
				//Works for archaic saving scheme as well as the new one : 
				if(isset($doc["transcriptions"][$filename]["text"])){
					$txns = str_replace('&#34;','"', $doc["transcriptions"][$filename]["text"]);
					$transcription = str_replace('&#34;','"', $doc["transcriptions"][$filename]["text"]);
				}else if(isset($doc["transcriptions"][$filename])){
					$txns = str_replace('&#34;','"', $doc["transcriptions"][$filename]["text"]);
					$transcription = str_replace('&#34;','"', $doc["transcriptions"][$filename]);
				}else{
					$transcription = "";
				}
				array_push($retTranscript, array("type" => "audio" , "content" => $transcription));
				
			}
		}else{
			if(!empty($photo["audio"])){
				$ext   = $device == "iOS" ? "wav" : "amr";
				for($j = 1 ; $j <= $photo["audio"]; $j++ ){
					$filename = "audio_".$i."_".$j . "." .$ext;

					//WONT NEED THIS FOR IOS, BUT FOR NOW CANT TELL DIFF
	                $attach_url 	= "passthru.php?_id=".$doc["_id"]."&_file=$filename" . $old;
					//$audio_src 		= getConvertedAudio($attach_url);

					$download 		= cfg::$couch_url . "/".cfg::$couch_attach_db."/" . $doc["_id"] . "/". $filename;
					$transcription 	= isset($doc["transcriptions"][$filename]) ? $txns = str_replace('&#34;','"', $doc["transcriptions"][$audio_name]) : "";
				}
			}
		}

        $text_comment = !empty($photo["text_comment"]) ? $photo["text_comment"] : false;
        if($text_comment){
        	array_push($retTranscript, array("type" => "text" , "content" => $text_comment));
        }
		break;
	}
	///////////////////////////// GET TRANSCRIPTIONS END /////////////////////////////		

	///////////////////////////// FORM HTML BEGIN /////////////////////////////
	$htmlobj = [];
	$htmlobj['date'] = date("F j, Y", floor($doc["geotags"][0]["timestamp"]/1000));
	$htmlobj['time'] = date("g:i a", floor($timestamp/1000));

	///////////////////////////// FORM HTML END /////////////////////////////
	
	///////////////////////////// GET STATIC GOOGLE MAP /////////////////////////////
	$lat = isset($doc["photos"][$pic]["geotag"]["lat"]) ? $doc["photos"][$pic]["geotag"]["lat"] : 0;
	$lng = isset($doc["photos"][$pic]["geotag"]["lng"]) ? $doc["photos"][$pic]["geotag"]["lng"] : 0;
	$urlp = urlencode("|$lat,$lng");
	$parameters = "markers=$urlp";

	$imageResource = imagecreatefromstring($htmlphoto); //convert to resource before checking dimensions
	if(imagesx($imageResource) > imagesy($imageResource)){ //check picture orientation
		// print_rr(imagesx($imageResource));
		// print_rr(imagesy($imageResource));
		$landscape = True;
		$scale = imagesx($imageResource)/imagesy($imageResource);
	}else{
		$landscape = False;
		$scale = imagesy($imageResource)/imagesx($imageResource);
	}
	// print_rr($scale);
	$url = 'https://maps.googleapis.com/maps/api/staticmap?size=400x'.floor(533).'&zoom=16&'.$parameters."&key=".cfg::$gvoice_key;

	imagedestroy($imageResource);
	$gmapsPhoto = $ds->doCurl($url);
	
	// $photo = imagecreatefromstring($gmapsPhoto);
	// print_rr($goodbad);
	generatePage($pdf, $htmlobj, $htmlphoto, $retTranscript, $gmapsPhoto, $landscape, $scale, $rotation, $goodbad);
	///////////////////////////// END STATIC GOOGLE MAP /////////////////////////////
}

function setup($pdf, $id){ //set page contents and function initially
	$alpha = explode("_",$id)[0];
	$numeric = substr($id,(strlen($id)-4),strlen($id));

	$pdf->SetHeaderData("", "", "WALK ID: ".$alpha.' - '.$numeric);

	// $pdf->setFooterData("", "", "COPYRIGHT STANFORD UNIVERSITY 2017");
	$pdf->SetTitle($id);
	$pdf->setHeaderFont(Array(PDF_FONT_NAME_MAIN, '', 8));
	// $pdf->setFooterData(array(0,64,0), array(0,64,128));

	// set header and footer fonts
	$pdf->setHeaderFont(Array(PDF_FONT_NAME_MAIN, '', 8));
	$pdf->setFooterFont(Array(PDF_FONT_NAME_DATA, '', PDF_FONT_SIZE_DATA));
	// set default monospaced font
	$pdf->SetDefaultMonospacedFont(PDF_FONT_MONOSPACED);

	// set margins
	$pdf->SetMargins(PDF_MARGIN_LEFT, PDF_MARGIN_TOP, PDF_MARGIN_RIGHT);
	$pdf->SetHeaderMargin(PDF_MARGIN_HEADER);
	$pdf->SetFooterMargin(PDF_MARGIN_FOOTER);

	// set auto page breaks
	$pdf->SetAutoPageBreak(TRUE, PDF_MARGIN_BOTTOM);

	// set image scale factor
	$pdf->setImageScale(PDF_IMAGE_SCALE_RATIO);

	// set some language-dependent strings (optional)
	if (@file_exists(dirname(__FILE__).'/lang/eng.php')) {
		require_once(dirname(__FILE__).'/lang/eng.php');
		$pdf->setLanguageArray($l);
	}
		$pdf->setFontSubsetting(true);
		$pdf->SetFont('dejavusans', '', 8, '', true);
		$pdf->setTextShadow(array('enabled'=>true, 'depth_w'=>0.2, 'depth_h'=>0.2, 'color'=>array(196,196,196), 'opacity'=>1, 'blend_mode'=>'Normal'));
}

function generatePage($pdf, $htmlobj, $htmlphoto, $retTranscript, $gmapsPhoto, $landscape, $scale, $rotation, $goodbad){
	/* arguments: SORRY for list will clean up later.
	pdf = export object
	htmlobj = includes date, time for picture information
	htmlphoto = walk photo from portal / one per page
	retTranscript = text transcription in array format for each photo
	photo = google maps photo of location
	landscape = boolean T/F to determine how to scale
	scale = float that determines scale factor
	rotation = int of 0-3 to determine which 90 degree offset to rotate
	goodbad = img path to the correct smile icon
 	*/
	// print_rr($rotation);
	$pdf->AddPage();
	$pdf->StartTransform();
	$pdf->Rotate(90,0,250);
	$pdf->writeHTMLCell(0,0,0,250, "<small>Generated using the Stanford Discovery Tool, © Stanford University 2018</small>",0,1,0, true, '',true);
	
	$pdf->StopTransform();
	$pdf->writeHTMLCell(0,0,20,9.5, $htmlobj['date'] . " " .$htmlobj['time'],0,1,0, true, '',true);
	if($scale > 1.4) {#scale = 1.77 in this case 
		$basePixels = 60;
	}else{
		$basePixels = 80;
	}

	//make sure the image is whole (broken images wont have a resource id)
	$resource_id 	= imagecreatefromstring($htmlphoto);
	$image_is_gd 	= get_resource_type($resource_id);

	if($landscape){ //Display Landscape
		$pdf->writeHTMLCell(0, 0, '', 140, "<h2>Why did you take this picture?</h2>", 0, 1, 0, true, '', true);
		if(isset($retTranscript[0]) && !empty($retTranscript[0])){
			$starting_v = 150; //arbitrary almost to sit under the photo;
			foreach($retTranscript as $k => $trans) {
				$type 		= $trans["type"];
				$content 	= $trans["content"];
                $typeicon 	= $type == "audio" ? "[<img src='./img/icon_mic'/> ".($k + 1)."]" : "[text]";
                
                $approx_lines   = ceil(strlen($content)/80);  //about 80 characters per line
                $approx_vert    = 5; //approx height per line
                $vert_offset    = $approx_lines * $approx_vert;

                $pdf->writeHTMLCell(0, 0, '', $starting_v, "<h3>$typeicon : '" . $content . "'</h3>", 0, 1, 0, true, '', true);
            	$starting_v = $starting_v + $vert_offset;
            }
		}else{
			$pdf->writeHTMLCell(0, 0, '', 150, "<h3>No Transcript Available</h3>", 0, 1, 0, true, '', true);
		}
		
		if($image_is_gd == "gd"){
			if($rotation == 0){
				$pdf->Image('@'.$htmlphoto,5, 20, $basePixels*$scale, $basePixels); //portrait
			}else{
				$pdf->StartTransform();
				
				if($rotation == 1){
					$pdf->Rotate(270,20,20);
					$pdf->Image('@'.$htmlphoto,20, -70, $basePixels*$scale, $basePixels); //portrait			
				}elseif($rotation == 2){
					$pdf->Rotate(180,20,20);
					$pdf->Image('@'.$htmlphoto,-70, -60, $basePixels*$scale, $basePixels); //portrait	
				}else{
					$pdf->Rotate(90,20,20);
					$pdf->Image('@'.$htmlphoto,-87, 15, $basePixels*$scale, $basePixels); //portrait	
				}
				$pdf->StopTransform();
			}
		}else{
			$pdf->writeHTMLCell(0, 0, '', 150, "<h3>No Transcript Available</h3>", 0, 1, 0, true, '', true);
		}
	}else{ //Display Portrait
		$pdf->writeHTMLCell(0, 0, '', 140, "<h2>Why did you take this picture?</h2>", 0, 1, 0, true, '', true);
		if(isset($retTranscript[0]) && !empty($retTranscript[0])){
			$starting_v = 150; //arbitrary almost to sit under the photo;
			foreach($retTranscript as $k => $trans){
                $type 		= $trans["type"];
                $content 	= $trans["content"];
                $typeicon 	= $type == "audio" ? "[audio ".($k + 1)."]" : "[text]";

                $approx_lines   = ceil(strlen($content)/80);  //about 80 characters per line
                $approx_vert    = 5; //approx height per line
                $vert_offset    = $approx_lines * $approx_vert;

                $pdf->writeHTMLCell(0, 0, '', $starting_v, "<h3>$typeicon : '".$content."'</h3>", 0, 1, 0, true, '', true);
                $starting_v = $starting_v + $vert_offset;
			}
		}else{
			$pdf->writeHTMLCell(0, 0, '', 50, "<i>Image Not Available</i>", 0, 1, 0, true, '', true);
		}
		
		if($image_is_gd == "gd"){
			if($rotation == 0){
				$pdf->Image('@'.$htmlphoto,16, 20, $basePixels, $basePixels*$scale); //portrait
			}else{
				$pdf->StartTransform();
				
				if($rotation == 1){
					$pdf->Rotate(270,20,20);
					$pdf->Image('@'.$htmlphoto,20, -70, $basePixels, $basePixels*$scale); //portrait			
				}elseif($rotation == 2){
					$pdf->Rotate(180,20,20);
					$pdf->Image('@'.$htmlphoto,-55, -87, $basePixels, $basePixels*$scale); //portrait	
				}else{
					$pdf->Rotate(90,20,20);
					$pdf->Image('@'.$htmlphoto,-60, 5, $basePixels, $basePixels*$scale); //portrait	
				}
				$pdf->StopTransform();
			}
		}else{
			$pdf->writeHTMLCell(0, 0, '', 50, "<i>Image Not Available</i>", 0, 1, 0, true, '', true);
		}
	}


	$pdf->Image('@'.$gmapsPhoto,115,20,80,106);
	$pdf->writeHTMLCell(0, 0, 146, 128, "Good or Bad for the Community?", 0, 1, 0, true, '', true);

	if(strpos($goodbad,"icon_none")){
		// this means both
		$pdf->Image('./img/icon_smile.png',173,133,10,10);
		$goodbad = "img/icon_frown.png";
	}
	$pdf->Image('./'.$goodbad,185,133,10,10);
}

function generateWalkMap($pdf, $_id){
	$url        = cfg::$couch_url . "/" . cfg::$couch_users_db . "/" . $_id;
    $response   = $ds->doCurl($url);
	$doc 		= json_decode(stripslashes($response),1); //wtf this breaking certain ones? 

	$pdf->AddPage();
	$pdf->StartTransform();
	$pdf->Rotate(90,0,250);
	$pdf->writeHTMLCell(0,0,0,250, "<small>Generated using the Stanford Discovery Tool, © Stanford University 2018</small>",0,1,0, true, '',true);
	$pdf->StopTransform();

	$walk_date 	= date("F j, Y", floor($doc["geotags"][0]["timestamp"]/1000));
	$walk_time 	= date("g:ia", floor($doc["geotags"][0]["timestamp"]/1000)) . " - " . date("g:ia", floor($doc["geotags"][count($doc["geotags"]) - 1]["timestamp"]/1000));
	$pdf->writeHTMLCell(0,0,20,9.5, $walk_date . " " .$walk_time,0,1,0, true, '',true);

	$geopoints = array();
	foreach($doc["geotags"] as $geotag){
		$geopoints[] = $geotag["lat"].",".$geotag["lng"];
	}
	$markers 	= implode("|",$geopoints);
	$urlp 		= urlencode("icon:https://ourvoice-projects.med.stanford.edu/img/icon_small_blue_dot.png"."|".$markers);
	$parameters = "markers=$urlp";

	$url 		= 'https://maps.googleapis.com/maps/api/staticmap?size=680x'.floor(533).'&zoom=16&'.$parameters."&key=".cfg::$gvoice_key;
	$gmapsPhoto = $ds->doCurl($url);
	$pdf->Image('@'.$gmapsPhoto,15,20,180,106);
}
?>
