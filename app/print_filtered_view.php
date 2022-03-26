<?php 
require_once "common.php";
//require_once "vendor/tcpdf/tcpdf.php";

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

$pcode 			= isset($_GET["pcode"]) ? filter_var($_GET["pcode"], FILTER_SANITIZE_STRING) : null;

$project_fs     = $ds->getProject($pcode);
$project_data   = $project_fs->snapshot()->data();
$project_tags   = array_key_exists("tags", $project_data) ? $project_data["tags"] : array();
$google_bucket  = "ov_walk_files";


function generateWalkMap($photo_geos){
	$geopoints = array();
	foreach($photo_geos as $geotag){
		$lat = array_key_exists("lat",$geotag) ? $geotag["lat"] : $geotag["latitude"];
		$lng = array_key_exists("lng",$geotag) ? $geotag["lng"] : $geotag["longitude"];
		$geopoints[] = $lat.",".$lng;
	}
	$markers 		= implode("|",$geopoints);
	$marker_img_url = "https://ourvoice-projects.med.stanford.edu/img/marker_gray.png"; //must be live url
	$urlp 			= urlencode("icon:$marker_img_url"."|".$markers);
	$parameters 	= "markers=$urlp";

	$url 			= 'https://maps.googleapis.com/maps/api/staticmap?size=640x500&zoom=16&'.$parameters."&key=".cfg::$gvoice_key;
	$gmapsPhoto 	= $ds->doCurl($url);
	
	return base64_encode($gmapsPhoto);
}

function generatePhotoPage($photo, $pcode, $page, $total, $highlight_tag=null){
    global $ds, $google_bucket;

	$_id 		= $photo["doc_id"];
	$goodbad    = "/img/icon_none.png";

	if($photo["goodbad"] == 2){
		$goodbad = "/img/icon_smile.png";	
	}elseif($photo["goodbad"] == 1){
		$goodbad = "/img/icon_frown.png";
	}

	$lng 		= $photo["long"];
	$lat 		= $photo["lat"];
	$rotation 	= $photo["rotate"];
    $walk_geo 	= json_encode(array( array("lat" => $lat, "lng" => $lng ) ) );

	$photo_name = "photo_" . $photo["n"] . ".jpg";
	$ph_id 		= $_id . "_" . $photo_name;
	$photo_uri 	= $photo["photo_uri"];

	////////////////GET MAIN PHOTO DEF/////////////////
	$id 	= isset($ph_id) ? $ph_id : NULL ;
	$file 	= isset($photo_name) ? $photo_name : NULL ;

	if (empty($id) || empty($file)) {
	    exit ("Invalid id or file");
	}

	$tags 		= !empty($photo["tags"]) ? $photo["tags"] : null;
	$htmlphoto 	= $photo["full_img"];;
	///////////////////////////// GET MAIN PHOTO END ///////////////////////////// 

	///////////////////////////// GET TRANSCRIPTIONS START /////////////////////////////		
	$retTranscript 	= array();
	$photo_tags 	= !empty($photo["tags"]) ? $photo["tags"] : array();
	if(!empty($photo["audios"]) ){
        foreach($photo["audios"] as $audiofile => $transcription){
            if(isset($transcription) && !empty($transcription)){
                $txn 	= is_array($transcription) ? $transcription : array("text" => $transcription);
                $txns 	= str_replace('&#34;','"', $txn["text"]);
                $txns 	= str_replace("rnrn","<br><br>", $txns);
                $txns 	= str_replace("\n\n","<br><br>", $txns);

                array_push($retTranscript, array("type" => "audio" , "content" => $txns));
            }
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

	$landscape 	= False;
	$scale 		= 1;

//	if($imageResource = imagecreatefromstring($htmlphoto)){
//		 //convert to resource before checking dimensions
//		if(imagesx($imageResource) > imagesy($imageResource)){ //check picture orientation
//			// print_rr(imagesx($imageResource));
//			// print_rr(imagesy($imageResource));
//			$landscape = True;
//			$scale = imagesx($imageResource)/imagesy($imageResource);
//		}else{
//			$scale 		= imagesy($imageResource)/imagesx($imageResource);
//		}
//		imagedestroy($imageResource);
//	}
	$url = 'https://maps.googleapis.com/maps/api/staticmap?size=400x400&zoom=16&'.$parameters."&key=".cfg::$gvoice_key;
	$gmapsPhoto = $ds->doCurl($url);

	generatePage($htmlobj, $htmlphoto, $retTranscript, $gmapsPhoto, $landscape, $scale, $rotation, $goodbad, $tags, $highlight_tag, $pcode , $page, $total);
	///////////////////////////// END STATIC GOOGLE MAP /////////////////////////////
}

function generatePage($htmlobj, $htmlphoto, $retTranscript, $gmapsPhoto, $landscape, $scale, $rotation, $goodbad, $tags, $highlight_tag, $pcode, $page, $total){
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



	$html_block 	= array();
	$html_block[] 	= "<section>";
	$html_block[] 	= "<h2 class='pghdr'>Project : $pcode <b>".$htmlobj['date'] . " " .$htmlobj['time']."<i>&#183; pg $page/$total </i></b></h2>";
	
	//make sure the image is whole (broken images wont have a resource id)
//	$resource_id 	= imagecreatefromstring($htmlphoto);
	$image_is_gd 	= "gd";//get_resource_type($resource_id);
//
//
	$gmapsPhoto 		= base64_encode($gmapsPhoto);
	$html_block[] 		= "<div class='photo_map'>";
	if($image_is_gd == "gd"){
		$wh 			= $landscape ? "width='100%'" : "height='100%'";
		$html_block[] 	= "<div class='photo_cont rotate' rev='$rotation'><img  src='$htmlphoto'/></div>";
	}else{
		$html_block[] 	= "<div class='photo_cont'><h4>Image Not Available</h4></div>";
	}
	$html_block[] 		= "<div class='map_cont'><img class='map' src='data:image/png;base64, $gmapsPhoto' height='100%'/></div>";
	$html_block[] 		= "</div>";

	$html_block[] 		= "<div class='good_bad'>";
	$html_block[] 		= "<h4>Good or Bad for the Community?</h4>";
	if(strpos($goodbad,"icon_none")){
		// this means both
		$html_block[] 	= "<img src='./img/icon_smile.png' width=30>";
		$goodbad 		= "/img/icon_frown.png";
	}
	$html_block[] 		= "<img src='.$goodbad' width=30>";
	$html_block[] 		= "</div>";
	
	$html_block[] 			= "<h2>Why did you take this picture?</h2>";
	if(isset($retTranscript[0]) && !empty($retTranscript[0])){
		foreach($retTranscript as $k => $trans) {
			$type 			= $trans["type"];
			$content 		= $trans["content"];
            $typeicon 		= $type == "audio" ? "[<img src='./img/icon_mic.png'/ width=10> ".($k + 1)."]" : "[text]";
            $html_block[] 	= "<dl><dt>$typeicon :</dt><dd>".nl2br($content)."</dd></dl>";
        }
	}else{
		$html_block[] 		= "<h4>No Transcript Available</h4>";
	}

	$html_block[] 		= "<h2>Tags:</h2>";
	if(!empty($tags)){
		foreach($tags as $tag){
			$hilite = $tag == $highlight_tag ? "hilite" : "";
			$html_block[] = "<h4 class='tag $hilite'><i>$tag</i></h4>";
		}
	}else{
		$html_block[] = "<i>No Tags Yet</i>";
	}

	
	

	$html_block[] = "<small>Generated using the Stanford Discovery Tool, © Stanford University 2020</small>";
	$html_block[] = "</section>";
	echo implode("\r\n",$html_block);
}

if(!empty($pcode) ){
	// THESE FILTERS COME IN MIXED WITH MOOD AND TAG
	$filters 		= $_GET["filters"]  ?: "[]";
	$pfilters 		= json_decode($filters,1);
	$pfilters 		= empty($pfilters) ? array() : $pfilters;

	$data_geos 		= $ds->getFilteredDataGeos($pcode, $pfilters);
	$photo_geos 	= $data_geos["photo_geos"];
	$photos 		= $data_geos["code_block"];

	$json_photo_geos = json_encode($photo_geos);
?>

<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.1//EN" "http://www.w3.org/TR/xhtml11/DTD/xhtml11.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en">
<head>
	<meta http-equiv="content-type" content="application/xhtml+xml; charset=UTF-8" />
  	<meta charset="utf-8">

    <link href="css/dt_common.css?v=<?php echo time();?>" rel="stylesheet" type="text/css"/>
    <link href="css/dt_index.css?v=<?php echo time();?>" rel="stylesheet" type="text/css"/>

    <link href="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.7/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-BVYiiSIFeK1dGmJRAkycuHAHRg32OmUcww7on3RYdg4Va+PmSTsz/K68vbdEjh4u" crossorigin="anonymous">
    <script type="text/javascript" src="https://maps.google.com/maps/api/js?key=<?php echo cfg::$gmaps_key; ?>"></script>
	<script type="text/javascript" src="js/dt_summary.js?v=<?php echo time();?>"></script>
    <style>
    	#main {
    		width: 990px; 
    		margin:25px auto;
    	}

	    section {
	    	position:relative;
	    	min-height:1500px;
		  	margin-bottom:20px;
		  	page-break-before: always;
		  	page-break-inside: avoid;
		}

		section small {
			display:none;
		}
		section h2.pghdr {
			margin:0 0 15px; 
			overflow:hidden;
		}
		section h2.pghdr b{
			float:right;
			font-size: 50%;
		    font-weight: normal;
		    line-height: 220%;
		}
		section h2.pghdr b i{
			font-style:normal; 
			font-weight:bold; 
			display:inline-block;
			margin-left:5px; 
		}
		section h3.tagcover{
			text-align: center;
		    font-size: 250%;
		    margin:100px 0 50px;
		}

		section h2{
			margin-top: 25px;
		    font-size: 175%;
		    padding-bottom: 8px;
		    border-bottom:1px solid #000;
		}

		.tagmaps{
			display:block;
			margin:0 auto; 
		}

		.photo_map {
			overflow:hidden;
		}

		.photo_cont{
			max-width:50%;
            max-height:700px;
			width:50%;
			float:left;
		}

        .photo_cont img{
            max-height: 100%;
            max-width: 100%;
            height: auto;
            width: auto;
        }
		.map_cont{
			max-height:400px; max-width:48%;
			width:50%;
			height:400px;
			float:right;
			text-align:right;
		}
		.map,.photo{
			display:inline-block;

		}

		.good_bad h4{
		    text-align: right;
		    font-weight: normal;
		    font-size: 100%;
		}
		.good_bad img{
			max-width: 30px;
		    float: right;
		}

		#google_map_photos {
			box-shadow:0 0 3px  #888; 
			width:100%;
			height:400px;
			margin: 0px auto 20px;
		}

        #google_map_photos.tall{
            height:80vh;
        }

		dl{ margin-bottom: 10px; font-size: 140%; }
		dt{ display:inline-block; vertical-align: top}
		dd{ display:inline-block; vertical-align: top; width: 90%;}

		h4.tag{
			border: 1px solid #ccc;
		    display:inline-block;
		    padding: 5px 10px;
		    border-radius: 5px;
		    background: #efefef;
		    margin:0 5px 5px 0 ;
		}
		h4.tag i{
			font-style:normal;
		}
		h4.tag.hilite{
			background:#ffffcc;
			font-weight:bold;
		}

		@media print {
			#main {
    			width:auto; 
    			margin:auto;
    		}


    		section {
		    	position:relative;
		    	min-height:100%;
			  	margin-bottom:20px;
			  	page-break-before: always;
			  	page-break-inside: avoid;
			}

			section small {
				display:block;
				position:fixed;
				width:100%;
				text-align:center;
				bottom:0; 
			}

            .photo_map img {
                height:auto;
            }
		  @page {
		    size: 210mm 297mm; /* landscape */
		    /* you can also specify margins here: */
		    margin: 10mm;
		  }

		}
    </style>
</head>
<body>
<div id="main">
<?php
	if(empty($pfilters)){
		?>
        <section>
            <h2 class="pghdr">Project : <?=$pcode?></h2>
            <div id='google_map_photos' class='gmap tall'></div>
            <small>Generated using the Stanford Discovery Tool, © Stanford University 2020</small>
        </section>
        <?php
        $total = count($photos);
		$page  = 1;
		foreach($photos as $photo){
			generatePhotoPage($photo,$pcode, $page, $total);
			$page++;
			set_time_limit(10);
		}
	}else{
		foreach($pfilters as $filter_tag){
			// MOOD IS ALREADY FILTERED OUT 
			if($filter_tag == "good" || $filter_tag == "bad" || $filter_tag == "neutral"){
				continue;
			}
			?>
			<section>
				<h2 class="pghdr">Project : <?=$pcode?></h2>
				<h3 class="tagcover">Theme : <?=$filter_tag?></h3>
				<?php
				// $imgsrc = generateWalkMap($photo_geos);
				// echo "<img class='tagmaps' src='data:image/png;base64,  $imgsrc' />";
				?>
				<div id='google_map_photos' class='gmap'></div>
				<small>Generated using the Stanford Discovery Tool, © Stanford University 2020</small>
			</section>

			<?php
			$total = 0; 
			foreach($photos as $photo){
				if(empty($photo["tags"])){
					continue;
				}elseif(in_array($filter_tag,$photo["tags"])){
					$total++;
				}
			}

			$page = 1; 
			foreach($photos as $photo){
				if(empty($photo["tags"])){
					continue;
				}elseif(in_array($filter_tag,$photo["tags"])){
					generatePhotoPage($photo, $pcode, $page, $total,  $filter_tag );
					$page++;
				}
			}
			set_time_limit(5);
		}
	}
?>
</div>
<script>
var gmarkers 	= drawGMap(<?=$json_photo_geos?>, 'photos', 16);
</script>
</body>
</html>
<?php
	}
?>




