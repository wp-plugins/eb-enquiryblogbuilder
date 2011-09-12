<?php
/**
 * Template Name: My Group Members
 *
 * @package Suffusion
 * @subpackage Templates
 *
 * Lists all the blogs in the current blog's group, starting with the group leader
 * If this template is used, any content stored is ignored.
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


get_header();
?>

<div id="main-col">
<?php
suffusion_page_navigation();
suffusion_before_begin_content();
?>
	<div id="content">
<?php
global $post;
if (have_posts()) {
	while (have_posts()) {
		the_post();
		$original_post = $post;
?>
		<div <?php post_class('fix'); ?> id="post-<?php the_ID(); ?>">
<?php suffusion_after_begin_post(); ?>
			<div class="entry-container fix">
				<div class="entry fix">

				<?php
				// Find out which group we are in
					$my_blog = $blog_id;
					
					switch_to_blog(1);
					$table_name = $wpdb->prefix."group_members";
					
					//echo 'my blog : '.$my_blog.'<br>';
					$group = $wpdb->get_var($wpdb->prepare("SELECT group_id FROM $table_name WHERE blog_id = %d",$my_blog));
					//echo 'This blog is part of group : '.$group.'<br>';
					$blog_results = $wpdb->get_results($wpdb->prepare("SELECT blog_id, leader FROM $table_name WHERE group_id = %d ORDER BY leader DESC", $group));

					$leaders = true;
					echo '<h2>Group leader</h2>';
					
					foreach ($blog_results as $blog) {
						if ($leaders && $blog->leader != 1) {
							$leaders = false;
							echo '<h2>Group members</h2>';
						}

						$bloginfo = get_blog_details((int) $blog->blog_id, true);
						echo '<a href="'.$bloginfo->siteurl.'">'.$bloginfo->blogname.'</a></br>';
					}					
					
					restore_current_blog();
			?>

				</div><!--/entry -->
			<?php
				// Due to the inclusion of Ad Hoc Widgets the global variable $post might have got changed. We will reset it to the original value.
				$post = $original_post;
				suffusion_after_content();
			?>
			</div><!-- .entry-container -->
<?php
suffusion_before_end_post();
comments_template();
?>

	</div><!--/post -->

<?php
	}
} 
?>
</div></div>
	<?php get_footer();
	?>
