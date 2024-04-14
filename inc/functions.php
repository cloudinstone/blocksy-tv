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
        $lines = explode("\n", $group['urls']);

        $set = [];
        foreach ($lines as $line) {
            $pair = explode('$', $line);
            $set[] = [
                'label' => $pair[0],
                'url' => $pair[1]
            ];
        }

        $source_url_groups[$key]['src_set'] = $set;
    }

    return $source_url_groups;
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

        echo '<div class="play-url-group">';

        echo '<div class="group-header">';
        echo '<h3>' . $provider->name . '</h3>';
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
