<?php

namespace WPTVTheme;

class Theme {
    public static function init() {

        add_action('wp_enqueue_scripts', [__CLASS__, 'enqueue']);

        add_filter('post_thumbnail_html', [__CLASS__, 'post_thumbnail_html'], 1, 5);
    }

    public static function enqueue() {
        wp_enqueue_style('blocksy-wptv', get_stylesheet_directory_uri() . '/build/css/theme.css');
        wp_enqueue_script('blocksy-wptv', get_stylesheet_directory_uri() . '/build/js/theme.js', [], false, ['in_footer' => false]);
    }

    public static function post_thumbnail_html($html, $post_id, $post_thumbnail_id, $size, $attr) {
        if (!empty($html))
            return $html;

        $thumbnail_url = get_post_meta($post_id, 'thumbnail_url', true);

        if (!empty($thumbnail_url))
            return '<a class="ct-media-container" href="' . get_permalink($post_id) . '"><img src="' . $thumbnail_url . '" loading="lazy" alt="' . get_the_title($post_id) . '" style="aspect-ratio: 5/7;" /></a>';

        return '';
    }
}
