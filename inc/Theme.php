<?php

namespace WPTVTheme;

use WPTV\Helper;

class Theme {
    public static function init() {

        // high priority `999` to make sure that load after all blocksy scripts and styles.
        add_action('wp_enqueue_scripts', [__CLASS__, 'enqueue'], 999);

        add_filter('post_thumbnail_html', [__CLASS__, 'post_thumbnail_html'], 1, 5);


        add_action('blocksy:loop:before', [__CLASS__, 'filters']);

        add_action('blocksy:loop:card:end', [__CLASS__, 'loop_card_end']);
    }

    public static function loop_card_end() {
        global $post;


        echo get_post_meta($post->ID, 'pubdate', true) . "\n";


        echo get_the_term_list($post->ID, 'wptv_year') . "\n";
        echo get_the_term_list($post->ID, 'wptv_category') . "\n";

        $total = get_post_meta($post->ID, 'episode_serial', true);
        echo sprintf('共%s集', $total);
    }

    public static function filters() {
        if (!is_tax() && !is_tag() && !is_category() && !is_home() && !is_post_type_archive()) {
            return;
        }

        $genre_terms = get_terms([
            'taxonomy' => 'wptv_genre',
            // 'number' => 20,
            'orderby' => 'count',
            'order' => 'desc'
        ]);
        // var_dump($genre_terms);

        $cat_term = get_queried_object();
        $type = 'movie';

        $map = [
            'movie' => '电影',
            'drama' => '电视剧',
            'anime' => '动漫',
            'vshow' => '综艺',
            'shama' => '爽文短剧',
            'sports' => '体育',
            'docum' => '纪录片',
        ];

        if ($type = array_search($cat_term->name, $map)) {
            $genre_names = Helper::get_comman_genres($type);
            $terms = [];
            foreach ($genre_terms as $term) {
                if (in_array($term->name, $genre_names)) {
                    $terms[] = $term;
                }
            }
        } else {
            $terms = get_terms([
                'taxonomy' => 'wptv_genre',
                'number' => 20,
                'orderby' => 'count',
                'order' => 'desc'
            ]);
        }

        echo '<div class="filters">';
        self::display_filter_group($terms, 'wptv_genre', 'genre');
        self::term_list('wptv_region', 'region');
        self::term_list('wptv_lang', 'lang');
        self::term_list('wptv_year', 'years');
        echo '</div>';
    }

    public static function term_list($taxonomy, $query_var) {
        $terms = get_terms([
            'taxonomy' => $taxonomy,
            'number' => 20,
            'orderby' => 'count',
            'order' => 'desc'
        ]);

        $taxobj = get_taxonomy($taxonomy);

        /**
         * no `/page/3` in base_url
         */
        $base_url = false;

        // if (is_tax() || is_tag() || is_category()) {
        //     $base_url = get_term_link(get_queried_object_id());
        // } elseif (is_post_type_archive()) {
        //     global $post_type;
        //     $base_url = get_post_type_archive_link($post_type);
        // } elseif (is_home()) {
        //     $base_url = home_url('/');
        // }

        self::display_filter_group($terms, $taxonomy,  $query_var);
    }

    public static function display_filter_group($terms, $taxonomy,  $query_var) {
        $taxobj = get_taxonomy($taxonomy);

        printf('<div class="filter-group__header">%s</div>', $taxobj->label);

        echo '<div class="filter-group__body">';
        echo '<div class="filter-group__list ct-dynamic-filter">';
        $class = '' == get_query_var($query_var) ? 'active' : '';
        printf('<a class="%s" href="%s">%s</a>', $class, remove_query_arg($query_var), _x('全部', 'filter all label', 'wptv'));
        foreach ($terms as $term) {
            $class = $term->name == get_query_var($query_var) ? 'active' : '';

            $term_url = add_query_arg($query_var, $term->slug);

            printf('<a class="%s" href="%s">%s</a>', $class, $term_url, $term->name);
        }
        echo '</div>';
        echo '</div>';
    }

    public static function enqueue() {
        wp_enqueue_style('blocksy-wptv', get_stylesheet_directory_uri() . '/build/css/theme.css');
        wp_enqueue_script('blocksy-wptv', get_stylesheet_directory_uri() . '/build/js/theme.js', [], false, ['in_footer' => false]);

        wp_localize_script('blocksy-wptv', 'themeSettings', [
            'ajaxUrl' => admin_url('admin-ajax.php')
        ]);
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
