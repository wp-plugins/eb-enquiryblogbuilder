<?php


// Update the group header image with the one uploaded
function eb_header_page() {
	//must check that the user has the required capability - could just be an administrator, not a super admin?
	if (!is_super_admin())
	{
		wp_die( __('You do not have sufficient permissions to access this page.') );
	}

	// Filter updated
	if( isset($_POST['update_filter']) && $_POST['update_filter'] == 'update_filter' ) {
		$selected_school = $_POST['selected_school'];
		$selected_group = $_POST['selected_group'];
	}

	if( isset($_POST['update_header']) && $_POST['update_header'] == 'update_header' ) {
		$group_id = intval($_POST['group_id']);
		updateHeader($group_id, $_FILES['userfile']);
	}

	if( isset($_POST['clear_header']) && $_POST['clear_header'] == 'clear_header' ) {
		$group_id = intval($_POST['group_id']);
		updateHeader($group_id, null);
	}

	// Build the drop-down lists
	$schoolInfo = get_dropdown_schools();	
	if (!empty($schoolInfo)) { // If no schools, then no groups created so far

		if (isset($_REQUEST['group_id'])) { // got here from clicking on an image
			$group_id = intval($_REQUEST['group_id']);
			$details = getGroupDetails($group_id);
			if (!$details) wp_die( __('Group does not exist.') );
			$selected_school = $details->school_name;
			$selected_group = $details->group_name;
		}

		$schoolList = to_dropdown($schoolInfo, $selected_school);
		if (empty($selected_school)) $selected_school = $schoolInfo[0]->name;
		
		$groupInfo = get_dropdown_groups($selected_school);
		$groupList = to_dropdown($groupInfo, $selected_group);

		$current_school = empty($selected_school) ? $schoolInfo[0]->name : $selected_school;
		$current_group = empty($selected_group) ? $groupInfo[0]->name : $selected_group;
	}

	$group_id = intval(get_group_id($current_school, $current_group, false));
	$thumbnail = getHeaderImage($group_id);
	
	include_once('eb-headerSetupView.php');
}


// Add the javascript ajax script that updates the group filter list
function header_setup_scripts() {
    wp_enqueue_script( 'eb_filter', plugins_url().'/eb-enquiryblogbuilder/filter.js', array('jquery'));
}

// Add the menu item to the admin system
function eb_header_menu() {
		// Add the site admin config page
		if (function_exists('is_network_admin')) {
			//3.1+
			add_submenu_page('settings.php', 'Enquiry Blog Header', 'Enquiry Blog Header', 'manage_sites', 'enquiryblogger_image_setup', 'eb_header_page');
		} else {
			// -3.1
			add_submenu_page('wpmu-admin.php', 'Enquiry Blog Header', 'Enquiry Blog Header', 'manage_sites', 'enquiryblogger_image_setup', 'eb_header_page');
		}
		header_setup_scripts();
}
add_action('network_admin_menu', 'eb_header_menu');


// When activated, create the group tables in the database to store the header image for each group
function header_setup_init() {
	global $wpdb;

	switch_to_blog(1);
	$table_name = $wpdb->prefix."group_header";

	if($wpdb->get_var("show tables like '$table_name'") != $table_name) {

		$sql = "CREATE TABLE IF NOT EXISTS " . $table_name . " (
			group_id mediumint(9) NOT NULL,
			header longtext,
			PRIMARY KEY (group_id)
		);";

		$wpdb->query($sql);
	}

	restore_current_blog();
}
add_action('init', 'header_setup_init');


// Add the image to the group header
function updateHeader($group_id, $filename) {
	global $wpdb;

	if ($filename) {	
		$mimes = array('jpg'=>'image/jpg','jpeg'=>'image/jpeg','png'=>'image/png');
		$upload_overrides = array( 'test_form' => false, 'mimes' => $mimes, 'upload_error_handler' => 'file_error' ); 
		$uploaded_file = wp_handle_upload($filename, $upload_overrides);
	
		// If the wp_handle_upload call returned OK, resize the image
		if(isset($uploaded_file['file'])) {
			$info = pathinfo($uploaded_file['url']);
			$dir = str_replace(dirname(get_site_url()), '', $info['dirname']);
			$ext = $info['extension'];
			$basename = wp_basename($uploaded_file['file'], ".$ext");			
			$filename = "{$dir}/{$basename}.{$ext}";
		}
	}
	
	switch_to_blog(1);
	$table_name = $wpdb->prefix."group_header";
	$id_exists = $wpdb->get_var($wpdb->prepare("SELECT group_id FROM $table_name WHERE group_id = %s", $group_id));
	if (isset($id_exists)) {
		$wpdb->update($table_name, array( 'header' => $filename), array( 'group_id' => $group_id) );
	} else {
		$wpdb->insert($table_name, array( 'group_id' => $group_id, 'header' => $filename) );
	}
	
	$table_name = $wpdb->prefix."group_members";
	$blog_list = $wpdb->get_results($wpdb->prepare("SELECT blog_id FROM $table_name WHERE group_id = %s", $group_id));

	restore_current_blog();
	
	foreach ($blog_list as $blog_info) {
		update_suffusion_settings(intval($blog_info->blog_id));
	}
}

// Return an array of blog options that set the image header for this blog
// which will be different depending on which group it is a member of.
function getBlogHeaderOptions($blog_id) {
	global $wpdb;

	$options = array(
		'suf_header_style_setting' => 'theme',
		'suf_header_background_image' => '',
  	'option-date' => date(get_option('date_format').' '.get_option('time_format'))
	);
	
	switch_to_blog(1);
	$table_name = $wpdb->prefix."group_members";
	$group_id = $wpdb->get_var($wpdb->prepare("SELECT group_id FROM $table_name WHERE blog_id = %d", $blog_id) );
	if (empty($group_id)) return $options;	
	restore_current_blog();
	$thumbnail = getHeaderImage($group_id);
	
	$options = array(
		'suf_header_style_setting' => 'custom',
		'suf_header_image_type' => 'image',
		'suf_header_background_image' => $thumbnail,
		'suf_header_background_repeat' => 'no-repeat',
  	'suf_header_background_position' => 'top right',
  	'option-date' => date(get_option('date_format').' '.get_option('time_format'))
	);
	return $options;
	
}

// Return the path for the particular image in the given group. This could be null
function getHeaderImage($group_id) {
	global $wpdb;

	switch_to_blog(1);
	$table_name = $wpdb->prefix."group_header";
	$thumbnail = $wpdb->get_var($wpdb->prepare("SELECT header FROM $table_name WHERE group_id = %d", $group_id));
	if ($thumbnail) $thumbnail = (dirname(get_site_url()).'/'.$thumbnail);
	restore_current_blog();
	
	return $thumbnail;
}


// Add the right set of widgets to the named sidebar 
function install_widget($sidebar, $widget_id) {
	$sidebars_widgets = wp_get_sidebars_widgets();

	if ( empty( $sidebars_widgets ) ) $sidebars_widgets = wp_get_widget_defaults();
	if ( empty( $sidebars_widgets[$sidebar] ) ) $sidebars_widgets[$sidebar] = array();

	if(!in_array($widget_id, $sidebars_widgets[$sidebar])) {
		$sidebars_widgets[$sidebar][] = $widget_id;
		wp_set_sidebars_widgets( $sidebars_widgets );
	}
}

// Clears all the widgets from a named sidebar ready for adding new ones.
// The sidebar name is taken from the particular theme that is in use.
function clear_sidebar($sidebar) {
	$sidebars_widgets = wp_get_sidebars_widgets();
	if ( empty( $sidebars_widgets ) ) return; // no widgets anywhere
	if ( empty( $sidebars_widgets[$sidebar] ) ) return; // no widgets in that sidebar	
	$sidebars_widgets[$sidebar] = array(); // clear required sidebar and store.
	wp_set_sidebars_widgets( $sidebars_widgets );
}


// Use sidebar names from the suffusion theme, and hardcoded plugin paths. Oooh er.
function set_default_widgets($blog_id){
	switch_to_blog($blog_id);

	// Clears the blog sidebars and adds widgets.
	// This is tied to the suffusion theme so has been commented out. The lines can be added and adjusted to fit your
	// theme's sidebars numbers. Also, use the "New Blog Defaults" plugin to set the default theme and categories for each new blog.
	// This section also assumes the named plugins are loaded : enquiry_spider_widget, enquiry_spiral_widget and mood_view_widget.
	
	clear_sidebar('sidebar-18'); // Top wide sidebar
	clear_sidebar('sidebar-1'); // Left narrow sidebar
	clear_sidebar('sidebar-2'); // Right narrow sidebar
	clear_sidebar('sidebar-19'); // Bottom wide sidebar
	install_widget('sidebar-1', 'enquiry_spider_widget');
	install_widget('sidebar-1', 'enquiry_spiral_widget');
	install_widget('sidebar-1', 'mood_view_widget');

	restore_current_blog();
}
add_action('wpmu_new_blog', 'set_default_widgets', 200, 1);


// Replace the blog header options with ones from the group
function update_suffusion_settings($blog_id) {
	global $wpdb, $suffusion_reevaluate_styles;

	switch_to_blog($blog_id);		
	$suffusion_blog_options = get_option('suffusion_options');
	if ($suffusion_blog_options ) {
		$header_options = getBlogHeaderOptions($blog_id);
		
		$suffusion_blog_options = array_merge($suffusion_blog_options, $header_options);
		$option = 'suffusion_options';
		$value = maybe_serialize( $suffusion_blog_options );
		$autoload = 'yes';
		$result = $wpdb->query( $wpdb->prepare( "INSERT INTO `$wpdb->options` (`option_name`, `option_value`, `autoload`) VALUES (%s, %s, %s) ON DUPLICATE KEY UPDATE `option_name` = VALUES(`option_name`), `option_value` = VALUES(`option_value`), `autoload` = VALUES(`autoload`)", $option, $value, $autoload ) );
		$suffusion_reevaluate_styles = true;
	}
	
	restore_current_blog();
}


// Suffusion allows saving and restoring all the theme options. A single settings file (suffusion-options.php) 
// has been created that is applied to every new blog created. This ensures they all look the same.
// Obviously it will only work if the suffusion theme has been installed and applied to the blog.
function import_suffusion_settings($blog_id) {
	global $wpdb, $suffusion_unified_options, $suffusion_reevaluate_styles;

	switch_to_blog($blog_id);
		$filename = plugin_dir_path(__FILE__)."suffusion-options.php";
		
		if (file_exists($filename)) {
			include ($filename);

			$suffusion_unified_options = array_merge($suffusion_unified_options, $suffusion_exported_options);			
			
			$option = 'suffusion_options';
			$value = maybe_serialize( $suffusion_unified_options );
			$autoload = 'yes';
			$result = $wpdb->query( $wpdb->prepare( "INSERT INTO `$wpdb->options` (`option_name`, `option_value`, `autoload`) VALUES (%s, %s, %s) ON DUPLICATE KEY UPDATE `option_name` = VALUES(`option_name`), `option_value` = VALUES(`option_value`), `autoload` = VALUES(`autoload`)", $option, $value, $autoload ) );
			$suffusion_reevaluate_styles = true;
		}

	restore_current_blog();
}
add_action('wpmu_new_blog', 'import_suffusion_settings', 220, 1);


// Add a page that lists all the members of the group this blog belongs to.
// The page itself has no content, but the page template in the suffusion-child theme supplies it all
function add_group_list_page($blog_id, $user_id) {
global $wpdb;

	switch_to_blog($blog_id);

	$post_title = "This Enquiry Group";
	$post_content = "An auto generated page";

	if (get_page_by_title($post_title) == null) { // a page with this title doesn't already exist

		$now_gmt = gmdate('Y-m-d H:i:s');
		$now = date('Y-m-d H:i:s');
		$post_guid = get_option('home'). '/?page_id=3'; // Assume this is the third post for a new blog. Not ideal
		
		$wpdb->insert( $wpdb->posts, array(
								'post_author' => $user_id,
								'post_date' => $now,
								'post_date_gmt' => $now_gmt,
								'post_content' => $post_content,
								'post_excerpt' => '',
								'post_title' => $post_title,
								/* translators: Default page slug */
								'post_name' => 'my_group',
								'post_modified' => $now,
								'post_modified_gmt' => $now_gmt,
								'guid' => $post_guid,
								'post_type' => 'page',
								'to_ping' => '',
								'pinged' => '',
								'ping_status' => 'closed',
								'comment_status' => 'closed',
								'post_content_filtered' => ''
								));

		$wpdb->insert( $wpdb->postmeta, array( 'post_id' => 3, 'meta_key' => '_wp_page_template', 'meta_value' => 'group_list.php' ) );
	}
	
	
	restore_current_blog();
}
add_action('wpmu_new_blog', 'add_group_list_page', 210, 2);

?>