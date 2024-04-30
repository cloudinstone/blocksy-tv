<?php

namespace WPTVTheme;

class Theme {
    public static function init() {

        // high priority `999` to make sure that load after all blocksy scripts and styles.
        add_action('wp_enqueue_scripts', [__CLASS__, 'enqueue'], 999);

        add_filter('post_thumbnail_html', [__CLASS__, 'post_thumbnail_html'], 1, 5);


        add_action('blocksy:loop:before', [__CLASS__, 'filters']);
    }

    public static function filters() {
        if (!is_tax() && !is_tag() && !is_category() && !is_home() && !is_post_type_archive()) {
            return;
        }

        echo '<div class="filters">';
        self::term_list('wptv_genre', 'genre');
        self::term_list('wptv_region', 'region');
        self::term_list('wptv_lang', 'lang');
        self::term_list('wptv_year', 'years');
        echo '</div>';
    }

    public static function term_list($taxonomy, $query_var) {
        $year_terms = get_terms([
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


        printf('<div class="filter-group__header">%s</div>', $taxobj->label);

        echo '<div class="filter-group__body">';
        echo '<div class="filter-group__list ct-dynamic-filter">';
        $class = '' == get_query_var($query_var) ? 'active' : '';
        printf('<a class="%s" href="%s">%s</a>', $class, remove_query_arg($query_var), _x('全部', 'filter all label', 'wptv'));
        foreach ($year_terms as $term) {
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
