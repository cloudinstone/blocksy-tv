<?php

namespace WPTVTheme;

require_once get_stylesheet_directory() . '/vendor/autoload.php';

require_once get_stylesheet_directory() . '/inc/AutoLoader.php';
new Autoloader('WPTVTheme', get_stylesheet_directory() . '/inc');

Theme::init();

require get_stylesheet_directory() . '/inc/functions.php';
