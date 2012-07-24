<?php
/*
		Plugin Name: EnquiryBlogger: Enquiry Spiral
		Plugin URI: http://kmi.open.ac.uk/
		Description: Displays how many posts in each category have been posted
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

function display_spiral($categories, $prefix) {
	global $blog_id;

	$path = '/wp-content/plugins/eb-enquiryblogbuilder/';
	$filename = getcwd().$path.'spiralBackground.jpg';

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
	$spiralImageWidth = 150;

	list( $width, $height ) = getimagesize( $filename );
	$scale = $spiralImageWidth / $width;

	$points = array(
							array(177, 209),
							array(117, 212),
							array(135, 278),
							array(210, 295),
							array(273, 257),
							array(298, 170),
							array(240, 94),
							array(140, 83),
							array(54, 143)
						);

	echo '<map name="spiral_'.$blog_id.'">';

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

	if (!imagepng( $final_img, '.'.$path.'spiral_'.$blog_id.'.png' )) return;

	imagedestroy( $work_img );
	imagedestroy( $final_img );

	?>

	<div style="text-align:center">
	<img src="<?php echo $prefix.$path.'spiral_'.$blog_id.'.png';?>" style="border:0" title="Enquiry Spiral" alt="Enquiry Spiral" usemap="#spiral_<?php echo $blog_id; ?>" />
	</div>
	
	<?php
}


function display_enquiry_spiral($args) {

  $title = "Enquiry Spiral";
  extract($args);
	echo $before_widget;
  echo $before_title.$title.$after_title;

  $args = array(
	    'type'                     => 'post',
	    'child_of'                 => 0,
	    'parent'                   => 0,
	    'orderby'                  => 'id',
	    'order'                    => 'ASC', // or DESC to reverse the spiral
	    'hide_empty'               => 0,
	    'hierarchical'             => 1,
	    'exclude'                  => '1,12,13,14,15,16,17,18', // exclude 'other' category
	    'taxonomy'                 => 'category',
    	'pad_counts'               => false );

  $categories = get_categories( $args );
	$prefix = get_bloginfo( 'url' );

  display_spiral( $categories, $prefix);

  echo $after_widget;
}


function enquiry_spiral_init()
{
  $widget_ops = array('classname' => 'enquiry_spiral_widget', 'description' => "Displays number of posts in each category." );
  wp_register_sidebar_widget('enquiry_spiral_widget', 'Enquiry Spiral', 'display_enquiry_spiral', $widget_ops);
}
add_action("plugins_loaded", "enquiry_spiral_init");

?>