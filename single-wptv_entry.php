<?php

/**
 * The template for displaying archive pages
 *
 * @link https://developer.wordpress.org/themes/basics/template-hierarchy/
 *
 * @package Blocksy
 */

use WPTV\DoubanMovieSubjectParser;
use Brick\Schema\SchemaReader;
use Overtrue\Pinyin\Pinyin;
use WPTV\ChatAnywhereApi;
use WPTV\Controllers\WPTVEntryController;
use WPTV\DataCleaner;
use WPTV\DbMigrator;
use WPTV\DoubanApiClient;
use WPTV\DoubanApiDataImporter;
use WPTV\DoubanApiDataQuery;
use WPTV\DoubanBookSubjectParser;
use WPTV\DoubanMoviePageParser;
use WPTV\Helper;
use WPTV\Objects\WPTVEntry;
use WPTV\Objects\WPTVSource;
use WPTV\EntryExternalRef;
use WPTV\PostUpdater;
use WPTV\SourceListParser;
use WPTV\SourceProviderInspector;
use WPTV\Test;
use WPTV\TmdbApiClient;
use WPTV\TmdbApiDataFinder;
use WPTV\TmdbApiDataImporter;
use WPTV\TmdbApiDataQuery;
use WPTV\VodItemDataSanitizer;
use WPTV\VodTitleParser;
use WPTVTheme\VodItemHelper;

get_header();

global $post;
set_time_limit(0);


$entry = new WPTVEntry($post);

// $t = DoubanApiDataQuery::getSubjectBy('title', '男儿本色');
// var_dump($t);

// $t = DoubanApiDataImporter::findSubjectByTitle('男儿本色', 2024);
// var_dump($t);




$top_type = Helper::get_post_top_type($post);


if (in_array($entry->getScope(), ['series'])) {
    $tmdb_data = WPTVEntryController::getEntryTmdbData($post->ID);

    // var_dump($tmdb_data);

    if (!$tmdb_data) {
        TmdbApiDataFinder::updatePostTmdbData($post->ID);
    }
}




?>

<div class="ct-container">



    <?php while (have_posts()) : the_post();
        global $post;
    ?>



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

                <div class="episode-area">
                    <?php

                    $tmdb_id = EntryExternalRef::getEntryExternalRef($post->ID, 'tmdb_id');

                    if ($tmdb_id) {
                        $api_data = TmdbApiDataQuery::getSeason($tmdb_id);

                        // var_dump($api_data->episodes);


                        echo '<div class="episode-loop-container">';
                        echo '<div class="episode-loop" data-layout="flex">';



                        foreach ($api_data->episodes as $episode) {
                            get_template_part('template-parts/episode-item', null, $episode);
                        }
                        echo '</div>';
                        echo '</div>';
                    }
                    ?>
                </div>
            </div>



            <div class="info-area">

                <?php
                $season_id = EntryExternalRef::getEntryExternalRef($post->ID, 'tmdb_id');

                $season_posts_by_tmdb = Helper::getSeasonPostsByTmdb($post->ID);
                $season_posts_by_title = Helper::getSeasonPostsByTitle($post->post_title);

                if (count($season_posts_by_title) > count($season_posts_by_tmdb)) {
                    $season_posts = $season_posts_by_title;
                } else {
                    $season_posts = $season_posts_by_tmdb;
                }

                if (count($season_posts) > 1) {
                    echo '<select class="select-season" name="season">';
                    echo '<option disabled>' . __('选择该剧集的其它季', 'wptv') . '</option>';
                    foreach ($season_posts as $season_post) {
                        echo '<option value="' . esc_url(get_permalink($season_post->ID)) . '"' . selected($post->ID, $season_post->ID, false) . '>' . $season_post->post_title . '</option>';
                    }
                    echo '</select>';
                }

                $douban_id = get_post_meta($post->ID, 'douban_id', true);

                if ($douban_id) {
                    $post_ids = Helper::get_post_ids_by_douban_id($douban_id);

                    if ($post_ids) {
                        $version_posts = get_posts([
                            'post_type' => 'wptv_entry',
                            'post__in' => array_diff($post_ids, [$post->ID])
                        ]);

                        if ($version_posts) {
                            echo '<div class="other-versions">';
                            echo '<h2>其它版本</h2>';
                            echo '<ul class="other-versions-list">';
                            foreach ($version_posts as $post) {
                                echo '<li>';
                                the_title('<a href="' . get_permalink($post->ID) . '">', '</a>');
                                echo '</li>';
                            }
                            wp_reset_query();
                            echo '</ul>';

                            echo '</div>';
                        }
                    }
                }

                ?>


                <div class="source-area" data-post-id="<?php echo $post->ID; ?>">
                    <div class="source-message">

                    </div>

                    <div class="source-list-container">
                        <source-list id="source-list"></source-list>
                    </div>

                    <div class="episode-list-container">
                        <episode-list item-type="thumbnail" id="episode-list"></episode-list>
                    </div>
                </div>


                <div class="item-header">
                    <div class="thumbnail">
                        <?php the_post_thumbnail(); ?>
                    </div>

                    <div class="info">
                        <h1 class="title">
                            <?php
                            the_title();
                            ?>

                            <?php
                            $keyword = preg_replace('/\s+.+/', '', $post->post_title);
                            $reimport_url = home_url('?action=bulk_import_by_keyword&keyword=' . $keyword);
                            printf('<a role="button" href="%s">全资源重新导入</a>', $reimport_url);
                            ?>


                        </h1>

                        <h2>
                            <?php
                            $original_title = '';
                            if ($post->original_title) {
                                $original_title = $post->original_title;

                                if ($post->season_number) {
                                    $original_title .= ' ' . sprintf('Season %s', $post->season_number);
                                }
                            }

                            echo ' <span>' . $original_title . '</span>';
                            echo get_the_term_list($post->ID, 'wptv_year');
                            ?>
                        </h2>

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



                        $total =  get_post_meta($post->ID, 'episode_total', true);
                        $serial =  get_post_meta($post->ID, 'episode_serial', true);
                        if ($total) {
                            printf('共%s集', $total);
                        }
                        if ($serial) {
                            printf('更新到第%s集', $serial);
                        }

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

                            $tmdb_id = EntryExternalRef::getEntryExternalRef($post->ID, 'tmdb_id');

                            if ($tmdb_id) {
                                $tmdb_data = TmdbApiDataQuery::getSeason($tmdb_id);

                                if ($tmdb_data->series_id)
                                    echo Helper::get_tmdb_series_link($tmdb_data->series_id, $tmdb_data->season_number);
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

                // var_dump($related_posts);


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

                    $parser = new SourceListParser(wptv_get_vod_source_list($post->ID));

                    var_dump($parser);

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
