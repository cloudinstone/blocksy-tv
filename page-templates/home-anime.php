<?php

/**
 * Template Name: Home Anime
 */

use WPTV\DoubanMovieSearchApi;
use WPTV\Helper;
use WPTV\TmdbApiClient;
use WPTVTheme\VodItemHelper;

get_header(); ?>

<div class="ct-container">

    <?php




    $list = [
        '' => '欧美',
        'JP' => '日本',
        'KR' => '韩国',
        'HK' => '香港',
        'TW' => '台湾',
        'CN' => '国产',
    ];

    foreach ($list as $code => $section_title) {
        $client = new TmdbApiClient();
        $data = $client->discover('tv', [
            'with_genres' => 16,
            'with_origin_country' => $code
        ]);
        $items = $data->results;

        // var_dump($items);


        $names = array_column($items, 'name');

        // var_dump($names);



        $posts = [];
        foreach ($items as $item) {
            $title = sprintf('%s 第一季', $item->name);
            $year = (int)$item->first_air_date;
            $slug = Helper::buildEntrySlug($title, $year, '');
            $post = get_page_by_path($slug, OBJECT, 'wptv_entry');

            if ($post) {
                $posts[] = $post;
            } else {
                // $keyword = preg_replace('/\s+.+/', '', $name);
                // $reimport_url = home_url('?action=bulk_import_by_keyword&keyword=' . $keyword);
                // printf('%s <a role="button" href="%s">全资源重新导入</a><br/>', $name, $reimport_url);
            }
        }
        $posts = array_filter($posts);

        get_template_part('template-parts/section-items', null, [
            'title' => $section_title,
            'posts' => $posts
        ]);
    }





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
            'post_type' => 'wptv_entry',
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
