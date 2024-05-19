<?php

/**
 * Template Name: Home Docum
 */

use WPTV\DoubanMovieSearchApi;

get_header(); ?>

<div class="ct-container">

    <?php
    $tags = [
        '纪录片',
    ];

    foreach ($tags as $tag) {
        $query_args = [
            'type' => 'tv',
            'tag' => $tag,
            'page_limit' => 48
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
    }
    ?>

    <?php
    $regions = [
        '美国',
        '英国',
        '法国',
        '德国',
        '加拿大',
        '中国大陆',
    ];
    foreach ($regions as $region) {
        $query_args = [
            'post_type' => 'wptv_entry',
            'posts_per_page' => 24,
            'ignore_sticky_posts' => true,
            'no_found_rows' => true,
            'suppress_filters' => true,
            'tax_query' => [
                [
                    'taxonomy' => 'wptv_genre',
                    'terms' => '纪录片',
                    'field' => 'name'
                ],
                [
                    'taxonomy' => 'wptv_region',
                    'terms' => $region,
                    'field' => 'name'
                ]
            ]
        ];

        $query = new WP_Query($query_args);
    ?>

        <section>
            <h2><?php echo $region; ?></h2>

            <?php
            echo '<div class="item-loop">';
            while ($query->have_posts()) : $query->the_post();
                get_template_part('template-parts/item');
            endwhile;
            wp_reset_query();
            echo '</div>';

            ?>
        </section>

    <?php } ?>

    <?php
    $keywords = [
        'BBC',
        'Netflix',
        '国家地理',
        'Discovery',
        'HBO',
        'PBS'
    ];
    foreach ($keywords as $keyword) {
        $query_args = [
            'post_type' => 'wptv_entry',
            'posts_per_page' => 24,
            'ignore_sticky_posts' => true,
            's' => $keyword,
            'tax_query' => [
                [
                    'taxonomy' => 'wptv_genre',
                    'terms' => '纪录片',
                    'field' => 'name'
                ]
            ]
        ];

        $query = new WP_Query($query_args);

        // var_dump($query);

    ?>

        <section>
            <h2><?php echo $keyword; ?></h2>

            <?php
            echo '<div class="item-loop">';
            while ($query->have_posts()) : $query->the_post();
                get_template_part('template-parts/item');
            endwhile;
            wp_reset_query();
            echo '</div>';

            ?>
        </section>

    <?php } ?>


</div>

<?php get_footer();
