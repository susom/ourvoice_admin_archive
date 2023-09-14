<?php
require_once "common.php";

//adhoc https redirect
include("inc/https_redirect.php");

// Loop through all projects
$projects 	= [];
$alerts 	= [];

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
			<li><a href = "index.php" class="on">Home</a></li>
			<li><a href = "project_configuration.php">Project Configuration</a></li>
		</ul>
	</div>
	<?php
	if( isset($_GET["proj_id"]) ){
		// SET UP NEW PROJECT
		$proj_id    = filter_var($_GET["proj_id"], FILTER_SANITIZE_STRING);
        $project    = $ds->getProject($proj_id);
        $p          = $project->snapshot()->data();

		$pid 	    = $p["code"];
		$email      = isset($p["project_email"]) ? $p["project_email"] : "";
		$pname 	    = $p["name"];
		$ppass 	    = $p["project_pass"];
		$spass 	    = isset($p["summ_pass"]) ? $p["summ_pass"] : "";
		$thumbs     = $p["thumbs"];
        $texts      = isset($p["text_comments"]) ? $p["text_comments"] : true;
        $audios     = isset($p["audio_comments"]) ? $p["audio_comments"] : false;
        $forever_login 		    = isset($p["forever_login"]) ? $p["forever_login"] : false;


        // Convert <p> tags back to raw HTML text with linebreaks
        $custom_takephoto_text = isset($p["custom_takephoto_text"]) ? $p["custom_takephoto_text"] : null;
        $custom_takephoto_text = preg_replace('#</p>\s*<p>#', "\n\n", $custom_takephoto_text);
        $custom_takephoto_text = preg_replace('#<p>|</p>#', '', $custom_takephoto_text);

        $expire_date 		    = isset($p["expire_date"]) ? $p["expire_date"] : "";
        $tags                   = isset($p["tags"]) ? $p["tags"] : array();
        $show_proj_tags         = isset($p["show_project_tags"]) ? $p["show_project_tags"] : false;
        $showhideprojtags       = $show_proj_tags ? "display:block" : "display:none";
        $tpl_project            = $ds->getProject("TPLFULL");
        $tpl_p                  = $tpl_project->snapshot()->data();
        $available_langs 	    = $tpl_p["languages"];
        $project_created        = isset($p["project_created"]) ? $p["project_created"] : null;

        $langs 	  			    = $p["languages"];
		$template_type          = isset($p["template_type"]) ? $p["template_type"] : "1";
        $include_surveys        = isset($p["include_surveys"]) ? $p["include_surveys"] : false;
        $template_instructions  = "";
		$template               = false;

		$show_archive_btn       = false;
		if($proj_id == "TPLFULL" || $proj_id == "TPLSHORT"){
			$template = true;
            $new_edit = "new_project";
			$template_instructions = "*Input a new Project ID & Name to create a new project";
		}else{
			$show_archive_btn = true;
            $new_edit = "edit_project";
			$archive_class = isset($p["archived"]) && $p["archived"] ?  "archived" : "active";
		}
		?>
		<form id="project_config" action="ajaxHandler.php" method="post" class='<?php echo $template ? "template" : ""?>'>
			<fieldset class="app_meta">
				<legend>Project Meta <?= $project_created ? "<em>created: $project_created</em>" : "" ?></legend>
                <input type="hidden" name="action" value="<?=$new_edit?>"/>
				<input type="hidden" name="proj_id" value="<?php echo $proj_id; ?>"/>
				<label><span>Admin Email</span><input type="text" name="project_email" value="<?php echo !$template ? $email : ""; ?>"/></label>
                <label id="proj_id_label"><span>Project Id</span><input <?php echo $template ? "" : "readonly"; ?>  type="text" name="project_id" value="<?php echo !$template ? $pid : ""; ?>"/><strong class='tpl_instructions'><?php echo $template_instructions ?></strong></label>
				<label id="proj_name_label"><span>Project Name</span><input  type="text" name="project_name" value="<?php echo !$template ? $pname : ""; ?>"/></label>
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

                <label><span>Forever Logged In</span>
                    <input type="radio" name="forever_login" <?php if(!$forever_login) echo "checked"; ?> value="0"/> Login Once Daily
                    <input type="radio" name="forever_login" <?php if($forever_login) echo "checked"; ?> value="1"/> Never Login Again (after first time)
                </label>

                <label><span >Custom "Take Photo" Text</span>
                    <textarea style="width: 24%; height: 8vh; vertical-align: text-top;" name="custom_takephoto_text" placeholder="eg; Remember to smell roses."><?=$custom_takephoto_text?></textarea>
                </label>

                <label class="proj_tags"><span>Project Tags</span>
                    <input type="radio" name="show_project_tags" <?php if(!$show_proj_tags) echo "checked"; ?> value="0"/> No Project Tags
                    <input type="radio" name="show_project_tags" <?php if($show_proj_tags) echo "checked"; ?> value="1"/> Allow Project Tags
                </label>

                <div class="project_tags" style="<?=$showhideprojtags?>">
                    <?php if($new_edit == "edit_project"){ ?>
                    <p><input type='text' data-proj_idx='<?=$proj_id?>' id='newtag_txt' placeholder="+ Add a New Tag"> <input id="savetag" type='submit' value='Save'/></p>
                    <div id="project_tags">
                        <?php
                        foreach($tags as $tag){
                            $delete_button = "<a href='#' data-proj_idx='$proj_id' data-tag='$tag' class='delete_tag'>&#215;</a>" ;
                            echo "<div class='pricetag'>$tag $delete_button</div>";
                        }
                        ?>
                    </div>
                    <?php
                        }else {
                            echo "<p><em>To add Project Tags, Please save this new project and return in 'edit' mode</em></p>";
                        }
                    ?>
                </div>

                <br><br>
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

                <?php
                if(!$template){
                ?>
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
                <?php
                }
                ?>


				<a href="#" id="delete_project">Delete This Project</a>
				
				<?php 
					if($show_archive_btn){
						echo '<a href="#" id="active_archive" class="'.$archive_class.'"></a>';
					}
				?>
			</fieldset>
			<button type="submit" id="save_project" class="btn btn-primary">Save Project</button>
            <button id="copy_project" class="btn btn-info">Copy Project</button>
            <button id="cancel_copy" class="btn btn-danger">Cancel Copy</button>
			<?php echo '</form>'.'<form action="summary.php" form id="route_summary" method="get">';	?>
		</form>
		<?php
	}else{
	    $recent_days = 14;
		?>
		<form id="project_config" method="get">
			<table id = "rec-table">
				<tr>
					<td ><h3>Recent Activity (last <?=$recent_days?> days)</h3></td>
				</tr>
				<tr>
					<th onclick="sortTable(0)" class = "tablehead" >Project ID -<em> (Click to sort)</em></th>
					<th onclick="sortTable(1)" class = "tablehead">Last Updated</th>
				</tr>
				<?php
                    $recent_walks = $ds->getRecentWalkActivity($recent_days);
					populateRecent($recent_walks);
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
		var hinput 				= $("<input type='hidden' name='delete_project_id'/>").val(delete_project_id);
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

    var ajax_handler  = 'ajaxHandler.php';
    var cash = {};

    $("#cancel_copy").hide();
    //AJAX SAVE PROJECT TAGS
    $("#savetag").click(function(){
      var proj_idx 	    = $("#newtag_txt").data("proj_idx");
      var tagtxt 	    = $("#newtag_txt").val().trim();

      if(tagtxt){
          // add tag to project's tags and update disc_project
          // ADD new tag to UI
          var data = { proj_idx: proj_idx, tag_text: tagtxt, action: "add_project_tag_admin" };

          $.ajax({
              method: "POST",
              url: ajax_handler,
              data: data,
              dataType : "JSON",
              success: function(response){
                  if(response["new_project_tag"]){
                      // add to drop down
                      var delete_button = $("<a>").attr("href","#").data("proj_idx",proj_idx).data("tag", tagtxt).addClass("delete_tag").html("&#215;") ;
                      var newtag = $("<div>").addClass("pricetag").html(tagtxt);
                      newtag.append(delete_button);
                      $("#project_tags").append(newtag)	;
                  }
              },
              error: function(e){
                  console.log("error",e, data);
              }
          }).done(function( msg ) {
              $("#newtag_txt").val("");
          });
      }
      return false;
    });

    //DELETE PROJECT TAGS
    $("#project_tags").on("click",".delete_tag", function(){
        var ele 	= $(this).closest("div");
        var tag 	= $(this).data("tag");
        var pcode 	= $(this).data("proj_idx");

        $.ajax({
            url:  ajax_handler,
            type:'POST',
            data: { deleteTag: tag, project_code: pcode, action : "delete_project_tag_admin"},
            success:function(result){
                // remove from UI: tag list, dropdown and tagged photos
                ele.fadeOut("medium",function(){ $(this).remove(); });
            },
            error:function(e){
                console.log(tag + " wasn't removed");
            }
        });
        return false;
    });

    $("input[name='show_project_tags']").on("change",function(){
        var val = parseInt( $(this).val() );

        if(val){
            $(".project_tags").slideDown("fast");
        }else{
            $(".project_tags").slideUp("medium");
        }
    });

    $("#copy_project").click(function(e){
        e.preventDefault();

        $("input[name='action']").val("copy_project");
        $("input[name='project_id']").prop("readonly", false).val("");
        var old_val = $("input[name='project_name']").val();
        $("input[name='project_name']").data("old_val", old_val);
        $("input[name='project_name']").val("");

        $("#delete_project, #active_archive, #copy_project").fadeOut(function(){
            $(".tpl_instructions").html("*Input a new Project ID / Name to create a new project with these settings");
            $("#cancel_copy").fadeIn();
        });
    });
    $("#cancel_copy").click(function(e){
        e.preventDefault();

        $("input[name='action']").val("edit_project");
        $("input[name='project_id']").prop("readonly", true).val($("input[name='proj_id']").val());
        var old_val = $("input[name='project_name']").data("old_val");
        $("input[name='project_name']").val(old_val);

        $("#cancel_copy").fadeOut(function(){
            $(".tpl_instructions").html("");
            $("#delete_project, #active_archive, #copy_project").fadeIn();
        });
    });
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
		width:100%;
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

    .pricetag{
        white-space:nowrap;
        position:relative;
        margin:0 15px 15px 10px;
        displaY:inline-block;
        height:25px;
        border-radius: 5px 5px 5px 5px;
        padding: 0 25px 0 15px;
        background:#E8EDF0;
        border: 0 solid #C7D2D4;
        border-top-width:1px;
        border-bottom-width:1px;
        color:#999;
        line-height:23px;
    }
    .pricetag .delete_tag{
        position: absolute;
        right: 0;
        font-weight: bold;
        font-size: 19px;
        width: 23px;
        height: 23px;
        text-align: center;
        line-height: 100%;
        color:red;
        text-decoration: none;
    }

    #project_tags{
        max-width:700px;
    }
    .project_tags{
        padding:10px;
    }
</style>
