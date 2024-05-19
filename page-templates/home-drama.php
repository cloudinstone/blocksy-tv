<?php

/**
 * Template Name: Home Drama
 */

use WPTV\DoubanMovieSearchApi;

get_header();

?>

<div class="ct-container">


    <?php
    $tags = [
        '热门',
        '国产剧',
        // '综艺',
        '美剧',
        '日剧',
        '韩剧',
        '英剧',
        '港剧',
        '日本动画',
        '纪录片'
    ];


    /**
     * 一个豆瓣ID可能对应多篇文章，每个豆瓣ID仅显示一篇对应的文章
     */
    // $outputed_douban_ids = [];
    // foreach ($posts as $post) {
    //     $post = get_post($post);
    //     $douban_id = get_post_meta($post->ID, 'douban_id', true);

    //     // if (in_array($douban_id, $outputed_douban_ids)) {
    //     //     continue;
    //     // }

    //     $outputed_douban_ids[] = $douban_id;

    //     if (count($outputed_douban_ids) > 24)
    //         break;

    //     get_template_part('template-parts/item');
    // }

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

    $tags = [
        'Netflix',
        'HBO',
        'Disney+'
    ];

    foreach ($tags as $tag) {
    ?>
        <section>
            <h2><?php echo $tag; ?></h2>

            <div class="item-loop">
                <?php

                $args = [
                    'post_type' => 'wptv_entry',
                    'posts_per_page' => 24,
                    's' => $tag,
                    'meta_key' => 'douban_score',
                    'orderby' => 'meta_value_num',
                    'tax_query' => [
                        [
                            'taxonomy' => 'wptv_category',
                            'terms' => ['电视剧'],
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
