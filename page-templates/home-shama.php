<?php

/**
 * Template Name: Home Shama
 */

use WPTV\DoubanMovieSearchApi;

get_header();

?>

<div class="ct-container">




    <?php
    $tags = [
        '短剧',
    ];

    foreach ($tags as $tag) {
        $query_args = [
            'type' => 'tv',
            'tags' => $tag,
            'limit' => 100
        ];
        $query = http_build_query($query_args);
        $cache_key = md5($query);

        $transient = 'douban_search_subjects_' . $cache_key;
        $douban_items = get_transient($transient);
        if (!$douban_items) {
            $douban_items  = DoubanMovieSearchApi::new_search_subjects($query_args);
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

    $parent = get_term_by('name', '爽文短剧', 'wptv_category')->term_id;

    $cat_names = get_terms([
        'taxonomy' => 'wptv_category',
        'parent' => $parent,
        'fields' => 'names',
        'hide_empty' => false,
    ]);

    // var_dump($cat_names);

    foreach ($cat_names as $cat_name) {
    ?>
        <section>
            <h2><?php echo $cat_name; ?></h2>

            <div class="item-loop">
                <?php

                $args = [
                    'post_type' => 'wptv_entry',
                    'posts_per_page' => 24,
                    'orderby' => 'meta_value_num',
                    'tax_query' => [
                        [
                            'taxonomy' => 'wptv_category',
                            'terms' => [$cat_name],
                            'field' => 'name'
                        ]
                    ]
                ];
                $posts = get_posts($args);

                foreach ($posts as $post) {
                    get_template_part('template-parts/item');
                }
                ?>
            </div>
        </section>

    <?php } ?>




</div>

<?php get_footer();
