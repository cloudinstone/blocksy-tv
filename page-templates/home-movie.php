<?php

/**
 * Template Name: Home Movie
 */

use WPTVCore\DoubanMovieSearchApi;

get_header(); ?>

<div class="ct-container">

    <section>
        <h2>正在热映</h2>

        <div class="scrollable-container">
            <div class="item-loop scrollable-list">
                <?php

                // $items = wptv_get_douban_upcoming_to_theaters();

                $douban_items = wptv_get_douban_nowplaying_in_theaters();

                $douban_ids = array_map(function ($item) {
                    return $item['id'];
                }, $douban_items);

                $posts = wptv_get_items_by_douban_ids($douban_ids, [
                    'posts_per_page' => 24
                ]);

                foreach ($posts as $post) {
                    get_template_part('template-parts/item');
                }
                ?>
            </div>
        </div>
    </section>





    <?php
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
        echo ' <section>';

        echo '<h2>' . $tag . '</h2>';



        $query_args = [
            'tag' => $tag,
            'page_limit' => 24
        ];
        $query = http_build_query($query_args);
        $cache_key = base64_encode($query);

        $douban_items = wptv_douban_search_subjects('douban_subjects_' . $cache_key, $query_args);

        $douban_ids = array_map(function ($item) {
            return $item['id'];
        }, $douban_items);

        $posts = wptv_get_items_by_douban_ids($douban_ids, [
            'posts_per_page' => 24
        ]);


        echo '<div class="item-loop">';


        foreach ($posts as $post) {
            get_template_part('template-parts/item');
        }

        echo '</div>';

        echo '</section>';
    }

    ?>







</div>

<?php get_footer();
