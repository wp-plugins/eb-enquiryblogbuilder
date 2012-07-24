<?php
/*
		Plugin Name: EnquiryBlogger: Blog Builder
		Plugin URI: http://kmi.open.ac.uk/
		Description: WordPress admin plugin to build a set of teacher and student blogs
		Version: 1.1
		Author: KMi
		Author URI: http://kmi.open.ac.uk/
		License: GPL2
*/

/*  Copyright 2012  Geoff Austin  (email : Geoff.Austin@codejuggler.com)

    This program is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License, version 2, as
    published by the Free Software Foundation.

    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with this program; if not, write to the Free Software
    Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
*/

if (!class_exists('MemberTable')) {
    require_once('eb-MemberTable.php');
}


// Only comment in if using the suffusion theme.
// Also remember to put the suffusion-child theme into your theme folder 
// to add a list of the other blogs within a group on every blog home page menu.
include_once('eb-suffusionSetup.php');


function eb_settings_page() {
	//must check that the user has the required capability - could just be an administrator, not a super admin?
	if (!is_super_admin())
	{
		wp_die( __('You do not have sufficient permissions to access this page.') );
	}

	// See if the user has posted us some information
	// If they did, the hidden field will be set
	
	if( isset($_REQUEST['page']) && $_REQUEST['page'] == 'enquiryblogger_blog_creation') {
	
		// Change the group for a member within the table
		if (isset($_POST['switch_group']) && $_POST['switch_group'] == 'switch_group' ) {
			updateGroup($_POST['blog_id'], $_POST['selected_school'], $_POST['update_group']);
			
			$selected_school = $_POST['selected_school'];
			$selected_group = $_POST['selected_group'];
		}
		
		// Add a new member from the form below the table
		if (isset($_POST['new_member']) && $_POST['new_member'] == 'new_member' ) {
			$school_name = strtolower(preg_replace('/[^A-Za-z0-9]/', '', ($_POST['member_school'])));
			$group_name = strtolower(preg_replace('/[^A-Za-z0-9]/', '', ($_POST['member_group'])));

			$numMembers = count($_POST['member_name']);

			if (isset($_FILES['userfile']) && ($_FILES['userfile']['error'] == 0)) {
				eb_importMembers($_FILES['userfile'], $school_name, $group_name);
			} else {
				for ($i = 0; $i < $numMembers; $i++) {

					$member_name = trim($_POST['member_name'][$i]);
					$member_email = trim($_POST['member_email'][$i]);

					$infoMessage = create_member($school_name, $group_name, $member_name, $member_email);
					echo '<div class="updated"><p><strong>Adding a member</strong></p><p>'.$infoMessage.'</p></div>';
				}
			}
			
			$selected_school = $school_name;
			$selected_group = $group_name;
		}

		// Change setting for a member in the table - Student/Teacher, Customise or not
		if (isset($_GET['member']) && isset($_GET['action'])) {	

			if (($_GET['action'] == 'promote') || ($_GET['action'] == 'demote')) {
				updateLeadership($_GET['member'], ($_GET['action'] == 'promote'));
			}

			if (($_GET['action'] == 'customize') || ($_GET['action'] == 'nocustomize')) {
				updateCustomize($_GET['member'], ($_GET['action'] == 'customize'));
			}

			$selected_school = $_GET['selected_school'];
			$selected_group = $_GET['selected_group'];		
		}
		
		// Changed filter at top of page
		if( isset($_GET['update_filter']) && $_GET['update_filter'] == 'update_filter' ) {
			$selected_school = $_GET['selected_school'];
			$selected_group = $_GET['selected_group'];
		}
	}
	
	$schoolInfo = get_dropdown_schools();	
	$schoolList = to_dropdown($schoolInfo, $selected_school);

	if (!empty($schoolInfo)) {
		if (empty($selected_school)) $selected_school = $schoolInfo[0]->name;
		$groupInfo = get_dropdown_groups($selected_school);
	} else {
		$groupInfo = NULL;
	}
	$groupList = to_dropdown($groupInfo, $selected_group);
	
	if (!empty($schoolInfo)) $current_school = empty($selected_school) ? $schoolInfo[0]->name : $selected_school;
	if (!empty($groupInfo)) $current_group = empty($selected_group) ? $groupInfo[0]->name : $selected_group;
	
	$testListTable = new MemberTable(); //Create an instance of our package class...
	$testListTable->prepare_items($current_school, $current_group); // Display the first school and first group
	    	        
	include_once('eb-memberTableView.php');
}


function eb_export_details() {
	if (is_super_admin()) {
		if( isset($_REQUEST['page']) && $_REQUEST['page'] == 'enquiryblogger_blog_creation') {
			if( isset($_POST['export_members']) && $_POST['export_members'] == 'export_members' ) {
				eb_exportMembers($_POST['member_school'], $_POST['member_group']);
			}
		}
	}
}
add_action('init', 'eb_export_details');


/*
 * Export the given school and group blog details into a CSV file.
 */
function eb_exportMembers($school, $group) {
	global $wpdb;

	switch_to_blog(1);
	$table_name = $wpdb->prefix."group_list";
	$group_id = $wpdb->get_var($wpdb->prepare("SELECT id FROM $table_name WHERE school_name = %s AND group_name = %s", $school, $group));
	$table_name = $wpdb->prefix."group_members";
	$blogs = $wpdb->get_results($wpdb->prepare("SELECT blog_id, leader FROM $table_name WHERE group_id = %d", $group_id));
	restore_current_blog();

	$blog_details = array();

	foreach ($blogs as $blog) {
		$item = get_blog_details((int) $blog->blog_id, true);
		$users = get_users( array( 'blog_id' => $blog->blog_id, 'fields' => 'all' ) );
		$blog_details[] = array('blogname' => $item->blogname, 'siteurl' => $item->siteurl, 'user_login' => $users[0]->user_login, 'user_email' => $users[0]->user_email);
	}

	$payload = '';
	foreach ($blog_details as $blog_detail) {
		$payload = $payload . '"'.$blog_detail['user_login'].'","'.$blog_detail['user_email'].'","'.html_entity_decode($blog_detail['blogname'], ENT_QUOTES | ENT_HTML401).'", "'. $blog_detail['siteurl'].'"'."\r\n";
	}

	header("Content-Type: plain/text");
	header("Content-Disposition: attachment; filename="."BlogDetails_School_".$school."_Group_".$group.".csv");
	header("Content-length: ".(string) mb_strlen($payload, '8bit') );
	header("Expires: ".gmdate("D, d M Y H:i:s", mktime(date("H")+2, date("i"), date("s"), date("m"), date("d"), date("Y")))." GMT");
	header("Last-Modified: ".gmdate("D, d M Y H:i:s")." GMT");
	header("Cache-Control: no-store, no-cache, must-revalidate, post-check=0, pre-check=0");
	header("Pragma: no-cache");
	header("Pragma: public");
	header("Expires: 0");

	print $payload;
}


/*
 * Import from the file a comma separated list of student names and emails into the given school and group
 */
function eb_importMembers($file, $school_name, $group_name) {
	ini_set("auto_detect_line_endings", true);
	if ($file['size'] != 0 && ($handle = fopen($file['tmp_name'], "r")) !== FALSE) {
			while (($data = fgetcsv($handle, 0, ",")) !== FALSE) {
					if (count($data) > 0) {
						$infoMessage = create_member($school_name, $group_name, $data[0], $data[1]);
						echo '<div class="updated"><p><strong>Adding a member</strong></p><p>'.$infoMessage.'</p></div>';
					}
			}
			fclose($handle);
	}
}


/*
 * Update the leadership value for the given blog and activate the plugins
 */
function updateLeadership($blog_id, $value) {
	global $wpdb;
	
	switch_to_blog(1);
	$table_name = $wpdb->prefix."group_members";
	$group_id = $wpdb->update($table_name, array('leader' => $value), array('blog_id' => $blog_id));
	restore_current_blog();

	// Activate each dashboard plugin for this blog if it is a leader
	$dashboard_plugins = array('eb-enquiryblogbuilder/eb-enquiryMoodDashboard.php', 'eb-enquiryblogbuilder/eb-enquirySpiderDashboard.php', 'eb-enquiryblogbuilder/eb-enquirySpiralDashboard.php');

	switch_to_blog($blog_id);
	foreach ($dashboard_plugins as $plugin) { 
		if ($value && !is_plugin_active($plugin)) activate_plugin( $plugin, '', false);
		if (!$value && is_plugin_active($plugin)) deactivate_plugins( $plugin );
	}	
	restore_current_blog();
}


/*
 * Update the customization for the given blog - allows the author theme option and widget changes
 */
function updateCustomize($blog_id, $value) {
	global $wpdb, $wp_user_roles;

	switch_to_blog($blog_id);

	$author_role = get_role('author');
	if ($value)
		$author_role->add_cap('edit_theme_options');
	else
		$author_role->remove_cap('edit_theme_options');

	restore_current_blog();
}

/*
 * Update the group for the given blog
 */
function updateGroup($blog_id, $school_name, $group_name) {
	global $wpdb;

	switch_to_blog(1);
	$table_name = $wpdb->prefix."group_list";
	$group_id = $wpdb->get_var($wpdb->prepare("SELECT id FROM $table_name WHERE school_name = %s AND group_name = %s", $school_name, $group_name));

	if (!empty($group_id)) {
		$table_name = $wpdb->prefix."group_members";
		$group_id = $wpdb->update($table_name, array('group_id' => $group_id), array('blog_id' => $blog_id));
	}
	
	update_suffusion_settings($blog_id); // update the blog header image
	
	restore_current_blog();
}

/* 
 * Get the id of the school group pair - if the school, group pair does not exist and create is true, create it
 */
function get_group_id($school_name, $group_name, $create = true) {
	global $wpdb;

	switch_to_blog(1);
	$table_name = $wpdb->prefix."group_list";

	$group_id = $wpdb->get_var($wpdb->prepare("SELECT id FROM $table_name WHERE school_name = %s AND group_name = %s", $school_name, $group_name));
	
	if (empty($group_id) && $create) {
		$wpdb->insert($table_name, array( 'school_name' => $school_name, 'group_name' => $group_name, 'time' => current_time('mysql')) );
		$group_id = $wpdb->insert_id;
	}	
	restore_current_blog();

	return ($group_id);
}


/* 
 * Link the blog_id and member_id to the school group pair
 */
function add_blog_membership($member_blog_id, $member_user_id, $school_name, $group_name) {
	global $wpdb;

	$group_id = get_group_id($school_name, $group_name);
	updateLeadership($member_blog_id, false); // always a member initially
	
	switch_to_blog(1);
	$table_name = $wpdb->prefix."group_members";
	$entryExisits = $wpdb->get_var($wpdb->prepare("SELECT group_id FROM $table_name WHERE blog_id = %d AND user_id = %d AND group_id = %d", $member_blog_id, $member_user_id, $group_id) );

	if (empty($entryExisits)) {
		$rows_affected = $wpdb->insert( $table_name, array( 'group_id' => $group_id, 'user_id' => $member_user_id, 'blog_id' => $member_blog_id, 'leader' => 0) );
	}
	restore_current_blog();
}


/* 
 * Create a new member (if required) and add it to a new blog (if required)
 */
function create_member($school_name, $group_name, $memberName, $memberEmail) {
global $wpdb, $current_site;

	switch_to_blog(1);
	$mydomain = $current_site->domain;
	$mypath = $current_site->path;
	restore_current_blog();

	// Create the teacher user and blog
	$member_blogtitle = preg_replace('/[^- A-Za-z0-9]/', '', $memberName); // can only have a-Z, space and hyphen
	$member_blogname = strtolower(preg_replace('/[^A-Za-z0-9]/', '', $school_name.$group_name.$memberName)); // can only have a-z, 0-9
	$member_username = strtolower(preg_replace('/[^A-Za-z0-9]/', '', $school_name.$memberName)); // can only have a-z, 0-9
	$member_username = sanitize_user($member_username, true);
	
	if (!empty($member_blogtitle)) {

		// Create a member if the email address or username doesn't already exist
		$member_user_id = email_exists($memberEmail);		
		if ( !$member_user_id || $memberEmail == '') {
			$member_user_id = username_exists( $member_username );
		} else {
			$infoMessage .= "Email '$memberEmail' already used, existing member used, password unchanged.<br />";
		}
		
		if ( !$member_user_id ) {
			$member_user_id = wpmu_create_user( $member_username, 'member', $memberEmail );
			$infoMessage .= 'New member : <strong>'.$memberText.'</strong> Username: <strong>'.$member_username.'</strong> Password : <strong>member</strong><br /> ';
		} else {
			$infoMessage .= "Username '$member_username' already used, existing member used, password unchanged.<br />";
		}
		if ( is_wp_error($member_user_id) ) wp_die($member_user_id->get_error_message());		
		
		// Create a blog and attach the member to it
		$member_blogtitle .= "'s blog";
		$result = wpmu_validate_blog_signup($member_blogname, $member_blogtitle, $user = '');
		if ( is_wp_error($result) ) wp_die($result->get_error_message());

		$member_blog_id = domain_exists($mydomain, $mypath.$member_blogname.'/');
		if ( !$member_blog_id ) {
			$member_blog_id = wpmu_create_blog( $mydomain, $mypath.$member_blogname.'/', $member_blogtitle, 1, array( 'public' => 1 ));
			add_user_to_blog($member_blog_id, $member_user_id, 'author');
			remove_user_from_blog(1, $member_blog_id);
		} else {
			$infoMessage .= 'Blog path '.$mypath.$member_blogname.' duplicated. Existing blog used, no new blog created. ';
			add_user_to_blog($member_blog_id, $member_user_id, 'author');
		}
		if (is_wp_error( $member_blog_id )) wp_die( $member_blog_id->get_error_message());
		
		// Add an entry to link groups of blogs together
		add_blog_membership($member_blog_id, $member_user_id, $school_name, $group_name);	
		
		// Update the header to use the image associated with the group
		update_suffusion_settings($member_blog_id);
	}

	return $infoMessage;
}


// Return list of all schools
function get_dropdown_schools() {	
	global $wpdb;
	$table_name = $wpdb->prefix."group_list";	
	$info = $wpdb->get_results("SELECT school_name AS name FROM $table_name GROUP BY school_name");
	return $info;	
}


// Return list of groups from the first school in the given list
function get_dropdown_groups($school) {	
	global $wpdb;
	$table_name = $wpdb->prefix."group_list";	
	$info = $wpdb->get_results($wpdb->prepare("SELECT group_name AS name FROM $table_name WHERE school_name = %s", $school));
	return $info;	
}


// Convert a list to an option string for a drop-down menu
function to_dropdown($info, $selected) {
	$list = "";
	if (!is_array($info)) return $list;
	foreach ($info as $item) {
		$select = ($selected == $item->name) ? 'selected' : '';
		$list .= '<option '.$select.'>'.$item->name.'</option>';
	}
	return $list;
}


// Ajax function to update the group filter list when the school item changes
function set_group_list() {
  if (isset($_POST['selected_school'])) {  
		$info = get_dropdown_groups($_POST['selected_school']);
		$list = to_dropdown($info, false);
	}
	echo $list;

	die();
}
add_action('wp_ajax_set_group_list', 'set_group_list');


// Update the author and editor role capabilities here.
// You can find a list of them at http://codex.wordpress.org/Roles_and_Capabilities
function update_roles($blog_id) {
	global $wpdb, $wp_user_roles;

	switch_to_blog($blog_id);

	$author_role = get_role('author');
	$author_role->add_cap('edit_theme_options'); // Change widegts, colours and theme option - this is toggled by 'Customize' in the blog list

	$author_role->add_cap('manage_options'); // Change the blog name and the way posts are displayed
	
	$author_role->add_cap('delete_pages'); // Allow pages to be created and removed
	$author_role->add_cap('delete_published_pages');
	$author_role->add_cap('edit_pages');
	$author_role->add_cap('publish_pages');

	restore_current_blog();
}
add_action('wpmu_new_blog', 'update_roles');


function remove_dashboard_boxes() {
	remove_meta_box( 'dashboard_right_now', 'dashboard', 'core' );
	remove_meta_box( 'dashboard_incoming_links', 'dashboard', 'core' );
	remove_meta_box( 'dashboard_plugins', 'dashboard', 'core' );
	remove_meta_box( 'dashboard_quick_press', 'dashboard', 'core' );
	remove_meta_box( 'dashboard_recent_drafts', 'dashboard', 'core' );
	remove_meta_box( 'dashboard_recent_comments', 'dashboard', 'core' );
	remove_meta_box( 'dashboard_primary', 'dashboard', 'core' );
	remove_meta_box( 'dashboard_secondary', 'dashboard', 'core' );
}
add_action('admin_menu', 'remove_dashboard_boxes' );


// Add the javascript ajax script that updates the group filter list
function blog_builder_scripts() {
    wp_enqueue_script( 'eb_filter', plugins_url().'/eb-enquiryblogbuilder/filter.js', array('jquery'));
    wp_enqueue_script( 'eb_member', plugins_url().'/eb-enquiryblogbuilder/member.js', array('jquery'));
}

function eb_plugin_menu() {
		// Add the site admin config page
		if (function_exists('is_network_admin')) {
			//3.1+
			add_submenu_page('settings.php', 'Enquiry Blog Builder', 'Enquiry Blog Builder', 'manage_sites', 'enquiryblogger_blog_creation', 'eb_settings_page');
		} else {
			// -3.1
			add_submenu_page('wpmu-admin.php', 'Enquiry Blog Builder', 'Enquiry Blog Builder', 'manage_sites', 'enquiryblogger_blog_creation', 'eb_settings_page');
		}
		blog_builder_scripts();
}
add_action('network_admin_menu', 'eb_plugin_menu');

	
// When activated, create the group tables in the database to store each group and their members
function blog_builder_init() {
	global $wpdb;

	switch_to_blog(1);
	$table_name = $wpdb->prefix."group_list";

	if($wpdb->get_var("show tables like '$table_name'") != $table_name) {

		$sql = "CREATE TABLE IF NOT EXISTS " . $table_name . " (
			id mediumint(9) NOT NULL AUTO_INCREMENT,
			school_name varchar(255) NOT NULL,
			group_name varchar(255) NOT NULL,
			time datetime NOT NULL,
			PRIMARY KEY (id)
		);";

		$wpdb->query($sql);
	}

	$table_name = $wpdb->prefix."group_members";

	if($wpdb->get_var("show tables like '$table_name'") != $table_name) {

		$sql = "CREATE TABLE IF NOT EXISTS " . $table_name . " (
			group_id mediumint(9) NOT NULL,
			user_id bigint(20) NOT NULL,
			blog_id bigint(20) NOT NULL,
			leader tinyint(2) NOT NULL
		);";

		$wpdb->query($sql);
	}

	restore_current_blog();
}
add_action('init', 'blog_builder_init');

// When a blog is deleted, remove it from the member table. Also remove the group if it has no blogs left.
function cleanOutBlogs($blog_id) {
global $wpdb;

		switch_to_blog(1);
		$table_name = $wpdb->prefix."group_members";
		$group_id = $wpdb->get_var($wpdb->prepare("SELECT group_id FROM $table_name WHERE blog_id = %d", $blog_id));		
		
		if (!empty($group_id)) {
			$wpdb->query($wpdb->prepare("DELETE FROM $table_name WHERE blog_id = %d", $blog_id));
			$count = $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM $table_name WHERE group_id = %d", $group_id));			
			if ($count == 0) {
				$table_name = $wpdb->prefix."group_list";
				$wpdb->query($wpdb->prepare("DELETE FROM $table_name WHERE id = %d", $group_id));
			}
		}
		restore_current_blog();
}
add_action('delete_blog', 'cleanOutBlogs');

// [image name="image_name"]
function image_shortcode( $atts ) {
	extract( shortcode_atts( array(
		'name' => 'something',
	), $atts ) );

	return '<img src="'.get_stylesheet_directory_uri().'/'.$name.'">';
}
add_shortcode( 'image', 'image_shortcode');


?>