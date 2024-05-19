<?php

use DressPress\ChineseNumber\ChineseNumberHelper;
use WPTV\DoubanMoviePageParser;
use WPTV\DoubanMovieSearchApi;
use WPTV\Helper;
use WPTV\Objects\WPTVEntry;

function wptv_vod_attr_row($key, $label, $type = 'post_meta') {
    global $post;
?>
    <div class="attr-row">
        <span class="attr-key">
            <?php echo $label; ?>
        </span>
        <span class="attr-value">
            <?php
            if ($type == 'post_meta') {
                echo get_post_meta($post->ID, $key, true);
            } else {
                echo get_the_term_list($post->ID, $key, '', ',');
            }
            ?>
        </span>
    </div>
<?php }


function wptv_get_vod_source_list($post_id) {
    $entry = new WPTVEntry($post_id);
    return $entry->getSourceList();
}



function wptv_vod_source_list($post_id) {
    $entry = new WPTVEntry($post_id);
    $sources = $entry->getSourceList();


    echo '<section class="play-url-sources" data-post-id="' . $post_id . '">';

    if (empty($sources)) {
        echo '<div class="empty-state no-play-urls">';
        echo __('还没有播放地址', 'wptv');
        echo '</div>';
        return;
    }

    echo '<div class="source-toolbar">';
    echo '<div class="source-switch-container">';



    foreach ($sources as $i => $source) {
        if (!empty($source->provider_id))
            $source_provider = get_term_by('id', $source->provider_id, 'wptv_source_provider');


        $api_url = get_term_meta($source_provider->term_id, 'api_url', true);


        if (!empty($source->ext_id)) {
            $api_url = add_query_arg(['ac' => 'detail', 'ids' => $source->ext_id], $api_url);
        } else {
            $api_url = add_query_arg(['ac' => 'detail', 'wd' => get_the_title($post_id)], $api_url);
        }


        $api_url = get_rest_url(null, 'wptv/v1/view_json?url=' . urlencode($api_url));

        $site_link = '<a href="' . get_term_meta($source_provider->term_id, 'site_url', true) . '">网站</a>';
        $api_link = '<a href="' . $api_url . '">API</a>';


        $reimport_url = home_url('?action=source_import');




        if (!empty($source->ext_id)) {
            $reimport_url = add_query_arg([
                'providers' => $source_provider->term_id,
                'ids' => $source->ext_id,
                'redirect' => 0
            ], $reimport_url);
        } else {
            $reimport_url = add_query_arg([
                'providers' => $source_provider->term_id,
                'keyword' => get_the_title($post_id),
                'redirect' => 0
            ], $reimport_url);
        }

        $reimport_link = '<a href="' . $reimport_url . '">重新导入</a>';

        echo '<div class="play-url-source">';

        echo '<div class="source-header">';
        echo '<h4>' . $source_provider->name .  $site_link . $api_link . $reimport_link . '</h4>';
        echo '</div>';

        echo '<div class="source-body">';

        echo '<div class="play-url-list">';

        $episodes = $source->episodes;
        foreach ($episodes as $index => $src) {
            $play_id = $source->provider_id . '-' . ($index + 1);

            echo '<span class="play-url" data-id="' . $play_id . '" data-url="' . $src['url'] . '">' . $src['title'] . '</span>';
        }
        echo '</div>';

        echo '</div>';

        echo '</div>';
    }

    echo '</section>';
}


function get_play_url($post_id, $provider_id, $index) {
    $url = get_permalink($post_id);
    $url = trailingslashit($url);
    $url .= $provider_id . '-' . $index;

    return $url;
}


function wptv_get_items_by_douban_ids($douban_ids, $args = [], $sort_by_douban_ids = true) {
    $args = array_merge([
        'post_type' => 'wptv_entry',
        'posts_per_page' => 100,
        'meta_query' => [
            [
                'key' => 'douban_id',
                'compare' => 'IN',
                'value' => $douban_ids
            ]
        ],
        'fields' => 'ids',
    ], $args);

    $posts = get_posts($args);

    return $posts;
}

function wptv_get_douban_upcoming_items() {
    $transient = 'douban_upcoming_movies';
    $items = get_transient($transient);

    if (empty($items)) {
        $items = DoubanMoviePageParser::get_upcoming_items();

        if (!is_wp_error($items) && !empty($items)) {
            set_transient($transient, $items, 24 * HOUR_IN_SECONDS);
        }
    }

    return $items;
}


function wptv_get_douban_nowplaying_items() {
    $transient = 'douban_nowplaying_movies';

    // delete_transient($transient);
    $items = get_transient($transient);

    if (empty($items)) {
        $items = DoubanMoviePageParser::get_nowplaying_items();

        if (!is_wp_error($items) && !empty($items)) {
            set_transient($transient, $items, 24 * HOUR_IN_SECONDS);
        }
    }

    return $items;
}


function wptv_douban_search_subjects($transient, $params = [], $args = []) {
    // delete_transient($transient);

    $items = get_transient($transient);

    if (empty($items)) {
        $items = DoubanMovieSearchApi::search_subjects($params, $args);

        var_dump($items);

        if (!is_array($items))
            return;

        set_transient($transient, $items, 24 * HOUR_IN_SECONDS);

        /**
         * Update Douban score.
         */
        $douban_ids = array_map(function ($item) {
            return $item['id'];
        }, $items);

        $posts = wptv_get_items_by_douban_ids($douban_ids, [
            'posts_per_page' => 24
        ]);

        foreach ($posts as $post) {
            $douban_id = get_post_meta($post->ID, 'douban_id', true);

            $douban_item = reset(array_filter($items, function ($item) use ($douban_id) {
                return $item['id'] == $douban_id;
            }));

            if (!empty($douban_item['rate'])) {
                update_post_meta($post->ID, 'douban_score', $douban_item['rate']);
                update_post_meta($post->ID, 'api_douban_score', $douban_item['rate']);
            }
        }
    }

    return $items;
}



function wpse_11826_search_by_title($search, $wp_query) {
    var_dump($search);

    return $search;

    if (!empty($search) && !empty($wp_query->query_vars['search_terms'])) {
        global $wpdb;

        $q = $wp_query->query_vars;
        $q['exact'] = 1;
        $n = !empty($q['exact']) ? '' : '%';

        $search = array();

        foreach ((array) $q['search_terms'] as $term)
            $search[] = $wpdb->prepare("$wpdb->posts.post_title LIKE %s", $n . $wpdb->esc_like($term) . $n);

        if (!is_user_logged_in())
            $search[] = "$wpdb->posts.post_password = ''";

        $search = ' AND ' . implode(' AND ', $search);
    }

    return $search;
}

add_filter('post_search_columns', function ($columns) {
    // $columns = [
    //     'post_title'
    // ];

    return $columns;
});
