<?php
require_once("common.php");

if(isset($_POST["folder_data"]) && isset($_POST["folder_name"])){
	$data = json_decode($_POST["folder_data"],1);
	print_r($data);
	$_SESSION["folder_content"] = $data;
	$_SESSION["folder_name"] = $_POST["folder_name"];

}

if(isset($_SESSION["folder_content"]) && isset($_SESSION["rec_times"])){	
	$ALL_PROJ_DATA = $_SESSION["DT"];
	$Rec = $_SESSION["rec_times"];	
	$checkWeek = strtotime("-4 Week");

	echo '<div id = "header">Folder contents of : <strong>'.$_SESSION["folder_name"].'</strong> </div>';
	echo '<p><em>* Highlighted boxes indicate updates within the last 4 weeks</em></p>';

	echo '<div class = "proj">';

	foreach($_SESSION["folder_content"] as $key => $val){
		if($Rec[$key]["rec_date"] > $checkWeek)
			echo '<div class="entry highlight" data-key = "'.$val.'" ><p class = "abv"><a href="summary.php?id='.$key.'"'.'>'.$key .'</a></p>';
		else 
			echo '<div class="entry" data-key = "'.$val.'" ><p class = "abv"><a href="summary.php?id='.$key.'"'.'>'.$key .'</a></p>';
		
		if(isset($Rec[$key])){
			echo '<p>('.$Rec[$key]["full"].')</p>';
			// if($Rec[$key]["rec_date"] > $checkWeek)
				echo '<p>'.gmdate("Y-m-d",$Rec[$key]["rec_date"]).'</p>';
			// else
			//  	echo '<p>'.gmdate("Y-m-d",$Rec[$key]["rec_date"]).'</p>';
		}else{
			echo '<p>No title data</p>';
			echo '<p>No Walk data</p>'; 
		}
		echo '</div>';

		           

	}
	echo '</div>';
}else{
	echo "please refresh the home page and try again.";

}

?>

<link href="css/dt_popup_folders.css" rel="stylesheet" type="text/css"/>
