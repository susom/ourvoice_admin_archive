
<div id='newtag'>
	<h5>Add New Tag to Photo</h5>
	<form name="newtag" method='post'>
		<input type='text' id='newtag_txt' data-proj_idx='<?php echo $proj_idx; ?>' data-doc_id='<?php echo $_id?>' data-photo_i='<?php echo $photo_i?>'> <input type='submit' value='Save'/>
	</form>
	<h4>Or</h4>
	<h5>Add Existing Tag to Photo</h5>
	<ul>
		<?php

		if(empty($project_tags)){
			echo "<p class='noback notags'>There are currently no tags in this project.</p>";
		}
		foreach($project_tags as $idx => $tag){
			echo "<li ><a href='#' class='tagphoto' data-doc_id='$_id' data-photo_i='$photo_i'>$tag</a></li>";
		}
		?>
	</ul>

</div>