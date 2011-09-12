<?php
/*
		Plugin Name: EnquiryBlogger: Enquiry Spider Dashboard
		Plugin URI: http://kmi.open.ac.uk/
		Description: Displays category details of all other blogs
		Version: 1.0
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


// Output a single imagemap for a particular blog
function display_spider_dashboard($categories, $prefix, $id) {

	$path = '../wp-content/plugins/eb-enquiryBlogBuilder/';
	$filename = getcwd().'/'.$path.'spiderBackground.jpg';

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
	$spiderImageWidth = 130;
	
	list( $width, $height ) = getimagesize( $filename );
	$scale = $spiderImageWidth / $width;
	
	$points = array(
							array(175,55), 
							array(268,103), 
							array(289,201), 
							array(227,280), 							
							array(124,278), 
							array(62,200),							
							array(85,101)
						);

	echo '<map name="spider_'.$id.'">';

	$i = 0;
	foreach ($categories as $category) {
		
		$post = min($postLimit, $category->count);
		
		$radius = $minSize + ($post / $postLimit) * ($maxSize - $minSize);
		$colour = ($post <= 0) ? $nopost_colour : (($post <= 2) ? $somepost_colour : $manypost_colour);
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
	
	if (!imagepng( $final_img, $path.'spider_'.$id.'.png' )) return;

	imagedestroy( $work_img );
	imagedestroy( $final_img );
	
	?>

	<img src="<?php echo $path.'spider_'.$id.'.png';?>" style="border:0" title="Enquiry Spider" alt="Enquiry Spider" usemap="#spider_<?php echo $id ?>" />
	
	<?php	
}


// Output a graph showing all of the category data for each blog
function display_spider_graph($blognames, $values, $categories) {
	?>

<!--[if IE]><script language="javascript" type="text/javascript" src="<?php echo plugins_url(); ?>/flot/excanvas.min.js"></script><![endif]-->

<div id="spider_placeholder" style="width:100%; height:250px;"></div>

<script language="javascript" type="text/javascript">

jQuery(function spider() {
		<?php 
		$i = 0;
		$data = "";
		foreach ($values as $value) {
			?>
			var spider_data_<?php echo $i; ?> = [<?php	$j=0; foreach ($value as $number) {	echo '['.$j.','.$number.']'; $j++; if ($j < count($value)) echo ', ';	}	?> ];
			<?php
			$data = $data.' spider_data_'.$i;
			$i++;
			if ($i < count($values)) $data = $data.', ';
		}

		$i = 0;
		echo 'var spider_blognames = [';
		foreach ($blognames as $name) {
			echo '"'.$name[1].'"';
			$i++;
			if ($i < count($blognames)) echo ', '; 
		}
		echo '];';
				
		$i = 0;
		echo 'var spider_categorynames = [';
		foreach ($categories as $category) {
			echo '{url:"'.$category->slug.'",name:"'.$category->name.'"}';
			$i++;
			if ($i < count($categories)) echo ', '; 
		}
		echo '];';
		?>

		// Attach a click function to links with the metabox-group class to redraw the graph
		// otherwise it doesn't get redrawn if the box starts closed.
		jQuery('#enquiry_spider_dashboard').click(function(){
			plotGraphSpider();
		});

		jQuery(window).resize(function () {
			plotGraphSpider();
		});

    function plotGraphSpider() {
        jQuery.plot(jQuery("#spider_placeholder"), [ <?php echo $data; ?> ], {
            series: {
                stack: true,
                bars: { show: true, barWidth: 0.9, align:"center"}
            },
						grid: {clickable: true, hoverable: true},
						xaxis: {
								ticks: [<?php $i = 0; foreach ($blognames as $name) { echo '['.$i.',"'.$name[0].'"]';$i++; if ($i < count($blognames)) echo ', '; }?>]
						}
        });
    }
    
   jQuery("#spider_placeholder").bind("plotclick", function (event, pos, item) {
			if (item) {
				window.open(spider_blognames[item.dataIndex]+"/category/"+spider_categorynames[item.seriesIndex].url, "_self", null, true);
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
		jQuery("#spider_placeholder").bind("plothover", function (event, pos, item) {
			
			if (item) {
				
				if (previousPoint != item.datapoint) {
					previousPoint = item.datapoint;

					jQuery("#tooltip").remove();
					var x = item.pageX;
					var y = item.pageY;
					showTooltip(x, y, spider_categorynames[item.seriesIndex].name);
				}
			}
			else {
				jQuery("#tooltip").remove();
				previousPoint = null;
			}
		});

    plotGraphSpider();
});
</script>

<?php
}

// Output the contents of the Enquiry Spider Dashboard
function display_enquiry_spider_dashboard() {	

	global $current_user;
		
  $args = array(
	    'type'                     => 'post',
	    'child_of'                 => 0,
	    'parent'                   => 0,
	    'orderby'                  => 'id',
	    'order'                    => 'ASC',
	    'hide_empty'               => 0,
	    'hierarchical'             => 1,
	    'exclude'                  => '1,2,3,4,5,6,7,8,9,10,11', // exclude 'other' category and those in the spiral
	    'taxonomy'                 => 'category',
    	'pad_counts'               => false );
    	
	$blogs = get_student_blogs($current_user->id);
	//$blogs = get_blogs_of_user( $current_user->id );
	$primary_blog = get_user_meta( get_current_user_id(), 'primary_blog', true );
	if (empty ($blogs)) return;

	$blognames = array();
	$values = array();
	$noPosts = ""; // list of blogs with no posts in any category
		
	foreach ($blogs as $blog) {
		//if ($primary_blog == $blog->userblog_id) continue;

		$prefix = $blog->path;

		//switch_to_blog( $blog->userblog_id );
		switch_to_blog( $blog->blog_id );
		$categories = get_categories( $args );
		restore_current_blog();

		echo '<div style="float:left; width:120px; padding-right:10px; padding-left:10px; overflow: hidden; text-overflow: ellipsis; white-space:nowrap;">';
		echo '<p><strong>'.$blog->blogname."</strong></p>";

		//display_spider_dashboard( $categories, $prefix, $blog->userblog_id );
		display_spider_dashboard( $categories, $prefix, $blog->blog_id );

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

	if (!empty($values)) display_spider_graph($blognames, $values, $categories);

	if ($noPosts != "") echo '<hr><h4>Blogs with no posts in their ELLI Spider</h4><p>'.$noPosts.'</p>';

} 


// Create the function use in the action hook
function enquiry_spider_dashboard_init() {
	wp_add_dashboard_widget('enquiry_spider_dashboard', 'Enquiry Spider Dashboard', 'display_enquiry_spider_dashboard');	
} 

//function spider_dashboard_scripts() {	
//    wp_enqueue_script( 'flot', plugins_url().'/flot/jquery.flot.js', array('jquery'));
//    wp_enqueue_script( 'flot-stack', plugins_url().'/flot/jquery.flot.stack.js', array('jquery', 'flot'));
//}

//add_action('init', 'spider_dashboard_scripts');
add_action('wp_dashboard_setup', 'enquiry_spider_dashboard_init' );

?>