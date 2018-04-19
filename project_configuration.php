<?php
require_once("common.php");

?>

<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.1//EN" "http://www.w3.org/TR/xhtml11/DTD/xhtml11.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en">
	<head>
		<meta http-equiv="content-type" content="application/xhtml+xml; charset=UTF-8" />
	  	<meta charset="utf-8">
	  	<script src="js/jquery-3.3.1.min.js"></script>
  		<script src="js/jquery-ui.js"></script>
	    <link href="css/dt_common.css?v=<?php echo time();?>" rel="stylesheet" type="text/css"/>
	    <link href="css/dt_index.css?v=<?php echo time();?>" rel="stylesheet" type="text/css"/>
	  
	    <link href="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.7/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-BVYiiSIFeK1dGmJRAkycuHAHRg32OmUcww7on3RYdg4Va+PmSTsz/K68vbdEjh4u" crossorigin="anonymous">
		<script src="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.7/js/bootstrap.min.js" integrity="sha384-Tc5IQib027qvyjSMfHjOMaLkfuWVxZxUPnCJA7l2mCWNIpG9mGCD8wGNIcPD7Txa" crossorigin="anonymous"></script>
		<link rel = "stylesheet" type = "text/css" href = "css/dt_project_configuration.css">
	</head>
	<div id = "nav">
		<ul>
			<li><a href = "index.php">Home</a></li>
			<li><a href = "project_configuration.php">Project Configuration</a></li>
			<li><a href = "organization.php">Organization</a></li>
			<li><a href = "recent_activity.php">All Data</a></li>
			<li style="float:right"><a href="index.php?clearsession=1">Refresh Project Data</a></li>
		</ul>
	</div>
	<div id = "main">
		<p><strong><em>* To Configure a New Project: Choose a template below and add a ProjectID and Name!</em></strong></p>
			<a href="index.php?proj_idx=100" class="tpl btn btn-success" data-tpl="100">Create new Project from Template</a>
		<p><strong><em>* To Make Changes to an Existing Project: Click on a project Below</em></strong></p>
	</div>

<div id = "proj">
<?php 
	if(isset($_SESSION["DT"])){
		$ALL_PROJ_DATA = $_SESSION["DT"];
		$sort = array();
		foreach ($ALL_PROJ_DATA["project_list"] as $key=>$projects)
			if($projects["project_name"] != ""){
				if(isset($sort[$projects["project_name"]]))
					array_push($sort[$projects["project_name"]],$key);
				else
	       			$sort[$projects["project_name"]] = $key;  	
			}
	    ksort($sort);
	}
	foreach($sort as $name => $key){
			echo '<div class="entry" data-key = "'.$key.'" ><p><a href="index.php?proj_idx='.$key.'"'.'>'.$name.'</a></p></div>';
	}
?>

</div>
