<?php
/*
		Plugin Name: EnquiryBlogger: Mood View Dashboard
		Plugin URI: http://kmi.open.ac.uk/
		Description: Displays the mood of all other blogs
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

// Extract the current and average mood for the given blog
function get_mood($blog_id) {
	global $wpdb;

	switch_to_blog(1);
	$table_name = $wpdb->prefix."moods";

	$average_mood = $wpdb->get_var( "SELECT AVG(mood) FROM $table_name WHERE blog_id = $blog_id" );
	$current_mood = $wpdb->get_var( "SELECT mood FROM $table_name WHERE blog_id = $blog_id ORDER BY time DESC LIMIT 1" );

	restore_current_blog();
	return (object)array('average' => $average_mood, 'current' => $current_mood);
}


// Extract the current and average mood for the given blog
function get_moods_dashboard_history($blog_id) {
	global $wpdb;

	$max_moods = 50; // number of past moods to display
	$my_blog = $blog_id;

	switch_to_blog(1);
	$table_name = $wpdb->prefix."moods";
	restore_current_blog();

	switch_to_blog($blog_id);
	$blog_table_name = $wpdb->prefix."posts";
	restore_current_blog();

	$mood_history = $wpdb->get_results( "SELECT time, mood, post_title, post_date FROM $table_name AS m, $blog_table_name AS p WHERE m.blog_id = $my_blog AND m.post_id = p.id ORDER BY time DESC LIMIT $max_moods" );
	$mood_history = array_reverse($mood_history);

	return $mood_history;
}


// Output a graph showing all of the mood data for each blog
function display_moods_dashboard_graph($blognames, $values) {
	?>

<!--[if IE]><script language="javascript" type="text/javascript" src="<?php echo plugins_url(); ?>/eb-enquiryblogbuilder/flot/excanvas.min.js"></script><![endif]-->

<div id="moods_placeholder" style="width:100%; height:250px;"></div>

<div id="moods_legend_placeholder" style="width:100%;"></div>

<script language="javascript" type="text/javascript">

jQuery(function moods() {
		<?php
		
		$i = 0;
		$data = "";
		foreach ($values as $value) {
			?>
			var moods_data_<?php echo $i; ?> = {label:"<?php echo $blognames[$i][0]; ?>", data:[<?php	$j=0; foreach ($value as $number) {	echo '['.strtotime($number->time).'000,'.$number->mood.']'; $j++; if ($j < count($value)) echo ', ';	}	?> ]};
			<?php
			$data = $data.' moods_data_'.$i;
			$i++;
			if ($i < count($values)) $data = $data.', ';
		}
		
		echo "var tooltip = [";
		$j = 0;
		foreach ($values as $value) {
			$i = 0;
			echo " [";

			foreach ($value as $number) {
				 $date = urlencode($number->post_date);
				 echo '{title:"'.($number->post_title).'", url:"'.$blognames[$j][1].'?mood_view='.($date).'"}'; $i++; if ($i < count($value)) echo ', ';
			}
			$j++;
			echo "]";
			if ($j < count($values)) echo ', ';
		}
		echo "];";
		?>


		// Attach a click function to links with the metabox-group class to redraw the graph
		// otherwise it doesn't get redrawn if the box starts closed.
		jQuery('#mood_view_dashboard').click(function(){
			plotGraphMoods();
		});

		jQuery(window).resize(function () {
			plotGraphMoods();
		});

    function plotGraphMoods() {
			jQuery.plot(jQuery("#moods_placeholder"), [ <?php echo $data; ?> ], {
				series: {
					 points: { show: true },
					 lines: { show: true }
			 },

				grid: {clickable: true, hoverable: true},
				legend: {noColumns: 5, container: jQuery("#moods_legend_placeholder")},

				yaxis: {
					min: -0.5,
					max: 4.5,
					ticks: [[0, ":-("], [1,":-/"], [2,":-|"], [3,":-)"], [4,":-D"]]
				},
				xaxis: {
					mode: "time" }
			});
    }


    jQuery("#moods_placeholder").bind("plotclick", function (event, pos, item) {
			if (item) {
				window.open(tooltip[item.seriesIndex][item.dataIndex].url, "_self", null, true);
			}
    });

    function showTooltip(x, y, label) {
			jQuery('<div id="tooltip">' + label + '</div>').css( {
				position: 'absolute',
				display: 'none',
				top: y + 5,
				left: x + 5,
				border: '1px solid #fdd',
				padding: '2px',
				'background-color': '#fee',
				opacity: 0.80
			}).appendTo("body").fadeIn(200);
    }

		var previousPoint = null;
		jQuery("#moods_placeholder").bind("plothover", function (event, pos, item) {
			if (item) {
				if (previousPoint != item.datapoint) {
					previousPoint = item.datapoint;

					jQuery("#tooltip").remove();
					var x = item.pageX;
					var y = item.pageY;

					showTooltip(x, y, item.series.label  + "<br/>" + tooltip[item.seriesIndex][item.dataIndex].title);
				}
			}
			else {
				jQuery("#tooltip").remove();
				previousPoint = null;
			}
		});



    plotGraphMoods();
});
</script>

<?php
}


// Create the function to output the contents of the Mood Dashboard
function mood_view_dashboard() {

	global $current_user;
	$blogs = get_student_blogs($current_user->id);

	if (empty ($blogs)) return;

	$blognames = array();
	$values = array();
	$noPosts = ""; // list of blogs with no mood posts
	
	foreach ($blogs as $blog) {
		$prefix = $blog->path;
		
		$data = get_moods_dashboard_history($blog->blog_id);
		if ($data != null) {
			$blognames[] = array($blog->blogname, $blog->siteurl);
			$values[] = $data;
		} else {
			if ($noPosts != "") $noPosts = $noPosts.', ';
			$noPosts = $noPosts.'<a href="'.$prefix.'">'.$blog->blogname.'</a>';
		}
	}

	if (!empty($values)) display_moods_dashboard_graph($blognames, $values);

	if ($noPosts != "") echo '<hr><h4>Blogs with no mood changes</h4><p>'.$noPosts.'</p>';

}


// Create the function use in the action hook
function mood_view_dashboard_init() {
	wp_add_dashboard_widget('mood_view_dashboard', 'Mood View Dashboard', 'mood_view_dashboard');
}


function mood_dashboard_scripts() {
    wp_enqueue_script( 'flot', plugins_url().'/eb-enquiryblogbuilder/flot/jquery.flot.js', array('jquery'));
    wp_enqueue_script( 'flot-stack', plugins_url().'/eb-enquiryblogbuilder/flot/jquery.flot.stack.js', array('jquery', 'flot'));
}

add_action('init', 'mood_dashboard_scripts');
add_action('wp_dashboard_setup', 'mood_view_dashboard_init' );

?>