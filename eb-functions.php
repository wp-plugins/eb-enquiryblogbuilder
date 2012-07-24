<?php

// return an array containing details of blogs in the group that this user and blog leads
function get_student_blogs($user_id) {
	global $wpdb, $blog_id;
	
	$my_blog = $blog_id;
	
	switch_to_blog(1);

	$table_name = $wpdb->prefix."group_members";
	$group_id = $wpdb->get_var($wpdb->prepare("SELECT group_id FROM $table_name WHERE user_id = %d AND blog_id = %d AND leader = 1", $user_id, $my_blog));
	$blogs = $wpdb->get_results($wpdb->prepare("SELECT blog_id FROM $table_name WHERE group_id = %d AND leader = 0", $group_id));
	restore_current_blog();
	
	$blog_details = array();

	foreach ($blogs as $blog) {
		array_push($blog_details, get_blog_details((int) $blog->blog_id, true));
	}

	return($blog_details);
}

?>