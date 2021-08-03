<?php //ensure this is at the top always
//link this page to the summary page with all the information.
require_once "common.php";

if(isset($_SESSION["discpw"])){ 
$turl  = cfg::$couch_url . "/" . cfg::$couch_users_db . "/"  . "_design/filter_by_projid/_view/get_data_ts"; 
$pdurl = cfg::$couch_url . "/" . cfg::$couch_proj_db . "/" . cfg::$couch_config_db;

$ALL_PROJ_DATA = $ds->urlToJson($pdurl);
$tm = $ds->urlToJson($turl);
$stor = array(); //house information from couch 
$listid = array(); //1D array with all proj names
$iter = 0; //number of results to display'
$ful = ""; //full name
$checkWeek = strtotime("-4 Week"); //for recent activities
$counter = 0;

//Parse & consolidate info here
echo '<a href="index.php">Back to Project Picker</a>';
echo '<h1 id = "title">Recent Activity</h1>';

$stor = parseTime($tm, $stor);

foreach ($stor as $key => $value)
	array_push($listid, $key);

for($i = 0 ; $i < count($stor) ; $i++){
	if($i == 0)
		echo '<div class="column">';
	if($i == intval(floor(count($stor))/3)){ //separate into 3 columns for easier viewing
		echo '</div>';
		echo '<div class="column">';
	}
	if($i == 2*intval(floor(count($stor))/3))
	{
		echo '</div>';
		echo '<div class="column">';
	}

	rsort($stor[$listid[$i]]); //sort corresponding timestamps for each element
	$ful = getFullName($ALL_PROJ_DATA,$listid[$i]);
	echo '<h4>'. "(".$listid[$i]. ") " . $ful . '</h4>';
	echo '<form action="summary.php" form id="route_summary" method="get">';	
	echo '<button type="submit" class="submitbutton" name = "id" value="'.$listid[$i].'">Go</button>';
	echo '</form>';
			

	$iter = 0;
	echo '<ul>';
		while(!empty($stor[$listid[$i]][$iter]) && $iter < 1) //display 3 
		{
			if(($stor[$listid[$i]][$iter]/1000) > $checkWeek){
				$counter++;
				echo '<li class = "recent">'.gmdate("Y-m-d", $stor[$listid[$i]][$iter]/1000).'</li>';
			
			}else
				echo '<li>'.gmdate("Y-m-d", $stor[$listid[$i]][$iter]/1000).'</li>';

			$iter++;
		}
	echo '</ul>';

}//for
	echo '</div>';
}//if
else{
	header('Location: index.php');	 //re route to the 
}

?>

<script src="https://code.jquery.com/jquery-3.2.1.min.js" integrity="sha256-hwg4gsxgFZhOsEEamdOYGBf13FyQuiTwlAQgxVSNgt4=" crossorigin="anonymous"></script>
<script src="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.7/js/bootstrap.min.js" integrity="sha384-Tc5IQib027qvyjSMfHjOMaLkfuWVxZxUPnCJA7l2mCWNIpG9mGCD8wGNIcPD7Txa" crossorigin="anonymous"></script>
<script>
$(document).ready(function(){
	let counter = '<?php echo " ($counter)"?>';
	console.log($("#title").append(counter));
	return false;
	});

</script>
<style>
h1{
	padding-top:20px; 
	text-align: left;
	color:black;
}

.recent{
	list-style-type:none;
    background-image: linear-gradient(to right, yellow, transparent 50%);

)

}
li{
	color:black;
	list-style-type: none;
}
.column{
	width: 33%;
	float: left;
}
h4{
	text-align: left;
}
.submitbutton{
	float: left;
}
</style>
