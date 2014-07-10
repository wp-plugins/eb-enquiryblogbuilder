<?php
/*
		Plugin Name: EnquiryBlogger: ELLI Spider
		Plugin URI: http://kmi.open.ac.uk/
		Description: Displays how many posts in each category have been posted
		Version: 1.2
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

include_once("eb-functions.php");

include_once("eb-spiderSetup.php");

// Return the details of the requested group, if it exists
function getGroupFromBlog($blog_id) {
	global $wpdb;

	switch_to_blog(1);
	$table_name = $wpdb->prefix."group_members";
	$group_id = $wpdb->get_var($wpdb->prepare("SELECT group_id FROM $table_name WHERE blog_id = %d", $blog_id));
	restore_current_blog();

	return ($group_id);
}

// Return true if any of the images for the group are non-null
function groupHasImages($group_id) {
	global $wpdb;

	switch_to_blog(1);
	$table_name = $wpdb->prefix."group_spider";
	$spider_info = $wpdb->get_row($wpdb->prepare("SELECT * FROM $table_name WHERE group_id = %s", $group_id));
	restore_current_blog();

	for ($i = 0; $i < 7; $i++) {
		$imageName = 'image_'.$i;
		if ($spider_info->$imageName != NULL) return true;
	}

	return ($false);
}


function display_spider($categories, $prefix) {
	global $blog_id;

	$path = '/wp-content/plugins/eb-enquiryblogbuilder/';
	$filename = getcwd().$path.'spiderBackground';


	// If the blog is in a group, and the group has images assigned, then use it
	$group_id = getGroupFromBlog($blog_id);
	$has_images = groupHasImages($group_id);
	if ($group_id && $has_images) {
		$filename = $filename.'_'.$group_id.'.jpg';
		rebuildSpider($group_id);
		$pointDistance = 60;
	} else {
		$filename = $filename.'.jpg';
		$pointDistance = 120;
	}


	$work_img = imagecreatefromjpeg( $filename );
	if (!$work_img) return;

	$edge_colour = 0x00000000;
	$nopost_colour = 0x00FF4040;
	$somepost_colour = 0x00FFA240;
	$manypost_colour = 0x001DFF33;

	$postLimit = 10;
	$minSize = 20;
	$maxSize = 50;
	$thickness = 3;
	$spiderImageWidth = 280; // 150; // larger image for double width image

	list( $width, $height ) = getimagesize( $filename );
	$scale = $spiderImageWidth / $width;

	$points = getPoints($pointDistance, $width);

	echo '<map name="spider_'.$blog_id.'">';

	$i = 0;
	foreach ($categories as $category) {

		$post = min($postLimit, $category->count);

		$radius = $minSize + ($post / $postLimit) * ($maxSize - $minSize);
		$colour = ($post <= 0) ? $nopost_colour : (($post <= 1) ? $somepost_colour : $manypost_colour);
		list($x_pos, $y_pos) = $points[$i];
		if ($x_pos == null) break;

		$label = $category->name.' ('.$category->count.')';
		$url = $prefix.'/category/'.$category->slug;

		imagefilledellipse( $work_img, $x_pos, $y_pos, $radius+$thickness, $radius+$thickness, $edge_colour );
		imagefilledellipse( $work_img, $x_pos, $y_pos, $radius, $radius, $colour );
		echo '<area shape="circle" coords="'.$x_pos*$scale.', '.$y_pos*$scale.', '.$radius*$scale.'" alt="'.$label.'" title="'.$label.'" href="'.$url.'" />';
		$i++;
	}
	echo '</map>';

	$new_width = $width * $scale;
	$new_height = $height * $scale;

	$final_img = imagecreatetruecolor($new_width, $new_height);
	if (!$final_img) return;

	imagecopyresampled($final_img, $work_img, 0, 0, 0, 0, $new_width, $new_height, $width, $height);

	if (!imagepng( $final_img, '.'.$path.'spider_'.$blog_id.'.png' )) return;

	imagedestroy( $work_img );
	imagedestroy( $final_img );

	?>

	<div style="text-align:center">
	<img src="<?php echo $prefix.$path.'spider_'.$blog_id.'.png';?>" style="border:0" title="ELLI spider" alt="ELLI spider" usemap="#spider_<?php echo $blog_id; ?>" />
	</div>

	<?php
}


function display_enquiry_spider($args) {

  $title = "ELLI Spider";
  extract($args);
	echo $before_widget;
  echo $before_title.$title.$after_title;

  $args = array(
	    'type'                     => 'post',
	    'child_of'                 => 0,
	    'parent'                   => 0,
	    'orderby'                  => 'id',
	    'order'                    => 'ASC',
	    'hide_empty'               => 0,
	    'hierarchical'             => 1,
	    'exclude'                  => '1,2,3,4,5,6,7,8,9,10,11', // exclude categories 'other', 'links' and those in the spiral
	    'taxonomy'                 => 'category',
    	'pad_counts'               => false );

  $categories = get_categories( $args );
	$prefix = get_bloginfo( 'url' );

  display_spider( $categories, $prefix);

  echo $after_widget;
}


function enquiry_spider_init()
{
  $widget_ops = array('classname' => 'enquiry_spider_widget', 'description' => "Displays number of posts in each category." );
  wp_register_sidebar_widget('enquiry_spider_widget', 'ELLI Spider', 'display_enquiry_spider', $widget_ops);
}
add_action("plugins_loaded", "enquiry_spider_init");

?>