<?php

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

function wptv_vod_get_play_urls($post_id) {
    $play_url_groups = get_post_meta($post_id, 'play_urls', true);

    if (!is_array($play_url_groups) || empty($play_url_groups)) {
        $play_url_groups = [];
    } else {
        foreach ($play_url_groups as $key => $group) {
            // Clean invalid group.
            if (is_int($key) || empty($group['provider']) || empty($group['urls'])) {
                unset($play_url_groups[$key]);
            }
        }
    }

    return $play_url_groups;
}

function wptv_vod_play_urls($post_id) {
    $groups = wptv_vod_get_play_urls($post_id);

    echo '<section class="play-url-groups">';

    if (empty($groups)) {
        echo '<div class="empty-state no-play-urls">';
        echo __('还没有播放地址', 'wptv');
        echo '</div>';
        return;
    }

    foreach ($groups as $group) {
        $provider = get_term_by('slug', $group['provider'], 'wptv_provider');

        echo '<div class="play-url-group">';

        echo '<div class="group-header">';
        echo '<h3>' . $provider->name . '</h3>';
        echo '</div>';

        $lines = explode("\n", $group['urls']);

        echo '<div class="group-body">';
        foreach ($lines as $line) {
            $pair = explode('$', $line);
            $label = $pair[0];
            $url = $pair[1];
            echo '<span class="play-url" data-url="' . $url . '">' . $label . '</span>';
        }
        echo '</div>';

        echo '</div>';
    }

    echo '</div>';
}
