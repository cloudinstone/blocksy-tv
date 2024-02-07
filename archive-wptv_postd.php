<?php

/**
 * The template for displaying archive pages
 *
 * @link https://developer.wordpress.org/themes/basics/template-hierarchy/
 *
 * @package Blocksy
 */

get_header(); ?>

<div class="ct-container">


	<?php while (have_posts()) : the_post(); ?>

		<?php the_title(); ?>

	<?php endwhile; ?>

</div>

<?php get_footer();
