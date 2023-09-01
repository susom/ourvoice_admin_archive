<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
require_once "common.php";
	//Get all attachment data
	$url = cfg::$couch_url . "/". cfg::$couch_attach_db . "/" .cfg::$couch_all_db;
	$result = $ds->doCurl($url);
	$result = json_decode($result,1);
	$count = 0;
	 // print_rr($result);

	
		foreach($result['rows'] as $row){ //find all pictures
			$count++;	
			if($count == 5157)
				break;
			elseif($count < 5136)
				continue;
			else{
				print_rr($count . '-----------------');

				if(isset($row['id'])){
				$type = explode('.',$row['id']);
				if($type[1] == 'jpg'){ //if there is a jpg on the portal
					$rev = $row['value']['rev'];
					$photo_numeral = explode('photo',$row['id']);
					if(isset($photo_numeral[1]))
						$photo_numeral = 'photo'.$photo_numeral[1];
					detectFaces($row['id'],0,$photo_numeral,$rev);
					}
				}

			}	
		}
//detectFaces("AFUM_c9adec2879e0d0b5_30_1523461489155_photo_6.jpg",0,"photo_6.jpg");
  // detectFaces("IRV_B48AC597-7E1B-4800-A060-B71EF21DEFEB_1_1528399128851_photo_0.jpg",0,"photo_0.jpg");
//will save under ID





function detectFaces($id, $old, $photo_name, $rev){
	if($old){
		if($old == 2)
			$url = cfg::$couch_url . "/disc_attachments/$id";
		else
			$url = cfg::$couch_url . "/".cfg::$couch_users_db."/" . $id;
	}else{
		$url = cfg::$couch_url . "/". cfg::$couch_attach_db . "/" . $id; 
	}
	$result = $ds->doCurl($url);
	$meta = json_decode($result,true);
	$picture = $ds->doCurl($url . '/' . $photo_name); //returns the actual image in string format
	// $picture = file_get_contents('5faces_landscape.jpg');
	$new = imagecreatefromstring($picture); //set the actual picture for editing
	$pixel_count = imagesy($new)*imagesx($new);
	$picture = base64_encode($picture); //encode so we can send it to API 
	$data = array(
	    "requests" => array(
	        "image" => array(
	        	"content" => $picture
	        ),
	    	"features" => array(
	        	"type" => "FACE_DETECTION",
	        	"maxResults" => 10
	    	)    
	    )
	);

	$vertices = array();
	 //POST to google's service
	$resp = $ds->postData('https://vision.googleapis.com/v1/images:annotate?key='.cfg::$gvoice_key,$data);
	$attach_url = cfg::$couch_url . "/" . cfg::$couch_attach_db;

		    $couchurl       = $attach_url."/".$id."/".$photo_name."?rev=".$rev;
		    $content_type   = 'image/jpeg';
		    			$filepath = "./temp/$id";

//	    	print_rr($couchurl);
//			print_rr($filepath);
	//parse response into useable format : XY coordinates per face / IF 
	if(!empty($resp['responses'][0])){
		print_rr('detected face '. $id);
		foreach($resp['responses'][0]['faceAnnotations'] as $index => $entry){
	 		$coord = ($entry['boundingPoly']['vertices']);
		 	$put = array();
		 	foreach($coord as $vtx){
				isset($vtx['x']) ? array_push($put, $vtx['x']) : array_push($put, -1);
			 	isset($vtx['y']) ? array_push($put, $vtx['y']) : array_push($put, -1);
			}
			array_push($vertices,$put);
		}
	// print_rr($vertices);
		// $new = imagecreatefromstring(base64_decode($picture)); //set the actual picture for editing

		$altered_image = filterFaces($vertices, $new, $id, $pixel_count);
		if(isset($altered_image) && $altered_image){
			$filepath = "./temp/$id";
			imagejpeg($altered_image, $filepath); //save it 
			imagedestroy($altered_image);
			$attach_url = cfg::$couch_url . "/" . cfg::$couch_attach_db;

		    $couchurl       = $attach_url."/".$id."/".$photo_name."?rev=".$rev;
		    $content_type   = 'image/jpeg';
//	    	print_rr($couchurl);
			// $response       = $ds->uploadAttach($couchurl, $filepath, $content_type);
			// if(isset("./temp/$id"))
			// 	unset("./temp/$id");

		}

	}

}

//Not used
//function filterFaces($vertices,$image,$id, $pixel_count){
//	$passed = false;
//	foreach($vertices as $faces){
//		$width = isset($faces[0]) && isset($faces[2]) ? $faces[2] - $faces[0] : 0;
//		$height = isset($faces[1]) && isset($faces[7]) ? $faces[7] - $faces[1] : 0;
//		$scale_pixels = isset($pixel_count)? ($pixel_count/(50000)) : 15;
//		if($width != 0 && $height != 0){
//			//have to crop out the faces first then apply filter
//			$crop = imagecrop($image,['x'=>$faces[0],'y'=>$faces[1],'width'=>$width, 'height'=>$height]);
//			// pixelate($crop, $scale_pixels,$scale_pixels);
//			pixelate($crop);
//			//put faces back on the original image
//			imagecopymerge($image, $crop, $faces[0], $faces[1], 0, 0, $width, $height, 100);
//			$passed = true;
//		}
//		// $gaussian = array(array(1.0, 3.0, 1.0), array(3.0, 4.0, 3.0), array(1.0, 3.0, 1.0));
//		// $divisor = array_sum(array_map('array_sum',$gaussian));
//		// 	$col = imagecolorallocate($new, 255, 255, 255);
//		// 	imagepolygon($new, $faces, 4, $col);
//		// 	//imagecrop($new,$faces);
//		// for($i = 0 ; $i < $itr ; $i++)
//		// 	imageconvolution($crop, $gaussian, $divisor, 0);
//	}
//	//save image locally
//	if($passed){
//		return $image;
//	}
//	else
//		return false;
//}

function pixelate($image, $pixel_width = 20, $pixel_height = 20){
    if(isset($image)){
	    $height = imagesy($image);
	    $width = imagesx($image);
	    // start from the top-left pixel and keep looping until we have the desired effect
	    for($y = 0; $y < $height; $y += $pixel_height+1){
	        for($x = 0; $x < $width; $x += $pixel_width+1){
	            // get the color for current pixel, make it legible 
	            $rgb = imagecolorsforindex($image, imagecolorat($image, $x, $y));

	            // get the closest color from palette
	            $color = imagecolorclosest($image, $rgb['red'], $rgb['green'], $rgb['blue']);
	            // fill squares with specified width/height
	            imagefilledrectangle($image, $x, $y, $x+$pixel_width, $y+$pixel_height, $color);
	        }       
	    }
	}
}


?>
