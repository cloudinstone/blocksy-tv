<?php

/**
 * Template Name: Home Movie
 */

use WPTV\DoubanMoviePageParser;
use WPTV\DoubanMovieSearchApi;

get_header();

?>

<div class="ct-container">
    <?php
    get_template_part('template-parts/section-douban-items', null, [
        'title' => __('即将上映', 'wptv'),
        'transient' => 'douban_upcoming_movies_post_ids',
        'douban_items' =>  wptv_get_douban_upcoming_items()
    ]);

    get_template_part('template-parts/section-douban-items', null, [
        'title' => __('正在热映', 'wptv'),
        'transient' => 'douban_nowplaying_movies_post_ids',
        'douban_items' =>  wptv_get_douban_nowplaying_items()
    ]);

    $tags = [
        '热门',
        '最新',
        '冷门佳片',
        '华语',
        '欧美',
        '韩国',
        '日本'
    ];

    foreach ($tags as $tag) {
        $query_args = [
            'tag' => $tag,
            'page_limit' => 50
        ];
        $query = http_build_query($query_args);
        $cache_key = md5($query);

        $transient = 'douban_search_subjects_' . $cache_key;
        $douban_items = get_transient($transient);
        if (!$douban_items) {
            $douban_items  = DoubanMovieSearchApi::search_subjects($query_args);
            set_transient($transient, $douban_items);
        }

        $transient = 'douban_search_subjects_post_ids_' . $cache_key;

        get_template_part('template-parts/section-douban-items', null, [
            'title' => $tag,
            'transient' => $transient,
            'douban_items' =>  $douban_items,
        ]);
    } ?>
</div>

<?php get_footer();
