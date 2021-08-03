<?php 
require_once "common.php";
require_once "vendor/tcpdf/tcpdf.php";

$pcode 			= $_GET["pcode"] ?? null;
$active_pid 	= $_GET["pid"] ?? null;

if(!empty($pcode) && !empty($active_pid)){
	$project_tags 	= $_SESSION["DT"]["project_list"][$active_pid]["tags"] ?? array();
	
	// THESE FILTERS COME IN MIXED WITH MOOD AND TAG
	$filters 		= $_GET["filters"] ?? "[]";
	$pfilters 		= json_decode($filters,1);

	$pfilters 		= empty($pfilters) ? $project_tags : $pfilters;

	$data_geos 		= $ds->getFilteredDataGeos($pcode, $pfilters);
	$photo_geos 	= $data_geos["photo_geos"];
	$photos 		= $data_geos["code_block"];

	// SET UP NEW PDF Obj
	$pdf = new TCPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);
	pdf_setup($pdf, $pcode);


	// generateWalkMap($pdf, $photo_geos);

	// SORT THE PHOTOS INTO GROUPINGS BY TAG
	$groupings = array();
	foreach($pfilters as $filter_tag){
		// MOOD IS ALREADY FILTERED OUT 
		if($filter_tag == "good" || $filter_tag == "bad" || $filter_tag == "neutral"){
			continue;
		}

		// GENERATE TAG TITLE PAGE
		generateTagPage($pdf,$pcode,$filter_tag);
		
		foreach($photos as $photo){
			if(empty($photo["tags"])){
				continue;
			}elseif(in_array($filter_tag,$photo["tags"])){
				generatePhotoPage($pdf,$photo, $active_pid, $filter_tag);
			}
		}
	}

	$pdf->Output($pcode . '_all_data.pdf', 'I');
}

define ('K_PATH_IMAGES', './img/');

function pdf_setup($pdf, $header){ //set page contents and function initially
	$pdf->SetHeaderData("logo.png", "", "Project : $header");
	$pdf->SetTitle($header);

	// set header and footer fonts
	$pdf->setHeaderFont(Array(PDF_FONT_NAME_MAIN, '', 12));
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
	// if (@file_exists(dirname(__FILE__).'/lang/eng.php')) {
	// 	require_once(dirname(__FILE__).'/lang/eng.php');
	// 	$pdf->setLanguageArray($l);
	// }
	
	$pdf->setFontSubsetting(true);
	$pdf->SetFont('dejavusans', '', 8, '', true);
	$pdf->SetTextColor(20,20,20);
	$pdf->setTextShadow(array('enabled'=>true, 'depth_w'=>0.2, 'depth_h'=>0.2, 'color'=>array(196,196,196), 'opacity'=>1, 'blend_mode'=>'Normal'));
}

function generateWalkMap($pdf, $photo_geos){
	$pdf->AddPage();
	$pdf->StartTransform();
	$pdf->Rotate(90,0,250);
	$pdf->writeHTMLCell(0,0,0,250, "<small>Generated using the Stanford Discovery Tool, © Stanford University 2018</small>",0,1,0, true, '',true);
	$pdf->StopTransform();

	$geopoints = array();
	foreach($photo_geos as $geotag){
		$lat = array_key_exists("lat",$geotag) ? $geotag["lat"] : $geotag["latitude"];
		$lng = array_key_exists("lng",$geotag) ? $geotag["lng"] : $geotag["longitude"];
		$geopoints[] = $lat.",".$lng;
	}
	$markers 		= implode("|",$geopoints);
	$marker_img_url = "https://ourvoice-projects.med.stanford.edu/img/marker_gray.png"; //must be live url
	$urlp 		= urlencode("icon:$marker_img_url"."|".$markers);
	$parameters = "markers=$urlp";

	$url 		= 'https://maps.googleapis.com/maps/api/staticmap?size=680x'.floor(533).'&zoom=16&'.$parameters."&key=".cfg::$gvoice_key;
	$gmapsPhoto = $ds->doCurl($url);
	$pdf->Image('@'.$gmapsPhoto,15,20,180,106);
}

function generateTagPage($pdf, $pcode, $tag){
	$pdf->AddPage();
	$pdf->writeHTMLCell(0, 0, '', 140, "<h1>Tag : $tag</h1>", 0, 1, 0, true, '', true);
}

function generatePhotoPage($pdf, $photo, $active_pid, $highlight_tag=null){
	/* Parameters: 
		pdf = PDF object 
		id = full walk ID 
		pic = number from [0,x) where x is the picture # on the portal 
	*/

	$_id 		= $photo["doc_id"];
	$_file		= "photo_".$photo["n"].".jpg";

	$proj_idx 	= $active_pid;
    $walk_geo 	= json_encode(array( array("lat" => $photo["lat"], "lng" => $photo["long"]) ) );
	$old 		= $photo["old"];

	$goodbad = "";
	if($photo["goodbad"] == 2){
		$goodbad = "/img/icon_smile.png";	
	}elseif($photo["goodbad"] == 1){
		$goodbad = "/img/icon_frown.png";
	}else{
		// 3 = both wasnt it?
		$goodbad = "/img/icon_none.png";
	}

	$lng 		= $photo["long"];
	$lat 		= $photo["lat"];
	$rotation 	= $photo["rotate"];

	$photo_name = "photo_" . $photo["n"] . ".jpg";
	$ph_id 		= $old ? $_id : $_id . "_" . $photo_name;
	$photo_uri 	= "passthru.php?_id=".$ph_id."&_file=$photo_name" . $old;

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
	
	$tags 		= !empty($photo["tags"]) ? $photo["tags"] : null;
	$result 	= $ds->doCurl($url);
	$result 	= json_decode($result,true);
	$htmlphoto 	= $ds->doCurl($url ."/" . $file); //the string representation htmlphoto is the WALK photo
	///////////////////////////// GET MAIN PHOTO END ///////////////////////////// 

	///////////////////////////// GET TRANSCRIPTIONS START /////////////////////////////		
	$retTranscript 	= array();
	$photo_tags 	= !empty($photo["tags"]) ? $photo["tags"] : array();
	if(!empty($photo["transcriptions"])){
		foreach($photo["transcriptions"] as $txn){
			$txns = str_replace('&#34;','"', $txn["text"]);
			$txns = str_replace("rnrn","<br><br>", $txns);
			array_push($retTranscript, array("type" => "audio" , "content" => $txns));
		}
	}
    if(!empty($photo["text_comment"])){
    	$photo_comment = str_replace("rnrn","<br><br>",$photo["text_comment"]);
    	array_push($retTranscript, array("type" => "text" , "content" => $photo_comment));
    }
	///////////////////////////// GET TRANSCRIPTIONS END /////////////////////////////		

	///////////////////////////// FORM HTML BEGIN /////////////////////////////
	$htmlobj = [];
	$htmlobj['date'] = date("F j, Y", floor($photo["actual_ts"]/1000));
	$htmlobj['time'] = date("g:i a", floor($photo["actual_ts"]/1000));

	///////////////////////////// FORM HTML END /////////////////////////////
	
	///////////////////////////// GET STATIC GOOGLE MAP /////////////////////////////
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

	$url = 'https://maps.googleapis.com/maps/api/staticmap?size=400x'.floor(533).'&zoom=16&'.$parameters."&key=".cfg::$gvoice_key;
	imagedestroy($imageResource);
	$gmapsPhoto = $ds->doCurl($url);

	generatePage($pdf, $htmlobj, $htmlphoto, $retTranscript, $gmapsPhoto, $landscape, $scale, $rotation, $goodbad, $tags, $highlight_tag);
	///////////////////////////// END STATIC GOOGLE MAP /////////////////////////////
}

function generatePage($pdf, $htmlobj, $htmlphoto, $retTranscript, $gmapsPhoto, $landscape, $scale, $rotation, $goodbad, $tags, $highlight_tag){
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

	$starting_v = 150; //arbitrary almost to sit under the photo;
	if($landscape){ //Display Landscape
		$pdf->writeHTMLCell(0, 0, '', 140, "<h2>Why did you take this picture?</h2>", 0, 1, 0, true, '', true);
		if(isset($retTranscript[0]) && !empty($retTranscript[0])){
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
			$pdf->writeHTMLCell(0, 0, '', $starting_v, "<i>No Transcriptions</i>", 0, 1, 0, true, '', true);
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

	// writeHTMLCell($w, $h, $x, $y, $html='', $border=0, $ln=0, $fill=0, $reseth=true, $align='', $autopadding=true)
	
	$starting_v += 10;
	$pdf->writeHTMLCell(0, 0, '',$starting_v , "<h2>Tags:</h2>");
	$approx_hor = 32; //32 width units per 10 characters "w's" in <h3>
	$starting_v += 10;
	$starting_w = 15;
	if(!empty($tags)){
		foreach($tags as $tag){
			$taglen 	= strlen($tag);
			$tagwid 	= round(($taglen/10) * $approx_hor);
			$tag_html 	= "<h3><i>$tag</i></h3>";
			if($tag == $highlight_tag){
				$pdf->SetTextColor(255,0,0);
			}
			$pdf->writeHTMLCell($tagwid, 0, $starting_w, $starting_v , $tag_html,1);
			$starting_v += 10;

			// reset text color;
			$pdf->SetTextColor(20,20,20);
		}
	}else{
		$pdf->writeHTMLCell(0, 0, $starting_w, $starting_v, "<i>No Tags Yet</i>");
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
?>





