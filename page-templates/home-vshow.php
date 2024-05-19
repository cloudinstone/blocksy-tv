<?php

/**
 * Template Name: Home Vshow
 */

use WPTV\DoubanMovieSearchApi;

get_header(); ?>

<div class="ct-container">

    <?php
    $tags = [
        '综艺',
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

            // var_dump($douban_items);
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
    $genres = [
        '真人秀',
        '脱口秀',
    ];
    foreach ($genres as $genre) {
        $query_args = [
            'post_type' => 'wptv_entry',
            'posts_per_page' => 24,
            'ignore_sticky_posts' => true,
            'tax_query' => [
                [
                    'taxonomy' => 'wptv_genre',
                    'terms' => $genre,
                    'field' => 'name'
                ]
            ]
        ];

        $query = new WP_Query($query_args);

        // var_dump($query);

    ?>

        <section>
            <h2><?php echo $genre; ?></h2>

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
