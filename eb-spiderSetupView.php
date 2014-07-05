<div class="wrap">
  <div id="icon-options-general" class="icon32"><br/></div>

	<h2>Enquiry Blog Spider</h2>

	<?php if (!($group_id == 0)) { ?>

	<form action="<?php echo esc_attr($actionPage) ?>" id="update_filter" method="post">
		<p>
		Edit the spider image for this group :

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

	<p>Choose images to appear at the points of the spider diagram. They will be reduced to 80x60 pixels and may be jpeg, png or gif files.</p>
	<?php echo buildSpiderMap($group_id); ?>
	<img usemap="#spidermap" src="<?php echo plugins_url().'/eb-enquiryblogbuilder/spiderBackground_'.$group_id.'.jpg'; ?>" />

<?php if (isset($_GET['image_id'])) { ?>
<table>
<tr>
<td>
		<img src="<?php echo esc_attr($thumbnail) ?>" />
</td>
<td>
<form action="<?php echo $actionPage; ?>" enctype="multipart/form-data" id="update_image" method="POST">
	<input type="hidden" name="page" value="<?php echo $_REQUEST['page'] ?>" />
	<input type="hidden" name="update_image" value="update_image">
	<input type="hidden" name="group_id" value="<?php echo esc_attr($group_id) ?>">
	<input type="hidden" name="image_id" value="<?php echo esc_attr($image_id) ?>">
	<input type="hidden" name="MAX_FILE_SIZE" value="512000" />
	Select image file: <input name="userfile" size="50" type="file" />
	<input type="submit" value="Upload Image" />
</form>
</td>
</tr>
<tr>
<td>
<?php if ($thumbnail) { ?>
<form action="<?php echo esc_attr($actionPage) ?>" id="clear_image" method="POST">
		<input type="hidden" name="page" value="<?php echo esc_attr($_REQUEST['page']) ?>" />
		<input type="hidden" name="clear_image" value="clear_image">
		<input type="hidden" name="group_id" value="<?php echo esc_attr($group_id) ?>">
		<input type="hidden" name="image_id" value="<?php echo esc_attr($image_id) ?>">
    <input type="submit" value="Remove Image" />
</form>
<?php } ?>
</td>
</tr>
</table>

<?php } ?>

</div>

