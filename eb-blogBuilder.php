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

/*  Copyright 2010  Geoff Austin  (email : Geoff.Austin@codejuggler.com)

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


// Change the author and editor role capabilities here.
// You can find a list of them at http://codex.wordpress.org/Roles_and_Capabilities
function update_roles($id) {
	global $wpdb, $wp_user_roles;

	switch_to_blog($id);

	$author_role = get_role('author');
	$author_role->add_cap('edit_theme_options');
	$author_role->add_cap('unfiltered_html');
	$author_role->add_cap('manage_options');
	$author_role->add_cap('delete_pages');
	$author_role->add_cap('edit_pages');
	$author_role->add_cap('publish_pages');

	$editor_role = get_role('editor');
	$editor_role->remove_cap('manage_categories');
	$editor_role->add_cap('edit_theme_options');
	$editor_role->add_cap('unfiltered_html');
	$editor_role->add_cap('manage_options');

	$editor_role->add_cap('delete_private_pages');
	$editor_role->add_cap('edit_private_pages');
	$editor_role->add_cap('publish_private_pages');

	restore_current_blog();
}

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


// Exactly the same as wpmu_create_blog in ms-functions.php, except that we add the user as an Author, not Administrator.
// A blog_id is returned for an existing blog rather than an error.
function eb_create_blog($domain, $path, $title, $user_id, $meta = '', $site_id = 1, $is_teacher_blog = 'no') {
	global $wpdb, $wp_queries;

	$domain = preg_replace( '/\s+/', '', sanitize_user( $domain, true ) );

	if ( is_subdomain_install() )
		$domain = str_replace( '@', '', $domain );

	$title = strip_tags( $title );
	$user_id = (int) $user_id;

	if ( empty($path) )
		$path = '/';
	
	// Check if the domain has been used already. Return the blog id rather than an error.
	if ($blog_id = domain_exists($domain, $path, $site_id) )
		return $blog_id; // new WP_Error('blog_taken', __('Site already exists.'));

	if ( !defined('WP_INSTALLING') )
		define( 'WP_INSTALLING', true );

	if ( ! $blog_id = insert_blog($domain, $path, $site_id) )
		return new WP_Error('insert_blog', __('Could not create site.'));

	switch_to_blog($blog_id);

	// Need to include this or else only the first blog will have the tables created.
	// $wp_queries needs to be redefined to pick up the changing wpdb->terms item each time the function is called.

	$wp_queries = "CREATE TABLE $wpdb->terms (
	 term_id bigint(20) unsigned NOT NULL auto_increment,
	 name varchar(200) NOT NULL default '',
	 slug varchar(200) NOT NULL default '',
	 term_group bigint(10) NOT NULL default 0,
	 PRIMARY KEY  (term_id),
	 UNIQUE KEY slug (slug),
	 KEY name (name)
	) $charset_collate;
	CREATE TABLE $wpdb->term_taxonomy (
	 term_taxonomy_id bigint(20) unsigned NOT NULL auto_increment,
	 term_id bigint(20) unsigned NOT NULL default 0,
	 taxonomy varchar(32) NOT NULL default '',
	 description longtext NOT NULL,
	 parent bigint(20) unsigned NOT NULL default 0,
	 count bigint(20) NOT NULL default 0,
	 PRIMARY KEY  (term_taxonomy_id),
	 UNIQUE KEY term_id_taxonomy (term_id,taxonomy),
	 KEY taxonomy (taxonomy)
	) $charset_collate;
	CREATE TABLE $wpdb->term_relationships (
	 object_id bigint(20) unsigned NOT NULL default 0,
	 term_taxonomy_id bigint(20) unsigned NOT NULL default 0,
	 term_order int(11) NOT NULL default 0,
	 PRIMARY KEY  (object_id,term_taxonomy_id),
	 KEY term_taxonomy_id (term_taxonomy_id)
	) $charset_collate;
	CREATE TABLE $wpdb->commentmeta (
	  meta_id bigint(20) unsigned NOT NULL auto_increment,
	  comment_id bigint(20) unsigned NOT NULL default '0',
	  meta_key varchar(255) default NULL,
	  meta_value longtext,
	  PRIMARY KEY  (meta_id),
	  KEY comment_id (comment_id),
	  KEY meta_key (meta_key)
	) $charset_collate;
	CREATE TABLE $wpdb->comments (
	  comment_ID bigint(20) unsigned NOT NULL auto_increment,
	  comment_post_ID bigint(20) unsigned NOT NULL default '0',
	  comment_author tinytext NOT NULL,
	  comment_author_email varchar(100) NOT NULL default '',
	  comment_author_url varchar(200) NOT NULL default '',
	  comment_author_IP varchar(100) NOT NULL default '',
	  comment_date datetime NOT NULL default '0000-00-00 00:00:00',
	  comment_date_gmt datetime NOT NULL default '0000-00-00 00:00:00',
	  comment_content text NOT NULL,
	  comment_karma int(11) NOT NULL default '0',
	  comment_approved varchar(20) NOT NULL default '1',
	  comment_agent varchar(255) NOT NULL default '',
	  comment_type varchar(20) NOT NULL default '',
	  comment_parent bigint(20) unsigned NOT NULL default '0',
	  user_id bigint(20) unsigned NOT NULL default '0',
	  PRIMARY KEY  (comment_ID),
	  KEY comment_approved (comment_approved),
	  KEY comment_post_ID (comment_post_ID),
	  KEY comment_approved_date_gmt (comment_approved,comment_date_gmt),
	  KEY comment_date_gmt (comment_date_gmt),
	  KEY comment_parent (comment_parent)
	) $charset_collate;
	CREATE TABLE $wpdb->links (
	  link_id bigint(20) unsigned NOT NULL auto_increment,
	  link_url varchar(255) NOT NULL default '',
	  link_name varchar(255) NOT NULL default '',
	  link_image varchar(255) NOT NULL default '',
	  link_target varchar(25) NOT NULL default '',
	  link_description varchar(255) NOT NULL default '',
	  link_visible varchar(20) NOT NULL default 'Y',
	  link_owner bigint(20) unsigned NOT NULL default '1',
	  link_rating int(11) NOT NULL default '0',
	  link_updated datetime NOT NULL default '0000-00-00 00:00:00',
	  link_rel varchar(255) NOT NULL default '',
	  link_notes mediumtext NOT NULL,
	  link_rss varchar(255) NOT NULL default '',
	  PRIMARY KEY  (link_id),
	  KEY link_visible (link_visible)
	) $charset_collate;
	CREATE TABLE $wpdb->options (
	  option_id bigint(20) unsigned NOT NULL auto_increment,
	  blog_id int(11) NOT NULL default '0',
	  option_name varchar(64) NOT NULL default '',
	  option_value longtext NOT NULL,
	  autoload varchar(20) NOT NULL default 'yes',
	  PRIMARY KEY  (option_id),
	  UNIQUE KEY option_name (option_name)
	) $charset_collate;
	CREATE TABLE $wpdb->postmeta (
	  meta_id bigint(20) unsigned NOT NULL auto_increment,
	  post_id bigint(20) unsigned NOT NULL default '0',
	  meta_key varchar(255) default NULL,
	  meta_value longtext,
	  PRIMARY KEY  (meta_id),
	  KEY post_id (post_id),
	  KEY meta_key (meta_key)
	) $charset_collate;
	CREATE TABLE $wpdb->posts (
	  ID bigint(20) unsigned NOT NULL auto_increment,
	  post_author bigint(20) unsigned NOT NULL default '0',
	  post_date datetime NOT NULL default '0000-00-00 00:00:00',
	  post_date_gmt datetime NOT NULL default '0000-00-00 00:00:00',
	  post_content longtext NOT NULL,
	  post_title text NOT NULL,
	  post_excerpt text NOT NULL,
	  post_status varchar(20) NOT NULL default 'publish',
	  comment_status varchar(20) NOT NULL default 'open',
	  ping_status varchar(20) NOT NULL default 'open',
	  post_password varchar(20) NOT NULL default '',
	  post_name varchar(200) NOT NULL default '',
	  to_ping text NOT NULL,
	  pinged text NOT NULL,
	  post_modified datetime NOT NULL default '0000-00-00 00:00:00',
	  post_modified_gmt datetime NOT NULL default '0000-00-00 00:00:00',
	  post_content_filtered text NOT NULL,
	  post_parent bigint(20) unsigned NOT NULL default '0',
	  guid varchar(255) NOT NULL default '',
	  menu_order int(11) NOT NULL default '0',
	  post_type varchar(20) NOT NULL default 'post',
	  post_mime_type varchar(100) NOT NULL default '',
	  comment_count bigint(20) NOT NULL default '0',
	  PRIMARY KEY  (ID),
	  KEY post_name (post_name),
	  KEY type_status_date (post_type,post_status,post_date,ID),
	  KEY post_parent (post_parent),
	  KEY post_author (post_author)
	) $charset_collate;
	CREATE TABLE $wpdb->users (
	  ID bigint(20) unsigned NOT NULL auto_increment,
	  user_login varchar(60) NOT NULL default '',
	  user_pass varchar(64) NOT NULL default '',
	  user_nicename varchar(50) NOT NULL default '',
	  user_email varchar(100) NOT NULL default '',
	  user_url varchar(100) NOT NULL default '',
	  user_registered datetime NOT NULL default '0000-00-00 00:00:00',
	  user_activation_key varchar(60) NOT NULL default '',
	  user_status int(11) NOT NULL default '0',
	  display_name varchar(250) NOT NULL default '',
	  PRIMARY KEY  (ID),
	  KEY user_login_key (user_login),
	  KEY user_nicename (user_nicename)
	) $charset_collate;
	CREATE TABLE $wpdb->usermeta (
	  umeta_id bigint(20) unsigned NOT NULL auto_increment,
	  user_id bigint(20) unsigned NOT NULL default '0',
	  meta_key varchar(255) default NULL,
	  meta_value longtext,
	  PRIMARY KEY  (umeta_id),
	  KEY user_id (user_id),
	  KEY meta_key (meta_key)
	) $charset_collate;";

	install_blog($blog_id, $title);
	wp_install_defaults($user_id);

	add_user_to_blog($blog_id, $user_id, 'author');

	if ( is_array($meta) ) foreach ($meta as $key => $value) {
		if ( $key == 'public' || $key == 'archived' || $key == 'mature' || $key == 'spam' || $key == 'deleted' || $key == 'lang_id' )
			update_blog_status( $blog_id, $key, $value );
		else
			update_option( $key, $value );
	}

	add_option( 'WPLANG', get_site_option( 'WPLANG' ) );
	update_option( 'blog_public', (int)$meta['public'] );

	if ( !is_super_admin() && get_user_meta( $user_id, 'primary_blog', true ) == get_site_option( 'dashboard_blog', 1 ) )
		update_user_meta( $user_id, 'primary_blog', $blog_id );
	
	add_option('is_teacher_blog', $is_teacher_blog); // so we know whether to activate plugs for this blog later
	
	restore_current_blog();
	do_action( 'wpmu_new_blog', $blog_id, $user_id, $domain, $path, $site_id, $meta );

	return $blog_id;
}


function add_teacher($teacherText, $schoolPrefix, $groupName, $group_id) {
global $wpdb, $current_site;

				switch_to_blog(1);
				$mydomain = $current_site->domain;
				$mypath = $current_site->path;
				restore_current_blog();

				// Create the teacher user and blog
				$teacher_blogtitle = preg_replace('/[^- [:alpha:]*]/', '', $teacherText); // can have a-Z, space and hyphen
				$teacher_blogname = strtolower(preg_replace('/[^[:alpha:]*]/', '', $schoolPrefix.$groupName.$teacherText)); // can have a-z
				$teacher_username = strtolower(preg_replace('/[^[:alpha:]*]/', '', $schoolPrefix.$teacherText)); // can have a-z
				$teacher_username = sanitize_user($teacher_username, true);

				if (!empty($teacher_blogtitle)) {
					$teacher_blogtitle .= "'s blog";

					$result = wpmu_validate_blog_signup($teacher_blogname, $teacher_blogtitle, $user = '');
					extract($result);
					if ($errors->get_error_code()) $infoMessage .= 'Name already exists : '.$teacher_blogname.'<br />';
					$teacher_user_id = username_exists( $teacher_username );
					if ( !$teacher_user_id ) $teacher_user_id = wpmu_create_user( $teacher_username, 'teacher', $teacher_username.'@example.com' );
					
					$id = eb_create_blog( $mydomain, $mypath.$teacher_blogname.'/', $teacher_blogtitle, $teacher_user_id, array( 'public' => 1 ), 1, 'yes');
					if (is_wp_error( $id )) wp_die( $id->get_error_message());
					update_roles($id);
					// Add a page dynamically listing members of the group. Only works if suffusion-child theme used
					// add_group_list_page($id, $teacher_user_id);
					$infoMessage = $teacherText.' - username: <strong>'.$teacher_username.'</strong> password : <strong>teacher</strong><br />';

					switch_to_blog(1);
					// Add member to the group
					$table_name = $wpdb->prefix."group_members";
					$entryExisits = $wpdb->get_var($wpdb->prepare("SELECT group_id FROM $table_name WHERE group_id = %d AND user_id = %d AND blog_id = %d", $group_id, $teacher_user_id, $id));
					if (empty($entryExisits)) {
						$rows_affected = $wpdb->insert( $table_name, array( 'group_id' => $group_id, 'user_id' => $teacher_user_id, 'blog_id' => $id, 'leader' => 1) );
					}
					restore_current_blog();
				}


				return $infoMessage;
}


function add_student($studentText, $schoolPrefix, $groupName, $group_id) {
global $wpdb, $current_site;

				switch_to_blog(1);
				$mydomain = $current_site->domain;
				$mypath = $current_site->path;
				restore_current_blog();

				$student_blogtitle = preg_replace('/[^- [:alpha:]*]/', '', $studentText); // can have a-Z, space and hyphen
				$student_blogname = strtolower(preg_replace('/[^[:alpha:]*]/', '', $schoolPrefix.$groupName.$studentText)); // can have a-z only
				$student_username = strtolower(preg_replace('/[^[:alpha:]*]/', '', $schoolPrefix.$studentText)); // can have a-z only
				$student_username = sanitize_user($student_username, true);

				if (!empty($student_blogtitle)) {;
					$student_blogtitle .= "'s blog";

					$result = wpmu_validate_blog_signup($student_blogname, $student_blogtitle, $user = '');
					extract($result);
					if ($errors->get_error_code()) $infoMessage .= 'Name already exists : '.$student_blogname.'<br />';
					$student_user_id = username_exists( $student_username );
					if ( !$student_user_id ) $student_user_id = wpmu_create_user( $student_username, 'student', $student_username.'@example.com' );

					$id = eb_create_blog( $mydomain, $mypath.$student_blogname.'/', $student_blogtitle, $student_user_id, array( 'public' => 1 ));
					if (is_wp_error( $id )) wp_die( $id->get_error_message());
					update_roles($id);
					// Add a page dynamically listing members of the group. Only works if suffusion-child theme used
					// add_group_list_page($id, $student_user_id);
					$infoMessage = $studentText.' - username: <strong>'.$student_username.'</strong> password : <strong>student</strong><br />';
					// Need this now? if ($teacher_user_id) add_user_to_blog( $id, $teacher_user_id, 'editor' );

					switch_to_blog(1);
					// Add member to the group
					$table_name = $wpdb->prefix."group_members";
					$entryExisits = $wpdb->get_var($wpdb->prepare("SELECT group_id FROM $table_name WHERE group_id = %d AND user_id = %d AND blog_id = %d", $group_id, $student_user_id, $id) );
					if (empty($entryExisits)) {
						$rows_affected = $wpdb->insert( $table_name, array( 'group_id' => $group_id, 'user_id' => $student_user_id, 'blog_id' => $id, 'leader' => 0) );
					}
					restore_current_blog();
				}
				

				return $infoMessage;
}

function eb_settings_page() {

	global $wpdb, $current_site;

	//must check that the user has the required capability
	if (!is_super_admin())
	{
		wp_die( __('You do not have sufficient permissions to access this page.') );
	}

	// variable for the hidden field
	$hidden_field_name = 'eb_blog_builder';

	// See if the user has posted us some information
	// If they did, this hidden field will be set to 'Y'
	if( isset($_POST[ $hidden_field_name ]) && $_POST[ $hidden_field_name ] == 'build_blogs' ) {
		
		$schoolPrefix = sanitize_user(trim($_POST['prefix']));
		$groupName = sanitize_user(trim($_POST['group']));
		$teacherText = trim($_POST['teacher']);
		$studentText = trim($_POST['students']);
		$studentArray = explode(',', $_POST['students']);
		$infoMessage = '';

		if (empty($schoolPrefix) || empty($groupName)) {
			echo '<div class="updated"><p><strong>Both a School and a Group prefix are required</strong></p></div>';
		} else {

			// Check to see if the school+group combination already exists.If not, create it, otherwise, empty the group ready for new members
			$table_name = $wpdb->prefix."group_list";
			$group_id = $wpdb->get_var($wpdb->prepare("SELECT id FROM $table_name WHERE school_name = %s AND group_name = %s", $schoolPrefix, $groupName));

			if (empty($group_id)) { // group does not exist, so create it
				$wpdb->insert($table_name, array( 'school_name' => $schoolPrefix, 'group_name' => $groupName, 'time' => current_time('mysql')) );
				$group_id = $wpdb->insert_id;
			} else { // group exists so remove current members
				$table_name = $wpdb->prefix."group_members";
				$wpdb->query($wpdb->prepare("DELETE FROM $table_name WHERE group_id = %d", $group_id));
			}

			// Create a teacher and their blog
			$infoMessage .= add_teacher($teacherText, $schoolPrefix, $groupName, $group_id);

			// Create each of the student users and blogs
			foreach ($studentArray as $student) {
				$infoMessage .= add_student($student, $schoolPrefix, $groupName, $group_id);
			}

			echo '<div class="updated"><p><strong>Blogs built</strong></p><p>'.$infoMessage.'</p></div>';
		}
	}
	
	if( isset($_POST[ $hidden_field_name ]) && $_POST[ $hidden_field_name ] == 'new_teacher' ) {
		$schoolPrefix = sanitize_user(trim($_POST['teacher_prefix']));
		$groupName = sanitize_user(trim($_POST['teacher_group']));
		$teacherText = trim($_POST['teacher']);
		$infoMessage = '';

		// Check to see if the school+group combination already exists.If not give up
		$table_name = $wpdb->prefix."group_list";
		$group_id = $wpdb->get_var($wpdb->prepare("SELECT id FROM $table_name WHERE school_name = %s AND group_name = %s", $schoolPrefix, $groupName));

		if (empty($group_id)) { // group does not exist
			$infoMessage = "School or Group does not exist, teacher not added.";
		} else {
			$infoMessage = add_teacher($teacherText, $schoolPrefix, $groupName, $group_id);
		}
		echo '<div class="updated"><p><strong>Adding a teacher</strong></p><p>'.$infoMessage.'</p></div>';
	}

	if( isset($_POST[ $hidden_field_name ]) && $_POST[ $hidden_field_name ] == 'new_student' ) {
		$schoolPrefix = sanitize_user(trim($_POST['student_prefix']));
		$groupName = sanitize_user(trim($_POST['student_group']));
		$studentText = trim($_POST['student']);
		$infoMessage = '';

		// Check to see if the school+group combination already exists.If not give up
		$table_name = $wpdb->prefix."group_list";
		$group_id = $wpdb->get_var($wpdb->prepare("SELECT id FROM $table_name WHERE school_name = %s AND group_name = %s", $schoolPrefix, $groupName));

		if (empty($group_id)) { // group does not exist
			$infoMessage = "School or Group does not exist, teacher not added.";
		} else {
			$infoMessage = add_student($studentText, $schoolPrefix, $groupName, $group_id);
		}
		echo '<div class="updated"><p><strong>Adding a student</strong></p><p>'.$infoMessage.'</p></div>';
	}
	
	
	// Build drop-down lists for schools and groups
	$table_name = $wpdb->prefix."group_list";
	$schoolInfo = $wpdb->get_results("SELECT school_name FROM $table_name GROUP BY school_name");
	
	$schoolList = "";
	foreach ($schoolInfo as $school) {
		$schoolList .= '<option>'.$school->school_name.'</option>';
	}

?>

<div class="wrap">
	<h2>Enquiry Blog builder</h2>
	
	<p>If the school and group names entered already exist, all current members will be removed and replaced with the new ones. 
	The previous members will not be deleted and will be reused if their name comes up again.</p>
	
	<p>If a member is in several groups, they will have a single username but multiple blogs</p>
	
	<form name="form1" method="post" action="">
		<input type="hidden" name="<?php echo $hidden_field_name; ?>" value="build_blogs">

		<table class="form-table">
			<tbody>

				<tr valign="top">
					<th scope="row"><label for="prefix">School prefix <br />(up to six letters)</label></th>
					<td><input id="prefix" type="text" name="prefix" value="<?php echo $schoolPrefix;?>" maxlength="6" size="6"></td>
				</tr>

				<tr valign="top">
					<th scope="row"><label for="prefix">Group name<br />(up to six letters)</label></th>
					<td><input id="group" type="text" name="group" value="<?php echo $groupName;?>" maxlength="6" size="6"></td>
				</tr>

				<tr valign="top">
					<th scope="row"><label for="teacher">Teacher name</label></th>
					<td><input id="teacher" type="text" name="teacher" value="<?php echo $teacherText;?>" size="20"></td>
				</tr>

				<tr valign="top">
					<th scope="row"><label for="students">Student names</label></th>
					<td>
						<p>Enter the names of students separated by commas. All names need to be unique.</p>
						<textarea id="students" style="width:98%" rows="10" cols="60" type="text" name="students" value="names"><?php echo $studentText;?></textarea>
					</td>
				</tr>

			</tbody>
		</table>

		<p class="submit">
			<input type="submit" name="Submit" class="button-primary" value="Build blogs" />
		</p>

	</form>

	<hr />

	<h2>Add a new teacher to a group</h2>

<?php if (empty($schoolList)) { ?>
	<p>First create a group to add to.</p>
<?php } else { ?>
	
	<p>Add a teacher to an exisiting group.</p>

	<form name="form2" method="post" action="">
		<input type="hidden" name="<?php echo $hidden_field_name; ?>" value="new_teacher">

		<table class="form-table">
			<tbody>

				<tr valign="top">
					<th scope="row"><label for="teacher_prefix">School prefix</label></th>
					<td><select name="teacher_prefix" class="new_teacher_school school"><?php echo $schoolList; ?></select></td>
				</tr>

				<tr valign="top">
					<th scope="row"><label for="teacher_group">Group name</label></th>
					<td><select name="teacher_group" class="new_teacher_group"></select></td>
				</tr>

				<tr valign="top">
					<th scope="row"><label for="teacher">Add teacher name</label></th>
					<td><input id="teacher" type="text" name="teacher" value="<?php echo $teacherText;?>" size="20"></td>
				</tr>

			</tbody>
		</table>

		<p class="submit">
			<input type="submit" name="Submit" class="button-primary" value="Add teacher" />
		</p>

	</form>

<?php } ?>

	<hr />

	<h2>Add a new student to a group</h2>

<?php if (empty($schoolList)) { ?>
	<p>First create a group to add to.</p>
<?php } else { ?>

	<p>Add a student to an exisiting group.</p>

	<form name="form3" method="post" action="">
		<input type="hidden" name="<?php echo $hidden_field_name; ?>" value="new_student">

		<table class="form-table">
			<tbody>

				<tr valign="top">
					<th scope="row"><label for="student_prefix">School prefix</label></th>
					<td><select name="student_prefix" class="new_student_school school"><?php echo $schoolList; ?></select></td>
				</tr>

				<tr valign="top">
					<th scope="row"><label for="student_group">Group name</label></th>
					<td><select name="student_group" class="new_student_group"></select></td>
				</tr>

				<tr valign="top">
					<th scope="row"><label for="student">Add student name</label></th>
					<td><input id="teacher" type="text" name="student" value="<?php echo $tstudentText;?>" size="20"></td>
				</tr>

			</tbody>
		</table>

		<p class="submit">
			<input type="submit" name="Submit" class="button-primary" value="Add student" />
		</p>

	</form>

<?php } ?>

	<hr />

<?php


}

function eb_plugin_menu() {
		// Add the site admin config page
		if (function_exists('is_network_admin')) {
			//3.1+
			add_submenu_page('settings.php', 'Enquiry Blog builder', 'Enquiry Blog builder', 'manage_sites', 'enquiryblogger_blog_creation', 'eb_settings_page');
		} else {
			// -3.1
			add_submenu_page('wpmu-admin.php', 'Enquiry Blog builder', 'Enquiry Blog builder', 'manage_sites', 'enquiryblogger_blog_creation', 'eb_settings_page');
		}
}

add_action('network_admin_menu', 'eb_plugin_menu');



function my_action_javascript() {
global $wpdb;

	// Build drop-down lists for schools and groups
	$table_name = $wpdb->prefix."group_list";
	$schoolInfo = $wpdb->get_results("SELECT school_name FROM $table_name GROUP BY school_name");
	
	$groupList = "var items = {";
	foreach ($schoolInfo as $school) {
		$groupList .= '"'.$school->school_name.'":"';

		$groupInfo = $wpdb->get_results($wpdb->prepare("SELECT group_name FROM $table_name WHERE school_name = %s", $school->school_name));

		foreach ($groupInfo as $group) {
			$groupList .= "<option>".$group->group_name."</option>"; 
		}
		$groupList .= '"';
		if (end($schoolInfo) != $school) $groupList .= ',';
	}
	$groupList .= '};';
?>

<script type="text/javascript" >
jQuery(document).ready(function($) {

	<?php echo $groupList; ?>
	
	jQuery("select.school").change(function () {
			if (jQuery(this).hasClass("new_teacher_school")) {
				key = jQuery('select.new_teacher_school option:selected').text();
				jQuery("select.new_teacher_group").empty().append(items[key]);	
			}

			if (jQuery(this).hasClass("new_student_school")) {
				key = jQuery('select.new_student_school option:selected').text();
				jQuery("select.new_student_group").empty().append(items[key]);	
			}
	})
	.change();

});
</script>
<?php
}
add_action('admin_head', 'my_action_javascript');


// Return a list of members of the school's group
function get_members_callback() {
	global $wpdb; // this is how you get access to the database

	$schoolName = $_POST['school'];
	$groupName = $_POST['group'];

	$table_name = $wpdb->prefix."group_list";
	$group_id = $wpdb->get_var($wpdb->prepare("SELECT id FROM $table_name WHERE school_name = %s AND group_name = %s", $schoolName, $groupName));
	
	$table_name = $wpdb->prefix."group_members";
	$members = $wpdb->get_results($wpdb->prepare("SELECT blog_id FROM $table_name WHERE group_id = %d", $group_id));

	$list = "";
	foreach ($members as $member) {
		$list .= $member->blog_id." - ";
	}
	
  echo $list;

	die(); // this is required to return a proper result
}
add_action('wp_ajax_my_action', 'get_members_callback');



function remove_dashboard_boxes() {
	//remove_meta_box( 'dashboard_right_now', 'dashboard', 'core' );
	remove_meta_box( 'dashboard_incoming_links', 'dashboard', 'core' );
	remove_meta_box( 'dashboard_plugins', 'dashboard', 'core' );
	remove_meta_box( 'dashboard_quick_press', 'dashboard', 'core' );
	remove_meta_box( 'dashboard_recent_drafts', 'dashboard', 'core' );
	//remove_meta_box( 'dashboard_recent_comments', 'dashboard', 'core' );
	remove_meta_box( 'dashboard_primary', 'dashboard', 'core' );
	remove_meta_box( 'dashboard_secondary', 'dashboard', 'core' );
}

add_action('admin_menu', 'remove_dashboard_boxes' );



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

// Use sidebar names from the suffusion theme, and hardcoded plugin names. Oooh er.
function set_default_widgets($blog_id, $user_id){
	global $wpdb;

	switch_to_blog($blog_id);

	// Network activate each plugin if not already done.
	$plugins = array('eb-enquiryblogbuilder/eb-enquiryMood.php', 'eb-enquiryblogbuilder/eb-enquirySpider.php', 'eb-enquiryblogbuilder/eb-enquirySpiral.php', 'image-widget/image-widget.php');

	foreach ($plugins as $plugin) { 
		if (!is_plugin_active_for_network($plugin))
			activate_plugin( $plugin, '', true);
	}

	// Activate each dashboard plugin just for this blog if it belongs to a teacher.
	$is_teacher_blog = get_option('is_teacher_blog', 'no');
	if ($is_teacher_blog == 'yes') {
		$dashboard_plugins = array('eb-enquiryblogbuilder/eb-enquiryMoodDashboard.php', 'eb-enquiryblogbuilder/eb-enquirySpiderDashboard.php', 'eb-enquiryblogbuilder/eb-enquirySpiralDashboard.php');
		
		foreach ($dashboard_plugins as $plugin) { 
			if ( !is_plugin_active($plugin))
				activate_plugin( $plugin, '', false);
		}
	}
	
	// Clears the blog sidebars, adds widgets and applies a number of suffusion-child theme specific settings. 
	// This is tied to the suffusion theme so has been commented out. The lines can be added and adjusted to fit your
	// theme's sidebars numbers. Also, use the "New Blog Defaults" plugin to set the default theme and categories for each new blog.
	// This section also assumes the named plugins are loaded : enquiry_spider_widget, enquiry_spiral_widget and mood_view_widget.
	/*
	clear_sidebar('sidebar-18'); // Top wide sidebar
	clear_sidebar('sidebar-1'); // Left narrow sidebar
	clear_sidebar('sidebar-2'); // Right narrow sidebar
	clear_sidebar('sidebar-19'); // Bottom wide sidebar

	install_widget('sidebar-1', 'enquiry_spider_widget');
	install_widget('sidebar-2', 'enquiry_spiral_widget');
	install_widget('sidebar-19', 'mood_view_widget');

	import_suffusion_settings();
	*/

	install_widget('sidebar-1', 'enquiry_spider_widget');
	install_widget('sidebar-1', 'enquiry_spiral_widget');
	install_widget('sidebar-1', 'mood_view_widget');

	restore_current_blog();
}

// Suffusion allows saving and restoring all the theme options. A single settings file (suffusion-options.txt) 
// has been created that is applied to every new blog created. This ensures they all look the same.
// Obviously it will only work if the suffusion theme has been installed and applied to the blog.
function import_suffusion_settings() {
	global $suffusion_options, $suffusion_options_intro_page, $suffusion_reevaluate_styles, $suffusion_unified_options;
	if ($suffusion_options === FALSE || $suffusion_options == null || $suffusion_options == '') {
		$suffusion_options = array();
	}

	$filename = WP_PLUGIN_DIR. '/suffusion-options.txt';
	if (file_exists($filename)) {
		include ($filename);
		foreach ($suffusion_exported_options as $option => $option_value) {
			$suffusion_options[$option] = $option_value;
		}
		update_option('suffusion_options', $suffusion_options);
		$suffusion_reevaluate_styles = true;
		$suffusion_unified_options = suffusion_get_unified_options(false, false);
		suffusion_set_options_version($suffusion_unified_options);
		update_option('suffusion_unified_options', $suffusion_unified_options);
		if (isset($suffusion_unified_options['suf_autogen_css']) && $suffusion_unified_options['suf_autogen_css'] == 'autogen') {
			suffusion_create_or_update_custom_css(true);
		}
	}
}
add_action('wpmu_new_blog', 'set_default_widgets', 100, 2);


// When activated, create the group tables in the database to store each group and their members
// TODO: When all blogs have been removed from a group, should the group also be removed?
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


// When a blog is deleted, remove it from the member table
function cleanOutBlogs($blog_id) {
global $wpdb;

		switch_to_blog(1);
		$table_name = $wpdb->prefix."group_members";
		$wpdb->query($wpdb->prepare("DELETE FROM $table_name WHERE blog_id = %d", $blog_id));
		restore_current_blog();
}
add_action('delete_blog', cleanOutBlogs);


// When a user is deleted, remove them (and all their blogs) from the member table
function cleanOutUsers($user_id) {
global $wpdb;

		switch_to_blog(1);
		$table_name = $wpdb->prefix."group_members";
		$wpdb->query($wpdb->prepare("DELETE FROM $table_name WHERE user_id = %d", $user_id));
		restore_current_blog();
}
add_action('deleted_user', cleanOutUsers);


?>