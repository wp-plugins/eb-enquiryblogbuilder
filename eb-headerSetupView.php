<div class="wrap">
  <div id="icon-options-general" class="icon32"><br/></div>

	<h2>Enquiry Blog Header</h2>

	<?php if (!($group_id == 0)) { ?>

	<form action="<?php echo esc_attr($actionPage) ?>" id="update_filter" method="post">
		<p>
		Edit the header image for this group :

		<input type="hidden" name="page" value="<?php echo esc_attr($_REQUEST['page']) ?>" />
		<input type="hidden" name="update_filter" value="update_filter">

		<label for="teacher_prefix">School </label>
		<select style="width:130px" name="selected_school" id="selected_school"><?php echo esc_attr($schoolList) ?></select>

		<label for="teacher_group">Group </label>
		<select style="width:130px" name="selected_group" id="selected_group"><?php echo esc_attr($groupList) ?></select>

		<input type="submit" name="Submit" class="button-primary" value="Update filter" />

		</p>
	</form>

	<?php } ?>

	<p>Choose image to appear at the top of the blog.</p>

	<form action="<?php echo esc_attr($actionPage) ?>" enctype="multipart/form-data" id="update_image" method="POST">
		<input type="hidden" name="page" value="<?php echo esc_attr($_REQUEST['page']) ?>" />
		<input type="hidden" name="update_header" value="update_header">
		<input type="hidden" name="group_id" value="<?php echo esc_attr($group_id) ?>">
		<input type="hidden" name="MAX_FILE_SIZE" value="512000" />
		Select image file: <input name="userfile" size="50" type="file" />
		<input type="submit" value="Upload Image" />
	</form>

<?php if ($thumbnail) { ?>
	<img src="<?php echo esc_attr($thumbnail) ?>" />
	<form action="<?php echo esc_attr($actionPage) ?>" id="clear_image" method="POST">
			<input type="hidden" name="page" value="<?php echo esc_attr($_REQUEST['page']) ?>" />
			<input type="hidden" name="clear_header" value="clear_header">
			<input type="hidden" name="group_id" value="<?php echo esc_attr($group_id) ?>">
			<input type="submit" value="Remove Image" />
	</form>

<?php } ?>

</div>

