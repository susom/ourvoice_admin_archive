<div id='addtags' >
	<a href="#" id='close_addtags'></a>
	<h5>Add New Tag to Project</h5>
	<form name="newtag" method='post'>
		<input type='text' id='newtag_txt' data-proj_idx='<?php echo $active_pid; ?>'> <input type='submit' value='Save'/>
	</form>
	<h4>Or</h4>
	<h5>Drag Existing Tag to Photo</h5>
	<ul>
		<?php
		if(empty($project_tags)){
			echo "<p class='noback notags'>There are currently no tags in this project.</p>";
		}
		foreach($project_tags as $idx => $tag){
			echo "<li ><a href='#' class='tagphoto'><b></b>$tag</a></li>";
		}
		?>
	</ul>
</div>