<?php
require_once "common.php";

if(isset($_GET["clearsession"])){
	$_SESSION = null;
}

//MEANING IT HAS TO MAKE A CALL TO GET THIS STUFF
if(!isset($_SESSION["DT"])){
	//TURN IT INTO PHP ARRAY
    // Query for the all projects document
    $url 			= cfg::$couch_url . "/" . cfg::$couch_proj_db . "/" . cfg::$couch_config_db;
    $response 		= doCurl($url);
	$_SESSION["DT"] = json_decode($response,1);
}

// Loop through all projects
$ap 		= $_SESSION["DT"];
$_id 		= $ap["_id"];
$_rev 		= $ap["_rev"];
$projects 	= [];
$alerts 	= [];

foreach($ap["project_list"] as $pid => $proj){
	$projects[$pid] = $proj["project_id"];
} 

// Handle actions on PSOT
if( isset($_POST["proj_idx"]) ){
	$proj_idx  	= $_POST["proj_idx"];

	// Delete
	if(isset($_POST["delete_project_id"])){
		$pidx 		= $proj_idx;
		$payload 	= $ap;
		unset($payload["project_list"][$pidx]);

		$_SESSION["DT"] = $payload;

        //putDoc($payload);
        $url = cfg::$couch_url . "/" . cfg::$couch_proj_db . "/" . cfg::$couch_config_db;
        $response = doCurl($url, json_encode($payload), 'PUT');
        // TODO: Check for success

        $msg = "Project " . $projects[$pidx] . " has been deleted";
		header("location:index.php?msg=$msg");
		exit;
	}else{
	    // REDIRECT IF NO OTHER ACTION
		$redi 		= false;
		if( $projects[$proj_idx] !==  $_POST["project_id"]){
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

		$app_text = array();
		foreach($_POST["app_text_key"] as $tdx => $key){
			array_push($app_text, array(
				 "key" => $key
				,"val" => $_POST["app_text_trans"][$tdx]
			));
		}

		$surveys  = array();
		foreach($_POST["survey_key"] as $sdx => $name){
			$survey_q = array(
				 "name" 	=> $name
				,"type" 	=> $_POST["survey_type"][$sdx]
				,"label" 	=> $_POST["survey_label"][$sdx]
			);
			if(isset($_POST["option_value"][$sdx])){
				$survey_q["options"] = $_POST["option_value"][$sdx];
			}
			array_push($surveys, $survey_q);
		}

		$fixed_consents = array();
		foreach($_POST["consent_trans"] as $consent){
			foreach($consent["text"] as $lang => $trans){
				$consent["text"][$lang] = str_replace("<br />\r\n","\r\n",nl2br($trans));//preg_replace("/<br\W*?\/>/", "\r", nl2br($trans));
				$consent["text"][$lang] = str_replace("/r/n","\r\n",$consent["text"][$lang]);
			}
			$fixed_consents[] = $consent;
		}

		$updated_project = array(
			 "project_id" 		=> strtoupper($_POST["project_id"])
			,"project_name" 	=> $_POST["project_name"]
			,"project_pass" 	=> $_POST["project_pass"]
			,"summ_pass" 		=> $_POST["summ_pass"]
			,"thumbs"			=> $_POST["thumbs"]
			,"template_type"	=> $_POST["template_type"]
			,"app_lang" 		=> $app_lang
			,"app_text" 		=> $app_text
			,"surveys"	 		=> $surveys
			,"consent" 			=> $fixed_consents
		);

		$pidx 		= $proj_idx;
		$payload 	= $ap;
		$payload["project_list"][$pidx] = $updated_project;

        $url 		= cfg::$couch_url . "/" . cfg::$couch_proj_db . "/" . cfg::$couch_config_db;
        $response 	= doCurl($url, json_encode($payload), 'PUT');
        $resp 		= json_decode($response,1);

        if(isset($resp["ok"])){
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
		$discpw 	= $_POST["discpw"];
		if(strtolower($discpw) !== "annban"){
			$alerts[] = "Director Password is incorrect. Please try again.";
		}else{
			$_SESSION["discpw"] = $discpw;
		}
	}
}
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.1//EN" "http://www.w3.org/TR/xhtml11/DTD/xhtml11.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en">
<head>
<meta http-equiv="content-type" content="application/xhtml+xml; charset=UTF-8" />
    <link href="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.7/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-BVYiiSIFeK1dGmJRAkycuHAHRg32OmUcww7on3RYdg4Va+PmSTsz/K68vbdEjh4u" crossorigin="anonymous">
    <link href="css/dt_common.css?v=<?php echo time();?>" rel="stylesheet" type="text/css"/>
    <link href="css/dt_index.css?v=<?php echo time();?>" rel="stylesheet" type="text/css"/>
</head>
<body id="main" class="configurator">
<div id="box">
<?php
$projs 	= $ap["project_list"];
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
				<input type="checkbox" name='authorized'>  Check here to indicate that you are authorized to view these data
			</label>
			<label><input type="password" name="discpw" id="proj_pw" placeholder="Admin Password"/></label>
			<button type="submit" class="btn btn-primary">Go to Configurator</button>
		</form>
	</div>
	<?php
}else{
	?>
	<hgroup>
		<h1>Discovery Tool Project Configurator</h1>
		<a href="index.php">Back to Project Picker</a>
		<a href="index.php?clearsession=1">Refresh Project Data</a>
	</hgroup>
	<?php
	if( isset($_GET["proj_idx"]) ){
		$proj_ids = array_column($projs, 'project_id');
		$p 		  = $projs[$_GET["proj_idx"]];
		$pid 	  = $p["project_id"];
		$pname 	  = $p["project_name"];
		$ppass 	  = $p["project_pass"];
		$spass 	  = isset($p["summ_pass"]) ? $p["summ_pass"] : "";
		$thumbs   = $p["thumbs"];
		$langs 	  = $p["app_lang"];
		$template_type = isset($p["template_type"]) ? $p["template_type"] : "1";

		$template_instructions = "";
		$template = false;
		if($_GET["proj_idx"] == 99 || $_GET["proj_idx"] == 100){
			$template = true;
			$template_instructions = "<strong class='tpl_instructions'>*Input a new Project ID & Name to create a new project</strong>";
		}

		$app_text = $p["app_text"];
		$app_surv = !empty($p["surveys"]) ? $p["surveys"] : array();
		$app_cons = !empty($p["consent"]) ? $p["consent"] : array();
		?>
		<form id="project_config" method="post" class='<?php echo $template ? "template" : ""?>'>
			<fieldset class="app_meta">
				<legend>Project Meta</legend>
				<input type="hidden" name="proj_idx" value="<?php echo $_GET["proj_idx"]; ?>"/>
				<label><span>Project Id</span><input <?php echo $template ? "" : "readonly"; ?>  type="text" name="project_id" value="<?php echo !$template ? $pid : ""; ?>"/><?php echo $template_instructions ?></label>
				<label><span>Project Name</span><input  type="text" name="project_name" value="<?php echo !$template ? $pname : ""; ?>"/></label>
				<label><span>Project Pass</span><input type="text" name="project_pass" value="<?php echo $ppass; ?>"/></label>
				<label><span>Portal Pass</span><input type="text" name="summ_pass" value="<?php echo $spass; ?>"/></label>
				<label><span>Template Type</span>
					<input type="radio" name="template_type" <?php if(!$template_type) echo "checked"; ?> value="0"/> Short Template 
					<input type="radio" name="template_type" <?php if($template_type) echo "checked"; ?> value="1"/> Full Template
				</label>
				<label><span>Use Smilies</span>
					<input type="radio" name="thumbs" <?php if($thumbs) echo "checked"; ?> value="2"/> Yes 
					<input type="radio" name="thumbs" <?php if(!$thumbs) echo "checked"; ?> value="1"/> No
				</label>
				<label class="languages"><p><span>Languages</span> 
					<!-- <a href='#' class='add_language'>+ Add Language</a> -->
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
				<a href="#" id="delete_project">Delete This Project</a>
			</fieldset>
			<fieldset class="app_trans">
				<legend>App Translations</legend>
				<div class="fieldbody">
				<a href='#' class='add_trans'>+ Add Translation Key</a>
				<?php 
				foreach($app_text as $ldx => $text){
					$key 		= $text["key"];
					$val 	 	= $text["val"];

					echo "<div class='one_unit'>";
					echo "<label class='text_key'><span>Translation Key</span> <input type='text' name='app_text_key[$ldx]' value=\"$key\"/></label>";
					foreach($val as $tkey => $trans){
						echo  "<label><span class='key'>Code</span> <i>$tkey</i>
						<span class='val'>Value</span><input type='text' name='app_text_trans[$ldx][$tkey]' value=\"$trans\"/></label>";
					}
					echo "<a href='#' class='delete_parent'>- Delete Key</a><hr></div>";
					
				}
				?>
				</div>
			</fieldset>
			<fieldset class="consent_trans">
				<legend>Consent Translations</legend>
				<div class="fieldbody">
				<?php 
				foreach($app_cons as $cdx => $page){
					$title 	= $page["title"];
					$text 	= $page["text"];
					$button = $page["button"]; 
					echo "<div class='one_unit'>";
					echo "<div><h3>Title</h3>";
					foreach($title as $lkey => $trans){
						echo  "<label><span class='key'>Code</span> <i>$lkey</i>
						<span class='val'>Value</span><input type='text' name='consent_trans[$cdx][title][$lkey]' value=\"$trans\"/></label>";
					}
					echo "</div>";	
					echo "<div><h3>Text</h3>";
					foreach($text as $lkey => $trans){
						$input = "<textarea class='nlbr' name='consent_trans[$cdx][text][$lkey]'>".$trans."</textarea>" ;
						echo  "<label><span class='key'>Code</span> <i>$lkey</i>
						<span class='val'>Value</span>$input</label>";
					}
					echo "</div>";	
					echo "<div><h3>Button</h3>";
					foreach($button as $lkey => $trans){
						echo  "<label><span class='key'>Code</span> <i>$lkey</i>
						<span class='val'>Value</span><input type='text' name='consent_trans[$cdx][button][$lkey]' value=\"$trans\"/></label>";
					}
					echo "</div>";	
					echo "<a href='#' class='delete_parent'>- Delete Consent Page</a><hr>";
					echo "</div>";
				}
				?>
				</div>
			</fieldset>
			<fieldset class="survey_trans">
				<legend>Survey Translations</legend>
				<div class="fieldbody">
				<?php 
				foreach($app_surv as $sdx => $question){
					$type 	= $question["type"];
					$name 	= $question["name"];
					$label 	= $question["label"];
					$opts 	= isset($question["options"]) ? $question["options"] : null;
					
					echo "<div class='one_unit'>";
					echo "<label class='text_key'><span>Survey Key</span> <input type='text' name='survey_key[$sdx]' value=\"$name\"/></label>";
					echo "<label class='text_key'><span>Question Type</span> <input type='text' name='survey_type[$sdx]' value=\"$type\"></label>";
					
					echo "<h4>Question Label</h4>";
					foreach($label as $lang_code => $trans){
						echo  "<label><span class='key'>Code</span> <i>$lang_code</i>
						<span class='val'>Text</span><input type='text' name='survey_label[$sdx][$lang_code]' value=\"$trans\"/></label>";
					}

					if($opts){
						echo "<h4>Answer Options <a href='#' class='add_option'>+ Add Option</a></h4>";
						foreach($opts as $odx => $opt){
							$val 	= $opt["value"];
							echo "<div class='one_unit'>";
							echo "<label class='text_key'><span>Option Value</span><input type='text' name='option_value[$sdx][$odx][value]' value=\"$val\"/></label>";
							foreach($opt as $lang_code => $trans){
								if(in_array($lang_code,$lang_codes)){
									echo  "<label><span class='key'>Code</span> <i>$lang_code</i>
									<span class='val'>Text</span><input type='text' name='option_value[$sdx][$odx][$lang_code]' value=\"$trans\"/></label>";								
								}
							}
							echo "<a href='#' class='delete_parent'>- Delete Answer Option</a>";

							if(isset($opts[$odx+1])){
								echo "<hr class='answer_div'>";
							}
							echo "</div>";
						}
					}
					echo "<a href='#' class='delete_parent'>- Delete Survey Question</a><hr></div>";
				}
				?>
				</div>
			</fieldset>
			<button type="submit" class="btn btn-primary">Save Project</button>
			<?php echo '</form>'.'<br>'.'<form action="summary.php" form id="route_summary" method="get">';	?>
			<button type="submit" class ="btn btn-info" name = "id" value = <?php echo $pid?> >Summary</button>
		</form>
		<?php
	}else{
		$opts 	= array();
		foreach($projs as $idx => $proj){
			if($idx == 99 || $idx == 100){
				// these will be the immutable templates
				continue;
			}
			$opts[] = "<option value='$idx'>".$proj["project_name"]."</option>";
		}
		?>
		<form id="project_config" method="get">
			<h3>Chose project to edit*:</h3>
			<select name="proj_idx">
			<?php 
				echo implode("",$opts);
			?>
			</select>
			<input type="submit"/>
			
			<br><br>
			<p>
				<p><strong><em>* To Configure New Project: <br> Choose a template below and add a ProjectID and Name!</em></strong></p>
				<a href="?proj_idx=99" class="tpl btn btn-info" data-tpl="99">Short Template</a> <a href="?proj_idx=100" class="tpl btn btn-success" tata-tpl="100">Full Template</a>
			</p>
		</form>
		<?php
	}
}
?>
</div>
<script src="https://code.jquery.com/jquery-3.2.1.min.js" integrity="sha256-hwg4gsxgFZhOsEEamdOYGBf13FyQuiTwlAQgxVSNgt4=" crossorigin="anonymous"></script>
<script src="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.7/js/bootstrap.min.js" integrity="sha384-Tc5IQib027qvyjSMfHjOMaLkfuWVxZxUPnCJA7l2mCWNIpG9mGCD8wGNIcPD7Txa" crossorigin="anonymous"></script>
<script>
$(document).ready(function(){
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
</script>	
</body>
</html>
<style>
#box {
	margin:20px;
}

form.template #delete_project,
.consent_trans,
.survey_trans,
.app_trans{
	opacity:0;
	position:absolute;
	z-index:-1000;
}

.tpl_instructions {
	color: red;
    display: inline-block;
    margin: 0 10px;
    font-style: italic;
    font-size: 130%;
}

input[readonly]{ 
	background:#efefef;
	color:#999;
}
</style>