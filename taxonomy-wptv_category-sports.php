<?php

/**
 * Template Name: Sections
 */

use WPTVCore\DoubanMovieSearchApi;

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
                    'post_type' => 'wptv_post',
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


                    // 日期数字前加空格，有的标题没有空格
                    $title = preg_replace('/\d{4,}/', " $0", $title);
                    // $title = trim($title);

                    $data = [];

                    preg_match('/([^ ]+)vs([^ ]+)/i', $title, $vs_match);
                    if ($vs_match) {
                        $data['home'] = $vs_match[1];
                        $data['away'] = $vs_match[2];
                    }

                    $year = $month = $day = '';
                    preg_match('/(\d{4})(\d{2})(\d{2})/i', $title, $date_match);
                    if ($date_match) {
                        $year = $date_match[1];
                        $month = $date_match[2];
                        $day = $date_match[3];
                    } else {
                        preg_match('/(\d{1,2})月(\d{1,2})日/i', $title, $date_match);

                        if ($date_match) {
                            $month = $date_match[1];
                            $day = $date_match[2];
                        }

                        preg_match('/(\d{4})/i', $title, $year_match);
                        $year = $year_match ? $year_match[1] : '';
                    }

                    $data['date'] = implode('-', array_filter([$year, $month, $day]));




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
                'post_type' => 'wptv_post',
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
