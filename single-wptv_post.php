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

    <?php while (have_posts()) : the_post();
        global $post; ?>

        <div class="thumbnail">
            <?php the_post_thumbnail(); ?>
        </div>

        <?php the_terms($post->ID, 'wptv_category'); ?>
        <h1 class="entry-title">
            <?php the_title(); ?>
            <?php echo get_post_meta($post->ID, 'version', true); ?>
            <?php echo get_post_meta($post->ID, 'remarks', true); ?>
        </h1>

        <div class="entry-content">
            <?php the_content(); ?>
        </div>

        <div class="entry-attrs">
            <?php
            wptv_vod_attr_row('wptv_director', __('Director', 'wptv'), 'term');
            wptv_vod_attr_row('wptv_writer', __('Writer', 'wptv'), 'term');
            wptv_vod_attr_row('wptv_actor', __('Actor', 'wptv'), 'term');
            wptv_vod_attr_row('wptv_area', __('Area', 'wptv'), 'term');
            wptv_vod_attr_row('wptv_lang', __('Language', 'wptv'), 'term');
            wptv_vod_attr_row('pubdate', __('Publish Date', 'wptv'), 'post_meta');
            wptv_vod_attr_row('duration', __('Duration', 'wptv'), 'post_meta');
            wptv_vod_attr_row('douban_id', __('Douban ID', 'wptv'), 'post_meta');

            $douban_id = $post->douban_id;
            if ($douban_id) {
                printf('<a href="%s">%s</a>', 'https://movie.douban.com/subject/' . $douban_id . '/', __('豆瓣'));
            }

            ?>
        </div>

        <div class="player">
            <video id="video" controls hidden></video>
        </div>

        <?php wptv_vod_play_urls($post->ID); ?>

    <?php endwhile; ?>

</div>

<?php get_footer();
