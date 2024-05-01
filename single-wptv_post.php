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
use WPTVCore\ChatAnywhereApi;
use WPTVCore\DataCleaner;
use WPTVCore\DoubanBookSubjectParser;
use WPTVCore\DoubanMoviePageParser;
use WPTVCore\Helpers;
use WPTVCore\SourceListParser;
use WPTVCore\SourceProviderInspector;
use WPTVCore\Test;
use WPTVCore\VodItemDataSanitizer;
use WPTVTheme\VodItemHelper;

get_header();



global $post;





set_time_limit(0);


// die;

$provider_id = (int)get_query_var('provider');

$episode = (int)get_query_var('episode');

$sources = wptv_get_vod_source_list($post->ID, 'string');



$first = reset($sources);

$provider_id = $first['provider_id'];

$episode = 1;
?>

<div class="ct-container">

    <?php while (have_posts()) : the_post();
        global $post;

        $src = $sources[$provider_id]['srcset'][$episode - 1];
    ?>

        <template id="source-list-item">
            <li>
                <span class="name"></span>
                <span class="episode-count"></span>
                <span class="speed"></span>
            </li>
        </template>

        <template id="episode-list-item">
            <li>
                <span class="name"></span>
            </li>
        </template>



        <div class="play-container">
            <div class="player-area">
                <div id="player">
                    <video id="video" src="" controls></video>
                </div>

                <div class="player-toolbar">
                    <button type="button" class="button mark-intro-end-time">标记当前视频时间为片头结束时间</button>
                </div>
            </div>

            <div class="info-area">

                <?php
                $season_posts = find_season_posts_by_title($post->post_title);

                echo '<select class="select-season" name="season">';
                echo '<option value="">' . __('选择该剧集的其它季', 'wptv') . '</option>';
                foreach ($season_posts as $season_post) {
                    echo '<option value="' . esc_url(get_permalink($season_post->ID)) . '"' . selected($post->ID, $season_post->ID, false) . '>' . $season_post->post_title . '</option>';
                }
                echo '</select>';
                ?>


                <div class="source-area" data-post-id="<?php echo $post->ID; ?>">
                    <div class="source-notice"></div>

                    <div class="source-list-container">
                        <ul class="source-list">
                        </ul>
                    </div>

                    <div class="episode-list-container">
                        <ul class="episode-list" hidden>
                        </ul>
                    </div>
                </div>


                <div class="item-header">
                    <div class="thumbnail">
                        <?php the_post_thumbnail(); ?>
                    </div>

                    <div class="info">
                        <h1 class="title">
                            <?php the_title(); ?>

                            <?php echo get_the_term_list($post->ID, 'wptv_year'); ?>


                        </h1>

                        <code><?php echo $post->post_name; ?></code>


                        <?php
                        $aka =  get_post_meta($post->ID, 'aka', true);
                        if ($aka) {
                            $aka = html_entity_decode($aka);
                            echo ' <h2 class="subtitle">' . $aka . '</h2>';
                        }
                        ?>

                        <?php echo get_the_term_list($post->ID, 'wptv_category'); ?>

                        <?php

                        echo VodItemHelper::get_remark_html($post);

                        ?>

                        <span class="item-rating">
                            <?php
                            $douban_id = get_post_meta($post->ID, 'douban_id', true);

                            if ($douban_id) {
                                printf(
                                    '<a href="%s">%s</a> <span>%s</span>',
                                    'https://movie.douban.com/subject/' . $douban_id . '/',
                                    __('豆瓣评分', 'wptv'),
                                    get_post_meta($post->ID, 'douban_score', true)
                                );
                            }
                            ?>
                        </span>
                    </div>
                </div>


                <?php
                $related_posts = null;

                $related_posts = yarpp_get_related([
                    'limit' => 12
                ]);

                // $cat_terms = get_the_terms($post->ID, 'wptv_category');

                // if (!empty($cat_terms)) {
                //     if ($cat_terms[0]->parent) {
                //         $parent_term = get_term($cat_terms[0]->parent, 'wptv_category');
                //     } else {
                //         $parent_term = $cat_terms[0];
                //     }

                //     $type = $parent_term->name; // 电影|电视剧|综艺

                //     delete_post_meta($post->ID, 'ai_related_douban_ids');
                //     $douban_ids = get_post_meta($post->ID, 'ai_related_douban_ids', true);
                //     if (empty($douban_ids)) {
                //         $api = new ChatAnywhereApi();
                //         $douban_ids = $api->get_related_douban_ids($post->post_title, $type);

                //         if (!empty($douban_ids)) {
                //             update_post_meta($post->ID, 'ai_related_douban_ids', $douban_ids);
                //         }
                //     }

                //     if (!empty($douban_ids)) {
                //         $douban_ids = explode(',', $douban_ids);

                //         $related_posts = wptv_get_items_by_douban_ids($douban_ids, [
                //             'posts_per_page' => 24
                //         ]);
                //     }
                // }





                if ($related_posts) {
                    echo '<div class="related-items" data-layout="list">';
                    foreach ($related_posts as $post) {
                        get_template_part('template-parts/item');
                    }
                    wp_reset_query();
                    echo '</div>';
                }







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
                    wptv_vod_source_list($post->ID);
                    ?>
                </div>

                <div class="content-panel" id="details-panel" role="tabpanel" aria-labelledby="details-tab" hidden>





                    <div class="entry-content">
                        <?php the_content(); ?>
                    </div>

                    <div class="entry-attrs">
                        <?php
                        wptv_vod_attr_row('directors', __('导演', 'wptv'), 'post_meta');
                        wptv_vod_attr_row('writers', __('编剧', 'wptv'), 'post_meta');
                        wptv_vod_attr_row('actors', __('主演', 'wptv'), 'post_meta');

                        wptv_vod_attr_row('wptv_genre', __('类型', 'wptv'), 'term');
                        wptv_vod_attr_row('wptv_region', __('地区', 'wptv'), 'term');
                        wptv_vod_attr_row('wptv_lang', __('语言', 'wptv'), 'term');

                        wptv_vod_attr_row('pubdate', __('上映日期', 'wptv'), 'post_meta');
                        wptv_vod_attr_row('duration', __('片长', 'wptv'), 'post_meta');
                        wptv_vod_attr_row('aka', __('又名', 'wptv'), 'post_meta');


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
