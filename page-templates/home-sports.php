<?php

/**
 * Template Name: Home Sports
 */

use WPTVCore\DoubanMovieSearchApi;
use WPTVCore\VodItemDataSanitizer;

get_header();

$paged = get_query_var('paged');
var_dump($paged);
?>

<div class="ct-container">

    <?php

    $search_terms = [
        // '世界杯',
        '欧洲杯',
        '亚洲杯',

        'NBA',
        'CBA',

        '英超',

        '西甲',
        '意甲',
        '德甲',
        '法甲',

        '中超',
        '中甲',


    ];
    foreach ($search_terms as $search_term) {

    ?>

        <section>
            <h2><?php echo $search_term; ?></h2>

            <div class="match-list">
                <?php

                $args = [
                    'post_type' => 'wptv_video',
                    'posts_per_page' => 24,
                    's' => $search_term,
                    'tax_query' => [
                        [
                            'taxonomy' => 'wptv_category',
                            'terms' => ['体育'],
                            'field' => 'name'
                        ]
                    ]
                ];
                $posts = get_posts($args);

                foreach ($posts as $post) {
                    $title = get_the_title();

                    $data = VodItemDataSanitizer::parse_sports_title($title);

                    echo '<div>' . $title . '</div>';

                    echo '<a href="' . get_permalink($post->ID) . '">';
                    echo '<span class="date">' . $data['date'] . '</span>';
                    echo '<span class="home">' . $data['home'] . '</span>';
                    echo '<span class="vs">vs</span>';
                    echo '<span class="away">' . $data['away'] . '</span>';
                    echo '</a>';

                    // var_dump($data);
                }
                ?>
            </div>
        </section>

    <?php } ?>



    <section>
        <h2>NBA常规赛</h2>

        <div class="item-loop" data-item-ratio="1/1">
            <?php

            $args = [
                'post_type' => 'wptv_video',
                'posts_per_page' => 120,
                'tax_query' => [
                    [
                        'taxonomy' => 'wptv_category',
                        'terms' => ['体育'],
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
</div>

<?php get_footer();
