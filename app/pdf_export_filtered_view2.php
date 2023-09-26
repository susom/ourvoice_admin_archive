<?php 
require_once "common.php";
require_once "vendor/tcpdf/tcpdf.php";
require_once "vendor/autoload.php";

// reference the Dompdf namespace
use Dompdf\Dompdf;

$pcode 			= $_GET["pcode"] ?? null;
$active_pid 	= $_GET["pid"] ?? null;

if(!empty($pcode) && !empty($active_pid)){
	$project_tags 	= $_SESSION["DT"]["project_list"][$active_pid]["tags"] ?? array();
	
	// THESE FILTERS COME IN MIXED WITH MOOD AND TAG
	$filters 		= $_GET["filters"] ?? "[]";
	$pfilters 		= json_decode($filters,1);

	$pfilters 		= empty($pfilters) ? array() : $pfilters;
	$data 			= $ds->getFilteredDataGeos($pcode, $pfilters);
	$photos 		= $data["code_block"];

	// instantiate and use the dompdf class
	$dompdf = new Dompdf();
	$dompdf->set_option('isHtml5ParserEnabled', true);
	$dompdf->set_option('isRemoteEnabled',true);
	$dompdf->set_option('defaultFont', 'Arial');
	makeHTML($dompdf, $pcode, $active_pid, $photos, $pfilters);

	// Render the HTML as PDF, Output the generated PDF to Browser
	$dompdf->render();
	$dompdf->stream("dt_filtered_data_" . $pcode);
}


function makeHTML($pdf, $pcode, $active_pid, $photos, $filters){
	ob_start();
	?>
	<!DOCTYPE html>
	<html class="no-js">
	<head>
	    <style>
	        body { margin:0; padding:0; font-family:Helvetica,Arial,Verdana; color:#4F504E; }
	        .page_break { page-break-before: always; }
	        .page_break_after { page-break-after: always; }
	        #main { position:relative; }
	        .content {}

	        .header_title { margin:0; padding:0 0 10px; font-weight:normal; border-bottom:1px solid #B3142D; }
	        .header_title img { vertical-align: text-bottom; width:40px; }
	        .coverpage { margin-top:15%; text-align:center; }
	        .tagTitle { margin-top:25%; text-align:center;  }
	        
	        .photo { margin-top:10px; }

	        h1,h2,h3 { font-weight:normal; }
			
			.icon { font-style:normal; display:inline-block; height:20px; border:1px solid #666; border-radius:3px; padding:3px 5px 0; }
	        .icon span { display:inline-block; vertical-align:middle; }
	        .tag img { display:inline-block; height:15px; margin:0 3px 0 0; vertical-align:middle; }

			ul { margin:0; padding:0; list-style:none; }

			.txns li { margin-bottom:10px; padding:0; vertical-align: text-bottom; }
			.txns img { display:inline-block; height:15px; margin:3px 0 0; }
			.txns i { display:inline-block; width:20px; padding:0; height:15px; }

			.tags li { display:inline-block; margin:0 10px 10px 0; }
			.highlight { background:#ffffcc; color:#333; }
	        
	        .photo_details { clear:both; overflow:hidden; height:472px; }
	        .photo_details aside { float:right; width:40%; display:block; }
	        .photo_details aside .gmap { max-height:400px; }
			.photo_details aside .mood { width:30px; margin-right:5px;}
			.photo_details aside h4 { margin:10px 0 ; font-weight:normal; }

			.photo_details .main_photo { float:left;  max-height:400px; }
			.photo_details .main_photo[rev='1'] { transform: rotate(90deg) translate(-5%,-15%); }
			.photo_details .main_photo[rev='2'] { transform: rotate(180deg) translate(0,0); }
			.photo_details .main_photo[rev='3'] { transform: rotate(270deg) translate(5%,15%); }

			hgroup { position:relative; }
			hgroup b { display:inline-block; position:absolute; right:0; top:18px; font-weight:normal; }
	        small { position:fixed; width:500px; left:50%; bottom:10px; margin-left:-250px; display:none; }
	    </style>
	</head>
	<body>
	<div id="main">
	    <h2 class="header_title"><img src="img/logo.png"> Citizen Science for Health Equity</h2>
	    <div class="content coverpage">
	        <h1>Discovery Tool Portal Filtered Data Report</h1>
	        <h2>Project : <b><?php echo $pcode; ?></b></h2>
	        <h3>Tags : <?php echo implode(", ", $filters); ?></h3>
	    </div>

		<?php 
		// SORT THE PHOTOS INTO GROUPINGS BY TAG
		$groupings = array();
		foreach($filters as $filter_tag){
			// MOOD IS ALREADY FILTERED OUT 
			if($filter_tag == "good" || $filter_tag == "bad" || $filter_tag == "neutral"){
				continue;
			}

			// GENERATE TAG TITLE PAGE
			echo makeTagTitlePage($pcode,$filter_tag);
			
			foreach($photos as $photo){
				if(empty($photo["tags"])){
					continue;
				}elseif(in_array($filter_tag,$photo["tags"])){
					echo makePhotoPage($pcode, $active_pid, $photo, $filter_tag);
				}
			}
		}
		?>
	</div>
	</body>
	</html>
	<?php
	$html = ob_get_clean();
    $pdf->loadHtml($html);
}

function makeTagTitlePage($pcode,$tag){
	$html = <<<EOT
		<div class="content tagTitle page_break">
			<h1>Project : <b>$pcode</b></h2>
			<h2>Tag : $tag</h3>
		</div>
	EOT;
	return $html;    	
}

function makePhotoPage($pcode, $active_pid, $photo, $highlight_tag){
	$_id 		= $photo["doc_id"];
	$last4 		= substr($_id,-4);
	$old 		= $photo["old"];

	$time_date 	= date("F j, Y g:i a", floor($photo["actual_ts"]/1000));
	$goodbad 	= $photo["goodbad"];
	$lng 		= $photo["long"];
	$lat 		= $photo["lat"];
	$rotation 	= $photo["rotate"];
	$tags 		= !empty($photo["tags"]) ? $photo["tags"] : array();

	$photo_name = "photo_" . $photo["n"] . ".jpg";
	$ph_id 		= $old ? $_id : $_id . "_" . $photo_name;
	$photo_uri 	= "passthru.php?_id=".$ph_id."&_file=$photo_name" . $old;

	if($old == "&_old=2"){
		// https://uname:pw@ourvoice-cdb.med.stanford.edu/disc_attachments/AFUM_c9adec2879e0d0b5_5_1511886084386/photo_0.jpg
		$img_url = "https://".cfg::$couch_user.":".cfg::$couch_pw."@ourvoice-cdb.med.stanford.edu/disc_attachments/$_id/$photo_name";
	}else if($old == "&_old=1"){
		// https://uname:pw@ourvoice-cdb.med.stanford.edu/disc_users/GTT_106DF44B-D45F-4E7B-BBF9-6FD23266B3E4_1_1497157548199/photo_0.jpg
		$img_url = "https://".cfg::$couch_user.":".cfg::$couch_pw."@ourvoice-cdb.med.stanford.edu/disc_users/$_id/$photo_name";
	}else{
		// https://uname:pw@ourvoice-cdb.med.stanford.edu/disc_attachment/IRV_281DA1B2-CC01-44FD-9CB4-74212F37455C_1_1587393986767_photo_0.jpg/photo_0.jpg
		$img_url = "https://".cfg::$couch_user.":".cfg::$couch_pw."@ourvoice-cdb.med.stanford.edu/". cfg::$couch_attach_db."/" . $ph_id . "/" . $photo_name;
	}
	$photo_uri  = $img_url;

	// 0 & 2 are good.   1 and 3 need some adjustment
	$main_img 	= "<img class='main_photo rotate' src='$photo_uri' rev='$rotation'>";


	///////////////////////////// GET STATIC GOOGLE MAP /////////////////////////////
	$parameters	= "markers=" . urlencode("|$lat,$lng");;
	$maps_url 	= 'https://maps.googleapis.com/maps/api/staticmap?size=400x'.floor(533).'&zoom=16&'.$parameters."&key=".cfg::$gvoice_key;
	
	///////////////////////////// GET GOOD BAD /////////////////////////////
	$gb_html = "<h4>Good or Bad for the community?</h4>";
	if($goodbad == 1){
		$gb_html .= "<img src='img/icon_smile.png' class='mood'/>";	
	}elseif($goodbad == 2){
		$gb_html .= "<img src='img/icon_frown.png' class='mood'/>";	
	}elseif($goodbad == 3){
		$gb_html .= "<img src='img/icon_smile.png' class='mood'/>";	
		$gb_html .= "<img src='img/icon_frown.png' class='mood'/>";	
	}else{
		$gb_html .= "<img src='img/icon_smile_gray.png' class='mood'/>";	
		$gb_html .= "<img src='img/icon_frown_gray.png' class='mood'/>";	
	}


	///////////////////////////// GET TRANSCRIPTIONS /////////////////////////////		
	$txn_html  = "<h2>Why did you take this picture?</h2>";
	$txn_html .= "<ul class='txns'>";
	if(empty($photo["transcriptions"]) && empty($photo["text_comment"])){
		$txn_html .= "<li><em>No Transcriptions or Text Comments</em></li>";
	}
	if(!empty($photo["transcriptions"])){
		foreach($photo["transcriptions"] as $txn){
			$txns 		= str_replace('&#34;','"', $txn["text"]);
			$txn_html  .= "<li><i><img src='img/icon_mic.png'/></i> <span>'$txns'</span></li>";
		}
	}
    if(!empty($photo["text_comment"])){
		$txn_html .= "<li><i><img src='img/icon_keyboard.png'/></i> <span>'".$photo["text_comment"]."'</span></li>";
    }
    $txn_html .= "</ul>";

	///////////////////////////// GET TAGS /////////////////////////////		
	$tags_html  = "<h2>Tags on this observation:</h2>";
	$tags_html .= "<ul class='tags'>";
	if(!empty($tags)){
		foreach($tags as $tag){
			$highlighted = $tag == $highlight_tag ? "highlight" : "";
			$tags_html 	.= "<li><i class='icon tag $highlighted'><img src='img/icon_tag.png'/><span>$tag</span></i></li>";
		}
	}else{
		$tags_html 	.="<li><i>No Tags</i></li>";
	}
	$tags_html .= "</ul>";

	$html = <<<EOT
		<hgroup class="page_break">
			<h2 class="header_title"><img src="img/logo.png"> Walk Id : $pcode - $last4</h2>
			<b>$time_date</b>
		</hgroup>
		<div class="content photo">
			<div class="photo_details">
				$main_img

				<aside>
					<img class='gmap' src="$maps_url">	
					$gb_html 
				</aside>
			</div>

			$txn_html
			$tags_html
		</div>
	EOT;
	return $html;
}

?>
