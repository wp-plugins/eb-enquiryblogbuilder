<?php
/*
		Plugin Name: EnquiryBlogger: Mood View
		Plugin URI: http://kmi.open.ac.uk/
		Description: Allows student to set their current mood
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

// Get the current mood for this blog
function get_my_mood() {
	global $wpdb, $blog_id;

	$my_blog = $blog_id;
	switch_to_blog(1);
	$table_name = $wpdb->prefix."moods";
	$current_mood = $wpdb->get_var( "SELECT mood FROM $table_name WHERE blog_id = $my_blog ORDER BY time DESC LIMIT 1" );

	//Debugging
	//$all_moods = $wpdb->get_results( "SELECT mood, time FROM $table_name WHERE blog_id = $my_blog ORDER BY time DESC" );
	//echo "<!-- MOODY ".$table_name." current:".$current_mood." ";
	//var_export($all_moods);
	//echo "-->";

	restore_current_blog();
	if (($current_mood != 0) && empty($current_mood)) $current_mood = 4; // error getting data from table, so choose default mood
	return $current_mood;
}


// Get the last few moods for this blog
function get_mood_history($blog_id) {
	global $wpdb;

	$options = get_option("widget_mood_view");
  if (!is_array( $options )) {
		$options = array('points' => 20);
  }

	$max_moods = $options['points']; // number of past moods to display set in widget option panel default = 20
	$my_blog = $blog_id;

	switch_to_blog(1);
	$table_name = $wpdb->prefix."moods";
	restore_current_blog();

	switch_to_blog($blog_id);
	$blog_table_name = $wpdb->prefix."posts";
	restore_current_blog();

	$mood_history = $wpdb->get_results( "SELECT time, mood, post_content, post_date FROM $table_name AS m, $blog_table_name AS p WHERE m.blog_id = $my_blog AND m.post_id = p.id ORDER BY time DESC LIMIT $max_moods" );
	$mood_history = array_reverse($mood_history);

	return $mood_history;
}


// Display a graph of the past moods with flot javascript library
function display_moods_graph($blognames, $values) {
	?>

<!--[if lt IE 9]><script language="javascript" type="text/javascript" src="<?php echo plugins_url(); ?>/eb-enquiryblogbuilder/flot/excanvas.min.js"></script><![endif]-->

<div id="moods_placeholder" style="width:100%; height:200px; border:none;"></div>

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
				 $title = (substr($number->post_content, 0, 30));
				 echo '{title:"'.($title).'", url:"'.$blognames[$j][1].'?mood_view='.($date).'"}'; $i++; if ($i < count($value)) echo ', ';
			}
			$j++;
			echo "]";
			if ($j < count($values)) echo ', ';
		}
		echo "];";
		?>

    function plotGraphMoods() {
			jQuery.plot(jQuery("#moods_placeholder"), [ <?php echo $data; ?> ], {
				lines: { show: true },
				points: { show: true },
				grid: {clickable: true, hoverable: true},
				yaxis: {
					min: -0.5,
					max: 4.5,
					ticks: [[0, ":-("], [1,":-/"], [2,":-|"], [3,":-)"], [4,":-D"]]
				},
				xaxis: {mode: "time" }
			});
    }


    jQuery("#moods_placeholder").bind("plotclick", function (event, pos, item) {
			if (item) {
				window.open(tooltip[item.seriesIndex][item.dataIndex].url, "_self", null, true);
			}
    });

    function showTooltip(x, y, label) {
			jQuery('<div id="tooltip">' + (label) + '</div>').css( {
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

					showTooltip(x, y, tooltip[item.seriesIndex][item.dataIndex].title);
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


// Display the mood control - only show for a logged in user on their primary blog
function display_mood_view($args) {
	global $blog_id, $blog, $_POST;

	$current_mood = get_my_mood();

	$options = array(":-D Going great",":-) Smooth sailing",":-| Feeling challenged",":-/ I'm stuck", ":-( It's a disaster");

	extract($args);
	$title = "Mood View";
	echo $before_widget;
	echo $before_title.$title.$after_title;

	$user = get_userdata( $current_user->ID );

	$blognames = array();
	$values = array();

	$blognames[] = array('', get_blogaddress_by_id( $blog_id ));
	$values[] = get_mood_history($blog_id);
	display_moods_graph($blognames, $values);

	if( is_user_logged_in() && current_user_can( 'publish_posts' ) && ($blog_id == get_user_meta( get_current_user_id(), 'primary_blog', true ))) {
		?>

		<script language="javascript" type="text/javascript">

		jQuery(function() {
			jQuery('#mood_change').click(function() {
				jQuery('#mood_form').show();
				jQuery('#mood_change').hide();
			});
		});
		</script>

		<form method="post" action="">
			<div style="display:none" id="mood_form">
				<select style="width: 100%;" name="mood_select" id="mood_select">

				<?php
				$i = count($options);
				foreach ($options as $option) {
					$i--;
					echo "<option ";
					if ($i == $current_mood) echo 'selected="selected" ';
					echo "value='$i'>$option</option>";
				}
				?>

				<input type="hidden" name="action" value="post" />
				<?php wp_nonce_field( 'new-post' ); ?>
				I'm changing status because...
				<textarea name="posttext" id="posttext" rows="3" style="width: 100%;"></textarea>
				<input id="submit" type="submit" value="Set new mood &raquo;" />
			</div>
		</form>
		<input id="mood_change" type="submit" value="Choose a mood..." />

	<?php

	}
  else {
		echo "<p>My mood is ".$options[4-$current_mood]."</p>";
	}
  echo $after_widget;
}


// Plugin description details
function mood_view_details() {
  $widget_ops = array('classname' => 'mood_view_widget', 'description' => "Displays your current mood." );
  wp_register_sidebar_widget('mood_view_widget', 'Mood View', 'display_mood_view', $widget_ops);
}
add_action("plugins_loaded", "mood_view_details");


// Add a new mood line to the mood table and add the post too
function update_mood() {
	global $wpdb, $blog_id, $current_user;

	if( 'POST' == $_SERVER['REQUEST_METHOD'] && !empty( $_POST['action'] ) && $_POST['action'] == 'post' )  {

		$mood = $_POST['mood_select'];


		if( !current_user_can( 'publish_posts' ) ) {
			wp_redirect( get_bloginfo( 'url' ) . '/' );
			exit;
		}

		// FIXME: These items could be in a database somewhere
		$options = array(":-( It's a disaster",":-/ I'm stuck",":-| Feeling challenged",":-) Smooth sailing", ":-D Going great");

		check_admin_referer( 'new-post' );
		$user_id = $current_user->user_id;
		$post_content = $_POST['posttext'];
		$post_title = "I'm changing my mood to ".$options[$mood];
		$post_id = wp_insert_post( array(
			'post_author' => $user_id,
			'post_title' => $post_title,
			'post_content' => $post_content,
			'post_status' => 'publish'
			) );

		$primary_blog = $blog_id;
		switch_to_blog(1);
		$table_name = $wpdb->prefix."moods";
		$rows_affected = $wpdb->insert( $table_name, array( 'post_id' => $post_id, 'time' => current_time('mysql'), 'blog_id' => $primary_blog, 'mood' => $mood ) );
		restore_current_blog();

		wp_redirect( get_bloginfo( 'url' ) . '/' );
		exit;
	}
}
add_action("wp_loaded", "update_mood");


// When a post is deleted, delete the associated mood too
function remove_mood($post_id) {
	global $wpdb, $blog_id, $current_user;

	$primary_blog = $blog_id;
	switch_to_blog(1);
	$table_name = $wpdb->prefix."moods";
	$rows_affected = $wpdb->query( "DELETE FROM $table_name WHERE post_id = $post_id AND blog_id = $primary_blog" );
	restore_current_blog();
}
add_action("deleted_post", "remove_mood");


// When activated, create the mood table in the database to store each change of mood from all users.
function mood_view_init() {
	global $wpdb;

	switch_to_blog(1);
	$table_name = $wpdb->prefix."moods";

	if($wpdb->get_var("show tables like '$table_name'") != $table_name) {

		$sql = "CREATE TABLE IF NOT EXISTS " . $table_name . " (
			id mediumint(9) NOT NULL AUTO_INCREMENT,
			time datetime NOT NULL,
			blog_id bigint(20) NOT NULL,
			post_id bigint(20) NOT NULL,
			mood tinyint(2) NOT NULL,
			PRIMARY KEY (id),
			UNIQUE KEY id (id)
		);";

		$wpdb->query($sql);
	}
	restore_current_blog();
}
register_activation_hook(__FILE__,'mood_view_init');


// Filter posts from a particular time and those older.
function posts_where( $where ) {

	global $wpdb;

	if ( isset( $_GET['mood_view'] ) && !empty( $_GET['mood_view'] ) && intval( $_GET['mood_view'] ) != 0 ) {
		$timestamp = urldecode($_GET['mood_view']);
		$where .= " AND (post_date <= '$timestamp')";
	}

	return $where;
}
add_filter( 'posts_where' , 'posts_where' );


// Allow user to set the number of points displayed (default = 30)
function mood_view_control() {

	$default_points = 10;
	$max_points = 30;
	$min_points = 3;

	$options = get_option("widget_mood_view");
  if (!is_array( $options )) {
		$options = array('points' => $default_points);
  }

	if ($_POST['mood_view-submit']) {
		$options['points'] = intval(htmlspecialchars($_POST['mood_view-points']));
		if ($options['points'] < $min_points) $options['points'] = $min_points;
		if ($options['points'] > $max_points) $options['points'] = $max_points;
		update_option("widget_mood_view", $options);
	}

?>
	<p>
		<label for="mood_view-points">Number of past moods to display <br />(between <?php echo $min_points; ?> and <?php echo $max_points; ?>): </label><br /><br />
		<input type="text" id="mood_view-points" name="mood_view-points" value="<?php echo $options['points'];?>" />
		<input type="hidden" id="mood_view-submit" name="mood_view-submit" value="1" />
  </p>
<?php
}
register_widget_control('mood_view_widget', 'mood_view_control', 200, 200 );


// Add the flot script
function mood_view_scripts() {
		$path = plugin_dir_url(__FILE__);
   wp_enqueue_script( 'flot', $path.'flot/jquery.flot.min.js', array('jquery') );
   wp_enqueue_script( 'flot-time', $path.'flot/jquery.flot.time.js', array('jquery', 'flot') );
}
add_action('init', 'mood_view_scripts');

?>