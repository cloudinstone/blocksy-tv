<?php

use WPTVCore\DoubanMoviePageParser;
use WPTVCore\DoubanMovieSearchApi;
use WPTVCore\Helpers;

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

function wptv_vod_get_source_urls($post_id) {
    $source_url_groups = get_post_meta($post_id, 'source_urls', true);

    if (!is_array($source_url_groups) || empty($source_url_groups)) {
        $source_url_groups = [];
    } else {
        foreach ($source_url_groups as $key => $group) {
            // Clean invalid group.
            if (empty($group['provider_id']) || empty($group['urls'])) {
                unset($source_url_groups[$key]);
            }
        }
    }

    foreach ($source_url_groups as $key => $group) {
        $source_url_groups[$key]['srclist'] = Helpers::parse_vod_srcset($group['urls'], "\n");
    }

    return $source_url_groups;
}

function wptv_split_url_group($url_group) {
    $set = [];

    $lines = explode("\n", $url_group);
    foreach ($lines as $line) {
        $pair = explode('$', $line);
        $set[] = [
            'label' => $pair[0],
            'url' => $pair[1]
        ];
    }

    return $set;
}

function wptv_vod_source_urls($post_id) {
    $groups = wptv_vod_get_source_urls($post_id);
    $groups = array_values($groups);

    // var_dump($groups);

    echo '<section class="play-url-groups">';

    if (empty($groups)) {
        echo '<div class="empty-state no-play-urls">';
        echo __('还没有播放地址', 'wptv');
        echo '</div>';
        return;
    }

    echo '<div class="source-toolbar">';
    echo '<div class="source-provider-switch-container">';



    $post = get_post($post_id);


?>
    <md-filled-select>
        <md-select-option value="apple">
            <div slot="headline">Apple</div>
        </md-select-option>
        <md-select-option value="apricot">
            <div slot="headline">Apricot</div>
        </md-select-option>
    </md-filled-select>

<?php
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

        $api_url = add_query_arg(['ac' => 'detail', 'wd' => get_the_title($post_id)], $api_url);
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
        echo '<h3>' . $provider->name . $api_link . $reimport_link .  '</h3>';
        echo '</div>';

        $lines = explode("\n", $group['urls']);

        echo '<div class="group-body">';



        echo '<div class="play-url-list">';
        foreach ($lines as $index => $line) {
            $pair = explode('$', $line);
            $label = $pair[0];
            $url = $pair[1];

            $play_id = $group['provider_id'] . '-' . ($index + 1);

            echo '<span class="play-url" data-id="' . $play_id . '" data-url="' . $url . '">' . $label . '</span>';
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


function wptv_get_items_by_douban_ids($douban_ids, $args = []) {
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

        set_transient($transient, $items, 24 * HOUR_IN_SECONDS);
    }

    return $items;
}
