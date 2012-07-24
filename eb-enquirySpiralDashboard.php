<?php
/*
		Plugin Name: EnquiryBlogger: Enquiry Spiral Dashboard
		Plugin URI: http://kmi.open.ac.uk/
		Description: Displays category details of all other blogs
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

include_once("eb-functions.php");

// Output a single imagemap for a particular blog
function display_spiral_dashboard($categories, $prefix, $id) {

	$path = dirname(__FILE__);	
	$filename = $path.'/spiralBackground.jpg';

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
	$spiralImageWidth = 130;

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

	echo '<map name="spiral_'.$id.'">';

	$i = 0;
	foreach ($categories as $category) {

		$post = min($postLimit, $category->count);

		$radius = $minSize + ($post / $postLimit) * ($maxSize - $minSize);
		$colour = ($post <= 0) ? $nopost_colour : (($post <= 1) ? $somepost_colour : $manypost_colour);
		list($x_pos, $y_pos) = $points[$i];
		if ($x_pos == null) break;

		$label = $category->name.' ('.$category->count.')';
		$url = $prefix.'category/'.$category->slug;

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

	if (!imagepng( $final_img, $path.'/spiral_'.$id.'.png' )) return;

	imagedestroy( $work_img );
	imagedestroy( $final_img );

	?>

	<img src="<?php echo plugins_url().'/eb-enquiryblogbuilder/spiral_'.$id.'.png';?>" style="border:0" title="Enquiry Spiral" alt="Enquiry Spiral" usemap="#spiral_<?php echo $id ?>" />

	<?php
}


// Output a graph showing all of the category data for each blog
function display_spiral_graph($blognames, $values, $categories) {
	?>

<!--[if lt IE 9]><script language="javascript" type="text/javascript" src="<?php echo plugins_url(); ?>/flot/excanvas.min.js"></script><![endif]-->

<div id="spiral_placeholder" style="width:100%; height:250px;"></div>

<script language="javascript" type="text/javascript">

jQuery(function spiral() {
		<?php
		$i = 0;
		$data = "";
		foreach ($values as $value) {
			?>
			var spiral_data_<?php echo $i; ?> = [<?php	$j=0; foreach ($value as $number) {	echo '['.$j.','.$number.']'; $j++; if ($j < count($value)) echo ', ';	}	?> ];
			<?php
			$data = $data.' spiral_data_'.$i;
			$i++;
			if ($i < count($values)) $data = $data.', ';
		}
		
		$i = 0;
		echo 'var spiral_blognames = [';
		foreach ($blognames as $name) {
			echo '"'.$name[1].'"';
			$i++;
			if ($i < count($blognames)) echo ', '; 
		}
		echo '];';
				
		$i = 0;
		echo 'var spiral_categorynames = [';
		foreach ($categories as $category) {
			echo '{url:"'.$category->slug.'",name:"'.$category->name.'"}';
			$i++;
			if ($i < count($categories)) echo ', '; 
		}
		echo '];';
		?>

		// Attach a click function to links with the metabox-group class to redraw the graph
		// otherwise it doesn't get redrawn if the box starts closed. Removed thanks to jquery.flot.resize.js
		//jQuery('#enquiry_spiral_dashboard').click(function(){
		//	plotGraphSpiral();
		//});

		//jQuery(window).resize(function () {
		//	plotGraphSpiral();
		//});

    function plotGraphSpiral() {
        jQuery.plot(jQuery("#spiral_placeholder"), [ <?php echo $data; ?> ], {
            series: {
                stack: true,
                bars: { show: true, barWidth: 0.9, align:"center"}
            },
						grid: {clickable: true, hoverable: true},
						xaxis: {
								rotateTicks: 90,
								ticks: [<?php $i = 0; foreach ($blognames as $name) { echo '['.$i.',"'.html_entity_decode($name[0], ENT_QUOTES | ENT_HTML401).'"]';$i++; if ($i < count($blognames)) echo ', '; }?>]
						}
						
        });
    }
	
   jQuery("#spiral_placeholder").bind("plotclick", function (event, pos, item) {
			if (item) {
				window.open(spiral_blognames[item.dataIndex]+"/category/"+spiral_categorynames[item.seriesIndex].url, "_self", null, true);
			}
    });

    function showTooltip(x, y, label) {
			
			jQuery('<div id="tooltip">' + label + '</div>').css( {
				position: 'absolute',
				display: 'none',
				top: y + 8,
				left: x + 8,
				border: '1px solid #fdd',
				padding: '2px',
				'background-color': '#fee',
				opacity: 0.80
			}).appendTo("body").fadeIn(200);
    }

		var previousPoint = null;
		jQuery("#spiral_placeholder").bind("plothover", function (event, pos, item) {
			
			if (item) {
				
				if (previousPoint != item.datapoint) {
					previousPoint = item.datapoint;

					jQuery("#tooltip").remove();
					var x = item.pageX;
					var y = item.pageY;
					showTooltip(x, y, spiral_categorynames[item.seriesIndex].name);
				}
			}
			else {
				jQuery("#tooltip").remove();
				previousPoint = null;
			}
		});


    plotGraphSpiral();
});
</script>

<?php
}

// Output the contents of the Enquiry Spiral Dashboard
function display_enquiry_spiral_dashboard() {

	global $current_user;

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

	$blogs = get_student_blogs($current_user->id);
	$primary_blog = get_user_meta( get_current_user_id(), 'primary_blog', true );
	if (empty ($blogs)) return;

	$blognames = array();
	$values = array();
	$noPosts = ""; // list of blogs with no posts in any category
	
	foreach ($blogs as $blog) {

		$prefix = $blog->path;

		//switch_to_blog( $blog->userblog_id );
		switch_to_blog( $blog->blog_id );
		$categories = get_categories( $args );
		restore_current_blog();

		echo '<div class="enquiryblogger-dashboard enquiryblogger-dashboard-spiral">';
		echo '<p><strong>'.$blog->blogname.'</strong></p>';

		display_spiral_dashboard( $categories, $prefix, $blog->blog_id );

		echo '</div>';

		// Check if there are any posts in any category
		$hasPosts = false;
		foreach ($categories as $category) {
			if ($category->count > 0) {
				$hasPosts = true;
				break;
			}
		}
		
		if ($hasPosts) { 
			// Store all the values for use in the combined graph
			$blognames[] = array($blog->blogname, $blog->siteurl); // $blog->blogname;
			foreach ($categories as $category) {
				$values[$category->slug][] = $category->count;
			}
		} else {
			if ($noPosts != "") $noPosts = $noPosts.', ';
			$noPosts = $noPosts.'<a href="'.$prefix.'">'.$blog->blogname.'</a>';
		}
		
	}
	echo '<div style="clear:left;"> </div>';

	if (!empty($values)) display_spiral_graph($blognames, $values, $categories);
	
	if ($noPosts != "") echo '<hr><h4>Blogs with no posts in their enquiry spiral</h4><p>'.$noPosts.'</p>';
}


// Create the function to use in the action hook
function enquiry_spiral_dashboard_init() {
	wp_add_dashboard_widget('enquiry_spiral_dashboard', 'Enquiry Spiral Dashboard', 'display_enquiry_spiral_dashboard');
}
add_action('wp_dashboard_setup', 'enquiry_spiral_dashboard_init' );

function spiral_dashboard_scripts() {	
		$path = plugin_dir_url(__FILE__);
    wp_enqueue_script( 'flot', $path.'flot/jquery.flot.min.js', array('jquery') );
    wp_enqueue_script( 'flot-stack', $path.'flot/jquery.flot.stack.min.js', array('jquery', 'flot') );
    wp_enqueue_script( 'flot-tickrotor', $path.'flot/jquery.flot.tickrotor.js', array('jquery', 'flot') );
    wp_enqueue_script( 'flot-resize', $path.'flot/jquery.flot.resize.min.js', array('jquery', 'flot', 'flot-tickrotor') );
    wp_enqueue_style( 'dashboard', $path.'dashboard.css' );
}
add_action('admin_menu', 'spiral_dashboard_scripts');

?>