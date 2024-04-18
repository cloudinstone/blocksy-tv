<?php

/**
 * Template Name: Sections
 */

use WPTVCore\DoubanMovieSearchApi;

get_header(); ?>

<div class="ct-container">






    <?php
    $cat_terms = [
        '国产动漫',
        '日韩动漫',
        '欧美动漫',
        '港台动漫'
    ];

    foreach ($cat_terms as $term_name) {
        $term = get_term_by('name', $term_name, 'wptv_category');

        // var_dump($term);

        echo ' <section>';

        echo '<h2>' . $term->name . '</h2>';


        $posts = get_posts([
            'post_type' => 'wptv_post',
            'posts_per_page' => 24,
            'tax_query' => [
                [
                    'taxonomy' => 'wptv_category',
                    'terms' => [$term->term_id]
                ]
            ]
        ]);


        echo '<div class="item-loop">';


        foreach ($posts as $post) {
            get_template_part('template-parts/item');
        }



        wp_reset_query();

        echo '</div>';

        echo '</section>';
    }

    ?>







</div>

<?php get_footer();
