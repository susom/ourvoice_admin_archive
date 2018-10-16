<?php 
require_once "common.php";
require_once "vendor/tcpdf/tcpdf.php";

$html = 
'<link href="css/dt_summary.css" rel="stylesheet" type="text/css"/>
<script src="https://code.jquery.com/jquery-3.2.1.min.js" integrity="sha256-hwg4gsxgFZhOsEEamdOYGBf13FyQuiTwlAQgxVSNgt4=" crossorigin="anonymous"></script>
<script type="text/javascript" src="https://maps.google.com/maps/api/js?key=AIzaSyCn-w3xVV38nZZcuRtrjrgy4MUAW35iBOo"></script>
<script type="text/javascript" src="./js/dt_summary.js"></script>
<body id="main" class="photo_detail">';

if(isset($_GET["_id"]) && isset($_GET["_file"])){
	$_id 		= trim($_GET["_id"]);
	$_file 		= $_GET["_file"];

    $url        = cfg::$couch_url . "/" . cfg::$couch_users_db . "/" . $_id;
    $response   = doCurl($url);

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
	// $rotate 	= isset($doc['photos'][0]['rotate']) ? $doc['photos'][0]['rotate'] : 0;
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
		$goodbad 	= "";
		if($photo["goodbad"] > 1){
			$goodbad  .= "<span class='goodbad good'></span>";
		}

		if($photo["goodbad"] == 1 || $photo["goodbad"] == 3){
			$goodbad  .= "<span class='goodbad bad'></span>";
		}

		if(!$photo["goodbad"]){
			$goodbad = "N/A";
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

		////PASSTHRU DEF/////
		// $id 	= isset($ph_id) ? $ph_id : NULL ;
		// $file 	= isset($photo_name) ? $photo_name : NULL ;

		// if (empty($id) || empty($file)) {
		//     exit ("Invalid id or file");
		// }

		// // Do initial query to get metadata from couchdb
		
		// if($old == "&_old=2"){
		// 	$url = cfg::$couch_url . "/disc_attachments/$id";
		// }else if($old == "&_old=1"){
		// 	$url = cfg::$couch_url . "/".cfg::$couch_users_db."/" . $id;
		// }else{
		// 	$url = cfg::$couch_url . "/". cfg::$couch_attach_db."/" . $id;
		// }
		

		// $result = doCurl($url);
		// $result = json_decode($result,true);
		// //print_rr( $result);
		// $result = doCurl($url ."/" . $file); //the string representation
		// // $photo = base64_decode($result);
		
		// $photo = imagecreatefromstring($result);
		// imagejpeg($photo, "AAA.jpg");
		////END PASSTHRU/////


		$attach_url = "#";
		$audio_attachments = "";
		
		$photo_tags = isset($photo["tags"]) ? $photo["tags"] : array();
		if(isset($photo["audios"])){
			foreach($photo["audios"] as $filename){
				//WONT NEED THIS FOR IOS, BUT FOR NOW CANT TELL DIFF

				$aud_id			= $doc["_id"] . "_" . $filename;
                $attach_url 	= "passthru.php?_id=".$aud_id."&_file=$filename" . $old;
				//$audio_src 		= getConvertedAudio($attach_url);
				$confidence 	= appendConfidence($attach_url);
				$script 		= !empty($confidence) ? "This audio was transcribed using Google's API at ".round($confidence*100,2)."% confidence" : "";
				$download 		= cfg::$couch_url . "/".$couch_attach_db."/" . $aud_id . "/". $filename;
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
				$audio_attachments .=   "<div class='audio_clip'>
											
											<a class='download' href='$download' title='right click and save as link to download'>&#8676;</a> 
											<div class='forprint'>$transcription</div>
											<textarea name='transcriptions[$filename]' placeholder='Click the icon and transcribe what you hear'>$transcription</textarea>
											<p id = 'confidence_exerpt'>$script</p>
										</div>";
			}
		}else{
			if(!empty($photo["audio"])){
				$ext   = $device == "iOS" ? "wav" : "amr";
				for($j = 1 ; $j <= $photo["audio"]; $j++ ){
					$filename = "audio_".$i."_".$j . "." .$ext;

					//WONT NEED THIS FOR IOS, BUT FOR NOW CANT TELL DIFF
	                $attach_url 	= "passthru.php?_id=".$doc["_id"]."&_file=$filename" . $old;
					//$audio_src 		= getConvertedAudio($attach_url);

					$download 		= cfg::$couch_url . "/".$couch_attach_db."/" . $doc["_id"] . "/". $filename;
					$transcription 	= isset($doc["transcriptions"][$filename]) ? $txns = str_replace('&#34;','"', $doc["transcriptions"][$audio_name]) : "";
					$audio_attachments .=   "<div class='audio_clip'>
										
											<a class='download' href='$download' title='right click and save as link to download'>&#8676;</a> 
											<div class='forprint'>$transcription</div>
											<textarea name='transcriptions[$filename]' placeholder='Click the icon and transcribe what you hear'>$transcription</textarea>
											<p id = 'confidence_exerpt'>$script</p>
										</div>";
				}
			}
		}
		break;
	}
	// rotate= '$doc['photos'][0]['rotate'])'

	$html .= "<input type='hidden' name='doc_id' value='".$doc["_id"]."'/>";
	$html .= "<div class='user_entry'>";
	$html .= "<hgroup>";
	$html .= "<h4>Photo Detail : 
	<b>".date("F j, Y", floor($doc["geotags"][0]["timestamp"]/1000))." <span class='time'>@".date("g:i a", floor($timestamp/1000))."</span></b> 
	<i>".$doc["_id"]."</i></h4>";
	$html .= "</hgroup>";

	$html .= "<div class='photobox'>";
	$html .= 	"<section class='photo_previews'>";
	$html .= 		"<div>";	
	$html .= "
		<figure>
		<a class='preview rotate' rev='$hasrotate' data-photo_i=$photo_i data-doc_id='".$doc["_id"]."' rel='google_map_0' data-long='$long' data-lat='$lat'>
				<canvas class='covering_canvas'></canvas>
				<img id = 'main_photo' src='$photo_uri'/><span></span>

		</a>

		</figure>";
		
		$geotags   = array();
		$geotags[] = array("lat" => $lat, "lng" => $long);
		$json_geo  = json_encode($geotags);
		$gmaps[]   = "drawGMap($json_geo, 0, 16, $walk_geo);\n";

	$html .= 		"</div>";
	$html .= 	"</section>";

	$html .= "<section class='side'>";

	$html .= "<aside>
			<b id = 'lat' value = '$lat'>lat: $lat</b>
			<b id = 'long' value = '$long'>long: $long</b>
			<div id ='cover' class = 'gmap location_alert'></div>
			<div id='google_map_0' class='gmap'></div>
		</aside>";
	$html .= "<aside class='forcommunity'>
			<h4>Good or bad for the community?</h4>
			$goodbad
		</aside>";

	$html .= "<aside>
			<h4>Why did you take this picture?</h4>
			$audio_attachments
		</aside>";

	$html .= "<i class='print_only'>Data gathered using the Stanford Healthy Neighborhood Discovery Tool, © Stanford University 2017</i>";
	$html .= "</section>";
	$html .= "</div>";
	$html .= "</div>";
}
// echo headers_sent($filename, $line) ? $filename . " ". $line: "no";

echo $html;



/*


$pdf = new TCPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);

// set document information
// $pdf->SetCreator(PDF_CREATOR);
// $pdf->SetAuthor('Nicola Asuni');
// $pdf->SetTitle('TCPDF Example 001');
// $pdf->SetSubject('TCPDF Tutorial');
// $pdf->SetKeywords('TCPDF, PDF, example, test, guide');

// set default header data
// $pdf->SetHeaderData(PDF_HEADER_LOGO, PDF_HEADER_LOGO_WIDTH, PDF_HEADER_TITLE.' 001', PDF_HEADER_STRING, array(0,64,255), array(0,64,128));
$pdf->setFooterData(array(0,64,0), array(0,64,128));

// set header and footer fonts
$pdf->setHeaderFont(Array(PDF_FONT_NAME_MAIN, '', PDF_FONT_SIZE_MAIN));
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

// ---------------------------------------------------------

// set default font subsetting mode
$pdf->setFontSubsetting(true);

// Set font
// dejavusans is a UTF-8 Unicode font, if you only need to
// print standard ASCII chars, you can use core fonts like
// helvetica or times to reduce file size.
$pdf->SetFont('dejavusans', '', 11, '', true);

// Add a page
// This method has several options, check the source code documentation for more information.
$pdf->AddPage();

// set text shadow effect
$pdf->setTextShadow(array('enabled'=>true, 'depth_w'=>0.2, 'depth_h'=>0.2, 'color'=>array(196,196,196), 'opacity'=>1, 'blend_mode'=>'Normal'));

// Set some content to print
// $html = <<<EOD
// <h1>Welcome to <a href="http://www.tcpdf.org" style="text-decoration:none;background-color:#CC0000;color:black;">&nbsp;<span style="color:black;">TC</span><span style="color:white;">PDF</span>&nbsp;</a>!</h1>
// <i>This is the first example of TCPDF library.</i>
// <p>This text is printed using the <i>writeHTMLCell()</i> method but you can also use: <i>Multicell(), writeHTML(), Write(), Cell() and Text()</i>.</p>
// <p>Please check the source code documentation and other examples for further information.</p>
// <p style="color:#CC0000;">TO IMPROVE AND EXPAND TCPDF I NEED YOUR SUPPORT, PLEASE <a href="http://sourceforge.net/donate/index.php?group_id=128076">MAKE A DONATION!</a></p>
// EOD;
//  echo $html;

// Print text using writeHTMLCell()
$pdf->writeHTMLCell(0, 0, '', '', $html, 0, 1, 0, true, '', true);
$pdf->Image('AAA.jpg',15, 140, 100, 100);
//// Image($file, $x='', $y='', $w=0, $h=0, $type='', $link='', $align='', $resize=false, $dpi=300, $palign='', $ismask=false, $imgmask=false, $border=0, $fitbox=false, $hidden=false, $fitonpage=false)

// ---------------------------------------------------------

// Close and output PDF document
// This method has several options, check the source code documentation for more information.
$pdf->Output('example_001.pdf', 'I');

//============================================================+
// END OF FILE
//============================================================+
*/



function appendConfidence($attach_url){
	$split 			= explode("=",$attach_url);
	$filename 		= $split[count($split) -1];
	$full_proj_code = explode("_audio",$split[1]);
	
	$url            = cfg::$couch_url . "/" . cfg::$couch_users_db . "/" . $full_proj_code[0];
    $response       = doCurl($url);
	$storage 		= json_decode($response,1);
	if(isset($storage["transcriptions"][$filename]["confidence"]))
		return $storage["transcriptions"][$filename]["confidence"];
	else
		return "";
}

?>
<script>
$(document).ready(function(){
	<?php
		echo implode($gmaps);
	?>
});
</script>
