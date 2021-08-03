<?php
header("location:index.php");

exit;
require_once "common.php";

//adhoc https redirect
include("inc/https_redirect.php");

include("inc/check_login.php");

if(!isset($_SESSION["DT"])){
    $url 			= cfg::$couch_url . "/" . cfg::$couch_proj_db . "/" . cfg::$couch_config_db;
    $response 		= $ds->doCurl($url);
	$_SESSION["DT"] = json_decode($response,1);
}
	$ALL_PROJ_DATA 	= $_SESSION["DT"];

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
		<link rel = "stylesheet" type = "text/css" href = "css/dt_organization.css">
		<script src="js/common.js"></script>
	</head>
	<div id = "nav">
		<ul>
			<li><a href = "index.php">Home</a></li>
			<li><a href = "project_configuration.php">Project Configuration</a></li>
			<li><a href = "organization.php">Organization</a></li>
			<li><a href = "recent_activity.php">All Data</a></li>
			<li style="float:right"><a href="index.php?clearsession=1">Refresh Project Data</a></li>
			<li style="float:right"><img id = "magnifying_glass" src = "img/Magnifying_glass_icon.svg"></li>
			<li style="float:right"><input type = "text" id = "search" placeholder="TAG"></li>
			<li style="float:right"><a href = "">Search: </a></li>
		</ul>
	</div>
	<form id="project_config" method="get">
		<div id="project_folders">
			<div class = "folderbar">
				<div class="pull-left">
					<p class = "create_delete"><input type ="text" id = "foldername">
					<button type ="button" onclick="CreateFolder(document.getElementById('foldername').value)">Create Folder</button></p>
					
					<p class = "create_delete"><input type ="text" id = "d_foldername">
					<button type ="button" onclick="DeleteFolder(document.getElementById('d_foldername').value)">Delete Folder</button></p>
				</div>
			</div>
			<div id = "organization_sector">
				<div id = "workingspace">
					<h4>Projects <em>* Drag projects into folders</em></h4>
			      	<?php
				      foreach ($ALL_PROJ_DATA["project_list"] as $key=>$projects) { //populate projects on base page
				        if(isset($ALL_PROJ_DATA["project_list"][$key]["dropTag"])){
				            //if droptag is set do not show in the project list, but rather under hidden folders
				        }else{
				          	if(isset($ALL_PROJ_DATA["project_list"][$key]["project_id"])){
				            	echo '<div class="ui-widget-drag" data-key = "'.$key.'" ><p><a href="index.php?proj_idx='.$key.'"'.'>'.$projects["project_id"] .'</a></p></div>';
				          	}
				        }
				      }
			    	?>
			    </div>
					
			    <div id = "folderspace">
			    	<h4>Folders <em>*Drag projects to trash to remove</em></h4>
			    	<img class = "deleteArea trash-drop" src = "img/icon_trash.png">
			      	<?php
			      		$pCount = array();
			        	foreach ($ALL_PROJ_DATA["folders"] as $key => $value) { //populate folders inside working
				        	$counter = 0;
				        	echo "<div class = individual_sector_".$value.">";
				        	echo "<div class ='ui-widget-drop'><p>".$value." </p></div>";
				          	echo "<div class ='hiddenFolders' id ='".$value."'>";
				            	foreach ($ALL_PROJ_DATA["project_list"] as $k => $v) {
				              		if(isset($v["dropTag"]) && $v["dropTag"] ==$value){
				               			$counter++;
				                	echo '<div class="foldercontents drag-from-folder" data-key = "'.$k.'" ><p><a href="index.php?proj_idx='.$k.'"'.'>'.$v["project_id"] .'</a></p></div>';
				              }
				            }
				            $pCount[$value] = $counter;
				            echo "</div>"; //hiddenfolders
				            echo "</div>"; //individual_sector
				        }
			      	?>    
			    </div>
			</div>
		</div>
	</form>
</html>
<script >
$(document).ready(function(){
	pdata = <?php echo json_encode($ALL_PROJ_DATA);?>;
	implementSearch(pdata);
    bindProperties();
    
	$(document).on("dblclick",".ui-widget-drop",function(event,ui){
	  	console.log(this.innerText);
	  	if($('#'+this.innerText+':visible').length == 0)
	    	$('#'+this.innerText).css('display','inline-block');
		else{
    	 	$('#'+this.innerText).css('display','none');
        	console.log(this.innerText);
		}
	});



	<?php


		if(isset($pname)){
			echo "var current_project_id = '".$pid. "';\n";
		}
		if(isset($_GET["msg"])){
			echo "alert('" . $_GET["msg"] . "');\n";
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

	$(".add_language").click(function(){
		var new_lang = "<div class='one_unit'><span class='code'>Code</span><input type='text' name='lang_code[]' value=''/> <span class='full'>Language</span> <input type='text' name='lang_full[]' value=''/><a href='#' class='delete_parent'>- Delete Language</a></div>";
		$("label.languages").append(new_lang);
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
  
  // function updateProjectCounter(num, folder){
  // 	if(num == 1){ //increase
  // 		$("#"+folder).parent
  // 	}else{			//decrease

  // 	}
  
  // }

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

