<?php



add_action('wp_enqueue_scripts', function () {
    wp_enqueue_style('blocksy-wptv', get_stylesheet_directory_uri() . '/build/css/theme.css');
    wp_enqueue_script('blocksy-wptv', get_stylesheet_directory_uri() . '/build/js/theme.js', [], false, ['in_footer' => true]);
});

require get_stylesheet_directory() . '/inc/functions.php';
