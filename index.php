<?php
session_start();
$_SESSION 	= null;

$_ENV['couch_url'   	] 	='https://cci-hrp-cdb.stanford.edu'			;	
$_ENV['couch_proj_proj' ] 	='disc_projects';
$_ENV['couch_db_proj'  	] 	='all_projects';
$_ENV['couch_proj_users']  	='disc_users';
$_ENV['couch_db_all' 	] 	='_all_docs';
$_ENV['couch_adm'   	] 	='disc_user_general';
$_ENV['couch_pw'    	] 	="rQaKibbDx7rP";
$_ENV['gmaps_key'		] 	="AIzaSyCn-w3xVV38nZZcuRtrjrgy4MUAW35iBOo";

$couch_proj = $_ENV["couch_proj"]; 
$couch_db 	= $_ENV["couch_db"]; 
$couch_url 	= $_ENV["couch_url"] . "/$couch_proj" . "/$couch_db";
$couch_adm 	= $_ENV["couch_adm"]; 
$couch_pw 	= $_ENV["couch_pw"]; 

if(!isset($_SESSION["DT"])){
	//CURL OPTIONS
	$ch 		= curl_init($couch_url);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
	curl_setopt($ch, CURLOPT_HTTPHEADER, array(
		"Content-type: application/json",
		"Accept: */*"
	));
	curl_setopt($ch, CURLOPT_USERPWD, "$couch_adm:$couch_pw");
	curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "GET"); //JUST FETCH DATA

	$response 	= curl_exec($ch);
	echo "<pre>";
	print_r($ch);

	curl_close($ch);

	//TURN IT INTO PHP ARRAY
	$_SESSION["DT"] = json_decode(stripslashes($response),1);
}

exit;
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.1//EN" "http://www.w3.org/TR/xhtml11/DTD/xhtml11.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en">
<head>
<meta http-equiv="content-type" content="application/xhtml+xml; charset=UTF-8" />
<link href="css/dt_summary.css" rel="stylesheet" type="text/css"/>
</head>
<body id="main">
<h1>Discovery Tool Project Configurator</h1>
<?php
$ap 	= $_SESSION["DT"];
$_id 	= $ap["_id"];
$_rev 	= $ap["_rev"];
$projs 	= $ap["project_list"];

if( isset($_POST["proj_idx"]) ){
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
	$updated_project = array(
		 "project_id" 		=> $_POST["project_id"]
		,"project_name" 	=> $_POST["project_name"]
		,"project_pass" 	=> $_POST["project_pass"]
		,"thumbs"			=> $_POST["thumbs"]
		,"app_lang" 		=> $app_lang
		,"app_text" 		=> $app_text
		,"surveys"	 		=> $surveys
	);

	$pidx 		= $_POST["proj_idx"];
	$payload 	= $ap;
	$payload["project_list"][$pidx] = $updated_project;

	//CURL OPTIONS
	$ch 		= curl_init($couch_url);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
	curl_setopt($ch, CURLOPT_HTTPHEADER, array(
		"Content-type: application/json",
		"Accept: */*"
	));
	curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'PUT'); //PUT to UPDATE/CREATE IF NOT EXIST
	curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode(cast_data_types($payload)));

	$response 	= curl_exec($ch);
	curl_close($ch);

	$_SESSION["DT"] = $payload;

	$_SESSION 		= null;
	echo "Saved!<pre>";
	print_r($response);
	print_r(json_encode(cast_data_types($payload)));
	exit;
}

if( isset($_GET["proj_idx"]) ){
	$p 		= $projs[$_GET["proj_idx"]];
	$pid 	= $p["project_id"];
	$pname 	= $p["project_name"];
	$ppass 	= $p["project_pass"];
	$thumbs = $p["thumbs"];
	$langs 	= $p["app_lang"];

	$app_text = $p["app_text"];
	$app_surv = $p["surveys"];
	?>
	<form method="post">
		<fieldset class="app_meta">
			<legend>Project Meta</legend>
			<input type="hidden" name="proj_idx" value="<?php echo $_GET["proj_idx"]; ?>"/>
			<label><span>Project Id</span><input type="text" name="project_id" value="<?php echo $pid; ?>"/></label>
			<label><span>Project Name</span><input type="text" name="project_name" value="<?php echo $pname; ?>"/></label>
			<label><span>Project Pass</span><input type="text" name="project_pass" value="<?php echo $ppass; ?>"/></label>
			<label><span>Use Up/Down Thumbs</span>
			<input type="radio" name="thumbs" <?php if($thumbs) echo "checked"; ?> value="1"/> Yes
			<input type="radio" name="thumbs" <?php if(!$thumbs) echo "checked"; ?> value="0"/> No
			</label>
			<label class="languages"><p><span>Languages</span> <a href='#' class='add_language'>+ Add Language</a></p>
			<?php
			$lang_codes = array();
			foreach($langs as $lang){
				array_push($lang_codes,$lang["lang"]);
				echo "<div class='one_unit'><span class='code'>Code</span><input type='text' name='lang_code[]' value='".$lang["lang"]."'/> <span class='full'>Language</span> <input type='text' name='lang_full[]' value='".$lang["language"]."'/><a href='#' class='delete_parent'>- Delete Language</a></div>";
			}
			?>
			</label>
		</fieldset>
		<fieldset class="app_trans">
			<legend>App Translations</legend>
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
		</fieldset>
		<fieldset class="survey_trans">
			<legend>Survey Translations</legend>
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
		</fieldset>
		<input type="submit"/>
	</form>
	<?php
}else{
	$opts 	= array();
	foreach($projs as $idx => $proj){
		$opts[] = "<option value='$idx'>".$proj["project_name"]."</option>";
	}
	?>
	<form method="get">
		<h3>Chose project to edit:</h3>
		<select name="proj_idx">
		<?php 
			echo implode("",$opts);
		?>
		</select>
		<input type="submit"/>
	</form>
	<?php
}
?>
<script src="http://code.jquery.com/jquery-3.1.1.slim.min.js" integrity="sha256-/SIrNqv8h6QGKDuNoLGA4iret+kyesCkHGzVUUV0shc=" crossorigin="anonymous"></script>
<script>
$(document).ready(function(){
	$(".delete_parent").click(function(){
		$(this).parent().remove();
		return false;	
	});
});
</script>	
</body>
</html>



