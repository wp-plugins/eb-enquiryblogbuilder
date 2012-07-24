<div class="wrap">	
  <div id="icon-users" class="icon32"><br/></div>
  
	<h2>Enquiry Blog Builder</h2>

	<form id="update_filter" method="get">
		<p>
		Display only these members :
		
		<input type="hidden" name="page" value="<?php echo $_REQUEST['page'] ?>" />
		<input type="hidden" name="update_filter" value="update_filter">

		<label for="teacher_prefix">School </label>
		<select style="width:130px" name="selected_school" id="selected_school"><?php echo $schoolList; ?></select>

		<label for="teacher_group">Group </label>
		<select style="width:130px" name="selected_group" id="selected_group"><?php echo $groupList; ?></select>

		<input type="submit" name="Submit" class="button-primary" value="Update filter" />

		</p>
	</form>

	<form id="update-table" method="post">
		<?php $testListTable->display() ?>
	</form>

	<hr>
	
	<h3>Add new members</h3>
	
	<form enctype="multipart/form-data" id="new-members" method="post">
		<input type="hidden" name="page" value="<?php echo $_REQUEST['page'] ?>" />
		<input type="hidden" name="new_member" value="new_member">
			
		<table class="form-table">
			<tbody>
				<tr>					
					<th colspan="2" scope="row">Group details :</th>
				</tr>

				<tr>					
					<td><label for="member_name">School </label><input type="text" name="member_school" placeholder="School name" value="<?php echo $current_school;?>" size="20"> <label for="member_name">Group </label><input type="text" name="member_group" placeholder="Group name" value="<?php echo $current_group;?>" size="20">
					</td>
				</tr>

				<tr>					
					<th colspan="2" scope="row">Member details :</th>
				</tr>
			</tbody>


			<tbody id="member_details">				
				<tr>
						<td><input type="text" name="member_name[]" placeholder="Member name" value="" size="20"> <input type="text" name="member_email[]" placeholder="Member email" value="" size="20"> <input type="button" class="button-secondary addMemberDetails" value=" + " /></td>
				</tr> 
			</tbody>
 				
			<tbody>
				<tr>
					<td>OR Select a CSV file with two columns. The first is a list of names, the second a list of email addreses.</td>
				</tr>
				<tr>
					<td>
					<input type="hidden" name="MAX_FILE_SIZE" value="30000" />
					<input type="file" name="userfile" size="60">
					</td>
				</tr>
			</tbody>

			<tbody>
				<tr>
					<td>
							<input type="submit" name="Submit" class="button-primary" value="Add member(s)" />
					</td>
				</tr>
			</tbody>

		</table>

	</form>

	<hr>
	
	<h3>Export members details</h3>

	<form id="export-table" method="post">
		<input type="hidden" name="page" value="<?php echo $_REQUEST['page'] ?>" />
		<input type="hidden" name="export_members" value="export_members">
		<input type="hidden" name="member_school" value="<?php echo $current_school;?>"> 
		<input type="hidden" name="member_group" value="<?php echo $current_group;?>">
		<p>Export member details as a CSV file <input type="submit" name="Submit" class="button-primary" value="Export" /></p>
	</form>

</div>