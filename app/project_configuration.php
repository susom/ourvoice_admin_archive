<?php
require_once("common.php");

//adhoc https redirect
include("inc/https_redirect.php");
include("inc/check_login.php");
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
		<script src="js/common.js"></script>
        <style>
            .open_link{
                display: inline-block;
                background:url(img/icon_open_link.png) no-repeat;
                width: 20px;
                height: 20px;
                margin-left: 5px;
                vertical-align: bottom;
                background-size:contain;
            }

            #main {
                padding:20px;
            }
        </style>
	</head>
	<div id = "nav">
		<ul>
			<li><a href = "index.php">Home</a></li>
			<li><a href = "project_configuration.php" class="on">Project Configuration</a></li>
		</ul>
	</div>

	<div id = "main">
		<p><strong><em>* To Configure a New Project: Choose a template below and add a ProjectID and Name!</em></strong></p>
			<a href="index.php?proj_id=TPLFULL" class="tpl btn btn-success">Create new Project from Template</a>
		<p><strong><em>* To Make Changes to an Existing Project: Click on a project Below</em></strong></p>

        <br>

        <div id = "proj">
        <?php
            $ALL_PROJ_DATA  = $ds->getActiveProjectsMeta();
            $sort           = array();
            foreach ($ALL_PROJ_DATA as $key=> $project){
                $sort[$project["code"]] = array("key" => $key, "project_name" => $project["name"]);
            }
            ksort($sort);

            foreach($sort as $code => $p){
                if(strpos($code,"Template") > -1){
                    continue;
                }

                echo '<div class="entry" data-key = "'.$p["key"].'" ><p><a href="index.php?proj_id='.$code.'"'.'>'.$code. ' [' . $p["project_name"] . ']</a> <a href="summary.php?id='.$code.'" class="open_link" target="blanket"></a></p></div>';
            }
        ?>
        </div>
    </div>
<script>
	$(document).ready(function(){
        <?php
        if(isset($_GET["msg"])){
            echo "alert('" . filter_var($_GET["msg"], FILTER_SANITIZE_STRING) . "');\n";
        }
        ?>

		pdata = <?php echo json_encode($ALL_PROJ_DATA);?>;
		implementSearch(pdata);
	});
</script>
