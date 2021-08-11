<?php
require_once "common.php";

//adhoc https redirect
include("inc/https_redirect.php");

//MEANING IT HAS TO MAKE A CALL TO GET THIS STUFF
if(!isset($_SESSION["DT"])){
	//TURN IT INTO PHP ARRAY
    // Query for the all projects document
    $url 			= cfg::$couch_url . "/" . cfg::$couch_proj_db . "/" . cfg::$couch_config_db;
    $response 		= $ds->doCurl($url);
	$_SESSION["DT"] = json_decode($response,1);
}

// Loop through all projects
$ap 			= $_SESSION["DT"];
$ALL_PROJ_DATA 	= $ap;
$projects 	= [];
$alerts 	= [];

// AJAX HANDLER 
if( isset($_POST["proj_idx"]) ){
	$proj_idx  	= filter_var($_POST["proj_idx"], FILTER_SANITIZE_NUMBER_INT);

	// Delete
	if(isset($_POST["delete_project_id"])){
		$pidx 		= $proj_idx;
		$payload 	= $ap;
		unset($payload["project_list"][$pidx]);

		$_SESSION["DT"] = $payload;

        //putDoc($payload);
        $url 		= cfg::$couch_url . "/" . cfg::$couch_proj_db . "/" . cfg::$couch_config_db;
        $response 	= $ds->doCurl($url, json_encode($payload), 'PUT');
        $resp 		= json_decode($response,1);
        if(isset($resp["rev"])){
        	$payload["_rev"] = $resp["rev"];
        	$ap = $_SESSION["DT"] = $payload;
        }else{
        	echo "something went wrong:";
        	print_rr($resp);
        	print_rr($payload);
        }

        $msg = "Project " . $projects[$pidx] . " has been deleted";
		header("location:index.php?msg=$msg");
		exit;
	}else if(isset($_POST["archive"])){
		// due to unfreshness of SESSION multiple people saving and shit, we need to pull fresh version before pushing back up
		$url 		= cfg::$couch_url . "/" . cfg::$couch_proj_db . "/" . cfg::$couch_config_db;
	    $response 	= $ds->doCurl($url);
		$payload 	= $_SESSION["DT"] = json_decode($response,1);
		
		$payload["project_list"][$proj_idx]["archived"] = $_POST["archive"];
		
        $response 	= $ds->doCurl($url, json_encode($payload), 'PUT');
        $resp 		= json_decode($response,1);
        if(isset($resp["rev"])){
        	$payload["_rev"] = $resp["rev"];
        	$ap = $_SESSION["DT"] = $payload;
        	print_rr("yay sucess");
        }else{
        	echo "something went wrong:";
        	print_rr($resp);
        	print_rr($payload);
        }
		exit;
	}else{
	    // REDIRECT IF NO OTHER ACTION
		$redi 		= false;
		if( $projects[$proj_idx] !==  filter_var($_POST["project_id"], FILTER_SANITIZE_STRING)){
			//MEANS THIS IS A NEW PROJECT
			//NEED A NEW PROJECT ID!
			$temp 		= array_keys($projects);
			$last_key 	= array_pop($temp);
			$last_key++;
			while($last_key > 98 && $last_key < 101){
				$last_key++;
			}
			$proj_idx 	= $last_key;
			$redi 		= true;
		}
		
		//GOT ALL THE DATA IN A STRUCTURE, NOW JUST MASSAGE IT INTO RIGHT FORMAT THEN SUBMIT IT
		$app_lang = array();
		foreach($_POST["lang_code"] as $ldx => $code){
			array_push($app_lang, array("lang" => $code , "language" => $_POST["lang_full"][$ldx]));
		}

		if(isset($_POST["thumbs"]) && is_array($_POST["thumbs"])){
			$_POST["thumbs"] = $_POST["thumbs"][1];
		}

		$expire_date = null;
		if(!empty($_POST["expire_date"])){
			$exp_time 		= strtotime($_POST["expire_date"]);
			$expire_date 	= date('Y-m-d',$exp_time);
		}

		$updated_project = array(
			 "project_id" 		=> strtoupper(filter_var($_POST["project_id"], FILTER_SANITIZE_STRING))
			,"project_name" 	=> filter_var($_POST["project_name"], FILTER_SANITIZE_STRING)
			,"project_pass" 	=> filter_var($_POST["project_pass"], FILTER_SANITIZE_STRING)
			,"summ_pass" 		=> filter_var($_POST["summ_pass"], FILTER_SANITIZE_STRING)
			,"project_email" 	=> filter_var($_POST["project_email"], FILTER_SANITIZE_STRING)
			,"template_type"	=> filter_var($_POST["template_type"], FILTER_SANITIZE_NUMBER_INT)
            ,"text_comments"    => filter_var($_POST["text_comments"], FILTER_SANITIZE_NUMBER_INT)
            ,"audio_comments"  	=> filter_var($_POST["audio_comments"], FILTER_SANITIZE_NUMBER_INT)
            ,"custom_takephoto_text"  => filter_var($_POST["custom_takephoto_text"], FILTER_SANITIZE_STRING)


            // ,"include_surveys"  => $_POST["include_surveys"]
            ,"expire_date"  	=> $expire_date
			,"thumbs"			=> isset($_POST["thumbs"]) ? filter_var($_POST["thumbs"], FILTER_SANITIZE_NUMBER_INT) : 0
			,"app_lang" 		=> $app_lang
		);

		$pidx 			= $proj_idx;
		
		// due to unfreshness of SESSION multiple people saving and shit, we need to pull fresh version before pushing back up
		$url 			= cfg::$couch_url . "/" . cfg::$couch_proj_db . "/" . cfg::$couch_config_db;
	    $response 		= $ds->doCurl($url);
		$payload = $_SESSION["DT"] = json_decode($response,1);

		// since originally setting up the configurator, these new properties were added so 
		// need to make sure they are included in an update.
		if(array_key_exists("tags",$payload["project_list"][$pidx])){
			$updated_project["tags"] = $payload["project_list"][$pidx]["tags"];
		}
		if(array_key_exists("dropTag",$payload["project_list"][$pidx])){
			$updated_project["dropTag"] = $payload["project_list"][$pidx]["dropTag"];
		}
		$payload["project_list"][$pidx] = $updated_project;

        $url 		= cfg::$couch_url . "/" . cfg::$couch_proj_db . "/" . cfg::$couch_config_db;
        $response 	= $ds->doCurl($url, json_encode($payload), 'PUT');
        $resp 		= json_decode($response,1);
        if(isset($resp["rev"])){
        	$payload["_rev"] = $resp["rev"];
        	$ap = $_SESSION["DT"] = $payload;
        	
        	if($redi){
				header("location:index.php?proj_idx=$pidx");
			}
        }else{
        	echo "something went wrong:";
        	print_rr($resp);
        	print_rr($payload);
        }
	}
}

//NOW LOGIN TO YOUR PROJECT
if(isset($_POST["discpw"])){
	if(!isset($_POST["authorized"])){
		$alerts[] = "Please check the box to indicate you are authorized to view these data.";
	}else{
		$discpw 	= filter_var($_POST["discpw"], FILTER_SANITIZE_STRING);
		if(strtolower($discpw) !== $masterblaster){
			$alerts[] = "Director Password is incorrect. Please try again.";
		}else{
			$_SESSION["discpw"] = $discpw;
			$_SESSION["authorized"] = $_POST["authorized"];
		}
	}
}
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
	<script src="js/common.js"></script>
</head>
<body id="main" class="configurator">
<div id="box">
<?php
$pCount = array();
if(!isset($_SESSION["discpw"])) {
	$show_alert 	= "";
	$display_alert 	= "";
	if(count($alerts)){
		$show_alert 	= "show";
		$display_alert 	= "<ul>";
		foreach($alerts as $alert){
			$display_alert .= "<li>$alert</li>";
		}
	}
    ?>
        <div class="alert alert-danger <?php echo $show_alert;?>" role="alert"><?php echo $display_alert  ?></div>
        <div id="box">
            <form id="summ_auth" method="post">
                <h2>Our Voice: Citizen Science for Health Equity</h2>
                <h3>Discovery Tool Data Configurator</h3>
                <copyright>© Stanford University 2017</copyright>
                <disclaim>Please note that Discovery Tool data can be viewed only by signatories to the The Stanford Healthy Neighborhood Discovery Tool Software License Agreement and in accordance with all relevant IRB/Human Subjects requirements.</disclaim>
                <label class="checkauth">
                    <input type="checkbox" name='authorized'>  Check here to indicate that you are authorized to view this data
                </label>
                <label><input type="password" name="discpw" id="proj_pw" placeholder="Admin Password"/></label>
                <!--Sends entered text in as "discpw"= and authorized as on/empty-->
                <button type="submit" class="btn btn-primary">Go to Configurator</button>
            </form>
        </div>
    <?php
}else{ //if password is actually set, display the project configurator
	?>
	<div id = "nav">
		<ul>
			<li><a href = "index.php">Home</a></li>
			<li><a href = "project_configuration.php">Project Configuration</a></li>
			<li><a href = "recent_activity.php">All Data</a></li>
			<li style="float:right"><a href="index.php?clearsession=1">Refresh Project Data</a></li>
		</ul>
	</div>
	<?php
	if( isset($_GET["proj_idx"]) ){
		// SET UP NEW PROJECT
		$proj_idx = filter_var($_GET["proj_idx"], FILTER_SANITIZE_NUMBER_INT);
		$proj_ids = array_column($projs, 'project_id');
		$p 		  = $projs[$proj_idx];
		$pid 	  = $p["project_id"];
		$email    = isset($p["project_email"]) ? $p["project_email"] : "";
		$pname 	  = $p["project_name"];
		$ppass 	  = $p["project_pass"];
		$spass 	  = isset($p["summ_pass"]) ? $p["summ_pass"] : "";
		$thumbs   = $p["thumbs"];
        $texts    = isset($p["text_comments"]) ? $p["text_comments"] : true;
        $audios   = isset($p["audio_comments"]) ? $p["audio_comments"] : false;
        $custom_takephoto_text = isset($p["custom_takephoto_text"]) ? $p["custom_takephoto_text"] : null;
        $expire_date 		= isset($p["expire_date"]) ? $p["expire_date"] : "";
        $available_langs 	= $projs[100]["app_lang"];
		$langs 	  			= $p["app_lang"];
		$template_type      = isset($p["template_type"]) ? $p["template_type"] : "1";
        $include_surveys    = isset($p["include_surveys"]) ? $p["include_surveys"] : false;
        $template_instructions = "";
		$template = false;

		$show_archive_btn = false;
		if($proj_idx == 99 || $proj_idx == 100){
			$template = true;
			$template_instructions = "<strong class='tpl_instructions'>*Input a new Project ID & Name to create a new project</strong>";
		}else{
			$show_archive_btn = true;
			$archive_class = isset($p["archived"]) && $p["archived"] ?  "archived" : "active";
		}
		?>
		<form id="project_config" method="post" class='<?php echo $template ? "template" : ""?>'>
			<fieldset class="app_meta">
				<legend>Project Meta</legend>
				<input type="hidden" name="proj_idx" value="<?php echo $proj_idx; ?>"/>
				<label><span>Admin Email</span><input type="text" name="project_email" value="<?php echo !$template ? $email : ""; ?>"/></label>
				<label><span>Project Id</span><input <?php echo $template ? "" : "readonly"; ?>  type="text" name="project_id" value="<?php echo !$template ? $pid : ""; ?>"/><?php echo $template_instructions ?></label>
				<label><span>Project Name</span><input  type="text" name="project_name" value="<?php echo !$template ? $pname : ""; ?>"/></label>
				<label><span>Project Pass</span><input type="text" name="project_pass" value="<?php echo $ppass; ?>"/></label>
				<label><span>Portal Pass</span><input type="text" name="summ_pass" value="<?php echo $spass; ?>"/></label>
                <input type="hidden" name="template_type" value="1"/>
                <input type="hidden" name="thumbs" value="2"/>
                
                <label>
                	<span>Expire Project Date?</span><input name="expire_date"  type='text'  value="" id='datetimepicker1' />
			        <script type="text/javascript">
			            $(function () {
			                $('#datetimepicker1').datetimepicker({
				                format: 'MM/DD/YYYY'
				                <?php if($expire_date){ echo ",defaultDate:'$expire_date'"; } ?>
				            });
			            });
			        </script>
			        <style>
					.app_meta .bootstrap-datetimepicker-widget .table-condensed span{
						width:initial ;
					}
			    	</style>
					<script src="https://cdnjs.cloudflare.com/ajax/libs/moment.js/2.24.0/moment.min.js"></script>
					<script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-datetimepicker/4.17.47/js/bootstrap-datetimepicker.min.js"></script>
                </label>

                <label><span>Audio Comments</span>
                    <input type="radio" name="audio_comments" <?php if(!$audios) echo "checked"; ?> value="0"/> No Audio Recordings
                    <input type="radio" name="audio_comments" <?php if($audios) echo "checked"; ?> value="1"/> Allow Audio Recordings
                </label>

                <label><span>Text Comments</span>
                    <input type="radio" name="text_comments" <?php if(!$texts) echo "checked"; ?> value="0"/> No Texting
                    <input type="radio" name="text_comments" <?php if($texts) echo "checked"; ?> value="1"/> Allow Texting
                </label>

                <label><span >Custom "Take Photo" Text</span>
                    <input type="text" name="custom_takephoto_text" placeholder="eg; Remember to smell roses." value="<?=$custom_takephoto_text?>"/>
                </label>


				<label class="languages">
					<p><span>Languages</span>
					</p>
					<?php
					$lang_codes = array();
					
					
					foreach($langs as $lang){
						array_push($lang_codes,$lang["lang"]);
						$readonly = "readonly";
						$delete_button = $lang["lang"] !==  "en" ? "<a href='#' class='delete_parent'>- Delete Language</a>" : "";
						echo "<div class='one_unit'><span class='code'>Code</span><input type='text' name='lang_code[]' value='".$lang["lang"]."' $readonly/> <span class='full'>Language</span> <input type='text' name='lang_full[]' value='".$lang["language"]."' $readonly/>" . $delete_button . "</div>";
					}
					?>
				</label>
				<label>
					<b>+ Add Language</b> 
				<?php 
					echo "<select id='add_language'>";
					foreach($available_langs as $lang){
						echo "<option data-code='".$lang["lang"]."'>".$lang["language"]."</option>\r\n";
					}
					echo "</select>";
				?>
				</label>


				<a href="#" id="delete_project">Delete This Project</a>
				
				<?php 
					if($show_archive_btn){
						echo '<a href="#" id="active_archive" class="'.$archive_class.'"></a>';
					}
				?>
			</fieldset>
			<button type="submit" class="btn btn-primary">Save Project</button>
			<?php echo '</form>'.'<form action="summary.php" form id="route_summary" method="get">';	?>
		</form>
		<?php
	}else{
		$turl  	= cfg::$couch_url . "/" . cfg::$couch_users_db . "/"  . "_design/filter_by_projid/_view/get_data_ts"; 
		$tm 	= $ds->urlToJson($turl); //Just for times + project abv
		$stor 	= $listid = array();
		$stor 	= parseTime($tm, $stor, $listid);

		if(is_array($stor)){
            foreach ($stor as $key => $value){
                array_push($listid, $key);
            }
        }
		?>
		<form id="project_config" method="get">
				    <div id = "folderspace">
				      	<?php
				      		if(isset($ALL_PROJ_DATA["folders"])) {
                                foreach ($ALL_PROJ_DATA["folders"] as $key => $value) { //populate folders inside working
                                    $counter = 0;
                                    echo "<div class = 'folder_entry'>";
                                    echo "<div class ='ui-widget-drop'><p>" . $value . " </p></div>";
                                    echo "<div class ='hiddenFolders' id ='" . $value . "'>";
                                    foreach ($ALL_PROJ_DATA["project_list"] as $k => $v) {
                                        if (isset($v["dropTag"]) && $v["dropTag"] == $value) {
                                            $counter++;
                                            echo '<div class="foldercontents drag-from-folder" data-key = "' . $k . '" ><p><a href="index.php?proj_idx=' . $k . '"' . '>' . $v["project_id"] . '</a></p></div>';
                                        }
                                    }
                                    $pCount[$value] = $counter;
                                    echo "</div>"; //hiddenfolders
                                    echo "</div>"; //individual_sector
                                }
                            }
				      	?>    
				    </div>
			<table id = "rec-table">
				<tr>
					<td ><h3>Recent Activity</h3></td>
				</tr>
				<tr>
					<th onclick="sortTable(0)" class = "tablehead" >Project ID -<em> (Click to sort)</em></th>
					<th onclick="sortTable(1)" class = "tablehead">Last Updated</th>
				</tr>
				<?php 
					$turl  = cfg::$couch_url . "/" . cfg::$couch_users_db . "/"  . "_design/filter_by_projid/_view/get_data_ts"; 
					$tm = $ds->urlToJson($turl);
					$stor = $listid = array();
					$stor = parseTime($tm,$stor);
                    if(is_array($stor)) {
                        foreach ($stor as $key => $value) {
                            array_push($listid, $key);
                        }
                    }
					populateRecent($ALL_PROJ_DATA,$stor,$listid);
				?>	
			</table>
		</form>
		<?php
	}
}
?>
</div>
</body>
<script>
function sortTable(n){
		var table, rows, switching, i, x, y, shouldSwitch, dir, switchcount = 0;
	  	table = document.getElementById("rec-table");
	  	console.log(table);
	  	switching = true;
	  // Set the sorting direction to ascending:
	  	dir = "asc"; 
	  /* Make a loop that will continue until
	  no switching has been done: */
	 	 while (switching) {
	    // Start by saying: no switching is done:
		    switching = false;
		    rows = table.getElementsByTagName("TR");
		    /* Loop through all table rows (except the
		    first, which contains table headers): */
		    for (i = 2; i < (rows.length - 1); i++) {
		      // Start by saying there should be no switching:
		      shouldSwitch = false;
		      /* Get the two elements you want to compare,
		      one from current row and one from the next: */
		      x = rows[i].getElementsByTagName("TH")[n];
		      y = rows[i + 1].getElementsByTagName("TH")[n];
		      //console.log(rows[i].getElementsByTagName("TH")[n]);
		      //console.log(rows[i+1]);
		      /* Check if the two rows should switch place,
		      based on the direction, asc or desc: */
		      if (dir == "asc") {
		        if (x.innerHTML.toLowerCase() > y.innerHTML.toLowerCase()) {
		          // If so, mark as a switch and break the loop:
		          shouldSwitch= true;
		          break;
		        }
		      } else if (dir == "desc") {
		        if (x.innerHTML.toLowerCase() < y.innerHTML.toLowerCase()) {
		          // If so, mark as a switch and break the loop:
		          shouldSwitch= true;
		          break;
		        }
		      }
		    }
		    if (shouldSwitch) {
		      /* If a switch has been marked, make the switch
		      and mark that a switch has been done: */
		      rows[i].parentNode.insertBefore(rows[i + 1], rows[i]);
		      switching = true;
		      // Each time a switch is done, increase this count by 1:
		      switchcount ++; 
		    } else {
		      /* If no switching has been done AND the direction is "asc",
		      set the direction to "desc" and run the while loop again. */
		      if (switchcount == 0 && dir == "asc") {
		        dir = "desc";
		        switching = true;
		      }
		    }
		}
}
$(document).ready(function(){
	pdata = <?php echo json_encode($ALL_PROJ_DATA);?>;
	if($("#folderspace").length){
		sortTable(1);
		sortTable(1); //default to Last updated Time
	    bindProperties();
    }
	$(document).on("dblclick",".ui-widget-drop",function(event,ui){
	  	var package = {};
	  	var folder_content = $('#'+this.innerText);
	  	for(var i = 0 ; i < folder_content[0].childNodes.length ; i++){
	  		var abv = (folder_content[0].childNodes[i].innerText);
	  		var key = (folder_content[0].childNodes[i].attributes[1].value);
	  		package[abv] = key;
	  	}
	  	console.log(package);
	  	$.ajax({
	        url:"popup_folders.php",
	        type: 'POST',
	        data: "&folder_data=" + JSON.stringify(package)+ "&folder_name="+this.innerText,
	        success:function(result){
	          console.log(result);
	          var win = window.open('popup_folders.php');
	  			if(win){
	  				win.focus();
	  			}else{
	  				alert("please allow popups for this functionality");
	  			}
	        }
	        
	        },function(err){
	          console.log("ERROR");
	          console.log(err);
	    }); //ajax

	});//on

	/*On magnifying glass click and enter press -> run search fx */
	// implementSearch(pdata);

	<?php


		if(isset($pname)){
			echo "var current_project_id = '".$pid. "';\n";
		}
		if(isset($_GET["msg"])){
			echo "alert('" . filter_var($_GET["msg"], FILTER_SANITIZE_STRING) . "');\n";
		}

		if(isset($proj_ids)){
			echo "var proj_ids = ['".implode("','",$proj_ids)."'];";
		}
	?>
	$("#project_config").submit(function(){
		if($("input[name='project_id']").val() == ""){
			alert("A Project ID is required!");
			$("input[name='project_id']").focus();
			return false;
		}
		return true;
	});

	$("input[name='project_id']").change(function(){
		var newpid 	= $(this).val();
		newpid 		= newpid.toUpperCase();
		if(proj_ids.indexOf(newpid) > -1){
			alert( "'"+newpid+"' is already being used."  );
			$(this).val("");
			$(this).focus();
		}
		return false;
	});

	$("fieldset").on("click",".delete_parent",function(){
		$(this).parent().remove();
		return false;	
	});

	$("legend").click(function(){
		$(this).parent().toggleClass("open");
		return false;
	});

	$("#add_language").change(function(){
		var lang_code = $("#add_language option:selected").data("code");
		var lang_name = $("#add_language option:selected").text();

		if(!$('input[value="'+lang_code+'"]').length){
			var new_lang = "<div class='one_unit'><span class='code'>Code</span><input type='text' name='lang_code[]' value='"+lang_code+"' readonly/> <span class='full'>Language</span> <input type='text' name='lang_full[]' readonly value='"+lang_name+"'/><a href='#' class='delete_parent'>- Delete Language</a></div>";
			$("label.languages").append(new_lang);
		}
		return false;
	});

	$(".add_trans").click(function(){
		
		return false;
	})

	$("#delete_project").click(function(){
		var delete_project_id 	= prompt("Please type the Project Id of this project to confirm that you are deleting it.");
		var hinput 				= $("<input type='hidden' name='delete_project_id'/>").val(delete_project);
		if(delete_project_id 	== current_project_id){
			$("#project_config").append(hinput);
			$("#project_config").submit();
		}else{
			alert("Project IDs do not match.  No action taken.");			
		}
		return false;
	});

	$("#active_archive").click(function(){
		var archived = $(this).hasClass("archived");
		var action 	 = 0;
		if(archived){
			$(this).removeClass("archived");
		}else{
			$(this).addClass("archived");
			action = 1;
		}

		var proj_idx  = $("input[name='proj_idx']").val();

		$.ajax({
          url:  "index.php",
          type:'POST',
          data: "&proj_idx=" + proj_idx + "&archive=" + action,
          success:function(result){
            console.log(result);
          }        
            //THIS JUST STORES IS 
          },function(err){
          console.log("ERRROR");
          console.log(err);
        });

		return false;
	});
});
  function bindProperties(){
    $( ".ui-widget-drag").draggable({
      cursor: "move",
      containment: $("#organization_sector"),
      start: function(event,ui){
      		$(".trash-drop").droppable("option", "disabled",true);
      },
      stop: function(event,ui){
    		$(".trash-drop").droppable("option", "disabled",false);
      },
      drag: function(event,ui){
      //  ui.css("z-index", "-1"); //fix frontal input
      }

    });
    $(".drag-from-folder").draggable({
    	cursor: "move",
    	containment: $("#folderspace"),
    	start: function(event,ui){
    		$(".ui-widget-drop").droppable("option", "disabled",true);
    	},
    	stop: function(event,ui){
    		$(".ui-widget-drop").droppable("option", "disabled",false);
    	}
    })
    $(".trash-drop").droppable({
    	hoverClass: "trash-hover" ,
    	drop: function( event, ui ) {
    		var rm_project = event.originalEvent.target;
    		console.log(rm_project);
    		console.log(rm_project.parentNode)
    		repopulateProjects(rm_project.parentNode,rm_project.getAttribute("data-key"));
    	  	bindProperties();

    		rm_project.remove();
    	}

    })  
    $( ".ui-widget-drop" ).droppable({
      drop: function( event, ui ) {
        //var pdata = <?php echo json_encode($ALL_PROJ_DATA);?>;
        var dropBox_name = $.trim(this.innerText);
        var dragBox_name = $.trim(ui.draggable[0].innerText);
        var key = $(ui.draggable[0]).data("key");
        //if does not exist within folder then render it
        addProject(key,dragBox_name,dropBox_name);
        bindProperties();
        $.ajax({
          url:  "config_gui_post.php",
          type:'POST',
          data: "&dropTag=" + dropBox_name + "&dragTag=" + dragBox_name + "&datakey=" + key,
          success:function(result){
            console.log(result);
          }        
            //THIS JUST STORES IS 
          },function(err){
          console.log("ERRROR");
          console.log(err);
        });
        ui.draggable.hide(350);
      }//drop

    }); //ui-widget-drop
  }//bindProperties
  function appendProjectCounter(){
  	var pCounters = <?php echo json_encode($pCount); ?>;
  	for(var proj in pCounters){
  		var appendLoc = $(".individual_sector_"+proj).children(".ui-widget-drop")[0];
  		appendLoc.textContent += (pCounters[proj]);
  	}
  }
  function deleteprompt(){
      var value = confirm("Are you sure you want to delete this folder?");
      return value;

  }
  function CreateFolder(name){
  	name = name.replace(/ /g, "_");
    if(name)
    {
    	
    	if(!isValidElement(name,"ui-widget-drop","class")){
	    	$("<div class ='ui-widget-drop'><p>"+name+"</p></div>").appendTo("#folderspace");
	     	let hiddennode = $("<div class = 'hiddenFolders' id ='"+name+"'></div");
	      	$("#folderspace").append(hiddennode);
	      	bindProperties();
	      	$.ajax({
	        url:"config_gui_post.php",
	        type: 'POST',
	        data: "&folders=" + name,
	        success:function(result){
	          console.log(result);
	        }
	        
	        },function(err){
	          console.log("ERROR");
	          console.log(err);
	      });
	 	}//if
	 	else
	 		alert("Folder already created, please enter a different name");
    }//if name
    else
      alert("Please enter a name for your folder");
  }//CreateFolder
  function DeleteFolder(name){
  	if(name && isValidElement(name,"ui-widget-drop","class")){
  		if(deleteprompt()){
	  		let d_folder = selectFolder(name);
	  		let d_folder_contents = $("#"+name); //selects hidden folder class
	  		let d_folder_parent = $("."+"individual_sector_"+name);
	  		repopulateProjects(d_folder_contents);	
	      	bindProperties();

	  		d_folder.remove();
	  		d_folder_contents.remove();
	  		d_folder_parent.remove();
 		}
  	}else{
  		alert("Please enter a valid name for a folder you wish to delete");
  	}
  }
  function removeFromDB(project){
  	 $.ajax({
          url:  "config_gui_post.php",
          type:'POST',
          data: "&deleteTag=" + project,
          success:function(result){
            console.log(result);
          }        
            //THIS JUST STORES IS 
          },function(err){
          console.log("ERRROR");
          console.log(err);
        });


  }
  function repopulateProjects(hiddenfolder, spc_id = -1){
  	let workingspace = $("#workingspace");
  	var deletion_data = {keys:[],names:[],folder:[]};
 	
 	if(spc_id == -1){
 		let proj_list = (hiddenfolder[0].childNodes);
	  	for(var i = 0 ; i < proj_list.length ;i++){
	  		let key = proj_list[i].getAttribute("data-key");
	  		let proj_name = proj_list[i].textContent;
	  		let div = createNode(key,"ui-widget-drag",proj_name)
	    	$(workingspace).append(div); //repopulate projects
	    	deletion_data.keys.push(key);
	    	deletion_data.names.push(proj_name);
	  	}
	  	deletion_data.folder.push(hiddenfolder[0].id);

	}//if
	else{
		let proj_list = hiddenfolder.childNodes;
		for(var i = 0 ; i < proj_list.length ;i++){
	  		let key = proj_list[i].getAttribute("data-key");
	  		if(key == spc_id){
		  		let proj_name = proj_list[i].textContent;
		  		let div = createNode(key,"ui-widget-drag",proj_name)
		    	$(workingspace).append(div); //repopulate projects
		    	deletion_data.keys.push(key);
		    	deletion_data.names.push(proj_name);

	  		}
	  		
		}
		deletion_data.folder.push("-1");

	}
	console.log(deletion_data);
	removeFromDB(JSON.stringify(deletion_data));
  }
  function createNode(data_key,class_name,text){
  	let div = document.createElement("div");
  	let p = document.createElement("p");
    let a = document.createElement("a");
	div.className = class_name;
    div.setAttribute("data-key",data_key);
    a.href = "index.php?proj_idx="+data_key;
    a.textContent = text;
	$(p).append(a);
    $(div).append(p);
    bindProperties();
    return div; 
  }
  function isValidElement(name,location,type){
  	let selection = (type=="class") ? "." : "#";
  	console.log(selection);
  	let folders = $(selection+location);
  	// ".ui-widget-drop"
  	console.log(folders);
  	for(var i = 0 ; i < folders.length ; i++){
  		if(folders[i].textContent.trim() == name) //trim to ensure no whitespace errors
  			return true;
  	}
  	return false;
  }//isValid
  function selectFolder(name){
  	let folders = $(".ui-widget-drop");
  	for(var i = 0 ; i < folders.length ; i++){
  		if(folders[i].textContent.trim() == name) //trim to ensure no whitespace errors
  			return folders[i];
  	}
  	return false;
  }
  function addProject(key,dragBox_name,dropBox_name){
    let div = document.createElement("div");
    
    let p = document.createElement("p");
    let a = document.createElement("a");
    a.href = "index.php?proj_idx="+key;
    a.textContent = dragBox_name;
    $(p).append(a);
    
    div.className = "foldercontents drag-from-folder";
    div.setAttribute("data-key",key);
    $(div).append(p);
    let search = document.getElementById(dropBox_name);
    $(search).append(div);
  }
</script>	
</html>
<style>
	label{
		display:block;
		max-width: 100%;
		margin-bottom: 5px;
		font-weight: 700;
	}

	hgroup{
		border-bottom:1px solid #999;
		padding:0 20px 10px;
		overflow:hidden;
	}
	hgroup h1{
		float:left; 
		margin:0;
	}

	#project_config{
		overflow:hidden;
		padding:20px;
	}

	.btn-default{
		
		background-color:orange;
	}
	form.template #delete_project,
	.consent_trans,
	.survey_trans,
	.app_trans{
		opacity:0;
		position:absolute;
		z-index:-1000;
	}
	.folder_entry{
		display:inline-block;
	}
	.tpl_instructions {
		color: red;
	    display: inline-block;
	    margin: 0 10px;
	    font-style: italic;
	    font-size: 130%;
	}




	#rec-table {
		border-collapse: collapse;
		position:relative;
		width:49%;
		float:right;
		margin:0 0;
		top:0;
	}

	#rec-table h3 {
		margin:0 0 10px;
	}
	#rec-table .btn {
		 float:right;
		 margin-bottom:10px;
	}
	.tablehead{
		cursor:pointer;
	}
	th{
		border: 1px solid #dddddd;
		text-align: left;
		padding:8px;
	}

	input[readonly]{ 
		background:#efefef;
		color:#999;
	}
	.deleteArea{
		max-width: 70px;
		max-height: 70px;
		float: right;


	}



	.hiddenFolders{
		display: none;


	}


	#folderspace{
		padding:20px;
		display:inline-block;
		float:left;
		width:50%;
		background: #efefef;
	    border-radius: 5px;
	    min-height:600px;
	}

	.ui-widget-drop{
		width: 111px; height: 96px; padding: 0.5em; 
		margin: 10px;
		margin-left: 20px; 
		text-align: center; 
		background-image: url('img/FolderClose.svg');
		background-color: transparent;
		background-size: 100%;
		line-height: 600%;
		background-repeat: no-repeat;
		font-size: 14px;
		display:block;
		-webkit-user-select:none;


	}

	.ui-widget-drag, .foldercontents{
		padding: 5px; 
		float: left; 
		margin: 0px 4px 4px; 
		text-align: center;
		border: transparent;
		width: 80px;
		font-size: 11px;
		font-weight: bold;
		border:1px solid cornflowerblue;
		border-radius:3px;
		background-color: azure;
		display:inline-block;
		cursor:pointer;
	} 
	.ui-widget-drag p{
		margin:0;
	}


	.ui-state-highlight{
		background: transparent;
	}

	#active_archive{
	    display: block;
	    position: absolute;
	    top: 110px;
	    right: 30px;
	    font-weight: Bold;
	    text-decoration: none;
		
		width:140px;
		height:54px;
		background:url(img/button_toggle.png) top left no-repeat;
		background-size:100%;
	}
	#active_archive.archived{
		background-position:bottom left;
	}

	#active_archive:before{
	    content: "Active";
	    position: absolute;
	    color: #fff;
	    left: 30px;
	    top: 15px;
	}
	#active_archive.archived:before{
	    content: "Archived";
	    position: absolute;
	    color: #ccc;
	    left: 56px;
	    top: 15px;
	}
</style>
