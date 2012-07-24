// Geoff Austin
// Add and remove members from the 'Add a new member form' to allow multiple new members in one go.
jQuery(function() {
	var location = jQuery('#member_details');
	var i = location.size();

	location.on('click', '.addMemberDetails',function() {
		if (i < 40) {
			jQuery(this).parent().parent().after('<tr><td><input type="text" name="member_name[]" placeholder="Member name" value="" size="20"> <input type="text" name="member_email[]" placeholder="Member email" value="" size="20"> <input type="button" class="button-secondary addMemberDetails" value=" + " />  <input type="button" class="button-secondary removeMemberDetails" value=" - " /></td></tr>');
			i++;
		}
		return false;
	});

	location.on('click', '.removeMemberDetails',function() {
		if ( i > 1 ) {
			jQuery(this).parents('tr').remove();
			i--;
		}
		return false;
	});
});