<?php //ensure this is at the top always
require_once "common.php";

$turl 		= cfg::$couch_url . "/" . cfg::$couch_users_db . "/"  . "_design/filter_by_projid/_view/get_data_ts"; 
$inf = doCurl($turl);
$tm = json_decode(stripslashes($inf),1);
$stor = array(); //house information from couch 
$listid = array(); //1D array with all proj names
$iter = 0; //number of results to display

echo "<h1>Recent Activity</h1>";

if($tm["rows"] == null)
	echo "<h2>No updates</h2>";
else
	//search through all projects 
	for($i = 0 ; $i < count($tm["rows"]) ; $i++){ 		
		$temp = explode('_', $tm["rows"][$i]["id"]); // index zero is the 4 char PID key, index 3 is the time
		$simp_PID = $temp[0];
		$ts = $temp[3];
		
		if(array_key_exists($simp_PID, $stor)) //if ID is already inside
			array_push($stor[$simp_PID], $ts);
		else{
			$stor[$simp_PID] = array($ts);
			array_push($listid, $simp_PID); //otherwise create it and keep track of tags in order
		}
	}
	echo '<h4>'."Timestamps are displayed earliest to latest per project" .'</h4>';
	sort($listid);
	ksort($stor);
	

	for($i = 0 ; $i < count($stor) ; $i++){
		if($i == 0)
			echo '<div class="column">';
		if($i == intval(floor(count($listid))/3)){
			echo '</div>';
			echo '<div class="column">';

		}
		if($i == 2*intval(floor(count($listid))/3))
		{
			echo '</div>';
			echo '<div class="column">';
		}

		rsort($stor[$listid[$i]]); //sort corresponding timestamps
		echo '<h2>'.$listid[$i]. '</h2>';
		$iter = 0;
			echo '<ul>';
			while(!empty($stor[$listid[$i]][$iter]) && $iter < 3) //display 3 
			{
				echo '<li>'.gmdate("Y-m-d", $stor[$listid[$i]][$iter]/1000).'</li>';
				$iter++;
			}
			echo '</ul>';
	}

	echo '</div>';

?>

<style>
h1{
	padding-top:20px; 
	text-align: left;
	color:black;
}

li{
	color: black;
}
.column{
	width: 33%;
	float: left;
}
h4{
	text-align: left;
}
