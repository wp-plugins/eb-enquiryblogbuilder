// Geoff Austin
// Handle the ajax call to look up the group dropdown list whenever the school list is changed
jQuery(document).ready( function($) {

	jQuery("#selected_school").change(function() {

		var data = {
			action: 'set_group_list',
			selected_school: jQuery("#selected_school option:selected").text()
		};

		jQuery.post(ajaxurl, data, function(response) {
			jQuery("#selected_group").empty();
			jQuery("#selected_group").html(response);
		});

	}); 
    
});