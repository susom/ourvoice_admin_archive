<?php 
require_once "common.php";
require_once "vendor/tcpdf/tcpdf.php";
// if(isset($_POST['data'])){
// 		print_rr($_POST['data']);
// 		$full_path = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") . "://$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]";
// 		// $full_path = 'https://www.google.com';
// 		$result = str_replace('takeScreenshot.php','pdf_conversion.php',$full_path);
// 		$result .= '?_id='.$_POST['data']['walkID'].'&_file=photo_0.jpg';
// 		print($result);
// 		$nice = file_get_contents($result);
// 		print($nice);
// 		exit();
// 		generatePDF('yes.tjp');
// 	$_id 		= trim($_GET["_id"]);
// 	$_file 		= $_GET["_file"];
// 	//get URL and navigate to photo page
// 	//Take snapshot of image to use in PDF
// 	$result = urlencode($result);
// 	$googlePagespeedData = file_get_contents("https://www.googleapis.com/pagespeedonline/v4/runPagespeed?url=$result&screenshot=true");
// 	$googlePagespeedData = json_decode($googlePagespeedData, true);
// 	$screenshot = $googlePagespeedData['screenshot']['data'];
// 	$screenshot = str_replace(array('_','-'),array('/','+'),$screenshot);
// 	// print_rr($screenshot);
// 	// file_put_contents('vvv.txt', $screenshot);
// 	$resource = imagecreatefromstring($screenshot);
// 	$filename = "test.jpg"; 
// 	// imagejpeg($resource,$filename);
// 	generatePDF($filename);
// 	echo "<img src=\"data:image/jpeg;base64,".$screenshot."\" />";
generateHtml();


function generateHtml(){
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

						$download 		= cfg::$couch_url . "/".cfg::$couch_attach_db."/" . $doc["_id"] . "/". $filename;
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


}


// }

function generatePDF($filename){
	$pdf = new TCPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);
/*
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
*/
	// Print text using writeHTMLCell()
	/*
	$test = '/9j/4AAQSkZJRgABAQAAAQABAAD/2wBDAAYEBQYFBAYGBQYHBwYIChAKCgkJChQODwwQFxQYGBcUFhYaHSUfGhsjHBYWICwgIyYnKSopGR8tMC0oMCUoKSj/2wBDAQcHBwoIChMKChMoGhYaKCgoKCgoKCgoKCgoKCgoKCgoKCgoKCgoKCgoKCgoKCgoKCgoKCgoKCgoKCgoKCgoKCj/wAARCACzAUADASIAAhEBAxEB/8QAHAABAAIDAQEBAAAAAAAAAAAAAAQFAQMGAgcI/8QASxAAAQMCAwILBAQLBgYDAAAAAQACAwQRBRIhBjETFCIyQVFSYXGRkoGhsdEHFUJiFiMzNlRydJOys8EkNURF0uElNENTgvBzg6P/xAAZAQEBAQEBAQAAAAAAAAAAAAAAAgEEAwX/xAAlEQEBAAIBBAEDBQAAAAAAAAAAAQIRAwQSITFBIjJxUWGRobH/2gAMAwEAAhEDEQA/AP1SiIg1OqYmuILtR1AlY41F2j6SqLGpa+Che/CKaKprDI1rY5n5GWLwHOJHQBc+xUDsf2kYxodslM94BzmOrblO+2XS/Vv7+64d5xqLtH0lONRdo+krh6vGtooK2ojh2afU04faOVs7W8nS9wScx6rWF9O9S8LxPGKqmqZavA30cgH4mF8ocXcgnUjTnADo3oOt41F2j6SnGou0fSVxsmLY2xhDMBfK4A2cJMocb6G2pAI1tckblvjxPFZKeaR2CyQPY9gax8oeZGl3KIy7rDVB1fGou0fSU41F2j6SuWnrsWFfNHT4aJKZvMleS0v3d/efHutrDdjONNnoIXYNaSeNzpDdxax3KsLjdube/aQdrxqLtH0lONRdo+krlJcSxaOpkvhDjTMjLg9r7ueQwmwA3crTdqpeF1dbVOk47hz6MAAsLpQ/N17hog6DjUXaPpKcai7R9JUBEE/jUXaPpKcai7R9JUBEE/jUXaPpKcai7R9JUBEE/jUXaPpKcai7R9JUBEE/jUXaPpKcai7R9JUBEE/jUXaPpKcai7R9JUBEE/jUXaPpKcai7R9JVXnfwlhGcludf+i18LUW/wCW1/XH/vUguONRdo+kpxqLtH0lVJkm4SzYbs6y6y88LUXI4uLfrhBccai7R9JTjUXaPpKpzLUBxtT3F9OWNyyZJ9LQDovy/NBb8ai7R9JTjUXaPpKpjNU9FNfd9sLPC1Ga3Fxa175xvQXHGou0fSU41F2j6SqbhamxIpxfqzrJlqB/hwf/ADQXHGou0fSU41F2j6Sqp0klnZIsxB0ubXHWvHC1JNuLi19+dBccai7R9JWW1MTnAB2p6wQqUzVP6MD/AOYW+EvcGmRmR2bde/SguEREBERBzWPYpHg+HTVs0UkrGOALY7XN3W6Vy4+kWgP+X1o8cnzV9tk3NgNQPvt/iXBxwDTRTavHHboR9IVAf8DWebPmvTdv6A7qOq9hZ81RNp2HnNb7Qt7KKFxAdEz2tCbZZpdx7c0T3AcTqh4lvzR23NECf7HVebfmqsYdTEC8MfkvDsLpT/0R7CVm6aWh29oR/gqvzb81rd9IdA3/AAVZ5s+appcIpj9hw/8AIqFNgsBGhkHtTuNR0LvpJw5u+grfNnzWp30n4Y3/AC+u82fNcpPgcetpHj2AqvnwPpE3m1bLWV2r/pXwtm/DsQ82fNaH/TBhLN+GYj5x/NfP6nBH9ErD4gqpqcHnF7GM+1ax9Qf9NGDt34XiXnH81rP03YKP8qxPzj/1L4/UYXUN+y0+DlXTUM7b/iytH24/ThggH91Yn5x/6l5P06YGAScJxTTvj/1L4NJTyjfG7yUaaNwY67Xbj0IqSP2phtWyvw+lrI2uayoiZM1rt4DmggHv1UlVWyn5r4N+xQfy2q1RIiIgLTVyuhgfIyN0rmi4Y293dwsCb+xblgi9tSCNQQbWQRKSrM3DCSMMdGA4hr82++moBBBaQQqWi20wqupq6alFU4UcHDytdFk3AktBcQCRbw710MVPFC57o2BrnnM4gc43JufaSfatuWQ/YPmEHKVm2+HUcNK+emrwal72xtbEHGzXBuYkGwBJ0ubmx0Wio+kHDafOJ6LEWOZa7eDaTqC4EcrcQN/eOtdnll7DvUEyy9g+oIOLh+kLDZmTvZQ4rlhYXyXp9QAH3sL3P5MjTrC3Uu3uEVENRKIq9jKcB0hdTnQGTg7jr1103Arrsso+w71BLSn7LvUEHOYftjg1fiEFFTTTGpmuY2vgczMBvIJA0Wqr20w2lrW00kVUHOkDA4ta0G5jAdZzgbfjB0dB6l0joM0rZXQNMrQWtecpcAd4B3gaBey2Q72HzCDksQ28wygrH09RS4jmaXDMyDODlc8XABJPMuOsOHfbVJ9IOGMphO6jxPgy7JcQA66fe7x5+K7MNluAGOuepwXvgZ+wfUEHE0n0h4RUiMtp8QbwjxG3NBrcujGoBJA/GDU6cl3dfsiLEjqWS2VriMrr/rBYySdg+YQEHOb4j4plk7B8wguHDMLG40QWqIiAiIg5TakZsHnH32/xLj44xou02iF8LmH3x/EuXjjUX2vG+GkRcttgNx/opUTGDMCWAtF3C407+5emR/jWaDp3qBHHCzE8edK27XQRmRgGpaGOv5i4QvtMxGZlFROqZIZHxNy5uDaCQCQL+GoW18QDiCOmy5B+BbKxUr5ItnQ11PDDUlrBlLWuPJIOexItcju6V3Ega1/Lc1pLrC5tc9QWVKG6EHoWmSAd/mrOPg5C9scjHujOV4a4EtPUR0HxXmSHTcvOqiilgHeoE8PeVZ4rVR0T4xIxzuENgRbReaiK+5XMpfDLL7c7PCddVWVUJ1t8F0dRFa+iosaiBoKkZS4cG67WvyE6bg7o8ehUxRVUZF9yqahpudykYVSTRSSmemrIrg2dPWcO0jNcAco2NrarNWzeqjFJONFBnPIdr0FWVS3RVdQOS7wKWKfrbZf82cI/Y4f5bVZqr2X/ADZwj9jh/ltVotSIiICIiAs4u7EGYPM7BmRSV4IMbJdGu5QuCejk31WFK16CR4FBzGFYjth9b0lLimD0baR4LpamKW+Xfpa/RdoHXY7t46uq4YU0xpgDNlOS4uL20WvXtO9RQ3H2neooKp9RtAW2ZRUwOrbukF9w5W/rvopVBLi5qmMrKeAU5Zdz2u5Qd1WupQzdLneorOvad6ig9P8AyjvFeSiyxud9ujpQe4GfbPTu8Fslfkbfp6B1r3uCivdnffoG5BgDr1Ky3nt8UWEGmCSYzRhz5yC6UODqfKLA6a9Fug/a+HmT8oPYpGvad6io8n5UeIQWaIiAiIg5rHv7ul/XH8S51g00XR47/d8v6w/iXPMGnQpvtWL2zRzevXoUOJrnYviLJS0wuhjs2wBtyg65tu9uncpv2me34KBT5Bjteczs/AREgjk25W75LYyuRqIthxKabB8ApcVq47HJhtLn4PKRq6UWaLHvJ7rrstooGSwQvOJRYdURykwVMjI3hry1wNg/S9ibdPwXLzY3seWunw3aGkw6ocwgPpJQNHG5vHYtNyOoG/SuuxmnFTBCx2FQYm3hgXQzZLNFncoBwsSN1u/eFmTI5+ipMP2dEX1HVUcv4ng5AxzZJ53F2Z00rgeUSekj7R7guYwX6QsXq9t63BqyGkELDM2IticHXbq2+uui6pr8BndVRYNS0VNX09mVMcMDI3x3Nw15Zp0E7zuXy2Yth+miCBsjWyzzNeWEHVr4Tc+4ri5ssu7w+n0OPDljnOWfF1+VvSbY12PbXVOD4hT0sbacyGN0QcHHKRvuT0Kz2S2zq8W2qxXBsSpaenFG15jkYXXcGuAN7m2430XzT6SKir2Y20qsQwuRkMoibNGSwPABZldod+4ri8R2hnxaB1RNIX11S0SSPjaGDN3AbtLKcLl9zv5em4LNa1vDc/L7ds9tvNjNfiFLU0kMD6VwHIcTmbmLb6+A81b45wM+GVrGzMs6JzS5pzZbjuXyvZGrdDtjCyUH+20odr0kxh3xaVApcQGC7U4hRugEkUkpiMbnWa4EmwJ6AQ5e3HyX5cvVdFhbcuPx9Muv9fSoKVkTpHtrKyffG5tRKXBpB1tcDwuq+qdGXNAe0l4LmgHnDrHXvHmpdBRsppJntwekw4Oj58ErXl2u4gAeK5vDqalixGR8FXU1DxGGnhImhjQQDlaQBYgEEtHXqujb5DNULBVFVzXeBVzV7lTVfNd4FVLtr9abL/mzhH7HD/LarRVey/5s4R+xw/y2q0WpEREBERAUoKKpQQZXneUJWQLBAWUWEAnq3qREzI23T0rVA25znd0Lc9wY0koNc79Mo6d/gtQQXJJO871lAWFlG89vig8uIAuVHJzPBPWFBwmsqptpcWgmz8DGBlaTozq8xqpo3t8QvLh5Zy43KT5s/i6Xnh2XV/b+1qiIvVAiIg5rHv7ul/WH8S59hV/j/wDdsv64/iXNsduU32rFJPOb4/0VdEY/whrGho4U0sZccxOmZwGm4a31VgCLs8f6FRI3MOPTx2eJDTMcXXFrZnCw039O9JSxSRU21gnkqMuzgkfTtgMd5y0ZS49VvtWtu0VxtZQPxLCYoI8ObiJ4djjA+qdTssAbl7m3OUX1aBruVFE7GqbDhgdNgLQBGadtdxmMUwaRbhMt+EvbXLl39PSukxamrJ6OlbhrqfjEE8cg40X5HBt75smp8OtZUxwu1tJiOx30eYzUQ1dNTFkP9np6GHg4qS7gDkc4lzib73dWgC/OP4T4lNtk3GBX1M89PE8Q1M2XhGgRuDb20uCV90+mKixXDtg3txPEMJeSWwsMYkbKc87X3c6RzrsGW27qX56ZG6ZoJZLwz5yeMAExW6dA3XWx06OhbhhMpajLk7MlhWY9NjlfnxfFa6fmRxyT5OZflZ7dG+1r71a7DU9Adt54eEiqaKOmdI14aS0Gzegi+huFSikxSYPZmqiJakPkDaSQ6jdLo3dqbAa9y+0bO7AQYds9hWLRw1b8aroL1jnucTd1nc081efUccmPh0dN1Oe+3K+HDvc6t+kGGOguWUUdm5OTo0E/FwCqq2mkxPavFWSudE6K982pBFgF2GxeDYjQ4/jeK4ph9VTwGN7WOmZlvd19PYAp7cFo6361xecSQOqMpDzrlDG2JsN+uvsXhhhd6fR5uo7dyfppf0lE6ha4msqZ87c3By5AA46k8loVDGJop2Mmgw2EFry0UzjmvcZrAgC19/fZb8JmhkqZnQ4k6sdkN/7NwYbd1zY2F9ejoVdS4S3Dp3ysnzlzcrm8Exg9wv8AMm51XVp8uo1fM8V0UYI4MscXC2t+hV9U4Wc24zZSbdykV0jW4gCGAvEJOa53dVlXTPDnmQkA8Dcgd6qRsfr/AGW/NnCP2OH+W1WiqtlCHbL4O4bjRQEfu2q1WoEREBERAUq6iqSdSgDrXpYWUBYAzuDR7fBCbBb4WZW68470HsAAABR5XZ36c0fFbJ32FhvPuWkCwQZREQFrldlGm87l7cbC5WmM5pm33X3IPF3anM65+8sDnN8Qo9DV4jLjtfTVOGsioImNMM+cHMde7W9hp0e1SBvb4hBaoiICIiDmNojbC5j98fxLlo36Lp9pjbCZz98fxLjmSahRfasVkyQcnX7SjskZ+ETm8Fy3UoOfNvAeeTa3fvv7OleQ/m6/aCNjccRbOBFk4Hg725d8199ub3X39CRtcpPUYEyleKnb+uAAcHWxOJhbvuLBtxbqOui7OsbWTUETcKrIqaa7CJpYeFGTp5Nxckd4XBVrMUrsSmnwzBKjCIQ4l1VC2LjFXb7hcGAHdd4c6x3Bd/TSEwxktLCWi7T0ablmTESlwitficNXjOJU2IMiifG2H6vZEGlxac17uJ5u5fO9gNn8To/pb2hxetoqynoXcYEDpWBsTs0jQ0s16WtJ3BfVjNYKNNN3qJay4y2Vunqj2nD2qoq6jfqsVU+/VU9XUDW7h5pIpqxJ0c8T4pmtfG4Wc124rn8QfFTYfM2I8XjYxxBjaORpvA6+lTKupbryx5qmrpI6iCWKQkskaWm3UV6SMtV1C6NssgZir61xBcWOfGcpJuTZoFtV5qXXuvdoovyMTWaW5DA34KLO8m9gVbFLXlrasuLm5jC6wym+m/Xcq6cnIRpfgAdys6qBzpnSEi3B5ADfS+9QJ4TlcS4fk8mgRWn672P02SwP9gp/5bVbqq2UGXZfBmjcKKAf/m1WqIEREBERAUoKKo0seKNqZHQSQOjc7QSkmzb7gBu0377rdeLSebIs0VRP9dOq7U/Fmwtsczxo6+8eI16huVvYucGjefcsHuFuZ2Y7hu8VvcQ1pJ3BGgNaANwWiZ2Z2Ubhv8UHi5c4uO8rK0Vb5o4HOpomzSjmsL8oPtUPjmIEs/4boedeobpqPPpQWaKmnxKspmsdU0kEbXOy5jNpv8OpRn7QuGQCmjcHOIzNnFgBqXWtfdqgu5XXNh7Uh/LM8QoBxKjbSipNQw05fk4QXIzdSxFi9CSHx1UTsuu820v8igsocUoKisko4K6CSqjvnhbIC5ttDcdy0je3xC0fhDRlt+NUxB6QStrHB4Y5pu02IPWEFuiIgIiIOT2rNsFnP32/xLiGvGhXZ7Zuy4BUn77f4l89ZUDtDzUX2rFah9wLWJBB1W9sjutvvVSycdoea3MqB2h5opZhziOcB7F6DiPt+5VVVXx0tLLPISWRtLiBvsFFxPFnQUlHPTFpbNNG059+V39VN8ml899hq93moU8o11J8SV4lnAvyh5rl9rMWZS03BRVccdW6z2R57Pc0EAkdQuRqskYt6mRutwPaqmqlaL2A8lCrquRuJ0DDLZro38I0O0JsN/X0rVUzt7Q81cg0VNXHwxhzgSZc2XpsoM0t1Fmc0Yy5wkjBdBq2+p5W9VZqJZcODzNyzNYuzAaZ7WurQtHvUaZxyuta9uleDVMdVvgBbdrQ4nMOk7liZ7cjiXNAsdSUbFeyYy08T35Q97c2UKA+cPpw4iznRl4b3Bbo3RZaRweS4QuytuNR0/0UB7ozE08JrxZxuSDp39azan7E2Sdn2VwV1rZqGA2/+tqtlU7JfmrgtrW4jBu/+NqtlqBERAREQF64WTqZ5FeDe2lr965yH8K2MDXjCZTfVz3OBOv3QB/t1lB03CydTPIrLJ5GEmzCT3FUMA2g/ENnOHZeCPCvGbNwnKtlG63M9+9R4RtU9zWznCo2Bhu+Muc4u4MgaHS2fKfAFB1JqpbaCMewrUJZB0M8iueLdpnUbhwmGMqQ9oa4Nc4FuuYm/TzdB3r2XbScXbaLCuHzPzAOfltcZbdO7Nf2IL4ySHseRWOEk+55Fc8DtSDq3BiN9hwgv3X/AKry921IkjfkwosuA6NpcdCbXueoa9+6yDo87/ueRRznnRzWe1pXKbY/XEOx8opZnyVgkbw0kDS14hL+Vlyi9w21yBfeQFSfRGcWDK1lbPNUUDWs4OWR73gy3dmDS8A2y5b20v33U3LWXa5cup7eecPb7+X0QF/3LdVlkFwAAEYA6mrKKnU83drYR6/dWRcuF7XuNyyg5zfEfFBaoiICIiCokYyQObI1r2k7nC43rVxSm/R4P3bfkt55zvE/FEGjilN+jw/u2/JZ4pT/AKPD+7b8luRBHkoqWRhbJTQPad4dE0g+5DRUpa1ppactbYgcE2wtutopCINPFaf/ALEP7sfJa34fRPdmfRUrnWtd0LSbeSlIgjGgoy7MaOmJ6+Bbf4J9X0X6HS/uW/JSUQRPqygzZuIUea1r8Ay/wXk4VhxFjh9Fbfbi7PkpqIIf1Vh2Yu+r6LMdCeLsv8E+q8P/AECj/cM+SmIggjCMNFrYdQ6C3/LM3eSfVGGWt9W0NrWtxZnyU5EHljGsY1jGhrWiwa0WAHUAvSIgIiICIiDBvbQXPQL2XMUu1jpDeqwTFKVhLWtc+MG7nEAC2lr3Hv6l07jYEqPHUl8bnOhqWuG5pi3+GqCpbtEXYi+l+q8QDWymLhnR2YQASXX6tNOu/QlNtNFUtvDh2KHNGZGZqctDwGZwL9BIta/SQrbjQJLQyq0FzenIA0Bte/ettVJxaXg3Cd50twcJdvv393vQUdHtM2pqhCMKxVmZ+Rsj6chpNnEm/QOTv7wsUW0wqHRibCsTps7XOzSQ3a2zS7U+DfM2V9Tv4eRjBw7S8E3fCRawB1103+4rXFO58rI3xVLHPdlB4LM3p1JB03IKJm1cb2sf9VYqI3WObi50aRcG3Tutp1qZQYy+tqWRR4bXRNzZXvqGcHlGXMCN9x0dxKs3SSBzm5J7h5aORzuUG337tb+AKzE8vimeWztETQ+xi1dpew139FkGxCSd5J8VpfLleG5KgkuynLDcDlWude+/gp3Enf8AdHo/3QR0UjiTv+6PR/uho3AflR6P90EdBzm+I+Kww3aD1i6yOc3xHxQWqIiAiIgqjzneJ+KIec7xPxRAREQEREBERAREQEREBERAREQEREBERAREQYcLgjr0UGPamJrH8LQ10ZjbcgwuNxcDSwN949/Up5XjOe37igiM2rp3NlcKOuyx214vJyrkDQZbnen4V0+SNwo64tfutTvve9tRa4UvM7t+4pnPb9xQQjtdTjKeJV5a6+opn6ezLdZk2spomBz6StALc5/EPOVtr3OmmimZ3dv3FMzu37ighS7W00WYy0tW1jWh2fgXEWIvfQaaFZbtXC6nMzaCvIABy8A4OPKtut7fBTMzu37imY9v3FBEdtQ1rDmw6uEgYHmPgjcXdlte1r31tfdqvbtpMk0cb8OqxnLRmDbht7b7dRNvYepSMx7XuKZj2vcUEKba2CPdQYg8XIJZTvNvct9JtHFWTthjpatr3gkGWF7G2tfUkaLdnPb9xTOel48ig9MGVoHULLI5zfEfFeeV1jyWW3u2/WPigtkREBERBVHnO8T8UQ853ifiiAiIgIiICIiAiIgIiICIiAiIgIiICIiAiIg8ycx3gVGDWsle4zYoSTrlOg1Og/8AekKWqVmDNja8Q4nXsDhYDhGuy6g3Fwer3lBYGpaKOSNrsTDpRm4QsJc02b5bxp4rBcwyMZwuLB2YC99NXEand3+wKAzB3NEv/F8QLpLHNnZdtiN3JsN1vas/VByMBxfEbt3uEjQXC+48lBePwwOcDx+ubbobNot0dG1kcrDUTvEjcpLpLkb9Qeg6rmjgpOU/XGJZm31EjP8ATZZkwdzmAMxevYWtsLObvtzjprrqg6AYeA8O43VWuXZc/JJuD/T3lZmoBLNJIKqpZwm9rH2A0toPYuekwZzs7o8XrY5HNAuC0i4Fr2I9qNwUCnMTsVxB1wLvMjc2jr77ezwQdEyhLaXgON1As8ODw7lADcNejRbKSkFM97uHmlzAN/GPzWtf5rmn4LGYzGzEa5keTI1olBy8rNe5FyejXoXo4PGZYn8dqgY8tgH6G1t46b21HeUHWXHWsOIsdfeuQmwThedi+IggkjLI0W9ykUeG8XnZI7EayoDQQWTOaWnS1zYBBZRfk2+AXoc5viPivOdvWFlpuW26x8UFsiIgIiIKo853ifiiHnO8T8UQEREBERAREQEREBERAREQEREBERAREQEREBERAREQEREBERAREQEREBBzm+I+KIOc3xHxQWqIiAiIgqjzneJ+KIiAiIgIiICIiAiIgIiICIiAiIgIiICIiAiIgIiICIiAiIgIiICIiAiIgIOc3xHxREFqiIg//9k=';
*/

	// $imageDimensions = getimagesize($filename);
	// $width = $imageDimensions[0]/10;
	// $height = $imageDimensions[1]/10;
	// print_rr($imageDimensions);
	// print($width);
	// echo "<img src=\"data:image/jpeg;base64,".$test."\" />";

	$test = base64_decode($test);
	$pdf->AddPage();
	$pdf->setJPEGQuality(100);
	// $pdf->writeHTMLCell(0, 0, '', '', $html, 0, 1, 0, true, '', true);
	$pdf->Image('@'.$test,0, 0, 210, 140);
	//// Image($file, $x='', $y='', $w=0, $h=0, $type='', $link='', $align='', $resize=false, $dpi=300, $palign='', $ismask=false, $imgmask=false, $border=0, $fitbox=false, $hidden=false, $fitonpage=false)

	// ---------------------------------------------------------

	// Close and output PDF document
	// This method has several options, check the source code documentation for more information.
	$pdf->Output('example_001.pdf', 'I');

	//============================================================+
	// END OF FILE
	//============================================================+

}




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