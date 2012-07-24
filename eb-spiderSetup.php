<?php


// Report a problem with the file upload
function file_error($file, $error) {
	wp_die( __('Could not upload the file "'.$file['name']. '". '.$error));
}


// Return $numPoints coordinates of points in a circle with radius $distance 
// offset from a point in the centre of a square $width x $width
function getPoints($distance, $width) {
	$points = array();
	$numPoints = 7;
	$imageSize = $width / 2;
	
	$step = 360 / $numPoints;
	for ($i = 0; $i < $numPoints; $i++) {
		$angle = deg2rad(180 - $i * $step); // anticlockwise from the top.
		$points[] = array(intval($imageSize + $distance*sin($angle)), intval($imageSize + $distance*cos($angle)) );
	}
	
	return ($points);
}


// Return the imagemap for the spider diagram
function buildSpiderMap($group_id) {

	$points = getPoints(120, 350);
	$width = 80;
	$height = 60;
	
	$fullurl = "http" . ((!empty($_SERVER['HTTPS'])) ? "s" : "") . "://".$_SERVER['SERVER_NAME'].$_SERVER['REQUEST_URI'];
  $baseurl = explode("?", $fullurl); 
  $page = (isset($_GET['page'])) ? 'page='.$_GET['page'].'&' : '';
	$group = 'group_id='.$group_id.'&';
	
	$categories = array("Changing & Learning", "Learning Relationships", "Strategic Awareness", "Resilience", "Creativity", "Meaning Making", "Critical Curiosity");

	$i = 0;
	foreach ($points as $point) {
		list($x_pos, $y_pos) = $point;
		$label = "Click to edit image for ".$categories[$i];
		$url = $baseurl[0].'?'.$page.$group.'image_id='.$i;
		$map = $map.'<area shape="rect" coords="'.($x_pos-($width/2)).', '.($y_pos-($height/2)).', '.($x_pos+($width/2)).', '.($y_pos+($height/2)).'" alt="'.$label.'" title="'.$label.'" href="'.$url.'" />';
		$i++;
	}
	
	$map = '<map name="spidermap">'.$map.'</map>';
	
	return $map;	
}


// Rebuild the master image for the group required
function rebuildSpider($group_id) {
	global $wpdb;

	switch_to_blog(1);
	$table_name = $wpdb->prefix."group_spider";
	$spider_info = $wpdb->get_row($wpdb->prepare("SELECT * FROM $table_name WHERE group_id = %s", $group_id));
	restore_current_blog();

	$points = getPoints(120, 350);

	$filename = dirname(__FILE__).'/spiderGroupBackground.jpg';

	$work_img = imagecreatefromjpeg( $filename );
	if (!$work_img) wp_die( __('Cannot build composite spider image.') );

	for ($i = 0; $i < 7; $i++) {
		$imageName = 'image_'.$i;
		list($img, $width, $height) = buildSpiderImage($spider_info->$imageName);
		list($x_pos, $y_pos) = $points[$i];
		if ($img) {
			imagecopy($work_img, $img, $x_pos-($width/2), $y_pos-($height/2), 0, 0, $width, $height);
			imagedestroy($img);
		}
	}

	imagejpeg( $work_img, dirname(__FILE__).'/spiderBackground_'.$group_id.'.jpg');
	imagedestroy( $work_img );
}


// Return a reference to the given filename, or to a new image
function buildSpiderImage($filename) {
	$image = NULL;
	
	if ($filename) {
		$filename = $_SERVER['DOCUMENT_ROOT'].$filename;
		if (file_exists($filename)) {
			$filetype = exif_imagetype($filename);
			switch ($filetype) {
				case IMAGETYPE_PNG: $image = imagecreatefrompng( $filename);  break;
				case IMAGETYPE_JPEG: $image = imagecreatefromjpeg( $filename); break;
				case IMAGETYPE_GIF: $image = imagecreatefromgif( $filename); break;
				default: wp_die( __('file type not recognised')); break;
			}
			list($width, $height) = getimagesize($filename);
		}
	} else {
		$width = 80;
		$height = 60;
		$image = imagecreatetruecolor($width, $height);		
		$blank_col = imagecolorallocate($image, 0xee, 0xee, 0xee);
		imagefilledrectangle($image, 0, 0, $width, $height, $blank_col);
	}
	
	return (array($image, $width, $height));
}


// Return the path for the particular image in the given group. This could be null
function getSpiderImage($group_id, $image_id) {
	global $wpdb;

	switch_to_blog(1);
	$table_name = $wpdb->prefix."group_spider";
	$thumbnail = $wpdb->get_var($wpdb->prepare("SELECT image_%d FROM $table_name WHERE group_id = %d", $image_id, $group_id));
	if ($thumbnail) $thumbnail = (dirname(get_site_url()).'/'.$thumbnail);
	restore_current_blog();
	
	return $thumbnail;
}


// Resize and store the image for the correct group and image number (0-6)
function updateSpiderImages($group_id, $image_id, $filename) {
	global $wpdb;

	// check $image_id is in the range 0-6
	if (!is_int($group_id) || !is_int($image_id) || ($image_id < 0) || ($image_id > 6)) {
		wp_die( __('Invalid values sent to update the group_spider table.') );
	}

	if ($filename) {	
		$mimes = array('jpg'=>'image/jpg','jpeg'=>'image/jpeg','png'=>'image/png');
		$upload_overrides = array( 'test_form' => false, 'mimes' => $mimes, 'upload_error_handler' => 'file_error' ); 
		$uploaded_file = wp_handle_upload($filename, $upload_overrides);
	
		// If the wp_handle_upload call returned OK, resize the image
		if(isset($uploaded_file['file'])) {

			$tmp_file = $uploaded_file['file'];
			$old_temp = $tmp_file;
			$width = 80;
			$height = 60;
			$tmp_file = image_resize( $tmp_file, $width, $height, true, 'thumb');

			if ( ! is_wp_error($tmp_file) ) {
				unlink($old_temp);
			} else {
				$tmp_file = $old_temp;
			}

			$info = pathinfo($uploaded_file['url']);
			$dir = str_replace(dirname(get_site_url()), '', $info['dirname']);
			$ext = $info['extension'];
			$basename = wp_basename($tmp_file, ".$ext");			
			$thumbnail = "{$dir}/{$basename}.{$ext}";
		}
	}
	
	switch_to_blog(1);
	$table_name = $wpdb->prefix."group_spider";
	$id_exists = $wpdb->get_var($wpdb->prepare("SELECT group_id FROM $table_name WHERE group_id = %s", $group_id));
	if (isset($id_exists)) {
		$wpdb->update($table_name, array( 'image_'.$image_id => $thumbnail), array( 'group_id' => $group_id) );
	} else {
		$wpdb->insert($table_name, array( 'group_id' => $group_id, 'image_'.$image_id => $thumbnail) );
	}
	restore_current_blog();
}


// Return the details of the requested group, if it exists
function getGroupDetails($group_id) {
	global $wpdb;

	switch_to_blog(1);
	$table_name = $wpdb->prefix."group_list";
	$groupDetails = $wpdb->get_row($wpdb->prepare("SELECT * FROM $table_name WHERE id = %d", $group_id));
	restore_current_blog();
	
	return ($groupDetails);
}


// Update the main image based on the ones selected
function eb_spider_page() {
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

	// Group and image selected for editing
	if (isset($_GET['group_id'])) { // got here from clicking on an image
		$group_id = intval($_GET['group_id']);
		if (!getGroupDetails($group_id)) wp_die( __('Group does not exist.') );
	}
	
	if (isset($_GET['image_id'])) {
		$image_id =  intval($_GET['image_id']);		
		if ($image_id < 0) $image_id = 0;
		if ($image_id >= 7) $image_id = 6;	
	}

	// Image cleared	
	if (isset($_POST['clear_image']) && $_POST['clear_image'] == 'clear_image' ) {
		$group_id = intval($_POST['group_id']);
		$image_id = intval($_POST['image_id']);
		updateSpiderImages($group_id, $image_id, null);			
	}
	
	// Image updated
	if (isset($_POST['update_image']) && $_POST['update_image'] == 'update_image' ) {
		$group_id = intval($_POST['group_id']);
		$image_id = intval($_POST['image_id']);
		updateSpiderImages($group_id, $image_id, $_FILES['userfile']);
	}
	
	rebuildSpider($group_id);
	
	$fullurl = "http" . ((!empty($_SERVER['HTTPS'])) ? "s" : "") . "://".$_SERVER['SERVER_NAME'].$_SERVER['REQUEST_URI'];
  $baseurl = explode("?", $fullurl); 
  $page = (isset($_GET['page'])) ? 'page='.$_GET['page'] : '';
	$actionPage = $baseurl[0].'?'.$page;
	
	$thumbnail = getSpiderImage($group_id, $image_id);
	
	include_once('eb-spiderSetupView.php');
}


// When activated, create the group tables in the database to store the images for each group
function spider_setup_init() {
	global $wpdb;

	switch_to_blog(1);
	$table_name = $wpdb->prefix."group_spider";

	if($wpdb->get_var("show tables like '$table_name'") != $table_name) {

		$sql = "CREATE TABLE IF NOT EXISTS " . $table_name . " (
			group_id mediumint(9) NOT NULL,
			image_0 longtext,
			image_1 longtext,
			image_2 longtext,
			image_3 longtext,
			image_4 longtext,
			image_5 longtext,
			image_6 longtext,
			PRIMARY KEY (group_id)
		);";

		$wpdb->query($sql);
	}

	restore_current_blog();
}
add_action('init', 'spider_setup_init');


// Add the javascript ajax script that updates the group filter list
function spider_setup_scripts() {
    wp_enqueue_script( 'eb_filter', plugins_url().'/eb-enquiryblogbuilder/filter.js', array('jquery'));
}

// Add the menu item to the admin system
function eb_spider_menu() {
		// Add the site admin config page
		if (function_exists('is_network_admin')) {
			//3.1+
			add_submenu_page('settings.php', 'Enquiry Blog Spider', 'Enquiry Blog Spider', 'manage_sites', 'enquiryblogger_spider_setup', 'eb_spider_page');
		} else {
			// -3.1
			add_submenu_page('wpmu-admin.php', 'Enquiry Blog Spider', 'Enquiry Blog Spider', 'manage_sites', 'enquiryblogger_spider_setup', 'eb_spider_page');
		}
		spider_setup_scripts();
}
add_action('network_admin_menu', 'eb_spider_menu');

?>
