<?php

/**
 * The template for displaying archive pages
 *
 * @link https://developer.wordpress.org/themes/basics/template-hierarchy/
 *
 * @package Blocksy
 */

use WPTVCore\DoubanMovieSubjectParser;
use Brick\Schema\SchemaReader;
use Overtrue\Pinyin\Pinyin;
use WPTVCore\DataCleaner;
use WPTVCore\DoubanBookSubjectParser;
use WPTVCore\DoubanMoviePageParser;
use WPTVCore\Helpers;

get_header();





// DataCleaner::delete_unpopular_terms('wptv_director');






// $id = '36514643';

// $parser = new DoubanBookSubjectParser($id);
// $data = $parser->get_data();

// var_dump($data);
// die;

// $id  = '35736202';

// $parser = new DoubanMovieSubjectParser($id);
// $data = $parser->get_data();

// var_dump($data);


// $query = new WP_Query([
//     'post_type' => 'wptv_post',
//     'tax_query' => [
//         [
//             'taxonomy' => 'wptv_category',
//             'terms' => ['recap'],
//             'field' => 'slug'
//         ]
//     ],
//     'meta_query' => [
//         [
//             'key' => 'douban_id',
//             'compare' => 'EXISTS'
//         ]
//     ]
// ]);

// var_dump($query->found_posts);



$provider_id = (int)get_query_var('provider');

$episode = (int)get_query_var('episode');


$sources = wptv_vod_get_source_urls($post->ID);


echo '<ul class="m3u8-urls">';
foreach ($sources as $group) {
    $provider = get_term($group['provider_id'], 'wptv_provider');

    $first_url = $group['srclist'][0]['url'];

    printf('<li>%s: <a title="%s" href="%s">%s</a></li>', $provider->name, $provider->name, $first_url, $first_url);
    // $content = file_get_contents($first_url);
    // var_dump($content);
}
echo '</ul>';


$first = reset($sources);

// var_dump($first);

$provider_id = $first['provider_id'];

$episode = 1;





?>

<div class="ct-container">

    <?php while (have_posts()) : the_post();
        global $post;

        var_dump($post);


        // var_dump($sources);

        $src = $sources[$provider_id]['src_set'][$episode - 1];

        // var_dump($src);

        // $src['url'] = 'http://commondatastorage.googleapis.com/gtv-videos-bucket/sample/BigBuckBunny.mp4';
        // echo do_shortcode('[video src="' . $src['url'] . '"]');
    ?>


        <div class="play-container">
            <div class="player-area">
                <div class="player">
                    <video id="video" data-src="<?php echo $src['url']; ?>" controls></video>

                </div>
            </div>



            <div class="info-area">

                <h1 class="entry-title">
                    <?php the_title(); ?>
                    <?php echo get_the_term_list($post->ID, 'wptv_year'); ?>
                </h1>

                <mark><?php echo get_post_meta($post->ID, 'remarks', true); ?></mark>

                <?php
                // var_dump($post);
                $douban_id = get_post_meta($post->ID, 'douban_id', true);
                // var_dump($douban_id);
                if ($douban_id) {
                    printf('<a href="%s">%s</a>', 'https://movie.douban.com/subject/' . $douban_id . '/', __('豆瓣', 'wptv'));
                }

                echo $post->douban_score;
                ?>



                <?php

                $tabs = [
                    'sources' => __('选集', 'wptv'),
                    'details' => __('详情', 'wptv'),
                    'related' => __('相关', 'wptv'),
                    'comments' => __('评论', 'wptv'),
                ];
                ?>
                <md-tabs class="content-tabs">
                    <?php
                    foreach ($tabs as $id => $label) {
                        echo '<md-primary-tab id="' . $id . '-tab" aria-controls="' . $id . '-panel">' . $label . '</md-primary-tab>';
                    }
                    ?>
                </md-tabs>

                <div class="content-panel" id="sources-panel" role="tabpanel" aria-labelledby="sources-tab">
                    <?php
                    wptv_vod_source_urls($post->ID);
                    ?>
                </div>

                <div class="content-panel" id="details-panel" role="tabpanel" aria-labelledby="details-tab" hidden>

                    <div class="thumbnail">
                        <?php the_post_thumbnail(); ?>
                    </div>



                    <div class="entry-content">
                        <?php the_content(); ?>
                    </div>

                    <div class="entry-attrs">
                        <?php
                        wptv_vod_attr_row('directors', __('导演', 'wptv'), 'post_meta');
                        wptv_vod_attr_row('writers', __('编剧', 'wptv'), 'post_meta');
                        wptv_vod_attr_row('actors', __('主演', 'wptv'), 'post_meta');

                        wptv_vod_attr_row('wptv_category', __('类型', 'wptv'), 'term');
                        wptv_vod_attr_row('wptv_area', __('地区', 'wptv'), 'term');
                        wptv_vod_attr_row('wptv_lang', __('语言', 'wptv'), 'term');

                        wptv_vod_attr_row('pubdate', __('上映日期', 'wptv'), 'post_meta');
                        wptv_vod_attr_row('duration', __('片长', 'wptv'), 'post_meta');
                        wptv_vod_attr_row('aka_names', __('又名', 'wptv'), 'post_meta');


                        echo '</br>';
                        ?>
                    </div>
                </div>

                <div class="content-panel" id="related-panel" role="tabpanel" aria-labelledby="related-tab" hidden>
                    related
                </div>

                <div class="content-panel" id="comments-panel" role="tabpanel" aria-labelledby="comments-tab" hidden>
                    <?php

                    echo 'comments';
                    ?>
                </div>
            </div>
        </div>

    <?php endwhile; ?>

</div>

<?php get_footer();
