<?php 
require_once("common.php");

//adhoc https redirect
include("inc/https_redirect.php");

include("inc/check_login.php");

if(isset($_SESSION["DT"]))
	$ALL_PROJ_DATA = $_SESSION["DT"];

?>
<html> 
<head>
 	<link rel = "stylesheet" href = "css/dt_recent_activities.css">
 	<script src="js/jquery-3.3.1.min.js"></script>
  	<script src="js/jquery-ui.js"></script>
  	
	<link href="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.7/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-BVYiiSIFeK1dGmJRAkycuHAHRg32OmUcww7on3RYdg4Va+PmSTsz/K68vbdEjh4u" crossorigin="anonymous">
    <link href="css/dt_common.css?v=<?php echo time();?>" rel="stylesheet" type="text/css"/>
    <link href="css/dt_index.css?v=<?php echo time();?>" rel="stylesheet" type="text/css"/>

 	<script src="https://unpkg.com/react@16/umd/react.production.min.js"></script>
	<script src="https://unpkg.com/react-dom@16/umd/react-dom.production.min.js"></script>
 	
 	<script src="https://unpkg.com/babel-standalone/babel.min.js"></script>
 	<!-- This script exectues everything with a script tag of text/babel when integrated in browser -->
 	
 	<script type = "text/babel" src="React/recent_activity.js"></script>
</head>
<div id = "nav">
	<ul>
		<li><a href = "index.php">Home</a></li>
		<li><a href = "project_configuration.php">Project Configuration</a></li>
		<li><a href = "recent_activity.php">All Data</a></li>
		<li style="float:right"><a href="index.php?clearsession=1">Refresh Project Data</a></li>
		<li style="float:right"><img id = "magnifying_glass" src = "img/Magnifying_glass_icon.svg"></li>
		<li style="float:right"><input type = "text" id = "search" placeholder="TAG"></li>
		<li style="float:right"><a href = "">Search: </a></li>
	</ul>
</div>
<body> 
	<div id="root"></div> 
</body>
</html>

<script>
$(document).ready(function(){
	pdata = <?php echo json_encode($ALL_PROJ_DATA);?>;
	console.log("pdata",pdata);
});
</script>
