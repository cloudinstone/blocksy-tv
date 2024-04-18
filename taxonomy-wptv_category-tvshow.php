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


    <div class="item-loop">
        <?php while (have_posts()) : the_post(); ?>

            <?php get_template_part('template-parts/item'); ?>

        <?php endwhile; ?>

    </div>

</div>

<?php get_footer();
