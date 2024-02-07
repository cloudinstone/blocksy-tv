<?php

namespace WPTVTheme;

class Theme {
    public static function init() {
        add_action('wp_enqueue_scripts', [__CLASS__, 'enqueue']);
    }

    public static function enqueue() {
        wp_enqueue_script('theme', get_stylesheet_directory_uri() . '/build/js/theme.js', [], false, true);
    }
}
