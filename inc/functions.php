<?php

use DressPress\ChineseNumber\ChineseNumberHelper;
use WPTVCore\DoubanMoviePageParser;
use WPTVCore\DoubanMovieSearchApi;
use WPTVCore\Helpers;

function parse_season_title($title) {
    preg_match('/(.*?)第(.*?)季/', $title, $match);

    if (!$match)
        return null;

    $name = trim($match[1]);
    $season = $match[2];
    $season = ChineseNumberHelper::toNumber($season);

    return [
        'series_name' => $name,
        'season_number' => $season
    ];
}

function find_season_posts_by_title($title) {
    $season_data = parse_season_title($title);

    if (!$season_data)
        return [];

    $args = [
        'post_type' => 'wptv_post',
        'search_title' => $season_data['series_name'],
        // 'suppress_filters' => true,
        'ignore_sticky_posts' => true,
        'no_found_rows' => true
    ];

    $query = new WP_Query($args);

    $posts = $query->posts;

    $season_posts = [];
    foreach ($posts as $post) {
        $season_data = parse_season_title($post->post_title);

        if ($season_data) {
            $post->series_name = $season_data['series_name'];
            $post->season_number = $season_data['season_number'];
            $season_posts[] = $post;
        }
    }

    usort($season_posts, function ($a, $b) {
        return $a->season_number > $b->season_number;
    });

    wp_reset_query();

    return $season_posts;
}

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

function wptv_get_vod_source_list($post_id, $srcset_type = 'string') {
    $source_list = get_post_meta($post_id, 'source_list', true);

    if (empty($source_list)) {
        return [];
    }

    $is_json = json_validate($source_list);

    if ($is_json) {
        $source_list = json_decode($source_list, true);
    }

    if (!is_array($source_list)) {
        return [];
    }

    foreach ($source_list as $key => $group) {
        // some srcset may contains whitespaces or redundant delimiters.
        $srcset = trim($group['srcset'], ' #\n\r\t\v\0');

        // Clean invalid group.
        if (empty($group['provider_id']) || empty($srcset)) {
            unset($source_list[$key]);
        }

        $source_list[$key]['srcset'] = $srcset;
    }

    if ($srcset_type == 'array') {
        foreach ($source_list as $key => $group) {
            $source_list[$key]['srcset'] = Helpers::parse_vod_srcset($group['srcset']);
        }
    }

    return $source_list;
}

function wptv_vod_source_list($post_id) {
    $groups = wptv_get_vod_source_list($post_id, 'array');
    $groups = array_values($groups);

    // var_dump($groups);

    echo '<section class="play-url-groups" data-post-id="' . $post_id . '">';

    if (empty($groups)) {
        echo '<div class="empty-state no-play-urls">';
        echo __('还没有播放地址', 'wptv');
        echo '</div>';
        return;
    }

    echo '<div class="source-toolbar">';
    echo '<div class="source-provider-switch-container">';



    $post = get_post($post_id);



    echo '<ul class="source-provider-switch-list">';

    foreach ($groups as $i => $group) {
        $class = '';
        if ($i == 0) {
            $class = 'current';
        }
        if (!empty($group['provider_id']))
            $provider = get_term_by('id', $group['provider_id'], 'wptv_provider');
        elseif (!empty($group['provider']))
            $provider = get_term_by('slug', $group['provider'], 'wptv_provider');



        echo '<li class="' . $class . '">' . $provider->name . '</li>';
    }
    echo '</ul></div>';
    echo '</div>';

    foreach ($groups as $i => $group) {
        if (!empty($group['provider_id']))
            $provider = get_term_by('id', $group['provider_id'], 'wptv_provider');
        elseif (!empty($group['provider']))
            $provider = get_term_by('slug', $group['provider'], 'wptv_provider');

        // var_dump($provider);

        $api_url = get_term_meta($provider->term_id, 'api_url', true);


        if ($group['srcid']) {
            $api_url = add_query_arg(['ac' => 'detail', 'ids' => $group['srcid']], $api_url);
        } else {
            $api_url = add_query_arg(['ac' => 'detail', 'wd' => get_the_title($post_id)], $api_url);
        }




        $api_url = get_rest_url(null, 'wptv/v1/view_json?url=' . urlencode($api_url));

        $api_link = '<a href="' . $api_url . '">API</a>';



        $reimport_url = 'http://hdzy.local/wp-admin/admin.php?page=wptv-import';
        $reimport_url = add_query_arg([
            'provider_id' => $provider->term_id,
            'keyword' => get_the_title($post_id),
            'redirect' => 0
        ], $reimport_url);

        $reimport_link = '<a href="' . $reimport_url . '">重新导入</a>';

        echo '<div class="play-url-group">';

        echo '<div class="group-header">';
        echo '<h4>' . $provider->name .  $api_link . $reimport_link . '</h4>';
        echo '</div>';

        echo '<div class="group-body">';

        echo '<div class="play-url-list">';

        foreach ($group['srcset'] as $index => $src) {
            $play_id = $group['provider_id'] . '-' . ($index + 1);

            echo '<span class="play-url" data-id="' . $play_id . '" data-url="' . $src['url'] . '">' . $src['label'] . '</span>';
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
        'post_type' => 'wptv_post',
        'posts_per_page' => 100,
        'meta_query' => [
            [
                'key' => 'douban_id',
                'compare' => 'IN',
                'value' => $douban_ids
            ]
        ]
    ], $args);
    $posts = get_posts($args);

    if ($sort_by_douban_ids) {
        usort($posts, function ($a, $b) use ($douban_ids) {
            return array_search($a->douban_id, $douban_ids) > array_search($b->douban_id, $douban_ids);
        });
    }

    return $posts;
}

function wptv_get_douban_upcoming_to_theaters() {
    $transient = 'douban_upcoming_movies';
    $items = get_transient($transient);

    if (empty($items)) {
        $items = DoubanMoviePageParser::get_upcoming_to_theaters();

        set_transient($transient, $items, 24 * HOUR_IN_SECONDS);
    }

    return $items;
}


function wptv_get_douban_nowplaying_in_theaters() {
    $transient = 'douban_nowplaying_movies';

    $items = get_transient($transient);

    if (empty($items)) {
        $items = DoubanMoviePageParser::get_nowplaying_in_theaters();

        set_transient($transient, $items, 24 * HOUR_IN_SECONDS);
    }

    return $items;
}


function wptv_douban_search_subjects($transient, $params = [], $args = []) {
    // delete_transient($transient);

    $items = get_transient($transient);

    if (empty($items)) {
        $items = DoubanMovieSearchApi::search_subjects($params, $args);

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
